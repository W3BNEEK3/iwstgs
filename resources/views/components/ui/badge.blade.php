{{-- <x-ui.badge tone="success">Active</x-ui.badge> — color + dot together,
     never color alone (design doc §1 rule 4, §13.1). --}}
@props(['tone' => 'neutral'])

<span {{ $attributes->merge(['class' => "badge badge-{$tone}"]) }}>
    <span class="badge-dot" aria-hidden="true"></span>
    {{ $slot }}
</span>
