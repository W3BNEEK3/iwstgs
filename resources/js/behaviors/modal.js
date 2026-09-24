/**
 * Generic open/close wiring for .modal-overlay elements — backdrop click,
 * Escape key, and any [data-modal-close] button inside. Confirmation modals
 * (design doc §10.3) are the same component with a destructive-action trigger;
 * this is the shared plumbing both use.
 */
export function initModals() {
  document.addEventListener('click', (e) => {
    const opener = e.target.closest('[data-modal-open]');
    if (opener) {
      const modal = document.getElementById(opener.dataset.modalOpen);
      modal?.classList.add('is-open');
      modal?.querySelector('[autofocus], button, input')?.focus();
      return;
    }

    const overlay = e.target.closest('.modal-overlay');
    if (overlay && e.target === overlay) {
      overlay.classList.remove('is-open');
      return;
    }

    const closer = e.target.closest('[data-modal-close]');
    if (closer) {
      closer.closest('.modal-overlay')?.classList.remove('is-open');
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.modal-overlay.is-open').forEach((m) => m.classList.remove('is-open'));
  });
}

