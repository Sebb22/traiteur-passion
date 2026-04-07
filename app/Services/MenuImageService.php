<?php
declare (strict_types = 1);

namespace App\Services;

final class MenuImageService
{
    private const DESKTOP_WIDTH                = 1200;
    private const MOBILE_WIDTH                 = 600;
    private const WEBP_QUALITY                 = 82;
    private const WEBP_METHOD                  = 4;
    private const PREVIEW_WIDTH                = 320;
    private const PREVIEW_PROCESS_MAX_SIZE     = 512;
    private const DEFAULT_FINAL_INPUT_MAX_SIZE = 1600;
    private const PREVIEW_CACHE_TTL            = 1800;

    private string $projectRoot;
    private string $uploadDir;
    private string $sourceDir;
    private string $previewCacheDir;
    private string $timingLogPath;
    private string $rembgBinaryPath;
    private string $rembgModelsPath;
    private string $rembgServerUrl;
    private string $rembgPreviewModel;
    private string $rembgFinalModel;
    private bool $rembgDebugTiming;
    private int $rembgServerTimeout;
    private int $finalInputMaxSize;
    private int $previewInputMaxSize;

    public function __construct(?string $projectRoot = null)
    {
        $this->projectRoot         = $projectRoot ?? dirname(__DIR__, 2);
        $this->uploadDir           = $this->projectRoot . '/public/uploads/pages/menu';
        $this->sourceDir           = $this->uploadDir . '/sources';
        $this->previewCacheDir     = $this->projectRoot . '/storage/rembg-preview-cache';
        $this->timingLogPath       = $this->projectRoot . '/storage/logs/rembg-timing.log';
        $this->rembgBinaryPath     = $this->projectRoot . '/.venv-rembg/bin/rembg';
        $this->rembgModelsPath     = getenv('REMBG_MODELS_DIR') ?: $this->projectRoot . '/storage/rembg-models';
        $this->rembgServerUrl      = rtrim(trim((string) (getenv('REMBG_SERVER_URL') ?: '')), '/');
        $this->rembgPreviewModel   = getenv('REMBG_PREVIEW_MODEL') ?: 'u2netp';
        $this->rembgFinalModel     = getenv('REMBG_FINAL_MODEL') ?: 'u2net';
        $this->rembgDebugTiming    = in_array(strtolower((string) (getenv('REMBG_DEBUG_TIMING') ?: '0')), ['1', 'true', 'yes', 'on'], true);
        $this->rembgServerTimeout  = max(5, (int) (getenv('REMBG_SERVER_TIMEOUT') ?: 120));
        $this->finalInputMaxSize   = max(self::DESKTOP_WIDTH, (int) (getenv('REMBG_MAX_INPUT_SIZE') ?: self::DEFAULT_FINAL_INPUT_MAX_SIZE));
        $this->previewInputMaxSize = max(self::PREVIEW_WIDTH, (int) (getenv('REMBG_PREVIEW_MAX_INPUT_SIZE') ?: self::PREVIEW_PROCESS_MAX_SIZE));
    }

    public function hasUploadedImage(array $file): bool
    {
        return isset($file['error']) && (int) $file['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * @return array{imagemagick: bool, rembg: bool, preview_ready: bool, final_ready: bool, preview_model: string, final_model: string, preview_reusable: bool, rembg_binary_path: string, rembg_models_path: string, rembg_mode: string, rembg_server_url: string}
     */
    public function getRuntimeStatus(): array
    {
        $hasImageMagick = $this->canUseImageMagickCli();
        $hasRembg       = $this->canUseRembg();
        $previewModel   = $this->rembgPreviewModel;

        return [
            'imagemagick'       => $hasImageMagick,
            'rembg'             => $hasRembg,
            'preview_ready'     => $hasImageMagick && $hasRembg,
            'final_ready'       => $hasImageMagick,
            'preview_model'     => $previewModel,
            'final_model'       => $this->rembgFinalModel,
            'preview_reusable'  => $hasImageMagick && $hasRembg,
            'rembg_binary_path' => $this->rembgBinaryPath,
            'rembg_models_path' => $this->rembgModelsPath,
            'rembg_mode'        => $this->shouldUseRembgServer() ? 'server' : 'cli',
            'rembg_server_url'  => $this->rembgServerUrl,
        ];
    }

    /**
     * @param array<string, mixed> $file
     * @param array{remove_background?: bool, background_fuzz?: int, preview_width?: int, preview_model?: string} $options
     */
    public function generatePreviewDataUri(array $file, array $options = []): string
    {
        return $this->generatePreviewResult($file, $options)['data_uri'];
    }

    /**
     * @param array<string, mixed> $file
     * @param array{remove_background?: bool, background_fuzz?: int, preview_width?: int, preview_model?: string} $options
     * @return array{data_uri: string, preview_token: string}
     */
    public function generatePreviewResult(array $file, array $options = []): array
    {
        $this->writeProbe('preview-enter');

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

        $previewWidth          = (int) ($options['preview_width'] ?? self::PREVIEW_WIDTH);
        $previewModel          = (string) ($options['preview_model'] ?? $this->rembgPreviewModel);
        $previewProcessMaxSize = $this->resolvePreviewProcessMaxSize($previewWidth, $previewModel);
        $previewToken          = '';
        $totalStartedAt        = microtime(true);
        $prepareMs             = 0;
        $rembgMs               = 0;
        $resizeMs              = 0;

        $tmpOrientedPath = $this->sourceDir . '/.tmp-preview-oriented-' . uniqid('', true) . '.png';
        $tmpCutoutPath   = $this->sourceDir . '/.tmp-preview-cutout-' . uniqid('', true) . '.png';
        $tmpPreviewPath  = $this->sourceDir . '/.tmp-preview-result-' . uniqid('', true) . '.png';

        try {
            $stepStartedAt = microtime(true);
            $this->writeProbe('preview-prepare-start', [
                'process_max_size' => $previewProcessMaxSize,
            ]);
            $this->prepareImageForProcessing($tmpPath, $tmpOrientedPath, $previewProcessMaxSize);
            $prepareMs = $this->elapsedMs($stepStartedAt);
            $this->writeProbe('preview-prepare-end', [
                'prepare_ms' => $prepareMs,
            ]);

            if ($removeBackground) {
                $stepStartedAt = microtime(true);
                $this->writeProbe('preview-rembg-start', [
                    'model' => $previewModel,
                ]);
                $this->runRembg($tmpOrientedPath, $tmpCutoutPath, $previewModel);
                $rembgMs = $this->elapsedMs($stepStartedAt);
                $this->writeProbe('preview-rembg-end', [
                    'model'    => $previewModel,
                    'rembg_ms' => $rembgMs,
                ]);

                $previewToken = $this->storePreviewCache($tmpCutoutPath, $tmpPath, $previewModel, $previewProcessMaxSize);
            } else {
                $this->runCommand([
                    'convert',
                    $tmpOrientedPath,
                    '-strip',
                    'PNG32:' . $tmpCutoutPath,
                ]);
            }

            $stepStartedAt = microtime(true);
            $this->writeProbe('preview-resize-start', [
                'preview_width' => $previewWidth,
            ]);
            $this->runCommand([
                'convert',
                $tmpCutoutPath,
                '-resize',
                $previewWidth . 'x',
                '-strip',
                'PNG32:' . $tmpPreviewPath,
            ]);
            $resizeMs = $this->elapsedMs($stepStartedAt);
            $this->writeProbe('preview-resize-end', [
                'resize_ms' => $resizeMs,
            ]);

            $previewBytes = @file_get_contents($tmpPreviewPath);
            if (! is_string($previewBytes) || $previewBytes === '') {
                throw new \RuntimeException('Aperçu vide.');
            }

            $this->logTiming('preview', [
                'mode'             => $this->shouldUseRembgServer() ? 'server' : 'cli',
                'model'            => $previewModel,
                'prepare_ms'       => $prepareMs,
                'rembg_ms'         => $rembgMs,
                'resize_ms'        => $resizeMs,
                'total_ms'         => $this->elapsedMs($totalStartedAt),
                'preview_width'    => $previewWidth,
                'process_max_size' => $previewProcessMaxSize,
                'preview_token'    => $previewToken !== '' ? 'yes' : 'no',
            ]);

            return [
                'data_uri'      => 'data:image/png;base64,' . base64_encode($previewBytes),
                'preview_token' => $previewToken,
            ];
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
     * @param array{remove_background?: bool, background_fuzz?: int, preview_token?: string} $options
     * @return array{desktop_path: string, mobile_path: string, source_png_path: string}
     */
    public function processItemImage(array $file, string $baseName, array $options = []): array
    {
        $this->writeProbe('save-enter');

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
        $previewToken     = trim((string) ($options['preview_token'] ?? ''));

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
                $previewToken,
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
        int $backgroundFuzz,
        string $previewToken = ''
    ): void {
        unset($backgroundFuzz);
        $totalStartedAt = microtime(true);
        $prepareMs      = 0;
        $rembgMs        = 0;
        $mobileMs       = 0;
        $desktopMs      = 0;
        $cacheReused    = false;

        // Step 1: Orient the input image and downscale it before AI processing.
        $tmpOrientedPath = $this->sourceDir . '/.tmp-' . uniqid('oriented-', true) . '.png';

        try {
            $stepStartedAt = microtime(true);
            $this->writeProbe('save-prepare-start', [
                'final_input_max_size' => $this->finalInputMaxSize,
            ]);
            $this->prepareImageForProcessing($sourcePath, $tmpOrientedPath, $this->finalInputMaxSize);
            $prepareMs = $this->elapsedMs($stepStartedAt);
            $this->writeProbe('save-prepare-end', [
                'prepare_ms' => $prepareMs,
            ]);

            // Step 2: Apply background removal if requested
            if ($removeBackground && $previewToken !== '' && $this->restorePreviewCache($previewToken, $sourcePath, $sourcePngPath)) {
                // Reuse the preview accepted by the user instead of rerunning rembg during save.
                $cacheReused = true;
                $this->writeProbe('save-cache-reused');
            } elseif ($removeBackground) {
                $stepStartedAt = microtime(true);
                $this->writeProbe('save-rembg-start', [
                    'model' => $this->rembgFinalModel,
                ]);
                $this->runRembg($tmpOrientedPath, $sourcePngPath, $this->rembgFinalModel);
                $rembgMs = $this->elapsedMs($stepStartedAt);
                $this->writeProbe('save-rembg-end', [
                    'model'    => $this->rembgFinalModel,
                    'rembg_ms' => $rembgMs,
                ]);
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
        $stepStartedAt = microtime(true);
        $this->writeProbe('save-mobile-webp-start', [
            'width' => self::MOBILE_WIDTH,
        ]);
        $this->createWebpVariant($sourcePngPath, $mobileWebpPath, self::MOBILE_WIDTH);
        $mobileMs = $this->elapsedMs($stepStartedAt);
        $this->writeProbe('save-mobile-webp-end', [
            'mobile_webp_ms' => $mobileMs,
        ]);

        $stepStartedAt = microtime(true);
        $this->writeProbe('save-desktop-webp-start', [
            'width' => self::DESKTOP_WIDTH,
        ]);
        $this->createWebpVariant($sourcePngPath, $desktopWebpPath, self::DESKTOP_WIDTH);
        $desktopMs = $this->elapsedMs($stepStartedAt);
        $this->writeProbe('save-desktop-webp-end', [
            'desktop_webp_ms' => $desktopMs,
        ]);

        $this->logTiming('save', [
            'mode'                 => $this->shouldUseRembgServer() ? 'server' : 'cli',
            'model'                => $this->rembgFinalModel,
            'prepare_ms'           => $prepareMs,
            'rembg_ms'             => $rembgMs,
            'mobile_webp_ms'       => $mobileMs,
            'desktop_webp_ms'      => $desktopMs,
            'total_ms'             => $this->elapsedMs($totalStartedAt),
            'final_input_max_size' => $this->finalInputMaxSize,
            'preview_cache_reused' => $cacheReused ? 'yes' : 'no',
        ]);
    }

    private function canUseImageMagickCli(): bool
    {
        $which = @shell_exec('command -v convert 2>/dev/null');
        return is_string($which) && trim($which) !== '';
    }

    private function canUseCwebpCli(): bool
    {
        $which = @shell_exec('command -v cwebp 2>/dev/null');
        return is_string($which) && trim($which) !== '';
    }

    private function createWebpVariant(string $sourcePngPath, string $targetWebpPath, int $targetWidth): void
    {
        if ($this->canUseCwebpCli()) {
            $this->runCommand([
                'cwebp',
                '-quiet',
                '-mt',
                '-m',
                (string) self::WEBP_METHOD,
                '-q',
                (string) self::WEBP_QUALITY,
                '-alpha_q',
                '100',
                '-resize',
                (string) $targetWidth,
                '0',
                $sourcePngPath,
                '-o',
                $targetWebpPath,
            ]);

            return;
        }

        $this->runCommand([
            'convert',
            $sourcePngPath,
            '-resize',
            $targetWidth . 'x',
            '-strip',
            '-define',
            'webp:method=' . self::WEBP_METHOD,
            '-define',
            'webp:auto-filter=true',
            '-quality',
            (string) self::WEBP_QUALITY,
            $targetWebpPath,
        ]);
    }

    private function prepareImageForProcessing(string $sourcePath, string $targetPath, int $maxSize): void
    {
        $resizeValue = max(1, $maxSize) . 'x' . max(1, $maxSize) . '>';

        $this->runCommand([
            'convert',
            $sourcePath,
            '-auto-orient',
            '-resize',
            $resizeValue,
            '-strip',
            'PNG32:' . $targetPath,
        ]);
    }

    private function resolvePreviewProcessMaxSize(int $previewWidth, string $previewModel): int
    {
        $maxSize = max($previewWidth, $this->previewInputMaxSize);

        if ($previewModel === $this->rembgFinalModel) {
            $maxSize = max($maxSize, self::DESKTOP_WIDTH);
        }

        return $maxSize;
    }

    private function canUseRembg(): bool
    {
        if ($this->shouldUseRembgServer()) {
            return function_exists('curl_init') && function_exists('curl_file_create');
        }

        return is_file($this->rembgBinaryPath) && is_executable($this->rembgBinaryPath);
    }

    /** @param list<string> $parts @param array<string, string> $env */
    private function runCommand(array $parts, array $env = []): void
    {
        $pipes   = [];
        $process = proc_open(
            $parts,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $this->projectRoot,
            $env === [] ? null : array_merge(is_array(getenv()) ? getenv() : [], $env),
        );

        if (! is_resource($process)) {
            throw new \RuntimeException('Impossible de lancer la commande système.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $message = trim(implode("\n", array_filter([$stdout, $stderr], static fn($value): bool => is_string($value) && trim($value) !== '')));
            throw new \RuntimeException('Erreur traitement image: ' . ($message !== '' ? $message : 'commande en echec.'));
        }
    }

    private function runRembg(string $inputPath, string $outputPath, string $model): void
    {
        if ($this->shouldUseRembgServer()) {
            $this->runRembgViaServer($inputPath, $outputPath, $model);
            return;
        }

        $this->ensureRembgModelDirectory();

        $this->runCommand([
            $this->rembgBinaryPath,
            'i',
            '-m',
            $model,
            $inputPath,
            $outputPath,
        ], [
            'U2NET_HOME' => $this->rembgModelsPath,
        ]);
    }

    private function shouldUseRembgServer(): bool
    {
        return $this->rembgServerUrl !== '';
    }

    private function runRembgViaServer(string $inputPath, string $outputPath, string $model): void
    {
        if (! function_exists('curl_init') || ! function_exists('curl_file_create')) {
            throw new \RuntimeException('Le mode rembg serveur nécessite l\'extension PHP cURL.');
        }

        $requestStartedAt = microtime(true);
        $this->writeProbe('rembg-server-request-start', [
            'model' => $model,
        ]);

        $curl = curl_init($this->rembgServerUrl . '/api/remove');
        if ($curl === false) {
            throw new \RuntimeException('Impossible d\'initialiser la requete vers le serveur rembg.');
        }

        $postFields = [
            'model' => $model,
            'file'  => curl_file_create($inputPath, 'image/png', basename($inputPath)),
        ];

        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => $this->rembgServerTimeout,
            CURLOPT_FAILONERROR    => false,
        ]);

        $response = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error    = curl_error($curl);
        curl_close($curl);

        if (! is_string($response)) {
            $this->writeProbe('rembg-server-request-error', [
                'model'      => $model,
                'http_code'  => $httpCode,
                'error'      => $error !== '' ? $error : 'empty_response',
                'elapsed_ms' => $this->elapsedMs($requestStartedAt),
            ]);
            throw new \RuntimeException('Erreur appel serveur rembg: ' . ($error !== '' ? $error : 'reponse vide.'));
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $message = trim($response);
            $this->writeProbe('rembg-server-request-error', [
                'model'      => $model,
                'http_code'  => $httpCode,
                'elapsed_ms' => $this->elapsedMs($requestStartedAt),
            ]);
            throw new \RuntimeException('Erreur serveur rembg (' . $httpCode . '): ' . ($message !== '' ? $message : 'reponse invalide.'));
        }

        if (@file_put_contents($outputPath, $response) === false) {
            throw new \RuntimeException('Impossible d\'ecrire l\'image retournee par le serveur rembg.');
        }

        $this->writeProbe('rembg-server-request-end', [
            'model'      => $model,
            'http_code'  => $httpCode,
            'elapsed_ms' => $this->elapsedMs($requestStartedAt),
        ]);
    }

    private function ensureRembgModelDirectory(): void
    {
        if (! is_dir($this->rembgModelsPath) && ! mkdir($this->rembgModelsPath, 0775, true) && ! is_dir($this->rembgModelsPath)) {
            throw new \RuntimeException('Impossible de creer le dossier de modeles rembg.');
        }

        if (! is_writable($this->rembgModelsPath)) {
            throw new \RuntimeException('Le dossier des modeles rembg n\'est pas accessible en ecriture: ' . $this->rembgModelsPath);
        }
    }

    private function ensurePreviewCacheDirectory(): void
    {
        if (! is_dir($this->previewCacheDir) && ! mkdir($this->previewCacheDir, 0775, true) && ! is_dir($this->previewCacheDir)) {
            throw new \RuntimeException('Impossible de creer le dossier cache des apercus rembg.');
        }

        if (! is_writable($this->previewCacheDir)) {
            throw new \RuntimeException('Le dossier cache des apercus rembg n\'est pas accessible en ecriture: ' . $this->previewCacheDir);
        }
    }

    private function storePreviewCache(string $cutoutPath, string $sourcePath, string $model, int $processMaxSize): string
    {
        $this->ensurePreviewCacheDirectory();
        $this->purgeExpiredPreviewCache();

        $token     = bin2hex(random_bytes(16));
        $cachePath = $this->previewCacheDir . '/' . $token . '.png';

        if (! @copy($cutoutPath, $cachePath)) {
            throw new \RuntimeException('Impossible de mettre en cache le detourage de previsualisation.');
        }

        if (! isset($_SESSION) || ! is_array($_SESSION)) {
            return '';
        }

        if (! isset($_SESSION['rembg_preview_cache']) || ! is_array($_SESSION['rembg_preview_cache'])) {
            $_SESSION['rembg_preview_cache'] = [];
        }

        $_SESSION['rembg_preview_cache'][$token] = [
            'path'             => $cachePath,
            'signature'        => $this->computeFileSignature($sourcePath),
            'model'            => $model,
            'process_max_size' => $processMaxSize,
            'created_at'       => time(),
        ];

        return $token;
    }

    private function restorePreviewCache(string $token, string $sourcePath, string $targetPath): bool
    {
        $entry = $_SESSION['rembg_preview_cache'][$token] ?? null;
        if (! is_array($entry)) {
            $this->writeProbe('save-cache-miss', [
                'reason' => 'missing_session_entry',
            ]);
            return false;
        }

        $createdAt = (int) ($entry['created_at'] ?? 0);
        if ($createdAt < (time() - self::PREVIEW_CACHE_TTL)) {
            $this->writeProbe('save-cache-miss', [
                'reason' => 'expired',
            ]);
            $this->deletePreviewCacheEntry($token, $entry);
            return false;
        }

        $cachePath = (string) ($entry['path'] ?? '');
        if ($cachePath === '' || ! is_file($cachePath)) {
            $this->writeProbe('save-cache-miss', [
                'reason' => 'missing_cache_file',
            ]);
            unset($_SESSION['rembg_preview_cache'][$token]);
            return false;
        }

        if ((string) ($entry['signature'] ?? '') !== $this->computeFileSignature($sourcePath)) {
            $this->writeProbe('save-cache-miss', [
                'reason' => 'signature_mismatch',
            ]);
            return false;
        }

        $restored = @copy($cachePath, $targetPath);
        $this->writeProbe($restored ? 'save-cache-hit' : 'save-cache-miss', [
            'reason' => $restored ? 'restored' : 'copy_failed',
        ]);
        $this->deletePreviewCacheEntry($token, $entry);

        return $restored;
    }

    private function purgeExpiredPreviewCache(): void
    {
        if (! isset($_SESSION['rembg_preview_cache']) || ! is_array($_SESSION['rembg_preview_cache'])) {
            return;
        }

        foreach ($_SESSION['rembg_preview_cache'] as $token => $entry) {
            if (! is_array($entry)) {
                unset($_SESSION['rembg_preview_cache'][$token]);
                continue;
            }

            $createdAt = (int) ($entry['created_at'] ?? 0);
            if ($createdAt < (time() - self::PREVIEW_CACHE_TTL)) {
                $this->deletePreviewCacheEntry((string) $token, $entry);
            }
        }
    }

    /** @param array<string, mixed> $entry */
    private function deletePreviewCacheEntry(string $token, array $entry): void
    {
        $cachePath = (string) ($entry['path'] ?? '');
        if ($cachePath !== '' && is_file($cachePath)) {
            @unlink($cachePath);
        }

        unset($_SESSION['rembg_preview_cache'][$token]);
    }

    private function computeFileSignature(string $path): string
    {
        $hash = @sha1_file($path);
        if (is_string($hash) && $hash !== '') {
            return $hash;
        }

        return md5($path . '|' . (string) @filesize($path) . '|' . (string) @filemtime($path));
    }

    private function elapsedMs(float $startedAt): int
    {
        return (int) round((microtime(true) - $startedAt) * 1000);
    }

    /** @param array<string, scalar|null> $context */
    private function logTiming(string $phase, array $context): void
    {
        if (! $this->rembgDebugTiming) {
            return;
        }

        $parts = [];
        foreach ($context as $key => $value) {
            if ($value === null) {
                continue;
            }

            $parts[] = $key . '=' . (string) $value;
        }

        $line = '[' . date('Y-m-d H:i:s') . '] [rembg:' . $phase . '] ' . implode(' ', $parts) . PHP_EOL;

        $logDir = dirname($this->timingLogPath);
        if (! is_dir($logDir) && ! @mkdir($logDir, 0775, true) && ! is_dir($logDir)) {
            error_log('[rembg:' . $phase . '] unable_to_create_log_dir=' . $logDir . ' ' . implode(' ', $parts));
            return;
        }

        if (@file_put_contents($this->timingLogPath, $line, FILE_APPEND) === false) {
            error_log('[rembg:' . $phase . '] unable_to_write_log=' . $this->timingLogPath . ' ' . implode(' ', $parts));
        }
    }

    /** @param array<string, scalar|null> $context */
    private function writeProbe(string $label, array $context = []): void
    {
        if (! $this->rembgDebugTiming) {
            return;
        }

        $logDir = dirname($this->timingLogPath);
        if (! is_dir($logDir) && ! @mkdir($logDir, 0775, true) && ! is_dir($logDir)) {
            return;
        }

        $parts = [];
        foreach ($context as $key => $value) {
            if ($value === null) {
                continue;
            }

            $parts[] = $key . '=' . (string) $value;
        }

        $line = '[' . date('Y-m-d H:i:s') . '] [probe] ' . $label;
        if ($parts !== []) {
            $line .= ' ' . implode(' ', $parts);
        }

        @file_put_contents(
            $this->timingLogPath,
            $line . PHP_EOL,
            FILE_APPEND,
        );
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
