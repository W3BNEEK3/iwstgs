/**
 * Spinners are rendered with the `hidden` attribute (so they can never spin
 * on their own) and revealed here while their button is busy.
 */
export function setBusy(button, busy) {
  if (!button) return;
  button.toggleAttribute('aria-busy', busy);
  if (busy) button.setAttribute('aria-busy', 'true');
  button.querySelector('.btn-spinner')?.toggleAttribute('hidden', !busy);
}

/**
 * Marks the button that submitted a form as busy while the next page loads,
 * which also blocks accidental double submits.
 */
export function initBusyButtons() {
  document.addEventListener('submit', (e) => {
    if (e.defaultPrevented) return;
    setBusy(e.submitter, true);
  });

  // Back/forward cache can restore a page with buttons still marked busy.
  window.addEventListener('pageshow', (e) => {
    if (!e.persisted) return;
    document.querySelectorAll('[aria-busy="true"]').forEach((el) => setBusy(el, false));
  });
}
