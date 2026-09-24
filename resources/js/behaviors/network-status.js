/**
 * Drives the network status snackbar (design doc §10.2). This cannot be
 * server-driven — if the network is actually down there's no round-trip to
 * report it — so it's detected client-side via navigator.onLine plus HTMX's
 * own request-failure events, which distinguish "slow" from "fully offline."
 */
export function initNetworkStatus() {
  const el = document.querySelector('[data-network-snackbar]');
  if (!el) return;

  const label = el.querySelector('[data-snackbar-label]');
  let slowRequestTimer = null;
  let restoredTimer = null;

  function show(state, text) {
    clearTimeout(restoredTimer);
    el.classList.remove('state-offline', 'state-restored');
    el.classList.add('is-visible');
    if (state) el.classList.add(state);
    if (label) label.textContent = text;
    el.setAttribute('aria-label', text);

    if (state === 'state-restored') {
      restoredTimer = setTimeout(() => hide(), 3000);
    }
  }

  function hide() {
    el.classList.remove('is-visible', 'is-expanded', 'state-offline', 'state-restored');
  }

  window.addEventListener('offline', () => show('state-offline', 'Offline'));
  window.addEventListener('online', () => show('state-restored', 'Connection restored'));

  // A request that's taking unusually long suggests a poor connection, not a
  // full outage — htmx:beforeRequest/afterRequest bracket that window.
  document.body.addEventListener('htmx:beforeRequest', () => {
    slowRequestTimer = setTimeout(() => {
      if (navigator.onLine) show('', 'Poor connection');
    }, 4000);
  });
  document.body.addEventListener('htmx:afterRequest', (e) => {
    clearTimeout(slowRequestTimer);
    if (e.detail.successful && !el.classList.contains('is-visible')) return;
    if (e.detail.successful) hide();
  });
  document.body.addEventListener('htmx:sendError', () => show('state-offline', 'Offline'));
  document.body.addEventListener('htmx:timeout', () => show('', 'Poor connection'));

  // Mobile: tap the collapsed circle to expand/collapse.
  el.addEventListener('click', () => {
    if (window.matchMedia('(max-width: 639px)').matches) {
      el.classList.toggle('is-expanded');
    }
  });
}
