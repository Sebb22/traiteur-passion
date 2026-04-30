export function initAdminCatalog() {
    initCatalogWorkspaceTools();

    const sectionList = document.querySelector("[data-section-sortable]");
    const sectionOrderInput = document.getElementById("catalogSectionOrderInput");
    const sectionOrderForm = document.getElementById("catalogSectionOrderForm");

    if (sectionList && sectionOrderInput && sectionOrderForm) {
        const sections = Array.from(sectionList.querySelectorAll("[data-section-id]"));

        sections.forEach((section) => {
            section.addEventListener("toggle", () => {
                if (!section.open) {
                    return;
                }

                sections.forEach((otherSection) => {
                    if (otherSection !== section) {
                        otherSection.removeAttribute("open");
                    }
                });
            });
        });

        initReorderableList({
            list: sectionList,
            nodeSelector: "[data-section-id]",
            idAttribute: "data-section-id",
            input: sectionOrderInput,
            form: sectionOrderForm,
            scope: "sections",
        });
    }

    document.querySelectorAll("[data-item-sortable]").forEach((itemList) => {
        const sectionId = itemList.getAttribute("data-section-id");
        const input = document.getElementById(`catalogItemOrderInput-${sectionId}`);
        const form = document.getElementById(`catalogItemOrderForm-${sectionId}`);

        if (!input || !form || !sectionId) {
            return;
        }

        initReorderableList({
            list: itemList,
            nodeSelector: "[data-item-id]",
            idAttribute: "data-item-id",
            input,
            form,
            scope: `items-${sectionId}`,
        });
    });

    document.querySelectorAll("[data-option-sortable]").forEach((optionList) => {
        const itemId = optionList.getAttribute("data-item-id");
        const input = document.getElementById(`catalogOptionOrderInput-${itemId}`);
        const form = document.getElementById(`catalogOptionOrderForm-${itemId}`);

        if (!input || !form || !itemId) {
            return;
        }

        initReorderableList({
            list: optionList,
            nodeSelector: "[data-option-id]",
            idAttribute: "data-option-id",
            input,
            form,
            scope: `options-${itemId}`,
        });
    });

    initCatalogImageUploadUX();
}

function initReorderableList({ list, nodeSelector, idAttribute, input, form, scope }) {
    if (!(list instanceof HTMLElement) || !(input instanceof HTMLInputElement) || !(form instanceof HTMLFormElement)) {
        return;
    }

    const toggleButton = document.querySelector(`[data-reorder-toggle="${scope}"]`);
    const saveButton = document.querySelector(`[data-reorder-save="${scope}"]`);
    const cancelButton = document.querySelector(`[data-reorder-cancel="${scope}"]`);
    const manualMode =
        toggleButton instanceof HTMLButtonElement &&
        saveButton instanceof HTMLButtonElement &&
        cancelButton instanceof HTMLButtonElement;
    const allowDrag = list.getAttribute("data-reorder-method") !== "buttons";

    let active = !manualMode;
    let draggedNode = null;
    let armedNode = null;
    let originalOrder = [];

    const getNodes = () => Array.from(list.querySelectorAll(nodeSelector));
    const getNodeId = (node) => String(node.getAttribute(idAttribute) || "").trim();
    const getOrder = () => getNodes().map((node) => getNodeId(node)).filter(Boolean);
    const isDirty = () => getOrder().join(",") !== originalOrder.join(",");

    const syncOrder = () => {
        input.value = getOrder().join(",");
    };

    const applyOrder = (orderedIds) => {
        const nodeMap = new Map(getNodes().map((node) => [getNodeId(node), node]));
        orderedIds.forEach((id) => {
            const node = nodeMap.get(id);
            if (node) {
                list.appendChild(node);
            }
        });
        syncOrder();
    };

    const renderState = () => {
        list.classList.toggle("is-reorder-active", manualMode && active);
        list.classList.toggle("is-reorder-dirty", manualMode && isDirty());

        getNodes().forEach((node) => {
            node.draggable = active && allowDrag;
            node.classList.toggle("is-reorder-mode", manualMode && active);
        });

        if (manualMode) {
            toggleButton.textContent = active ? "Quitter le mode tri" : "Réordonner";
            saveButton.hidden = !active;
            cancelButton.hidden = !active;
            saveButton.disabled = !isDirty();
            cancelButton.disabled = !isDirty();
        }
    };

    const resetArmedDrag = () => {
        armedNode = null;
    };

    const saveOrder = () => {
        syncOrder();
        form.submit();
    };

    syncOrder();
    originalOrder = getOrder();
    renderState();

    if (manualMode) {
        toggleButton.addEventListener("click", () => {
            if (active) {
                if (isDirty()) {
                    const shouldDiscard = window.confirm("Annuler le nouvel ordre non enregistré ?");
                    if (!shouldDiscard) {
                        return;
                    }
                    applyOrder(originalOrder);
                }

                active = false;
                draggedNode = null;
                resetArmedDrag();
                renderState();
                return;
            }

            originalOrder = getOrder();
            active = true;
            renderState();
        });

        saveButton.addEventListener("click", () => {
            if (!isDirty()) {
                return;
            }

            saveOrder();
        });

        cancelButton.addEventListener("click", () => {
            applyOrder(originalOrder);
            renderState();
        });
    }

    getNodes().forEach((node) => {
        const handles = Array.from(node.querySelectorAll("[data-drag-handle]"));
        const moveButtons = Array.from(node.querySelectorAll("[data-reorder-move]"));
        const summary = node.querySelector("summary");

        if (summary instanceof HTMLElement) {
            summary.addEventListener("click", (event) => {
                if (!active) {
                    return;
                }

                event.preventDefault();
            });
        }

        handles.forEach((handle) => {
            const armDrag = (event) => {
                if (!active || !allowDrag) {
                    return;
                }

                armedNode = node;

                if (event.type === "click") {
                    event.preventDefault();
                    event.stopPropagation();
                }
            };

            handle.addEventListener("pointerdown", armDrag);
            handle.addEventListener("mousedown", armDrag);
            handle.addEventListener("click", armDrag);
        });

        moveButtons.forEach((button) => {
            button.addEventListener("click", (event) => {
                if (!active) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                const direction = button.getAttribute("data-reorder-move");
                const nodes = getNodes();
                const index = nodes.indexOf(node);

                if (direction === "up" && index > 0) {
                    list.insertBefore(node, nodes[index - 1]);
                }

                if (direction === "down" && index >= 0 && index < nodes.length - 1) {
                    list.insertBefore(nodes[index + 1], node);
                }

                syncOrder();
                renderState();
            });
        });

        node.addEventListener("dragstart", (event) => {
            if (!allowDrag || !active || (handles.length > 0 && armedNode !== node)) {
                event.preventDefault();
                return;
            }

            draggedNode = node;
            node.classList.add("is-dragging");

            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = "move";
                event.dataTransfer.setData("text/plain", getNodeId(node));
            }
        });

        node.addEventListener("dragend", () => {
            node.classList.remove("is-dragging");
            draggedNode = null;
            resetArmedDrag();
            syncOrder();
            renderState();
        });
    });

    list.addEventListener("dragover", (event) => {
        if (!allowDrag || !active || !draggedNode) {
            return;
        }

        event.preventDefault();

        const nextNode = getDragAfterElement(list, nodeSelector, draggedNode, event.clientY);
        if (!nextNode) {
            list.appendChild(draggedNode);
        } else {
            list.insertBefore(draggedNode, nextNode);
        }

        syncOrder();
        renderState();
    });

    list.addEventListener("drop", (event) => {
        if (!allowDrag || !active || !draggedNode) {
            return;
        }

        event.preventDefault();
        syncOrder();

        if (!manualMode) {
            saveOrder();
            return;
        }

        renderState();
    });
}

function getDragAfterElement(list, nodeSelector, draggedNode, clientY) {
    const sortableNodes = Array.from(list.querySelectorAll(nodeSelector)).filter((node) => node !== draggedNode);

    let closest = {
        offset: Number.NEGATIVE_INFINITY,
        node: null,
    };

    sortableNodes.forEach((node) => {
        const rect = node.getBoundingClientRect();
        const offset = clientY - rect.top - rect.height / 2;

        if (offset < 0 && offset > closest.offset) {
            closest = { offset, node };
        }
    });

    return closest.node;
}

function initCatalogWorkspaceTools() {
    const searchInput = document.querySelector("[data-catalog-search]");
    const jumpSelect = document.querySelector("[data-catalog-jump]");
    const expandAllButton = document.querySelector("[data-catalog-expand-all]");
    const collapseAllButton = document.querySelector("[data-catalog-collapse-all]");
    const sections = Array.from(document.querySelectorAll("[data-catalog-section]"));

    if (sections.length === 0) {
        return;
    }

    const filterCatalog = () => {
        const query = String(searchInput ? searchInput.value : "")
            .trim()
            .toLowerCase();

        sections.forEach((section) => {
            const sectionText = String(section.getAttribute("data-catalog-search-text") || "");
            const items = Array.from(section.querySelectorAll("[data-catalog-item]"));

            let sectionMatches = query === "" || sectionText.includes(query);
            let visibleItemsCount = 0;

            items.forEach((item) => {
                const itemText = String(item.getAttribute("data-catalog-search-text") || "");
                const itemMatches = query === "" || sectionMatches || itemText.includes(query);

                item.classList.toggle("is-filtered-out", !itemMatches);
                if (itemMatches) {
                    visibleItemsCount += 1;
                }
            });

            if (!sectionMatches && visibleItemsCount > 0) {
                sectionMatches = true;
            }

            section.classList.toggle("is-filtered-out", !sectionMatches);

            if (query !== "" && sectionMatches) {
                section.setAttribute("open", "open");
            }
        });
    };

    if (searchInput) {
        searchInput.addEventListener("input", filterCatalog);
    }

    if (jumpSelect) {
        jumpSelect.addEventListener("change", () => {
            const targetId = String(jumpSelect.value || "").trim();
            if (targetId === "") {
                return;
            }

            const target = document.getElementById(targetId);
            if (!target) {
                return;
            }

            if (target instanceof HTMLDetailsElement) {
                target.setAttribute("open", "open");
            }

            target.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    }

    if (expandAllButton) {
        expandAllButton.addEventListener("click", () => {
            sections.forEach((section) => {
                section.setAttribute("open", "open");
            });
        });
    }

    if (collapseAllButton) {
        collapseAllButton.addEventListener("click", () => {
            sections.forEach((section) => {
                section.removeAttribute("open");
            });
        });
    }
}

function initCatalogImageUploadUX() {
    const catalogRoot = document.querySelector("[data-rembg-preview-model]");
    const configuredPreviewModel = String(catalogRoot ? catalogRoot.getAttribute("data-rembg-preview-model") || "" : "")
        .trim() || "u2netp";
    const previewReusableOnSave = catalogRoot ? catalogRoot.getAttribute("data-rembg-preview-reusable") === "1" : false;
    const previewEndpoint = String(catalogRoot ? catalogRoot.getAttribute("data-image-preview-endpoint") || "" : "")
        .trim() || "/admin/catalog/image-preview";
    const uploadForms = Array.from(document.querySelectorAll('form')).filter((form) =>
        form.querySelector('input[name="image_file"]'),
    );

    uploadForms.forEach((form) => {
        const fileInput = form.querySelector('input[name="image_file"]');
        const removeBgInput = form.querySelector('input[name="remove_bg"]');
        const fuzzInput = form.querySelector('input[name="background_fuzz"]');
        const imageSaveButtons = Array.from(form.querySelectorAll('[data-image-save-button]'));

        if (!fileInput) {
            return;
        }

        const preview = createUploadPreviewBlock();
        const uploadLabel = fileInput.closest("label");

        if (uploadLabel && uploadLabel.parentNode) {
            uploadLabel.parentNode.insertBefore(preview.container, uploadLabel.nextSibling);
        } else {
            form.appendChild(preview.container);
        }

        let sourceImage = null;
        let sourceFile = null;
        let previewAbortController = null;
        let previewRequestToken = 0;
        let previewReuseToken = "";

        let previewTokenInput = form.querySelector('input[name="preview_token"]');
        if (!previewTokenInput) {
            previewTokenInput = document.createElement("input");
            previewTokenInput.type = "hidden";
            previewTokenInput.name = "preview_token";
            form.appendChild(previewTokenInput);
        }

        const syncPreviewToken = () => {
            previewTokenInput.value = previewReuseToken;
        };

        const clearPreviewToken = () => {
            previewReuseToken = "";
            syncPreviewToken();
        };

        preview.triggerButton.disabled = true;

        const syncImageSaveButtons = () => {
            const hasFile = Boolean(fileInput.files && fileInput.files.length > 0);
            imageSaveButtons.forEach((button) => {
                button.disabled = !hasFile;
            });
        };

        const renderPreview = () => {
            if (!sourceImage) {
                preview.container.hidden = true;
                preview.status.textContent = "";
                preview.triggerButton.disabled = true;
                syncImageSaveButtons();
                return;
            }

            preview.container.hidden = false;
            preview.triggerButton.disabled = false;
            syncImageSaveButtons();

            const rawContext = preview.rawCanvas.getContext("2d");
            const cutoutContext = preview.cutoutCanvas.getContext("2d");
            if (!rawContext || !cutoutContext) {
                return;
            }

            const maxPreviewWidth = 460;
            const ratio = sourceImage.naturalWidth > 0 ? sourceImage.naturalHeight / sourceImage.naturalWidth : 1;
            const width = Math.min(maxPreviewWidth, sourceImage.naturalWidth || maxPreviewWidth);
            const height = Math.max(1, Math.round(width * ratio));

            [preview.rawCanvas, preview.cutoutCanvas].forEach((canvas) => {
                canvas.width = width;
                canvas.height = height;
                canvas.style.aspectRatio = `${width} / ${height}`;
            });

            rawContext.clearRect(0, 0, width, height);
            rawContext.drawImage(sourceImage, 0, 0, width, height);

            cutoutContext.clearRect(0, 0, width, height);
            cutoutContext.drawImage(sourceImage, 0, 0, width, height);

            if (!removeBgInput || !removeBgInput.checked) {
                clearPreviewToken();
                preview.status.textContent = "Le fichier sera enregistré sans détourage automatique.";
                return;
            }

            clearPreviewToken();
            if (previewReusableOnSave) {
                preview.status.textContent = `Aperçu non généré. Cliquez sur \"Générer l'aperçu\" (${configuredPreviewModel}) puis l'enregistrement réutilisera ce détourage.`;
                return;
            }

            preview.status.textContent = `Aperçu non généré. Cliquez sur \"Générer l'aperçu\" (${configuredPreviewModel}). L'enregistrement reprendra exactement ce détourage.`;
        };

        const renderCutoutFromDataUri = (dataUri) => {
            if (!sourceImage) {
                return;
            }

            const cutoutContext = preview.cutoutCanvas.getContext("2d");
            if (!cutoutContext) {
                return;
            }

            const processedImage = new Image();
            processedImage.addEventListener("load", () => {
                const width = preview.cutoutCanvas.width;
                const height = preview.cutoutCanvas.height;
                cutoutContext.clearRect(0, 0, width, height);
                cutoutContext.drawImage(processedImage, 0, 0, width, height);
            });
            processedImage.src = dataUri;
        };

        const requestServerPreview = () => {
            if (!sourceImage || !sourceFile) {
                preview.status.textContent = "Ajoutez une image puis lancez l'aperçu.";
                return;
            }

            if (removeBgInput && !removeBgInput.checked) {
                preview.status.textContent = "Activez \"Détourage fond auto\" pour générer un aperçu détouré.";
                return;
            }

            if (previewAbortController) {
                previewAbortController.abort();
            }

            previewRequestToken += 1;
            const currentToken = previewRequestToken;
            previewAbortController = new AbortController();
            preview.container.classList.add("is-previewing");
            preview.status.textContent = "Calcul du détouré en cours...";

            const formData = new FormData();
            formData.append("image_file", sourceFile);
            formData.append("remove_bg", "1");
            formData.append("background_fuzz", fuzzInput ? String(fuzzInput.value || "6") : "6");
            formData.append("preview_width", "320");
            formData.append("preview_model", configuredPreviewModel);

            fetch(previewEndpoint, {
                    method: "POST",
                    body: formData,
                    signal: previewAbortController.signal,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    },
                })
                .then(async(response) => {
                    const payload = await response.json().catch(() => null);

                    if (!response.ok) {
                        const message = payload && typeof payload.error === "string" ?
                            payload.error :
                            "Impossible de générer l'aperçu.";
                        throw new Error(message);
                    }

                    return payload;
                })
                .then((payload) => {
                    if (currentToken !== previewRequestToken) {
                        return;
                    }

                    if (!payload || payload.ok !== true || typeof payload.preview_data_uri !== "string") {
                        throw new Error(
                            payload && typeof payload.error === "string" ?
                            payload.error :
                            "Réponse d'aperçu invalide",
                        );
                    }

                    previewReuseToken = typeof payload.preview_token === "string" ? payload.preview_token : "";
                    syncPreviewToken();
                    renderCutoutFromDataUri(payload.preview_data_uri);
                    if (previewReuseToken) {
                        preview.status.textContent = `Aperçu prêt (${configuredPreviewModel}). L'enregistrement réutilisera ce détourage sans relancer rembg.`;
                        return;
                    }

                    preview.status.textContent = `Aperçu prêt (${configuredPreviewModel}). L'enregistrement reprendra exactement ce détourage.`;
                })
                .catch((error) => {
                    if (error && error.name === "AbortError") {
                        return;
                    }

                    if (currentToken !== previewRequestToken) {
                        return;
                    }

                    preview.status.textContent = error instanceof Error && error.message ?
                        error.message :
                        "Impossible de générer l'aperçu serveur.";
                })
                .finally(() => {
                    if (currentToken !== previewRequestToken) {
                        return;
                    }

                    preview.container.classList.remove("is-previewing");
                });
        };

        preview.triggerButton.addEventListener("click", () => {
            requestServerPreview();
        });

        fileInput.addEventListener("change", () => {
            const file = fileInput.files ?.[0];
            if (!file) {
                sourceImage = null;
                sourceFile = null;
                clearPreviewToken();

                if (previewAbortController) {
                    previewAbortController.abort();
                }

                renderPreview();
                syncImageSaveButtons();
                return;
            }

            sourceFile = file;
            clearPreviewToken();

            const fileReader = new FileReader();
            fileReader.addEventListener("load", () => {
                const previewImage = new Image();
                previewImage.addEventListener("load", () => {
                    sourceImage = previewImage;
                    renderPreview();
                });
                previewImage.src = String(fileReader.result || "");
            });
            fileReader.readAsDataURL(file);
            syncImageSaveButtons();
        });

        syncImageSaveButtons();
        syncPreviewToken();

        if (removeBgInput) {
            removeBgInput.addEventListener("change", () => {
                clearPreviewToken();
                renderPreview();
            });
        }

        if (fuzzInput) {
            fuzzInput.addEventListener("input", () => {
                clearPreviewToken();
                renderPreview();
            });
        }

        form.addEventListener("submit", () => {
            if (!fileInput.files || fileInput.files.length === 0) {
                return;
            }

            form.classList.add("is-uploading");
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach((button) => {
                button.disabled = true;
            });
        });
    });
}

function createUploadPreviewBlock() {
    const container = document.createElement("div");
    container.className = "adminLivePreview";
    container.hidden = true;
    container.innerHTML = `
        <div class="adminLivePreview__head">Aperçu avant enregistrement</div>
        <div class="adminLivePreview__grid">
            <figure class="adminLivePreview__pane">
                <figcaption>Image brute</figcaption>
                <div class="adminLivePreview__checker"><canvas class="adminLivePreview__canvas"></canvas></div>
            </figure>
            <figure class="adminLivePreview__pane">
                <figcaption>Rendu envoyé</figcaption>
                <div class="adminLivePreview__checker"><canvas class="adminLivePreview__canvas"></canvas></div>
            </figure>
        </div>
        <div class="adminLivePreview__hint"></div>
        <div class="adminLivePreview__actions">
            <button type="button" class="adminBtn adminBtn--primary adminLivePreview__trigger">Générer l'aperçu</button>
        </div>
        <div class="adminUploadLoader" aria-live="polite">Traitement de l'image en cours...</div>
    `;

    const canvases = container.querySelectorAll("canvas");
    const status = container.querySelector(".adminLivePreview__hint");
    const triggerButton = container.querySelector(".adminLivePreview__trigger");

    return {
        container,
        rawCanvas: canvases[0],
        cutoutCanvas: canvases[1],
        status,
        triggerButton,
    };
}

function colorDistance(r, g, b, target) {
    const dr = r - target.r;
    const dg = g - target.g;
    const db = b - target.b;
    return Math.sqrt(dr * dr + dg * dg + db * db);
}