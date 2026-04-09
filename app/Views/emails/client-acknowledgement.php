<?php
    /** @var string $requestLabel */
    /** @var string $clientName */
    /** @var int $contactId */
    /** @var string $introCopy */
    /** @var string $nextStepCopy */
    /** @var string $messageBlockTitle */
    /** @var string $selectionBlockTitle */
    /** @var string $ctaBackground */
    /** @var string $ctaTextColor */
    /** @var string $closingCopy */
    /** @var string $clientMessageHtml */
    /** @var array<int,array{label:string,value:string}> $summaryFields */
    /** @var array<int,array{category:string,name:string,price:string,quantity:string}> $menuItems */
    /** @var string|null $ctaLabel */
    /** @var string|null $ctaUrl */

    $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
            <p style="margin:0 0 4px 0;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#8b6f47;font-weight:700;">Référence de demande</p>
            <p style="margin:0;font-size:26px;line-height:1.2;color:#231f20;font-weight:700;">#<?php echo $e($contactId); ?></p>
        </td>
    </tr>
</table>

<div style="margin:0 0 20px 0;padding:18px 20px;border:1px solid #e6dccf;border-radius:18px;background:#fffdf9;">
    <p style="margin:0 0 10px 0;font-size:13px;letter-spacing:0.14em;text-transform:uppercase;color:#8b6f47;font-weight:700;">Prochaine étape</p>
    <p style="margin:0;font-size:15px;line-height:1.7;color:#231f20;">
        <?php echo $e($nextStepCopy); ?>
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
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;">
            <tr>
                <th style="padding:8px 10px;border:1px solid #e6dccf;background:#f6f0e8;text-align:left;font-size:12px;color:#4d433c;">Catégorie</th>
                <th style="padding:8px 10px;border:1px solid #e6dccf;background:#f6f0e8;text-align:left;font-size:12px;color:#4d433c;">Item</th>
                <th style="padding:8px 10px;border:1px solid #e6dccf;background:#f6f0e8;text-align:left;font-size:12px;color:#4d433c;">Prix</th>
                <th style="padding:8px 10px;border:1px solid #e6dccf;background:#f6f0e8;text-align:left;font-size:12px;color:#4d433c;">Qté</th>
            </tr>
            <?php foreach ($menuItems as $item): ?>
                <tr>
                    <td style="padding:8px 10px;border:1px solid #e6dccf;font-size:14px;color:#231f20;"><?php echo $e($item['category']); ?></td>
                    <td style="padding:8px 10px;border:1px solid #e6dccf;font-size:14px;color:#231f20;"><?php echo $e($item['name']); ?></td>
                    <td style="padding:8px 10px;border:1px solid #e6dccf;font-size:14px;color:#231f20;"><?php echo $e($item['price']); ?></td>
                    <td style="padding:8px 10px;border:1px solid #e6dccf;font-size:14px;color:#231f20;"><?php echo $e($item['quantity']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
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