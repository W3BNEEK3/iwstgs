@extends('layouts.app')
@section('title', 'Enrol as a Learner')
@section('content')
<div style="max-width:480px;margin:80px auto;padding:0 1rem;">
    <h1>Enrol as a Learner</h1>
    <p>Tell us about your experience so we can calibrate your starting level.</p>

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('learn.enrol') }}">
        @csrf
        <div>
            <label>Experience Level</label>
            <select name="entry_category" id="entry_category" required>
                <option value="">Select...</option>
                <option value="inexperienced" {{ old('entry_category') === 'inexperienced' ? 'selected' : '' }}>
                    Inexperienced (less than 1 year)
                </option>
                <option value="experienced" {{ old('entry_category') === 'experienced' ? 'selected' : '' }}>
                    Experienced (1 or more years)
                </option>
            </select>
        </div>

        @if (old('entry_category') === 'experienced')
            <div id="years-field">
        @else
            <div id="years-field" style="display:none">
        @endif
            <label for="years_experience">Years of Experience</label>
            <input type="number" id="years_experience" name="years_experience"
                   value="{{ old('years_experience') }}" min="1" max="50">
        </div>

        <button type="submit">Continue to Assessment</button>
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('entry_category').addEventListener('change', function () {
        document.getElementById('years-field').style.display =
            this.value === 'experienced' ? '' : 'none';
    });
</script>
@endpush
@endsection
