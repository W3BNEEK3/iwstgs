{{-- <x-forms.textarea name="business_context" label="Business Context" :value="$project->businessContext()" required /> --}}
@props(['name', 'label' => null, 'help' => null, 'required' => false, 'value' => null, 'rows' => 4])

@php
    $hasError = $errors->has($name);
    $resolvedValue = old($name, $value);
@endphp

<div class="field @if ($hasError) has-error @endif">
    @if ($label)
        <label for="{{ $name }}">{{ $label }}@if ($required) <span aria-hidden="true">*</span>@endif</label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        {{ $attributes }}
    >{{ $resolvedValue }}</textarea>

    @if ($hasError)
        <span class="field-error" id="{{ $name }}-error">
            <x-ui.icon name="error" :size="12" />
            {{ $errors->first($name) }}
        </span>
    @elseif ($help)
        <span class="field-help">{{ $help }}</span>
    @endif
</div>
