/**
 * Generic [data-dropdown-toggle]/[data-dropdown] open-close wiring — the
 * profile menu today, reusable for any future popover (design doc §9, §16
 * "Overlays: dropdown menu, popover").
 *
 * .action-menu panels (table row actions) get viewport-fixed positioning
 * computed from the trigger's rect on open, rather than relying on CSS
 * position:absolute. A row action lives inside .table-frame, which sets
 * overflow-x:auto — and per the CSS spec, setting only one overflow axis to
 * non-visible forces the other axis to 'auto' too, so an absolutely
 * positioned dropdown that overflows the table's box gets silently clipped
 * to a squashed sliver instead of floating freely. position:fixed escapes
 * that clipping entirely (it's relative to the viewport, not any scrolling
 * ancestor). .profile-dropdown is untouched — it isn't inside a clipping
 * container, so its existing CSS-anchored position:absolute is left alone.
 */
export function initDropdowns() {
  document.addEventListener('click', (e) => {
    const toggle = e.target.closest('[data-dropdown-toggle]');
    if (toggle) {
      const dropdown = document.getElementById(toggle.dataset.dropdownToggle);
      const isOpen = dropdown && !dropdown.hasAttribute('hidden');
      closeAllDropdowns();
      if (dropdown && !isOpen) {
        positionActionMenu(toggle, dropdown);
        dropdown.removeAttribute('hidden');
        toggle.setAttribute('aria-expanded', 'true');
      }
      return;
    }

    if (!e.target.closest('[data-dropdown]')) {
      closeAllDropdowns();
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    closeAllDropdowns();
  });

  // A position:fixed menu doesn't track its trigger during scroll — closing
  // it is simpler and more predictable than repositioning on every frame.
  // Capture phase so this also fires for scrolling inside .table-frame
  // itself, not just window-level scroll.
  window.addEventListener('scroll', closeAllDropdowns, true);
  window.addEventListener('resize', closeAllDropdowns);
}

function closeAllDropdowns() {
  document.querySelectorAll('[data-dropdown]').forEach((d) => d.setAttribute('hidden', ''));
  document.querySelectorAll('[data-dropdown-toggle]').forEach((t) => t.setAttribute('aria-expanded', 'false'));
}

function positionActionMenu(toggle, dropdown) {
  if (!dropdown.classList.contains('action-menu')) return;

  const rect = toggle.getBoundingClientRect();
  dropdown.style.position = 'fixed';
  dropdown.style.top = `${rect.bottom + 4}px`;
  dropdown.style.left = 'auto';
  dropdown.style.right = `${window.innerWidth - rect.right}px`;
}
