{{-- Global "a request is in flight" indicator — body is hx-boost'ed, so every
     link click and form submit becomes an AJAX swap with no browser navigation
     and therefore no native loading feedback. Driven entirely by
     resources/js/behaviors/page-loading-bar.js via HTMX's global lifecycle
     events, so it covers boosted links, hx-get/hx-post, and forms alike. --}}
<div class="page-loading-bar" data-page-loading-bar aria-hidden="true"></div>
