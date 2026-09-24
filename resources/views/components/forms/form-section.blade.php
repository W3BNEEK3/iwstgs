{{-- <x-forms.form-section title="Enrolment"> ...fields... </x-forms.form-section>
     Large forms group into labeled sections instead of separate cards (design doc §11). --}}
@props(['title'])

<div class="form-section">
    <div class="form-section-title">{{ $title }}</div>
    {{ $slot }}
</div>
