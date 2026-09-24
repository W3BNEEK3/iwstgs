/**
 * Global "something is loading" feedback: starts when the browser begins
 * leaving the page (link click / form submit) and brackets every $ajax call.
 */
let activeRequests = 0;
let hideTimer = null;

function bar() {
  return document.querySelector('[data-page-loading-bar]');
}

export function startLoading() {
  activeRequests++;
  if (activeRequests > 1) return;

  clearTimeout(hideTimer);
  const el = bar();
  if (!el) return;
  el.classList.remove('is-done', 'is-hidden');
  // Force a reflow so the width transition restarts from 0.
  void el.offsetWidth;
  el.classList.add('is-loading');
}

export function finishLoading() {
  activeRequests = Math.max(0, activeRequests - 1);
  if (activeRequests > 0) return;

  const el = bar();
  if (!el) return;
  el.classList.remove('is-loading');
  el.classList.add('is-done');
  hideTimer = setTimeout(() => bar()?.classList.add('is-hidden'), 350);
}

export function initPageLoadingBar() {
  window.addEventListener('beforeunload', startLoading);
  // Back/forward cache restores the page with the bar mid-animation.
  window.addEventListener('pageshow', (e) => {
    if (e.persisted) {
      activeRequests = 1;
      finishLoading();
    }
  });
}
