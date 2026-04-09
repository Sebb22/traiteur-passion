<?php
    /** @var string $appName */
    /** @var string $title */
    /** @var string $preheader */
    /** @var string $eyebrow */
    /** @var string $content */
    /** @var string $footerNote */
    /** @var string $accentColor */
    /** @var string $pageBackground */
    /** @var string $panelBackground */
    /** @var string $footerBackground */
    /** @var string $borderColor */
    /** @var string $eyebrowColor */
    /** @var string $heroBackground */
    /** @var string $heroTextColor */
    /** @var string $heroAccent */
    /** @var string $badgeBackground */
    /** @var string $badgeColor */
    /** @var string $heroBadge */
    /** @var string $heroSummary */
    /** @var string|null $appUrl */

    $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $e($title); ?></title>
</head>
<body style="margin:0;padding:0;background:<?php echo $e($pageBackground); ?>;color:#231f20;">
    <span style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden;">
        <?php echo $e($preheader); ?>
    </span>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:<?php echo $e($pageBackground); ?>;">
        <tr>
            <td style="padding:28px 14px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:720px;margin:0 auto;background:<?php echo $e($panelBackground); ?>;border:1px solid <?php echo $e($borderColor); ?>;border-radius:24px;overflow:hidden;">
                    <tr>
                        <td style="padding:0;">
                            <div style="height:8px;background:<?php echo $e($accentColor); ?>;"></div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px 20px 32px;background:<?php echo $e($heroBackground); ?>;color:<?php echo $e($heroTextColor); ?>;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td>
                                        <span style="display:inline-block;padding:7px 12px;border-radius:999px;background:<?php echo $e($badgeBackground); ?>;color:<?php echo $e($badgeColor); ?>;font-size:11px;letter-spacing:0.18em;text-transform:uppercase;font-weight:700;">
                                            <?php echo $e($heroBadge); ?>
                                        </span>
                                        <p style="margin:18px 0 8px 0;font-size:11px;letter-spacing:0.24em;text-transform:uppercase;color:<?php echo $e($eyebrowColor); ?>;font-weight:700;">
                                            <?php echo $e($eyebrow); ?>
                                        </p>
                                        <h1 style="margin:0 0 10px 0;font-size:32px;line-height:1.15;color:<?php echo $e($heroTextColor); ?>;font-weight:700;">
                                            <?php echo $e($title); ?>
                                        </h1>
                                        <p style="margin:0;max-width:560px;font-size:15px;line-height:1.7;color:<?php echo $e($heroAccent); ?>;">
                                            <?php echo $e($heroSummary); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 10px 32px;">
                            <?php echo $content; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 32px 28px 32px;border-top:1px solid <?php echo $e($borderColor); ?>;background:<?php echo $e($footerBackground); ?>;">
                            <p style="margin:0 0 6px 0;font-size:14px;color:#231f20;font-weight:700;">
                                <?php echo $e($appName); ?>
                            </p>
                            <p style="margin:0 0 8px 0;font-size:13px;line-height:1.6;color:#5c5148;">
                                <?php echo $e($footerNote); ?>
                            </p>
                            <?php if (! empty($appUrl)): ?>
                                <p style="margin:0;font-size:13px;line-height:1.6;">
                                    <a href="<?php echo $e($appUrl); ?>" style="color:<?php echo $e($accentColor); ?>;text-decoration:none;font-weight:700;">
                                        <?php echo $e($appUrl); ?>
                                    </a>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>