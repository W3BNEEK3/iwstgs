import './bootstrap';
import { initSidebarCollapse } from './behaviors/sidebar-collapse';
import { initNetworkStatus } from './behaviors/network-status';
import { initToastAutodismiss, initHtmxErrorToasts } from './behaviors/toast-autodismiss';
import { initModals } from './behaviors/modal';
import { initThemeToggle } from './behaviors/theme-toggle';
import { initDropdowns } from './behaviors/dropdown';
import { initMobileDrawer } from './behaviors/mobile-drawer';
import { initPageLoadingBar } from './behaviors/page-loading-bar';
import { initProfileChartRotate } from './behaviors/profile-chart-rotate';

document.addEventListener('DOMContentLoaded', () => {
  initSidebarCollapse();
  initNetworkStatus();
  initToastAutodismiss();
  initHtmxErrorToasts();
  initModals();
  initThemeToggle();
  initDropdowns();
  initMobileDrawer();
  initPageLoadingBar();
  initProfileChartRotate();
});

// hx-boost'ed navigations swap the body without a full reload, so re-run the
// per-page wiring after every settle (design doc §19).
document.body.addEventListener('htmx:afterSettle', () => {
  initSidebarCollapse();
  initToastAutodismiss();
  initProfileChartRotate();
});
