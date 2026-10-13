@extends('layouts.shells.dashboard')

@section('title', $board->projectTitle . ' — ' . config('app.name', 'Areyna'))

@php
    $percent = $board->milestoneCount() > 0 ? (int) round(100 * $board->passedCount() / $board->milestoneCount()) : 0;
    $openCards = array_values(array_filter($board->cards, fn ($c) => ! $c['attempted']));
    $repo = $board->repository;
@endphp

@section('body')
<div style="margin-bottom:var(--sp-lg);">
    <h1 style="font-size:var(--text-2xl);margin-bottom:var(--sp-xs);">{{ $board->projectTitle }}</h1>
    <div style="display:flex;gap:var(--sp-sm);flex-wrap:wrap;align-items:center;">
        <x-ui.badge tone="info"><x-ui.icon name="code" :size="14" /> {{ $board->variant->name }}</x-ui.badge>
        @if ($board->isComplete())
            <x-ui.badge tone="success"><x-ui.icon name="emoji_events" :size="14" /> Complete</x-ui.badge>
        @endif
    </div>
    <div class="build-progress">
        <div class="build-progress-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}" aria-label="Milestones passed">
            <span style="width:{{ $percent }}%"></span>
        </div>
        <span class="build-progress-label">{{ $board->passedCount() }} of {{ $board->milestoneCount() }} milestones passed</span>
    </div>
</div>

<div class="build-layout">
    <div>
        @if ($board->isComplete())
            <x-content.narrative-panel icon="emoji_events" label="You built it" style="margin-bottom:var(--sp-xl);">
                Every milestone passed. {{ $repo ? 'Your finished app lives in ' . $repo->fullName . ', and it\'s yours to keep building.' : '' }}
            </x-content.narrative-panel>
        @elseif ($board->chapterTitle)
            <x-content.narrative-panel icon="auto_stories" :label="$board->chapterTitle" style="margin-bottom:var(--sp-xl);">
                {{ $board->chapterStory }}
            </x-content.narrative-panel>
        @endif

        @if ($openCards !== [])
            <section style="margin-bottom:var(--sp-xl);" aria-labelledby="fixes-heading">
                <h2 id="fixes-heading" class="chapter-title"><x-ui.icon name="priority_high" :size="18" /> To fix first</h2>
                <div style="display:flex;flex-direction:column;gap:var(--sp-sm);">
                    @foreach ($openCards as $card)
                        <div class="panel fix-card {{ $card['type'] === 'suggestion' ? 'is-suggestion' : '' }}">
                            <div style="display:flex;justify-content:space-between;gap:var(--sp-md);align-items:flex-start;flex-wrap:wrap;">
                                <div>
                                    <strong>{{ $card['title'] }}</strong>
                                    <div style="font-size:var(--text-xs);color:var(--text-muted);">{{ $card['type'] === 'suggestion' ? 'Practice task' : 'Something came back from your users' }}</div>
                                </div>
                                <x-ui.button :href="route('learn.milestone', [$board->sessionId, $card['task_id']])" severity="secondary" icon="build">Open</x-ui.button>
                            </div>
                            @if ($card['ticket'])
                                <p style="margin:var(--sp-sm) 0 0;font-size:var(--text-sm);white-space:pre-line;">{{ $card['ticket'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @foreach ($board->chapters as $i => $chapter)
            @php
                $upcoming = ! $chapter['is_current'] && collect($chapter['milestones'])->every(fn ($m) => $m['status'] === 'locked');
            @endphp
            <section class="chapter {{ $upcoming ? 'is-upcoming' : '' }}" aria-label="Chapter {{ $i + 1 }}">
                <h2 class="chapter-title">
                    <span>Chapter {{ $i + 1 }} · {{ $chapter['title'] }}</span>
                    @if ($chapter['is_current'] && ! $board->isComplete())
                        <x-ui.badge tone="info">Now</x-ui.badge>
                    @endif
                </h2>
                <ol class="milestone-path">
                    @foreach ($chapter['milestones'] as $milestone)
                        @php
                            [$icon, $label] = match ($milestone['status']) {
                                'passed'  => ['check', 'Passed'],
                                'current' => ['play_arrow', 'Current milestone'],
                                default   => ['lock', 'Locked'],
                            };
                        @endphp
                        <li class="milestone is-{{ $milestone['status'] }}">
                            <span class="milestone-icon" aria-hidden="true"><x-ui.icon :name="$icon" :size="18" /></span>
                            <div class="milestone-body">
                                <div class="milestone-title">{{ $milestone['title'] }}</div>
                                <div class="milestone-meta">
                                    {{ $label }}@if ($milestone['attempts'] > 0) · {{ $milestone['attempts'] }} {{ Str::plural('attempt', $milestone['attempts']) }}@endif
                                </div>
                            </div>
                            @if ($milestone['status'] !== 'locked')
                                <x-ui.button :href="route('learn.milestone', [$board->sessionId, $milestone['id']])"
                                    :severity="$milestone['status'] === 'current' ? 'primary' : 'secondary'"
                                    :icon="$milestone['status'] === 'current' ? 'arrow_forward' : 'visibility'">
                                    {{ $milestone['status'] === 'current' ? 'Work on it' : 'View' }}
                                </x-ui.button>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endforeach
    </div>

    <aside class="build-side">
        <section class="panel" aria-labelledby="repo-heading">
            <h2 id="repo-heading" style="font-size:var(--text-md);margin:0 0 var(--sp-md);display:flex;align-items:center;gap:var(--sp-sm);">
                <x-ui.icon name="folder_code" :size="18" /> Your repository
            </h2>

            @if (! $githubOn)
                <p style="margin:0;font-size:var(--text-sm);color:var(--text-muted);">Connecting GitHub isn't switched on yet. Your admin can enable it.</p>
            @elseif ($repo && $repo->isUsable())
                <p style="margin:0 0 var(--sp-sm);font-size:var(--text-sm);">
                    <a href="{{ $repo->htmlUrl() }}" target="_blank" rel="noopener noreferrer"><strong>{{ $repo->fullName }}</strong></a>
                </p>
                <p style="margin:0;font-size:var(--text-xs);color:var(--text-muted);">
                    Push your work, then open the current milestone and submit the commit.
                    @if ($repo->lastAcceptedSha) Last accepted commit: <code>{{ substr($repo->lastAcceptedSha, 0, 7) }}</code>. @endif
                </p>
            @else
                @if ($repo && ! $repo->isUsable())
                    <p style="margin:0 0 var(--sp-md);font-size:var(--text-sm);color:var(--warning-fg);">
                        Areyna lost access to {{ $repo->fullName }}. Give access again (step 3), then check again.
                    </p>
                @endif
                <ol class="repo-steps">
                    <li class="repo-step {{ $board->github ? 'is-done' : '' }}">
                        <div>
                            <strong>Connect GitHub</strong>
                            @if ($board->github)
                                <p>Connected as {{ $board->github->githubLogin }}.</p>
                            @else
                                <p>Areyna needs to know which GitHub account is yours. Free accounts are fine.</p>
                                <x-ui.button :href="route('github.connect', ['return' => request()->getRequestUri()])" severity="primary" icon="link">Connect GitHub</x-ui.button>
                            @endif
                        </div>
                    </li>
                    <li class="repo-step">
                        <div>
                            <strong>Create my repo</strong>
                            <p>GitHub makes <code>{{ $board->suggestedRepoName }}</code> in your account from the starter code. It's public: it becomes part of your portfolio, and the tests run free on public repos. Never commit passwords or keys.</p>
                            <x-ui.button :href="$board->variant->templateUrl($board->suggestedRepoName)" target="_blank" rel="noopener noreferrer" severity="secondary" icon="add">Create my repo</x-ui.button>
                        </div>
                    </li>
                    <li class="repo-step">
                        <div>
                            <strong>Give Areyna access</strong>
                            <p>Install the Areyna app and choose <em>only</em> your new repository. Areyna reads your code and test results; it never touches your other repositories.</p>
                            <x-ui.button :href="route('github.install')" target="_blank" rel="noopener noreferrer" severity="secondary" icon="key">Give Areyna access</x-ui.button>
                        </div>
                    </li>
                    <li class="repo-step">
                        <div>
                            <strong>Check again</strong>
                            <p>Usually it links by itself within seconds. If not:</p>
                            <form method="POST" action="{{ route('github.check') }}">
                                @csrf
                                <x-ui.button type="submit" severity="secondary" icon="refresh">Check again</x-ui.button>
                            </form>
                        </div>
                    </li>
                </ol>
                @if ($board->variant->setupNotes)
                    <p style="margin:var(--sp-md) 0 0;font-size:var(--text-xs);color:var(--text-muted);">{{ $board->variant->setupNotes }}</p>
                @endif
            @endif
        </section>

        <section class="panel" aria-labelledby="feed-heading">
            <h2 id="feed-heading" style="font-size:var(--text-md);margin:0 0 var(--sp-md);display:flex;align-items:center;gap:var(--sp-sm);">
                <x-ui.icon name="forum" :size="18" /> Messages
            </h2>
            @if ($board->feed === [])
                <p style="margin:0;font-size:var(--text-sm);color:var(--text-muted);">Nothing yet. Messages from the people you're building for appear here.</p>
            @else
                <ul class="story-feed">
                    @foreach ($board->feed as $message)
                        <li class="story-message">
                            <strong>{{ $message->sender }}</strong>
                            @if ($message->senderRole) <span style="color:var(--text-muted);">· {{ $message->senderRole }}</span> @endif
                            @if ($message->eventType === 'requirement_change') <x-ui.badge tone="warning">Change of plan</x-ui.badge> @endif
                            @if ($message->eventType === 'incident') <x-ui.badge tone="error">Incident</x-ui.badge> @endif
                            <p>{{ $message->text }}</p>
                            <time datetime="{{ $message->firedAt }}">{{ \Illuminate\Support\Carbon::parse($message->firedAt)->diffForHumans() }}</time>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </aside>
</div>
@endsection
