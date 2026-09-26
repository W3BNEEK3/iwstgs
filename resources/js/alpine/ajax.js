import { pushToast } from '../behaviors/toast-autodismiss';

const ERROR_MESSAGES = {
  403: "You don't have permission to do that.",
  404: 'That could not be found.',
  422: 'Please check the form for errors.',
  429: 'Slow down a little and try again.',
  500: 'Something went wrong on our end.',
  503: "We're down for maintenance — try again shortly.",
};

function csrfToken() {
  return document.querySelector('meta[name=csrf-token]')?.content ?? '';
}

function parse(html) {
  const template = document.createElement('template');
  template.innerHTML = html.trim();
  return template.content;
}

/**
 * Elements marked data-swap-oob replace the element with the same id anywhere
 * on the page (e.g. a publish toggle updates both the desktop table row and
 * its mobile card from a single response).
 */
function applyOutOfBand(fragment) {
  fragment.querySelectorAll('[data-swap-oob]').forEach((node) => {
    node.remove();
    node.removeAttribute('data-swap-oob');
    document.getElementById(node.id)?.replaceWith(node);
  });
}

/**
 * '#some-id' is looked up with getElementById, not querySelector: ids built
 * from keys like "simulation.project_catalogue" contain dots, which a CSS
 * selector would read as class names and silently match nothing.
 */
function resolveTarget(target) {
  if (typeof target !== 'string') return target;
  return target.startsWith('#') ? document.getElementById(target.slice(1)) : document.querySelector(target);
}

function swapInto(target, fragment, swap) {
  if (!target) return;
  if (swap === 'beforeend') target.append(fragment);
  else if (swap === 'innerHTML') target.replaceChildren(fragment);
  else target.replaceWith(fragment);
}

/**
 * Server-rendered partial swap for admin inline actions. The endpoints return
 * Blade partials (the same ones used for the initial render), so markup stays
 * server-owned and Alpine only moves HTML into place.
 *
 * $ajax(url, { method, target, swap, form, reset })
 *   swap: 'outerHTML' (default) | 'beforeend' | 'innerHTML'
 */
export async function ajax(url, { method = 'POST', target = null, swap = 'outerHTML', form = null, reset = false } = {}) {
  const verb = method.toUpperCase();
  const body = form ? new FormData(form) : new FormData();
  if (!['GET', 'POST'].includes(verb)) body.set('_method', verb);

  let response;
  try {
    response = await fetch(url, {
      method: verb === 'GET' ? 'GET' : 'POST',
      // '*/*' makes Laravel answer validation failures with 422 JSON instead of a redirect.
      headers: { 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest', Accept: '*/*' },
      body: verb === 'GET' ? undefined : body,
      credentials: 'same-origin',
    });
  } catch {
    pushToast('error', "Couldn't reach the server. Check your connection and try again.");
    return false;
  }

  if (response.status === 419) {
    pushToast('warning', 'Your session expired — reloading the page.');
    setTimeout(() => window.location.reload(), 1200);
    return false;
  }

  if (response.status === 422) {
    const errors = (await response.json().catch(() => ({}))).errors ?? {};
    pushToast('error', Object.values(errors).flat()[0] ?? ERROR_MESSAGES[422]);
    return false;
  }

  if (!response.ok) {
    pushToast('error', ERROR_MESSAGES[response.status] || `Something went wrong (${response.status}).`);
    return false;
  }

  const fragment = parse(await response.text());
  applyOutOfBand(fragment);
  swapInto(resolveTarget(target), fragment, swap);

  if (reset && form) form.reset();
  return true;
}
