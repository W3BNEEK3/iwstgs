# Areyna v2 — 01: Platform Foundation

> **Goal:** the platform changes every v2 project depends on — tracks and stack variants, the GitHub App, milestone submissions from real commits and pull requests, acceptance-test results from CI, evaluation of real code, scripted events, and teammate PRs. **No project content here** (that's docs 02–04).
>
> Principle: **extend the engine, don't replace it.** Projects → scenarios → tasks, sprints, backlog, rubrics, dimensions, AI evaluation, `PostEvaluationRouter`, consequence/suggestion injection, CAC and ranks all stay. v2 adds a *source of truth for code* (the learner's repo) and *new signals* (CI results, diffs, PR reviews) that feed the same pipeline.

---

## 1. Concepts → Schema

| Concept | Where it lives | New? |
|---|---|---|
| Track (`build` / `work_experience` / `classic`) | `project_templates.track` | **new column** |
| Stack variant | `project_stack_variants` | **new table** |
| Learner's chosen variant | `learner_sessions.stack_variant_id` | **new column** |
| Milestone | `tasks` (a task with `task_type = 'milestone'`) | **new enum value** |
| Per-variant milestone details (tests, hints) | `task_variant_specs` | **new table** |
| Scripted event (requirement change, incident, teammate PR, review request, stakeholder message) | `scenario_events` + `learner_scenario_events` | **new tables** |
| GitHub account link | `github_connections` | **new table** |
| Learner's repo for a session | `learner_repositories` | **new table** |
| Commit / PR submission + CI result | `submission_packages` (+ columns) | **extended** |
| Role on a WE project | `role_definitions` + `role_enrolments` (existing) + `tasks.role_tags` (existing) | reuse |

### 1.1 `project_templates` — add `track`

```php
$table->enum('track', ['classic', 'build', 'work_experience'])->default('classic')->after('project_type');
```

- Existing 5 projects stay `classic` (current paste-in flow) until converted.
- The catalogue groups by track: **Build**, **Work Experience**, and (while they exist) **Classic challenges**.

### 1.2 `project_stack_variants` — new

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `project_id` | uuid FK → project_templates, cascade | |
| `key` | string(50) | e.g. `express-ejs`, `express-react`. Unique per project |
| `name` | string(100) | "Express + EJS" |
| `languages` | json | `["JavaScript"]` — shown on the catalogue card |
| `difficulty` | tinyint unsigned | 1 = easiest variant of this project. Drives ordering and explainer copy |
| `min_rank_tier` / `min_rank_level` | string(20) / smallint, nullable | Rank gate (e.g. React variant needs Junior-2) |
| `template_repo` | string(200) | `areyna-templates/taskly-express-ejs` — starter the learner's repo is generated from |
| `reference_repo` | string(200) | Private reference build, tagged per milestone (`m1`…`m7`) — source of teammate PRs and our own test verification |
| `acceptance_ref` | string(100) | Pinned version of the acceptance suite, e.g. `taskly-acceptance@v1.0.0` |
| `setup_notes` | text, nullable | "Requires Node 20+" etc. |
| `is_published` | bool | Variants can ship independently |

### 1.3 `learner_sessions` — add `stack_variant_id`

Nullable FK → `project_stack_variants`. Null for classic sessions and the diagnostic. Chosen at enrolment (Build) or at role selection (WE). **Not changeable mid-project** (switching stacks = new session).

### 1.4 `tasks` — milestones

Add `milestone` to the `task_type` enum. A milestone is a core task with extra meaning:

- `sequence_order` within the scenario is its order in the build;
- it is **cumulative**: passing milestone *n* requires the acceptance tests of milestones 1…*n* (regressions fail);
- `role_tags` still decide who does it on a WE project (see §7).

Existing `core`, `consequence`, `suggestion` types remain and are used alongside milestones:
- **consequence** — a "fix it" milestone on the same repo (e.g. a regression, an incident);
- **suggestion** — a focused practice task (can be a small code change or written);
- new **`review`** type (enum value) — "review this teammate PR" (see §7.3).

### 1.5 `task_variant_specs` — new

Most milestone content is stack-neutral (the story, the requirement, the rubric). What varies per stack lives here.

| Column | Type | Notes |
|---|---|---|
| `task_id` | uuid FK → tasks, cascade | |
| `stack_variant_id` | uuid FK → project_stack_variants, cascade | unique(task_id, stack_variant_id) |
| `brief_addendum` | text, nullable | Stack-specific guidance appended to the brief ("In EJS, render the list with a `forEach` in `views/index.ejs`") |
| `acceptance_tests` | json | Test IDs this milestone introduces, e.g. `["T2.1","T2.2"]` (cumulative set is computed) |
| `hints` | json, nullable | Extra per-variant hints by autonomy level |
| `reference_tag` | string(50) | Tag in the reference repo that completes this milestone (`m2`) |

### 1.6 Scripted events — `scenario_events` + `learner_scenario_events`

`scenario_events` (authored content):

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `scenario_id` | uuid FK | |
| `trigger` | enum(`scenario_start`, `after_task`, `sprint_day`) | When it fires |
| `trigger_task_id` | uuid nullable | For `after_task` (fires when that task is *passed*) |
| `trigger_day` | smallint nullable | For `sprint_day` (simulated day N of the sprint) |
| `event_type` | enum(`stakeholder_message`, `requirement_change`, `incident`, `teammate_pr`, `review_request`) | |
| `role_tags` | json nullable | Only for learners in these roles (WE); null = everyone |
| `payload` | json | Message (sender, channel, text), `inject_task_id` (a task added to the backlog/board), `pr` details (§7) |
| `display_order` | smallint | |

`learner_scenario_events` (runtime, idempotency): `learner_session_id`, `scenario_event_id`, `fired_at`, `result` json (e.g. PR number). Unique(session, event) so an event never fires twice.

A new `ScenarioEventDispatcher` (SimExecution) is called at scenario start, after each pass, and on each simulated sprint day; it fires due events, writes a message to the board's feed, injects tasks through the existing `InjectAdaptiveTaskCommand` (card type `scripted`, new visual treatment), and asks the GitHub module to open teammate PRs.

### 1.7 `github_connections` — new

| Column | Type | Notes |
|---|---|---|
| `user_id` | uuid PK/FK → users | One GitHub account per Areyna user |
| `github_user_id` | bigint unique | Stable ID (logins can change) |
| `github_login` | string(100) | For display and links |
| `connected_at` / `revoked_at` | timestamps | |

**No long-lived tokens are stored.** We act through short-lived **installation tokens** minted on demand from the App's private key (§3).

### 1.8 `learner_repositories` — new

| Column | Type | Notes |
|---|---|---|
| `id` | uuid PK | |
| `learner_session_id` | uuid FK unique | One repo per project session |
| `github_repo_id` | bigint | Stable repo ID (renames don't break the link) |
| `full_name` | string(200) | `ada/taskly` (refreshed on webhook) |
| `installation_id` | bigint | App installation that grants access |
| `default_branch` | string(100) | |
| `template_repo` | string(200) | Which template it was created from (validated on link) |
| `status` | enum(`linked`, `access_lost`, `archived`) | `access_lost` when the app is uninstalled from it |
| `last_accepted_sha` | string(40) nullable | Head of the last passed milestone — the base for the next diff |

### 1.9 `submission_packages` — extend

| New column | Type | Notes |
|---|---|---|
| `source` | enum(`paste`, `commit`, `pull_request`) default `paste` | Classic = paste |
| `commit_sha` | string(40) nullable | What was submitted |
| `base_sha` | string(40) nullable | Diff base (`last_accepted_sha` or template root) |
| `pr_number` | int nullable | WE submissions |
| `ci_status` | enum(`pending`, `passed`, `failed`, `errored`, `not_run`) nullable | |
| `ci_run_url` | string(300) nullable | Link shown to learner |
| `ci_report` | json nullable | Per-test results: `[{id:"T2.1", status:"passed"}, …]` |
| `diff_summary` | json nullable | Files changed, additions/deletions, truncated flag |

Existing layers are reused: `layer1_text` = the learner's explanation, `layer3_code` = the (truncated) diff text, `layer3_execution_result` = the CI summary, `layer4_planning_snapshot` = sprint board state (unchanged).

---

## 2. The Learner-Facing Flow

```
Enrol ─▶ choose stack variant ─▶ Connect GitHub (once) ─▶ Create repo from template
   ─▶ install Areyna app on that repo ─▶ Areyna links repo to the session
   ─▶ milestone: code locally, push ─▶ "Submit milestone" (pick commit / PR, write explanation)
   ─▶ CI runs acceptance tests ─▶ webhook delivers result ─▶ evaluation (tests + AI review)
   ─▶ PostEvaluationRouter (existing): pass → next milestone / scenario transition;
                                        fail → feedback + consequence card
```

### 2.1 Repo creation (why a guided flow, not "Areyna creates it for you")

Creating repositories *in a user's personal account* requires broad user permissions we don't want to hold. The robust, least-privilege flow:

1. Areyna shows **Create my repo** → deep link to GitHub's "use this template" page for the variant's `template_repo`, with the repo name pre-filled (e.g. `taskly`).
2. The learner creates it (one click on GitHub; public by default — see Overview §7.1).
3. Areyna shows **Give Areyna access** → the App's install page, where the learner selects **only that repo**.
4. The `installation` / `installation_repositories` webhook arrives → Areyna matches the repo to the waiting session (by GitHub user ID + expected template) and creates the `learner_repositories` row.
5. Areyna verifies the repo was generated from the expected template (`template_repository` field on the repo API). If not, it asks the learner to recreate it.

The learner owns the repo from minute one and keeps it after the project.

### 2.2 Submitting a milestone

**Build track** — "Submit milestone" form: commit picker (defaults to the head of the default branch; recent commits listed), explanation textarea (layer 1), optional files (layer 2, unchanged). On submit:

1. Record `commit_sha`, `base_sha = last_accepted_sha ?? template root commit`.
2. Fetch the **compare** (`base...head`) → file list + unified diff → truncate to a budget (e.g. 60 KB, largest generated/lock files skipped) → `layer3_code`, `diff_summary`.
3. Look up the CI run for that SHA. If already finished → evaluate now. If pending → submission shows **"Tests running…"** and evaluation starts when the `workflow_run` webhook arrives. If none within 15 minutes → `ci_status = not_run` and evaluation proceeds with a flag (AI told tests didn't run; milestone cannot *pass* without them — learner is told how to fix CI).

**Work Experience track** — the learner opens a PR in their repo using the template's PR description template; "Submit ticket" picks that PR. Same pipeline, plus: the AI review is posted as PR review comments (Overview §7.5), and the ticket is **done** when the PR is merged after tests + review pass.

---

## 3. GitHub App

### 3.1 Registration (done once by the platform owner)

| Setting | Value |
|---|---|
| Name / slug | `Areyna` / `areyna` (or `areyna-dev` for local) |
| Webhook URL | `https://<app-host>/github/webhook` (local: a tunnel such as smee.io) |
| Webhook secret | random 32+ bytes → `GITHUB_WEBHOOK_SECRET` |
| **Repository permissions** | Contents: **Read & write** (teammate PR branches) · Pull requests: **Read & write** · Actions: **Read** · Checks: **Read** · Metadata: **Read** |
| Account permissions | none |
| Events | `installation`, `installation_repositories`, `push`, `workflow_run`, `pull_request`, `pull_request_review` |
| Where can it be installed | Any account |
| User authorization (OAuth) | Enabled — used only to identify the learner's GitHub user ID during **Connect GitHub**; the user token is discarded after reading `/user` |

A templates organisation (e.g. `areyna-templates`) holds public **template repos** and the **acceptance suites**; **reference repos** are private in the same org, and the App must be installed on them to read reference code for teammate PRs.

### 3.2 Configuration

```dotenv
GITHUB_APP_ID=
GITHUB_APP_SLUG=areyna
GITHUB_APP_CLIENT_ID=
GITHUB_APP_CLIENT_SECRET=
GITHUB_APP_PRIVATE_KEY_PATH=storage/app/github-app.pem   # never committed
GITHUB_WEBHOOK_SECRET=
GITHUB_TEMPLATES_ORG=areyna-templates
```

### 3.3 Module layout

New bounded context **`src/SourceControl/`** (keeps GitHub specifics out of SimExecution/Submission):

- `Domain/`: `RepositoryHost` interface (compare, getCommit, listCommits, getWorkflowRunsForSha, downloadArtifact, createBranch, putFiles, openPullRequest, createReview), value objects (`CommitSha`, `RepoRef`).
- `Infrastructure/GitHub/`: `GitHubAppAuth` (JWT → installation token, cached ~50 min), `GitHubRepositoryHost` (REST via Laravel HTTP client), `WebhookSignatureVerifier`.
- `Application/`: `ConnectGitHubAccount`, `LinkRepository`, `HandleWorkflowRunCompleted`, `OpenTeammatePullRequest`, `PostReviewComments`.
- `Presentation/`: `GitHubWebhookController` (single endpoint, dispatches by `X-GitHub-Event`), `GitHubConnectController` (OAuth callback).

A **fake `RepositoryHost`** (in-memory) is bound in tests so the whole milestone flow is testable without GitHub.

### 3.4 Webhook handling

1. Verify `X-Hub-Signature-256` against the raw body (constant-time compare). Reject otherwise (401).
2. Store the delivery ID (`X-GitHub-Delivery`) for idempotency; ignore duplicates.
3. Respond `202` fast; handle in a queued job (`queue:work`), since evaluation can take seconds.
4. Handlers: `installation*` → link/unlink repos; `workflow_run.completed` → fetch report → resume pending submissions for that SHA; `pull_request` → PR merged/closed state for WE tickets and teammate PRs; `pull_request_review` → capture learner reviews of teammate PRs (review tasks).

---

## 4. Acceptance Tests

### 4.1 Black-box, per project (not per stack)

Acceptance tests exercise the **running app from the outside**:
- HTTP tests for APIs and server-rendered pages (status codes, JSON shapes, redirects);
- browser tests (Playwright) for UI behaviour, using a **`data-testid` contract** defined in the project spec (e.g. `task-item`, `task-toggle`).

Because they don't import the learner's code, **one suite serves every stack variant** of a project. Each variant only declares how to install, build and start the app (a small `areyna.json` in the template, see 4.3).

Every test has a stable ID tagged with the milestone that introduces it (`T2.1 — adding a task shows it in the list`). A milestone requires **all tests with IDs ≤ its milestone** to pass, so regressions are caught automatically.

### 4.2 Where tests live and how tampering is prevented

- The suite lives in its own repo in the templates org (`areyna-templates/taskly-acceptance`), versioned by tag.
- The learner's repo contains a workflow `.github/workflows/areyna.yml` (from the template) that: checks out the learner's code, starts the app using `areyna.json`, **checks out the acceptance suite at the pinned tag**, runs it, and uploads a JSON report as the artifact `areyna-report`.
- Areyna trusts only reports whose workflow file matches the expected content hash for the variant (checked via the API at the submitted SHA). A modified workflow → `ci_status = errored` with a clear message ("restore `.github/workflows/areyna.yml`").
- The AI review also sees the diff; edits to tests or the workflow are called out.

This isn't meant to stop a determined cheater (they can always pass without learning); it makes accidental breakage obvious and honest learners' results trustworthy.

### 4.3 `areyna.json` (in every template)

```json
{
  "project": "taskly",
  "variant": "express-ejs",
  "node": "20",
  "install": "npm ci",
  "build": null,
  "start": "npm start",
  "port": 3000,
  "healthcheck": "/",
  "env": { "DATABASE_FILE": ".data/taskly.db" }
}
```

The React variant starts two processes (API + built frontend served statically, or a single `npm start` that serves both) — the variant decides, the suite only needs one base URL (plus an optional `apiBaseUrl`).

### 4.4 Report format (`areyna-report.json`)

```json
{
  "suite": "taskly-acceptance",
  "version": "1.0.0",
  "commit": "<sha>",
  "startedOk": true,
  "tests": [
    { "id": "T1.1", "milestone": 1, "title": "home page lists tasks", "status": "passed" },
    { "id": "T2.1", "milestone": 2, "title": "adding a task shows it", "status": "failed",
      "message": "expected 4 task-item elements, found 3" }
  ]
}
```

---

## 5. Evaluation Changes

### 5.1 Inputs to the AI (EvaluationPromptBuilder)

For `source ∈ {commit, pull_request}` the user content becomes:

1. Milestone brief (resolved CAC variant, as today) + `brief_addendum` for the learner's stack;
2. Rubric criteria (unchanged);
3. **Test results** for this milestone's required set (IDs, pass/fail, failure messages);
4. **Diff** since the last accepted milestone (file list + truncated unified diff);
5. Learner's explanation (layer 1) and any files (layer 2);
6. For WE: the ticket, the PR description, and whether team procedure was followed (branch name, PR template filled, linked ticket).

### 5.2 Pass rule

A milestone **passes** when **both**:
- **tests**: every required test (≤ this milestone) passed; and
- **AI**: overall tier ≥ `proficient` (as today).

If tests fail, the AI still reviews (the learner gets useful feedback), but `passes_threshold = false` and `gap_type` is inferred from the failures. The existing `PostEvaluationRouter` then does what it already does: dimension scores, CAC adjustment, gap flags, consequence injection, habit detection, rank escalation, scenario transition.

### 5.3 Regressions → consequence tasks

A failed test from an **earlier** milestone is a regression. `ConsequenceTaskInjector` gets a second selection rule: if the failure set contains regressions, inject the milestone's configured **regression consequence** (authored per project — "Users say they can't add tasks since yesterday's update") instead of the generic one.

### 5.4 WE: AI PR review

After evaluation, `PostReviewComments` posts a GitHub review on the learner's PR as the persona "Areyna Senior Reviewer": a summary comment + up to N line comments drawn from `criteria_missed` and the diff. `REQUEST_CHANGES` when failing, `APPROVE` when passing. The learner addresses comments with new commits; each push re-runs CI and re-evaluates (attempt n+1, as today).

---

## 6. Catalogue, Enrolment and Guide Changes

- **Build-track sessions skip sprint planning and the backlog.** A scenario is a *chapter*; its milestones unlock one at a time in `sequence_order`. The board becomes a **milestone path** (done / current / locked) with the chapter's messages and any injected consequence or suggestion cards. `EnsureSprintPlanningReady` is not used; submissions for Build sessions don't require an active sprint (same rule the diagnostic already uses). Work Experience sessions keep sprints, backlog and planning exactly as today.
- Catalogue: tabs **Build** / **Work Experience** / **Classic**; cards show available stack variants and their difficulty; locked variants show the rank needed.
- Enrolment: choose variant (Build) or role → variant (WE). Classic unchanged.
- New "Repository" panel on the board: repo link, connection status, latest CI run, last accepted milestone.
- Guide (Guidance module): new steps — `github-connect`, `repo-setup`, `first-milestone-submit`, `ci-failed`, `teammate-pr`, `pr-review` — using the existing milestone-tip mechanism.

---

## 7. Work Experience: Teammate PRs and Roles

### 7.1 Monorepo layout

WE templates are monorepos with one directory per role area, e.g. `api/` and `web/`, plus `docs/` (API contract, procedures). The learner's role owns certain directories; teammates own the rest.

### 7.2 Delivering teammate work

The reference repo is built role-by-role with tags per event (e.g. `api-s1-donations`). A `teammate_pr` scenario event's payload:

```json
{
  "persona": { "name": "Tunde Bakare", "role": "Backend Lead" },
  "reference_tag": "api-s1-donations",
  "paths": ["api/"],
  "title": "Add POST /donations and donation model",
  "body": "Adds the donations endpoint per docs/api.yaml §2. Can you review before Friday?",
  "then": "review"
}
```

`OpenTeammatePullRequest`: reads those paths at that tag from the reference repo → creates branch `team/api-s1-donations` in the learner's repo from its default branch → commits the files (author: persona name, committer: the App) → opens a PR. `then` decides the learner's follow-up:
- `review` → inject a **review task**; the learner reviews on GitHub; `pull_request_review` webhook → the review text is evaluated (communication + problem analysis dimensions); then the PR is auto-merged by the App.
- `merge` → auto-merge after a short delay and notify ("Tunde merged the donations API — you can build against it now").

Because teammates only touch *their* directories, conflicts are rare; if the learner edited a teammate's area, a conflict is a realistic lesson and the event offers "ask Tunde to rebase" (App regenerates the branch).

**Full-stack role**: no code teammate PRs (they own everything); they still get QA reports, design hand-offs and review requests as events.

### 7.3 Roles

Reuse `role_definitions` (title, `specialization_tags`, `min_years_experience`, dimension weights/thresholds). Pilot roles: `frontend`, `backend`, `fullstack` specialization tags. A task's `role_tags` decides who must do it; tasks tagged for other roles are satisfied by teammate PRs instead. New roles (mobile, QA, DevOps, UI/UX, data) are added when a project needs them (doc 04).

---

## 8. Admin Authoring

- **Project**: track field.
- **Stack variants** (new screen under a project): CRUD for §1.2 fields; "Verify" button runs a health check (template exists, reference tags exist, acceptance tag exists).
- **Task**: `milestone` / `review` types; per-variant spec tab (§1.5).
- **Scenario events** (new screen under a scenario): list/create events (§1.6) with a payload editor per event type.
- Seeders remain the main authoring path for pilot content (as today), using the extended `AuthorsSimulationContent` trait.

---

## 9. Feature Flags

| Flag | Gates |
|---|---|
| `sourcecontrol.github` | Connect GitHub, repo linking, webhooks, commit/PR submissions |
| `tracks.build` | Build track in the catalogue |
| `tracks.work_experience` | Work Experience track in the catalogue |
| `scenario.scripted_events` | `ScenarioEventDispatcher` |
| `sourcecontrol.pr_review_comments` | Posting AI reviews to GitHub PRs |

Classic projects keep working with all of these off.

---

## 10. Security and Privacy

- Webhook signature verification on every delivery; replay protection via delivery IDs.
- The App private key is read from a file outside the web root, never logged or committed.
- Installation tokens are short-lived and cached server-side only.
- Every repo operation checks that the repo belongs to the session's learner (`github_user_id` match) and that the installation still includes it.
- Diffs sent to the AI provider exclude files matching `.env*`, `*.pem`, `*.key`, `node_modules/`, lock files and anything over a size limit; the learner is told that code they submit is sent to the configured AI provider.
- Learners can disconnect GitHub at any time (Settings); sessions with that repo move to `access_lost` and ask to reconnect.

---

## 11. Build Order for v2-1

1. Migrations (§1) + models + repositories.
2. `SourceControl` module with the fake host; unit/feature tests for linking, compare, CI ingestion.
3. GitHub connect (OAuth identify) + webhook endpoint + signature verification + queue jobs.
4. Milestone submission form (commit picker) + pending-CI state + resume on `workflow_run`.
5. Evaluation prompt changes + pass rule + regression consequences.
6. `ScenarioEventDispatcher` + board feed + scripted cards.
7. Teammate PRs + review tasks + PR review comments (needed for v2-3; can land after the Build pilot).
8. Catalogue tabs, variant selection, repository panel, guide steps.
9. Admin screens for variants and scenario events.

Each step ships behind its flag with feature tests using the fake host; a manual end-to-end run against a real GitHub test org closes the phase.
