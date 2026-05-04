import { showToast } from "../generic/toast";

function clampQuantity(value, max) {
    const number = Number.parseInt(value, 10) || 0;
    return Math.max(0, Math.min(max, number));
}

function pluralize(count, singular, plural) {
    return `${count} ${count === 1 ? singular : plural}`;
}

function parseResponse(response) {
    return response.text().then((text) => {
        try {
            return text ? JSON.parse(text) : {};
        } catch {
            return {};
        }
    });
}

function formatPrice(cents) {
    return new Intl.NumberFormat("fr-FR", {
        style: "currency",
        currency: "EUR",
    }).format((Number.parseInt(cents, 10) || 0) / 100);
}

function normalizeStockUnit(value) {
    return String(value || "").trim() === "g" ? "g" : "unit";
}

function formatStockQuantity(quantity, unit) {
    const amount = Math.max(0, Number.parseInt(quantity, 10) || 0);
    const stockUnit = normalizeStockUnit(unit);

    if (stockUnit === "g") {
        if (amount >= 1000) {
            let kilograms = new Intl.NumberFormat("fr-FR", {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2,
            }).format(amount / 1000);
            kilograms = kilograms.replace(/(?:,0+|,\d*0+)$/, (match) =>
                match.replace(/0+$/, "").replace(/,$/, ""),
            );
            return `${kilograms} kg`;
        }

        return `${amount} g`;
    }

    return `${amount} unité(s)`;
}

function getSelectedOptionData(select) {
    if (!(select instanceof HTMLSelectElement) || select.selectedOptions.length === 0) {
        return null;
    }

    const option = select.selectedOptions[0];
    return {
        id: Number.parseInt(option.getAttribute("data-option-id") || option.value || "0", 10) || 0,
        label: option.getAttribute("data-option-label") || option.textContent || "",
        price: option.getAttribute("data-option-price") || "",
        priceCents: Number.parseInt(option.getAttribute("data-option-price-cents") || "0", 10) || 0,
        quantityUnits: Math.max(
            1,
            Number.parseInt(option.getAttribute("data-option-quantity") || "1", 10) || 1,
        ),
    };
}

function normalizePromoCode(value) {
    return String(value || "")
        .trim()
        .toUpperCase()
        .replace(/[^A-Z0-9_-]+/g, "")
        .slice(0, 40);
}

function clearElementChildren(element) {
    if (!element) {
        return;
    }

    if (typeof element.replaceChildren === "function") {
        element.replaceChildren();
        return;
    }

    while (element.firstChild) {
        element.removeChild(element.firstChild);
    }
}

function bindMediaQueryChange(mediaQueryList, listener) {
    if (!mediaQueryList || typeof listener !== "function") {
        return () => {};
    }

    if (typeof mediaQueryList.addEventListener === "function") {
        mediaQueryList.addEventListener("change", listener);
        return () => mediaQueryList.removeEventListener("change", listener);
    }

    if (typeof mediaQueryList.addListener === "function") {
        mediaQueryList.addListener(listener);
        return () => mediaQueryList.removeListener(listener);
    }

    return () => {};
}

export function initShopPage() {
    const form = document.querySelector("[data-shop-form]");
    if (!form) {
        return;
    }

    const stockEndpoint = form.getAttribute("data-stock-endpoint") || "/api/boutique/stock";
    const submitEndpoint =
        form.getAttribute("data-submit-endpoint") ||
        form.getAttribute("action") ||
        "/boutique-en-ligne";
    const summaryCount = form.querySelector("[data-shop-summary-count]");
    const summaryItems = form.querySelector("[data-shop-summary-items]");
    const summaryState = form.querySelector("[data-shop-summary-state]");
    const summaryDock = form.querySelector("[data-shop-summary-dock]");
    const summary = form.querySelector("[data-shop-summary]");
    const summaryHandle = form.querySelector(".shopSummary__handle");
    const summaryToggle = form.querySelector("[data-shop-summary-toggle]");
    const summaryClose = form.querySelector("[data-shop-summary-close]");
    const summaryOverlay = form.querySelector("[data-shop-summary-overlay]");
    const summaryTabCount = form.querySelector("[data-shop-summary-tab-count]");
    const summaryCountMobile = form.querySelector("[data-shop-summary-count-mobile]");
    const summaryTabTotal = form.querySelector("[data-shop-summary-tab-total]");
    const summaryTabCta = form.querySelector(".shopSummaryTab__cta");
    const summaryBoundsHost = form.closest(".shopPanel") || form.closest(".menuSplit__right");
    const summaryLines = form.querySelector("[data-shop-summary-lines]");
    const summaryTotal = form.querySelector("[data-shop-summary-total]");
    const summarySubtotal = form.querySelector("[data-shop-summary-subtotal]");
    const summaryDiscount = form.querySelector("[data-shop-summary-discount]");
    const feedback = form.querySelector("[data-shop-feedback]");
    const submitButton = form.querySelector("[data-shop-submit]");
    const goCheckoutButton = form.querySelector("[data-shop-go-checkout]");
    const checkout = form.querySelector("[data-shop-checkout]");
    const checkoutAnchor = form.querySelector("#shop-checkout");
    const fulfillmentInputs = Array.from(form.querySelectorAll("[data-shop-fulfillment]"));
    const deliveryPanel = form.querySelector("[data-shop-delivery-panel]");
    const deliveryFields = Array.from(form.querySelectorAll("[data-shop-delivery-field]"));
    const pickupLocationPanel = form.querySelector("[data-shop-pickup-location]");
    const appointmentFields = Array.from(form.querySelectorAll("[data-shop-appointment-field]"));
    const pickupDateInput = form.querySelector("[data-shop-pickup-date]");
    const pickupSlotInput = form.querySelector("[data-shop-pickup-slot]");
    const pickupSlotList = form.querySelector("[data-shop-pickup-slot-list]");
    const pickupSlotHint = form.querySelector("[data-shop-pickup-slot-hint]");
    const cardNodes = Array.from(form.querySelectorAll("[data-shop-item-card]"));
    const lineNodes = Array.from(form.querySelectorAll("[data-shop-order-line]"));
    const stockBadges = Array.from(form.querySelectorAll("[data-shop-stock]"));
    const inputs = Array.from(form.querySelectorAll("[data-shop-qty]"));
    const addButtons = Array.from(form.querySelectorAll("[data-shop-add]"));
    const increaseButtons = Array.from(form.querySelectorAll("[data-shop-increase]"));
    const decreaseButtons = Array.from(form.querySelectorAll("[data-shop-decrease]"));
    const removeButtons = Array.from(form.querySelectorAll("[data-shop-remove]"));
    const promoInput = form.querySelector("[data-shop-promo-input]");
    const promoApplyButton = form.querySelector("[data-shop-promo-apply]");
    const promoState = form.querySelector("[data-shop-promo-state]");
    let isSummaryOpen = false;
    let isSummaryPinned = false;
    let hideSummaryTimeout = null;
    let summaryPromptTimeout = null;
    let summaryDragResetTimeout = null;
    let touchStartY = 0;
    let touchCurrentY = 0;
    let isDraggingSummary = false;
    const desktopToastMedia = window.matchMedia(
        "(min-width: 1320px) and (hover: hover) and (pointer: fine)",
    );
    const desktopQuickAddMedia = window.matchMedia("(min-width: 981px)");
    const summarySheetMedia = window.matchMedia("(max-width: 980px)");
    const compactDrawerMedia = window.matchMedia("(max-width: 980px)");
    let unbindDesktopToastMedia = () => {};
    let unbindDesktopQuickAddMedia = () => {};
    const promoEndsAt = form.getAttribute("data-promo-ends-at") || "";
    const promoEndsAtDate = promoEndsAt ? new Date(promoEndsAt) : null;
    const promoConfig = {
        active: form.getAttribute("data-promo-active") === "1",
        code: normalizePromoCode(form.getAttribute("data-promo-code") || ""),
        title: form.getAttribute("data-promo-title") || "Offre boutique",
        percent: Number.parseInt(form.getAttribute("data-promo-percent") || "0", 10) || 0,
        endsAt: promoEndsAtDate,
    };

    const isDesktopToast = () => desktopToastMedia.matches;
    const isDesktopQuickAdd = () => desktopQuickAddMedia.matches;
    const isSummarySheet = () => summarySheetMedia.matches;
    const isCompactDrawerViewport = () => compactDrawerMedia.matches;

    const getSelectedFulfillmentMethod = () => {
        const selected = fulfillmentInputs.find(
            (input) => input instanceof HTMLInputElement && input.checked,
        );

        return selected instanceof HTMLInputElement ? selected.value : "";
    };

    const wantsDelivery = () => getSelectedFulfillmentMethod() === "delivery";

    const formatPickupTime = (minutes) => {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${String(hours).padStart(2, "0")}:${String(mins).padStart(2, "0")}`;
    };

    const buildPickupSlots = (startMinutes, endMinutes) => {
        const slots = [];
        for (let current = startMinutes; current + 30 <= endMinutes; current += 30) {
            slots.push(`${formatPickupTime(current)} - ${formatPickupTime(current + 30)}`);
        }
        return slots;
    };

    const getPickupSchedule = (dateValue) => {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(dateValue || ""));
        if (!match) {
            return null;
        }

        const year = Number.parseInt(match[1], 10);
        const month = Number.parseInt(match[2], 10) - 1;
        const day = Number.parseInt(match[3], 10);
        const weekday = new Date(year, month, day).getDay();

        if (weekday === 0 || weekday === 1) {
            return {
                closed: true,
                hint: "Retrait indisponible le dimanche et le lundi. Choisissez une date du mardi au samedi.",
                slots: [],
            };
        }

        if (weekday === 6) {
            return {
                closed: false,
                hint: "Samedi: créneaux proposés de 08:30 à 15:30. Ces horaires peuvent légèrement varier selon les prestations.",
                slots: buildPickupSlots(8 * 60 + 30, 15 * 60 + 30),
            };
        }

        return {
            closed: false,
            hint: "Du mardi au vendredi: créneaux proposés de 08:30 à 19:00.",
            slots: buildPickupSlots(8 * 60 + 30, 19 * 60),
        };
    };

    const setPickupHint = (message) => {
        if (pickupSlotHint) {
            pickupSlotHint.textContent = message;
        }
    };

    const syncPickupSlotOptions = (slots) => {
        if (!(pickupSlotList instanceof HTMLDataListElement)) {
            return;
        }

        clearElementChildren(pickupSlotList);
        slots.forEach((slot) => {
            const option = document.createElement("option");
            option.value = slot;
            pickupSlotList.append(option);
        });
    };

    const validatePickupSlotValue = () => {
        if (!(pickupSlotInput instanceof HTMLInputElement)) {
            return true;
        }

        const selectedMethod = getSelectedFulfillmentMethod();

        if (selectedMethod !== "pickup") {
            pickupSlotInput.setCustomValidity("");
            return true;
        }

        const schedule = getPickupSchedule(
            pickupDateInput instanceof HTMLInputElement ? pickupDateInput.value : "",
        );
        const value = pickupSlotInput.value.trim();

        if (!schedule || schedule.closed) {
            pickupSlotInput.setCustomValidity("");
            return false;
        }

        if (value === "") {
            pickupSlotInput.setCustomValidity("Choisissez un créneau de retrait.");
            return false;
        }

        if (!schedule.slots.includes(value)) {
            pickupSlotInput.setCustomValidity(
                "Choisissez un créneau proposé pour cette date de retrait.",
            );
            return false;
        }

        pickupSlotInput.setCustomValidity("");
        return true;
    };

    const syncPickupScheduleState = () => {
        if (!(pickupSlotInput instanceof HTMLInputElement)) {
            return;
        }

        const selectedMethod = getSelectedFulfillmentMethod();
        const deliverySelected = selectedMethod === "delivery";
        const pickupSelected = selectedMethod === "pickup";

        if (pickupDateInput instanceof HTMLInputElement) {
            pickupDateInput.disabled = selectedMethod === "";
            pickupDateInput.required = selectedMethod !== "";
        }

        pickupSlotInput.disabled = selectedMethod === "";
        pickupSlotInput.required = pickupSelected;

        if (selectedMethod === "") {
            if (pickupDateInput instanceof HTMLInputElement) {
                pickupDateInput.setCustomValidity("");
            }
            pickupSlotInput.setCustomValidity("");
            syncPickupSlotOptions([]);
            pickupSlotInput.placeholder = "Choisissez d’abord un mode";
            setPickupHint("Sélectionnez d’abord Retrait ou Livraison.");
            return;
        }

        if (deliverySelected) {
            if (pickupDateInput instanceof HTMLInputElement) {
                pickupDateInput.setCustomValidity("");
            }
            pickupSlotInput.setCustomValidity("");
            syncPickupSlotOptions([]);
            pickupSlotInput.placeholder = "Ex: entre 11:00 et 12:00";
            setPickupHint("Indiquez si besoin un créneau de livraison souhaité.");
            return;
        }

        const schedule = getPickupSchedule(
            pickupDateInput instanceof HTMLInputElement ? pickupDateInput.value : "",
        );

        if (!schedule) {
            if (pickupDateInput instanceof HTMLInputElement) {
                pickupDateInput.setCustomValidity("");
            }
            pickupSlotInput.setCustomValidity("");
            syncPickupSlotOptions([]);
            pickupSlotInput.placeholder = "Choisissez d’abord une date";
            setPickupHint(
                "Créneaux de retrait disponibles du mardi au vendredi de 8:30 à 19:00 et le samedi de 8:30 à 15:30.",
            );
            return;
        }

        setPickupHint(schedule.hint);
        syncPickupSlotOptions(schedule.slots);

        if (schedule.closed) {
            if (pickupDateInput instanceof HTMLInputElement) {
                pickupDateInput.setCustomValidity(
                    "Le retrait boutique est fermé le dimanche et le lundi.",
                );
            }
            pickupSlotInput.value = "";
            pickupSlotInput.placeholder = "Retrait fermé le dimanche et le lundi";
            pickupSlotInput.setCustomValidity("");
            return;
        }

        if (pickupDateInput instanceof HTMLInputElement) {
            pickupDateInput.setCustomValidity("");
        }

        pickupSlotInput.placeholder = "Ex: 11:00 - 11:30";
        if (pickupSlotInput.value && !schedule.slots.includes(pickupSlotInput.value.trim())) {
            pickupSlotInput.value = "";
        }
        validatePickupSlotValue();
    };

    const isPromoAvailable = () => {
        if (!promoConfig.active || !promoConfig.code || promoConfig.percent <= 0) {
            return false;
        }

        if (!(promoConfig.endsAt instanceof Date) || Number.isNaN(promoConfig.endsAt.getTime())) {
            return false;
        }

        return promoConfig.endsAt.getTime() > Date.now();
    };

    const getPromoEvaluation = (subtotalCents) => {
        const normalizedCode = normalizePromoCode(
            promoInput instanceof HTMLInputElement ? promoInput.value : "",
        );

        if (!isPromoAvailable()) {
            return {
                valid: normalizedCode === "",
                code: normalizedCode,
                discountCents: 0,
                totalCents: subtotalCents,
                message: normalizedCode === "" ? "" : "Ce code promo n’est plus disponible.",
            };
        }

        if (normalizedCode === "") {
            return {
                valid: true,
                code: "",
                discountCents: 0,
                totalCents: subtotalCents,
                message: "",
            };
        }

        if (normalizedCode !== promoConfig.code) {
            return {
                valid: false,
                code: normalizedCode,
                discountCents: 0,
                totalCents: subtotalCents,
                message: "Le code promo saisi est invalide.",
            };
        }

        const discountCents = Math.floor(subtotalCents * (promoConfig.percent / 100));
        return {
            valid: true,
            code: normalizedCode,
            discountCents,
            totalCents: Math.max(0, subtotalCents - discountCents),
            message: `${promoConfig.title} appliquée: -${promoConfig.percent}%`,
        };
    };

    const syncPromoState = (evaluation) => {
        if (!promoState) {
            return;
        }

        if (!promoConfig.active) {
            promoState.textContent = "";
            promoState.className = "shopSummary__promoState";
            return;
        }

        if (evaluation.discountCents > 0) {
            promoState.textContent = evaluation.message;
            promoState.className = "shopSummary__promoState is-applied";
            return;
        }

        if (evaluation.code !== "" && !evaluation.valid) {
            promoState.textContent = evaluation.message;
            promoState.className = "shopSummary__promoState is-error";
            return;
        }

        promoState.textContent = "";
        promoState.className = "shopSummary__promoState";
    };

    const clearSummaryDragResetTimeout = () => {
        if (summaryDragResetTimeout) {
            window.clearTimeout(summaryDragResetTimeout);
            summaryDragResetTimeout = null;
        }
    };

    const resetSummaryDrag = () => {
        clearSummaryDragResetTimeout();

        if (!summary) {
            return;
        }

        summary.style.transition = "";
        summary.style.transform = "";
        summary.style.willChange = "";
    };

    const scheduleSummaryDragReset = () => {
        clearSummaryDragResetTimeout();
        summaryDragResetTimeout = window.setTimeout(() => {
            resetSummaryDrag();
        }, 220);
    };

    const applySummaryDrag = (distance) => {
        if (!summary) {
            return;
        }

        clearSummaryDragResetTimeout();
        summary.style.transition = "none";
        summary.style.transform = `translateY(${Math.max(0, distance)}px)`;
    };

    const setSummaryOpen = (open) => {
        resetSummaryDrag();
        isSummaryOpen = open;

        if (summaryDock) {
            summaryDock.classList.toggle("is-open", open);
        }

        if (summaryToggle) {
            summaryToggle.setAttribute("aria-expanded", String(open));
        }

        if (summaryTabCta) {
            summaryTabCta.textContent = open ? "Fermer le panier" : "Ouvrir le panier";
        }

        if (summaryClose) {
            summaryClose.hidden = !open;
        }

        if (summary) {
            summary.hidden = false;
        }

        if (summaryOverlay) {
            summaryOverlay.hidden = false;
        }

        document.body.classList.toggle("shop-summary-open", open && isSummarySheet());
    };

    const clearSummaryHideTimeout = () => {
        if (hideSummaryTimeout) {
            window.clearTimeout(hideSummaryTimeout);
            hideSummaryTimeout = null;
        }
    };

    const scheduleSummaryHide = () => {
        if (!isDesktopToast() || isSummaryPinned) {
            return;
        }

        clearSummaryHideTimeout();
        hideSummaryTimeout = window.setTimeout(() => {
            setSummaryOpen(false);
        }, 1800);
    };

    const clearSummaryPromptTimeout = () => {
        if (summaryPromptTimeout) {
            window.clearTimeout(summaryPromptTimeout);
            summaryPromptTimeout = null;
        }
    };

    const revealSummaryToast = () => {
        if (!summaryDock || !summaryToggle || summaryToggle.hidden) {
            return;
        }

        if (isSummaryOpen) {
            if (isDesktopToast() && !isSummaryPinned) {
                scheduleSummaryHide();
            }
            return;
        }

        summaryDock.classList.add("is-prompt");
        clearSummaryPromptTimeout();
        summaryPromptTimeout = window.setTimeout(() => {
            summaryDock.classList.remove("is-prompt");
            summaryPromptTimeout = null;
        }, 1600);
    };

    const syncSummaryDockBounds = () => {
        if (!summaryDock) {
            return;
        }

        if (!isDesktopToast() || !(summaryBoundsHost instanceof HTMLElement)) {
            summaryDock.style.removeProperty("left");
            summaryDock.style.removeProperty("right");
            return;
        }

        const bounds = summaryBoundsHost.getBoundingClientRect();
        summaryDock.style.left = `${Math.max(12, Math.round(bounds.left))}px`;
        summaryDock.style.right = `${Math.max(12, Math.round(window.innerWidth - bounds.right))}px`;
    };

    const stockBadgesByItem = new Map();
    stockBadges.forEach((badge) => {
        const itemId = Number.parseInt(badge.getAttribute("data-item-id") || "0", 10);
        if (!itemId) {
            return;
        }

        const badges = stockBadgesByItem.get(itemId) || [];
        badges.push(badge);
        stockBadgesByItem.set(itemId, badges);
    });

    const cards = new Map();
    cardNodes.forEach((cardNode) => {
        if (!(cardNode instanceof HTMLElement)) {
            return;
        }

        const itemId = Number.parseInt(cardNode.getAttribute("data-item-id") || "0", 10);
        if (!itemId) {
            return;
        }

        const toggleButton = cardNode.querySelector("[data-shop-options-toggle]");
        const drawer = cardNode.querySelector("[data-shop-options-drawer]");
        cards.set(itemId, {
            itemId,
            cardNode,
            toggleButton: toggleButton instanceof HTMLButtonElement ? toggleButton : null,
            drawer: drawer instanceof HTMLElement ? drawer : null,
            isOpen: drawer instanceof HTMLElement && !drawer.hidden,
        });
    });

    const items = new Map();
    lineNodes.forEach((node) => {
        const lineKey = String(node.getAttribute("data-line-key") || "").trim();
        const itemId = Number.parseInt(node.getAttribute("data-item-id") || "0", 10);
        if (!lineKey || !itemId) {
            return;
        }

        const input = form.querySelector(`[data-shop-qty][data-line-key="${lineKey}"]`);
        const addButton = form.querySelector(`[data-shop-add][data-line-key="${lineKey}"]`);
        const controls = form.querySelector(`[data-shop-controls][data-line-key="${lineKey}"]`);
        const purchaseHint = node.querySelector(".shopPurchaseOption__hint");
        const stockQuantity = Number.parseInt(node.getAttribute("data-item-stock") || "0", 10);
        const stockUnit = normalizeStockUnit(node.getAttribute("data-item-stock-unit") || "unit");
        const lowStockThreshold = Number.parseInt(
            node.getAttribute("data-item-low-stock-threshold") || "0",
            10,
        );
        const priceCents = Number.parseInt(node.getAttribute("data-item-price-cents") || "0", 10);
        const optionUnits = Math.max(
            1,
            Number.parseInt(node.getAttribute("data-option-units") || "1", 10) || 1,
        );
        const optionStockQuantity = (() => {
            const rawValue = String(node.getAttribute("data-option-stock") || "").trim();
            if (rawValue === "") {
                return null;
            }

            return Math.max(0, Number.parseInt(rawValue, 10) || 0);
        })();

        if (addButton instanceof HTMLButtonElement && !addButton.dataset.baseLabel) {
            addButton.dataset.baseLabel = (addButton.textContent || "Ajouter").trim();
        }

        if (purchaseHint instanceof HTMLElement && !purchaseHint.dataset.defaultText) {
            purchaseHint.dataset.defaultText = purchaseHint.textContent || "";
        }

        items.set(lineKey, {
            lineKey,
            id: itemId,
            name: node.getAttribute("data-item-name") || "Produit",
            basePrice: node.getAttribute("data-item-price") || "",
            basePriceCents: priceCents,
            optionId: Number.parseInt(node.getAttribute("data-option-id") || "0", 10) || 0,
            optionLabel: node.getAttribute("data-option-label") || "",
            optionStockQuantity,
            optionUnits,
            cartLabelSingular: node.getAttribute("data-cart-label-singular") || "article",
            cartLabelPlural: node.getAttribute("data-cart-label-plural") || "articles",
            node,
            input,
            stockBadges: stockBadgesByItem.get(itemId) || [],
            addButton,
            controls,
            purchaseHint,
            stockQuantity,
            stockUnit,
            lowStockThreshold,
        });
    });

    const getUnitMultiplier = (item) =>
        Math.max(1, Number.parseInt(item.optionUnits || 1, 10) || 1);

    const getCardEntry = (itemOrId) => {
        const itemId = typeof itemOrId === "number" ? itemOrId : itemOrId ?.id;
        return itemId ? cards.get(itemId) || null : null;
    };

    const getCardItems = (itemId) => {
        return Array.from(items.values()).filter((item) => item.id === itemId);
    };

    const updateCardToggleLabel = (cardEntry, quantity = 0) => {
        if (!cardEntry ?.toggleButton) {
            return;
        }

        const { toggleButton, isOpen } = cardEntry;
        const closedLabel = toggleButton.dataset.closedLabel || "Ajouter au panier";
        const openLabel = toggleButton.dataset.openLabel || "Fermer les options";
        const filledLabel = toggleButton.dataset.filledLabel || closedLabel;
        toggleButton.textContent = isOpen ? openLabel : quantity > 0 ? filledLabel : closedLabel;
        toggleButton.classList.toggle("is-active", isOpen || quantity > 0);
    };

    const setOptionsDrawerOpen = (cardEntry, open) => {
        if (!cardEntry ?.drawer || !cardEntry.toggleButton) {
            return;
        }

        if (open) {
            cards.forEach((otherCardEntry) => {
                if (otherCardEntry.itemId === cardEntry.itemId || !otherCardEntry.drawer) {
                    return;
                }

                if (otherCardEntry.isOpen) {
                    otherCardEntry.isOpen = false;
                    otherCardEntry.drawer.hidden = true;
                    otherCardEntry.cardNode.classList.remove("is-expanded");
                    otherCardEntry.toggleButton ?.setAttribute("aria-expanded", "false");

                    const otherQuantity = getCardItems(otherCardEntry.itemId).reduce(
                        (total, item) => total + getQuantity(item),
                        0,
                    );
                    updateCardToggleLabel(otherCardEntry, otherQuantity);
                }
            });
        }

        cardEntry.isOpen = open;
        cardEntry.drawer.hidden = !open;
        cardEntry.cardNode.classList.toggle("is-expanded", open);
        cardEntry.toggleButton.setAttribute("aria-expanded", String(open));

        const quantity = getCardItems(cardEntry.itemId).reduce(
            (total, item) => total + getQuantity(item),
            0,
        );
        updateCardToggleLabel(cardEntry, quantity);

        if (open && isCompactDrawerViewport()) {
            window.requestAnimationFrame(() => {
                cardEntry.cardNode.scrollIntoView({
                    behavior: "smooth",
                    block: "nearest",
                    inline: "nearest",
                });
            });
        }
    };

    const syncCardState = (itemId) => {
        const cardEntry = getCardEntry(itemId);
        if (!cardEntry) {
            return;
        }

        const cardItems = getCardItems(itemId);
        if (cardItems.length === 0) {
            return;
        }

        let totalQuantity = 0;
        let hasAvailableLine = false;

        cardItems.forEach((item) => {
            totalQuantity += getQuantity(item);
            if (getAvailableOrderQuantity(item) > 0 || getQuantity(item) > 0) {
                hasAvailableLine = true;
            }
        });

        cardEntry.cardNode.classList.toggle("is-in-cart", totalQuantity > 0);
        cardEntry.cardNode.classList.toggle("is-sold-out", !hasAvailableLine);

        if (cardEntry.toggleButton) {
            cardEntry.toggleButton.disabled = !hasAvailableLine;
        }

        updateCardToggleLabel(cardEntry, totalQuantity);
    };

    const getEffectivePriceCents = (item) => {
        return item.basePriceCents;
    };

    const getEffectivePriceLabel = (item) => {
        return item.basePrice;
    };

    const getRawQuantity = (item) => {
        if (!item || !item.input) {
            return 0;
        }

        return Math.max(0, Number.parseInt(item.input.value, 10) || 0);
    };

    const getQuantity = (item) => {
        if (!item || !item.input) {
            return 0;
        }

        return clampQuantity(item.input.value, getAvailableOrderQuantity(item));
    };

    const getReservedUnitsForItem = (itemId, excludedLineKey = null) => {
        let reservedUnits = 0;

        items.forEach((item) => {
            if (item.id !== itemId || item.lineKey === excludedLineKey) {
                return;
            }

            reservedUnits += getRawQuantity(item) * getUnitMultiplier(item);
        });

        return reservedUnits;
    };

    const getRemainingStockUnits = (itemId) => {
        const itemLine = Array.from(items.values()).find((item) => item.id === itemId);
        if (!itemLine) {
            return 0;
        }

        return Math.max(0, itemLine.stockQuantity - getReservedUnitsForItem(itemId));
    };

    const getReservedSelectionsForOption = (optionId, excludedLineKey = null) => {
        if (!optionId) {
            return 0;
        }

        let reservedSelections = 0;
        items.forEach((item) => {
            if (!item || item.optionId !== optionId) {
                return;
            }

            if (excludedLineKey && item.lineKey === excludedLineKey) {
                return;
            }

            reservedSelections += getRawQuantity(item);
        });

        return reservedSelections;
    };

    const getAvailableOrderQuantity = (item) => {
        if (!item || !item.input) {
            return 0;
        }

        const availableUnits = Math.max(
            0,
            item.stockQuantity - getReservedUnitsForItem(item.id, item.lineKey),
        );
        let availableQuantity = Math.max(0, Math.floor(availableUnits / getUnitMultiplier(item)));

        if (item.optionStockQuantity !== null) {
            const reservedSelections = getReservedSelectionsForOption(item.optionId, item.lineKey);
            const remainingOptionSelections = Math.max(0, item.optionStockQuantity - reservedSelections);
            availableQuantity = Math.min(availableQuantity, remainingOptionSelections);
        }

        return availableQuantity;
    };

    const syncStockBadges = (itemId) => {
        const badges = stockBadgesByItem.get(itemId) || [];
        const itemLine = Array.from(items.values()).find((item) => item.id === itemId);
        if (!itemLine) {
            return;
        }

        const remainingUnits = getRemainingStockUnits(itemId);
        const soldOut = remainingUnits <= 0;
        const lowStock = !soldOut &&
            remainingUnits <= Math.max(0, Number.parseInt(itemLine.lowStockThreshold || 0, 10));

        badges.forEach((badge) => {
            let label = formatStockQuantity(remainingUnits, itemLine.stockUnit);
            let tone = "";

            if (soldOut) {
                label = "Rupture";
                tone = " is-sold-out";
            } else if (lowStock) {
                label = `Plus que ${formatStockQuantity(remainingUnits, itemLine.stockUnit)}`;
                tone = " is-low";
            }

            badge.textContent = label;
            badge.className = `shopStockBadge${tone}`;
        });
    };

    const syncItemPresentation = (item) => {
        if (!item) {
            return;
        }

        const allowed = getAvailableOrderQuantity(item);
        const quantity = getQuantity(item);
        const soldOut = allowed <= 0 && quantity <= 0;

        if (item.input) {
            item.input.max = String(allowed);
            item.input.value = String(clampQuantity(item.input.value, allowed));
            item.input.disabled = soldOut;
        }

        item.node.classList.toggle("is-sold-out", soldOut);
    };

    const syncItemState = (item) => {
        if (!item || !item.input) {
            return;
        }

        syncItemPresentation(item);

        const quantity = getQuantity(item);
        const soldOut = item.input.disabled;

        if (item.addButton) {
            item.addButton.disabled = soldOut;

            const baseLabel = item.addButton.dataset.baseLabel || "Ajouter";
            if (isDesktopQuickAdd()) {
                item.addButton.hidden = false;
                item.addButton.textContent = baseLabel;
                item.addButton.classList.toggle("is-active", quantity > 0);
            } else {
                item.addButton.hidden = quantity > 0;
                item.addButton.textContent = baseLabel;
                item.addButton.classList.remove("is-active");
            }
        }

        if (item.controls) {
            item.controls.hidden = quantity <= 0;
        }

        if (item.purchaseHint instanceof HTMLElement) {
            const defaultText = item.purchaseHint.dataset.defaultText || "";
            const quantityLabel =
                quantity > 1 ?
                item.cartLabelPlural || "articles" :
                item.cartLabelSingular || "article";
            item.purchaseHint.textContent =
                quantity > 0 ? `${quantity} ${quantityLabel} dans le panier` : defaultText;
        }

        item.node.classList.toggle("is-in-cart", quantity > 0);
        syncStockBadges(item.id);
        syncCardState(item.id);
    };

    const setQuantity = (item, quantity) => {
        if (!item || !item.input) {
            return;
        }

        const previousQuantity = getQuantity(item);
        const nextQuantity = clampQuantity(quantity, Number.parseInt(item.input.max || "0", 10));
        item.input.value = String(nextQuantity);
        syncItemState(item);
        renderSummary();

        if (nextQuantity > previousQuantity) {
            revealSummaryToast();
        }
    };

    const setFeedback = (message, type = "info") => {
        if (!message) {
            if (feedback) {
                feedback.hidden = true;
                feedback.textContent = "";
                feedback.className = "shopFeedback";
            }
            return;
        }

        if (feedback) {
            feedback.hidden = true;
            feedback.textContent = message;
            feedback.className = `shopFeedback is-${type}`;
        }
        showToast(message, { type });
    };

    const syncFulfillmentState = () => {
        const selectedMethod = getSelectedFulfillmentMethod();
        const deliverySelected = selectedMethod === "delivery";
        const pickupSelected = selectedMethod === "pickup";

        appointmentFields.forEach((field) => {
            if (field instanceof HTMLElement) {
                field.hidden = selectedMethod === "";
            }
        });

        if (deliveryPanel instanceof HTMLElement) {
            deliveryPanel.hidden = !deliverySelected;
        }

        if (pickupLocationPanel instanceof HTMLElement) {
            pickupLocationPanel.hidden = !pickupSelected;
        }

        deliveryFields.forEach((field) => {
            if (!(field instanceof HTMLInputElement)) {
                return;
            }

            field.disabled = !deliverySelected;
            field.required = deliverySelected;
        });

        syncPickupScheduleState();
    };

    const renderSummary = () => {
        let totalCount = 0;
        let subtotalCents = 0;
        let totalItems = 0;

        if (summaryLines) {
            clearElementChildren(summaryLines);
        }

        items.forEach((item) => {
            if (!item.input) {
                return;
            }

            const quantity = clampQuantity(
                item.input.value,
                Number.parseInt(item.input.max || "0", 10),
            );
            item.input.value = String(quantity);
            syncItemState(item);
            if (quantity <= 0) {
                return;
            }

            totalItems += 1;
            totalCount += quantity;
            subtotalCents += quantity * getEffectivePriceCents(item);

            if (summaryLines) {
                const line = document.createElement("div");
                line.className = "shopSummary__line";

                const left = document.createElement("div");
                left.className = "shopSummary__lineMain";

                const label = document.createElement("span");
                label.textContent = item.optionLabel ?
                    `${item.name} — ${item.optionLabel}` :
                    item.name;

                const meta = document.createElement("small");
                meta.textContent = `${quantity} × ${getEffectivePriceLabel(item)}`;

                left.append(label, meta);

                const right = document.createElement("div");
                right.className = "shopSummary__lineAside";

                const amount = document.createElement("strong");
                amount.textContent = formatPrice(quantity * getEffectivePriceCents(item));

                const controls = document.createElement("div");
                controls.className = "shopSummary__lineControls";

                const decrease = document.createElement("button");
                decrease.type = "button";
                decrease.className = "shopSummary__step";
                decrease.textContent = "−";
                decrease.disabled = quantity <= 1;
                decrease.setAttribute("aria-label", `Retirer une unité de ${item.name}`);
                decrease.addEventListener("click", () => setQuantity(item, quantity - 1));

                const count = document.createElement("span");
                count.className = "shopSummary__stepCount";
                count.textContent = String(quantity);

                const increase = document.createElement("button");
                increase.type = "button";
                increase.className = "shopSummary__step";
                increase.textContent = "+";
                increase.disabled = quantity >= getAvailableOrderQuantity(item);
                increase.setAttribute("aria-label", `Ajouter une unité à ${item.name}`);
                increase.addEventListener("click", () => setQuantity(item, quantity + 1));

                controls.append(decrease, count, increase);

                const remove = document.createElement("button");
                remove.type = "button";
                remove.className = "shopSummary__remove";
                remove.innerHTML = `
                    <svg viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <path d="M6 2.5h4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.2" />
                        <path d="M3.5 4h9" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.2" />
                        <path d="M5 4.8v6.2c0 .7.5 1.2 1.2 1.2h3.6c.7 0 1.2-.5 1.2-1.2V4.8" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1.2" />
                        <path d="M6.8 6.2v4.2" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.2" />
                        <path d="M9.2 6.2v4.2" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.2" />
                    </svg>
                `;
                remove.setAttribute("aria-label", `Retirer ${item.name} du panier`);
                remove.title = "Retirer";
                remove.addEventListener("click", () => setQuantity(item, 0));

                right.append(amount, controls, remove);
                line.append(left, right);
                summaryLines.appendChild(line);
            }
        });

        if (summaryCount) {
            summaryCount.textContent = pluralize(totalCount, "article", "articles");
        }

        if (summaryTabCount) {
            summaryTabCount.textContent = String(totalCount);
        }

        if (summaryItems) {
            summaryItems.textContent = pluralize(totalItems, "produit", "produits");
        }

        if (summaryCountMobile) {
            summaryCountMobile.textContent = pluralize(totalCount, "article", "articles");
        }

        if (summaryState) {
            summaryState.textContent = totalCount === 0 ? "Panier vide" : "Panier prêt";
        }

        const promoEvaluation = getPromoEvaluation(subtotalCents);
        const totalCents = promoEvaluation.totalCents;

        if (summarySubtotal) {
            summarySubtotal.hidden = promoEvaluation.discountCents <= 0;
            summarySubtotal.textContent =
                promoEvaluation.discountCents > 0 ? `Sous-total ${formatPrice(subtotalCents)}` : "";
        }

        if (summaryDiscount) {
            summaryDiscount.hidden = promoEvaluation.discountCents <= 0;
            summaryDiscount.textContent =
                promoEvaluation.discountCents > 0 ?
                `Remise -${formatPrice(promoEvaluation.discountCents)}` :
                "";
        }

        if (summaryTotal) {
            summaryTotal.textContent = formatPrice(totalCents);
        }

        if (summaryTabTotal) {
            summaryTabTotal.textContent = formatPrice(totalCents);
        }

        syncPromoState(promoEvaluation);

        if (summaryDock) {
            summaryDock.classList.add("is-available");
            summaryDock.classList.toggle("is-empty", totalCount === 0);
        }

        if (summary) {
            summary.hidden = isDesktopToast() ?
                totalCount === 0 && !isSummaryOpen :
                totalCount === 0;
        }

        if (summaryOverlay) {
            summaryOverlay.hidden = totalCount === 0;
        }

        if (summaryToggle) {
            summaryToggle.hidden = false;
        }

        if (totalCount === 0) {
            clearSummaryHideTimeout();
            clearSummaryPromptTimeout();
            isSummaryPinned = false;
            if (summaryDock) {
                summaryDock.classList.remove("is-prompt");
            }
            setSummaryOpen(false);
        }

        if (checkout) {
            checkout.classList.toggle("is-disabled", totalCount === 0);
        }

        if (submitButton) {
            submitButton.disabled = totalCount === 0;
        }

        if (goCheckoutButton) {
            goCheckoutButton.disabled = totalCount === 0;
            goCheckoutButton.textContent =
                totalCount === 0 ?
                "Continuer vers les informations" :
                `Continuer avec ${totalItems} ${totalItems === 1 ? "produit" : "produits"}`;
        }

        if (summaryLines && totalCount === 0) {
            const empty = document.createElement("p");
            empty.className = "shopSummary__empty";
            empty.textContent = "Ajoutez des quantités pour préparer votre commande.";
            summaryLines.appendChild(empty);
        }
    };

    const applyStockSnapshot = (snapshot) => {
        if (!Array.isArray(snapshot)) {
            return;
        }

        snapshot.forEach((entry) => {
            const stockQuantity = Math.max(0, Number.parseInt(entry.stock_quantity || 0, 10));

            items.forEach((item) => {
                if (item.id !== (Number.parseInt(entry.id, 10) || 0) || !item.input) {
                    return;
                }

                item.stockQuantity = stockQuantity;
                item.stockUnit = normalizeStockUnit(entry.stock_unit || item.stockUnit || "unit");
                item.lowStockThreshold = Math.max(
                    0,
                    Number.parseInt(entry.low_stock_threshold || item.lowStockThreshold || 0, 10),
                );
                const allowed = getAvailableOrderQuantity(item);
                item.input.max = String(allowed);
                item.input.value = String(clampQuantity(item.input.value, allowed));
                item.input.disabled =
                    (allowed <= 0 && getQuantity(item) <= 0) || entry.is_active === false;
                syncItemState(item);
            });
        });

        renderSummary();
    };

    const refreshStock = async({ silent = false } = {}) => {
        try {
            const response = await fetch(stockEndpoint, {
                headers: {
                    Accept: "application/json",
                },
            });

            const payload = await parseResponse(response);
            if (!response.ok) {
                if (!silent) {
                    setFeedback(
                        payload.error || "Impossible de synchroniser le stock pour le moment.",
                        "error",
                    );
                }
                return;
            }

            applyStockSnapshot(payload.items || []);
        } catch {
            if (!silent) {
                setFeedback("Impossible de synchroniser le stock pour le moment.", "error");
            }
        }
    };

    inputs.forEach((input) => {
        input.addEventListener("input", () => {
            const item = items.get(String(input.getAttribute("data-line-key") || "").trim());
            setQuantity(item, input.value);
        });
    });

    fulfillmentInputs.forEach((input) => {
        input.addEventListener("change", () => {
            syncFulfillmentState();
        });
    });

    addButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const item = items.get(String(button.getAttribute("data-line-key") || "").trim());
            setQuantity(item, getQuantity(item) + 1);
        });
    });

    cards.forEach((cardEntry) => {
        if (!cardEntry.toggleButton) {
            return;
        }

        cardEntry.toggleButton.addEventListener("click", () => {
            setOptionsDrawerOpen(cardEntry, !cardEntry.isOpen);
        });
    });

    increaseButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const item = items.get(String(button.getAttribute("data-line-key") || "").trim());
            setQuantity(item, getQuantity(item) + 1);
        });
    });

    decreaseButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const item = items.get(String(button.getAttribute("data-line-key") || "").trim());
            setQuantity(item, getQuantity(item) - 1);
        });
    });

    removeButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const item = items.get(String(button.getAttribute("data-line-key") || "").trim());
            setQuantity(item, 0);
        });
    });

    if (promoInput instanceof HTMLInputElement) {
        promoInput.addEventListener("input", () => {
            promoInput.value = normalizePromoCode(promoInput.value);
            renderSummary();
        });
    }

    if (promoApplyButton) {
        promoApplyButton.addEventListener("click", () => {
            renderSummary();
        });
    }

    if (goCheckoutButton) {
        goCheckoutButton.addEventListener("click", () => {
            if (goCheckoutButton.disabled || !checkoutAnchor) {
                return;
            }

            setSummaryOpen(false);
            clearSummaryHideTimeout();

            checkoutAnchor.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });

            const firstField = checkoutAnchor.querySelector("input, textarea");
            if (firstField instanceof HTMLElement) {
                window.setTimeout(() => firstField.focus({ preventScroll: true }), 250);
            }
        });
    }

    if (summaryToggle) {
        summaryToggle.addEventListener("click", () => {
            if (summaryToggle.hidden) {
                return;
            }

            if (isDesktopToast() && isSummaryOpen && !isSummaryPinned) {
                isSummaryPinned = true;
                clearSummaryHideTimeout();
                setSummaryOpen(true);
                return;
            }

            const nextOpenState = !isSummaryOpen;
            isSummaryPinned = nextOpenState;
            setSummaryOpen(nextOpenState);
            clearSummaryHideTimeout();
            clearSummaryPromptTimeout();
            if (summaryDock) {
                summaryDock.classList.remove("is-prompt");
            }
        });
    }

    if (summaryClose) {
        summaryClose.addEventListener("click", () => {
            isSummaryPinned = false;
            setSummaryOpen(false);
            clearSummaryHideTimeout();
        });
    }

    if (summaryOverlay) {
        summaryOverlay.addEventListener("click", () => {
            isSummaryPinned = false;
            setSummaryOpen(false);
            clearSummaryHideTimeout();
        });
    }

    [summaryToggle, summary].forEach((node) => {
        if (!node) {
            return;
        }

        node.addEventListener("mouseenter", () => {
            if (!isDesktopToast() || !isSummaryOpen) {
                return;
            }

            clearSummaryHideTimeout();
        });

        node.addEventListener("mouseleave", () => {
            if (!isDesktopToast() || !isSummaryOpen || isSummaryPinned) {
                return;
            }

            scheduleSummaryHide();
        });
    });

    if (summary) {
        summary.addEventListener(
            "wheel",
            (event) => {
                if (!isDesktopToast() || !isSummaryOpen) {
                    return;
                }

                const nextScrollTop = summary.scrollTop + event.deltaY;
                const maxScrollTop = Math.max(0, summary.scrollHeight - summary.clientHeight);

                if (maxScrollTop <= 0) {
                    event.preventDefault();
                    return;
                }

                summary.scrollTop = Math.max(0, Math.min(maxScrollTop, nextScrollTop));
                event.preventDefault();
            }, { passive: false },
        );
    }

    if (summaryHandle && summary) {
        summaryHandle.addEventListener(
            "touchstart",
            (event) => {
                if (!isSummarySheet() || isDesktopToast() || !isSummaryOpen) {
                    return;
                }

                touchStartY = event.touches[0].clientY;
                touchCurrentY = touchStartY;
                isDraggingSummary = true;
                clearSummaryHideTimeout();
                clearSummaryDragResetTimeout();
                summary.style.willChange = "transform";
            }, { passive: true },
        );

        summaryHandle.addEventListener(
            "touchmove",
            (event) => {
                if (!isDraggingSummary || !isSummarySheet() || isDesktopToast() || !isSummaryOpen) {
                    return;
                }

                touchCurrentY = event.touches[0].clientY;
                const deltaY = touchCurrentY - touchStartY;

                if (deltaY <= 0) {
                    applySummaryDrag(0);
                    return;
                }

                applySummaryDrag(deltaY);
            }, { passive: true },
        );

        summaryHandle.addEventListener(
            "touchend",
            () => {
                if (!isDraggingSummary || !isSummarySheet() || isDesktopToast()) {
                    return;
                }

                const deltaY = touchCurrentY - touchStartY;
                isDraggingSummary = false;
                summary.style.willChange = "";

                if (deltaY > 90) {
                    setSummaryOpen(false);
                    clearSummaryHideTimeout();
                    return;
                }

                summary.style.transition = "transform 220ms ease";
                summary.style.transform = "translateY(0)";
                scheduleSummaryDragReset();
            }, { passive: true },
        );

        summaryHandle.addEventListener(
            "touchcancel",
            () => {
                if (!isDraggingSummary || !isSummarySheet()) {
                    return;
                }

                isDraggingSummary = false;
                summary.style.willChange = "";
                summary.style.transition = "transform 220ms ease";
                summary.style.transform = "translateY(0)";
                scheduleSummaryDragReset();
            }, { passive: true },
        );
    }

    unbindDesktopToastMedia = bindMediaQueryChange(desktopToastMedia, () => {
        clearSummaryHideTimeout();
        clearSummaryPromptTimeout();
        isSummaryPinned = false;
        if (summaryDock) {
            summaryDock.classList.remove("is-prompt");
        }
        setSummaryOpen(false);
        syncSummaryDockBounds();
        renderSummary();
    });

    unbindDesktopQuickAddMedia = bindMediaQueryChange(desktopQuickAddMedia, () => {
        renderSummary();
    });

    window.addEventListener("beforeunload", () => {
        unbindDesktopToastMedia();
        unbindDesktopQuickAddMedia();
    });

    window.addEventListener("resize", syncSummaryDockBounds);

    if (pickupDateInput instanceof HTMLInputElement) {
        pickupDateInput.addEventListener("change", syncPickupScheduleState);
        pickupDateInput.addEventListener("input", syncPickupScheduleState);
    }

    if (pickupSlotInput instanceof HTMLInputElement) {
        pickupSlotInput.addEventListener("change", validatePickupSlotValue);
        pickupSlotInput.addEventListener("input", () => {
            pickupSlotInput.setCustomValidity("");
        });
    }

    form.addEventListener("submit", async(event) => {
        event.preventDefault();
        setFeedback("");

        const totalSelected = Array.from(items.values()).reduce(
            (sum, item) => sum + getQuantity(item),
            0,
        );
        if (totalSelected === 0) {
            setFeedback("Ajoutez au moins un produit à votre panier avant de valider.", "error");
            return;
        }

        const promoEvaluation = getPromoEvaluation(
            Array.from(items.values()).reduce(
                (sum, item) => sum + getQuantity(item) * getEffectivePriceCents(item),
                0,
            ),
        );

        if (promoEvaluation.code !== "" && !promoEvaluation.valid) {
            setFeedback(promoEvaluation.message, "error");
            return;
        }

        syncPickupScheduleState();
        const pickupSlotIsValid = validatePickupSlotValue();
        if (typeof form.reportValidity === "function" && !form.reportValidity()) {
            return;
        }
        if (!pickupSlotIsValid && !wantsDelivery()) {
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const response = await fetch(submitEndpoint, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                },
                body: new FormData(form),
            });

            const payload = await parseResponse(response);

            if (!response.ok) {
                const conflictNames = Array.isArray(payload.conflicts) ?
                    payload.conflicts
                    .map((conflict) => {
                        const name = conflict && conflict.name ? conflict.name : "Produit";
                        const available =
                            Number.parseInt(conflict && conflict.available, 10) || 0;
                        return `${name} (${available} dispo)`;
                    })
                    .join(", ") :
                    "";

                setFeedback(
                    payload.error ||
                    (conflictNames ?
                        `Stock mis à jour: ${conflictNames}` :
                        "Commande impossible pour le moment."),
                    "error",
                );

                if (Array.isArray(payload.stock)) {
                    applyStockSnapshot(payload.stock);
                }
                return;
            }

            form.reset();
            syncFulfillmentState();
            isSummaryPinned = false;
            setSummaryOpen(false);
            if (Array.isArray(payload.stock)) {
                applyStockSnapshot(payload.stock);
            } else {
                renderSummary();
            }
            if (promoInput instanceof HTMLInputElement) {
                promoInput.value = "";
            }
            const successParts = [payload.message || "Votre commande a bien été enregistrée."];
            if (payload.reference) {
                successParts.push(`Référence ${payload.reference}.`);
            } else if (payload.id) {
                successParts.push(`Référence #${payload.id}.`);
            }
            if (payload.client_ack_sent === true) {
                successParts.push("Un email de confirmation vient de vous être envoyé.");
            } else if (payload.email_notifications === false) {
                successParts.push(
                    "La confirmation affichée ici fait foi même sans email automatique immédiat.",
                );
            }
            setFeedback(successParts.join(" "), "success");
        } catch {
            setFeedback("Une erreur réseau empêche l’envoi de la commande.", "error");
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
            renderSummary();
        }
    });

    renderSummary();
    syncFulfillmentState();
    cards.forEach((cardEntry) => {
        updateCardToggleLabel(cardEntry, 0);
        if (cardEntry.drawer) {
            setOptionsDrawerOpen(cardEntry, cardEntry.isOpen);
        }
        syncCardState(cardEntry.itemId);
    });
    syncSummaryDockBounds();
    refreshStock({ silent: true });
    window.setInterval(() => refreshStock({ silent: true }), 15000);
}