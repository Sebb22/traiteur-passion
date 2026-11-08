export function initAdminContacts() {
    const form = document.querySelector("[data-bulk-contact-status]");
    if (!form) return;

    const selectAll = form.querySelector("[data-select-all-contacts]");
    const selectionCount = form.querySelector("[data-contact-selection-count]");
    const statusSelect = form.querySelector("select[name='status']");
    const checkboxes = () => Array.from(document.querySelectorAll("input[name='contact_ids[]']"));

    const updateSelectionCount = () => {
        const selected = checkboxes().filter((checkbox) => checkbox.checked).length;
        if (selectionCount) {
            selectionCount.textContent = `${selected} dossier${selected > 1 ? "s" : ""} sélectionné${selected > 1 ? "s" : ""}`;
        }
        if (selectAll) {
            selectAll.checked = selected > 0 && selected === checkboxes().length;
            selectAll.indeterminate = selected > 0 && selected < checkboxes().length;
        }
    };

    if (selectAll) {
        selectAll.addEventListener("change", () => {
            checkboxes().forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
            updateSelectionCount();
        });
    }

    checkboxes().forEach((checkbox) => checkbox.addEventListener("change", updateSelectionCount));
    form.addEventListener("submit", (event) => {
        const selected = checkboxes().filter((checkbox) => checkbox.checked).length;
        if (selected === 0 || !statusSelect) return;

        if (["completed", "cancelled"].includes(statusSelect.value)) {
            const label = statusSelect.options[statusSelect.selectedIndex].text.toLowerCase();
            if (
                !window.confirm(
                    `Passer ${selected} dossier${selected > 1 ? "s" : ""} à « ${label} » ?`,
                )
            ) {
                event.preventDefault();
            }
        }
    });

    updateSelectionCount();
}
