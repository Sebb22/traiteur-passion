<?php
    $eventCampaign = is_array($campaign ?? null) ? $campaign : [];
    $preview       = true;
?>
<div class="campaignPreviewPage">
    <a class="adminBtn adminBtn--secondary" href="/admin/campagnes">Retour aux campagnes</a>
    <?php require dirname(__DIR__) . '/partials/event-campaign-popup.php'; ?>
</div>
