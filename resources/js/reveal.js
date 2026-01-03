export function initReveal() {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduceMotion) return;

  const els = document.querySelectorAll('[data-reveal]');
  if (!els.length) return;

  // Stagger: si l’élément a data-stagger, on calcule un délai
  let staggerIndex = 0;
  els.forEach((el) => {
    if (el.hasAttribute('data-stagger')) {
      const delay = Math.min(staggerIndex * 80, 320); // 0ms, 80ms, 160ms, 240ms, max 320ms
      el.style.setProperty('--reveal-delay', `${delay}ms`);
      staggerIndex += 1;
    }
  });

  const io = new IntersectionObserver(
    (entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-revealed');
          io.unobserve(entry.target);
        }
      }
    },
    { threshold: 0.12, rootMargin: '0px 0px -10% 0px' }
  );

  els.forEach((el) => io.observe(el));
}
