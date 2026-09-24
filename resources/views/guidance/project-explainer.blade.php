{{-- "About this project", shown before a learner applies. The summary is
     AI-written when a provider is available (cached per project version). --}}
<x-forms.form-section title="About this project">
    <div class="explainer">
        <div class="explainer-summary">
            <span class="guide-avatar" aria-hidden="true"><x-ui.icon name="auto_awesome" :size="18" /></span>
            <div>
                <div class="guide-name" style="margin-bottom:4px;">Tiroco explains</div>
                <p>{{ $explainer->summary }}</p>
            </div>
        </div>

        <div class="explainer-facts">
            <div class="explainer-fact"><span>Sprints</span><strong>{{ count($explainer->scenarios) }}</strong></div>
            <div class="explainer-fact"><span>Main tasks</span><strong>{{ $explainer->coreTaskCount }}</strong></div>
            <div class="explainer-fact"><span>Time needed</span><strong>about {{ $explainer->estimatedHours }} {{ \Illuminate\Support\Str::plural('hour', $explainer->estimatedHours) }}</strong></div>
            @if (!empty($explainer->stack))
                <div class="explainer-fact"><span>Tech</span><strong style="font-size:var(--text-sm);">{{ implode(', ', $explainer->stack) }}</strong></div>
            @endif
        </div>

        @if (!empty($explainer->scenarios))
            <div>
                <h3 style="font-size:var(--text-base);margin:0 0 var(--sp-sm);">What you'll do</h3>
                <ol class="explainer-sprints">
                    @foreach ($explainer->scenarios as $scenario)
                        <li>
                            <strong>{{ $scenario['title'] }}</strong>
                            <p>{{ $scenario['blurb'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        @if (!empty($explainer->skills))
            <div>
                <h3 style="font-size:var(--text-base);margin:0 0 var(--sp-sm);">Skills you'll practise</h3>
                <div class="explainer-chips">
                    @foreach ($explainer->skills as $skill)
                        <x-ui.badge tone="neutral">{{ $skill }}</x-ui.badge>
                    @endforeach
                </div>
            </div>
        @endif

        @if (!empty($explainer->roles))
            <div>
                <h3 style="font-size:var(--text-base);margin:0 0 var(--sp-sm);">Which role fits you?</h3>
                <ul style="margin:0;padding-left:1.2em;display:flex;flex-direction:column;gap:4px;font-size:var(--text-sm);">
                    @foreach ($explainer->roles as $role)
                        <li><strong>{{ $role['title'] }}</strong>@if (!empty($role['focus'])) — weighted most on {{ implode(' and ', $role['focus']) }}@endif</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p style="margin:0;font-size:var(--text-sm);color:var(--text-muted);">
            After you enrol you'll get a short induction, then plan your first sprint. Tasks adapt as you go: strong work earns more complex, less guided tasks, and a missed detail comes back as a follow-up task to fix.
        </p>
    </div>
</x-forms.form-section>
