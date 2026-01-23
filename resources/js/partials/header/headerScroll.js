export function initHeaderScroll() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const header = document.querySelector('.header');
    if (!header) return;
  
    const onScroll = () => {
      const scrolled = window.scrollY > 8;
      header.classList.toggle('is-scrolled', scrolled);
    };
  
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }
  