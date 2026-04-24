function formatCountdown(targetDate) {
    const remaining = Math.max(0, targetDate.getTime() - Date.now());
    if (remaining <= 0) {
        return "Offre terminée";
    }

    const totalMinutes = Math.floor(remaining / 60000);
    const days = Math.floor(totalMinutes / (60 * 24));
    const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
    const minutes = totalMinutes % 60;

    if (days > 0) {
        return `Fin dans ${days}j ${String(hours).padStart(2, "0")}h ${String(minutes).padStart(2, "0")}m`;
    }

    return `Fin dans ${String(hours).padStart(2, "0")}h ${String(minutes).padStart(2, "0")}m`;
}

// UX mobile : handle pour masquer/afficher la bannière promo sticky (slide droite)
export function initShopPromoBanner() {
    const countdownNodes = Array.from(document.querySelectorAll("[data-countdown-target]"));
    if (countdownNodes.length === 0) {
        return;
    }

    const update = () => {
        countdownNodes.forEach((node) => {
            const rawTarget = node.getAttribute("data-countdown-target") || "";
            const targetDate = new Date(rawTarget);

            if (Number.isNaN(targetDate.getTime())) {
                node.textContent = "Offre limitée";
                return;
            }

            node.textContent = formatCountdown(targetDate);
        });
    };

    update();
    window.setInterval(update, 30000);

    // Ajout du comportement mobile slide droite
    const promoBanner = document.querySelector('.sitePromoSticky');
    const handle = promoBanner ?.querySelector('.sitePromoSticky__handle');
    // Correction : cibler le bouton onglet globalement
    const tabBtn = document.querySelector('.sitePromoSticky__tab');
    if (!promoBanner || !handle || !tabBtn) {
        console.log('[PromoBanner] Élément(s) manquant(s)', { promoBanner, handle, tabBtn });
        return;
    }

    function isMobile() {
        return window.matchMedia('(max-width: 849px)').matches;
    }

    function hideBanner() {
        promoBanner.classList.add('is-hidden');
        tabBtn.classList.add('is-visible');
        tabBtn.style.display = 'flex';
        tabBtn.style.visibility = 'visible';
        tabBtn.style.pointerEvents = 'auto';
        console.log('[PromoBanner] Bannière masquée');
    }

    function showBanner() {
        promoBanner.classList.remove('is-hidden');
        tabBtn.classList.remove('is-visible');
        tabBtn.style.display = 'none';
        tabBtn.style.visibility = 'hidden';
        tabBtn.style.pointerEvents = 'none';
        console.log('[PromoBanner] Bannière affichée');
    }

    handle.addEventListener('click', (e) => {
        if (isMobile()) {
            console.log('[PromoBanner] Click handle');
            hideBanner();
        }
    });
    // Swipe left (touch)
    let touchStartX = null;
    handle.addEventListener('touchstart', (e) => {
        if (!isMobile()) return;
        touchStartX = e.touches[0].clientX;
    });
    handle.addEventListener('touchend', (e) => {
        if (!isMobile() || touchStartX === null) return;
        const touchEndX = e.changedTouches[0].clientX;
        if (touchEndX - touchStartX < -30) hideBanner(); // swipe left
        touchStartX = null;
    });
    tabBtn.addEventListener('click', (e) => {
        showBanner();
    });
    window.addEventListener('resize', () => {
        if (!isMobile()) {
            showBanner();
        }
    });
    // Initial state (on resize/reload)
    if (!isMobile()) {
        showBanner();
    } else {
        showBanner(); // always start visible on mobile
    }
}