<div class="adminLoginPage">
    <main class="adminLoginContainer">
        <section class="adminLoginBox">
            <h1 class="adminLoginTitle">Admin</h1>
            <p class="adminLoginSubtitle">Connexion sécurisée requise.</p>

            <?php if (! empty($error)): ?>
                <div class="adminLoginError">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="/admin/login" method="post" class="adminLoginForm">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect ?? '/admin/contacts'); ?>">

                <label class="adminLoginField">
                    <span class="adminLoginField__label">Utilisateur</span>
                    <input name="username" required autocomplete="username" class="adminLoginField__input">
                </label>

                <label class="adminLoginField">
                    <span class="adminLoginField__label">Mot de passe</span>
                    <span class="adminLoginPassword" data-password-toggle>
                        <input type="password" name="password" required autocomplete="current-password" class="adminLoginField__input" data-password-toggle-input>
                        <button
                            type="button"
                            class="adminLoginPassword__toggle"
                            aria-label="Afficher le mot de passe"
                            aria-pressed="false"
                            data-password-toggle-button
                        >
                            <span class="adminLoginPassword__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <path d="M1.5 12s3.75-7.5 10.5-7.5S22.5 12 22.5 12 18.75 19.5 12 19.5 1.5 12 1.5 12Z" />
                                    <circle cx="12" cy="12" r="3.25" />
                                </svg>
                                <svg viewBox="0 0 24 24" focusable="false">
                                    <path d="M3 3l18 18" />
                                    <path d="M10.63 6.74A9.58 9.58 0 0 1 12 6.5c6.75 0 10.5 5.5 10.5 5.5a19.1 19.1 0 0 1-3.06 3.61" />
                                    <path d="M6.54 6.54A19.44 19.44 0 0 0 1.5 12s3.75 7.5 10.5 7.5a9.9 9.9 0 0 0 4.01-.84" />
                                    <path d="M9.88 9.88A3 3 0 0 0 9 12a3 3 0 0 0 4.12 2.79" />
                                </svg>
                            </span>
                        </button>
                    </span>
                </label>

                <button type="submit" class="adminLoginSubmit">
                    Se connecter
                </button>
            </form>
        </section>
    </main>
</div>
