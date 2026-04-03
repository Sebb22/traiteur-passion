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
                    <input type="password" name="password" required autocomplete="current-password" class="adminLoginField__input">
                </label>

                <button type="submit" class="adminLoginSubmit">
                    Se connecter
                </button>
            </form>
        </section>
    </main>
</div>
