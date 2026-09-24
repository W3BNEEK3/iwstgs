{{-- <x-ui.avatar :name="auth()->user()->name" /> or :src="$logoUrl" --}}
@props(['name' => null, 'src' => null])

<span {{ $attributes->merge(['class' => 'avatar']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="">
    @else
        {{ strtoupper(substr($name ?? '?', 0, 1)) }}
    @endif
</span>
