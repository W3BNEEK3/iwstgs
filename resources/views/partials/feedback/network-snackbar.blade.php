{{-- Connection-state indicator — persistent, not a one-off toast (design doc
     §10.2). Driven entirely client-side by resources/js/behaviors/network-status.js
     since a downed network has no server round-trip to report itself with. --}}
<button type="button" class="network-snackbar" data-network-snackbar aria-label="">
    <span class="material-symbols-outlined snackbar-icon" style="font-size:16px" aria-hidden="true">network_check</span>
    <span class="snackbar-label" data-snackbar-label></span>
    <span class="material-symbols-outlined snackbar-close" style="font-size:14px" aria-hidden="true">close</span>
</button>
