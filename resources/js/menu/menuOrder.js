/**
 * Combined plateau order widget:
 * – Per-plateau qty (+/−) starting at 0
 * – Plat type selector (Viande / Poisson / Végétarien)
 * – One "Commander ma sélection" button → builds URL with all params
 * – Mobile fixed CTA bar: scrolls to widget + visibility via IntersectionObserver
 */

export function initMenuOrder() {
    const initComposer = (rootSelector, emptyMessage) => {
        const orderWidget = document.querySelector(rootSelector);
        if (!orderWidget) return;

        const qtyBlocks = Array.from(orderWidget.querySelectorAll(".plateauOrder__optQty"));
        const hint = orderWidget.querySelector(".plateauOrder__hint");
        const submitBtn = orderWidget.querySelector("[data-plateau-submit]");

        qtyBlocks.forEach((block) => {
            const minusBtn = block.querySelector("[data-qty-action='minus']");
            const plusBtn = block.querySelector("[data-qty-action='plus']");
            const qtyVal = block.querySelector(".plateauOrder__qtyVal");
            if (!qtyVal) return;
            let qty = 0;

            const setQty = (delta) => {
                qty = Math.min(99, Math.max(0, qty + delta));
                qtyVal.textContent = qty;
                if (minusBtn) minusBtn.disabled = qty <= 0;
            };

            if (minusBtn) minusBtn.addEventListener("click", () => setQty(-1));
            if (plusBtn) plusBtn.addEventListener("click", () => setQty(1));
        });

        if (submitBtn) {
            submitBtn.addEventListener("click", () => {
                const params = new URLSearchParams();
                const category = orderWidget.dataset.quoteCategory || "";
                let hasAny = false;

                qtyBlocks.forEach((block) => {
                    const paramKey = block.dataset.param;
                    if (!paramKey) return;
                    const qtyVal = block.querySelector(".plateauOrder__qtyVal");
                    const qty = qtyVal ? parseInt(qtyVal.textContent, 10) || 0 : 0;
                    if (qty > 0) {
                        params.set(paramKey, qty);
                        hasAny = true;
                    }
                });

                if (!hasAny) {
                    if (hint) hint.textContent = emptyMessage;
                    return;
                }

                if (category) {
                    params.set("category", category);
                }

                if (hint) hint.textContent = "";
                window.location.href = `/devis?${params.toString()}#quoteForm`;
            });
        }

        orderWidget.addEventListener(
            "click",
            () => {
                if (hint && hint.textContent) hint.textContent = "";
            },
            { capture: true },
        );
    };

    initComposer("[data-plateau-order]", "Veuillez sélectionner au moins 1 plateau.");
    initComposer("[data-aperitif-order]", "Veuillez sélectionner au moins 1 option apéritif.");

    // --- Mobile fixed CTA bar ---
    const mobileCta = document.querySelector(".menuPlateauMobileCta");
    const plateauxSection = document.getElementById("plateaux-repas");

    if (mobileCta && plateauxSection) {
        // Scroll to #plateauOrder on click
        const ctaBtn = mobileCta.querySelector("[data-plateau-cta]");
        if (ctaBtn)
            ctaBtn.addEventListener("click", () => {
                const target = document.getElementById("plateauOrder");
                if (target) {
                    if (target instanceof HTMLDetailsElement) target.open = true;
                    window.setTimeout(() => {
                        target.scrollIntoView({ behavior: "smooth", block: "start" });
                    }, 60);
                }
            });

        // Show/hide via IntersectionObserver
        const observer = new IntersectionObserver(
            ([entry]) => mobileCta.classList.toggle("is-visible", entry.isIntersecting),
            { threshold: 0.05 },
        );
        observer.observe(plateauxSection);
    }
}
