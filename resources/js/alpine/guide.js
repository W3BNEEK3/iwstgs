import { pushToast } from '../behaviors/toast-autodismiss';

function request(url, { method = 'POST', body } = {}) {
  return fetch(url, {
    method,
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
      'X-Requested-With': 'XMLHttpRequest',
      Accept: 'application/json',
      ...(body ? { 'Content-Type': 'application/json' } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
    credentials: 'same-origin',
    keepalive: method === 'POST', // a dismiss sent as the learner follows a link must still arrive
  });
}

function post(url, body) {
  return request(url, { body }).catch(() => {});
}

const KIND_LABELS = {
  nudge: 'a nudge',
  tip: 'a tip',
  resource: 'something to try',
  announcement: "what's new",
};

/**
 * Tiroco's guide card. Two sources share it:
 *  - walkthrough steps: `autoShow` ones open by themselves (once each), the
 *    launcher replays every step for the page, and "Got it" on a step's last
 *    card dismisses it for good;
 *  - Tiroco's own messages: fetched from `nextUrl` once the page has loaded
 *    and any walkthrough is out of the way. At most one per page.
 * On a task page, `stuck` reports a long stretch without submitting, then
 * asks again in case that produced a nudge.
 */
export default ({ steps, autoShow, dismissUrl, disableUrl, nextUrl, messageUrl, stuck }) => ({
  open: false,
  queue: [],
  index: 0,
  card: 0,
  confirmingOff: false,
  message: null,
  messageShown: false,
  showWhy: false,
  rated: null,

  init() {
    if (autoShow.length > 0) {
      this.queue = autoShow;
      setTimeout(() => { this.open = true; }, 500);
    } else {
      setTimeout(() => this.fetchMessage(), 800);
    }

    if (stuck) {
      setTimeout(() => this.reportStuck(), stuck.minutes * 60 * 1000);
    }
  },

  // ---- Walkthrough steps -------------------------------------------------

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
      setTimeout(() => this.fetchMessage(), 400);
    }
  },

  replay() {
    this.message = null;
    this.queue = steps.map((s) => s.key);
    this.index = 0;
    this.card = 0;
    this.confirmingOff = false;
    this.open = true;
    this.$nextTick(() => this.$refs.primary?.focus());
  },

  // ---- Tiroco's own messages ---------------------------------------------

  async fetchMessage() {
    if (!nextUrl || this.messageShown || this.open) return;

    try {
      const response = await request(nextUrl, { method: 'GET' });
      if (!response.ok) return;
      const { message } = await response.json();
      if (!message || this.open) return;

      this.messageShown = true;
      this.message = message;
      this.showWhy = false;
      this.rated = null;
      this.open = true;
    } catch {
      // The guide is optional: a failed fetch simply means no message this time.
    }
  },

  kindLabel() {
    return KIND_LABELS[this.message?.kind] ?? 'your guide';
  },

  messageAction(action) {
    return messageUrl.replace('00000000-0000-0000-0000-000000000000', this.message.id).replace(/dismiss$/, action);
  },

  done() {
    if (!this.message) return;
    post(this.messageAction('dismiss'));
    this.open = false;
  },

  rate(helpful) {
    this.rated = helpful;
    post(this.messageAction('rate'), { helpful });
  },

  optOut(reason) {
    post(this.messageAction('opt-out'), { reason });
    this.open = false;
    pushToast('info', "Got it. I won't suggest that one again.");
  },

  async reportStuck() {
    if (!stuck) return;
    await post(stuck.url, { session: stuck.session, task: stuck.task, minutes: stuck.minutes });
    this.messageShown = false;
    this.fetchMessage();
  },

  // ---- Shared -------------------------------------------------------------

  later() {
    if (this.message && !this.confirmingOff) {
      this.done();
      return;
    }
    this.open = false;
    this.confirmingOff = false;
  },

  turnOff() {
    post(disableUrl);
    this.open = false;
    this.confirmingOff = false;
    this.message = null;
    pushToast('info', 'Guide turned off. Tap the lightbulb any time for tips, or turn it back on in Settings.');
  },
});
