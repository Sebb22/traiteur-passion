<main class="siteMain siteContainer snapY">
    <section class="menuSplit menuSplit--contact" data-wheel-redirect data-wheel-target=".menuPanel--contact">

        <!-- LEFT : visuel -->
        <div class="menuSplit__left" aria-label="Visuel de la page Contact">
            <div class="menuHero menuHero--contact">
                <img class="menuHero__img" src="/uploads/images/contact/contactIllu.png" alt="" aria-hidden="true">
                <h1 class="menuHero__title">Contact</h1>
            </div>
        </div>

        <!-- RIGHT : panel -->
        <div class="menuSplit__right">
            <div class="menuPanel menuPanel--contact">

                <header class="contactHead">
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <h2 class="contactHead__title">Demander un devis</h2>
                    <span class="menuSectionTitle__line" aria-hidden="true"></span>
                    <p class="contactHead__sub">
                        Dites-nous la date, le lieu et le format — réponse rapide avec une proposition claire.
                    </p>
                </header>
                <div class="contactCard">
                    <form class="contactForm" action="/contact" method="post">
                        <div class="contactGrid contactGrid--reservation">

                            <label class="field">
                                <span class="field__label">Nom</span>
                                <input class="field__input" name="name" autocomplete="name" placeholder="Votre nom"
                                    required>
                            </label>

                            <label class="field">
                                <span class="field__label">Email</span>
                                <input class="field__input" type="email" name="email" autocomplete="email"
                                    placeholder="vous@email.fr" required>
                            </label>

                            <label class="field">
                                <span class="field__label">Téléphone</span>
                                <input class="field__input" name="phone" autocomplete="tel" placeholder="+33 …">
                            </label>

                            <label class="field">
                                <span class="field__label">Personnes</span>
                                <input class="field__input" type="number" name="people" min="1" max="500"
                                    placeholder="1–200">
                            </label>

                            <label class="field">
                                <span class="field__label">Date</span>
                                <input class="field__input" type="date" name="date">
                            </label>

                            <label class="field">
                                <span class="field__label">Lieu</span>
                                <input class="field__input" name="location" placeholder="Ville / Salle / Adresse">
                            </label>

                            <label class="field field--full">
                                <span class="field__label">Type d’événement</span>
                                <select class="field__input" name="type">
                                    <option value="mariage">Mariage</option>
                                    <option value="entreprise">Entreprise</option>
                                    <option value="cocktail">Cocktail</option>
                                    <option value="anniversaire">Anniversaire</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </label>

                            <label class="field field--full">
                                <span class="field__label">Message</span>
                                <textarea class="field__input field__textarea" name="message" rows="6"
                                    placeholder="Décrivez le format, vos envies, vos contraintes alimentaires…"
                                    required></textarea>
                            </label>

                        </div>

                        <button class="contactCta" type="submit">Envoyer la demande</button>
                    </form>
                </div>
                <section class="contactInfos" aria-label="Coordonnées">
                    <div class="contactInfo">
                        <span class="contactInfo__k">Téléphone</span>
                        <a class="contactInfo__v" href="tel:+33XXXXXXXXX">+33 …</a>
                    </div>
                    <div class="contactInfo">
                        <span class="contactInfo__k">Email</span>
                        <a class="contactInfo__v"
                            href="mailto:contact@traiteur-passion.fr">contact@traiteur-passion.fr</a>
                    </div>
                    <div class="contactInfo">
                        <span class="contactInfo__k">Zone</span>
                        <span class="contactInfo__v">Vignemont • Oise</span>
                    </div>
                </section>

                <footer class="menuFooter">
                    <p>© <?php echo date('Y') ?> Traiteur Passion</p>
                </footer>

            </div>
        </div>
    </section>
</main>