<?php
declare (strict_types = 1);

namespace App\Services;

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
            'selectionBlockTitle' => $requestKind === 'quote' ? 'Votre sélection menu' : 'Les éléments mentionnés',
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
     * @return array<int,array{category:string,name:string,price:string,quantity:string}>
     */
    private function menuItemsViewData(array $menuItems): array
    {
        return array_map(function (array $item): array {
            return [
                'category' => (string) ($item['category'] ?? ''),
                'name'     => (string) ($item['name'] ?? ''),
                'price'    => (string) ($item['price'] ?? 'Sur devis'),
                'quantity' => (string) ($item['quantity'] ?? 1),
            ];
        }, $menuItems);
    }

    /**
     * @param array<int,array{category:string,name:string,price:string,quantity:string}> $menuItems
     */
    private function renderMenuItemsText(array $menuItems): string
    {
        if ($menuItems === []) {
            return "Sélection menu\n- Aucun item sélectionné.\n";
        }

        $lines = ["Sélection menu"];
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
     * @param array{title:string,preheader:string,eyebrow:string,accentColor:string,footerNote:string} $layout
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

        $layoutPath  = dirname(__DIR__) . '/Views/emails/layout.php';
        $appName     = $this->appName();
        $appUrl      = $this->appUrl();
        $title       = $layout['title'];
        $preheader   = $layout['preheader'];
        $eyebrow     = $layout['eyebrow'];
        $accentColor = $layout['accentColor'];
        $footerNote  = $layout['footerNote'];

        ob_start();
        require $layoutPath;
        return (string) ob_get_clean();
    }

    private function appUrl(): ?string
    {
        $url = trim((string) ($this->appConfig['url'] ?? ''));
        return $url === '' ? null : rtrim($url, '/');
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
            return 'Nous revenons vers vous rapidement avec une première proposition, les points à arbitrer et, si nécessaire, les questions utiles pour ajuster le bon calibrage.';
        }

        return 'Nous revenons vers vous rapidement pour confirmer le cadre, les contraintes et la meilleure suite à donner à votre demande.';
    }

    private function clientCtaLabel(string $requestKind): ?string
    {
        return $requestKind === 'quote' ? 'Découvrir le menu' : 'Revenir sur le site';
    }

    private function clientCtaUrl(string $requestKind): ?string
    {
        $appUrl = $this->appUrl();
        if ($appUrl === null) {
            return null;
        }

        return $requestKind === 'quote' ? $appUrl . '/menu' : $appUrl . '/contact';
    }

    private function clientClosingCopy(string $requestKind): string
    {
        if ($requestKind === 'quote') {
            return 'Si vous souhaitez compléter votre sélection avant notre retour, vous pouvez continuer à parcourir le menu et noter les éléments qui comptent le plus pour votre événement.';
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
            return 'Point d’attention: vérifiez d’abord la date, le nombre de personnes et la sélection menu afin de confirmer la faisabilité et le bon calibrage de la proposition.';
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
