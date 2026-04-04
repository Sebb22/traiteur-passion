<?php
declare (strict_types = 1);

namespace App\Services;

final class MenuImageService
{
    private const DESKTOP_WIDTH = 1200;
    private const MOBILE_WIDTH  = 600;
    private const WEBP_QUALITY  = 82;
    private const PREVIEW_WIDTH = 320;

    private string $projectRoot;
    private string $uploadDir;
    private string $sourceDir;
    private string $rembgBinaryPath;
    private string $rembgPreviewModel;
    private string $rembgFinalModel;

    public function __construct(?string $projectRoot = null)
    {
        $this->projectRoot       = $projectRoot ?? dirname(__DIR__, 2);
        $this->uploadDir         = $this->projectRoot . '/public/uploads/pages/menu';
        $this->sourceDir         = $this->uploadDir . '/sources';
        $this->rembgBinaryPath   = $this->projectRoot . '/.venv-rembg/bin/rembg';
        $this->rembgPreviewModel = getenv('REMBG_PREVIEW_MODEL') ?: 'u2netp';
        $this->rembgFinalModel   = getenv('REMBG_FINAL_MODEL') ?: 'u2net';
    }

    public function hasUploadedImage(array $file): bool
    {
        return isset($file['error']) && (int) $file['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * @return array{imagemagick: bool, rembg: bool, preview_ready: bool, final_ready: bool, preview_model: string, final_model: string, rembg_binary_path: string}
     */
    public function getRuntimeStatus(): array
    {
        $hasImageMagick = $this->canUseImageMagickCli();
        $hasRembg       = $this->canUseRembg();

        return [
            'imagemagick'       => $hasImageMagick,
            'rembg'             => $hasRembg,
            'preview_ready'     => $hasImageMagick && $hasRembg,
            'final_ready'       => $hasImageMagick,
            'preview_model'     => $this->rembgPreviewModel,
            'final_model'       => $this->rembgFinalModel,
            'rembg_binary_path' => $this->rembgBinaryPath,
        ];
    }

    /**
     * @param array<string, mixed> $file
     * @param array{remove_background?: bool, background_fuzz?: int, preview_width?: int, preview_model?: string} $options
     */
    public function generatePreviewDataUri(array $file, array $options = []): string
    {
        $this->assertUploadIsValid($file);
        $this->ensureDirectories();

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $mime    = $this->detectMimeType($tmpPath);
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new \RuntimeException('Format non supporté. Utilise JPG, JPEG, PNG ou WEBP.');
        }

        if (! $this->canUseImageMagickCli()) {
            throw new \RuntimeException('Aperçu temps réel indisponible: ImageMagick manquant.');
        }

        $removeBackground = (bool) ($options['remove_background'] ?? false);
        if ($removeBackground && ! $this->canUseRembg()) {
            throw new \RuntimeException('Aperçu temps réel indisponible: rembg manquant.');
        }

        $previewWidth = (int) ($options['preview_width'] ?? self::PREVIEW_WIDTH);
        $previewModel = (string) ($options['preview_model'] ?? $this->rembgPreviewModel);

        $tmpOrientedPath = $this->sourceDir . '/.tmp-preview-oriented-' . uniqid('', true) . '.png';
        $tmpCutoutPath   = $this->sourceDir . '/.tmp-preview-cutout-' . uniqid('', true) . '.png';
        $tmpPreviewPath  = $this->sourceDir . '/.tmp-preview-result-' . uniqid('', true) . '.png';

        try {
            $this->runCommand([
                'convert',
                $tmpPath,
                '-auto-orient',
                'PNG32:' . $tmpOrientedPath,
            ]);

            if ($removeBackground) {
                $this->runRembg($tmpOrientedPath, $tmpCutoutPath, $previewModel);
            } else {
                $this->runCommand([
                    'convert',
                    $tmpOrientedPath,
                    '-strip',
                    'PNG32:' . $tmpCutoutPath,
                ]);
            }

            $this->runCommand([
                'convert',
                $tmpCutoutPath,
                '-resize',
                $previewWidth . 'x',
                '-strip',
                'PNG32:' . $tmpPreviewPath,
            ]);

            $previewBytes = @file_get_contents($tmpPreviewPath);
            if (! is_string($previewBytes) || $previewBytes === '') {
                throw new \RuntimeException('Aperçu vide.');
            }

            return 'data:image/png;base64,' . base64_encode($previewBytes);
        } finally {
            foreach ([$tmpOrientedPath, $tmpCutoutPath, $tmpPreviewPath] as $tmpFile) {
                if (is_file($tmpFile)) {
                    @unlink($tmpFile);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $file
     * @param array{remove_background?: bool, background_fuzz?: int} $options
     * @return array{desktop_path: string, mobile_path: string, source_png_path: string}
     */
    public function processItemImage(array $file, string $baseName, array $options = []): array
    {
        $this->assertUploadIsValid($file);
        $this->ensureDirectories();

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $mime    = $this->detectMimeType($tmpPath);

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new \RuntimeException('Format non supporté. Utilise JPG, JPEG, PNG ou WEBP.');
        }

        $safeBase         = $this->sanitizeBaseName($baseName);
        $sourcePngPath    = $this->sourceDir . '/' . $safeBase . '.png';
        $mobileWebpPath   = $this->uploadDir . '/' . $safeBase . '-600.webp';
        $desktopWebpPath  = $this->uploadDir . '/' . $safeBase . '-1200.webp';
        $removeBackground = (bool) ($options['remove_background'] ?? false);
        $backgroundFuzz   = max(0, min(40, (int) ($options['background_fuzz'] ?? 12)));

        if ($removeBackground && ! $this->canUseRembg()) {
            throw new \RuntimeException('Le détourage IA nécessite rembg. Installe le runtime local puis vérifie .venv-rembg/bin/rembg.');
        }

        if ($this->canUseImageMagickCli()) {
            $this->processWithImageMagickCli(
                $tmpPath,
                $sourcePngPath,
                $mobileWebpPath,
                $desktopWebpPath,
                $removeBackground,
                $backgroundFuzz,
            );
        } else {
            $this->processWithGd($tmpPath, $mime, $sourcePngPath, $mobileWebpPath, $desktopWebpPath);
        }

        return [
            'desktop_path'    => '/uploads/pages/menu/' . basename($desktopWebpPath),
            'mobile_path'     => '/uploads/pages/menu/' . basename($mobileWebpPath),
            'source_png_path' => '/uploads/pages/menu/sources/' . basename($sourcePngPath),
        ];
    }

    public function cleanupFromDesktopPath(?string $desktopPath): void
    {
        $desktopPath = trim((string) ($desktopPath ?? ''));
        if ($desktopPath === '' || strpos($desktopPath, '/uploads/pages/menu/') !== 0) {
            return;
        }

        $fileName = basename($desktopPath);
        if (substr($fileName, -10) !== '-1200.webp') {
            return;
        }

        $base = substr($fileName, 0, -10);
        if ($base === '') {
            return;
        }

        $files = [
            $this->uploadDir . '/' . $base . '-600.webp',
            $this->uploadDir . '/' . $base . '-1200.webp',
            $this->sourceDir . '/' . $base . '.png',
        ];

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * @param array<string, mixed> $file
     */
    private function assertUploadIsValid(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new \RuntimeException('Aucun fichier image reçu.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Erreur d’upload image (code ' . $error . ').');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || ! is_uploaded_file($tmpPath)) {
            throw new \RuntimeException('Fichier upload invalide.');
        }
    }

    private function ensureDirectories(): void
    {
        if (! is_dir($this->uploadDir) && ! mkdir($this->uploadDir, 0775, true) && ! is_dir($this->uploadDir)) {
            throw new \RuntimeException('Impossible de créer le dossier d’images du menu.');
        }

        if (! is_dir($this->sourceDir) && ! mkdir($this->sourceDir, 0775, true) && ! is_dir($this->sourceDir)) {
            throw new \RuntimeException('Impossible de créer le dossier source PNG.');
        }
    }

    private function detectMimeType(string $tmpPath): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = (string) $finfo->file($tmpPath);
        return strtolower($mime);
    }

    private function sanitizeBaseName(string $baseName): string
    {
        $value = mb_strtolower(trim($baseName));
        $value = str_replace(['œ', 'æ'], ['oe', 'ae'], $value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value === '' ? ('item-' . date('YmdHis')) : $value;
    }

    private function processWithImageMagickCli(
        string $sourcePath,
        string $sourcePngPath,
        string $mobileWebpPath,
        string $desktopWebpPath,
        bool $removeBackground,
        int $backgroundFuzz
    ): void {
        unset($backgroundFuzz);

        // Step 1: Orient the input image
        $tmpOrientedPath = $this->sourceDir . '/.tmp-' . uniqid('oriented-', true) . '.png';

        try {
            $this->runCommand([
                'convert',
                $sourcePath,
                '-auto-orient',
                'PNG32:' . $tmpOrientedPath,
            ]);

            // Step 2: Apply background removal if requested
            if ($removeBackground) {
                $this->runRembg($tmpOrientedPath, $sourcePngPath, $this->rembgFinalModel);
            } else {
                // Just copy with -strip (remove metadata)
                $this->runCommand([
                    'convert',
                    $tmpOrientedPath,
                    '-strip',
                    'PNG32:' . $sourcePngPath,
                ]);
            }
        } finally {
            if (is_file($tmpOrientedPath)) {
                @unlink($tmpOrientedPath);
            }
        }

        // Step 3: Create WEBP variants from the processed PNG
        $this->runCommand([
            'convert',
            $sourcePngPath,
            '-resize',
            self::MOBILE_WIDTH . 'x',
            '-strip',
            '-define',
            'webp:method=6',
            '-define',
            'webp:auto-filter=true',
            '-quality',
            (string) self::WEBP_QUALITY,
            $mobileWebpPath,
        ]);

        $this->runCommand([
            'convert',
            $sourcePngPath,
            '-resize',
            self::DESKTOP_WIDTH . 'x',
            '-strip',
            '-define',
            'webp:method=6',
            '-define',
            'webp:auto-filter=true',
            '-quality',
            (string) self::WEBP_QUALITY,
            $desktopWebpPath,
        ]);
    }

    private function canUseImageMagickCli(): bool
    {
        $which = @shell_exec('command -v convert 2>/dev/null');
        return is_string($which) && trim($which) !== '';
    }

    private function canUseRembg(): bool
    {
        return is_file($this->rembgBinaryPath) && is_executable($this->rembgBinaryPath);
    }

    /** @param list<string> $parts */
    private function runCommand(array $parts): void
    {
        $escaped = array_map('escapeshellarg', $parts);
        $command = implode(' ', $escaped) . ' 2>&1';

        $output   = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Erreur traitement image: ' . trim(implode("\n", $output)));
        }
    }

    private function runRembg(string $inputPath, string $outputPath, string $model): void
    {
        $this->runCommand([
            $this->rembgBinaryPath,
            'i',
            '-m',
            $model,
            $inputPath,
            $outputPath,
        ]);
    }

    private function processWithGd(
        string $sourcePath,
        string $mime,
        string $sourcePngPath,
        string $mobileWebpPath,
        string $desktopWebpPath
    ): void {
        if (! function_exists('imagewebp')) {
            throw new \RuntimeException('Le support WEBP de GD est indisponible.');
        }

        $image = $this->createGdImageFromMime($sourcePath, $mime);
        if ($image === null) {
            throw new \RuntimeException('Impossible de lire l’image uploadée.');
        }

        $image = $this->applyExifOrientationIfNeeded($image, $sourcePath, $mime);

        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagepng($image, $sourcePngPath);

        $this->writeGdWebpVariant($image, self::MOBILE_WIDTH, $mobileWebpPath);
        $this->writeGdWebpVariant($image, self::DESKTOP_WIDTH, $desktopWebpPath);

        imagedestroy($image);
    }

    /** @return resource|\GdImage|null */
    private function createGdImageFromMime(string $sourcePath, string $mime)
    {
        if ($mime === 'image/jpeg') {
            return imagecreatefromjpeg($sourcePath) ?: null;
        }

        if ($mime === 'image/png') {
            return imagecreatefrompng($sourcePath) ?: null;
        }

        if ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
            return imagecreatefromwebp($sourcePath) ?: null;
        }

        return null;
    }

    /** @param resource|\GdImage $image */
    private function writeGdWebpVariant($image, int $targetWidth, string $targetPath): void
    {
        $width  = imagesx($image);
        $height = imagesy($image);

        if ($width <= 0 || $height <= 0) {
            throw new \RuntimeException('Dimensions image invalides.');
        }

        if ($width <= $targetWidth) {
            if (! imagewebp($image, $targetPath, self::WEBP_QUALITY)) {
                throw new \RuntimeException('Échec génération WEBP.');
            }
            return;
        }

        $targetHeight = (int) round(($height * $targetWidth) / $width);
        $canvas       = imagecreatetruecolor($targetWidth, max(1, $targetHeight));

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, max(1, $targetHeight), $transparent);

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, max(1, $targetHeight), $width, $height);

        if (! imagewebp($canvas, $targetPath, self::WEBP_QUALITY)) {
            imagedestroy($canvas);
            throw new \RuntimeException('Échec génération WEBP.');
        }

        imagedestroy($canvas);
    }

    /** @param resource|\GdImage $image @return resource|\GdImage */
    private function applyExifOrientationIfNeeded($image, string $sourcePath, string $mime)
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif        = @exif_read_data($sourcePath);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        if ($orientation === 3) {
            return imagerotate($image, 180, 0);
        }

        if ($orientation === 6) {
            return imagerotate($image, -90, 0);
        }

        if ($orientation === 8) {
            return imagerotate($image, 90, 0);
        }

        return $image;
    }
}
