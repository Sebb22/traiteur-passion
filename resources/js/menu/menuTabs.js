export function initMenuTabs() {
    const nav = document.querySelector("[data-menu-tabs]");
    const panel = document.querySelector(".menuPanel--menu");
    const shortcut = document.querySelector("[data-menu-tabs-shortcut]");

    if (!nav || !panel) return;

    const isMobileViewport = () => window.matchMedia("(max-width: 980px)").matches;

    const getClosestTab = (target) => {
        if (target instanceof Element) return target.closest(".menuTabs__tab");
        if (target && target.parentElement) return target.parentElement.closest(".menuTabs__tab");
        return null;
    };

    const findSectionByHref = (href) => {
        if (!href || href.charAt(0) !== "#") return null;

        const id = decodeURIComponent(href.slice(1));
        if (!id) return null;

        const byId = document.getElementById(id);
        if (byId) return byId;

        return panel.querySelector(`[id="${id.replace(/"/g, '\\"')}"]`);
    };

    const isPanelScrollable = () => {
        const style = window.getComputedStyle(panel);
        const overflowY = style.overflowY;
        const allowsScroll = overflowY === "auto" || overflowY === "scroll";
        return allowsScroll && panel.scrollHeight > panel.clientHeight + 2;
    };

    const getScrollOffset = () => {
        const raw = window
            .getComputedStyle(panel)
            .getPropertyValue("--menu-section-scroll-margin")
            .trim();
        const parsed = parseInt(raw, 10);
        return Number.isFinite(parsed) ? parsed : 140;
    };

    const scrollWindowToElement = (element) => {
        if (!element) return;

        const offset = getScrollOffset();
        const targetTop = Math.max(
            0,
            window.scrollY + element.getBoundingClientRect().top - offset + 12,
        );

        window.scrollTo({ top: targetTop, behavior: "smooth" });
    };

    const scrollToSection = (section) => {
        if (!section) return;

        if (isPanelScrollable()) {
            const panelRect = panel.getBoundingClientRect();
            const sectionRect = section.getBoundingClientRect();
            const delta = sectionRect.top - panelRect.top;
            const targetTop = Math.max(0, panel.scrollTop + delta - 8);
            panel.scrollTo({ top: targetTop, behavior: "smooth" });
            return;
        }

        scrollWindowToElement(section);
    };

    const links = Array.from(nav.querySelectorAll(".menuTabs__tab"));
    const sections = links
        .map((link) => {
            const href = link.getAttribute("href");
            const section = findSectionByHref(href);
            return section ? { link, section } : null;
        })
        .filter(Boolean);

    if (!sections.length) return;

    const updateStickyOffsets = () => {
        const isMobile = isMobileViewport();

        if (!isMobile) {
            nav.style.removeProperty("--menu-tabs-sticky-top");
            panel.style.removeProperty("--menu-section-scroll-margin");
            if (shortcut) shortcut.classList.remove("is-visible");
            return;
        }

        const header =
            document.querySelector(".header__container") || document.querySelector(".header");
        const fallback = 88;
        let stickyTop = fallback;

        if (header) {
            const rect = header.getBoundingClientRect();
            stickyTop = Math.max(fallback, Math.round(rect.bottom + 8));
        }

        nav.style.setProperty("--menu-tabs-sticky-top", `${stickyTop}px`);
        panel.style.setProperty("--menu-section-scroll-margin", `${stickyTop + 24}px`);
    };

    updateStickyOffsets();
    window.requestAnimationFrame(updateStickyOffsets);
    window.addEventListener("load", updateStickyOffsets, { passive: true });
    window.addEventListener("resize", updateStickyOffsets, { passive: true });
    window.addEventListener("orientationchange", updateStickyOffsets, {
        passive: true,
    });

    const canScrollTabs = () => nav.scrollWidth > nav.clientWidth + 4;

    const scrollActiveTabIntoView = (tab) => {
        if (!tab || !canScrollTabs()) return;
        tab.scrollIntoView({
            behavior: "smooth",
            inline: "center",
            block: "nearest",
        });
    };

    const scrollToTabs = () => {
        scrollWindowToElement(nav);
    };

    const setActive = (activeLink) => {
        links.forEach((link) => {
            const isActive = link === activeLink;
            link.classList.toggle("is-active", isActive);

            if (isActive) {
                link.setAttribute("aria-current", "location");
            } else {
                link.removeAttribute("aria-current");
            }
        });
    };

    nav.addEventListener("click", (event) => {
        const target = getClosestTab(event.target);
        if (!target) return;

        const href = target.getAttribute("href");
        if (!href || !href.startsWith("#")) return;

        const section = findSectionByHref(href);
        if (!section) return;

        event.preventDefault();
        try {
            scrollToSection(section);
        } catch (_error) {
            window.location.hash = href;
        }
        setActive(target);
        scrollActiveTabIntoView(target);

        if (history.replaceState) {
            history.replaceState(null, "", href);
        }
    });

    nav.addEventListener("keydown", (event) => {
        const current = getClosestTab(event.target);
        if (!current) return;

        const currentIndex = links.indexOf(current);
        if (currentIndex < 0) return;

        let nextIndex = currentIndex;

        if (event.key === "ArrowRight") nextIndex = (currentIndex + 1) % links.length;
        else if (event.key === "ArrowLeft")
            nextIndex = (currentIndex - 1 + links.length) % links.length;
        else if (event.key === "Home") nextIndex = 0;
        else if (event.key === "End") nextIndex = links.length - 1;
        else return;

        event.preventDefault();
        const next = links[nextIndex];
        next.focus();
        scrollActiveTabIntoView(next);
    });

    const observer = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

            if (!visible) return;

            const active = sections.find((item) => item.section === visible.target);
            if (!active) return;

            setActive(active.link);
            if (!isMobileViewport()) {
                scrollActiveTabIntoView(active.link);
            }
        },
        {
            root: isPanelScrollable() ? panel : null,
            rootMargin: "-18% 0px -60% 0px",
            threshold: [0.15, 0.35, 0.6],
        },
    );

    sections.forEach(({ section }) => observer.observe(section));

    if (shortcut) {
        shortcut.addEventListener("click", () => {
            scrollToTabs();
        });

        const toggleShortcut = () => {
            if (!isMobileViewport()) {
                shortcut.classList.remove("is-visible");
                return;
            }

            const navRect = nav.getBoundingClientRect();
            const header =
                document.querySelector(".header__container") || document.querySelector(".header");
            const headerBottom = header ? header.getBoundingClientRect().bottom : 0;
            const isPastTabs = navRect.bottom < headerBottom;
            shortcut.classList.toggle("is-visible", isPastTabs);
        };

        toggleShortcut();
        window.addEventListener("scroll", toggleShortcut, { passive: true });
        window.addEventListener("resize", toggleShortcut, { passive: true });
        window.addEventListener("orientationchange", toggleShortcut, {
            passive: true,
        });
    }
}
