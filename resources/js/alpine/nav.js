const COLLAPSE_KEY = 'iwstgs-sidebar-collapsed';

function readCollapsed() {
  try {
    return localStorage.getItem(COLLAPSE_KEY) === '1';
  } catch {
    return false;
  }
}

/**
 * One store drives every navigation surface: the desktop sidebar's collapsed
 * state, the dashboard/admin off-canvas drawer, and the public-site drawer.
 * Bound declaratively in Blade, so there is no element reference to go stale.
 */
export default {
  drawerOpen: false,
  collapsed: readCollapsed(),

  openDrawer() {
    this.drawerOpen = true;
    document.body.style.overflow = 'hidden';
  },

  closeDrawer() {
    this.drawerOpen = false;
    document.body.style.overflow = '';
  },

  toggleCollapsed() {
    this.collapsed = !this.collapsed;
    try {
      localStorage.setItem(COLLAPSE_KEY, this.collapsed ? '1' : '0');
    } catch {
      // Private mode: the preference just won't persist.
    }
  },
};
