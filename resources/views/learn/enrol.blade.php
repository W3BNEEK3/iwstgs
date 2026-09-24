@extends('layouts.shells.auth')

@section('title', 'Enrol as a Learner — ' . config('app.name', 'IWSTGS'))

@section('body')
<div style="text-align:left;margin-bottom:var(--sp-2xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">Enrol as a Learner</h1>
    <p style="color:var(--text-muted);font-size:var(--text-sm);margin:0;">Tell us about your experience so we can calibrate your starting level.</p>
</div>

<form method="POST" action="{{ route('learn.enrol') }}">
    @csrf

    <x-forms.select
        name="entry_category"
        label="Experience Level"
        placeholder="Select..."
        required
        :options="['inexperienced' => 'Inexperienced (less than 1 year)', 'experienced' => 'Experienced (1 or more years)']"
        onchange="document.getElementById('years-field').style.display = this.value === 'experienced' ? '' : 'none'"
    />

    <div id="years-field" style="display:{{ old('entry_category') === 'experienced' ? 'block' : 'none' }};margin-top:var(--sp-lg);">
        <x-forms.text-input
            name="years_experience"
            label="Years of Experience"
            type="number"
            min="1"
            max="50"
        />
    </div>

    <x-ui.button type="submit" severity="primary" style="width:100%;justify-content:center;margin-top:var(--sp-xl);">Continue</x-ui.button>
</form>
@endsection
