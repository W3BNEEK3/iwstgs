/**
 * Persists the dashboard/admin sidebar's collapsed state across page loads.
 * A full HTMX/server round-trip isn't warranted for a purely client-side
 * preference like this — see design doc §9 and §19.
 */
export function initSidebarCollapse() {
  const shell = document.querySelector('[data-app-shell]');
  const toggle = document.querySelector('[data-sidebar-toggle]');
  if (!shell || !toggle) return;

  const STORAGE_KEY = 'iwstgs-sidebar-collapsed';

  if (localStorage.getItem(STORAGE_KEY) === '1') {
    shell.classList.add('is-collapsed');
  }

  toggle.addEventListener('click', () => {
    const collapsed = shell.classList.toggle('is-collapsed');
    localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
  });
}
