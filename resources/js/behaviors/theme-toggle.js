/**
 * Theme default follows prefers-color-scheme; an explicit choice overrides it
 * and persists via cookie (not localStorage) so app.blade.php can read it
 * server-side and set data-theme before first paint — no flash of the wrong
 * theme (design doc §2.3, confirmed §22.5).
 */
export function initThemeToggle() {
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-theme-choice]');
    if (!btn) return;

    const choice = btn.dataset.themeChoice; // 'light' | 'dark' | 'system'
    if (choice === 'system') {
      document.documentElement.removeAttribute('data-theme');
      document.cookie = 'theme=; path=/; max-age=0';
    } else {
      document.documentElement.setAttribute('data-theme', choice);
      document.cookie = `theme=${choice}; path=/; max-age=31536000; samesite=lax`;
    }
  });
}
