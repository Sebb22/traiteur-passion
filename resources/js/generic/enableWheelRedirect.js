// enableWheelRedirect.js
export function enableWheelRedirect({
  rootSelector = "[data-wheel-redirect]",
  breakpoint = "(min-width: 1280px)",
  targetDefault = null, // ex: ".menuPanel" si tu veux un fallback
} = {}) {
  const roots = document.querySelectorAll(rootSelector);
  if (!roots.length) return () => {};

  const mq = window.matchMedia(breakpoint);

  const shouldIgnore = (e) => {
    // Accessibilité / comportements système
    if (e.ctrlKey || e.metaKey || e.altKey) return true; // zoom, gestures, etc.
    if (e.shiftKey) return true; // souvent scroll horizontal
    if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) return true;

    // zones à ignorer (sliders, carrousels, etc.)
    const el = e.target.closest("[data-wheel-ignore], .aboutSlider__viewport");
    if (el) return true;

    return false;
  };

  // On garde les handlers pour pouvoir cleanup
  const cleanups = [];

  roots.forEach((root) => {
    const targetSelector =
      root.getAttribute("data-wheel-target") || targetDefault;

    if (!targetSelector) return;

    // 1) on cherche d’abord dans root
    let target = root.querySelector(targetSelector);

    // 2) sinon fallback global (pratique si target est ailleurs)
    if (!target) target = document.querySelector(targetSelector);

    if (!target) return;

   const onWheel = (e) => {
  if (!mq.matches) return;
  if (shouldIgnore(e)) return;

  const maxScroll = target.scrollHeight - target.clientHeight;
  if (maxScroll <= 0) return;

  // Toujours empêcher le scroll global sur desktop
  e.preventDefault();

  const delta = e.deltaY;

  // Clamp (0 -> maxScroll)
  const next = Math.min(maxScroll, Math.max(0, target.scrollTop + delta));
  target.scrollTop = next;
};

    root.addEventListener("wheel", onWheel, { passive: false });
    cleanups.push(() => root.removeEventListener("wheel", onWheel));
  });

  // ✅ retourne une fonction cleanup
  return () => cleanups.forEach((fn) => fn());
}
