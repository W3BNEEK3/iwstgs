{{-- <x-forms.select name="role_id" label="Role" :options="['id1' => 'Backend Engineer']" /> --}}
@props(['name', 'label' => null, 'options' => [], 'help' => null, 'required' => false, 'value' => null, 'placeholder' => null])

@php
    $hasError = $errors->has($name);
    $resolvedValue = old($name, $value);
@endphp

<div class="field @if ($hasError) has-error @endif">
    @if ($label)
        <label for="{{ $name }}">{{ $label }}@if ($required) <span aria-hidden="true">*</span>@endif</label>
    @endif

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes }}
    >
        @if ($placeholder)
            <option value="" @selected(!$resolvedValue)>{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $resolvedValue === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if ($hasError)
        <span class="field-error" id="{{ $name }}-error">
            <x-ui.icon name="error" :size="12" />
            {{ $errors->first($name) }}
        </span>
    @elseif ($help)
        <span class="field-help">{{ $help }}</span>
    @endif
</div>
