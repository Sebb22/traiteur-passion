<?php
declare (strict_types = 1);

namespace App\Services;

use App\Core\Url;
use App\Models\ShopOrder;

final class ShopOrderNotificationService
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
     * @param array<string,mixed> $orderData
     * @return array{enabled:bool,admin_notified:bool,client_ack_sent:bool,errors:list<string>}
     */
    public function dispatch(int $orderId, array $orderData): array
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
                $this->sendAdminNotification($orderId, $orderData);
                $result['admin_notified'] = true;
            } catch (\Throwable $e) {
                $result['errors'][] = 'Notification admin: ' . $e->getMessage();
            }
        }

        if (($this->mailConfig['ack_client'] ?? true) === true) {
            try {
                $this->sendClientAcknowledgement($orderId, $orderData);
                $result['client_ack_sent'] = true;
            } catch (\Throwable $e) {
                $result['errors'][] = 'Accusé de réception client: ' . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $orderData
     * @return array{enabled:bool,client_status_sent:bool,errors:list<string>}
     */
    public function dispatchStatusUpdate(
        int $orderId,
        array $orderData,
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
            $this->sendClientStatusUpdate($orderId, $orderData, $previousStatus, $nextStatus, $customMessage, $customSubject);
            $result['client_status_sent'] = true;
        } catch (\Throwable $e) {
            $result['errors'][] = 'Suivi client: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $orderData
     */
    private function sendAdminNotification(int $orderId, array $orderData): void
    {
        $recipients = $this->adminRecipients();
        if ($recipients === []) {
            throw new \RuntimeException('MAIL_ADMIN_TO est vide ou invalide.');
        }

        $summaryFields = $this->summaryFields($orderId, $orderData);
        $orderItems    = $this->orderItemsViewData($orderData['items'] ?? []);
        $theme         = $this->emailTheme('admin');
        $message       = $this->messageOrFallback($orderData['message'] ?? null);

        $htmlBody = $this->renderEmailTemplate('admin-notification', [
            'requestLabel'        => 'commande boutique',
            'contactId'           => $orderId,
            'leadCopy'            => 'Une nouvelle commande a été validée depuis la boutique en ligne et attend votre prise en charge.',
            'actionCopy'          => 'Vérifiez le retrait demandé, les quantités retenues et confirmez si une livraison peut être proposée dans un rayon de 20 km dès 15 € de commande.',
            'messageBlockTitle'   => 'Message complémentaire',
            'selectionBlockTitle' => 'Contenu de la commande',
            'ctaBackground'       => $theme['buttonBackground'],
            'ctaTextColor'        => $theme['buttonTextColor'],
            'clientMessageHtml'   => nl2br($this->escape($message)),
            'summaryFields'       => $summaryFields,
            'menuItems'           => $orderItems,
            'adminDetailUrl'      => $this->adminOrdersUrl(),
            'ctaLabel'            => 'Voir les commandes boutique',
        ], [
            'title'            => sprintf('Nouvelle commande boutique #%d', $orderId),
            'preheader'        => sprintf('La commande boutique #%d vient d’être enregistrée sur le site.', $orderId),
            'eyebrow'          => 'Notification admin',
            'heroBadge'        => 'Commande boutique',
            'heroSummary'      => 'Une commande client confirmée par panier vient d’entrer dans le flux de préparation.',
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
            'footerNote'       => 'Commande reçue depuis la boutique. Répondez directement à cet email pour joindre le client.',
        ]);

        $textBody = sprintf(
            "Nouvelle commande boutique #%d\n\n%s\n%s\nMessage complémentaire\n%s",
            $orderId,
            $this->buildSummaryText($summaryFields),
            $this->renderOrderItemsText($orderItems),
            $message,
        );

        $this->mailer->send(
            $recipients,
            sprintf('[%s] Nouvelle commande boutique #%d', $this->appName(), $orderId),
            $htmlBody,
            $textBody,
            [
                'email' => (string) ($orderData['customer_email'] ?? ''),
                'name'  => (string) ($orderData['customer_name'] ?? ''),
            ],
        );
    }

    /**
     * @param array<string,mixed> $orderData
     */
    private function sendClientAcknowledgement(int $orderId, array $orderData): void
    {
        $clientEmail = trim((string) ($orderData['customer_email'] ?? ''));
        if (! filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Adresse client invalide.');
        }

        $summaryFields = $this->summaryFields($orderId, $orderData);
        $orderItems    = $this->orderItemsViewData($orderData['items'] ?? []);
        $theme         = $this->emailTheme('client');
        $message       = $this->messageOrFallback($orderData['message'] ?? null);

        $htmlBody = $this->renderEmailTemplate('client-acknowledgement', [
            'requestLabel'        => 'commande boutique',
            'clientName'          => $this->valueOrFallback($orderData['customer_name'] ?? null),
            'contactId'           => $orderId,
            'referenceLabel'      => 'Référence de commande',
            'introCopy'           => 'Votre commande a bien été enregistrée. Notre équipe vérifie maintenant le retrait demandé, l’ordre de préparation et la possibilité éventuelle de livraison.',
            'nextStepCopy'        => 'Nous revenons vers vous rapidement pour confirmer la bonne prise en charge de votre commande, les détails de retrait et, si votre zone le permet, une livraison dans un rayon de 20 km dès 15 € de commande.',
            'messageBlockTitle'   => 'Votre message complémentaire',
            'selectionBlockTitle' => 'Récapitulatif de votre commande',
            'ctaBackground'       => $theme['buttonBackground'],
            'ctaTextColor'        => $theme['buttonTextColor'],
            'closingCopy'         => 'Conservez cette référence de commande. Vous pouvez répondre directement à cet email si vous devez préciser un point avant le retrait ou vérifier la possibilité de livraison.',
            'clientMessageHtml'   => nl2br($this->escape($message)),
            'summaryFields'       => $summaryFields,
            'menuItems'           => $orderItems,
            'ctaLabel'            => 'Revenir à la boutique',
            'ctaUrl'              => $this->shopUrl(),
        ], [
            'title'            => sprintf('Commande boutique #%d bien reçue', $orderId),
            'preheader'        => sprintf('Votre commande boutique #%d a bien été prise en compte par %s.', $orderId, $this->appName()),
            'eyebrow'          => 'Accusé de réception',
            'heroBadge'        => 'Commande boutique',
            'heroSummary'      => 'Votre commande est bien enregistrée. Nous la reprenons maintenant pour confirmer sa préparation, son retrait et, si possible, sa livraison.',
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
            'footerNote'       => 'Cet email confirme la bonne réception de votre commande boutique. Notre équipe vous répond si un ajustement est nécessaire.',
        ]);

        $textBody = sprintf(
            "Bonjour %s,\n\nVotre commande a bien été enregistrée.\n\n%s\n\nRécapitulatif\n%s\n%s\nVotre message complémentaire\n%s\n\nÀ très vite,\n%s",
            (string) ($orderData['customer_name'] ?? ''),
            'Nous revenons vers vous rapidement pour confirmer la prise en charge, le retrait et, si votre zone le permet, la livraison dans un rayon de 20 km dès 15 € de commande.',
            $this->buildSummaryText($summaryFields),
            $this->renderOrderItemsText($orderItems),
            $message,
            $this->appName(),
        );

        $this->mailer->send(
            [[
                'email' => $clientEmail,
                'name'  => (string) ($orderData['customer_name'] ?? ''),
            ]],
            sprintf('%s - Nous avons bien reçu votre commande #%d', $this->appName(), $orderId),
            $htmlBody,
            $textBody,
            $this->firstAdminRecipient(),
        );
    }

    /**
     * @param array<string,mixed> $orderData
     */
    private function sendClientStatusUpdate(
        int $orderId,
        array $orderData,
        string $previousStatus,
        string $nextStatus,
        ?string $customMessage,
        ?string $customSubject
    ): void {
        $clientEmail = trim((string) ($orderData['customer_email'] ?? ''));
        if (! filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Adresse client invalide.');
        }

        $statusLabel       = ShopOrder::STATUS_LABELS[$nextStatus] ?? ucfirst($nextStatus);
        $summaryFields     = $this->summaryFieldsWithStatus($orderId, $orderData, $previousStatus, $nextStatus);
        $orderItems        = $this->orderItemsViewData($orderData['items'] ?? []);
        $theme             = $this->emailTheme('client');
        $statusMessage     = $this->statusUpdateMessage($nextStatus, $customMessage);
        $statusMessageHtml = nl2br($this->escape($statusMessage));
        $defaultSubject    = $this->statusUpdateSubject($orderId, $nextStatus);
        $subject           = $this->customSubjectOrFallback($customSubject, $defaultSubject);

        $htmlBody = $this->renderEmailTemplate('client-acknowledgement', [
            'requestLabel'        => 'commande boutique',
            'clientName'          => $this->valueOrFallback($orderData['customer_name'] ?? null),
            'contactId'           => $orderId,
            'referenceLabel'      => 'Reference de commande',
            'introCopy'           => $this->statusUpdateIntroCopy($nextStatus),
            'nextStepCopy'        => $this->statusUpdateNextStepCopy($nextStatus),
            'messageBlockTitle'   => 'Point de suivi',
            'selectionBlockTitle' => 'Recapitulatif de votre commande',
            'ctaBackground'       => $theme['buttonBackground'],
            'ctaTextColor'        => $theme['buttonTextColor'],
            'closingCopy'         => $this->statusUpdateClosingCopy($nextStatus),
            'clientMessageHtml'   => $statusMessageHtml,
            'summaryFields'       => $summaryFields,
            'menuItems'           => $orderItems,
            'ctaLabel'            => 'Revenir a la boutique',
            'ctaUrl'              => $this->shopUrl(),
        ], [
            'title'            => $this->statusUpdateTitle($nextStatus),
            'preheader'        => $this->statusUpdatePreheader($orderId, $nextStatus),
            'eyebrow'          => 'Suivi client',
            'heroBadge'        => $this->statusUpdateBadge($nextStatus),
            'heroSummary'      => $this->statusUpdateHeroSummary($nextStatus),
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
            'footerNote'       => 'Cet email vous informe d\'une mise a jour de votre commande. Vous pouvez y repondre directement si un point reste a preciser.',
        ]);

        $textBody = sprintf(
            "Bonjour %s,\n\n%s\n\n%s\n\n%s\n\nRecapitulatif\n%s\n%s\n\nA tres vite,\n%s",
            (string) ($orderData['customer_name'] ?? ''),
            $this->statusUpdateIntroCopy($nextStatus),
            $this->statusUpdateNextStepCopy($nextStatus),
            $statusMessage,
            $this->buildSummaryText($summaryFields),
            $this->renderOrderItemsText($orderItems),
            $this->appName(),
        );

        $this->mailer->send(
            [[
                'email' => $clientEmail,
                'name'  => (string) ($orderData['customer_name'] ?? ''),
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

    /**
     * @param array<string,mixed> $orderData
     * @return array<int,array{label:string,value:string}>
     */
    private function summaryFields(int $orderId, array $orderData): array
    {
        $fields = [
            ['label' => 'Référence', 'value' => '#' . $orderId],
            ['label' => 'Client', 'value' => (string) ($orderData['customer_name'] ?? '')],
            ['label' => 'Email', 'value' => (string) ($orderData['customer_email'] ?? '')],
            ['label' => 'Téléphone', 'value' => $this->valueOrFallback($orderData['customer_phone'] ?? null)],
            ['label' => 'Mode', 'value' => $this->fulfillmentLabel($orderData['fulfillment_method'] ?? null)],
            ['label' => 'Date souhaitée', 'value' => $this->formatDate($orderData['pickup_date'] ?? null)],
            ['label' => 'Créneau', 'value' => $this->valueOrFallback($orderData['pickup_slot'] ?? null)],
            ['label' => 'Articles', 'value' => (string) ((int) ($orderData['item_count'] ?? 0))],
            ['label' => 'Statut', 'value' => ShopOrder::STATUS_LABELS[(string) ($orderData['status'] ?? 'new')] ?? ucfirst((string) ($orderData['status'] ?? 'new'))],
            ['label' => 'Enregistrée le', 'value' => $this->formatDateTime($orderData['created_at'] ?? null)],
        ];

        $subtotalCents = max(0, (int) ($orderData['subtotal_cents'] ?? $orderData['total_cents'] ?? 0));
        $discountCents = max(0, (int) ($orderData['discount_cents'] ?? 0));
        if ($discountCents > 0) {
            $promoCode = trim((string) ($orderData['promo_code'] ?? ''));
            if ($promoCode !== '') {
                $fields[] = ['label' => 'Code promo', 'value' => $promoCode];
            }

            $fields[] = ['label' => 'Sous-total', 'value' => $this->formatPrice($subtotalCents)];
            $fields[] = ['label' => 'Remise', 'value' => '- ' . $this->formatPrice($discountCents)];
        }

        $fields[] = ['label' => 'Total', 'value' => $this->formatPrice((int) ($orderData['total_cents'] ?? 0))];

        if ($this->fulfillmentCode($orderData['fulfillment_method'] ?? null) === 'delivery') {
            $fields[] = ['label' => 'Adresse de livraison', 'value' => $this->deliveryAddressLabel($orderData)];
        }

        return $fields;
    }

    /**
     * @param array<string,mixed> $orderData
     * @return array<int,array{label:string,value:string}>
     */
    private function summaryFieldsWithStatus(int $orderId, array $orderData, string $previousStatus, string $nextStatus): array
    {
        $fields = [
            ['label' => 'Statut precedent', 'value' => ShopOrder::STATUS_LABELS[$previousStatus] ?? ucfirst($previousStatus)],
            ['label' => 'Statut actuel', 'value' => ShopOrder::STATUS_LABELS[$nextStatus] ?? ucfirst($nextStatus)],
        ];

        return array_merge($this->summaryFields($orderId, $orderData), $fields);
    }

    /**
     * @param array<int,array<string,mixed>> $orderItems
     * @return array<int,array{category:string,name:string,price:string,quantity:string,image_url:?string,image_alt:string,detail:string}>
     */
    private function orderItemsViewData(array $orderItems): array
    {
        return array_map(function (array $item): array {
            $priceLabel = trim((string) ($item['unit_price_label'] ?? ''));
            $imageUrl   = $this->emailImageUrl($item['image_path'] ?? null);
            $detail     = trim((string) ($item['item_description'] ?? ''));

            return [
                'category'  => (string) ($item['section_name_snapshot'] ?? ''),
                'name'      => (string) ($item['item_name_snapshot'] ?? ''),
                'price'     => $priceLabel !== '' ? $priceLabel : $this->formatPrice((int) ($item['unit_price_cents'] ?? 0)),
                'quantity'  => (string) max(0, (int) ($item['quantity'] ?? 0)),
                'image_url' => $imageUrl,
                'image_alt' => (string) (($item['image_alt'] ?? null) ?? ($item['item_name_snapshot'] ?? 'Produit')),
                'detail'    => $detail,
            ];
        }, $orderItems);
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
     * @param array<int,array{category:string,name:string,price:string,quantity:string}> $orderItems
     */
    private function renderOrderItemsText(array $orderItems): string
    {
        if ($orderItems === []) {
            return "Contenu de la commande\n- Aucun article enregistré.\n";
        }

        $lines = ["Contenu de la commande"];
        foreach ($orderItems as $item) {
            $lines[] = sprintf(
                '- %s | %s | %s | Qté %s',
                (string) ($item['category'] ?? ''),
                (string) ($item['name'] ?? ''),
                (string) ($item['price'] ?? ''),
                (string) ($item['quantity'] ?? '0'),
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

    /**
     * @return array{accentColor:string,pageBackground:string,panelBackground:string,footerBackground:string,borderColor:string,eyebrowColor:string,heroBackground:string,heroTextColor:string,heroAccent:string,badgeBackground:string,badgeColor:string,buttonBackground:string,buttonTextColor:string}
     */
    private function emailTheme(string $audience): array
    {
        return [
            'accentColor'      => '#8f4b2f',
            'pageBackground'   => '#f5ece7',
            'panelBackground'  => '#fffdfa',
            'footerBackground' => '#f2e5dd',
            'borderColor'      => '#e2cfc4',
            'eyebrowColor'     => '#d7ae98',
            'heroBackground'   => $audience === 'admin' ? '#3d241b' : '#6c3d2e',
            'heroTextColor'    => '#fff8f4',
            'heroAccent'       => '#f4cfbe',
            'badgeBackground'  => '#f1ddd2',
            'badgeColor'       => '#7a422d',
            'buttonBackground' => '#8f4b2f',
            'buttonTextColor'  => '#fffdfa',
        ];
    }

    private function appName(): string
    {
        return (string) ($this->mailConfig['from_name'] ?? 'Traiteur Passion');
    }

    private function appUrl(): ?string
    {
        return Url::resolveBaseUrl((string) ($this->appConfig['url'] ?? ''));
    }

    /**
     * @param mixed $value
     */
    private function emailImageUrl($value): ?string
    {
        $imagePath = trim((string) ($value ?? ''));
        if ($imagePath === '') {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $imagePath) === 1 || str_starts_with($imagePath, 'data:') || str_starts_with($imagePath, 'cid:')) {
            return $imagePath;
        }

        $appUrl = $this->appUrl();
        if ($appUrl !== null) {
            if (str_starts_with($imagePath, '/')) {
                return $appUrl . $imagePath;
            }

            return $appUrl . '/' . ltrim($imagePath, '/');
        }

        return $this->emailEmbeddedImage($imagePath);
    }

    private function emailEmbeddedImage(string $imagePath): ?string
    {
        $publicPath = $this->resolvePublicPath($imagePath);
        if ($publicPath === null || ! is_file($publicPath) || ! is_readable($publicPath)) {
            return null;
        }

        $imageData = @file_get_contents($publicPath);
        if (! is_string($imageData) || $imageData === '') {
            return null;
        }

        $mimeType = mime_content_type($publicPath);
        if (! is_string($mimeType) || $mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        return 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
    }

    private function resolvePublicPath(string $imagePath): ?string
    {
        $normalizedPath = ltrim($imagePath, '/');
        if ($normalizedPath === '' || str_contains($normalizedPath, '..')) {
            return null;
        }

        return dirname(__DIR__, 2) . '/public/' . $normalizedPath;
    }

    private function shopUrl(): ?string
    {
        $appUrl = $this->appUrl();
        if ($appUrl === null) {
            return null;
        }

        return $appUrl . '/boutique-en-ligne';
    }

    private function adminOrdersUrl(): ?string
    {
        $appUrl = $this->appUrl();
        if ($appUrl === null) {
            return null;
        }

        return $appUrl . '/admin/contacts#orders';
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

    /**
     * @param mixed $value
     */
    private function formatDateTime($value): string
    {
        $date = trim((string) ($value ?? ''));
        if ($date === '') {
            return '-';
        }

        $timestamp = strtotime($date);
        return $timestamp === false ? $date : date('d/m/Y H:i', $timestamp);
    }

    private function formatPrice(int $cents): string
    {
        return number_format(max(0, $cents) / 100, 2, ',', ' ') . ' €';
    }

    /**
     * @param mixed $value
     */
    private function messageOrFallback($value): string
    {
        $message = trim((string) ($value ?? ''));
        return $message === '' ? 'Aucun message complémentaire.' : $message;
    }

    private function fulfillmentLabel($value): string
    {
        return $this->fulfillmentCode($value) === 'delivery' ? 'Livraison demandée' : 'Retrait demandé';
    }

    private function fulfillmentCode($value): string
    {
        return trim((string) $value) === 'delivery' ? 'delivery' : 'pickup';
    }

    private function deliveryAddressLabel(array $orderData): string
    {
        $parts = array_filter([
            $this->nullableString($orderData['delivery_address'] ?? null),
            trim(sprintf(
                '%s %s',
                $this->nullableString($orderData['delivery_postal_code'] ?? null) ?? '',
                $this->nullableString($orderData['delivery_city'] ?? null) ?? '',
            )),
        ]);

        return $parts === [] ? '-' : implode(', ', $parts);
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function statusUpdateSubject(int $orderId, string $status): string
    {
        $appName = $this->appName();

        switch ($status) {
            case 'confirmed':
                return sprintf('%s - Votre commande #%d est confirmee', $appName, $orderId);
            case 'preparing':
                return sprintf('%s - Votre commande #%d est en preparation', $appName, $orderId);
            case 'ready':
                return sprintf('%s - Votre commande #%d est prete', $appName, $orderId);
            case 'completed':
                return sprintf('%s - Votre commande #%d est finalisee', $appName, $orderId);
            case 'cancelled':
                return sprintf('%s - Votre commande #%d a ete annulee', $appName, $orderId);
            default:
                return sprintf('%s - Suivi de votre commande #%d', $appName, $orderId);
        }
    }

    private function statusUpdateTitle(string $status): string
    {
        switch ($status) {
            case 'confirmed':
                return 'Votre commande est confirmee';
            case 'preparing':
                return 'Votre commande est en preparation';
            case 'ready':
                return 'Votre commande est prete';
            case 'completed':
                return 'Votre commande est finalisee';
            case 'cancelled':
                return 'Votre commande est annulee';
            default:
                return 'Suivi de votre commande';
        }
    }

    private function statusUpdatePreheader(int $orderId, string $status): string
    {
        switch ($status) {
            case 'confirmed':
                return sprintf('Votre commande #%d a bien ete confirmee par notre equipe.', $orderId);
            case 'preparing':
                return sprintf('Votre commande #%d est maintenant en preparation.', $orderId);
            case 'ready':
                return sprintf('Votre commande #%d est maintenant prete.', $orderId);
            case 'completed':
                return sprintf('Votre commande #%d est maintenant finalisee.', $orderId);
            case 'cancelled':
                return sprintf('Votre commande #%d a ete annulee.', $orderId);
            default:
                return sprintf('Voici le dernier point de suivi pour votre commande #%d.', $orderId);
        }
    }

    private function statusUpdateBadge(string $status): string
    {
        switch ($status) {
            case 'confirmed':
                return 'Commande confirmee';
            case 'preparing':
                return 'Preparation en cours';
            case 'ready':
                return 'Commande prete';
            case 'completed':
                return 'Commande finalisee';
            case 'cancelled':
                return 'Commande annulee';
            default:
                return 'Suivi commande';
        }
    }

    private function statusUpdateIntroCopy(string $status): string
    {
        switch ($status) {
            case 'confirmed':
                return 'Votre commande a bien ete confirmee par notre equipe.';
            case 'preparing':
                return 'Votre commande est maintenant en preparation.';
            case 'ready':
                return 'Votre commande est maintenant prete.';
            case 'completed':
                return 'Votre commande est maintenant finalisee.';
            case 'cancelled':
                return 'Votre commande a ete annulee.';
            default:
                return 'Votre commande a fait l\'objet d\'une mise a jour.';
        }
    }

    private function statusUpdateNextStepCopy(string $status): string
    {
        switch ($status) {
            case 'confirmed':
                return 'Nous conservons votre demande dans notre planning et vous recontactons si un ajustement logistique est necessaire. Vous pouvez aussi repondre directement a cet email si un detail doit etre confirme.';
            case 'preparing':
                return 'Notre equipe avance maintenant sur la preparation et le bon deroulement du retrait ou de la livraison. Si un changement de derniere minute est necessaire, repondez directement a cet email.';
            case 'ready':
                return 'Votre commande est prete. Nous vous invitons a venir sur le creneau prevu, ou a nous repondre directement si un ajustement de retrait ou de livraison est necessaire.';
            case 'completed':
                return 'Le dossier est considere comme boucle. Si vous avez besoin d\'un nouveau retrait ou d\'une nouvelle commande, vous pouvez nous recontacter librement.';
            case 'cancelled':
                return 'Si vous souhaitez relancer la commande sur une autre date ou un autre format, repondez simplement a cet email.';
            default:
                return 'Nous vous tenons informes de la suite donnee a votre commande.';
        }
    }

    private function statusUpdateClosingCopy(string $status): string
    {
        if ($status === 'cancelled') {
            return 'Nous restons disponibles si vous souhaitez repartir sur une nouvelle commande ou une autre date.';
        }

        if ($status === 'completed') {
            return 'Merci pour votre confiance. Vous pouvez repondre a cet email si vous souhaitez preparer une prochaine commande.';
        }

        if ($status === 'ready') {
            return 'Conservez cette reference et repondez directement a cet email si vous devez nous prevenir d\'un retard ou d\'un ajustement de retrait.';
        }

        return 'Conservez cette reference de commande. Vous pouvez repondre directement a cet email si un point doit etre precise avant le retrait ou la livraison.';
    }

    private function statusUpdateHeroSummary(string $status): string
    {
        switch ($status) {
            case 'confirmed':
                return 'Votre commande est confirmee et integree dans notre suivi.';
            case 'preparing':
                return 'Votre commande est entree dans le flux de preparation.';
            case 'ready':
                return 'Votre commande est terminee et attend maintenant son retrait ou sa remise.';
            case 'completed':
                return 'Le cycle de votre commande est maintenant clos.';
            case 'cancelled':
                return 'La commande a ete fermee avec statut annule.';
            default:
                return 'Votre commande vient d\'etre mise a jour.';
        }
    }

    private function statusUpdateMessage(string $status, ?string $customMessage): string
    {
        switch ($status) {
            case 'confirmed':
                $defaultMessage = 'Votre commande est bien prise en charge.';
                break;
            case 'preparing':
                $defaultMessage = 'Notre equipe est en train de preparer votre commande.';
                break;
            case 'ready':
                $defaultMessage = 'Votre commande est prete et peut maintenant etre retiree ou remise selon l\'organisation prevue.';
                break;
            case 'completed':
                $defaultMessage = 'Votre commande est marquee comme finalisee.';
                break;
            case 'cancelled':
                $defaultMessage = 'Votre commande est actuellement classee comme annulee.';
                break;
            default:
                $defaultMessage = 'Votre commande a ete mise a jour.';
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
}
