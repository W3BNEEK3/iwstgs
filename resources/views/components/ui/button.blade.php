{{-- <x-ui.button severity="destructive" type="submit" icon="delete">Delete</x-ui.button>
     Pass href instead of type for a navigation action — renders a real <a> so
     ctrl/middle-click and screen readers behave correctly, not a button faking
     navigation via onclick. Severity is the only color signal (design doc §12).
     Loading state relies on HTMX's built-in .htmx-indicator convention — no
     custom JS (design doc §19). --}}
@props(['severity' => 'primary', 'type' => 'button', 'icon' => null, 'href' => null])

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge(['class' => "btn btn-{$severity}"]) }}
    >
        @if ($icon)
            <x-ui.icon :name="$icon" :size="16" />
        @endif
        <span class="btn-spinner htmx-indicator" aria-hidden="true"></span>
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
        <span class="btn-spinner htmx-indicator" aria-hidden="true"></span>
        <span>{{ $slot }}</span>
    </button>
@endif
