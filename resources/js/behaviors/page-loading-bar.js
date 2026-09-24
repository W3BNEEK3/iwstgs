/**
 * Global "a request is in flight" feedback. Body is hx-boost'ed (see
 * layouts/app.blade.php), so a boosted navigation swaps body's innerHTML on
 * completion — the bar element itself gets replaced by every swap, so the
 * DOM node is re-queried on every event rather than cached at init, or the
 * classList toggles below would silently operate on a detached node after
 * the first navigation. The htmx:beforeRequest/afterRequest listeners are
 * attached to document.body itself, which survives the swap, so they only
 * need attaching once.
 */
export function initPageLoadingBar() {
  let activeRequests = 0;
  let hideTimer = null;

  function bar() {
    return document.querySelector('[data-page-loading-bar]');
  }

  function start() {
    activeRequests++;
    if (activeRequests > 1) return;

    clearTimeout(hideTimer);
    const el = bar();
    if (!el) return;
    el.classList.remove('is-done', 'is-hidden');
    // Force a reflow so the width transition restarts from 0 even if the
    // bar was mid-fade-out from a previous request.
    void el.offsetWidth;
    el.classList.add('is-loading');
  }

  function finish() {
    activeRequests = Math.max(0, activeRequests - 1);
    if (activeRequests > 0) return;

    const el = bar();
    if (!el) return;
    el.classList.remove('is-loading');
    el.classList.add('is-done');
    hideTimer = setTimeout(() => {
      const current = bar();
      if (current) current.classList.add('is-hidden');
    }, 350);
  }

  document.body.addEventListener('htmx:beforeRequest', start);
  document.body.addEventListener('htmx:afterRequest', finish);
  document.body.addEventListener('htmx:sendError', finish);
}
