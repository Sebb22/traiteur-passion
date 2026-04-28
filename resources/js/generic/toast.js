const TOAST_CONTAINER_SELECTOR = "[data-toast-stack]";
const recentToastTimestamps = new Map();

function getToastContainer() {
    let container = document.querySelector(TOAST_CONTAINER_SELECTOR);
    if (container instanceof HTMLElement) {
        return container;
    }

    container = document.createElement("div");
    container.className = "siteToastStack";
    container.setAttribute("data-toast-stack", "");
    container.setAttribute("aria-live", "polite");
    container.setAttribute("aria-atomic", "false");
    document.body.appendChild(container);
    return container;
}

function normalizeType(value) {
    return ["success", "error", "warning", "info"].includes(value) ? value : "info";
}

function resolveToastTitle(type) {
    if (type === "success") {
        return "Succes";
    }
    if (type === "error") {
        return "Erreur";
    }
    if (type === "warning") {
        return "Attention";
    }
    return "Information";
}

function inferToastType(element) {
    const className = element.className || "";
    if (
        className.includes("--error") ||
        className.includes("is-error") ||
        className.includes("adminLoginError")
    ) {
        return "error";
    }
    if (className.includes("--success") || className.includes("is-success")) {
        return "success";
    }
    if (className.includes("--warning") || className.includes("is-warning")) {
        return "warning";
    }
    return "info";
}

function isVisible(element) {
    if (!(element instanceof HTMLElement) || element.hidden) {
        return false;
    }

    const computedStyle = window.getComputedStyle(element);
    return computedStyle.display !== "none" && computedStyle.visibility !== "hidden";
}

function consumeSourceElement(element) {
    element.hidden = true;
    element.style.display = "none";
    element.setAttribute("data-toast-consumed", "1");
}

export function showToast(message, options = {}) {
    const text = typeof message === "string" ? message.trim() : "";
    if (text === "") {
        return null;
    }

    const type = normalizeType(options.type || "info");
    const duration = Math.max(
        2500,
        Number.parseInt(options.duration, 10) || (type === "error" ? 6500 : 4800),
    );
    const dedupeKey = `${type}:${text}`;
    const now = Date.now();
    const lastShownAt = recentToastTimestamps.get(dedupeKey) || 0;
    if (now - lastShownAt < 1200) {
        return null;
    }
    recentToastTimestamps.set(dedupeKey, now);

    const container = getToastContainer();
    const toast = document.createElement("section");
    toast.className = `siteToast siteToast--${type}`;
    toast.setAttribute("role", type === "error" ? "alert" : "status");

    const closeLabel =
        typeof options.closeLabel === "string" && options.closeLabel.trim() !== ""
            ? options.closeLabel.trim()
            : "Fermer le message";
    const title =
        typeof options.title === "string" && options.title.trim() !== ""
            ? options.title.trim()
            : resolveToastTitle(type);

    toast.innerHTML = `
        <div class="siteToast__accent" aria-hidden="true"></div>
        <div class="siteToast__content">
            <strong class="siteToast__title">${title}</strong>
            <p class="siteToast__message"></p>
        </div>
        <button type="button" class="siteToast__close" aria-label="${closeLabel}">Fermer</button>
    `;

    const messageNode = toast.querySelector(".siteToast__message");
    const closeButton = toast.querySelector(".siteToast__close");
    if (messageNode instanceof HTMLElement) {
        messageNode.textContent = text;
    }

    let dismissed = false;
    let dismissTimer = 0;
    const dismiss = () => {
        if (dismissed) {
            return;
        }
        dismissed = true;
        window.clearTimeout(dismissTimer);
        toast.classList.add("is-leaving");
        window.setTimeout(() => {
            toast.remove();
        }, 220);
    };

    if (closeButton instanceof HTMLButtonElement) {
        closeButton.addEventListener("click", dismiss);
    }

    toast.addEventListener("mouseenter", () => window.clearTimeout(dismissTimer));
    toast.addEventListener("mouseleave", () => {
        dismissTimer = window.setTimeout(dismiss, duration);
    });

    container.appendChild(toast);
    window.requestAnimationFrame(() => {
        toast.classList.add("is-visible");
    });
    dismissTimer = window.setTimeout(dismiss, duration);
    return toast;
}

export function initToastSystem() {
    getToastContainer();

    const sourceSelectors = [
        ".adminFlash",
        ".adminLoginError",
        ".contactAlert",
        "[data-shop-feedback]",
    ];

    Array.from(document.querySelectorAll(sourceSelectors.join(","))).forEach((element) => {
        if (!(element instanceof HTMLElement)) {
            return;
        }
        if (element.getAttribute("data-toast-consumed") === "1" || !isVisible(element)) {
            return;
        }

        const message = element.textContent ? element.textContent.trim() : "";
        if (message !== "") {
            showToast(message, { type: inferToastType(element) });
        }
        consumeSourceElement(element);
    });
}
