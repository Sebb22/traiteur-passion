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
    const summaryLines = form.querySelector("[data-shop-summary-lines]");
    const summaryTotal = form.querySelector("[data-shop-summary-total]");
    const feedback = form.querySelector("[data-shop-feedback]");
    const submitButton = form.querySelector("[data-shop-submit]");
    const goCheckoutButton = form.querySelector("[data-shop-go-checkout]");
    const checkout = form.querySelector("[data-shop-checkout]");
    const checkoutAnchor = form.querySelector("#shop-checkout");
    const itemNodes = Array.from(form.querySelectorAll("[data-shop-item]"));
    const inputs = Array.from(form.querySelectorAll("[data-shop-qty]"));
    const addButtons = Array.from(form.querySelectorAll("[data-shop-add]"));
    const increaseButtons = Array.from(form.querySelectorAll("[data-shop-increase]"));
    const decreaseButtons = Array.from(form.querySelectorAll("[data-shop-decrease]"));
    const removeButtons = Array.from(form.querySelectorAll("[data-shop-remove]"));
    let isSummaryOpen = false;
    let hideSummaryTimeout = null;
    let summaryDragResetTimeout = null;
    let touchStartY = 0;
    let touchCurrentY = 0;
    let isDraggingSummary = false;
    const desktopToastMedia = window.matchMedia("(min-width: 981px)");

    const isDesktopToast = () => desktopToastMedia.matches;

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

        if (summaryClose) {
            summaryClose.hidden = !open;
        }

        if (summary) {
            summary.hidden = false;
        }

        if (summaryOverlay) {
            summaryOverlay.hidden = false;
        }

        document.body.classList.toggle("shop-summary-open", open && !isDesktopToast());
    };

    const clearSummaryHideTimeout = () => {
        if (hideSummaryTimeout) {
            window.clearTimeout(hideSummaryTimeout);
            hideSummaryTimeout = null;
        }
    };

    const scheduleSummaryHide = () => {
        clearSummaryHideTimeout();
        hideSummaryTimeout = window.setTimeout(() => {
            setSummaryOpen(false);
        }, 2200);
    };

    const revealSummaryToast = () => {
        if (!summaryDock || !summary || summary.hidden) {
            return;
        }

        if (isDesktopToast()) {
            setSummaryOpen(true);
            scheduleSummaryHide();
        }
    };

    const items = new Map();
    itemNodes.forEach((node) => {
        const itemId = Number.parseInt(node.getAttribute("data-item-id") || "0", 10);
        if (!itemId) {
            return;
        }

        const input = form.querySelector(`[data-shop-qty][data-item-id="${itemId}"]`);
        const stockBadge = form.querySelector(`[data-shop-stock][data-item-id="${itemId}"]`);
        const addButton = form.querySelector(`[data-shop-add][data-item-id="${itemId}"]`);
        const controls = form.querySelector(`[data-shop-controls][data-item-id="${itemId}"]`);
        const stockQuantity = Number.parseInt(node.getAttribute("data-item-stock") || "0", 10);
        const maxOrder = Number.parseInt(node.getAttribute("data-item-max-order") || "1", 10);
        const priceCents = Number.parseInt(node.getAttribute("data-item-price-cents") || "0", 10);

        items.set(itemId, {
            id: itemId,
            name: node.getAttribute("data-item-name") || "Produit",
            price: node.getAttribute("data-item-price") || "",
            priceCents,
            node,
            input,
            stockBadge,
            addButton,
            controls,
            stockQuantity,
            maxOrder,
        });
    });

    const getQuantity = (item) => {
        if (!item || !item.input) {
            return 0;
        }

        return clampQuantity(item.input.value, Number.parseInt(item.input.max || "0", 10));
    };

    const syncItemState = (item) => {
        if (!item || !item.input) {
            return;
        }

        const quantity = getQuantity(item);
        const soldOut = item.input.disabled;

        if (item.addButton) {
            item.addButton.hidden = quantity > 0;
            item.addButton.disabled = soldOut;
        }

        if (item.controls) {
            item.controls.hidden = quantity <= 0;
        }

        item.node.classList.toggle("is-in-cart", quantity > 0);
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
        if (!feedback) {
            return;
        }

        if (!message) {
            feedback.hidden = true;
            feedback.textContent = "";
            feedback.className = "shopFeedback";
            return;
        }

        feedback.hidden = false;
        feedback.textContent = message;
        feedback.className = `shopFeedback is-${type}`;
    };

    const renderSummary = () => {
        let totalCount = 0;
        let totalCents = 0;
        let totalItems = 0;

        if (summaryLines) {
            summaryLines.replaceChildren();
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
            totalCents += quantity * item.priceCents;

            if (summaryLines) {
                const line = document.createElement("div");
                line.className = "shopSummary__line";

                const left = document.createElement("div");
                left.className = "shopSummary__lineMain";

                const label = document.createElement("span");
                label.textContent = item.name;

                const meta = document.createElement("small");
                meta.textContent = `${quantity} × ${item.price}`;

                left.append(label, meta);

                const right = document.createElement("div");
                right.className = "shopSummary__lineAside";

                const amount = document.createElement("strong");
                amount.textContent = formatPrice(quantity * item.priceCents);

                const remove = document.createElement("button");
                remove.type = "button";
                remove.className = "shopSummary__remove";
                remove.textContent = "Retirer";
                remove.addEventListener("click", () => setQuantity(item, 0));

                right.append(amount, remove);
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

        if (summaryTotal) {
            summaryTotal.textContent = formatPrice(totalCents);
        }

        if (summaryTabTotal) {
            summaryTabTotal.textContent = formatPrice(totalCents);
        }

        if (summaryDock) {
            summaryDock.classList.toggle("is-available", totalCount > 0);
        }

        if (summary) {
            summary.hidden = isDesktopToast()
                ? totalCount === 0 && !isSummaryOpen
                : totalCount === 0;
        }

        if (summaryOverlay) {
            summaryOverlay.hidden = totalCount === 0;
        }

        if (summaryToggle) {
            summaryToggle.hidden = isDesktopToast() ? false : totalCount === 0;
        }

        if (!isDesktopToast() && totalCount > 0 && !isSummaryOpen) {
            clearSummaryHideTimeout();
        }

        if (totalCount === 0) {
            clearSummaryHideTimeout();
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
                totalCount === 0
                    ? "Continuer vers les informations"
                    : `Continuer avec ${totalItems} ${totalItems === 1 ? "produit" : "produits"}`;
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
            const item = items.get(Number.parseInt(entry.id, 10));
            if (!item || !item.input) {
                return;
            }

            const stockQuantity = Math.max(0, Number.parseInt(entry.stock_quantity || 0, 10));
            const maxOrder = Math.max(1, Number.parseInt(entry.max_order_quantity || 1, 10));
            const allowed = Math.min(stockQuantity, maxOrder);

            item.stockQuantity = stockQuantity;
            item.maxOrder = maxOrder;
            item.input.max = String(allowed);
            item.input.value = String(clampQuantity(item.input.value, allowed));
            item.input.disabled = allowed <= 0 || entry.is_active === false;
            syncItemState(item);

            item.node.classList.toggle("is-sold-out", allowed <= 0 || entry.is_active === false);
            if (item.stockBadge) {
                let label = `${stockQuantity} disponible(s)`;
                let tone = "";

                if (entry.is_active === false || allowed <= 0) {
                    label = "Rupture";
                    tone = " is-sold-out";
                } else if (stockQuantity <= 5) {
                    label = `Plus que ${stockQuantity} disponible(s)`;
                    tone = " is-low";
                }

                item.stockBadge.textContent = label;
                item.stockBadge.className = `shopStockBadge${tone}`;
            }
        });

        renderSummary();
    };

    const refreshStock = async ({ silent = false } = {}) => {
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
            const item = items.get(Number.parseInt(input.getAttribute("data-item-id") || "0", 10));
            setQuantity(item, input.value);
        });
    });

    addButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const item = items.get(Number.parseInt(button.getAttribute("data-item-id") || "0", 10));
            setQuantity(item, 1);
        });
    });

    increaseButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const item = items.get(Number.parseInt(button.getAttribute("data-item-id") || "0", 10));
            setQuantity(item, getQuantity(item) + 1);
        });
    });

    decreaseButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const item = items.get(Number.parseInt(button.getAttribute("data-item-id") || "0", 10));
            setQuantity(item, getQuantity(item) - 1);
        });
    });

    removeButtons.forEach((button) => {
        button.addEventListener("click", () => {
            const item = items.get(Number.parseInt(button.getAttribute("data-item-id") || "0", 10));
            setQuantity(item, 0);
        });
    });

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

            const nextOpenState = !isSummaryOpen;
            setSummaryOpen(nextOpenState);
            if (isDesktopToast() && nextOpenState) {
                scheduleSummaryHide();
            } else {
                clearSummaryHideTimeout();
            }
        });
    }

    if (summaryClose) {
        summaryClose.addEventListener("click", () => {
            setSummaryOpen(false);
            clearSummaryHideTimeout();
        });
    }

    if (summaryOverlay) {
        summaryOverlay.addEventListener("click", () => {
            setSummaryOpen(false);
            clearSummaryHideTimeout();
        });
    }

    if (summaryHandle && summary) {
        summaryHandle.addEventListener(
            "touchstart",
            (event) => {
                if (isDesktopToast() || !isSummaryOpen) {
                    return;
                }

                touchStartY = event.touches[0].clientY;
                touchCurrentY = touchStartY;
                isDraggingSummary = true;
                clearSummaryHideTimeout();
                clearSummaryDragResetTimeout();
                summary.style.willChange = "transform";
            },
            { passive: true },
        );

        summaryHandle.addEventListener(
            "touchmove",
            (event) => {
                if (!isDraggingSummary || isDesktopToast() || !isSummaryOpen) {
                    return;
                }

                touchCurrentY = event.touches[0].clientY;
                const deltaY = touchCurrentY - touchStartY;

                if (deltaY <= 0) {
                    applySummaryDrag(0);
                    return;
                }

                applySummaryDrag(deltaY);
            },
            { passive: true },
        );

        summaryHandle.addEventListener(
            "touchend",
            () => {
                if (!isDraggingSummary || isDesktopToast()) {
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
            },
            { passive: true },
        );

        summaryHandle.addEventListener(
            "touchcancel",
            () => {
                if (!isDraggingSummary) {
                    return;
                }

                isDraggingSummary = false;
                summary.style.willChange = "";
                summary.style.transition = "transform 220ms ease";
                summary.style.transform = "translateY(0)";
                scheduleSummaryDragReset();
            },
            { passive: true },
        );
    }

    [summaryToggle, summary].forEach((node) => {
        if (!node) {
            return;
        }

        node.addEventListener("mouseenter", () => {
            if (!isDesktopToast()) {
                return;
            }

            if (summaryToggle && !summaryToggle.hidden) {
                setSummaryOpen(true);
            }
            clearSummaryHideTimeout();
        });

        node.addEventListener("mouseleave", () => {
            if (!isDesktopToast()) {
                return;
            }

            if (summaryToggle && !summaryToggle.hidden) {
                scheduleSummaryHide();
            }
        });
    });

    desktopToastMedia.addEventListener("change", () => {
        clearSummaryHideTimeout();
        setSummaryOpen(false);
        renderSummary();
    });

    form.addEventListener("submit", async (event) => {
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
                const conflictNames = Array.isArray(payload.conflicts)
                    ? payload.conflicts
                          .map((conflict) => {
                              const name = conflict && conflict.name ? conflict.name : "Produit";
                              const available =
                                  Number.parseInt(conflict && conflict.available, 10) || 0;
                              return `${name} (${available} dispo)`;
                          })
                          .join(", ")
                    : "";

                setFeedback(
                    payload.error ||
                        (conflictNames
                            ? `Stock mis à jour: ${conflictNames}`
                            : "Commande impossible pour le moment."),
                    "error",
                );

                if (Array.isArray(payload.stock)) {
                    applyStockSnapshot(payload.stock);
                }
                return;
            }

            form.reset();
            if (Array.isArray(payload.stock)) {
                applyStockSnapshot(payload.stock);
            } else {
                renderSummary();
            }
            setFeedback(payload.message || "Votre commande a bien été enregistrée.", "success");
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
    refreshStock({ silent: true });
    window.setInterval(() => refreshStock({ silent: true }), 15000);
}
