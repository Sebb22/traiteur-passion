export function initAdminBlog() {
    const searchInput = document.querySelector("[data-blog-search]");
    const expandAllButton = document.querySelector("[data-blog-expand-all]");
    const collapseAllButton = document.querySelector("[data-blog-collapse-all]");
    const articles = Array.from(document.querySelectorAll("[data-blog-article]"));

    if (articles.length === 0) {
        return;
    }

    const filterArticles = () => {
        const query = String(searchInput ? searchInput.value : "")
            .trim()
            .toLowerCase();

        articles.forEach((article) => {
            const text = String(article.getAttribute("data-blog-search-text") || "");
            const matches = query === "" || text.includes(query);

            article.classList.toggle("is-filtered-out", !matches);

            if (query !== "" && matches) {
                article.setAttribute("open", "open");
            }
        });
    };

    if (searchInput) {
        searchInput.addEventListener("input", filterArticles);
    }

    if (expandAllButton) {
        expandAllButton.addEventListener("click", () => {
            articles.forEach((article) => {
                if (!article.classList.contains("is-filtered-out")) {
                    article.setAttribute("open", "open");
                }
            });
        });
    }

    if (collapseAllButton) {
        collapseAllButton.addEventListener("click", () => {
            articles.forEach((article) => {
                article.removeAttribute("open");
            });
        });
    }

    filterArticles();
}
