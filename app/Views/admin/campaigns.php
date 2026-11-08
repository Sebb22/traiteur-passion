<?php
    $campaigns = is_array($campaigns ?? null) ? $campaigns : [];
    $campaign  = is_array($campaign ?? null) ? $campaign : null;
    $flash     = is_array($flash ?? null) ? $flash : null;
    $csrfToken = (string) ($csrfToken ?? '');
    $e         = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };
    $dateInput = static function ($value): string {
    $value = trim((string) $value);
    return $value === '' ? '' : str_replace(' ', 'T', substr($value, 0, 16));
    };
    $statusLabels = ['draft' => 'Brouillon', 'scheduled' => 'Programmée', 'active' => 'Active', 'ended' => 'Terminée'];
    $isEditing    = $campaign !== null;
    $formAction   = $isEditing ? '/admin/campagnes/' . (int) $campaign['id'] : '/admin/campagnes/create';
?>
<div class="adminSplit adminSplit--catalog">
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy">
        <div class="adminSplit__mediaOverlay"></div>
        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1">Campagnes</h1>
            <p class="adminMediaTitle__sub">Communication événementielle</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <?php require dirname(__DIR__) . '/partials/admin-navigation.php'; ?>
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div>
                    <h2 class="adminTitle">Campagnes événementielles</h2>
                    <p class="adminSubtitle">Créez des fenêtres promotionnelles réutilisables pour vos temps forts.</p>
                </div>
                <div class="adminPanelHead__actions">
                    <a href="/admin/campagnes/create" class="adminBtn adminBtn--primary">Nouvelle campagne</a>
                    <form action="/admin/logout" method="post"><button type="submit" class="adminBtn adminBtn--danger">Déconnexion</button></form>
                </div>
            </div>
        </header>

        <?php if ($flash !== null): ?>
        <div class="adminFlash adminFlash--<?php echo $e($flash['type'] ?? 'success'); ?>" role="status">
            <?php echo $e($flash['message'] ?? 'Modification enregistrée.'); ?>
        </div>
        <?php endif; ?>

        <?php if ($isEditing || ! empty($focusForm)): ?>
        <section class="adminCard" id="campaign-form">
            <div class="adminCard__header">
                <div>
                    <h3><?php echo $isEditing ? 'Modifier la campagne' : 'Créer une campagne'; ?></h3>
                    <p class="adminHint">Une campagne désactivée reste disponible dans l’historique.</p>
                </div>
                <a class="adminBtn adminBtn--secondary" href="/admin/campagnes">Annuler</a>
            </div>
            <form action="<?php echo $e($formAction); ?>" method="post" enctype="multipart/form-data" class="adminForm">
                <input type="hidden" name="_csrf" value="<?php echo $e($csrfToken); ?>">
                <div class="adminEditorGrid">
                    <label class="adminField"><span class="adminField__label">Nom interne *</span><input class="adminInput" name="internal_name" required maxlength="180" value="<?php echo $e($campaign['internal_name'] ?? ''); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Titre visible *</span><input class="adminInput" name="visible_title" required maxlength="180" value="<?php echo $e($campaign['visible_title'] ?? ''); ?>"></label>
                    <label class="adminField adminField--full"><span class="adminField__label">Description courte *</span><textarea class="adminTextarea" name="short_description" required rows="4"><?php echo $e($campaign['short_description'] ?? ''); ?></textarea></label>
                    <label class="adminField"><span class="adminField__label">Bouton principal *</span><input class="adminInput" name="primary_button_label" required maxlength="120" value="<?php echo $e($campaign['primary_button_label'] ?? ''); ?>"></label>
                    <label class="adminField"><span class="adminField__label">URL de destination</span><input class="adminInput" type="text" name="destination_url" placeholder="/ma-page ou https://..." value="<?php echo $e($campaign['destination_url'] ?? ''); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Ouverture</span><select class="adminInput" name="destination_target"><option value="_self"<?php echo($campaign['destination_target'] ?? '_blank') === '_self' ? ' selected' : ''; ?>>Même onglet</option><option value="_blank"<?php echo($campaign['destination_target'] ?? '_blank') === '_blank' ? ' selected' : ''; ?>>Nouvel onglet (recommandé)</option></select><small class="adminHint">Le navigateur ouvrira la destination dans un nouvel onglet.</small></label>
                    <label class="adminField"><span class="adminField__label">Bouton secondaire</span><input class="adminInput" name="secondary_button_label" maxlength="120" value="<?php echo $e($campaign['secondary_button_label'] ?? ''); ?>" placeholder="Continuer ma visite"></label>
                    <label class="adminField"><span class="adminField__label">Visuel principal</span><input type="hidden" name="MAX_FILE_SIZE" value="8388608"><input class="adminInput" type="file" name="image_file" accept="image/jpeg,image/png,image/webp"><small class="adminHint">JPEG, PNG ou WebP, 8 Mo maximum. Si un fichier est déjà enregistré, laissez ce champ vide pour le conserver.</small></label>
                    <label class="adminField"><span class="adminField__label">Texte alternatif</span><input class="adminInput" name="image_alt" maxlength="255" value="<?php echo $e($campaign['image_alt'] ?? ''); ?>"></label>
                </div>

                <h4 class="adminEditorBlock__title">Programmation</h4>
                <div class="adminEditorGrid">
                    <label class="adminField"><span class="adminField__label">Début</span><input class="adminInput" type="datetime-local" name="starts_at" value="<?php echo $e($dateInput($campaign['starts_at'] ?? '')); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Fin</span><input class="adminInput" type="datetime-local" name="ends_at" value="<?php echo $e($dateInput($campaign['ends_at'] ?? '')); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Fréquence</span><select class="adminInput" name="display_frequency"><option value="session"<?php echo($campaign['display_frequency'] ?? 'session') === 'session' ? ' selected' : ''; ?>>Une fois par session</option><option value="days"<?php echo($campaign['display_frequency'] ?? '') === 'days' ? ' selected' : ''; ?>>Une fois tous les X jours</option><option value="visit"<?php echo($campaign['display_frequency'] ?? '') === 'visit' ? ' selected' : ''; ?>>À chaque nouvelle visite</option></select></label>
                    <label class="adminField"><span class="adminField__label">Intervalle en jours</span><input class="adminInput" type="number" min="1" max="365" name="display_interval_days" value="<?php echo $e($campaign['display_interval_days'] ?? ''); ?>"></label>
                    <label class="adminField"><span class="adminField__label">Délai avant apparition</span><input class="adminInput" type="number" min="0" max="60" name="appearance_delay_seconds" value="<?php echo (int) ($campaign['appearance_delay_seconds'] ?? 3); ?>"></label>
                    <label class="adminField adminField--checkbox"><input type="checkbox" name="manual_enabled" value="1"<?php echo ! empty($campaign['manual_enabled']) ? ' checked' : ''; ?>> <span>Activation manuelle</span></label>
                </div>

                <h4 class="adminEditorBlock__title">Options d’affichage</h4>
                <div class="adminEditorGrid">
                    <label class="adminField"><span class="adminField__label">Périmètre</span><select class="adminInput" name="display_scope"><option value="homepage"<?php echo($campaign['display_scope'] ?? 'homepage') === 'homepage' ? ' selected' : ''; ?>>Accueil uniquement</option><option value="all"<?php echo($campaign['display_scope'] ?? '') === 'all' ? ' selected' : ''; ?>>Toutes les pages publiques</option></select></label>
                    <label class="adminField adminField--checkbox"><input type="checkbox" name="close_with_button" value="1"<?php echo ! empty($campaign['close_with_button']) || ! $isEditing ? ' checked' : ''; ?>> <span>Autoriser la fermeture par croix</span></label>
                    <label class="adminField adminField--checkbox"><input type="checkbox" name="close_on_overlay" value="1"<?php echo ! empty($campaign['close_on_overlay']) || ! $isEditing ? ' checked' : ''; ?>> <span>Fermer en cliquant à l’extérieur</span></label>
                </div>
                <div class="adminForm__actions"><button type="submit" class="adminBtn adminBtn--primary">Enregistrer</button></div>
            </form>
        </section>
        <?php endif; ?>

        <section class="adminCard">
            <div class="adminCard__header"><div><h3>Campagnes précédentes</h3><p class="adminHint"><?php echo count($campaigns); ?> campagne(s) conservée(s).</p></div></div>
            <?php if ($campaigns === []): ?>
                <p class="adminEmptyState">Aucune campagne créée.</p>
            <?php else: ?>
            <div class="adminTableWrap"><table class="adminTable"><thead><tr><th>Campagne</th><th>Statut</th><th>Programmation</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($campaigns as $item): ?>
                <tr>
                    <td><strong><?php echo $e($item['internal_name'] ?? ''); ?></strong><br><span class="adminHint"><?php echo $e($item['visible_title'] ?? ''); ?></span></td>
                    <td><span class="adminStatus adminStatus--<?php echo $e($item['computed_status'] ?? 'draft'); ?>"><?php echo $e($statusLabels[$item['computed_status'] ?? 'draft'] ?? 'Brouillon'); ?></span></td>
                    <td><?php echo $e($dateInput($item['starts_at'] ?? '') ?: 'Immédiate'); ?> → <?php echo $e($dateInput($item['ends_at'] ?? '') ?: 'Sans fin'); ?></td>
                    <td class="adminTable__actions"><a class="adminBtn adminBtn--secondary" href="/admin/campagnes/<?php echo (int) $item['id']; ?>/edit">Modifier</a><a class="adminBtn adminBtn--secondary" target="_blank" rel="noopener" href="/admin/campagnes/<?php echo (int) $item['id']; ?>/preview">Prévisualiser</a><form action="/admin/campagnes/<?php echo (int) $item['id']; ?>/toggle" method="post"><input type="hidden" name="_csrf" value="<?php echo $e($csrfToken); ?>"><button class="adminBtn <?php echo ! empty($item['manual_enabled']) ? 'adminBtn--danger' : 'adminBtn--primary'; ?>" type="submit"><?php echo ! empty($item['manual_enabled']) ? 'Désactiver' : 'Activer'; ?></button></form><form action="/admin/campagnes/<?php echo (int) $item['id']; ?>/delete" method="post" onsubmit="return window.confirm('Supprimer cette campagne ?');"><input type="hidden" name="_csrf" value="<?php echo $e($csrfToken); ?>"><button class="adminBtn adminBtn--danger" type="submit">Supprimer</button></form></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>
        </section>
    </main>
</div>
