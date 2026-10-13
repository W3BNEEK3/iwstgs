@extends('layouts.shells.dashboard')

@section('title', $form->taskTitle . ' — ' . config('app.name', 'Areyna'))

@if ($waiting)
    @push('scripts')
        {{-- Tests are still running: look again shortly, but never throw away an explanation being typed. --}}
        <script>
            setInterval(() => {
                const box = document.querySelector('textarea[name=explanation]');
                const typing = box && (box.value.trim() !== '' || document.activeElement === box);
                if (!typing && document.visibilityState === 'visible') window.location.reload();
            }, 20000);
        </script>
    @endpush
@endif

@php
    $statusTone = [
        'passed' => 'success', 'failed' => 'error', 'errored' => 'error', 'not_run' => 'warning', 'pending' => 'info',
    ];
    $statusLabel = [
        'passed' => 'Tests passed', 'failed' => 'Tests failed', 'errored' => 'Test run problem',
        'not_run' => 'Tests didn\'t run', 'pending' => 'Tests running…',
    ];
    $hints = $spec?->hints ?? [];
@endphp

@section('body')
<p style="margin-bottom:var(--sp-lg);">
    <a href="{{ route('learn.build', $projectId) }}" style="display:inline-flex;align-items:center;gap:4px;font-size:var(--text-sm);">
        <x-ui.icon name="arrow_back" :size="16" /> Back to the build
    </a>
</p>

<div style="margin-bottom:var(--sp-xl);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">{{ $form->taskTitle }}</h1>
    <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;">
        @if ($variant) <x-ui.badge tone="info"><x-ui.icon name="code" :size="14" /> {{ $variant->name }}</x-ui.badge> @endif
        @if ($isPassed) <x-ui.badge tone="success"><x-ui.icon name="check" :size="14" /> Passed</x-ui.badge> @endif
        @if ($isLocked) <x-ui.badge tone="neutral"><x-ui.icon name="lock" :size="14" /> Locked</x-ui.badge> @endif
    </div>
</div>

@if ($errors->has('submission'))
    <div class="field has-error" style="margin-bottom:var(--sp-lg);">
        <span class="field-error"><x-ui.icon name="error" :size="12" /> {{ $errors->first('submission') }}</span>
    </div>
@endif

<div class="build-layout">
    <div>
        <p style="white-space:pre-line;margin:0 0 var(--sp-lg);">{{ $form->taskBrief }}</p>

        @if ($spec?->briefAddendum)
            <x-content.narrative-panel icon="code" :label="'In ' . ($variant?->name ?? 'your stack')" style="margin-bottom:var(--sp-lg);">
                {{ $spec->briefAddendum }}
            </x-content.narrative-panel>
        @endif

        @if ($form->scaffoldingHint || ($hints[$form->cacAutonomy] ?? null))
            <div class="panel" style="margin-bottom:var(--sp-lg);background:var(--bg-subtle);">
                @if ($form->scaffoldingHint)
                    <p style="margin:0;font-size:var(--text-sm);"><strong>Guidance:</strong> {{ $form->scaffoldingHint }}</p>
                @endif
                @if ($hints[$form->cacAutonomy] ?? null)
                    <p style="margin:var(--sp-xs) 0 0;font-size:var(--text-sm);"><strong>Hint for your stack:</strong> {{ $hints[$form->cacAutonomy] }}</p>
                @endif
            </div>
        @endif

        @if (! empty($form->referenceMaterials))
            <details class="form-section" style="margin-bottom:var(--sp-lg);">
                <summary style="cursor:pointer;font-weight:600;">Reference materials</summary>
                <div style="display:flex;flex-direction:column;gap:var(--sp-md);margin-top:var(--sp-md);">
                    @foreach ($form->referenceMaterials as $material)
                        <x-reference-material :type="$material['type']" :title="$material['title']" :content="$material['content']" />
                    @endforeach
                </div>
            </details>
        @endif

        @if ($spec && $spec->acceptanceTests !== [])
            <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0 0 var(--sp-xl);">
                Checked by tests {{ implode(', ', $spec->acceptanceTests) }}, plus every test from the milestones you've already passed.
            </p>
        @endif

        @if ($isLocked)
            <div class="panel">Finish the current milestone first. Milestones open one at a time, each building on the last.</div>
        @elseif ($isPassed)
            <div class="panel">This milestone has passed. Carry on with the next one on your build.</div>
        @elseif (! $repository || ! $repository->isUsable())
            <div class="panel">Link your GitHub repository first. The steps are on <a href="{{ route('learn.build', $projectId) }}">your build page</a>.</div>
        @else
            <x-forms.form-section title="Submit this milestone">
                @if ($githubError)
                    <p class="field-error" style="margin-bottom:var(--sp-md);">{{ $githubError }}</p>
                @endif
                <form method="POST" action="{{ route('learn.milestone.store', [$sessionId, $taskId]) }}">
                    @csrf
                    <fieldset style="border:none;padding:0;margin:0;" class="@error('commit_sha') has-error @enderror">
                        <legend style="font-weight:600;font-size:var(--text-sm);margin-bottom:var(--sp-sm);">Which commit completes it?</legend>
                        @if ($commits === [] && $githubError)
                            {{-- The error above already says what happened. --}}
                        @elseif ($commits === [])
                            <p style="font-size:var(--text-sm);color:var(--text-muted);">No commits found on <code>{{ $repository->defaultBranch }}</code> yet. Push your work, then refresh.</p>
                        @else
                            <div class="commit-list">
                                @foreach ($commits as $i => $commit)
                                    <label class="commit-option">
                                        <input type="radio" name="commit_sha" value="{{ $commit->sha }}" @checked(old('commit_sha', $i === 0 ? $commit->sha : null) === $commit->sha) style="margin-top:3px;">
                                        <span>
                                            <span style="display:block;font-size:var(--text-sm);">{{ $commit->headline() }}</span>
                                            <code>{{ $commit->shortSha() }}</code>
                                            @if ($i === 0) <x-ui.badge tone="neutral">latest</x-ui.badge> @endif
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        @error('commit_sha') <span class="field-error">{{ $message }}</span> @enderror
                    </fieldset>

                    <x-forms.textarea name="explanation" label="What you did and why" :rows="6" required
                        help="Say what you changed, the choices you made and why, and how you checked it works. Reviewers judge this as much as the code." />

                    <x-ui.button type="submit" severity="primary" icon="send" style="margin-top:var(--sp-lg);" :disabled="$commits === []">Submit milestone</x-ui.button>
                </form>
            </x-forms.form-section>
        @endif
    </div>

    <aside class="build-side">
        <section class="panel" aria-labelledby="attempts-heading">
            <h2 id="attempts-heading" style="font-size:var(--text-md);margin:0 0 var(--sp-md);">Your attempts</h2>
            @if ($attempts === [])
                <p style="margin:0;font-size:var(--text-sm);color:var(--text-muted);">None yet.</p>
            @endif
            @foreach ($attempts as $attempt)
                <div style="padding:var(--sp-sm) 0;{{ ! $loop->last ? 'border-bottom:1px solid var(--border);' : '' }}">
                    <div style="display:flex;justify-content:space-between;gap:var(--sp-sm);align-items:center;flex-wrap:wrap;">
                        <strong style="font-size:var(--text-sm);">Attempt {{ $attempt->attemptNumber }}</strong>
                        @if ($attempt->ciStatus)
                            <x-ui.badge :tone="$statusTone[$attempt->ciStatus] ?? 'neutral'">{{ $statusLabel[$attempt->ciStatus] ?? $attempt->ciStatus }}</x-ui.badge>
                        @endif
                    </div>
                    @if ($attempt->shortSha())
                        <div style="font-size:var(--text-xs);color:var(--text-muted);">Commit <code>{{ $attempt->shortSha() }}</code></div>
                    @endif
                    @if ($attempt->ciSummary)
                        <p style="margin:var(--sp-xs) 0 0;font-size:var(--text-sm);">{{ $attempt->ciSummary }}</p>
                    @endif
                    @if ($attempt->tests)
                        <ul class="test-list">
                            @foreach ($attempt->tests as $test)
                                <li>
                                    <x-ui.icon :name="$test['status'] === 'passed' ? 'check_circle' : 'cancel'" :size="16" :aria-label="$test['status'] === 'passed' ? 'Passed' : 'Failed'" />
                                    <span>
                                        <strong>{{ $test['id'] }}</strong> {{ $test['title'] }}
                                        @if ($test['regression']) <x-ui.badge tone="warning">worked before</x-ui.badge> @endif
                                        @if ($test['status'] !== 'passed' && $test['message'])
                                            <span class="test-message">{{ $test['message'] }}</span>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <div style="display:flex;gap:var(--sp-md);margin-top:var(--sp-xs);font-size:var(--text-sm);flex-wrap:wrap;">
                        @if ($attempt->ciRunUrl)
                            <a href="{{ $attempt->ciRunUrl }}" target="_blank" rel="noopener noreferrer">Test run on GitHub</a>
                        @endif
                        @if ($attempt->passed !== null)
                            <a href="{{ route('learn.evaluation-result', $attempt->submissionId) }}">Review and feedback</a>
                        @elseif ($attempt->isWaitingForTests())
                            <span style="color:var(--text-muted);">Waiting for the test run. This page checks again every 20 seconds.</span>
                        @endif
                    </div>
                </div>
            @endforeach
            @if ($waiting)
                <form method="POST" action="{{ route('learn.milestone.refresh', [$sessionId, $taskId]) }}" style="margin-top:var(--sp-md);">
                    @csrf
                    <x-ui.button type="submit" severity="secondary" icon="refresh">Check now</x-ui.button>
                </form>
            @endif
        </section>
    </aside>
</div>
@endsection
