<?php
    /** @var string $requestLabel */
    /** @var string $clientName */
    /** @var int $contactId */
    /** @var string|null $referenceValue */
    /** @var string|null $referenceLabel */
    /** @var string $introCopy */
    /** @var string $nextStepCopy */
    /** @var string $messageBlockTitle */
    /** @var string $selectionBlockTitle */
    /** @var string $ctaBackground */
    /** @var string $ctaTextColor */
    /** @var string $closingCopy */
    /** @var string $clientMessageHtml */
    /** @var array<int,array{label:string,value:string}> $summaryFields */
    /** @var array<int,array{category:string,name:string,price:string,quantity:string,image_url:?string,image_alt:string,detail:string}> $menuItems */
    /** @var string|null $ctaLabel */
    /** @var string|null $ctaUrl */

    $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

    $resolvedReference = trim((string) ($referenceValue ?? ''));
    if ($resolvedReference === '') {
    $fallbackReference = trim((string) ($contactId ?? ''));
    $resolvedReference = $fallbackReference === '' ? '-' : '#' . $fallbackReference;
    }
?>
<p style="margin:0 0 16px 0;font-size:17px;line-height:1.7;color:#4d433c;">
    Bonjour <?php echo $e($clientName); ?>,
</p>

<p style="margin:0 0 14px 0;font-size:16px;line-height:1.8;color:#4d433c;">
    <?php echo $e($introCopy); ?>
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 22px 0;background:#f8f2ea;border:1px solid #e6dccf;border-radius:18px;">
    <tr>
        <td style="padding:18px 20px;">
            <p style="margin:0 0 4px 0;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#8b6f47;font-weight:700;"><?php echo $e($referenceLabel ?? 'Référence de demande'); ?></p>
            <p style="margin:0;font-size:26px;line-height:1.2;color:#231f20;font-weight:700;"><?php echo $e($resolvedReference); ?></p>
        </td>
    </tr>
</table>

<div style="margin:0 0 20px 0;padding:18px 20px;border:1px solid #e6dccf;border-radius:18px;background:#fffdf9;">
    <p style="margin:0 0 10px 0;font-size:13px;letter-spacing:0.14em;text-transform:uppercase;color:#8b6f47;font-weight:700;">Prochaine étape</p>
    <p style="margin:0;font-size:15px;line-height:1.7;color:#231f20;">
        <?php echo $e($nextStepCopy); ?>
    </p>
</div>

<div style="margin:0 0 24px 0;padding:16px 18px;border:1px dashed #d4c5b4;border-radius:18px;background:#f8f2ea;">
    <p style="margin:0;font-size:14px;line-height:1.7;color:#4d433c;">
        <strong style="color:#231f20;">Action utile :</strong> conservez la référence <?php echo $e($resolvedReference); ?> et répondez directement à cet email si vous devez ajuster la date, les quantités, le lieu ou une contrainte de service.
    </p>
</div>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 24px 0;border-collapse:collapse;">
    <?php foreach ($summaryFields as $field): ?>
        <tr>
            <td style="padding:9px 12px;border:1px solid #e6dccf;background:#f6f0e8;font-size:13px;font-weight:700;color:#4d433c;width:38%;">
                <?php echo $e($field['label']); ?>
            </td>
            <td style="padding:9px 12px;border:1px solid #e6dccf;background:#fffdf9;font-size:14px;color:#231f20;">
                <?php echo $e($field['value']); ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<div style="margin:0 0 24px 0;padding:18px 20px;border:1px solid #e6dccf;border-radius:18px;background:#fffdf9;">
    <p style="margin:0 0 12px 0;font-size:13px;letter-spacing:0.14em;text-transform:uppercase;color:#8b6f47;font-weight:700;"><?php echo $e($messageBlockTitle); ?></p>
    <div style="font-size:15px;line-height:1.7;color:#231f20;">
        <?php echo $clientMessageHtml; ?>
    </div>
    <?php if ($menuItems !== []): ?>
        <div style="height:1px;background:#e6dccf;margin:18px 0 16px 0;"></div>
        <p style="margin:0 0 12px 0;font-size:13px;letter-spacing:0.14em;text-transform:uppercase;color:#8b6f47;font-weight:700;"><?php echo $e($selectionBlockTitle); ?></p>
        <?php foreach ($menuItems as $item): ?>
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 12px 0;border:1px solid #e6dccf;border-radius:16px;background:#fff9f3;overflow:hidden;">
                <tr>
                    <?php if (! empty($item['image_url'])): ?>
                    <td style="width:92px;padding:14px 0 14px 14px;vertical-align:top;">
                        <img src="<?php echo $e($item['image_url']); ?>" alt="<?php echo $e($item['image_alt']); ?>" style="display:block;width:78px;height:78px;object-fit:cover;border-radius:14px;border:1px solid #eadfd4;">
                    </td>
                    <?php endif; ?>
                    <td style="padding:14px;vertical-align:top;">
                        <p style="margin:0 0 4px 0;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;color:#8b6f47;font-weight:700;"><?php echo $e($item['category']); ?></p>
                        <p style="margin:0 0 6px 0;font-size:17px;line-height:1.4;color:#231f20;font-weight:700;"><?php echo $e($item['name']); ?></p>
                        <?php if (($item['detail'] ?? '') !== ''): ?>
                        <p style="margin:0 0 8px 0;font-size:14px;line-height:1.6;color:#5c5148;"><?php echo $e($item['detail']); ?></p>
                        <?php endif; ?>
                        <p style="margin:0;font-size:14px;line-height:1.6;color:#231f20;">
                            <strong>Prix :</strong> <?php echo $e($item['price']); ?>
                            <span style="display:inline-block;margin-left:12px;"><strong>Qté :</strong> <?php echo $e($item['quantity']); ?></span>
                        </p>
                    </td>
                </tr>
            </table>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<p style="margin:0 0 18px 0;font-size:15px;line-height:1.7;color:#4d433c;">
    <?php echo $e($closingCopy); ?>
</p>

<?php if (! empty($ctaLabel) && ! empty($ctaUrl)): ?>
    <p style="margin:0 0 10px 0;">
        <a href="<?php echo $e($ctaUrl); ?>" style="display:inline-block;padding:13px 18px;border-radius:999px;background:<?php echo $e($ctaBackground); ?>;color:<?php echo $e($ctaTextColor); ?>;text-decoration:none;font-weight:700;font-size:14px;">
            <?php echo $e($ctaLabel); ?>
        </a>
    </p>
<?php endif; ?>