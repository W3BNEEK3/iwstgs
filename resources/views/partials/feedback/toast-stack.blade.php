{{-- Global toast container (design doc §10.1). Pre-renders the existing
     session('success')/'info'/'error'/'warning') flash conventions already
     used across the app's controllers, so nothing had to be rewritten to
     adopt this — new server-pushed toasts use the OOB pattern instead, see
     the design doc §19. --}}
@php
    $flashToasts = [
        'success' => ['kind' => 'success', 'icon' => 'check_circle'],
        'info'    => ['kind' => 'info', 'icon' => 'info'],
        'error'   => ['kind' => 'error', 'icon' => 'error'],
        'warning' => ['kind' => 'warning', 'icon' => 'warning'],
    ];
@endphp
<div id="toast-stack">
    @foreach ($flashToasts as $key => $meta)
        @if (session($key))
            <div class="toast toast-{{ $meta['kind'] }}">
                <span class="material-symbols-outlined toast-icon">{{ $meta['icon'] }}</span>
                <div class="toast-body"><div class="toast-msg">{{ session($key) }}</div></div>
                <button type="button" class="toast-close" aria-label="Dismiss">
                    <span class="material-symbols-outlined" style="font-size:14px">close</span>
                </button>
            </div>
        @endif
    @endforeach

    {{-- Validation errors are NOT auto-toasted here — they belong at the field
         level (design doc §10.4). See components/forms/text-input.blade.php. --}}
</div>
