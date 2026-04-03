<div class="adminSplit adminSplit--contacts">
    <!-- LEFT : Illustration -->
    <aside class="adminSplit__media" aria-hidden="true">
        <img class="adminSplit__mediaImg" src="/uploads/pages/admin/adminIllu.png" alt="" loading="lazy" />
        <div class="adminSplit__mediaOverlay"></div>

        <!-- TITRE BAS GAUCHE (dans l’image) -->
        <div class="adminMediaTitle">
            <h1 class="adminMediaTitle__h1">Demandes</h1>
            <p class="adminMediaTitle__sub">Contacts • suivi & gestion</p>
        </div>
    </aside>

    <main class="adminSplit__panel">
        <header class="adminPanelHead">
            <div class="adminPanelHead__row">
                <div class="adminPanelHead__actions">
                    <a href="/admin/catalog" class="adminBtn adminBtn--primary">Catalogue menu</a>
                    <a href="/admin/contacts/export" class="adminBtn adminBtn--primary">Exporter CSV</a>
                    <a href="/" class="adminBtn">Retour au site</a>
                    <form action="/admin/logout" method="post">
                        <button type="submit" class="adminBtn adminBtn--danger">Déconnexion</button>
                    </form>
                </div>
            </div>
        </header>

        <?php
            $total         = count($contacts);
            $new           = count(array_filter($contacts, fn($c) => $c['status'] === 'new'));
            $withMenuItems = 0;

            $contactModel = new \App\Models\Contact();
            foreach ($contacts as $contact) {
                $detail = $contactModel->getById($contact['id']);
                if (! empty($detail['menu_items'])) {
                    $withMenuItems++;
                }
            }
        ?>

        <section class="adminStats adminStats--panel" aria-label="Statistiques">
            <div class="statCard">
                <div class="statCard__label">Total</div>
                <div class="statCard__value"><?php echo $total ?></div>
            </div>

            <div class="statCard">
                <div class="statCard__label">Nouvelles</div>
                <div class="statCard__value"><?php echo $new ?></div>
            </div>

            <div class="statCard">
                <div class="statCard__label">Avec sélection menu</div>
                <div class="statCard__value"><?php echo $withMenuItems ?></div>
            </div>
        </section>

        <section class="adminCard adminCard--table">
            <div class="adminCard__head">
                <div class="adminCard__title">Liste des demandes</div>
                <div class="adminCard__meta">
                    <span class="adminHint">Clique sur un ID pour ouvrir le détail</span>
                </div>
            </div>

            <div class="adminTableWrap" role="region" aria-label="Table des demandes" tabindex="0">
                <table class="adminTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Date événement</th>
                            <th>Type</th>
                            <th>Personnes</th>
                            <th>Message</th>
                            <th>Statut</th>
                            <th>Date création</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($contacts)): ?>
                        <tr>
                            <td colspan="10" class="adminEmptyState">Aucune demande pour le moment</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td>
                                <a href="/admin/contacts/<?php echo $contact['id'] ?>" class="adminLink">
                                    #<?php echo $contact['id'] ?>
                                </a>
                            </td>

                            <td><?php echo htmlspecialchars($contact['name']) ?></td>

                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($contact['email']) ?>" class="adminLink">
                                    <?php echo htmlspecialchars($contact['email']) ?>
                                </a>
                            </td>

                            <td><?php echo htmlspecialchars($contact['phone'] ?? '-') ?></td>
                            <td><?php echo $contact['date'] ? date('d/m/Y', strtotime($contact['date'])) : '-' ?></td>
                            <td><?php echo htmlspecialchars($contact['type'] ?? '-') ?></td>
                            <td><?php echo $contact['people'] ?? '-' ?></td>

                            <td>
                                <div class="adminTextTruncate"
                                    title="<?php echo htmlspecialchars($contact['message']) ?>">
                                    <?php echo htmlspecialchars($contact['message']) ?>
                                </div>
                            </td>

                            <td>
                                <span class="adminBadge adminBadge--<?php echo $contact['status'] ?>">
                                    <?php echo ucfirst($contact['status']) ?>
                                </span>
                            </td>

                            <td><?php echo date('d/m/Y H:i', strtotime($contact['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>