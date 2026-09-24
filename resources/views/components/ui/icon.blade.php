{{-- <x-ui.icon name="check_circle" size="20" fill /> — Google Material Symbols,
     never emoji (design doc §6). Decorative by default (aria-hidden); pass
     aria-label to make it a meaningful standalone icon-only control instead. --}}
@props(['name', 'size' => 20, 'fill' => false])

<span
    {{ $attributes->merge(['class' => 'material-symbols-outlined']) }}
    @unless ($attributes->has('aria-label')) aria-hidden="true" @endunless
    style="font-size:{{ $size }}px;font-variation-settings:'FILL' {{ $fill ? 1 : 0 }},'wght' 400,'GRAD' 0,'opsz' {{ $size }};"
>{{ $name }}</span>
