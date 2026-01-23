export function initHeaderToggle() {
  const toggle = document.querySelector(".header__toggle");
  const menu = document.getElementById("menuOverlay");
  if (!toggle || !menu) return;

  const closeEls = menu.querySelectorAll("[data-close]");

  const closeMenu = () => {
    toggle.setAttribute("aria-expanded", "false");
    menu.hidden = true;
    document.body.style.overflow = "";
  };

  toggle.addEventListener("click", () => {
    const isOpen = toggle.getAttribute("aria-expanded") === "true";
    toggle.setAttribute("aria-expanded", String(!isOpen));
    menu.hidden = isOpen;
    document.body.style.overflow = isOpen ? "" : "hidden";
  });

  closeEls.forEach((el) => el.addEventListener("click", closeMenu));

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !menu.hidden) closeMenu();
  });
}
