# IWSTGS — Phase 5a: Learner-Side Migrations

> **Goal:** create the seven runtime tables the learner side needs — profile, rank log, dimension scores, concept mastery, role enrolment, session, and diagnostic session. **Migrations only** (models, profile bootstrapping, and the enrolment flow come in 5b/5c), mirroring the Phase-2 → Phase-3 split you already know.
>
> The real work here isn't typing `Schema::create` — it's **reconciling the Implementation Plan against Data Model v2**, because the plan is stale on several field types. Per the source-of-truth hierarchy, the Data Model wins; §3 lists every place they disagree and how I resolved it.

---

## 0. Phase 5 Is Split (like Phase 2/3)

| Sub-phase | Scope |
|---|---|
| **5a (this doc)** | The seven learner-side migrations |
| **5b** | Eloquent models for all seven + the `LearnerProfile` aggregate + **profile bootstrapping** (create profile + 6 dimension scores when a learner enrols) |
| **5c** | Project catalogue (`/learn`), the **enrolment flow** (role selection + experience gate → `role_enrolment` + `learner_session`), and the **learner dashboard read** — this is where your login/enrolment/dashboard mockups land |

You asked me to ignore page *look* and focus on backend — noted. 5c will build the controllers/queries/read-models the mockups imply (rank, active project, open tasks, per-dimension bars), not their styling.

---

## 1. Known Issues from Phase 4

- [x] **Bus fix landed.** Both buses now use `preg_replace('/Command$/' | '/Query$/', …)`. Dispatch resolves correctly — the standing blocker is gone.
- [ ] **🔴 `OrganizationModel` still points at a non-existent table.** The migration creates `organisations` and the FK column is `organisation_id` (and `ProjectTemplateModel` correctly uses `organisation_id`), but `OrganizationModel::$table = 'organizations'`. That's a one-line fix — revert it to match the schema (which the Data Model defines as British "organisation"):
  ```php
  // src/Organizations/Infrastructure/Persistence/Eloquent/Model/OrganizationModel.php
  protected $table = 'organisations';
  ```
  (Your "changed organisation→organization" commit touched code but not the schema; since the FK columns are still `organisation_id`, matching the class to `organisations` is the minimal correct fix. A full DB rename to `organizations` is a larger, Data-Model-diverging change — not worth it.)
- [x] The extra seeders you added (`AdminUserSeeder`, `FeatureFlagSeeder`, `RoleSeeder`) are sensible "implied" additions — no conflict with 5a.

---

## 2. Bounded-Context Ownership (matters in 5b, set expectations now)

Migrations all live in `database/migrations/`, but their **models** (5b) get placed by context, per the Data Model's context map:

| Tables | Context (owner of the model) |
|---|---|
| `role_enrolments`, `learner_sessions`, `diagnostic_sessions` | **SimExecution** (Group E — Session & Enrolment) |
| `learner_profiles`, `rank_events`, `dimension_scores`, `concept_mastery_records` | **LearnerProfile** (Group F — Rank & Profile) |

> Note: the Implementation Plan's 5.3 says "create `LearnerObserver` in `src/Identity/`." That's stale — the `Learner` model now lives in **SimExecution**, and the profile belongs to **LearnerProfile**. In 5b we'll bootstrap the profile via a domain event (SimExecution emits "learner enrolled" → LearnerProfile listens and creates the profile), not an Identity Eloquent observer reaching across two contexts. Flagging now so 5a's FKs make sense.

---

## 3. Plan ↔ Data Model Reconciliations (read before writing)

Every one of these follows "Data Model wins," except where the Data Model is internally impossible for a working MySQL FK — those I note explicitly.

| # | Field | Plan says | Data Model says | Resolution |
|---|---|---|---|---|
| 1 | `learner_profiles.current_rank_tier` | `ENUM('Junior','Mid','Senior')` | **VARCHAR(20)** | `string(20)` — Data Model wins (rank tiers are strings, not a DB enum). |
| 2 | `rank_events.source_session_id` | NULL | **NOT NULL**, FK→learner_session | **NULLABLE** — an `initial_assignment` rank event happens at diagnostic time, before any `learner_session` exists, so NOT NULL is logically impossible. Deviating from the Data Model with reason. **Confirm this.** |
| 3 | `dimension_scores.dimension_id` | VARCHAR(20) | **TEXT**, FK→competence_dimensions.id | `string(20)` — a MySQL FK must match the referenced PK's type, and `competence_dimensions.id` is `varchar(20)`. `TEXT` can't be that FK. The plan's VARCHAR(20) is correct here. |
| 4 | `learner_sessions.current_sprint_id` | UUID (no FK) | FK→learner_sprint | Plain nullable `uuid` **now**; the FK is added in **Phase 6** when `learner_sprints` exists. |
| 5 | `learner_profiles` timestamps | `updated_at` only | `updated_at` only (NOT NULL) | Single `updated_at` column, no `created_at` — so **not** `$table->timestamps()`. |
| 6 | integer widths (`*_rank_level`, `failure_streak`, counts) | mixed TINYINT/SMALLINT | **SMALLINT** | `smallInteger(...)->unsigned()` throughout, per Data Model. |

Enum values are taken verbatim from the Data Model and must match exactly when the models cast them in 5b.

---

## 4. The Migrations (in FK-dependency order)

**Order matters** — FKs require the referenced table to exist first. Use these timestamps (or later) so they run after Phase 2's content tables and in this internal order: profiles → enrolments → sessions → rank_events → scores → mastery → diagnostics. (`rank_events` must come *after* `learner_sessions` for its `source_session_id` FK.)

### 4.1 — `create_learner_profiles_table`
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('learner_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learner_id');
            $table->string('current_rank_tier', 20)->default('Junior');   // Reconciliation #1
            $table->smallInteger('current_rank_level')->unsigned()->default(1);
            $table->enum('cac_complexity', ['low', 'mid', 'high'])->default('low');
            $table->enum('cac_autonomy', ['low', 'mid', 'high'])->default('mid');
            $table->enum('cac_context_fidelity', ['low', 'mid', 'high'])->default('low');
            $table->boolean('mismatch_flag_active')->default(false);
            $table->smallInteger('failure_streak')->unsigned()->default(0);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate(); // #5 — no created_at

            $table->unique('learner_id'); // one-to-one with learner
            $table->foreign('learner_id')->references('id')->on('learners')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('learner_profiles'); }
};
```
> `cascadeOnDelete` on `learner_id`: a profile has no meaning without its learner, so deleting the learner removes the profile.

### 4.2 — `create_role_enrolments_table`
```php
Schema::create('role_enrolments', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('learner_id');
    $table->uuid('role_id');
    $table->uuid('project_id');
    $table->boolean('is_gated')->default(false);
    $table->timestamp('enrolled_at')->useCurrent();

    $table->foreign('learner_id')->references('id')->on('learners')->cascadeOnDelete();
    $table->foreign('role_id')->references('id')->on('role_definitions')->restrictOnDelete();
    $table->foreign('project_id')->references('id')->on('project_templates')->restrictOnDelete();
});
```
> `restrictOnDelete` on `role_id`/`project_id`: you shouldn't be able to delete a role definition or project that learners are enrolled in — the restriction protects referential history.

### 4.3 — `create_learner_sessions_table`
```php
Schema::create('learner_sessions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('learner_id');
    $table->uuid('project_id');
    $table->uuid('role_enrolment_id');
    $table->enum('status', ['diagnostic', 'active', 'complete', 'suspended'])->default('active');
    $table->uuid('current_scenario_id')->nullable();
    $table->uuid('current_task_id')->nullable();
    $table->uuid('current_sprint_id')->nullable();               // #4 — FK deferred to Phase 6
    $table->timestamp('induction_completed_at')->nullable();
    $table->timestamp('started_at')->useCurrent();
    $table->timestamp('completed_at')->nullable();

    $table->foreign('learner_id')->references('id')->on('learners')->cascadeOnDelete();
    $table->foreign('project_id')->references('id')->on('project_templates')->restrictOnDelete();
    $table->foreign('role_enrolment_id')->references('id')->on('role_enrolments')->cascadeOnDelete();
    $table->foreign('current_scenario_id')->references('id')->on('scenario_templates')->nullOnDelete();
    $table->foreign('current_task_id')->references('id')->on('tasks')->nullOnDelete();
    // current_sprint_id FK added in Phase 6 (learner_sprints not yet created)
});
```
> `nullOnDelete` on the two `current_*` pointers: if a scenario/task is deleted, the session simply loses its "current" pointer rather than cascading a delete of the whole session.

### 4.4 — `create_rank_events_table`
```php
Schema::create('rank_events', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('learner_id');
    $table->enum('event_type', [
        'initial_assignment', 'escalation', 'de_escalation', 'sub_level_progression', 'review_triggered',
    ]);
    $table->string('from_rank_tier', 20)->nullable();
    $table->smallInteger('from_rank_level')->unsigned()->nullable();
    $table->string('to_rank_tier', 20);
    $table->smallInteger('to_rank_level')->unsigned();
    $table->text('trigger_reason');
    $table->uuid('source_session_id')->nullable();              // #2 — nullable (initial_assignment has no session)
    $table->timestamp('created_at')->useCurrent();              // immutable audit log — created_at only

    $table->foreign('learner_id')->references('id')->on('learners')->cascadeOnDelete();
    $table->foreign('source_session_id')->references('id')->on('learner_sessions')->nullOnDelete();
});
```

### 4.5 — `create_dimension_scores_table`
```php
Schema::create('dimension_scores', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('learner_id');
    $table->string('dimension_id', 20);                        // #3 — varchar(20) to FK competence_dimensions.id
    $table->enum('tier', ['untested', 'basic', 'intermediate', 'advanced'])->default('untested');
    $table->smallInteger('evidence_count')->unsigned()->default(0);
    $table->timestamp('last_updated_at')->nullable();

    $table->unique(['learner_id', 'dimension_id']);            // one row per learner per dimension
    $table->foreign('learner_id')->references('id')->on('learners')->cascadeOnDelete();
    $table->foreign('dimension_id')->references('id')->on('competence_dimensions')->restrictOnDelete();
});
```

### 4.6 — `create_concept_mastery_records_table`
```php
Schema::create('concept_mastery_records', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('learner_id');
    $table->uuid('concept_id');
    $table->enum('status', ['not_encountered', 'encountered', 'partially_met', 'mastered'])->default('not_encountered');
    $table->smallInteger('tasks_encountered')->unsigned()->default(0);
    $table->smallInteger('tasks_met')->unsigned()->default(0);
    $table->timestamp('last_updated_at')->nullable();

    $table->unique(['learner_id', 'concept_id']);
    $table->foreign('learner_id')->references('id')->on('learners')->cascadeOnDelete();
    $table->foreign('concept_id')->references('id')->on('concept_tags')->cascadeOnDelete();
});
```

### 4.7 — `create_diagnostic_sessions_table`
```php
Schema::create('diagnostic_sessions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('learner_id');
    $table->enum('pathway', ['interview', 'assessment_tasks', 'diagnostic_scenario']);
    $table->enum('status', ['in_progress', 'complete', 'abandoned'])->default('in_progress');
    $table->string('assigned_rank_tier', 20)->nullable();
    $table->smallInteger('assigned_rank_level')->unsigned()->nullable();
    $table->timestamp('started_at')->useCurrent();
    $table->timestamp('completed_at')->nullable();

    $table->foreign('learner_id')->references('id')->on('learners')->cascadeOnDelete();
});
```

- [ ] All seven migrations created with the above FK order and reconciled types.

---

## 5. Verification Checklist

- [ ] `php artisan migrate` (or `migrate:fresh --seed`) creates all seven tables with **no FK errors** — confirms the ordering and that every referenced table exists.
- [ ] `learner_profiles.learner_id` is **UNIQUE** (one-to-one); `dimension_scores` and `concept_mastery_records` have their composite uniques.
- [ ] `learner_profiles.current_rank_tier` is `varchar(20)`, not an enum (Reconciliation #1).
- [ ] `rank_events.source_session_id` is **nullable** with an FK to `learner_sessions` (Reconciliation #2) — insert an `initial_assignment` row with `source_session_id = NULL` to prove it.
- [ ] `dimension_scores.dimension_id` FKs cleanly to `competence_dimensions.id` (Reconciliation #3) — both `varchar(20)`.
- [ ] `learner_sessions.current_sprint_id` exists as a plain nullable `uuid` with **no** FK yet (Reconciliation #4).
- [ ] `learner_profiles` has `updated_at` but **no** `created_at` (Reconciliation #5).
- [ ] All rank levels / counts are `smallint unsigned` (Reconciliation #6).
- [ ] `down()` on each drops cleanly (rollback check).

---

## 6. What 5b / 5c Build

- **5b — Models + profile bootstrapping.** Eloquent models for the seven tables (SimExecution owns sessions/enrolment/diagnostics; LearnerProfile owns profile/rank/scores/mastery), the `LearnerProfile` aggregate + repository, and the **event listener** that, when a learner enrols, creates their profile at Junior-1 with the six `dimension_score` rows (all `untested`) and an `initial_assignment` `rank_event`.
- **5c — Catalogue + enrolment + dashboard.** `/learn` catalogue (published/active projects), the enrolment flow (`EnrolmentService` in SimExecution: role select → experience-gate check → `role_enrolment` + `learner_session` → redirect to induction), and the **dashboard read model** feeding your mockup: current rank (`learner_profiles`), active project/session, open tasks, and the six per-dimension bars (`dimension_scores`).
  > Mockup terminology note for later: the dashboard's per-dimension bars map to `dimension_scores.tier` (`untested`/`basic`/`intermediate`/`advanced`). The mockup's "AVG. TIER (30D): Competent" is a **computed rollup over recent submissions** — a Phase-10 reporting value, not a `dimension_scores.tier` — so don't wire it to this table directly.

---

## 7. Scope Discipline Self-Check (Guidance §7)

- [x] **Every table matches Data Model v2** — all seven cross-checked field-by-field; the three deviations (#2 nullability, #3 FK type, #4 deferred FK) are documented with reasons, not silent.
- [x] **Correct context ownership recorded** for 5b (SimExecution vs LearnerProfile); the plan's "observer in Identity" is flagged as stale.
- [x] **Nothing pulled forward** — `current_sprint_id`'s FK to `learner_sprints` (Phase 6) is deliberately deferred; no models/flow built (that's 5b/5c).
- [x] **Enums verbatim from the Data Model** — session/diagnostic/rank/tier/mastery enums copied exactly; they'll be cast in 5b.
- [x] **FK delete-behaviour reasoning stated** (cascade for owned rows, restrict for referenced definitions, null for soft pointers).
- [x] **Migrations-only** — mirrors the Phase-2 discipline; models deferred to 5b.
- [x] **Runnable verification** (§5), including the initial-assignment null-session proof and the rollback check.
- [x] **Open item flagged** — Reconciliation #2 (`source_session_id` nullability) needs your confirmation, since it deviates from the Data Model's NOT NULL.

**One decision for you:** confirm Reconciliation #2 — I made `rank_events.source_session_id` nullable because an `initial_assignment` event precedes any `learner_session`. If you'd rather keep the Data Model's NOT NULL and instead create a session earlier in the diagnostic flow, tell me and I'll adjust 5a + the 5b bootstrap accordingly. Everything else follows the Data Model exactly.
