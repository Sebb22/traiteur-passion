<?php
declare (strict_types = 1);

namespace App\Services;

use App\Core\Url;
use App\Models\Contact;

final class ContactNotificationService
{
    private Mailer $mailer;

    /** @var array<string,mixed> */
    private array $mailConfig;

    /** @var array<string,mixed> */
    private array $appConfig;

    public function __construct(?Mailer $mailer = null)
    {
        $this->mailConfig = require dirname(__DIR__, 2) . '/config/mail.php';
        $this->appConfig  = require dirname(__DIR__, 2) . '/config/app.php';
        $this->mailer     = $mailer ?? new Mailer($this->mailConfig);
    }

    /**
     * @param array<string,mixed> $contactData
     * @param array<int,array<string,mixed>> $menuItems
     * @return array{enabled:bool,admin_notified:bool,client_ack_sent:bool,errors:list<string>}
     */
    public function dispatch(string $requestKind, int $contactId, array $contactData, array $menuItems = []): array
    {
        $result = [
            'enabled'         => $this->mailer->isEnabled(),
            'admin_notified'  => false,
            'client_ack_sent' => false,
            'errors'          => [],
        ];

        if (! $this->mailer->isEnabled()) {
            return $result;
        }

        if (($this->mailConfig['notify_admin'] ?? true) === true) {
            try {
                $this->sendAdminNotification($requestKind, $contactId, $contactData, $menuItems);
                $result['admin_notified'] = true;
            } catch (\Throwable $e) {
                $result['errors'][] = 'Notification admin: ' . $e->getMessage();
            }
        }

        if (($this->mailConfig['ack_client'] ?? true) === true) {
            try {
                $this->sendClientAcknowledgement($requestKind, $contactId, $contactData, $menuItems);
                $result['client_ack_sent'] = true;
            } catch (\Throwable $e) {
                $result['errors'][] = 'Accusé de réception client: ' . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $contactData
     * @param array<int,array<string,mixed>> $menuItems
     * @return array{enabled:bool,client_status_sent:bool,errors:list<string>}
     */
    public function dispatchStatusUpdate(
        string $requestKind,
        int $contactId,
        array $contactData,
        array $menuItems,
        string $previousStatus,
        string $nextStatus,
        ?string $customMessage = null,
        ?string $customSubject = null
    ): array {
        $result = [
            'enabled'            => $this->mailer->isEnabled(),
            'client_status_sent' => false,
            'errors'             => [],
        ];

        if (! $this->mailer->isEnabled()) {
            return $result;
        }

        try {
            $this->sendClientStatusUpdate(
                $requestKind,
                $contactId,
                $contactData,
                $menuItems,
                $previousStatus,
                $nextStatus,
                $customMessage,
                $customSubject,
            );
            $result['client_status_sent'] = true;
        } catch (\Throwable $e) {
            $result['errors'][] = 'Suivi client: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $contactData
     * @param array<int,array<string,mixed>> $menuItems
     */
    private function sendAdminNotification(string $requestKind, int $contactId, array $contactData, array $menuItems): void
    {
        $recipients = $this->adminRecipients();
        if ($recipients === []) {
            throw new \RuntimeException('MAIL_ADMIN_TO est vide ou invalide.');
        }

        $label         = $this->requestLabel($requestKind);
        $subject       = sprintf('[%s] Nouvelle demande de %s #%d', $this->appName(), $label, $contactId);
        $summaryFields = $this->summaryFields($contactId, $contactData);
        $menuItemsView = $this->menuItemsViewData($menuItems);
        $theme         = $this->emailTheme($requestKind, 'admin');

        $htmlBody = $this->renderEmailTemplate('admin-notification', [
            'requestLabel'        => $label,
            'contactId'           => $contactId,
            'leadCopy'            => $this->adminLeadCopy($requestKind),
            'actionCopy'          => $this->adminActionCopy($requestKind),
            'messageBlockTitle'   => $requestKind === 'quote' ? 'Brief client' : 'Message reçu',
            'selectionBlockTitle' => $requestKind === 'quote' ? 'Base de devis sélectionnée' : 'Sélection éventuelle',
            'ctaBackground'       => $theme['buttonBackground'],
            'ctaTextColor'        => $theme['buttonTextColor'],
            'clientMessageHtml'   => nl2br($this->escape((string) ($contactData['message'] ?? ''))),
            'summaryFields'       => $summaryFields,
            'menuItems'           => $menuItemsView,
            'adminDetailUrl'      => $this->adminDetailUrl($contactId),
        ], [
            'title'            => sprintf('Nouvelle demande de %s', $label),
            'preheader'        => sprintf('Une nouvelle demande de %s #%d attend votre attention.', $label, $contactId),
            'eyebrow'          => 'Notification admin',
            'heroBadge'        => $requestKind === 'quote' ? 'Devis entrant' : 'Contact entrant',
            'heroSummary'      => $this->adminHeroSummary($requestKind),
            'accentColor'      => $theme['accentColor'],
            'pageBackground'   => $theme['pageBackground'],
            'panelBackground'  => $theme['panelBackground'],
            'footerBackground' => $theme['footerBackground'],
            'borderColor'      => $theme['borderColor'],
            'eyebrowColor'     => $theme['eyebrowColor'],
            'heroBackground'   => $theme['heroBackground'],
            'heroTextColor'    => $theme['heroTextColor'],
            'heroAccent'       => $theme['heroAccent'],
            'badgeBackground'  => $theme['badgeBackground'],
            'badgeColor'       => $theme['badgeColor'],
            'footerNote'       => 'Demande reçue depuis le site. Répondez directement à cet email pour joindre le client.',
        ]);

        $textBody = "Nouvelle demande de {$label}\n\n"
        . $this->buildSummaryText($summaryFields)
        . "\n"
        . $this->renderMenuItemsText($menuItemsView)
        . "\nMessage client\n"
        . (string) ($contactData['message'] ?? '');

        $this->mailer->send(
            $recipients,
            $subject,
            $htmlBody,
            $textBody,
            [
                'email' => (string) ($contactData['email'] ?? ''),
                'name'  => (string) ($contactData['name'] ?? ''),
            ],
        );
    }

    /**
     * @param array<string,mixed> $contactData
     * @param array<int,array<string,mixed>> $menuItems
     */
    private function sendClientAcknowledgement(string $requestKind, int $contactId, array $contactData, array $menuItems): void
    {
        $clientEmail = trim((string) ($contactData['email'] ?? ''));
        if (! filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Adresse client invalide.');
        }

        $label         = $this->requestLabel($requestKind);
        $subject       = sprintf('%s - Nous avons bien reçu votre demande de %s', $this->appName(), $label);
        $summaryFields = $this->summaryFields($contactId, $contactData);
        $menuItemsView = $this->menuItemsViewData($menuItems);
        $theme         = $this->emailTheme($requestKind, 'client');

        $htmlBody = $this->renderEmailTemplate('client-acknowledgement', [
            'requestLabel'        => $label,
            'clientName'          => $this->valueOrFallback($contactData['name'] ?? null),
            'contactId'           => $contactId,
            'introCopy'           => $this->clientIntroCopy($requestKind),
            'nextStepCopy'        => $this->clientNextStepCopy($requestKind),
            'messageBlockTitle'   => $requestKind === 'quote' ? 'Votre brief de départ' : 'Votre message',
            'selectionBlockTitle' => $requestKind === 'quote' ? 'Votre sélection de carte évènementielle' : 'Les éléments mentionnés',
            'ctaBackground'       => $theme['buttonBackground'],
            'ctaTextColor'        => $theme['buttonTextColor'],
            'closingCopy'         => $this->clientClosingCopy($requestKind),
            'clientMessageHtml'   => nl2br($this->escape((string) ($contactData['message'] ?? ''))),
            'summaryFields'       => $summaryFields,
            'menuItems'           => $menuItemsView,
            'ctaLabel'            => $this->clientCtaLabel($requestKind),
            'ctaUrl'              => $this->clientCtaUrl($requestKind),
        ], [
            'title'            => sprintf('Demande de %s bien reçue', $label),
            'preheader'        => sprintf('Votre demande #%d a bien été prise en compte par %s.', $contactId, $this->appName()),
            'eyebrow'          => 'Accusé de réception',
            'heroBadge'        => $requestKind === 'quote' ? 'Demande de devis' : 'Prise de contact',
            'heroSummary'      => $this->clientHeroSummary($requestKind),
            'accentColor'      => $theme['accentColor'],
            'pageBackground'   => $theme['pageBackground'],
            'panelBackground'  => $theme['panelBackground'],
            'footerBackground' => $theme['footerBackground'],
            'borderColor'      => $theme['borderColor'],
            'eyebrowColor'     => $theme['eyebrowColor'],
            'heroBackground'   => $theme['heroBackground'],
            'heroTextColor'    => $theme['heroTextColor'],
            'heroAccent'       => $theme['heroAccent'],
            'badgeBackground'  => $theme['badgeBackground'],
            'badgeColor'       => $theme['badgeColor'],
            'footerNote'       => 'Cet email confirme uniquement la bonne réception de votre demande. Notre équipe vous répond rapidement.',
        ]);

        $textBody = sprintf(
            "Bonjour %s,\n\n%s\n\n%s\n\nRécapitulatif\n%s\n%s\nVotre message\n%s\n\nÀ très vite,\n%s",
            (string) ($contactData['name'] ?? ''),
            $this->clientIntroCopy($requestKind),
            $this->clientNextStepCopy($requestKind),
            $this->buildSummaryText($summaryFields),
            $this->renderMenuItemsText($menuItemsView),
            (string) ($contactData['message'] ?? ''),
            $this->appName(),
        );

        $replyTo = $this->firstAdminRecipient();

        $this->mailer->send(
            [[
                'email' => $clientEmail,
                'name'  => (string) ($contactData['name'] ?? ''),
            ]],
            $subject,
            $htmlBody,
            $textBody,
            $replyTo,
        );
    }

    /**
     * @param array<string,mixed> $contactData
     * @param array<int,array<string,mixed>> $menuItems
     */
    private function sendClientStatusUpdate(
        string $requestKind,
        int $contactId,
        array $contactData,
        array $menuItems,
        string $previousStatus,
        string $nextStatus,
        ?string $customMessage,
        ?string $customSubject
    ): void {
        $clientEmail = trim((string) ($contactData['email'] ?? ''));
        if (! filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Adresse client invalide.');
        }

        $label             = $this->requestLabel($requestKind);
        $statusLabel       = $this->contactStatusLabel($nextStatus);
        $summaryFields     = $this->summaryFieldsWithStatus($contactId, $contactData, $previousStatus, $nextStatus);
        $menuItemsView     = $this->menuItemsViewData($menuItems);
        $theme             = $this->emailTheme($requestKind, 'client');
        $statusMessage     = $this->statusUpdateMessage($requestKind, $nextStatus, $customMessage);
        $statusMessageHtml = nl2br($this->escape($statusMessage));
        $defaultSubject    = $this->statusUpdateSubject($requestKind, $contactId, $nextStatus);
        $subject           = $this->customSubjectOrFallback($customSubject, $defaultSubject);

        $htmlBody = $this->renderEmailTemplate('client-acknowledgement', [
            'requestLabel'        => $label,
            'clientName'          => $this->valueOrFallback($contactData['name'] ?? null),
            'contactId'           => $contactId,
            'referenceLabel'      => $requestKind === 'quote' ? 'Reference de demande' : 'Reference de contact',
            'introCopy'           => $this->statusUpdateIntroCopy($requestKind, $nextStatus),
            'nextStepCopy'        => $this->statusUpdateNextStepCopy($requestKind, $nextStatus),
            'messageBlockTitle'   => 'Point de suivi',
            'selectionBlockTitle' => $requestKind === 'quote' ? 'Base de devis' : 'Elements mentionnes',
            'ctaBackground'       => $theme['buttonBackground'],
            'ctaTextColor'        => $theme['buttonTextColor'],
            'closingCopy'         => $this->statusUpdateClosingCopy($requestKind, $nextStatus),
            'clientMessageHtml'   => $statusMessageHtml,
            'summaryFields'       => $summaryFields,
            'menuItems'           => $menuItemsView,
            'ctaLabel'            => $this->clientCtaLabel($requestKind),
            'ctaUrl'              => $this->clientCtaUrl($requestKind),
        ], [
            'title'            => $this->statusUpdateTitle($requestKind, $nextStatus),
            'preheader'        => $this->statusUpdatePreheader($requestKind, $contactId, $nextStatus),
            'eyebrow'          => 'Suivi client',
            'heroBadge'        => $this->statusUpdateBadge($requestKind, $nextStatus),
            'heroSummary'      => $this->statusUpdateHeroSummary($requestKind, $nextStatus),
            'accentColor'      => $theme['accentColor'],
            'pageBackground'   => $theme['pageBackground'],
            'panelBackground'  => $theme['panelBackground'],
            'footerBackground' => $theme['footerBackground'],
            'borderColor'      => $theme['borderColor'],
            'eyebrowColor'     => $theme['eyebrowColor'],
            'heroBackground'   => $theme['heroBackground'],
            'heroTextColor'    => $theme['heroTextColor'],
            'heroAccent'       => $theme['heroAccent'],
            'badgeBackground'  => $theme['badgeBackground'],
            'badgeColor'       => $theme['badgeColor'],
            'footerNote'       => 'Cet email vous informe d\'une mise a jour de votre dossier. Vous pouvez y repondre directement pour nous preciser un point.',
        ]);

        $textBody = sprintf(
            "Bonjour %s,\n\n%s\n\n%s\n\n%s\n\nRecapitulatif\n%s\n%s\n\nA tres vite,\n%s",
            (string) ($contactData['name'] ?? ''),
            $this->statusUpdateIntroCopy($requestKind, $nextStatus),
            $this->statusUpdateNextStepCopy($requestKind, $nextStatus),
            $statusMessage,
            $this->buildSummaryText($summaryFields),
            $this->renderMenuItemsText($menuItemsView),
            $this->appName(),
        );

        $this->mailer->send(
            [[
                'email' => $clientEmail,
                'name'  => (string) ($contactData['name'] ?? ''),
            ]],
            $subject,
            $htmlBody,
            $textBody,
            $this->firstAdminRecipient(),
        );
    }

    /**
     * @return list<array{email:string,name?:string}>
     */
    private function adminRecipients(): array
    {
        $recipients = [];

        foreach (($this->mailConfig['admin_recipients'] ?? []) as $email) {
            $email = trim((string) $email);
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $recipients[] = ['email' => $email];
        }

        return $recipients;
    }

    /**
     * @return array{email:string,name?:string}|null
     */
    private function firstAdminRecipient(): ?array
    {
        $recipients = $this->adminRecipients();
        return $recipients[0] ?? null;
    }

    private function requestLabel(string $requestKind): string
    {
        return $requestKind === 'quote' ? 'devis' : 'contact';
    }

    private function contactStatusLabel(string $status): string
    {
        return Contact::STATUS_LABELS[$status] ?? ucfirst($status);
    }

    private function statusUpdateSubject(string $requestKind, int $contactId, string $status): string
    {
        $appName = $this->appName();

        if ($requestKind === 'quote') {
            switch ($status) {
                case 'in_progress':
                    return sprintf('%s - Votre devis #%d est en cours d\'etude', $appName, $contactId);
                case 'quoted':
                    return sprintf('%s - Votre devis #%d est pret', $appName, $contactId);
                case 'completed':
                    return sprintf('%s - Votre devis #%d est finalise', $appName, $contactId);
                case 'cancelled':
                    return sprintf('%s - Votre devis #%d a ete cloture', $appName, $contactId);
                default:
                    return sprintf('%s - Suivi de votre devis #%d', $appName, $contactId);
            }
        }

        switch ($status) {
            case 'in_progress':
                return sprintf('%s - Nous etudions votre demande #%d', $appName, $contactId);
            case 'quoted':
                return sprintf('%s - Une proposition est prete pour votre demande #%d', $appName, $contactId);
            case 'completed':
                return sprintf('%s - Votre demande #%d est finalisee', $appName, $contactId);
            case 'cancelled':
                return sprintf('%s - Votre demande #%d a ete cloturee', $appName, $contactId);
            default:
                return sprintf('%s - Suivi de votre demande #%d', $appName, $contactId);
        }
    }

    private function statusUpdateTitle(string $requestKind, string $status): string
    {
        if ($requestKind === 'quote') {
            switch ($status) {
                case 'in_progress':
                    return 'Votre devis est en cours d\'etude';
                case 'quoted':
                    return 'Votre devis est pret';
                case 'completed':
                    return 'Votre devis est finalise';
                case 'cancelled':
                    return 'Votre devis est cloture';
                default:
                    return 'Suivi de votre devis';
            }
        }

        switch ($status) {
            case 'in_progress':
                return 'Votre demande est en cours d\'etude';
            case 'quoted':
                return 'Une proposition est prete';
            case 'completed':
                return 'Votre demande est finalisee';
            case 'cancelled':
                return 'Votre demande est cloturee';
            default:
                return 'Suivi de votre demande';
        }
    }

    private function statusUpdatePreheader(string $requestKind, int $contactId, string $status): string
    {
        $label = $requestKind === 'quote' ? 'devis' : 'demande';

        switch ($status) {
            case 'in_progress':
                return sprintf('Votre %s #%d est maintenant en cours de traitement.', $label, $contactId);
            case 'quoted':
                return sprintf('Votre %s #%d a avance et une proposition est prete.', $label, $contactId);
            case 'completed':
                return sprintf('Votre %s #%d est maintenant finalise.', $label, $contactId);
            case 'cancelled':
                return sprintf('Votre %s #%d a ete cloture.', $label, $contactId);
            default:
                return sprintf('Voici le dernier point de suivi pour votre %s #%d.', $label, $contactId);
        }
    }

    private function statusUpdateBadge(string $requestKind, string $status): string
    {
        if ($requestKind === 'quote') {
            switch ($status) {
                case 'in_progress':
                    return 'Devis en etude';
                case 'quoted':
                    return 'Devis pret';
                case 'completed':
                    return 'Devis finalise';
                case 'cancelled':
                    return 'Devis cloture';
                default:
                    return 'Suivi devis';
            }
        }

        switch ($status) {
            case 'in_progress':
                return 'Demande en etude';
            case 'quoted':
                return 'Proposition prete';
            case 'completed':
                return 'Demande finalisee';
            case 'cancelled':
                return 'Demande cloturee';
            default:
                return 'Suivi demande';
        }
    }

    private function appName(): string
    {
        return (string) ($this->mailConfig['from_name'] ?? 'Traiteur Passion');
    }

    /**
     * @param array<string,mixed> $contactData
     * @return array<int,array{label:string,value:string}>
     */
    private function summaryFields(int $contactId, array $contactData): array
    {
        return [
            ['label' => 'Référence', 'value' => '#' . $contactId],
            ['label' => 'Nom', 'value' => (string) ($contactData['name'] ?? '')],
            ['label' => 'Email', 'value' => (string) ($contactData['email'] ?? '')],
            ['label' => 'Téléphone', 'value' => $this->valueOrFallback($contactData['phone'] ?? null)],
            ['label' => 'Nombre de personnes', 'value' => $this->valueOrFallback($contactData['people'] ?? null)],
            ['label' => 'Date de l’événement', 'value' => $this->formatDate($contactData['date'] ?? null)],
            ['label' => 'Lieu', 'value' => $this->valueOrFallback($contactData['location'] ?? null)],
            ['label' => 'Type d’événement', 'value' => $this->valueOrFallback($contactData['type'] ?? null)],
        ];
    }

    /**
     * @param array<string,mixed> $contactData
     * @return array<int,array{label:string,value:string}>
     */
    private function summaryFieldsWithStatus(int $contactId, array $contactData, string $previousStatus, string $nextStatus): array
    {
        $fields = [
            ['label' => 'Statut precedent', 'value' => $this->contactStatusLabel($previousStatus)],
            ['label' => 'Statut actuel', 'value' => $this->contactStatusLabel($nextStatus)],
        ];

        return array_merge($this->summaryFields($contactId, $contactData), $fields);
    }

    /**
     * @param array<int,array{label:string,value:string}> $summaryFields
     */
    private function buildSummaryText(array $summaryFields): string
    {
        $lines = array_map(
            static fn(array $field): string => $field['label'] . ': ' . $field['value'],
            $summaryFields,
        );

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<int,array<string,mixed>> $menuItems
     * @return array<int,array{category:string,name:string,price:string,quantity:string,image_url:?string,image_alt:string,detail:string}>
     */
    private function menuItemsViewData(array $menuItems): array
    {
        return array_map(function (array $item): array {
            $imageUrl = trim((string) ($item['image_path'] ?? ''));
            $detail   = trim((string) ($item['item_description'] ?? ''));

            return [
                'category' => (string) (($item['category'] ?? null) ?? ($item['menu_item_category'] ?? '')),
                'name'     => (string) (($item['name'] ?? null) ?? ($item['menu_item_name'] ?? '')),
                'price'    => (string) (($item['price'] ?? null) ?? ($item['menu_item_price'] ?? 'Sur devis')),
                'quantity' => (string) ($item['quantity'] ?? 1),
                'image_url' => $imageUrl !== '' ? $imageUrl : null,
                'image_alt' => (string) (($item['image_alt'] ?? null) ?? (($item['name'] ?? null) ?? ($item['menu_item_name'] ?? 'Produit'))),
                'detail'    => $detail,
            ];
        }, $menuItems);
    }

    /**
     * @param array<int,array{category:string,name:string,price:string,quantity:string}> $menuItems
     */
    private function renderMenuItemsText(array $menuItems): string
    {
        if ($menuItems === []) {
            return "Sélection de carte évènementielle\n- Aucun item sélectionné.\n";
        }

        $lines = ["Sélection de carte évènementielle"];
        foreach ($menuItems as $item) {
            $lines[] = sprintf(
                '- %s | %s | %s | Qté %s',
                (string) ($item['category'] ?? ''),
                (string) ($item['name'] ?? ''),
                (string) ($item['price'] ?? 'Sur devis'),
                (string) ($item['quantity'] ?? 1),
            );
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<string,mixed> $data
     * @param array{title:string,preheader:string,eyebrow:string,heroBadge:string,heroSummary:string,accentColor:string,pageBackground:string,panelBackground:string,footerBackground:string,borderColor:string,eyebrowColor:string,heroBackground:string,heroTextColor:string,heroAccent:string,badgeBackground:string,badgeColor:string,footerNote:string} $layout
     */
    private function renderEmailTemplate(string $template, array $data, array $layout): string
    {
        $bodyPath = dirname(__DIR__) . '/Views/emails/' . $template . '.php';
        if (! file_exists($bodyPath)) {
            throw new \RuntimeException('Template email introuvable: ' . $template);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $bodyPath;
        $content = (string) ob_get_clean();

        $layoutPath       = dirname(__DIR__) . '/Views/emails/layout.php';
        $appName          = $this->appName();
        $appUrl           = $this->appUrl();
        $title            = $layout['title'];
        $preheader        = $layout['preheader'];
        $eyebrow          = $layout['eyebrow'];
        $heroBadge        = $layout['heroBadge'];
        $heroSummary      = $layout['heroSummary'];
        $accentColor      = $layout['accentColor'];
        $pageBackground   = $layout['pageBackground'];
        $panelBackground  = $layout['panelBackground'];
        $footerBackground = $layout['footerBackground'];
        $borderColor      = $layout['borderColor'];
        $eyebrowColor     = $layout['eyebrowColor'];
        $heroBackground   = $layout['heroBackground'];
        $heroTextColor    = $layout['heroTextColor'];
        $heroAccent       = $layout['heroAccent'];
        $badgeBackground  = $layout['badgeBackground'];
        $badgeColor       = $layout['badgeColor'];
        $footerNote       = $layout['footerNote'];

        ob_start();
        require $layoutPath;
        return (string) ob_get_clean();
    }

    private function appUrl(): ?string
    {
        return Url::resolveBaseUrl((string) ($this->appConfig['url'] ?? ''));
    }

    private function adminDetailUrl(int $contactId): ?string
    {
        $appUrl = $this->appUrl();
        if ($appUrl === null) {
            return null;
        }

        return $appUrl . '/admin/contacts/' . $contactId;
    }

    private function clientIntroCopy(string $requestKind): string
    {
        if ($requestKind === 'quote') {
            return 'Votre demande de devis est bien arrivée. Nous allons relire votre sélection, vérifier les quantités, le contexte et préparer une base claire pour revenir vers vous.';
        }

        return 'Votre message est bien arrivé chez Traiteur Passion. Nous reprenons votre besoin avec attention pour vous répondre de façon utile et concrète.';
    }

    private function clientNextStepCopy(string $requestKind): string
    {
        if ($requestKind === 'quote') {
            return 'Nous revenons vers vous rapidement avec une première proposition structurée, les points à arbitrer et les éventuelles questions utiles pour valider le bon calibrage.';
        }

        return 'Nous revenons vers vous rapidement pour confirmer le cadre, les contraintes et la meilleure suite à donner à votre demande. Si un élément urgent doit être pris en compte, vous pouvez répondre directement à cet email.';
    }

    private function clientCtaLabel(string $requestKind): ?string
    {
        return $requestKind === 'quote' ? 'Découvrir la carte évènementielle' : 'Revenir sur le site';
    }

    private function clientCtaUrl(string $requestKind): ?string
    {
        $appUrl = $this->appUrl();
        if ($appUrl === null) {
            return null;
        }

        return $requestKind === 'quote' ? $appUrl . '/carte-évènementielle' : $appUrl . '/contact';
    }

    private function clientClosingCopy(string $requestKind): string
    {
        if ($requestKind === 'quote') {
            return 'Si vous souhaitez compléter votre sélection avant notre retour, vous pouvez continuer à parcourir la carte évènementielle et nous indiquer en réponse les éléments qui comptent le plus pour votre événement.';
        }

        return 'Si votre demande comporte un délai ou une contrainte forte, n’hésitez pas à nous le rappeler en réponse à cet email afin que nous puissions prioriser le bon cadrage.';
    }

    private function clientHeroSummary(string $requestKind): string
    {
        if ($requestKind === 'quote') {
            return 'Une première base de commande est enregistrée. Nous la reprenons pour vous proposer une réponse structurée, cohérente et réaliste.';
        }

        return 'Votre message est bien pris en compte. Nous revenons vers vous avec une réponse claire, adaptée à votre besoin et à votre contexte.';
    }

    private function statusUpdateIntroCopy(string $requestKind, string $status): string
    {
        if ($requestKind === 'quote') {
            switch ($status) {
                case 'in_progress':
                    return 'Votre demande de devis est maintenant en cours de traitement par notre equipe.';
                case 'quoted':
                    return 'Votre dossier a avance jusqu\'a l\'etape devis envoye.';
                case 'completed':
                    return 'Votre demande de devis est maintenant cloturee.';
                case 'cancelled':
                    return 'Votre demande de devis a ete annulee ou classee sans suite.';
                default:
                    return 'Votre demande de devis a fait l\'objet d\'une mise a jour.';
            }
        }

        switch ($status) {
            case 'in_progress':
                return 'Votre prise de contact est maintenant en cours de traitement par notre equipe.';
            case 'quoted':
                return 'Votre dossier a avance jusqu\'a l\'etape devis envoye.';
            case 'completed':
                return 'Votre demande est maintenant cloturee.';
            case 'cancelled':
                return 'Votre demande a ete annulee ou classee sans suite.';
            default:
                return 'Votre demande a fait l\'objet d\'une mise a jour.';
        }
    }

    private function statusUpdateNextStepCopy(string $requestKind, string $status): string
    {
        switch ($status) {
            case 'in_progress':
                return 'Nous analysons maintenant votre besoin, les contraintes de date, de volume et les arbitrages utiles avant notre retour. Vous pouvez répondre directement à cet email si un point doit être précisé sans attendre.';
            case 'quoted':
                return 'Si un point manque ou si vous souhaitez ajuster le perimetre, repondez directement a cet email pour que nous puissions affiner la suite.';
            case 'completed':
                return 'Le dossier est considere comme finalise. Nous restons disponibles si vous souhaitez relancer un besoin complementaire.';
            case 'cancelled':
                return 'Si vous souhaitez reouvrir le sujet ou repartir sur une autre base, vous pouvez simplement repondre a ce message.';
            default:
                return $requestKind === 'quote'
                    ? 'Nous vous tenons informes des prochaines etapes sur votre demande de devis.'
                    : 'Nous vous tenons informes de la suite donnee a votre prise de contact.';
        }
    }

    private function statusUpdateClosingCopy(string $requestKind, string $status): string
    {
        if ($status === 'quoted') {
            return 'Conservez bien cette reference et revenez vers nous si vous souhaitez arbitrer une quantite, une composition ou le format de service.';
        }

        if ($status === 'cancelled') {
            return 'Si le contexte change, nous pourrons reprendre votre besoin a partir de cet historique.';
        }

        return $requestKind === 'quote'
            ? 'Vous pouvez continuer a nous preciser vos attentes en reponse a cet email pour accelerer un cadrage propre.'
            : 'Vous pouvez repondre a cet email si vous devez ajouter une precision utile a notre equipe.';
    }

    private function statusUpdateHeroSummary(string $requestKind, string $status): string
    {
        if ($requestKind === 'quote') {
            switch ($status) {
                case 'in_progress':
                    return 'Votre devis est en cours d\'analyse avec vos contraintes et votre selection.';
                case 'quoted':
                    return 'Votre dossier est passe au stade devis envoye et reste ouvert a vos ajustements.';
                case 'completed':
                    return 'Le cycle de devis est maintenant clos sur votre dossier.';
                case 'cancelled':
                    return 'Le dossier a ete ferme, avec possibilite de repartir sur une nouvelle demande.';
                default:
                    return 'Votre demande de devis vient d\'etre mise a jour.';
            }
        }

        switch ($status) {
            case 'in_progress':
                return 'Votre demande est maintenant en cours de reprise par notre equipe.';
            case 'quoted':
                return 'Le dossier a bascule sur l\'etape devis envoye.';
            case 'completed':
                return 'Votre demande est consideree comme finalisee.';
            case 'cancelled':
                return 'Le dossier a ete ferme, avec possibilite de reprendre le sujet ensuite.';
            default:
                return 'Votre prise de contact vient d\'etre mise a jour.';
        }
    }

    private function statusUpdateMessage(string $requestKind, string $status, ?string $customMessage): string
    {
        switch ($status) {
            case 'in_progress':
                $defaultMessage = 'Notre equipe est en train d\'etudier votre dossier.';
                break;
            case 'quoted':
                $defaultMessage = 'Votre dossier est a l\'etape devis envoye. Si un element manque dans nos echanges, dites-le-nous et nous vous repondrons rapidement.';
                break;
            case 'completed':
                $defaultMessage = 'Le suivi de votre dossier est maintenant termine.';
                break;
            case 'cancelled':
                $defaultMessage = 'Le dossier est actuellement classe comme annule.';
                break;
            default:
                $defaultMessage = $requestKind === 'quote'
                    ? 'Votre demande de devis a ete mise a jour.'
                    : 'Votre demande a ete mise a jour.';
                break;
        }

        $customMessage = trim((string) ($customMessage ?? ''));
        if ($customMessage === '') {
            return $defaultMessage;
        }

        return $defaultMessage . "\n\nMessage de notre equipe\n" . $customMessage;
    }

    private function customSubjectOrFallback(?string $customSubject, string $fallback): string
    {
        $customSubject = trim((string) ($customSubject ?? ''));
        return $customSubject === '' ? $fallback : $customSubject;
    }

    private function adminHeroSummary(string $requestKind): string
    {
        if ($requestKind === 'quote') {
            return 'Une nouvelle intention de commande attend une reprise commerciale: sélection, quantités, date et contexte sont déjà centralisés.';
        }

        return 'Un nouveau message client a été reçu. Les éléments utiles pour qualifier la demande sont regroupés ci-dessous.';
    }

    private function adminLeadCopy(string $requestKind): string
    {
        if ($requestKind === 'quote') {
            return 'Une nouvelle demande de devis a été déposée sur le site avec un premier panier de travail à analyser.';
        }

        return 'Une nouvelle prise de contact a été enregistrée sur le site et attend un premier retour de votre part.';
    }

    private function adminActionCopy(string $requestKind): string
    {
        if ($requestKind === 'quote') {
            return 'Point d’attention: vérifiez d’abord la date, le nombre de personnes et la sélection de carte afin de confirmer la faisabilité et le bon calibrage de la proposition.';
        }

        return 'Point d’attention: qualifiez rapidement le besoin, le contexte et l’échéance pour orienter le client vers la bonne formule ou préparer un devis si nécessaire.';
    }

    /**
     * @return array{accentColor:string,pageBackground:string,panelBackground:string,footerBackground:string,borderColor:string,eyebrowColor:string,heroBackground:string,heroTextColor:string,heroAccent:string,badgeBackground:string,badgeColor:string,buttonBackground:string,buttonTextColor:string}
     */
    private function emailTheme(string $requestKind, string $audience): array
    {
        if ($requestKind === 'quote') {
            return [
                'accentColor'      => '#8a5a2b',
                'pageBackground'   => '#efe4d7',
                'panelBackground'  => '#fffaf5',
                'footerBackground' => '#f5ede4',
                'borderColor'      => '#e2d0bb',
                'eyebrowColor'     => '#dcb88f',
                'heroBackground'   => $audience === 'admin' ? '#2b1f18' : '#4b2f1c',
                'heroTextColor'    => '#fff7ef',
                'heroAccent'       => '#f1d1ab',
                'badgeBackground'  => '#f0dfcb',
                'badgeColor'       => '#73431a',
                'buttonBackground' => '#8a5a2b',
                'buttonTextColor'  => '#fffaf5',
            ];
        }

        return [
            'accentColor'      => '#4e6a56',
            'pageBackground'   => '#ebf0ea',
            'panelBackground'  => '#fbfdfb',
            'footerBackground' => '#eef3ee',
            'borderColor'      => '#d4ded5',
            'eyebrowColor'     => '#bdd0c0',
            'heroBackground'   => $audience === 'admin' ? '#243229' : '#355140',
            'heroTextColor'    => '#f6fbf7',
            'heroAccent'       => '#d1e0d3',
            'badgeBackground'  => '#dce8de',
            'badgeColor'       => '#355140',
            'buttonBackground' => '#4e6a56',
            'buttonTextColor'  => '#fbfdfb',
        ];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param mixed $value
     */
    private function valueOrFallback($value): string
    {
        $stringValue = trim((string) ($value ?? ''));
        return $stringValue === '' ? '-' : $stringValue;
    }

    /**
     * @param mixed $value
     */
    private function formatDate($value): string
    {
        $date = trim((string) ($value ?? ''));
        if ($date === '') {
            return '-';
        }

        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dateTime === false) {
            return $date;
        }

        return $dateTime->format('d/m/Y');
    }
}
