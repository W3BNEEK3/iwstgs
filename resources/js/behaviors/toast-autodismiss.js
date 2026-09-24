/**
 * Toasts can arrive two ways: rendered by the server into #toast-stack from
 * session flash data, or pushed by client-side JS (window.iwstgsToast /
 * pushToast). A MutationObserver on the stack covers both sources with
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

/** Client-side toast push, for JS-triggered feedback (e.g. a failed $ajax call). */
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
    <div class="toast-body"><div class="toast-msg"></div></div>
    <button type="button" class="toast-close" aria-label="Dismiss">
      <span class="material-symbols-outlined" style="font-size:14px">close</span>
    </button>`;
  el.querySelector('.toast-msg').textContent = message;
  stack.appendChild(el);
}

if (typeof window !== 'undefined') {
  window.iwstgsToast = pushToast;
}
