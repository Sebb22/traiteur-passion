<?php
declare (strict_types = 1);

namespace App\Services;

final class EventCampaignImageService
{
    private const MAX_BYTES = 8 * 1024 * 1024;

    public function store(array $file, int $campaignId): array
    {
        $this->assertValidUpload($file);

        $tmpPath = (string) $file['tmp_name'];
        $finfo   = new \finfo(FILEINFO_MIME_TYPE);
        $mime    = strtolower((string) $finfo->file($tmpPath));
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (! in_array($mime, $allowed, true) || @getimagesize($tmpPath) === false) {
            throw new \RuntimeException('Le visuel doit être une image JPEG, PNG ou WebP valide.');
        }

        $convert = trim((string) shell_exec('command -v convert 2>/dev/null'));
        if ($convert === '') {
            throw new \RuntimeException('ImageMagick est nécessaire pour optimiser le visuel.');
        }

        $directory = dirname(__DIR__, 2) . '/public/uploads/campaigns';
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Impossible de créer le dossier des campagnes.');
        }

        $base        = 'campaign-' . $campaignId . '-' . bin2hex(random_bytes(8));
        $desktopFile = $base . '-desktop.webp';
        $mobileFile  = $base . '-mobile.webp';
        $desktopPath = $directory . '/' . $desktopFile;
        $mobilePath  = $directory . '/' . $mobileFile;

        $this->runConvert($convert, $tmpPath, $desktopPath, 1600);
        $this->runConvert($convert, $tmpPath, $mobilePath, 800);

        return [
            'desktop_path' => '/uploads/campaigns/' . $desktopFile,
            'mobile_path'  => '/uploads/campaigns/' . $mobileFile,
        ];
    }

    public function delete(?string $desktopPath, ?string $mobilePath): void
    {
        foreach ([$desktopPath, $mobilePath] as $path) {
            $path = trim((string) $path);
            if ($path === '' || strpos($path, '/uploads/campaigns/') !== 0) {
                continue;
            }

            $absolutePath = dirname(__DIR__, 2) . '/public' . $path;
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    private function assertValidUpload(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE   => 'Le visuel dépasse la limite upload_max_filesize du serveur.',
                UPLOAD_ERR_FORM_SIZE  => 'Le visuel dépasse la taille maximale autorisée de 8 Mo.',
                UPLOAD_ERR_PARTIAL    => 'Le téléversement du visuel a été interrompu.',
                UPLOAD_ERR_NO_FILE    => 'Sélectionnez un visuel avant d’enregistrer la campagne.',
                UPLOAD_ERR_NO_TMP_DIR => 'Le dossier temporaire PHP est indisponible.',
                UPLOAD_ERR_CANT_WRITE => 'PHP ne peut pas écrire le fichier téléversé.',
                UPLOAD_ERR_EXTENSION  => 'Une extension PHP a bloqué le téléversement du visuel.',
            ];
            throw new \RuntimeException($messages[$error] ?? 'Le téléversement du visuel a échoué (code ' . $error . ').');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $size    = (int) ($file['size'] ?? 0);
        if ($tmpPath === '' || ! is_uploaded_file($tmpPath)) {
            throw new \RuntimeException('Fichier téléversé invalide.');
        }
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new \RuntimeException('Le visuel dépasse la taille maximale de 8 Mo.');
        }
    }

    private function runConvert(string $convert, string $input, string $output, int $width): void
    {
        $command = sprintf(
            '%s %s -auto-orient -strip -resize %s -quality 82 %s 2>&1',
            escapeshellarg($convert),
            escapeshellarg($input),
            escapeshellarg($width . 'x>'),
            escapeshellarg($output)
        );
        exec($command, $outputLines, $exitCode);
        if ($exitCode !== 0 || ! is_file($output)) {
            throw new \RuntimeException('Impossible d’optimiser le visuel de campagne.');
        }
    }
}
