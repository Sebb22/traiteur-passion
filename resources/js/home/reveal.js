export function initReveal() {
  const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduceMotion) return;

  const els = document.querySelectorAll("[data-reveal]");
  if (!els.length) return;

  // Stagger (uniquement pour ceux qui ont data-stagger)
  let staggerIndex = 0;
  els.forEach((el) => {
    if (el.hasAttribute("data-stagger")) {
      const delay = Math.min(staggerIndex * 80, 320);
      el.style.setProperty("--reveal-delay", `${delay}ms`);
      staggerIndex += 1;
    }
  });

  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (!e.isIntersecting) return;
      e.target.classList.add("is-revealed");
      io.unobserve(e.target);
    });
  }, { threshold: 0.15 });

  els.forEach((el) => io.observe(el));
}
