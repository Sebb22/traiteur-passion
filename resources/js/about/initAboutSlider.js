export function initAboutSlider() {
  const slider = document.querySelector(".aboutSlider");
  if (!slider) return;

  const viewport = slider.querySelector(".aboutSlider__viewport");
  const prev = slider.querySelector(".aboutSlider__btn--prev");
  const next = slider.querySelector(".aboutSlider__btn--next");
  const track = slider.querySelector(".aboutSlider__track");

  if (!viewport || !prev || !next || !track) return;

  const step = () => viewport.clientWidth;

  const setBtnState = (btn, enabled) => {
    btn.classList.toggle("is-hidden", !enabled);
    btn.disabled = !enabled;
    btn.setAttribute("aria-disabled", String(!enabled));
    btn.tabIndex = enabled ? 0 : -1;
  };

  const updateArrows = () => {
    const max = Math.max(0, viewport.scrollWidth - viewport.clientWidth);
    const x = viewport.scrollLeft;
    const eps = 2;

    const hasOverflow = max > eps;
    const canPrev = x > eps;
    const canNext = x < (max - eps);

    setBtnState(prev, hasOverflow && canPrev);
    setBtnState(next, hasOverflow && canNext);
  };

  const scrollByPage = (dir) => {
    viewport.scrollBy({ left: dir * step(), behavior: "smooth" });
    requestAnimationFrame(updateArrows);
    setTimeout(updateArrows, 220);
    setTimeout(updateArrows, 480);
  };

  prev.addEventListener("click", () => scrollByPage(-1));
  next.addEventListener("click", () => scrollByPage(1));

  viewport.addEventListener("keydown", (e) => {
    if (e.key === "ArrowLeft") scrollByPage(-1);
    if (e.key === "ArrowRight") scrollByPage(1);
  });

  viewport.addEventListener(
    "scroll",
    () => requestAnimationFrame(updateArrows),
    { passive: true }
  );

  // images load / layout changes
  const ro = new ResizeObserver(updateArrows);
  ro.observe(viewport);
  ro.observe(track);

  // au cas où les images chargent après
  window.addEventListener("load", updateArrows);

  updateArrows();
}
