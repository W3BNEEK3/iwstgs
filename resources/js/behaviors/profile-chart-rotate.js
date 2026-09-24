/**
 * The competency web chart on the learner profile spins gently as the page
 * scrolls — tied to scroll position (not scroll-into-view), so it keeps
 * turning for as long as the page moves. Skipped entirely under
 * prefers-reduced-motion (base.css already applies that rule to CSS
 * animations; this is a JS-driven transform, so it needs its own check).
 */
const DEGREES_PER_PIXEL = 0.15;

export function initProfileChartRotate() {
  const chart = document.querySelector('[data-rotate-on-scroll]');
  if (!chart) return;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  let ticking = false;

  function apply() {
    chart.style.transform = `rotate(${window.scrollY * DEGREES_PER_PIXEL}deg)`;
    ticking = false;
  }

  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(apply);
  }

  apply();
  window.addEventListener('scroll', onScroll, { passive: true });
}
