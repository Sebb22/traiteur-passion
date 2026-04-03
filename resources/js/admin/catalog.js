export function initAdminCatalog() {
    const sectionList = document.querySelector("[data-section-sortable]");
    const sectionOrderInput = document.getElementById("catalogSectionOrderInput");
    const sectionOrderForm = document.getElementById("catalogSectionOrderForm");

    if (sectionList && sectionOrderInput && sectionOrderForm) {
        let draggedSection = null;
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

        const syncSectionOrder = () => {
            const ids = Array.from(sectionList.querySelectorAll("[data-section-id]")).map((node) =>
                node.getAttribute("data-section-id"),
            );
            sectionOrderInput.value = ids.join(",");
        };

        syncSectionOrder();

        sections.forEach((section) => {
            section.addEventListener("dragstart", () => {
                draggedSection = section;
                section.classList.add("is-dragging");
            });

            section.addEventListener("dragend", () => {
                section.classList.remove("is-dragging");
                draggedSection = null;
            });

            section.addEventListener("dragover", (event) => {
                event.preventDefault();
                if (!draggedSection || draggedSection === section) {
                    return;
                }

                const rect = section.getBoundingClientRect();
                const before = event.clientY < rect.top + rect.height / 2;
                if (before) {
                    sectionList.insertBefore(draggedSection, section);
                } else {
                    sectionList.insertBefore(draggedSection, section.nextSibling);
                }
            });

            section.addEventListener("drop", (event) => {
                event.preventDefault();
                syncSectionOrder();
                sectionOrderForm.submit();
            });
        });
    }

    document.querySelectorAll("[data-item-sortable]").forEach((itemList) => {
        const sectionId = itemList.getAttribute("data-section-id");
        const input = document.getElementById(`catalogItemOrderInput-${sectionId}`);
        const form = document.getElementById(`catalogItemOrderForm-${sectionId}`);

        if (!input || !form) {
            return;
        }

        let draggedItem = null;

        const syncItemOrder = () => {
            const ids = Array.from(itemList.querySelectorAll("[data-item-id]")).map((node) =>
                node.getAttribute("data-item-id"),
            );
            input.value = ids.join(",");
        };

        syncItemOrder();

        itemList.querySelectorAll("[data-item-id]").forEach((item) => {
            item.addEventListener("dragstart", () => {
                draggedItem = item;
                item.classList.add("is-dragging");
            });

            item.addEventListener("dragend", () => {
                item.classList.remove("is-dragging");
                draggedItem = null;
            });

            item.addEventListener("dragover", (event) => {
                event.preventDefault();
                if (!draggedItem || draggedItem === item) {
                    return;
                }

                const rect = item.getBoundingClientRect();
                const before = event.clientY < rect.top + rect.height / 2;
                if (before) {
                    itemList.insertBefore(draggedItem, item);
                } else {
                    itemList.insertBefore(draggedItem, item.nextSibling);
                }
            });

            item.addEventListener("drop", (event) => {
                event.preventDefault();
                syncItemOrder();
                form.submit();
            });
        });
    });

    initCatalogImageUploadUX();
}

function initCatalogImageUploadUX() {
    const uploadForms = Array.from(document.querySelectorAll('form')).filter((form) =>
        form.querySelector('input[name="image_file"]'),
    );

    uploadForms.forEach((form) => {
        const fileInput = form.querySelector('input[name="image_file"]');
        const removeBgInput = form.querySelector('input[name="remove_bg"]');
        const fuzzInput = form.querySelector('input[name="background_fuzz"]');

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

        const renderPreview = () => {
            if (!sourceImage) {
                preview.container.hidden = true;
                preview.status.textContent = "";
                return;
            }

            preview.container.hidden = false;

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
                preview.status.textContent = "Le fichier sera enregistré sans détourage automatique.";
                return;
            }

            preview.status.textContent = "Aperçu non généré. Cliquez sur \"Générer l'aperçu\" (mode rapide u2netp).";
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
            formData.append("preview_model", "u2netp");

            fetch("/admin/catalog/image-preview", {
                    method: "POST",
                    body: formData,
                    signal: previewAbortController.signal,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    },
                })
                .then((response) => response.json())
                .then((payload) => {
                    if (currentToken !== previewRequestToken) {
                        return;
                    }

                    if (!payload || payload.ok !== true || typeof payload.preview_data_uri !== "string") {
                        throw new Error("Réponse d'aperçu invalide");
                    }

                    renderCutoutFromDataUri(payload.preview_data_uri);
                    preview.status.textContent = "Aperçu rapide prêt (u2netp basse résolution).";
                })
                .catch((error) => {
                    if (error && error.name === "AbortError") {
                        return;
                    }

                    if (currentToken !== previewRequestToken) {
                        return;
                    }

                    preview.status.textContent = "Impossible de générer l'aperçu serveur.";
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

                if (previewAbortController) {
                    previewAbortController.abort();
                }

                renderPreview();
                return;
            }

            sourceFile = file;

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
        });

        if (removeBgInput) {
            removeBgInput.addEventListener("change", () => {
                renderPreview();
            });
        }

        if (fuzzInput) {
            fuzzInput.addEventListener("input", () => {
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