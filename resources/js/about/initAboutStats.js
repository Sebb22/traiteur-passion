export function initAboutStats() {
    const stats = document.querySelector("[data-about-stats]");
    if (!stats) return;

    const numbers = Array.from(stats.querySelectorAll("[data-counter-target]"));
    if (!numbers.length) return;

    const format = new Intl.NumberFormat("fr-FR");
    const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const cards = Array.from(stats.querySelectorAll(".aboutStat"));

    const paintFinalValues = () => {
        numbers.forEach((number) => {
            const target = Number.parseInt(number.getAttribute("data-counter-target") || "0", 10);
            number.textContent = format.format(Number.isFinite(target) ? target : 0);
        });

        cards.forEach((card, index) => {
            window.setTimeout(() => {
                card.classList.add("is-counter-visible");
            }, index * 90);
        });
    };

    if (reduceMotion) {
        paintFinalValues();
        return;
    }

    let hasStarted = false;

    const animateNumber = (element, target, delay) => {
        const duration = 1400;
        const startAt = performance.now() + delay;

        const tick = (now) => {
            if (now < startAt) {
                requestAnimationFrame(tick);
                return;
            }

            const progress = Math.min((now - startAt) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            element.textContent = format.format(Math.round(target * eased));

            if (progress < 1) {
                requestAnimationFrame(tick);
                return;
            }

            element.textContent = format.format(target);
            element.closest(".aboutStat")?.classList.add("is-counter-visible");
        };

        requestAnimationFrame(tick);
    };

    const start = () => {
        if (hasStarted) return;
        hasStarted = true;

        numbers.forEach((number, index) => {
            const target = Number.parseInt(number.getAttribute("data-counter-target") || "0", 10);
            animateNumber(number, Number.isFinite(target) ? target : 0, index * 110);
        });
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                start();
                observer.disconnect();
            });
        }, { threshold: 0.35 }
    );

    observer.observe(stats);
}