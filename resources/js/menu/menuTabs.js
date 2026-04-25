function initMenuTabsInstance(nav) {
    if (!(nav instanceof HTMLElement)) return;

    const panel = nav.closest(".menuPanel") || document.querySelector(".menuPanel--menu");
    if (!(panel instanceof HTMLElement)) return;

    const wheelRoot = nav.closest("[data-wheel-redirect]");
    const shortcut = panel.querySelector("[data-menu-tabs-shortcut]");

    const isMobileViewport = () => window.matchMedia("(max-width: 1279px)").matches;

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

    const hasScrollableRange = (element) => {
        if (!(element instanceof HTMLElement)) return false;

        return element.scrollHeight > element.clientHeight + 2;
    };

    const resolveWheelTarget = () => {
        if (!(wheelRoot instanceof HTMLElement)) return null;

        const targetSelector = wheelRoot.getAttribute("data-wheel-target");
        if (!targetSelector) return null;

        let target = wheelRoot.querySelector(targetSelector);
        if (!target) target = document.querySelector(targetSelector);

        return target instanceof HTMLElement ? target : null;
    };

    const resolveScrollContainer = () => {
        const wheelTarget = resolveWheelTarget();
        if (hasScrollableRange(wheelTarget)) return wheelTarget;

        const rightColumn = nav.closest(".menuSplit__right");
        if (hasScrollableRange(rightColumn)) return rightColumn;

        if (hasScrollableRange(panel)) return panel;

        return null;
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

    const scrollContainerToElement = (container, element) => {
        if (!(container instanceof HTMLElement) || !element) return;

        const containerRect = container.getBoundingClientRect();
        const elementRect = element.getBoundingClientRect();
        const delta = elementRect.top - containerRect.top;
        const targetTop = Math.max(0, container.scrollTop + delta - 8);
        container.scrollTo({ top: targetTop, behavior: "smooth" });
    };

    const scrollToSection = (section) => {
        if (!section) return;

        const scrollContainer = resolveScrollContainer();
        if (scrollContainer) {
            scrollContainerToElement(scrollContainer, section);
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
        const scrollContainer = resolveScrollContainer();
        if (scrollContainer) {
            scrollContainerToElement(scrollContainer, nav);
            return;
        }

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
        else if (event.key === "ArrowLeft") {
            nextIndex = (currentIndex - 1 + links.length) % links.length;
        } else if (event.key === "Home") nextIndex = 0;
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
            root: resolveScrollContainer(),
            rootMargin: "-18% 0px -60% 0px",
            threshold: [0.15, 0.35, 0.6],
        },
    );

    sections.forEach(({ section }) => observer.observe(section));

    if (shortcut) {
        const shortcutScrollContainer = resolveScrollContainer();

        shortcut.addEventListener("click", () => {
            scrollToTabs();
        });

        const toggleShortcut = () => {
            const navRect = nav.getBoundingClientRect();
            const header =
                document.querySelector(".header__container") || document.querySelector(".header");
            const mobileBoundary = header ? header.getBoundingClientRect().bottom : 0;
            const desktopBoundary = 24;
            const topBoundary = isMobileViewport() ? mobileBoundary : desktopBoundary;
            const isPastTabs = navRect.bottom < topBoundary;
            shortcut.classList.toggle("is-visible", isPastTabs);
        };

        toggleShortcut();
        window.addEventListener("scroll", toggleShortcut, { passive: true });
        window.addEventListener("resize", toggleShortcut, { passive: true });
        window.addEventListener("orientationchange", toggleShortcut, {
            passive: true,
        });
        if (shortcutScrollContainer instanceof HTMLElement) {
            shortcutScrollContainer.addEventListener("scroll", toggleShortcut, { passive: true });
        }
    }
}

export function initMenuTabs() {
    const navs = Array.from(document.querySelectorAll("[data-menu-tabs]"));
    if (!navs.length) return;

    navs.forEach((nav) => initMenuTabsInstance(nav));
}
