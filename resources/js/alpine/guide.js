import { pushToast } from '../behaviors/toast-autodismiss';

function post(url) {
  return fetch(url, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'same-origin',
  }).catch(() => {});
}

/**
 * Tiroco's guide card. Steps in `autoShow` open by themselves (once each);
 * the launcher replays every step for the page. "Got it" on a step's last
 * card dismisses it for good; closing just hides it until the next visit.
 */
export default ({ steps, autoShow, dismissUrl, disableUrl }) => ({
  open: false,
  queue: [],
  index: 0,
  card: 0,
  confirmingOff: false,

  init() {
    if (autoShow.length === 0) return;
    this.queue = autoShow;
    setTimeout(() => { this.open = true; }, 500);
  },

  step() {
    return steps.find((s) => s.key === this.queue[this.index]);
  },

  currentCard() {
    return this.step()?.cards[this.card];
  },

  isLastCard() {
    return this.card >= (this.step()?.cards.length ?? 1) - 1;
  },

  next() {
    if (!this.isLastCard()) {
      this.card++;
      return;
    }

    post(dismissUrl.replace('__STEP__', this.step().key));

    if (this.index < this.queue.length - 1) {
      this.index++;
      this.card = 0;
    } else {
      this.open = false;
    }
  },

  later() {
    this.open = false;
    this.confirmingOff = false;
  },

  replay() {
    this.queue = steps.map((s) => s.key);
    this.index = 0;
    this.card = 0;
    this.confirmingOff = false;
    this.open = true;
    this.$nextTick(() => this.$refs.primary?.focus());
  },

  turnOff() {
    post(disableUrl);
    this.open = false;
    this.confirmingOff = false;
    pushToast('info', 'Guide turned off. Tap the lightbulb any time for tips, or turn it back on in Settings.');
  },
});
