/**
 * Toasts can arrive two ways: pushed by client-side JS (window.iwstgsToast),
 * or delivered by the server as an HTMX out-of-band swap into #toast-stack
 * (design doc §19). A MutationObserver on the stack covers both sources with
 * one piece of wiring — dismiss button, expand/collapse for long messages,
 * and auto-dismiss for success/info (errors and warnings wait for the user,
 * design doc §10.1).
 */
export function initToastAutodismiss() {
  const stack = document.getElementById('toast-stack');
  if (!stack) return;

  function wire(toast) {
    if (toast.dataset.wired) return;
    toast.dataset.wired = '1';

    const closeBtn = toast.querySelector('.toast-close');
    closeBtn?.addEventListener('click', () => toast.remove());

    const moreBtn = toast.querySelector('.toast-more');
    const msg = toast.querySelector('.toast-msg');
    moreBtn?.addEventListener('click', () => {
      const collapsed = msg.classList.toggle('clamp');
      moreBtn.textContent = collapsed ? 'Show more' : 'Show less';
    });

    if (toast.classList.contains('toast-success') || toast.classList.contains('toast-info')) {
      setTimeout(() => toast.remove(), 5000);
    }
  }

  stack.querySelectorAll('.toast').forEach(wire);

  new MutationObserver((mutations) => {
    for (const m of mutations) {
      m.addedNodes.forEach((node) => {
        if (node.nodeType === 1 && node.classList?.contains('toast')) wire(node);
      });
    }
  }).observe(stack, { childList: true });
}

/**
 * Client-side toast push, for JS-triggered feedback that doesn't go through
 * a server round-trip. Server-driven toasts use the HTMX OOB pattern instead
 * — see resources/views/partials/feedback/toast-stack.blade.php.
 */
export function pushToast(kind, message) {
  const stack = document.getElementById('toast-stack');
  if (!stack) return;

  const icons = {
    success: '<span class="material-symbols-outlined toast-icon">check_circle</span>',
    error:   '<span class="material-symbols-outlined toast-icon">error</span>',
    warning: '<span class="material-symbols-outlined toast-icon">warning</span>',
    info:    '<span class="material-symbols-outlined toast-icon">info</span>',
  };

  const el = document.createElement('div');
  el.className = `toast toast-${kind}`;
  el.innerHTML = `
    ${icons[kind] || icons.info}
    <div class="toast-body"><div class="toast-msg">${message}</div></div>
    <button type="button" class="toast-close" aria-label="Dismiss">
      <span class="material-symbols-outlined" style="font-size:14px">close</span>
    </button>`;
  stack.appendChild(el);
}

if (typeof window !== 'undefined') {
  window.iwstgsToast = pushToast;
}

/**
 * HTMX drops non-2xx responses by default — it only swaps 2xx bodies in, and
 * a 4xx/5xx from a boosted link/form otherwise vanishes with zero feedback
 * (design doc §1 rule 5: "the interaction should never leave the user
 * wondering if it worked"). This is the gap that made login failures show
 * nothing before the underlying session bug was fixed — plain (non-boosted)
 * form posts still get Laravel's own redirect+flash handling, which already
 * works; this covers the hx-boost/hx-get/hx-post path specifically.
 */
export function initHtmxErrorToasts() {
  document.body.addEventListener('htmx:responseError', (e) => {
    const status = e.detail.xhr.status;

    // 419 means the CSRF token embedded in the page is stale — retrying the
    // same boosted request would just 419 again, since the token in the DOM
    // never refreshes without a reload. Reload gets a fresh one automatically.
    if (status === 419) {
      pushToast('warning', 'Your session expired — reloading the page.');
      setTimeout(() => window.location.reload(), 1200);
      return;
    }

    const messages = {
      403: "You don't have permission to do that.",
      404: 'That could not be found.',
      422: 'Please check the form for errors.',
      429: 'Slow down a little and try again.',
      500: 'Something went wrong on our end.',
      503: "We're down for maintenance — try again shortly.",
    };
    pushToast('error', messages[status] || `Something went wrong (${status}).`);
  });

  // A request that never got a response at all (server unreachable) — the
  // network snackbar (network-status.js) already covers the persistent
  // offline state; this is the one-off toast for "this specific action failed."
  document.body.addEventListener('htmx:sendError', () => {
    pushToast('error', "Couldn't reach the server. Check your connection and try again.");
  });
}
