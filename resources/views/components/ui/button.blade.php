{{-- <x-ui.button severity="destructive" type="submit" icon="delete">Delete</x-ui.button>
     Pass href instead of type for a navigation action — renders a real <a> so
     ctrl/middle-click and screen readers behave correctly, not a button faking
     navigation via onclick. Severity is the only color signal (design doc §12).
     The spinner shows while the button has aria-busy="true"; it is `hidden` in the
     markup so it can never spin on its own, even if the CSS bundle is stale. --}}
@props(['severity' => 'primary', 'type' => 'button', 'icon' => null, 'href' => null])

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => "btn btn-{$severity}"]) }}
    >
        @if ($icon)
            <x-ui.icon :name="$icon" :size="16" />
        @endif
        <span class="btn-spinner" aria-hidden="true" hidden></span>
        <span>{{ $slot }}</span>
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge(['class' => "btn btn-{$severity}"]) }}
    >
        @if ($icon)
            <x-ui.icon :name="$icon" :size="16" />
        @endif
        <span class="btn-spinner" aria-hidden="true" hidden></span>
        <span>{{ $slot }}</span>
    </button>
@endif
