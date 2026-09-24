@extends('layouts.shells.guest')

@section('title', config('app.name', 'IWSTGS') . ' — Intelligent Work Simulation Training & Grading System')

@section('guest-nav')
    <x-ui.button severity="secondary" :href="route('login')">Log in</x-ui.button>
    <x-ui.button severity="primary" :href="route('register')">Get started</x-ui.button>
@endsection

@php
    // Hexagon geometry — reused for competency radar visuals
    $bpHex = function (float $cx, float $cy, float $r, array $fractions = []) {
        $count = 6;
        $step = 360 / $count;
        $outer = [];
        $plotted = [];
        for ($i = 0; $i < $count; $i++) {
            $angle = deg2rad(-90 + $i * $step);
            $outer[] = ['x' => round($cx + $r * cos($angle), 1), 'y' => round($cy + $r * sin($angle), 1)];
            $frac = $fractions[$i] ?? 1.0;
            $plotted[] = ['x' => round($cx + $r * $frac * cos($angle), 1), 'y' => round($cy + $r * $frac * sin($angle), 1)];
        }
        return ['outer' => $outer, 'plotted' => $plotted];
    };
    $toPoints = fn ($pts) => implode(' ', array_map(fn ($p) => "{$p['x']},{$p['y']}", $pts));

    // Illustrative profile — not a real learner's scores
    $sample = [0.55, 0.85, 0.95, 0.6, 0.75, 0.5];

    $dimensionLabels = [
        'Problem Analysis', 'Design & Arch.', 'Implementation',
        'Testing & QA', 'Debugging', 'Comms & Docs',
    ];

    $bigHex   = $bpHex(130, 130, 96, $sample);
    $bigMid   = $bpHex(130, 130, 64);
    $bigInner = $bpHex(130, 130, 32);

    $techLogos = [
        ['icon' => 'terminal',          'label' => 'Python'],
        ['icon' => 'fork_right',        'label' => 'Git'],
        ['icon' => 'inventory_2',       'label' => 'Docker'],
        ['icon' => 'cloud',             'label' => 'AWS'],
        ['icon' => 'database',          'label' => 'PostgreSQL'],
        ['icon' => 'code',              'label' => 'TypeScript'],
        ['icon' => 'api',               'label' => 'REST API'],
        ['icon' => 'webhook',           'label' => 'CI / CD'],
        ['icon' => 'terminal',          'label' => 'Python'],
        ['icon' => 'fork_right',        'label' => 'Git'],
        ['icon' => 'inventory_2',       'label' => 'Docker'],
        ['icon' => 'cloud',             'label' => 'AWS'],
        ['icon' => 'database',          'label' => 'PostgreSQL'],
        ['icon' => 'code',              'label' => 'TypeScript'],
        ['icon' => 'api',               'label' => 'REST API'],
        ['icon' => 'webhook',           'label' => 'CI / CD'],
    ];

    $scenarios = [
        [
            'img'   => '/images/landing/scenario-bootcamp.jpg',
            'tag'   => 'Bootcamp Graduates',
            'title' => 'From training to real work',
            'desc'  => 'Bridge the gap between course projects and professional expectations.',
        ],
        [
            'img'   => '/images/landing/scenario-career-switch.jpg',
            'tag'   => 'Career Switchers',
            'title' => 'Build a verifiable track record',
            'desc'  => 'Prove your skills through graded simulation — no prior industry experience needed.',
        ],
        [
            'img'   => '/images/landing/scenario-code-review.jpg',
            'tag'   => 'Working Developers',
            'title' => 'Sharpen under senior review',
            'desc'  => 'Practice the code-review and sprint rituals that matter most on engineering teams.',
        ],
        [
            'img'   => '/images/landing/scenario-deployment.jpg',
            'tag'   => 'DevOps & Platform',
            'title' => 'Ship with confidence',
            'desc'  => 'Simulated CI/CD pressure, incident triage, and deployment decisions — evaluated end-to-end.',
        ],
    ];

    $darkFeatures = [
        [
            'icon' => 'psychology',
            'title' => 'AI-Mediated Grading',
            'body'  => 'Every submission is evaluated across six dimensions — the same axes a senior engineer uses when reviewing a pull request.',
        ],
        [
            'icon' => 'analytics',
            'title' => 'Six-Dimension Competency Model',
            'body'  => 'Problem analysis, design, implementation, testing, debugging, and communication — tracked in one radar chart.',
        ],
        [
            'icon' => 'timer',
            'title' => 'Real Sprint Cadence',
            'body'  => 'Time-boxed sprints with backlog planning, daily standups in text, and a review gate before submission counts.',
        ],
        [
            'icon' => 'verified',
            'title' => 'Rank That Means Something',
            'body'  => 'Your rank is derived only from graded simulation output — not self-assessments or multiple-choice tests.',
        ],
        [
            'icon' => 'description',
            'title' => 'Portfolio-Grade Artifacts',
            'body'  => 'Every sprint produces an Artifact Vault export — real evidence of work, shareable with hiring managers.',
        ],
    ];
@endphp

@section('body')
<div class="landing">

    {{-- ───────────────────────────────────────────────────────────────────────
         SECTION 1 — HERO
         ─────────────────────────────────────────────────────────────────────── --}}
    <section class="landing-hero" id="hero">
        <div class="l-inner">
            {{-- Left: text --}}
            <div class="landing-hero-text">
                <div class="landing-hero-badge" aria-label="Platform tagline">
                    <span class="landing-hero-badge-dot" aria-hidden="true"></span>
                    Work Simulation &amp; Grading Platform
                </div>

                <h1 class="landing-hero-headline">
                    Train like<br>
                    you're <span class="landing-hero-pill">already</span><br>
                    on the job.
                </h1>

                <span class="landing-hero-separator" aria-hidden="true"></span>

                <p class="landing-hero-sub">
                    Realistic sprints, real code review, evaluated the way a senior engineer would —
                    across six competency dimensions, not one grade.
                </p>

                <div class="landing-hero-actions">
                    <x-ui.button severity="primary" :href="route('register')" id="hero-cta-register">
                        Create free account
                    </x-ui.button>
                    <x-ui.button severity="secondary" :href="route('login')" id="hero-cta-login">
                        Sign in
                    </x-ui.button>
                </div>
            </div>

            {{-- Right: mock sprint board UI --}}
            <div class="landing-hero-visual" aria-hidden="true">
                <div class="landing-hero-mock">
                    <div class="landing-hero-mock-header">
                        <div class="landing-hero-mock-dot landing-hero-mock-dot--close" title="Close"></div>
                        <div class="landing-hero-mock-dot landing-hero-mock-dot--min" title="Minimise"></div>
                        <div class="landing-hero-mock-dot landing-hero-mock-dot--max" title="Expand"></div>
                        <span class="landing-hero-mock-title">MEDQUEUE — SPRINT 3</span>
                        <span class="landing-hero-mock-tag">IN PROGRESS</span>
                    </div>

                    <div class="mock-board">
                        <div>
                            <div class="mock-col-label">Backlog</div>
                            <div class="mock-card">Implement rate limiter on /api/v1/messages</div>
                            <div class="mock-card">Write unit tests for UserService</div>
                        </div>
                        <div>
                            <div class="mock-col-label">In Progress</div>
                            <div class="mock-card mock-card--active">Refactor auth middleware — token refresh edge case</div>
                            <div class="mock-card">DB migration: add index on messages.sender_id</div>
                        </div>
                        <div>
                            <div class="mock-col-label">Review</div>
                            <div class="mock-card mock-card--review">PR #14 — queue consumer retry logic</div>
                        </div>
                    </div>

                    <div class="mock-radar-row">
                        <span class="mock-radar-label">Competency progress</span>
                        <div class="mock-radar-bars">
                            @foreach ([
                                ['Implementation', '85', 'var(--info)'],
                                ['Testing & QA',   '60', 'var(--success)'],
                                ['Debugging',      '75', 'var(--warning)'],
                                ['Design & Arch.', '55', 'var(--suggestion)'],
                            ] as [$dim, $pct, $color])
                                <div class="mock-radar-bar-track" title="{{ $dim }}: {{ $pct }}%">
                                    <div class="mock-radar-bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ───────────────────────────────────────────────────────────────────────
         SECTION 2 — TECH STACK LOGOS STRIP
         ─────────────────────────────────────────────────────────────────────── --}}
    <section class="landing-logos-strip" aria-label="Technologies covered in simulations">
        <div class="landing-logos-track" aria-hidden="true">
            @foreach ($techLogos as $logo)
                <div class="landing-logos-item">
                    <span class="material-symbols-outlined" aria-hidden="true">{{ $logo['icon'] }}</span>
                    {{ $logo['label'] }}
                </div>
            @endforeach
        </div>
    </section>

    {{-- ───────────────────────────────────────────────────────────────────────
         SECTION 3 — SCENARIO CARDS "Built for every stage"
         ─────────────────────────────────────────────────────────────────────── --}}
    <section class="landing-scenarios" id="scenarios">
        <div class="l-inner">
            <div class="landing-scenarios-head">
                <span class="landing-eyebrow">Who it's for</span>
                <h2>Built for every stage<br>of your career.</h2>
            </div>

            <div class="landing-scenarios-grid">
                @foreach ($scenarios as $scenario)
                    <article class="landing-scenario-card">
                        <img
                            src="{{ $scenario['img'] }}"
                            alt="{{ $scenario['title'] }} — {{ $scenario['tag'] }}"
                            loading="lazy"
                        >
                        <div class="landing-scenario-card-overlay">
                            <span class="landing-scenario-card-tag">{{ $scenario['tag'] }}</span>
                            <h3>{{ $scenario['title'] }}</h3>
                            <p>{{ $scenario['desc'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───────────────────────────────────────────────────────────────────────
         SECTION 4 — ALTERNATING FEATURE ROWS
         ─────────────────────────────────────────────────────────────────────── --}}
    <section class="landing-features" id="features">

        {{-- Feature row 1 — AI Grading --}}
        <div class="l-inner">
            <div class="landing-feature-row">
                <div class="landing-feature-text">
                    <div class="landing-feature-stat">6<sup>×</sup></div>
                    <h2 class="landing-feature-h">Graded across six competency dimensions simultaneously.</h2>
                    <p class="landing-feature-body">
                        Most hiring tools score on one axis — pass/fail, or a single numeric rating.
                        IWSTGS evaluates every submission across Problem Analysis, Design, Implementation,
                        Testing, Debugging, and Communication. You get a radar, not a number.
                    </p>
                    <a href="{{ route('register') }}" class="landing-feature-link" id="feat1-cta">
                        Start your first sprint
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                    </a>
                </div>

                <div class="landing-feature-visual">
                    <div class="feature-ui-panel" aria-hidden="true">
                        <div class="feature-ui-panel-title">Submission evaluation — PR #14</div>

                        @php
                            $dims = [
                                ['Problem Analysis',    88],
                                ['Design & Arch.',      72],
                                ['Implementation',      91],
                                ['Testing & QA',        65],
                                ['Debugging',           79],
                                ['Comms & Docs',        58],
                            ];
                        @endphp
                        @foreach ($dims as [$label, $score])
                            <div class="feature-dim-row">
                                <span class="feature-dim-label">{{ $label }}</span>
                                <div class="feature-dim-track">
                                    <div class="feature-dim-fill" style="width:{{ $score }}%;"></div>
                                </div>
                                <span class="feature-dim-score">{{ $score }}</span>
                            </div>
                        @endforeach

                        {{-- Mini radar SVG --}}
                        <div style="padding-top:var(--sp-md);border-top:1px solid var(--border);display:flex;justify-content:center;">
                            <svg viewBox="0 0 260 260" width="160" height="160" role="img" aria-label="Competency radar diagram">
                                <polygon points="{{ $toPoints($bigHex['outer']) }}" class="bp-line" />
                                <polygon points="{{ $toPoints($bigMid['outer']) }}" class="bp-line-soft" />
                                <polygon points="{{ $toPoints($bigInner['outer']) }}" class="bp-line-soft" />
                                @foreach ($bigHex['outer'] as $p)
                                    <line x1="130" y1="130" x2="{{ $p['x'] }}" y2="{{ $p['y'] }}" class="bp-line-soft" />
                                @endforeach
                                <polygon points="{{ $toPoints($bigHex['plotted']) }}" class="bp-dash" style="fill:var(--bg-subtle);" />
                                @foreach ($bigHex['plotted'] as $p)
                                    <rect x="{{ $p['x'] - 2.5 }}" y="{{ $p['y'] - 2.5 }}" width="5" height="5" class="bp-dot" />
                                @endforeach
                                @foreach ($bigHex['outer'] as $i => $p)
                                    <text x="{{ $p['x'] }}" y="{{ $p['y'] }}" class="bp-label" text-anchor="middle" dominant-baseline="middle">D{{ $i + 1 }}</text>
                                @endforeach
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Feature row 2 — Sprint cadence (reversed) --}}
        <div class="l-inner">
            <div class="landing-feature-row landing-feature-row--reverse">
                <div class="landing-feature-text">
                    <div class="landing-feature-stat">2<sup>wk</sup></div>
                    <h2 class="landing-feature-h">Real sprint cadence. Real pressure. Real feedback.</h2>
                    <p class="landing-feature-body">
                        Two-week simulated sprints with a virtual backlog, planning poker, and a
                        code-review gate before your submission is scored. If you've never shipped
                        inside a Scrum team, this is how you learn what that actually feels like.
                    </p>
                    <a href="{{ route('register') }}" class="landing-feature-link" id="feat2-cta">
                        View an example sprint
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                    </a>
                </div>

                <div class="landing-feature-visual">
                    <div class="feature-ui-panel" aria-hidden="true">
                        <div class="feature-ui-panel-title">Sprint 3 — Week 1 of 2</div>
                        <div class="feature-sprint-grid">
                            @php
                                $sprintDays = [
                                    ['Mon', [['Plan', true], ['Stand', false]]],
                                    ['Tue', [['Code', true], ['Code', true]]],
                                    ['Wed', [['Code', true], ['Review', false]]],
                                    ['Thu', [['PR', false], ['QA', false]]],
                                    ['Fri', [['Retro', false], ['', false]]],
                                ];
                            @endphp
                            @foreach ($sprintDays as [$day, $blocks])
                                <div class="feature-sprint-day">
                                    <span class="feature-sprint-day-label">{{ $day }}</span>
                                    <div class="feature-sprint-day-blocks">
                                        @foreach ($blocks as [$label, $active])
                                            @if ($label)
                                                <div class="feature-sprint-block{{ $active ? ' feature-sprint-block--active' : '' }}">{{ $label }}</div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div style="margin-top:var(--sp-md);padding-top:var(--sp-md);border-top:1px solid var(--border);display:flex;gap:var(--sp-md);align-items:center;">
                            <span style="font-family:var(--font-mono);font-size:var(--text-xs);color:var(--text-muted);">VELOCITY</span>
                            <div style="flex:1;height:4px;background:var(--border);border-radius:2px;overflow:hidden;">
                                <div style="width:62%;height:100%;background:var(--text-strong);border-radius:2px;"></div>
                            </div>
                            <span style="font-family:var(--font-mono);font-size:var(--text-xs);color:var(--text);">62%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Feature row 3 — No portfolio needed --}}
        <div class="l-inner">
            <div class="landing-feature-row">
                <div class="landing-feature-text">
                    <div class="landing-feature-stat">0<sup>req</sup></div>
                    <h2 class="landing-feature-h">No prior portfolio. No industry experience required.</h2>
                    <p class="landing-feature-body">
                        A short diagnostic sprint sets your starting rank before your first scored submission.
                        The system meets you where you are — whether you just finished a bootcamp or
                        have two years of self-taught projects behind you.
                    </p>
                    <a href="{{ route('register') }}" class="landing-feature-link" id="feat3-cta">
                        Take the diagnostic
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                    </a>
                </div>

                <div class="landing-feature-visual">
                    <div class="feature-ui-panel" aria-hidden="true">
                        <div class="feature-ui-panel-title">Onboarding diagnostic — step 1 of 3</div>

                        <div style="display:flex;flex-direction:column;gap:var(--sp-sm);">
                            @foreach ([
                                ['Check background knowledge', true],
                                ['Short coding task (45 min)', true],
                                ['Assign starting rank', false],
                            ] as [$step, $done])
                                <div style="display:flex;align-items:center;gap:var(--sp-md);padding:var(--sp-md);border:1px solid var(--border);border-radius:var(--radius-sm);background:var(--bg);">
                                    <span class="material-symbols-outlined" style="font-size:18px;color:{{ $done ? 'var(--text-strong)' : 'var(--border-strong)' }};" aria-hidden="true">{{ $done ? 'check_circle' : 'radio_button_unchecked' }}</span>
                                    <span style="font-size:var(--text-sm);color:{{ $done ? 'var(--text)' : 'var(--text-muted)' }};">{{ $step }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div style="margin-top:var(--sp-md);padding:var(--sp-lg);background:var(--bg-subtle);border-radius:var(--radius-sm);border:1px solid var(--border);">
                            <div style="font-family:var(--font-mono);font-size:9px;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);margin-bottom:var(--sp-sm);">Starting rank assigned</div>
                            <div style="display:flex;align-items:baseline;gap:var(--sp-sm);">
                                <span style="font-size:var(--text-2xl);font-weight:700;color:var(--text-strong);">L2</span>
                                <span style="font-size:var(--text-sm);color:var(--text-muted);">Junior Practitioner</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>

    {{-- ───────────────────────────────────────────────────────────────────────
         SECTION 5 — DARK "AND THAT'S NOT ALL" — Apple bento grid
         ─────────────────────────────────────────────────────────────────────── --}}
    <section class="landing-dark-features" id="more-features">
        <div class="l-inner">
            <div class="bento-head">
                <span class="landing-eyebrow landing-eyebrow--light">There's more</span>
                <h2>And that's not all.</h2>
            </div>

            <div class="bento-grid">

                {{-- Card A (tall left) — The CAC Model --}}
                <div class="bento-card bento-card--tall">
                    <div class="bento-card-top">
                        <span class="bento-label">Dynamic Calibration</span>
                        <h3>Complexity.<br>Autonomy.<br>Context.</h3>
                    </div>
                    <div class="bento-cac-visual" aria-hidden="true" style="display:flex;flex-direction:column;gap:1.5rem;margin-top:2rem;flex:1;justify-content:center;">
                        @foreach ([['Complexity', '75%'], ['Autonomy', '40%'], ['Context Fidelity', '90%']] as [$label, $val])
                            <div>
                                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-family:var(--font-mono);font-size:11px;color:var(--neutral-400);">
                                    <span>{{ $label }}</span>
                                    <span style="color:var(--neutral-0);">{{ $val }}</span>
                                </div>
                                <div style="height:6px;background:var(--neutral-800);border-radius:3px;overflow:hidden;">
                                    <div style="height:100%;width:{{ $val }};background:var(--info);border-radius:3px;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="bento-stat-sub" style="margin-top:2rem;">The engine scales task difficulty across three dimensions based on your real-time performance.</p>
                </div>

                {{-- Card B (centre top) — Objective Rubrics / Answer Families --}}
                <div class="bento-card bento-card--stat">
                    <span class="bento-label">Objective Rubrics</span>
                    <h3 style="margin-top:var(--sp-md);">Graded on<br>Answer Families.</h3>
                    
                    <div class="bento-rubric-visual" aria-hidden="true" style="margin-top:var(--sp-lg);display:flex;flex-direction:column;gap:6px;">
                        @foreach ([['Expert', 'var(--success)', 1], ['Competent', 'var(--info)', 1], ['Weak', 'var(--neutral-600)', 0.4], ['Failing', 'var(--neutral-700)', 0.4]] as [$tier, $color, $op])
                            <div style="display:flex;align-items:center;gap:12px;opacity:{{ $op }};">
                                <div style="width:12px;height:12px;border-radius:2px;background:{{ $color }};"></div>
                                <span style="font-family:var(--font-mono);font-size:12px;font-weight:600;color:var(--neutral-200);">{{ $tier }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="bento-stat-sub" style="margin-top:auto;padding-top:1rem;">AI evaluation is anchored strictly to defined mastery patterns—not vibes.</p>
                </div>

                {{-- Card C (top right) — Consequence Tasks --}}
                <div class="bento-card bento-card--sprint">
                    <span class="bento-label">Remedial Pathways</span>
                    <h3 style="margin-top:var(--sp-md);">Dynamic<br>Consequence<br>Tasks.</h3>
                    
                    <div class="bento-flow-art" aria-hidden="true" style="margin-top:var(--sp-xl);flex:1;">
                        <svg viewBox="0 0 100 80" width="100%" height="80">
                            {{-- Task Flow Nodes --}}
                            <rect x="10" y="30" width="20" height="20" rx="4" style="fill:var(--neutral-800);stroke:var(--neutral-600);stroke-width:1;" />
                            <line x1="30" y1="40" x2="45" y2="40" style="stroke:var(--neutral-600);stroke-width:2;stroke-dasharray:2 2;" />
                            
                            {{-- Consequence Node (Red/Warning) --}}
                            <rect x="45" y="15" width="20" height="20" rx="4" style="fill:rgba(220,38,38,0.1);stroke:var(--error);stroke-width:1;" />
                            <text x="55" y="27" style="font-family:var(--font-mono);font-size:24px;fill:var(--error);" text-anchor="middle">!</text>
                            
                            <line x1="65" y1="25" x2="75" y2="40" style="stroke:var(--neutral-600);stroke-width:2;stroke-dasharray:2 2;" />
                            
                            <rect x="75" y="30" width="20" height="20" rx="4" style="fill:var(--neutral-800);stroke:var(--neutral-600);stroke-width:1;" />
                        </svg>
                    </div>
                    <p class="bento-stat-sub">Miss a core concept? The system injects a targeted scenario to close the gap.</p>
                </div>

                {{-- Card D (wide bottom) — Concept Tagging --}}
                <div class="bento-card bento-card--wide">
                    <div class="bento-wide-text">
                        <span class="bento-label">Knowledge Mapping</span>
                        <h3>Track true mastery<br>across every PR.</h3>
                        <p class="bento-stat-sub">The system aggregates concept tags across all your submissions to build a comprehensive graph of your engineering skills.</p>
                    </div>
                    <div class="bento-tech-cloud" id="bentoPhysicsCloud" aria-hidden="true" aria-label="Concept tags">
                        <div class="bento-tech-token" style="background:var(--neutral-800);border:1px solid var(--neutral-600);color:var(--neutral-200);">Auth Middleware</div>
                        <div class="bento-tech-token" style="background:rgba(37,99,235,0.1);border:1px solid var(--info);color:var(--info);">SOLID Principles</div>
                        <div class="bento-tech-token" style="background:rgba(22,163,74,0.1);border:1px solid var(--success);color:var(--success);">DB Indexing</div>
                        <div class="bento-tech-token" style="background:var(--neutral-800);border:1px solid var(--neutral-600);color:var(--neutral-200);">Concurrency</div>
                        <div class="bento-tech-token" style="background:rgba(217,119,6,0.1);border:1px solid var(--warning);color:var(--warning);">API Design</div>
                        <div class="bento-tech-token" style="background:var(--neutral-800);border:1px solid var(--neutral-600);color:var(--neutral-200);">Agile / Scrum</div>
                        <div class="bento-tech-token" style="background:rgba(13,148,136,0.1);border:1px solid var(--suggestion);color:var(--suggestion);">Data Structures</div>
                        <div class="bento-tech-token" style="background:var(--neutral-800);border:1px solid var(--neutral-600);color:var(--neutral-200);">System Arch</div>
                        <div class="bento-tech-token" style="background:rgba(67,56,202,0.1);border:1px solid var(--diagnostic);color:var(--diagnostic);">Design Patterns</div>
                        <div class="bento-tech-token" style="background:var(--neutral-800);border:1px solid var(--neutral-600);color:var(--neutral-200);">Code Review</div>
                        <div class="bento-tech-token" style="background:var(--neutral-800);border:1px solid var(--neutral-600);color:var(--neutral-200);">Unit Testing</div>
                        <div class="bento-tech-token" style="background:rgba(220,38,38,0.1);border:1px solid var(--error);color:var(--error);">Security Constraints</div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ───────────────────────────────────────────────────────────────────────
         SECTION 6 — DARK "YOUR RANK REFLECTS YOUR SKILLS"
         ─────────────────────────────────────────────────────────────────────── --}}
    <section class="landing-dark-rank" id="rank">
        <div class="l-inner">
            <div class="dark-rank-text">
                <span class="landing-eyebrow landing-eyebrow--light">The proof of work</span>
                <h2>Your rank<br>reflects your<br>skills.</h2>

                <div class="dark-rank-body">
                    @foreach ([
                        ['bolt',              'No self-reporting',     'Every rank change is derived from graded simulation output — nothing is self-assessed or multiple-choice.'],
                        ['insights',          'Dimension-level detail','Know exactly which of the six dimensions to improve, not just that you "failed."'],
                        ['description',       'Artifact Vault',        'Every sprint produces a real portfolio artifact — shareable evidence of your actual work.'],
                        ['workspace_premium', 'Benchmark peers',       'Your rank is relative to the cohort — you always know where you stand against others at your level.'],
                    ] as [$icon, $title, $body])
                        <div class="dark-rank-point">
                            <div class="dark-rank-point-icon">
                                <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
                            </div>
                            <div class="dark-rank-point-copy">
                                <strong>{{ $title }}</strong>
                                <span>{{ $body }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="dark-rank-visual" aria-hidden="true">
                @foreach ([
                    ['Expert',       90, false],
                    ['Senior',       72, true],
                    ['Mid-level',    55, false],
                    ['Junior',       30, false],
                    ['Practitioner', 18, false],
                ] as [$tier, $pct, $highlight])
                    <div class="dark-rank-card{{ $highlight ? ' dark-rank-card--highlight' : '' }}">
                        <span class="dark-rank-tier">{{ $tier }}</span>
                        <div class="dark-rank-tier-bar-track">
                            <div class="dark-rank-tier-bar-fill" style="width:{{ $pct }}%;"></div>
                        </div>
                        <span class="dark-rank-tier-pct">{{ $pct }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───────────────────────────────────────────────────────────────────────
         SECTION 7 — FOOTER CTA
         ─────────────────────────────────────────────────────────────────────── --}}
    <section class="landing-footer-cta" id="cta">
        <div class="l-inner">
            <div>
                <div class="landing-hero-badge" style="margin-bottom:var(--sp-xl);" aria-hidden="true">
                    <span></span>
                    No portfolio required
                </div>
                <h2 class="landing-footer-cta-headline">
                    Train like<br>you're already<br>on the job.
                </h2>
                <p class="landing-footer-cta-sub">
                    A diagnostic sprint sets your starting rank in under an hour.
                    From there, every submission you make builds the record that proves your ability.
                </p>
                <div class="landing-hero-actions">
                    <x-ui.button severity="primary" :href="route('register')" id="footer-cta-register">
                        Create free account
                    </x-ui.button>
                    <x-ui.button severity="secondary" :href="route('login')" id="footer-cta-login">
                        Log in
                    </x-ui.button>
                </div>
            </div>

            <div class="landing-footer-cta-right">
                @foreach ([
                    ['check', 'Free to join — no credit card'],
                    ['check', 'Diagnostic sprint included on signup'],
                    ['check', 'Real project brief, not toy exercises'],
                    ['check', 'Evaluated by AI, reviewed by the platform'],
                ] as [$icon, $note])
                    <div class="landing-footer-cta-note">
                        <span class="material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
                        {{ $note }}
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ───────────────────────────────────────────────────────────────────────
         SECTION 8 — GIANT BRAND MARK
         ─────────────────────────────────────────────────────────────────────── --}}
    <div class="landing-brand-mark" aria-hidden="true">
        <div class="landing-brand-mark-text">{{ config('app.name', 'IWSTGS') }}</div>
    </div>

</div>
@endsection

@push('scripts')
<script>
  // Subtle scroll-border on the guest topbar
  (function () {
    var bar = document.querySelector('.guest-topbar');
    if (!bar) return;
    var handler = function () {
      bar.classList.toggle('guest-topbar-scrolled', window.scrollY > 12);
    };
    window.addEventListener('scroll', handler, { passive: true });
    handler();
  }());
</script>

{{-- Matter.js Physics Animation --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/matter-js/0.20.0/matter.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const container = document.getElementById("bentoPhysicsCloud");
        if (!container) return;

        const { Engine, Runner, Composite, Bodies, Mouse, MouseConstraint, Events } = Matter;
        const engine = Engine.create();
        const world = engine.world;

        // Get container dimensions
        const width = container.offsetWidth || 300;
        const height = container.offsetHeight || 140;

        // Create boundaries (walls and floor)
        const wallOptions = { isStatic: true, render: { visible: false } };
        const floor = Bodies.rectangle(width / 2, height + 25, width, 50, wallOptions);
        const leftWall = Bodies.rectangle(-25, height / 2, 50, height * 2, wallOptions);
        const rightWall = Bodies.rectangle(width + 25, height / 2, 50, height * 2, wallOptions);
        
        Composite.add(world, [floor, leftWall, rightWall]);

        // Map DOM elements to physics bodies
        const tokens = container.querySelectorAll(".bento-tech-token");
        const bodyMap = [];

        tokens.forEach((el, index) => {
            const w = el.offsetWidth || 100;
            const h = el.offsetHeight || 30;
            
            // Start high above container, staggered
            const startX = 20 + Math.random() * (width - 40);
            const startY = -100 - (Math.random() * 200) - (index * 50);

            const body = Bodies.rectangle(startX, startY, w, h, {
                restitution: 0.4, // Bounciness
                friction: 0.5,
                density: 0.04,
                angle: (Math.random() - 0.5) * 0.5 // Slight random initial rotation
            });

            bodyMap.push({ el, body, width: w, height: h });
            Composite.add(world, body);
            
            // Show the tag now that it has physics coordinates
            el.style.opacity = '1';
        });

        // Add mouse control
        const mouse = Mouse.create(container);
        const mouseConstraint = MouseConstraint.create(engine, {
            mouse: mouse,
            constraint: {
                stiffness: 0.2,
                render: { visible: false }
            }
        });
        Composite.add(world, mouseConstraint);

        // Keep mouse in sync with scrolling
        mouseConstraint.mouse.element.removeEventListener("mousewheel", mouseConstraint.mouse.mousewheel);
        mouseConstraint.mouse.element.removeEventListener("DOMMouseScroll", mouseConstraint.mouse.mousewheel);

        // Sync loop: update DOM elements to match physics bodies
        Events.on(engine, 'afterUpdate', function() {
            bodyMap.forEach(({ el, body, width, height }) => {
                // Apply positions based on center of body
                el.style.transform = `translate(${body.position.x - width / 2}px, ${body.position.y - height / 2}px) rotate(${body.angle}rad)`;
            });
        });

        // Handle resize properly
        window.addEventListener('resize', () => {
            const newW = container.offsetWidth;
            const newH = container.offsetHeight;
            
            Matter.Body.setPosition(floor, { x: newW / 2, y: newH + 25 });
            Matter.Body.setVertices(floor, Matter.Bodies.rectangle(newW / 2, newH + 25, newW, 50).vertices);
            
            Matter.Body.setPosition(leftWall, { x: -25, y: newH / 2 });
            Matter.Body.setVertices(leftWall, Matter.Bodies.rectangle(-25, newH / 2, 50, newH * 2).vertices);
            
            Matter.Body.setPosition(rightWall, { x: newW + 25, y: newH / 2 });
            Matter.Body.setVertices(rightWall, Matter.Bodies.rectangle(newW + 25, newH / 2, 50, newH * 2).vertices);
        });

        // Run the engine
        const runner = Runner.create();
        Runner.run(runner, engine);

        // Trigger a slight upwards burst on click anywhere in container
        container.addEventListener('click', () => {
            bodyMap.forEach(({body}) => {
                Matter.Body.applyForce(body, body.position, {
                    x: (Math.random() - 0.5) * 0.05,
                    y: -0.15 - Math.random() * 0.1
                });
            });
        });
    });
</script>
@endpush
