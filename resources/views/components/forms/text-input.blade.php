{{-- <x-forms.text-input name="email" label="Email address" type="email" required />
     Field-level errors render directly beneath the input (design doc §10.4). --}}
@props(['name', 'label' => null, 'type' => 'text', 'help' => null, 'required' => false, 'value' => null])

@php
    $hasError = $errors->has($name);
    $resolvedValue = old($name, $value);
@endphp

<div class="field @if ($hasError) has-error @endif">
    @if ($label)
        <label for="{{ $name }}">{{ $label }}@if ($required) <span aria-hidden="true">*</span>@endif</label>
    @endif

    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ $resolvedValue }}"
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes }}
    >

    @if ($hasError)
        <span class="field-error" id="{{ $name }}-error">
            <x-ui.icon name="error" :size="12" />
            {{ $errors->first($name) }}
        </span>
    @elseif ($help)
        <span class="field-help">{{ $help }}</span>
    @endif
</div>
