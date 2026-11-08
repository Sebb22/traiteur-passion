<?php
declare (strict_types = 1);

namespace App\Controllers;

use App\Core\AdminAuth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\EventCampaign;
use App\Services\EventCampaignImageService;
use DateTimeImmutable;
use DateTimeZone;

final class EventCampaignController
{
    public function index(): void
    {
        AdminAuth::requireAuth();
        $model = new EventCampaign();

        View::render('admin/campaigns', [
            'title'     => 'Administration — Campagnes événementielles',
            'campaigns' => $model->getAllForAdmin(),
            'campaign'  => null,
            'csrfToken' => Csrf::token(),
            'flash'     => $this->pullFlash(),
        ]);
    }

    public function create(): void
    {
        AdminAuth::requireAuth();

        View::render('admin/campaigns', [
            'title'     => 'Administration — Nouvelle campagne',
            'campaigns' => (new EventCampaign())->getAllForAdmin(),
            'campaign'  => ['display_frequency' => 'session', 'display_scope' => 'homepage', 'appearance_delay_seconds' => 3, 'destination_target' => '_blank', 'close_with_button' => 1, 'close_on_overlay' => 1, 'theme_key' => 'classic', 'animation_key' => 'fade-slide'],
            'csrfToken' => Csrf::token(),
            'flash'     => $this->pullFlash(),
            'focusForm' => true,
        ]);
    }

    public function store(): void
    {
        AdminAuth::requireAuth();
        Csrf::requireValid();
        $this->save(null);
    }

    public function edit(string $id): void
    {
        AdminAuth::requireAuth();
        $campaign = (new EventCampaign())->findForAdmin((int) $id);
        if ($campaign === null) {
            http_response_code(404);
            echo 'Campagne introuvable.';
            return;
        }

        View::render('admin/campaigns', [
            'title'     => 'Administration — Modifier une campagne',
            'campaigns' => (new EventCampaign())->getAllForAdmin(),
            'campaign'  => $campaign,
            'csrfToken' => Csrf::token(),
            'flash'     => $this->pullFlash(),
            'focusForm' => true,
        ]);
    }

    public function update(string $id): void
    {
        AdminAuth::requireAuth();
        Csrf::requireValid();
        $this->save((int) $id);
    }

    public function toggle(string $id): void
    {
        AdminAuth::requireAuth();
        Csrf::requireValid();
        $campaignId = (int) $id;
        $model      = new EventCampaign();

        try {
            $campaign = $model->findForAdmin($campaignId);
            if ($campaign === null) {
                throw new \RuntimeException('Campagne introuvable.');
            }
            $enabled = (int) ($campaign['manual_enabled'] ?? 0) !== 1;
            if ($enabled) {
                $this->validatePublishable($campaign);
            }
            $model->toggle($campaignId, $enabled);
            $this->pushFlash('success', $enabled ? 'Campagne activée.' : 'Campagne désactivée.');
        } catch (\Throwable $exception) {
            error_log('Campaign toggle error: ' . $exception->getMessage());
            $this->pushFlash('error', $exception->getMessage());
        }

        $this->redirect();
    }

    public function delete(string $id): void
    {
        AdminAuth::requireAuth();
        Csrf::requireValid();
        $model = new EventCampaign();

        try {
            $campaign = $model->findForAdmin((int) $id);
            if ($campaign === null) {
                throw new \RuntimeException('Campagne introuvable.');
            }
            $model->softDelete((int) $id);
            (new EventCampaignImageService())->delete($campaign['image_path'] ?? null, $campaign['image_mobile_path'] ?? null);
            $this->pushFlash('success', 'Campagne supprimée.');
        } catch (\Throwable $exception) {
            error_log('Campaign delete error: ' . $exception->getMessage());
            $this->pushFlash('error', 'Impossible de supprimer la campagne.');
        }

        $this->redirect();
    }

    public function preview(string $id): void
    {
        AdminAuth::requireAuth();
        $campaign = (new EventCampaign())->findForAdmin((int) $id);
        if ($campaign === null) {
            http_response_code(404);
            echo 'Campagne introuvable.';
            return;
        }

        View::render('admin/campaign-preview', [
            'title'    => 'Prévisualisation — ' . $campaign['internal_name'],
            'campaign' => $campaign,
            'preview'  => true,
        ]);
    }

    private function save(?int $id): void
    {
        $model    = new EventCampaign();
        $existing = $id !== null ? $model->findForAdmin($id) : null;
        if ($id !== null && $existing === null) {
            $this->pushFlash('error', 'Campagne introuvable.');
            $this->redirect();
        }

        try {
            $data         = $this->validatedPayload($_POST, $existing);
            $imageFile    = $_FILES['image_file'] ?? null;
            $hasNewImage  = is_array($imageFile) && (int) ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
            $shouldEnable = ! empty($data['manual_enabled']);
            if ($shouldEnable && trim((string) ($data['destination_url'] ?? '')) === '') {
                throw new \RuntimeException('Une URL de destination est obligatoire pour activer la campagne.');
            }
            if ($shouldEnable && trim((string) ($data['image_path'] ?? '')) === '' && ! $hasNewImage) {
                throw new \RuntimeException('Un visuel est obligatoire pour activer la campagne.');
            }
            if ($id === null && $shouldEnable) {
                $data['manual_enabled'] = 0;
            }

            $campaignId = $id ?? $model->create($data);
            if ($id !== null) {
                $model->update($id, $data);
            }

            if (is_array($imageFile) && (int) ($imageFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $imageService              = new EventCampaignImageService();
                $image                     = $imageService->store($imageFile, $campaignId);
                $data['image_path']        = $image['desktop_path'];
                $data['image_mobile_path'] = $image['mobile_path'];
                $model->update($campaignId, $data);
                if (is_array($existing)) {
                    $imageService->delete($existing['image_path'] ?? null, $existing['image_mobile_path'] ?? null);
                }
            }

            if ($shouldEnable) {
                $model->toggle($campaignId, true);
            }

            $this->pushFlash('success', $id === null ? 'Campagne créée.' : 'Campagne mise à jour.');
        } catch (\Throwable $exception) {
            error_log('Campaign save error: ' . $exception->getMessage());
            $this->pushFlash('error', $exception->getMessage());
        }

        $this->redirect();
    }

    private function validatedPayload(array $input, ?array $existing): array
    {
        $internalName = trim((string) ($input['internal_name'] ?? ''));
        $title        = trim((string) ($input['visible_title'] ?? ''));
        $description  = trim((string) ($input['short_description'] ?? ''));
        $buttonLabel  = trim((string) ($input['primary_button_label'] ?? ''));
        if ($internalName === '' || $title === '' || $description === '' || $buttonLabel === '') {
            throw new \RuntimeException('Les champs nom, titre, description et bouton principal sont obligatoires.');
        }
        if (mb_strlen($internalName) > 180 || mb_strlen($title) > 180 || mb_strlen($buttonLabel) > 120) {
            throw new \RuntimeException('Un des champs textuels dépasse la longueur autorisée.');
        }

        $frequency = (string) ($input['display_frequency'] ?? 'session');
        $scope     = (string) ($input['display_scope'] ?? 'homepage');
        if (! in_array($frequency, EventCampaign::FREQUENCIES, true) || ! in_array($scope, EventCampaign::SCOPES, true)) {
            throw new \RuntimeException('Fréquence ou périmètre d’affichage invalide.');
        }

        $interval = trim((string) ($input['display_interval_days'] ?? ''));
        if ($frequency === 'days' && (! ctype_digit($interval) || (int) $interval < 1 || (int) $interval > 365)) {
            throw new \RuntimeException('L’intervalle doit être compris entre 1 et 365 jours.');
        }

        $destination = trim((string) ($input['destination_url'] ?? ''));
        if ($destination !== '' && ! $this->isSafeUrl($destination)) {
            throw new \RuntimeException('URL de destination invalide.');
        }
        $startsAt = $this->normalizeDate($input['starts_at'] ?? '');
        $endsAt   = $this->normalizeDate($input['ends_at'] ?? '');
        if ($startsAt !== null && $endsAt !== null && $endsAt <= $startsAt) {
            throw new \RuntimeException('La date de fin doit être postérieure à la date de début.');
        }

        $data = [
            'internal_name'            => $internalName,
            'visible_title'            => $title,
            'short_description'        => $description,
            'image_path'               => (string) ($existing['image_path'] ?? ''),
            'image_mobile_path'        => (string) ($existing['image_mobile_path'] ?? ''),
            'image_alt'                => trim((string) ($input['image_alt'] ?? $title)),
            'primary_button_label'     => $buttonLabel,
            'destination_url'          => $destination,
            'destination_target'       => in_array(($input['destination_target'] ?? '_blank'), ['_self', '_blank'], true) ? (string) $input['destination_target'] : '_blank',
            'secondary_button_label'   => trim((string) ($input['secondary_button_label'] ?? '')),
            'starts_at'                => $startsAt !== null ? $startsAt->format('Y-m-d H:i:s') : '',
            'ends_at'                  => $endsAt !== null ? $endsAt->format('Y-m-d H:i:s') : '',
            'manual_enabled'           => ! empty($input['manual_enabled']) ? 1 : 0,
            'display_frequency'        => $frequency,
            'display_interval_days'    => $frequency === 'days' ? (int) $interval : '',
            'display_scope'            => $scope,
            'appearance_delay_seconds' => min(60, max(0, (int) ($input['appearance_delay_seconds'] ?? 3))),
            'close_with_button'        => ! empty($input['close_with_button']) ? 1 : 0,
            'close_on_overlay'         => ! empty($input['close_on_overlay']) ? 1 : 0,
            'theme_key'                => (string) ($existing['theme_key'] ?? 'classic'),
            'animation_key'            => (string) ($existing['animation_key'] ?? 'fade-slide'),
        ];

        if (! empty($data['manual_enabled'])) {
            $this->validatePublishable($data);
        }

        return $data;
    }

    private function validatePublishable(array $campaign): void
    {
        if (trim((string) ($campaign['destination_url'] ?? '')) === '') {
            throw new \RuntimeException('Une URL de destination est obligatoire pour activer la campagne.');
        }
        if (trim((string) ($campaign['image_path'] ?? '')) === '') {
            throw new \RuntimeException('Un visuel est obligatoire pour activer la campagne.');
        }
    }

    private function normalizeDate($value): ?DateTimeImmutable
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $date   = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $value, new DateTimeZone('Europe/Paris'));
        $errors = DateTimeImmutable::getLastErrors();
        if (! $date || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \RuntimeException('Format de date invalide.');
        }
        return $date;
    }

    private function isSafeUrl(string $url): bool
    {
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            return true;
        }
        $parts = parse_url($url);
        return is_array($parts) && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) && ! empty($parts['host']);
    }

    private function pushFlash(string $type, string $message): void
    {
        $_SESSION['admin_campaign_flash'] = ['type' => $type, 'message' => $message];
    }

    private function pullFlash(): ?array
    {
        $flash = $_SESSION['admin_campaign_flash'] ?? null;
        unset($_SESSION['admin_campaign_flash']);
        return is_array($flash) ? $flash : null;
    }

    private function redirect(): void
    {
        header('Location: /admin/campagnes');
        exit;
    }
}
