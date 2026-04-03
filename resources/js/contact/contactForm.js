function minDateIso(days) {
    const date = new Date();
    date.setDate(date.getDate() + days);
    return date.toISOString().slice(0, 10);
}

function formatLeadDate(dateIso) {
    return new Date(dateIso + "T00:00:00").toLocaleDateString("fr-FR", {
        weekday: "long",
        day: "numeric",
        month: "long",
    });
}

function clampQuantity(value) {
    return Math.min(99, Math.max(0, parseInt(value, 10) || 0));
}

function intParam(params, key) {
    return clampQuantity(params.get(key));
}

function escapeKey(value) {
    return value.replaceAll("\\", "\\\\").replaceAll('"', '\\"');
}

function findItemToggle(form, itemSlug) {
    return form.querySelector(
        `[data-order-item-toggle][data-item-slug="${escapeKey(itemSlug)}"]`,
    );
}

function findItemGrid(form, itemSlug) {
    return form.querySelector(`[data-order-options-for="${escapeKey(itemSlug)}"]`);
}

function findOptionRow(form, itemSlug, optionKey) {
    const rows = getItemRows(form, itemSlug);
    return (
        rows.find((row) => {
            if (optionKey === "__item__") {
                return row.dataset.simpleItem === "1";
            }

            return (row.dataset.optionKey || "") === optionKey;
        }) || null
    );
}

function getItemRows(form, itemSlug) {
    const grid = findItemGrid(form, itemSlug);
    return grid ? Array.from(grid.querySelectorAll(".menuVariantQty")) : [];
}

function getRowQuantity(row) {
    const input = row.querySelector("[data-order-option-qty]");
    return input ? clampQuantity(input.value) : 0;
}

function setRowQuantity(row, quantity) {
    const input = row.querySelector("[data-order-option-qty]");
    if (input) {
        input.value = clampQuantity(quantity);
    }
}

function getItemTotalQuantity(form, itemSlug) {
    return getItemRows(form, itemSlug).reduce((sum, row) => sum + getRowQuantity(row), 0);
}

function clearItemQuantities(form, itemSlug) {
    getItemRows(form, itemSlug).forEach((row) => setRowQuantity(row, 0));
}

function applyDefaultItemQuantity(form, itemSlug) {
    const rows = getItemRows(form, itemSlug);
    if (!rows.length || getItemTotalQuantity(form, itemSlug) > 0) {
        return;
    }

    setRowQuantity(rows[0], 1);
}

function syncItemVisibility(form, toggle) {
    const itemSlug = toggle.dataset.itemSlug || "";
    const grid = findItemGrid(form, itemSlug);
    if (!grid) {
        return;
    }

    grid.style.display = toggle.checked ? "grid" : "none";
}

function buildLeadWarningText(thresholdIso, leadDays) {
    const leadLabel = leadDays >= 2 ? `${leadDays * 24}h` : `${leadDays} jour`;
    return (
        `⚠️ Cette sélection nécessite un délai minimum de ${leadLabel}. ` +
        `Veuillez choisir une date à partir du ${formatLeadDate(thresholdIso)}.`
    );
}

function renderCategoryIcon(iconKey) {
    const iconMap = {
        spark: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.5 9.7 6.3 14.5 8l-4.8 1.7L8 14.5 6.3 9.7 1.5 8l4.8-1.7Z" fill="currentColor"/></svg>',
        tray: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2 4.5h12v7H2z" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M4.5 2.8h7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M5 8h6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        sunrise: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4 9a4 4 0 1 1 8 0" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2 11.5h12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M8 2.2v2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        leaf: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M13.6 2.6c-4.1.2-7 1.7-8.6 4.4-1.2 2-.9 4.3.8 6 .2.2.6.2.8 0l6.7-6.7" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.8 13.4c-1.3-.3-2.4-1-3.2-2.1" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        basket: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 6h10l-1 6.5H4z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M5.2 6 8 2.8 10.8 6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        cuts: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 4.2a1.7 1.7 0 1 0 0 3.4 1.7 1.7 0 0 0 0-3.4Zm0 4.2a1.7 1.7 0 1 0 0 3.4 1.7 1.7 0 0 0 0-3.4Z" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="m4.4 5.6 8.1 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="m4.4 10.4 8.1-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
        flame: '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8.2 2.2c.5 2-.7 2.8-.7 4.1 0 1 .7 1.7 1.7 1.7 1.1 0 1.9-.8 2-2 .9 1 1.4 2.2 1.4 3.5 0 2.3-1.8 4-4.4 4S3.8 11.8 3.8 9.5c0-2 1-3.7 2.8-5.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        default: '<svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="3" fill="currentColor"/></svg>',
    };

    return iconMap[iconKey] || iconMap.default;
}

function syncHiddenSelections(form, entries) {
    form.querySelectorAll(".js-order-hidden").forEach((input) => input.remove());

    entries.forEach((entry) => {
        const itemInput = document.createElement("input");
        itemInput.type = "hidden";
        itemInput.name = "menu_items[]";
        itemInput.value = entry.value;
        itemInput.className = "js-order-hidden";
        form.appendChild(itemInput);

        if (entry.quantity > 1) {
            const quantityInput = document.createElement("input");
            quantityInput.type = "hidden";
            quantityInput.name = `menu_quantity[${entry.value}]`;
            quantityInput.value = String(entry.quantity);
            quantityInput.className = "js-order-hidden";
            form.appendChild(quantityInput);
        }
    });
}

function buildSelectionEntries(form) {
    const entries = [];
    const categories = Array.from(form.querySelectorAll("[data-menu-category]"));

    categories.forEach((category) => {
        const categoryName = category.dataset.categoryName || "Menu";
        const toggles = Array.from(category.querySelectorAll("[data-order-item-toggle]"));

        toggles.forEach((toggle) => {
            if (!toggle.checked) {
                return;
            }

            const itemName = toggle.dataset.itemName || "Sélection";
            const itemSlug = toggle.dataset.itemSlug || "";
            const itemPrice = toggle.dataset.itemPrice || "";
            const itemImage = toggle.dataset.itemImage || "";
            const itemVisual = toggle.dataset.itemVisual || "?";
            const categoryIcon = toggle.dataset.categoryIcon || "default";
            const categoryTone = toggle.dataset.categoryTone || "default";
            const requiresLeadTime = toggle.dataset.requiresLeadTime === "1";
            const rows = getItemRows(form, itemSlug).filter((row) => getRowQuantity(row) > 0);

            if (rows.length) {
                rows.forEach((row) => {
                    const quantity = getRowQuantity(row);
                    const optionLabel = row.dataset.optionLabel || itemName;
                    const optionPrice = row.dataset.optionPrice || itemPrice || "Sur devis";
                    const isSimpleItem = row.dataset.simpleItem === "1";
                    const itemLabel = isSimpleItem ? itemName : `${itemName} — ${optionLabel}`;
                    const optionKey = isSimpleItem ? "__item__" : row.dataset.optionKey || "";

                    entries.push({
                        category: categoryName,
                        itemSlug,
                        optionKey,
                        itemImage,
                        itemVisual,
                        categoryIcon,
                        categoryTone,
                        isSimpleItem,
                        isAdjustable: true,
                        line: optionPrice ? `${itemLabel} — ${optionPrice}` : itemLabel,
                        quantity,
                        requiresLeadTime,
                        value: `${categoryName}|${itemLabel}|${optionPrice}`,
                    });
                });
                return;
            }

            const fallbackPrice = itemPrice || "Sur devis";
            entries.push({
                category: categoryName,
                itemSlug,
                optionKey: "",
                itemImage,
                itemVisual,
                categoryIcon,
                categoryTone,
                isSimpleItem: true,
                isAdjustable: false,
                line: itemPrice ? `${itemName} — ${itemPrice}` : itemName,
                quantity: 1,
                requiresLeadTime,
                value: `${categoryName}|${itemName}|${fallbackPrice}`,
            });
        });
    });

    return entries;
}

function initRequestForm(form) {
    const container = form.closest(".contactCard") || form.parentElement;
    const successMessage = container ? container.querySelector("[data-form-success]") : null;
    const errorMessage = container ? container.querySelector("[data-form-error]") : null;
    const errorText = container ? container.querySelector("[data-form-error-text]") : null;
    const submitButton = form.querySelector('button[type="submit"]');
    const messageInput = form.querySelector('textarea[name="message"]');
    const dateInput = form.querySelector('input[name="date"]');
    const accordion = form.querySelector(".menuSelection__accordion");
    const summaryRoot = form.querySelector("[data-selection-summary]");
    const summaryEmpty = form.querySelector("[data-selection-summary-empty]");
    const summaryGroups = form.querySelector("[data-selection-summary-groups]");
    const summaryMeta = form.querySelector("[data-selection-summary-meta]");
    const summaryStats = form.querySelector("[data-selection-summary-stats]");
    const resetSelectionButton = form.querySelector("[data-reset-selection]");
    const selectedCategory = (form.dataset.selectedCategory || "").trim();
    const minLeadDays = Math.max(0, parseInt(form.dataset.minLeadDays, 10) || 0);
    const params = new URLSearchParams(window.location.search);
    let lastAutoMessage = "";
    let allowAutoMessage = !messageInput || !messageInput.value.trim();
    let hasPrefill = false;

    if (messageInput) {
        messageInput.addEventListener("input", () => {
            allowAutoMessage = !messageInput.value.trim() || messageInput.value === lastAutoMessage;
        });
    }

    if (dateInput && minLeadDays > 0) {
        dateInput.min = minDateIso(minLeadDays);
    }

    const updateLeadTimeWarning = () => {
        const warning = form.querySelector("[data-delivery-warning]");
        if (!warning || !dateInput || minLeadDays <= 0) {
            return;
        }

        const hasLeadSelection = buildSelectionEntries(form).some((entry) => entry.requiresLeadTime);
        if (!hasLeadSelection) {
            warning.style.display = "none";
            return;
        }

        const thresholdIso = minDateIso(minLeadDays);
        if (!dateInput.value || dateInput.value < thresholdIso) {
            warning.textContent = buildLeadWarningText(thresholdIso, minLeadDays);
            warning.style.display = "block";
            return;
        }

        warning.style.display = "none";
    };

    const syncAutoMessage = () => {
        const entries = buildSelectionEntries(form);
        syncHiddenSelections(form, entries);
        updateLeadTimeWarning();
        updateSummary(entries);

        if (!messageInput) {
            return;
        }

        if (!allowAutoMessage && messageInput.value.trim() && messageInput.value !== lastAutoMessage) {
            return;
        }

        if (!entries.length) {
            messageInput.value = "";
            lastAutoMessage = "";
            allowAutoMessage = true;
            return;
        }

        const lines = entries.map((entry) =>
            entry.quantity > 1 ? `${entry.quantity}× ${entry.line}` : entry.line,
        );

        if (entries.some((entry) => entry.requiresLeadTime)) {
            lines.push("Livraison souhaitée sous 72h minimum, selon votre zone.");
        }

        const generatedMessage = `Bonjour, je souhaite commander :\n${lines.join("\n")}`;
        messageInput.value = generatedMessage;
        lastAutoMessage = generatedMessage;
        allowAutoMessage = true;
    };

    const updateSummary = (entries) => {
        if (!summaryRoot || !summaryEmpty || !summaryGroups || !summaryMeta || !summaryStats) {
            return;
        }

        if (!entries.length) {
            summaryEmpty.style.display = "block";
            summaryGroups.style.display = "none";
            summaryGroups.innerHTML = "";
            summaryStats.style.display = "none";
            summaryStats.innerHTML = "";
            summaryMeta.style.display = "none";
            summaryMeta.textContent = "";
            return;
        }

        summaryEmpty.style.display = "none";
        summaryGroups.style.display = "grid";

        const grouped = new Map();
        entries.forEach((entry) => {
            if (!grouped.has(entry.category)) {
                grouped.set(entry.category, []);
            }
            grouped.get(entry.category).push(entry);
        });

        summaryGroups.innerHTML = Array.from(grouped.entries())
            .map(([category, categoryEntries]) => {
                const items = categoryEntries
                    .map((entry) => {
                        const imageMarkup = entry.itemImage ?
                            `<span class="selectionSummary__thumb selectionSummary__thumb--${entry.categoryTone}"><img src="${entry.itemImage}" alt="" loading="lazy" decoding="async"><span class="selectionSummary__thumbBadge">${renderCategoryIcon(entry.categoryIcon)}</span></span>` :
                            `<span class="selectionSummary__thumb selectionSummary__thumb--${entry.categoryTone} selectionSummary__thumb--fallback">${entry.itemVisual}<span class="selectionSummary__thumbBadge">${renderCategoryIcon(entry.categoryIcon)}</span></span>`;
                        const controlsMarkup = entry.isAdjustable ?
                            `<div class="selectionSummary__controls">
                                    <button type="button" class="selectionSummary__controlBtn" data-summary-action="minus" data-item-slug="${entry.itemSlug}" data-option-key="${entry.optionKey}" aria-label="Diminuer ${entry.line}">−</button>
                                    <span class="selectionSummary__controlQty">${entry.quantity}</span>
                                    <button type="button" class="selectionSummary__controlBtn" data-summary-action="plus" data-item-slug="${entry.itemSlug}" data-option-key="${entry.optionKey}" aria-label="Augmenter ${entry.line}">+</button>
                                </div>` :
                            `<button type="button" class="selectionSummary__removeBtn" data-summary-action="remove" data-item-slug="${entry.itemSlug}" aria-label="Retirer ${entry.line}">Retirer</button>`;

                        return `
                            <li class="selectionSummary__item">
                                ${imageMarkup}
                                <div class="selectionSummary__itemBody">
                                    <span class="selectionSummary__line">${entry.line}</span>
                                    ${controlsMarkup}
                                </div>
                            </li>`;
                    })
                    .join("");

                return `
                    <section class="selectionSummary__group">
                        <h4 class="selectionSummary__groupTitle">${category}</h4>
                        <ul class="selectionSummary__list">${items}
                        </ul>
                    </section>`;
            })
            .join("");

        const totalLines = entries.length;
        const totalUnits = entries.reduce((sum, entry) => sum + entry.quantity, 0);
        const totalCategories = grouped.size;
        const leadTimeNeeded = entries.some((entry) => entry.requiresLeadTime);
        summaryStats.innerHTML = `
            <span class="selectionSummary__stat">${totalCategories} catégorie${totalCategories > 1 ? "s" : ""}</span>
            <span class="selectionSummary__stat">${totalLines} ligne${totalLines > 1 ? "s" : ""}</span>
            <span class="selectionSummary__stat">${totalUnits} unité${totalUnits > 1 ? "s" : ""}</span>`;
        summaryStats.style.display = "flex";
        summaryMeta.textContent = `${totalLines} sélection${totalLines > 1 ? "s" : ""} • ${totalUnits} unité${totalUnits > 1 ? "s" : ""}${leadTimeNeeded ? " • délai 72h minimum à prévoir pour les plateaux" : ""}`;
        summaryMeta.style.display = "block";
    };

    const resetSelectionState = () => {
        form.querySelectorAll("[data-order-item-toggle]").forEach((toggle) => {
            toggle.checked = false;
            syncItemVisibility(form, toggle);
        });

        form.querySelectorAll("[data-order-option-qty]").forEach((input) => {
            input.value = 0;
        });

        form.querySelectorAll(".js-order-hidden").forEach((input) => input.remove());

        if (accordion) {
            accordion.open = selectedCategory !== "";
        }

        lastAutoMessage = "";
        allowAutoMessage = true;
        if (messageInput) {
            messageInput.value = "";
        }
        updateLeadTimeWarning();
        updateSummary([]);
    };

    const applySummaryAction = (action, itemSlug, optionKey) => {
        const toggle = findItemToggle(form, itemSlug);
        if (!toggle) {
            return;
        }

        if (action === "remove") {
            toggle.checked = false;
            clearItemQuantities(form, itemSlug);
            syncItemVisibility(form, toggle);
            syncAutoMessage();
            return;
        }

        const row = findOptionRow(form, itemSlug, optionKey);
        if (!row) {
            return;
        }

        const input = row.querySelector("[data-order-option-qty]");
        if (!input) {
            return;
        }

        const delta = action === "plus" ? 1 : -1;
        input.value = clampQuantity((parseInt(input.value, 10) || 0) + delta);
        toggle.checked = getItemTotalQuantity(form, itemSlug) > 0;
        syncItemVisibility(form, toggle);
        syncAutoMessage();
    };

    const bindToggle = (toggle) => {
        toggle.addEventListener("change", () => {
            const itemSlug = toggle.dataset.itemSlug || "";
            const hasQuantityGrid =
                toggle.dataset.hasOptions === "1" || toggle.dataset.hasDirectQuantity === "1";

            if (hasQuantityGrid) {
                if (toggle.checked) {
                    applyDefaultItemQuantity(form, itemSlug);
                } else {
                    clearItemQuantities(form, itemSlug);
                }
                syncItemVisibility(form, toggle);
            }

            syncAutoMessage();
        });

        syncItemVisibility(form, toggle);
    };

    Array.from(form.querySelectorAll("[data-order-item-toggle]")).forEach(bindToggle);

    Array.from(form.querySelectorAll(".menuVariantQty")).forEach((row) => {
        const grid = row.closest("[data-order-options-for]");
        if (!grid) {
            return;
        }

        const itemSlug = grid.getAttribute("data-order-options-for") || "";
        const toggle = findItemToggle(form, itemSlug);
        const input = row.querySelector("[data-order-option-qty]");
        const minusButton = row.querySelector('[data-action="minus"]');
        const plusButton = row.querySelector('[data-action="plus"]');

        const applyDelta = (delta) => {
            if (!input || !toggle) {
                return;
            }

            input.value = clampQuantity((parseInt(input.value, 10) || 0) + delta);
            toggle.checked = getItemTotalQuantity(form, itemSlug) > 0;
            syncItemVisibility(form, toggle);
            syncAutoMessage();
        };

        if (minusButton) {
            minusButton.addEventListener("click", () => applyDelta(-1));
        }

        if (plusButton) {
            plusButton.addEventListener("click", () => applyDelta(1));
        }

        if (input) {
            input.addEventListener("input", () => {
                input.value = clampQuantity(input.value);
                if (toggle) {
                    toggle.checked = getItemTotalQuantity(form, itemSlug) > 0;
                    syncItemVisibility(form, toggle);
                }
                syncAutoMessage();
            });
        }
    });

    Array.from(form.querySelectorAll(".menuVariantQty[data-prefill-param]")).forEach((row) => {
        const paramName = (row.dataset.prefillParam || "").trim();
        if (!paramName) {
            return;
        }

        const quantity = intParam(params, paramName);
        if (quantity <= 0) {
            return;
        }

        const grid = row.closest("[data-order-options-for]");
        if (!grid) {
            return;
        }

        const itemSlug = grid.getAttribute("data-order-options-for") || "";
        const toggle = findItemToggle(form, itemSlug);
        if (!toggle) {
            return;
        }

        setRowQuantity(row, quantity);
        toggle.checked = true;
        syncItemVisibility(form, toggle);
        hasPrefill = true;
    });

    if (accordion) {
        accordion.open = hasPrefill || selectedCategory !== "";
    }

    if (dateInput) {
        dateInput.addEventListener("change", updateLeadTimeWarning);
    }

    if (resetSelectionButton) {
        resetSelectionButton.addEventListener("click", () => {
            resetSelectionState();
            syncAutoMessage();
        });
    }

    if (summaryGroups) {
        summaryGroups.addEventListener("click", (event) => {
            const target = event.target.closest("[data-summary-action]");
            if (!target) {
                return;
            }

            applySummaryAction(
                target.dataset.summaryAction || "",
                target.dataset.itemSlug || "",
                target.dataset.optionKey || "",
            );
        });
    }

    syncAutoMessage();

    form.addEventListener("submit", async(event) => {
        event.preventDefault();

        const entries = buildSelectionEntries(form);
        syncHiddenSelections(form, entries);

        if (entries.some((entry) => entry.requiresLeadTime) && dateInput && minLeadDays > 0) {
            const thresholdIso = minDateIso(minLeadDays);
            if (!dateInput.value || dateInput.value < thresholdIso) {
                updateLeadTimeWarning();
                const warning = form.querySelector("[data-delivery-warning]");
                if (warning) {
                    warning.scrollIntoView({ behavior: "smooth", block: "center" });
                }
                return;
            }
        }

        if (successMessage) {
            successMessage.style.display = "none";
        }
        if (errorMessage) {
            errorMessage.style.display = "none";
        }

        const originalText = submitButton ? submitButton.textContent : "";
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Envoi en cours...";
        }

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
            });

            const data = await response.json();
            if (response.ok && data.success) {
                if (successMessage) {
                    successMessage.style.display = "block";
                    successMessage.scrollIntoView({ behavior: "smooth", block: "center" });
                }

                form.reset();
                resetSelectionState();

                window.setTimeout(() => {
                    if (successMessage) {
                        successMessage.style.display = "none";
                    }
                }, 8000);
            } else if (errorMessage && errorText) {
                errorText.textContent = data.error || "Une erreur est survenue. Veuillez réessayer.";
                errorMessage.style.display = "block";
                errorMessage.scrollIntoView({ behavior: "smooth", block: "center" });
            }
        } catch (error) {
            if (errorMessage && errorText) {
                errorText.textContent =
                    "Erreur de connexion. Veuillez vérifier votre connexion internet.";
                errorMessage.style.display = "block";
                errorMessage.scrollIntoView({ behavior: "smooth", block: "center" });
            }
            console.error("Request form submission error:", error);
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        }
    });
}

export function initContactForm() {
    Array.from(document.querySelectorAll("[data-request-form]")).forEach((form) => {
        initRequestForm(form);
    });
}