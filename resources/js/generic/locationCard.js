export function initLocationCards() {
    const cards = Array.from(document.querySelectorAll("[data-location-card]"));

    cards.forEach((card) => {
        if (!(card instanceof HTMLElement)) {
            return;
        }

        const trigger = card.querySelector("[data-location-map-load]");
        const iframe = card.querySelector("[data-location-map-frame]");

        if (!(trigger instanceof HTMLButtonElement) || !(iframe instanceof HTMLIFrameElement)) {
            return;
        }

        trigger.addEventListener("click", () => {
            if (!iframe.src) {
                const nextSrc = iframe.dataset.src || "";
                if (nextSrc) {
                    iframe.src = nextSrc;
                }
            }

            iframe.hidden = false;
            card.classList.add("is-loaded");
        });
    });
}
