<?php
    /** @var string $requestLabel */
    /** @var int $contactId */
    /** @var string $leadCopy */
    /** @var string $actionCopy */
    /** @var string $messageBlockTitle */
    /** @var string $selectionBlockTitle */
    /** @var string $ctaBackground */
    /** @var string $ctaTextColor */
    /** @var string $clientMessageHtml */
    /** @var array<int,array{label:string,value:string}> $summaryFields */
    /** @var array<int,array{category:string,name:string,price:string,quantity:string}> $menuItems */
    /** @var string|null $adminDetailUrl */

    $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<p style="margin:0 0 18px 0;font-size:16px;line-height:1.7;color:#4d433c;">
    <?php echo $e($leadCopy); ?>
</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 22px 0;background:#f8f2ea;border:1px solid #e6dccf;border-radius:18px;">
    <tr>
        <td style="padding:18px 20px;">
            <p style="margin:0 0 4px 0;font-size:12px;letter-spacing:0.18em;text-transform:uppercase;color:#8b6f47;font-weight:700;">Référence</p>
            <p style="margin:0;font-size:26px;line-height:1.2;color:#231f20;font-weight:700;">#<?php echo $e($contactId); ?></p>
        </td>
    </tr>
</table>

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

<div style="margin:0 0 24px 0;padding:16px 18px;border:1px dashed #d4c5b4;border-radius:18px;background:#f8f2ea;">
    <p style="margin:0;font-size:14px;line-height:1.7;color:#4d433c;">
        <?php echo $e($actionCopy); ?>
    </p>
</div>

<div style="margin:0 0 24px 0;padding:18px 20px;border:1px solid #e6dccf;border-radius:18px;background:#fffdf9;">
    <p style="margin:0 0 10px 0;font-size:13px;letter-spacing:0.14em;text-transform:uppercase;color:#8b6f47;font-weight:700;"><?php echo $e($messageBlockTitle); ?></p>
    <div style="font-size:15px;line-height:1.7;color:#231f20;">
        <?php echo $clientMessageHtml; ?>
    </div>
</div>

<div style="margin:0 0 24px 0;padding:18px 20px;border:1px solid #e6dccf;border-radius:18px;background:#fffdf9;">
    <p style="margin:0 0 12px 0;font-size:13px;letter-spacing:0.14em;text-transform:uppercase;color:#8b6f47;font-weight:700;"><?php echo $e($selectionBlockTitle); ?></p>
    <?php if ($menuItems === []): ?>
        <p style="margin:0;font-size:15px;line-height:1.7;color:#4d433c;">Aucun item sélectionné.</p>
    <?php else: ?>
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

<?php if (! empty($adminDetailUrl)): ?>
    <p style="margin:0 0 8px 0;">
        <a href="<?php echo $e($adminDetailUrl); ?>" style="display:inline-block;padding:13px 18px;border-radius:999px;background:<?php echo $e($ctaBackground); ?>;color:<?php echo $e($ctaTextColor); ?>;text-decoration:none;font-weight:700;font-size:14px;">
            Ouvrir la demande dans l'admin
        </a>
    </p>
<?php endif; ?>