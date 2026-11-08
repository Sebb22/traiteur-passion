const STORAGE_PREFIX = "tp-event-campaign-";

function storageKey(root) {
    return `${STORAGE_PREFIX}${root.dataset.campaignId || "active"}`;
}

function hasBeenSeen(root) {
    if (root.dataset.preview === "1") {
        return false;
    }
    const frequency = root.dataset.frequency || "session";
    const key = storageKey(root);

    if (frequency === "session") {
        return window.sessionStorage.getItem(key) === "1";
    }

    if (frequency === "days") {
        const value = Number(window.localStorage.getItem(key) || 0);
        const interval = Math.max(1, Number(root.dataset.intervalDays || 1));
        return value > 0 && Date.now() - value < interval * 86400000;
    }

    return false;
}

function markAsSeen(root) {
    const frequency = root.dataset.frequency || "session";
    const key = storageKey(root);

    if (frequency === "session") {
        window.sessionStorage.setItem(key, "1");
    } else if (frequency === "days") {
        window.localStorage.setItem(key, String(Date.now()));
    }
}

function getFocusable(dialog) {
    return [
        ...dialog.querySelectorAll(
            "a[href], button:not([disabled]), [tabindex]:not([tabindex='-1'])",
        ),
    ];
}

export function initEventCampaignPopup() {
    const root = document.querySelector("[data-event-campaign]");
    if (!root || hasBeenSeen(root)) {
        return;
    }

    const dialog = root.querySelector("[role='dialog']");
    const closeButtons = root.querySelectorAll("[data-event-campaign-close]");
    const overlay = root.querySelector("[data-event-campaign-overlay]");
    const previousFocus = document.activeElement;
    let previousOverflow = "";
    let isClosing = false;

    const close = () => {
        if (isClosing || root.hidden) {
            return;
        }
        isClosing = true;
        markAsSeen(root);
        root.classList.add("is-closing");
        document.body.classList.remove("eventCampaignOpen");
        document.body.style.overflow = previousOverflow;
        document.removeEventListener("keydown", onKeydown);
        window.setTimeout(() => {
            root.hidden = true;
            root.classList.remove("is-closing");
            if (previousFocus && typeof previousFocus.focus === "function") {
                previousFocus.focus({ preventScroll: true });
            }
        }, 240);
    };

    const onKeydown = (event) => {
        if (event.key === "Escape") {
            event.preventDefault();
            close();
            return;
        }
        if (event.key !== "Tab") {
            return;
        }
        const focusable = getFocusable(dialog);
        if (focusable.length === 0) {
            event.preventDefault();
            dialog.focus();
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    };

    closeButtons.forEach((button) => button.addEventListener("click", close));
    const primary = root.querySelector(".eventCampaign__primary");
    if (primary) {
        primary.addEventListener("click", () => markAsSeen(root));
    }
    if (overlay && root.dataset.closeOverlay === "1") {
        overlay.addEventListener("click", close);
    }

    const delay =
        root.dataset.preview === "1" ? 0 : Math.max(0, Number(root.dataset.delay || 0)) * 1000;
    window.setTimeout(() => {
        root.hidden = false;
        previousOverflow = document.body.style.overflow;
        document.body.classList.add("eventCampaignOpen");
        document.body.style.overflow = "hidden";
        document.addEventListener("keydown", onKeydown);
        const firstFocusable = getFocusable(dialog)[0] || dialog;
        firstFocusable.focus({ preventScroll: true });
    }, delay);
}
