<?php
    $ambience  = is_array($ambience ?? null) ? $ambience : [];
    $flash     = is_array($flash ?? null) ? $flash : null;
    $csrfToken = (string) ($csrfToken ?? '');
    $loadError = is_string($loadError ?? null) ? $loadError : null;
    $e = static function ($value): string {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };
    $dateInput = static function ($value): string {
        $value = trim((string) $value);
        return $value === '' ? '' : str_replace(' ', 'T', substr($value, 0, 16));
    };
?>
<div class="adminSplit adminSplit--catalog">
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy">
        <div class="adminSplit__mediaOverlay"></div>
        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1">Ambiances</h1>
            <p class="adminMediaTitle__sub">Saisonnières · site public</p>
        </div>
    </aside>

    <main class="adminSplit__panel" data-seasonal-ambience-admin>
        <?php require dirname(__DIR__) . '/partials/admin-navigation.php'; ?>
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle">Ambiances saisonnières</h2>
                    <p class="adminSubtitle">Ajoutez une neige légère au site public, sans modifier son fond ni ses interactions.</p>
                </div>
                <form action="/admin/logout" method="post"><button type="submit" class="adminBtn adminBtn--danger">Déconnexion</button></form>
            </div>
        </header>

        <?php if ($flash !== null): ?>
        <div class="adminFlash adminFlash--<?php echo $e($flash['type'] ?? 'success'); ?>" role="status"><?php echo $e($flash['message'] ?? 'Modification enregistrée.'); ?></div>
        <?php endif; ?>
        <?php if ($loadError !== null): ?>
        <div class="adminFlash adminFlash--error" role="alert"><?php echo $e($loadError); ?></div>
        <?php endif; ?>

        <section class="adminCard adminCard--padded seasonalAmbienceAdminCard">
            <div class="adminCard__head">
                <div>
                    <div class="adminCard__title">Réglages de la neige</div>
                    <p class="adminHint">La neige reste désactivée par défaut. Les dates sont interprétées dans le fuseau Europe/Paris.</p>
                </div>
                <button type="button" class="adminBtn" data-seasonal-preview>Prévisualiser</button>
            </div>

            <form action="/admin/ambiances-saisonnieres" method="post" class="adminForm">
                <input type="hidden" name="_csrf" value="<?php echo $e($csrfToken); ?>">
                <div class="adminEditorGrid">
                    <label class="adminField adminField--checkbox adminField--full">
                        <input type="checkbox" name="enabled" value="1" data-seasonal-enabled<?php echo ! empty($ambience['enabled']) ? ' checked' : ''; ?>>
                        <span>Activer l’ambiance sur le site public</span>
                    </label>
                    <label class="adminField"><span class="adminField__label">Début</span><input class="adminInput" type="datetime-local" name="starts_at" value="<?php echo $e($dateInput($ambience['starts_at'] ?? '')); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Fin</span><input class="adminInput" type="datetime-local" name="ends_at" value="<?php echo $e($dateInput($ambience['ends_at'] ?? '')); ?>"></label>
                </div>

                <h4 class="adminEditorBlock__title">Rendu</h4>
                <div class="adminEditorGrid">
                    <label class="adminField"><span class="adminField__label">Intensité desktop</span><input class="adminInput" type="number" min="5" max="120" name="snowflake_count" value="<?php echo (int) ($ambience['snowflake_count'] ?? 35); ?>"><small class="adminHint">35 flocons recommandés</small></label>
                    <label class="adminField"><span class="adminField__label">Couleur</span><input class="adminInput" type="color" name="color" value="<?php echo $e($ambience['color'] ?? '#ffffff'); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Opacité min.</span><input class="adminInput" type="number" min="0.05" max="1" step="0.05" name="opacity_min" value="<?php echo $e($ambience['opacity_min'] ?? '0.25'); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Opacité max.</span><input class="adminInput" type="number" min="0.05" max="1" step="0.05" name="opacity_max" value="<?php echo $e($ambience['opacity_max'] ?? '0.75'); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Vitesse min.</span><input class="adminInput" type="number" min="0.05" max="4" step="0.05" name="speed_min" value="<?php echo $e($ambience['speed_min'] ?? '0.35'); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Vitesse max.</span><input class="adminInput" type="number" min="0.05" max="4" step="0.05" name="speed_max" value="<?php echo $e($ambience['speed_max'] ?? '1.10'); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Vent min.</span><input class="adminInput" type="number" min="-4" max="4" step="0.05" name="wind_min" value="<?php echo $e($ambience['wind_min'] ?? '-0.20'); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Vent max.</span><input class="adminInput" type="number" min="-4" max="4" step="0.05" name="wind_max" value="<?php echo $e($ambience['wind_max'] ?? '0.60'); ?>"></label>
                    <label class="adminField adminField--checkbox adminField--full"><input type="checkbox" name="mobile_enabled" value="1" data-seasonal-mobile<?php echo ! empty($ambience['mobile_enabled']) ? ' checked' : ''; ?>> <span>Autoriser une version réduite sur mobile</span></label>
                </div>

                <div class="seasonalAmbienceAdminPreview" data-seasonal-preview-stage hidden aria-label="Prévisualisation de la neige"></div>
                <div class="adminForm__actions">
                    <button type="submit" class="adminBtn adminBtn--primary">Enregistrer</button>
                    <button type="button" class="adminBtn" data-seasonal-preview-close hidden>Arrêter la prévisualisation</button>
                </div>
            </form>
        </section>
    </main>
</div>