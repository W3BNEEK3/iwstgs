/**
 * Marks the button that submitted a form as busy so its spinner shows while
 * the next page loads, and blocks accidental double submits.
 */
export function initBusyButtons() {
  document.addEventListener('submit', (e) => {
    if (e.defaultPrevented) return;
    e.submitter?.setAttribute('aria-busy', 'true');
  });

  // Back/forward cache can restore a page with buttons still marked busy.
  window.addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    document.querySelectorAll('[aria-busy="true"]').forEach((el) => el.removeAttribute('aria-busy'));
  });
}
