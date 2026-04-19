export function initAdminLogin() {
    const passwordGroups = document.querySelectorAll("[data-password-toggle]");

    passwordGroups.forEach((group) => {
        const input = group.querySelector("[data-password-toggle-input]");
        const button = group.querySelector("[data-password-toggle-button]");

        if (!(input instanceof HTMLInputElement) || !(button instanceof HTMLButtonElement)) {
            return;
        }

        const updateState = (isVisible) => {
            input.type = isVisible ? "text" : "password";
            button.setAttribute("aria-pressed", isVisible ? "true" : "false");
            button.setAttribute(
                "aria-label",
                isVisible ? "Masquer le mot de passe" : "Afficher le mot de passe",
            );
            group.classList.toggle("is-visible", isVisible);
        };

        updateState(false);

        button.addEventListener("click", () => {
            updateState(input.type === "password");
        });
    });
}