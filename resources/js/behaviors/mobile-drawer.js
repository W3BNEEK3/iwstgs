/**
 * Sidebar becomes a full-height off-canvas drawer on mobile — one navigation
 * surface, not a separate bottom tab bar (design doc §9).
 */
export function initMobileDrawer() {
  const overlay = document.querySelector('[data-mobile-drawer-overlay]');
  const drawer = document.querySelector('[data-mobile-drawer]');
  if (!overlay || !drawer) return;

  function open() {
    overlay.classList.add('is-open');
    drawer.classList.add('is-open');
  }
  function close() {
    overlay.classList.remove('is-open');
    drawer.classList.remove('is-open');
  }

  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-mobile-drawer-toggle]')) return open();
    if (e.target === overlay) return close();
    if (e.target.closest('[data-mobile-drawer] a')) return close();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') close();
  });
}
