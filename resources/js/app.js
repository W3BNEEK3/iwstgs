import './bootstrap';
import Alpine from 'alpinejs';
import nav from './alpine/nav';
import { ajax } from './alpine/ajax';
import guide from './alpine/guide';
import { initNetworkStatus } from './behaviors/network-status';
import { initToastAutodismiss } from './behaviors/toast-autodismiss';
import { initModals } from './behaviors/modal';
import { initThemeToggle } from './behaviors/theme-toggle';
import { initDropdowns } from './behaviors/dropdown';
import { initPageLoadingBar, startLoading, finishLoading } from './behaviors/page-loading-bar';
import { initProfileChartRotate } from './behaviors/profile-chart-rotate';
import { initBusyButtons } from './behaviors/busy-buttons';

Alpine.store('nav', nav);
Alpine.data('guide', guide);

Alpine.magic('ajax', (el) => async (url, options = {}) => {
  const busy = options.form?.querySelector('[type=submit]') ?? el.closest('button');
  busy?.setAttribute('aria-busy', 'true');
  startLoading();
  try {
    return await ajax(url, options);
  } finally {
    busy?.removeAttribute('aria-busy');
    finishLoading();
  }
});

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
  initNetworkStatus();
  initToastAutodismiss();
  initModals();
  initThemeToggle();
  initDropdowns();
  initPageLoadingBar();
  initProfileChartRotate();
  initBusyButtons();
});
