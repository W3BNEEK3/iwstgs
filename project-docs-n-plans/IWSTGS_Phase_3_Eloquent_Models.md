# IWSTGS — Phase 3: Eloquent Models

> **Goal of this phase:** give every content, competency, and rubric table created in Phase 2 a working Eloquent model — with the correct primary-key configuration, casts, and relationships — so the rest of the system can *query* the schema instead of only owning empty tables. **No controllers, no domain entities, no repositories in this phase.** Just the persistence models.
>
> This is the "classes" half of the migrations-first / classes-second rhythm you used in Phase 2. Phase 2 built the tables; Phase 3 makes them addressable from PHP.

---

## 0. Where This Phase Sits

Phase 2 closed with ~40 content tables in the database and **zero** Eloquent models for them. Right now, if you opened `tinker` and wrote `ProjectTemplateModel::first()`, PHP wouldn't know what `ProjectTemplateModel` is. Phase 4 (the admin authoring UI) can't be built until these models exist, because the controllers there will lean on `ProjectTemplate::create(...)`, `$task->rubricCriteria`, and so on.

So Phase 3 is a bridge phase. It's mechanical in places, but there are four genuinely instructive things in it — a **string primary key** (not a UUID), a **self-referential** relationship, a **cross-context** relationship you should *deliberately not* declare in both directions, and **casts** that turn raw DB columns into real PHP types. Those are the parts to slow down on.

**Prerequisites (all already true on `development`):**
- Phases 0–2 complete; `php artisan migrate:fresh` builds every table with no errors.
- The five existing Eloquent models — `UserModel`, `RoleModel` (Identity), `LearnerModel` (SimExecution), `OrganizationModel` (Organizations), `FeatureFlagModel` (Shared) — are the pattern this phase copies.
- The three domain enums `TaskType`, `DifficultyLevel`, `ConceptTagCategory` already exist in `src/Simulation/Domain/`.

---

## 1. Architecture Reconciliation — Read Before You Start

The Implementation Plan's Phase 3 section is **stale on two points**. The source-of-truth hierarchy (Data Model → Integration Spec → Business Logic → Implementation Plan) means the plan loses these, so here is the corrected guidance:

**1.1 — Eloquent models do NOT go in `Domain/`.** The plan writes paths like `src/Simulation/Domain/Models/ProjectTemplate.php` and `src/Identity/Domain/Models/Learner.php`. That contradicts your DDD layering rule (Domain is framework-free — zero Eloquent) and everything Phases 1–2 established. **Every model in this phase lives in its module's `Infrastructure/Persistence/Eloquent/Model/` directory** and is named `<Entity>Model`, exactly like `UserModel`. The `Domain/` folder stays pure; domain entities for these tables come later, only when there's behaviour to model.

**1.2 — Do not pull relationships forward from later phases.** The plan lists `Learner` with `hasOne(LearnerProfile)` and `hasMany(RoleEnrolment)`. Those tables **do not exist yet** — `learner_profiles`, session, and enrolment tables arrive in Phases 5–6. A `hasOne` pointing at a non-existent table is a runtime error waiting to happen. `LearnerModel` already exists and is correct as-is; **we do not touch it in this phase** except to confirm it. We only declare relationships between tables that exist *now*.

**1.3 — The plan's index row for Phase 3 says "Competency Definitions."** That's the third stale spot — the plan's own section header, its verification table ("all models can be resolved via Eloquent"), and Phase 2's closing all say **Eloquent Models**. We follow those three. Seeding the actual six dimensions and role definitions is data-loading, and it happens in Phase 4's seeder, not here.

---

## 2. Known Issues to Fix First (Closing Out Phase 2)

Per the phase-doc convention, we clean up what the previous work surfaced before adding new code. Some of these you've **already fixed** — they're listed so the record is complete — and three are **still open**.

**Already resolved (verified on `development`):**
- [x] Duplicate `roleModel.php` deleted; only `RoleModel.php` remains.
- [x] `userDTO.php` renamed to `UserDTO.php` (PSR-4 filename now matches the `UserDTO` class).
- [x] `LearnerModel`, `LearnerMapper`, `EloquentLearnerRepository` moved to SimExecution, and `LearnerModel`'s namespace corrected to `Src\SimExecution\...`.

**Still open — do these before writing models:**

- [ ] **Add the missing `LearnerRepository` binding.** `EnrolAsLearnerHandler` depends on the `Src\SimExecution\Domain\Enrollment\LearnerRepository` interface, but nothing binds it, so the container can't build it (`BindingResolutionException` on first enrolment). Add to `SimExecutionServiceProvider::register()`:
  ```php
  public function register(): void
  {
      $this->app->bind(
          \Src\SimExecution\Domain\Enrollment\LearnerRepository::class,
          \Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository\EloquentLearnerRepository::class,
      );
  }
  ```
  *(Bindings go in `register()`, not `boot()` — `boot()` is for routes/events after all providers are registered.)*

- [ ] **Change the `CommandBus` binding to `singleton`.** `SynchronousCommandBus` holds only a `readonly Container` and resolves handlers fresh on every `dispatch()` — it's a stateless dispatcher, the textbook singleton. In `SharedServiceProvider`, change:
  ```php
  // from:
  $this->app->bind(CommandBus::class, SynchronousCommandBus::class);
  // to:
  $this->app->singleton(CommandBus::class, SynchronousCommandBus::class);
  ```
  Making the *bus* a singleton does **not** make handlers shared — each command still gets a freshly-resolved handler.

- [ ] **Remove the `administrator` and `administrator_role` tables.** A platform admin is a `users` row with the `super_admin` role via the `user_role` pivot; a separate identity table is a second source of truth for a fact `user_role` already stores. Delete the two migration files (`..._create_administrator_table.php`, `..._create_administrator_role_table.php`) and, if you've already migrated, `php artisan migrate:fresh` after removing them. Nothing in `src/` references them, so this is a clean removal.

- [ ] **(Cosmetic, optional) Rename the mis-named migration file.** `2026_05_05_134605_create_learner_role_table.php` actually creates the `user_role` table. The DB is correct; only the filename misleads. Rename the file to `..._create_user_role_table.php` when convenient so `ls migrations/` reads honestly. Low priority.

---

## 3. New PHP / Laravel Syntax Used in This Phase

You've seen models before, but this phase introduces several model-configuration tools for the first time. Here's what each does and why it matters.

**`protected $casts` — turning DB columns into PHP types.** MySQL stores everything as strings/numbers. A `json` column comes back as a raw JSON *string* unless you tell Eloquent to decode it. `$casts` is that instruction:
- `'some_column' => 'array'` — decodes a `json` column to a PHP array on read, encodes on write. Every `json` column in Phase 2 needs this, or you'll be calling `json_decode()` by hand everywhere.
- `'some_column' => 'boolean'` — MySQL has no real boolean; a `boolean` column is `tinyint(1)` and returns `0`/`1`. This cast gives you real PHP `true`/`false`. Without it, `if ($task->is_published)` is comparing against `1`, which works by luck until it doesn't.
- `'some_column' => 'integer'` / `'datetime'` — normalises numeric and timestamp columns.
- `'weight' => 'decimal:3'` — keeps a `decimal(4,3)` column as a fixed-precision string with 3 decimal places, so `0.250` doesn't drift into float rounding error. Rubric weights are precise on purpose.
- `'task_type' => TaskType::class` — an **enum cast**. Eloquent converts the DB string (`'core'`) into your PHP backed enum (`TaskType::Core`) on read, and back on write. This only works when the enum's cases exactly match the DB `enum` values — which is why we only cast the three columns whose enums already exist.

**`use HasUuids;` — automatic UUID generation.** Your `uuid` primary keys aren't auto-incrementing integers; something has to generate them. The `HasUuids` trait hooks model creation and fills `id` with a UUID if you don't supply one. It also tells Eloquent the key is a non-incrementing string. Every model with a `uuid('id')` primary key uses it.

**String primary keys — the exception.** `competence_dimensions` uses `string('id', 20)` (human-readable IDs like `dim_correctness`), **not** a UUID. Its model must *not* use `HasUuids`, and must declare:
```php
protected $keyType = 'string';
public $incrementing = false;
```
Otherwise Eloquent assumes an auto-incrementing integer key and breaks on lookup.

**`public $timestamps = false;`** — many Phase 2 tables deliberately have no `created_at`/`updated_at` (child rows like `task_cac_variants`, `rubric_criteria`, join-ish tables). If a table has no timestamps and you don't set this, every insert fails trying to write columns that don't exist.

**Typed relationship methods.** Each relationship is a method returning a typed relation (`BelongsTo`, `HasMany`, `HasOne`), imported from `Illuminate\Database\Eloquent\Relations\`. The return type isn't decoration — it documents the relationship and lets your IDE autocomplete `$project->scenarios`.

---

## 4. Module & File Placement

Seventeen new models across three contexts. Each goes in `src/<Module>/Infrastructure/Persistence/Eloquent/Model/`:

| Context | Models |
|---|---|
| **Competency** | `CompetenceDimensionModel`, `RoleDefinitionModel` |
| **Simulation** | `ConceptTagModel`, `ProjectTemplateModel`, `ScenarioTemplateModel`, `ScenarioReferenceMaterialModel`, `ArtifactVaultItemModel`, `BacklogItemTemplateModel`, `TaskModel`, `TaskExpectedDeliverableModel`, `TaskCacVariantModel`, `TaskDependencyModel`, `TaskKnowledgeAnchorModel`, `TaskGuidancePromptModel`, `RubricSetModel`, `RubricCriterionModel` |
| **EvalEngine** | `FollowUpPromptTemplateModel` |

Context assignment follows the Integration Spec map: Competency owns dimension definitions and role thresholds; Simulation owns project/scenario/task blueprints **and** rubric definitions; EvalEngine owns follow-up prompts.

> **Open design note (Section 8 of the guidance):** `follow_up_prompt_templates` is *authored* content, which argues for Simulation, but the context map explicitly lists "follow-up prompts" under EvalEngine. I've placed it in EvalEngine to follow the authoritative map. If you'd rather group all authored templates under Simulation, say so and we'll move it — flagging rather than silently deciding.

No provider changes are needed for the models themselves — they're plain classes autoloaded via PSR-4 (`Src\ → src/`). Models only need registration when something *binds* them, which isn't the case here.

---

## 5. Competency Models

### 5.1 — `CompetenceDimensionModel`

**Why it's first and why it's special:** this is the one string-PK table. The `id` is a short slug (`dim_correctness`), it has **no timestamps**, and it's referenced by `rubric_criteria.parent_dimension_id`.

`src/Competency/Infrastructure/Persistence/Eloquent/Model/CompetenceDimensionModel.php`
```php
<?php

namespace Src\Competency\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

class CompetenceDimensionModel extends Model
{
    protected $table = 'competence_dimensions';

    // String primary key, not an auto-incrementing integer and not a UUID.
    protected $keyType = 'string';
    public $incrementing = false;

    // This table has no created_at / updated_at columns.
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'short_label',
        'core_question',
        'observable_indicators',
        'sequence_order',
    ];

    protected $casts = [
        'observable_indicators' => 'array',
        'sequence_order'        => 'integer',
    ];
}
```

**Deliberate omission — the cross-context relationship.** You might expect a `hasMany(RubricCriterionModel::class, 'parent_dimension_id')` here. We **don't** add it. `RubricCriterion` lives in the Simulation context; declaring the `hasMany` here would make **Competency depend on Simulation**, coupling two contexts in the wrong direction. The reference only needs to exist on the *owning* side (Simulation's `RubricCriterionModel` points *up* to the dimension), because Simulation legitimately depends on Competency's vocabulary. Keep the arrow one-way. This is a small decision, but it's exactly the kind of discipline that keeps a modular monolith from quietly becoming a tangle.

- [ ] `CompetenceDimensionModel` created, no `HasUuids`, `$timestamps = false`, string key configured.

### 5.2 — `RoleDefinitionModel`

Standalone — it holds the qualification thresholds a learner is measured against. Three JSON columns, no outgoing relationships.

`src/Competency/Infrastructure/Persistence/Eloquent/Model/RoleDefinitionModel.php`
```php
<?php

namespace Src\Competency\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RoleDefinitionModel extends Model
{
    use HasUuids;

    protected $table = 'role_definitions';

    protected $fillable = [
        'id',
        'title',
        'specialization_tags',
        'min_years_experience',
        'dimension_weights',
        'dimension_thresholds',
        'is_lead_role',
    ];

    protected $casts = [
        'specialization_tags'  => 'array',
        'dimension_weights'    => 'array',
        'dimension_thresholds' => 'array',
        'min_years_experience' => 'integer',
        'is_lead_role'         => 'boolean',
    ];
}
```

- [ ] `RoleDefinitionModel` created with three array casts.

---

## 6. Simulation — Content Models

### 6.1 — `ConceptTagModel`

The shared vocabulary of concepts tasks can be tagged against. First enum cast (`category → ConceptTagCategory`). `source` stays a plain string — no enum exists for it yet, and we're not inventing scope.

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/ConceptTagModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Simulation\Domain\ConceptTag\ConceptTagCategory;

class ConceptTagModel extends Model
{
    use HasUuids;

    protected $table = 'concept_tags';

    protected $fillable = [
        'id',
        'tag',
        'category',
        'description',
        'source',
    ];

    protected $casts = [
        'category' => ConceptTagCategory::class,
    ];

    public function knowledgeAnchors(): HasMany
    {
        return $this->hasMany(TaskKnowledgeAnchorModel::class, 'concept_id');
    }
}
```

- [ ] `ConceptTagModel` created; `category` cast to the existing `ConceptTagCategory` enum.

### 6.2 — `ProjectTemplateModel`

The umbrella. Five JSON columns, an enum cast, and the most relationships of any model here.

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/ProjectTemplateModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Src\Organizations\Infrastructure\Persistence\Eloquent\Model\OrganizationModel;
use Src\Simulation\Domain\Project\DifficultyLevel;

class ProjectTemplateModel extends Model
{
    use HasUuids;

    protected $table = 'project_templates';

    protected $fillable = [
        'id',
        'title',
        'tagline',
        'project_type',
        'business_domain',
        'business_context',
        'stakeholders',
        'overarching_constraints',
        'tech_context',
        'specialization_tags',
        'organisation_id',
        'rubric_set_id',
        'coding_guidelines',
        'velocity_estimate',
        'difficulty_level',
        'is_published',
        'is_active',
    ];

    protected $casts = [
        'stakeholders'            => 'array',
        'overarching_constraints' => 'array',
        'tech_context'            => 'array',
        'specialization_tags'     => 'array',
        'velocity_estimate'       => 'array',
        'difficulty_level'        => DifficultyLevel::class,
        'is_published'            => 'boolean',
        'is_active'               => 'boolean',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(OrganizationModel::class, 'organisation_id');
    }

    public function scenarios(): HasMany
    {
        return $this->hasMany(ScenarioTemplateModel::class, 'project_id');
    }

    public function rubricSet(): HasOne
    {
        return $this->hasOne(RubricSetModel::class, 'project_id');
    }

    public function vaultItems(): HasMany
    {
        return $this->hasMany(ArtifactVaultItemModel::class, 'project_id');
    }

    public function backlogItems(): HasMany
    {
        return $this->hasMany(BacklogItemTemplateModel::class, 'project_id');
    }
}
```

**Note on the `rubric_set_id` column.** The schema links project and rubric set *both ways* — `rubric_sets.project_id` (unique) and `project_templates.rubric_set_id`. We model the ownership direction only: `rubricSet()` via `project_id`. The `rubric_set_id` column stays available for direct lookups but we don't add a second relationship to the same table, which would be redundant and confusing. (Phase 4's project-creation transaction is what will populate both sides atomically.)

- [ ] `ProjectTemplateModel` created; five array casts, `difficulty_level` enum cast, five relationships.

### 6.3 — `ScenarioTemplateModel`

The chapter. Belongs to a project; owns tasks and reference materials.

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/ScenarioTemplateModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScenarioTemplateModel extends Model
{
    use HasUuids;

    protected $table = 'scenario_templates';

    protected $fillable = [
        'id',
        'project_id',
        'sequence_order',
        'title',
        'narrative_context',
        'situation_trigger',
        'situation_trigger_type',
        'learner_role_label',
        'default_autonomy_level',
        'is_diagnostic',
        'is_published',
        'is_active',
    ];

    protected $casts = [
        'sequence_order' => 'integer',
        'is_diagnostic'  => 'boolean',
        'is_published'   => 'boolean',
        'is_active'      => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateModel::class, 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TaskModel::class, 'scenario_id');
    }

    public function referenceMaterials(): HasMany
    {
        return $this->hasMany(ScenarioReferenceMaterialModel::class, 'scenario_id');
    }
}
```

*(`situation_trigger_type` and `default_autonomy_level` are DB enums with no PHP enum yet — left as strings. See §9 for the deferred-enum note.)*

- [ ] `ScenarioTemplateModel` created; `belongsTo` project, `hasMany` tasks + reference materials.

### 6.4 — `ScenarioReferenceMaterialModel`

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/ScenarioReferenceMaterialModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScenarioReferenceMaterialModel extends Model
{
    use HasUuids;

    protected $table = 'scenario_reference_materials';

    protected $fillable = [
        'id',
        'scenario_id',
        'material_type',
        'title',
        'content',
        'embedded_signals',
        'display_order',
    ];

    protected $casts = [
        'embedded_signals' => 'array',
        'display_order'    => 'integer',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(ScenarioTemplateModel::class, 'scenario_id');
    }
}
```

- [ ] `ScenarioReferenceMaterialModel` created; `embedded_signals` array cast.

### 6.5 — `ArtifactVaultItemModel`

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/ArtifactVaultItemModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtifactVaultItemModel extends Model
{
    use HasUuids;

    protected $table = 'artifact_vault_items';

    protected $fillable = [
        'id',
        'project_id',
        'document_type',
        'title',
        'content',
        'rank_gate',
        'phase_gate',
        'is_reference_doc',
        'display_order',
    ];

    protected $casts = [
        'is_reference_doc' => 'boolean',
        'display_order'    => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateModel::class, 'project_id');
    }
}
```

- [ ] `ArtifactVaultItemModel` created.

### 6.6 — `BacklogItemTemplateModel`

**No timestamps.** Two JSON columns. Belongs to a project, optionally links to a task.

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/BacklogItemTemplateModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacklogItemTemplateModel extends Model
{
    use HasUuids;

    protected $table = 'backlog_item_templates';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'project_id',
        'title',
        'description',
        'default_priority',
        'role_tags',
        'task_id',
        'dependency_item_ids',
        'display_order',
    ];

    protected $casts = [
        'role_tags'           => 'array',
        'dependency_item_ids' => 'array',
        'display_order'       => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateModel::class, 'project_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }
}
```

- [ ] `BacklogItemTemplateModel` created; `$timestamps = false`, two array casts.

---

## 7. Simulation — Task Models

### 7.1 — `TaskModel`

The work item, and the busiest model in the schema: six JSON columns, an enum cast, and seven relationships.

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/TaskModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Simulation\Domain\Task\TaskType;

class TaskModel extends Model
{
    use HasUuids;

    protected $table = 'tasks';

    protected $fillable = [
        'id',
        'scenario_id',
        'sequence_order',
        'title',
        'task_brief',
        'domain',
        'task_type',
        'role_tags',
        'tools',
        'prerequisite_concepts',
        'is_cac_runtime_set',
        'fixed_complexity',
        'fixed_autonomy',
        'fixed_context_fidelity',
        'consequence_task_ids',
        'suggestion_task_ids',
        'is_architectural',
        'planning_layer_active',
        'code_execution_config',
        'model_response_summary',
        'time_limit_minutes',
        'is_published',
        'is_active',
    ];

    protected $casts = [
        'task_type'             => TaskType::class,
        'role_tags'             => 'array',
        'tools'                 => 'array',
        'prerequisite_concepts' => 'array',
        'consequence_task_ids'  => 'array',
        'suggestion_task_ids'   => 'array',
        'code_execution_config' => 'array',
        'sequence_order'        => 'integer',
        'time_limit_minutes'    => 'integer',
        'is_cac_runtime_set'    => 'boolean',
        'is_architectural'      => 'boolean',
        'planning_layer_active' => 'boolean',
        'is_published'          => 'boolean',
        'is_active'             => 'boolean',
    ];

    public function scenario(): BelongsTo
    {
        return $this->belongsTo(ScenarioTemplateModel::class, 'scenario_id');
    }

    public function expectedDeliverables(): HasMany
    {
        return $this->hasMany(TaskExpectedDeliverableModel::class, 'task_id');
    }

    public function cacVariants(): HasMany
    {
        return $this->hasMany(TaskCacVariantModel::class, 'task_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(TaskDependencyModel::class, 'task_id');
    }

    public function knowledgeAnchors(): HasMany
    {
        return $this->hasMany(TaskKnowledgeAnchorModel::class, 'task_id');
    }

    public function guidancePrompts(): HasMany
    {
        return $this->hasMany(TaskGuidancePromptModel::class, 'task_id');
    }

    public function rubricCriteria(): HasMany
    {
        return $this->hasMany(RubricCriterionModel::class, 'task_id');
    }
}
```

*(`fixed_complexity`, `fixed_autonomy`, `fixed_context_fidelity` are `low/mid/high` enums — left as strings for now; see §9.)*

- [ ] `TaskModel` created; six array casts, `task_type` enum cast, seven relationships.

### 7.2 — The Task child models

These five are structurally similar — each belongs to a task, none has timestamps. Two carry extra relationships worth noting: `TaskDependencyModel` is **self-referential** (both FKs point at `tasks`), and `TaskKnowledgeAnchorModel` also belongs to a `ConceptTag`.

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/TaskExpectedDeliverableModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskExpectedDeliverableModel extends Model
{
    use HasUuids;

    protected $table = 'task_expected_deliverables';
    public $timestamps = false;

    protected $fillable = [
        'id', 'task_id', 'type', 'label', 'description', 'is_required', 'display_order',
    ];

    protected $casts = [
        'is_required'   => 'boolean',
        'display_order' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }
}
```

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/TaskCacVariantModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskCacVariantModel extends Model
{
    use HasUuids;

    protected $table = 'task_cac_variants';
    public $timestamps = false;

    protected $fillable = [
        'id', 'task_id', 'complexity_level', 'scenario_text',
        'scaffolding_text_low', 'scaffolding_text_mid', 'scaffolding_text_high',
        'context_text_low', 'context_text_mid', 'context_text_high',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }
}
```

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/TaskDependencyModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDependencyModel extends Model
{
    use HasUuids;

    protected $table = 'task_dependencies';
    public $timestamps = false;

    protected $fillable = [
        'id', 'task_id', 'prerequisite_task_id',
    ];

    // The task that HAS the prerequisite.
    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }

    // The task that MUST be done first. Same table, different foreign key —
    // this is a self-referential relationship, so we name the method for the
    // role it plays rather than the table it points at.
    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'prerequisite_task_id');
    }
}
```

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/TaskKnowledgeAnchorModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskKnowledgeAnchorModel extends Model
{
    use HasUuids;

    protected $table = 'task_knowledge_anchors';
    public $timestamps = false;

    protected $fillable = [
        'id', 'task_id', 'concept_id', 'concept_name', 'domain',
        'application_expectation', 'is_required', 'remediation_hint',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }

    public function conceptTag(): BelongsTo
    {
        return $this->belongsTo(ConceptTagModel::class, 'concept_id');
    }
}
```

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/TaskGuidancePromptModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskGuidancePromptModel extends Model
{
    use HasUuids;

    protected $table = 'task_guidance_prompts';
    public $timestamps = false;

    protected $fillable = [
        'id', 'task_id', 'trigger_dimension', 'prompt_text',
        'autonomy_level_filter', 'delivery_mode', 'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }
}
```

- [ ] All five task-child models created; `$timestamps = false` on each; `TaskDependencyModel` has both self-referential relations; `TaskKnowledgeAnchorModel` links to `ConceptTagModel`.

---

## 8. Simulation — Rubric Models & EvalEngine

### 8.1 — `RubricSetModel`

One-to-one with a project (`project_id` is unique). Owns many criteria.

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/RubricSetModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RubricSetModel extends Model
{
    use HasUuids;

    protected $table = 'rubric_sets';

    protected $fillable = [
        'id', 'project_id', 'version',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplateModel::class, 'project_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(RubricCriterionModel::class, 'rubric_set_id');
    }
}
```

- [ ] `RubricSetModel` created.

### 8.2 — `RubricCriterionModel`

The single richest join in the system: it belongs to a **rubric set**, a **task**, and a **competence dimension** (via the string FK `parent_dimension_id`). It holds the four performance-level descriptions (`beginning`/`developing`/`proficient`/`distinguished`) and precise decimal weights. No timestamps.

`src/Simulation/Infrastructure/Persistence/Eloquent/Model/RubricCriterionModel.php`
```php
<?php

namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\CompetenceDimensionModel;

class RubricCriterionModel extends Model
{
    use HasUuids;

    protected $table = 'rubric_criteria';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'rubric_set_id',
        'task_id',
        'task_dimension_label',
        'parent_dimension_id',
        'complexity_level',
        'criterion_text',
        'weight',
        'dimension_weight',
        'claude_detection_hint',
        'distinguished_description',
        'proficient_description',
        'developing_description',
        'beginning_description',
        'is_architectural',
        'is_planning_layer',
        'reference_doc_anchor',
    ];

    protected $casts = [
        'weight'            => 'decimal:3',
        'dimension_weight'  => 'decimal:3',
        'is_architectural'  => 'boolean',
        'is_planning_layer' => 'boolean',
    ];

    public function rubricSet(): BelongsTo
    {
        return $this->belongsTo(RubricSetModel::class, 'rubric_set_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(TaskModel::class, 'task_id');
    }

    // Cross-context reference UP to Competency — allowed, because Simulation
    // legitimately depends on the competence vocabulary. Note the third arg:
    // the owner key is 'id' on a STRING primary key, not a UUID.
    public function parentDimension(): BelongsTo
    {
        return $this->belongsTo(CompetenceDimensionModel::class, 'parent_dimension_id', 'id');
    }
}
```

- [ ] `RubricCriterionModel` created; `decimal:3` weight casts, three `belongsTo` relations, `$timestamps = false`.

### 8.3 — `FollowUpPromptTemplateModel` (EvalEngine)

Standalone authored templates for evaluation follow-ups. No timestamps, no relationships.

`src/EvalEngine/Infrastructure/Persistence/Eloquent/Model/FollowUpPromptTemplateModel.php`
```php
<?php

namespace Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FollowUpPromptTemplateModel extends Model
{
    use HasUuids;

    protected $table = 'follow_up_prompt_templates';
    public $timestamps = false;

    protected $fillable = [
        'id', 'domain', 'prompt_text', 'trigger_condition', 'is_anti_copy',
    ];

    protected $casts = [
        'is_anti_copy' => 'boolean',
    ];
}
```

- [ ] `FollowUpPromptTemplateModel` created in EvalEngine.

---

## 9. Deferred: Enum Casts Not Done in This Phase

Several DB `enum` columns have **no** matching PHP backed enum yet, so they stay as plain strings for now: the CAC levels (`low/mid/high`) on tasks, cac variants, guidance prompts, rubric criteria; `situation_trigger_type`, `default_autonomy_level`, `material_type`, `document_type`, `phase_gate`, `default_priority`, `delivery_mode`, `concept_tags.source`, and `task_expected_deliverables.type` (the four-layer deliverable type).

This is a **conscious scope decision**, not an oversight. The plan's Phase 3 cast list only requires array casts; the three enums we *did* cast already existed. Creating ten new backed enums is domain work best done when the logic that *uses* them arrives — CAC-level enums in the Phase 6 execution engine, the deliverable-type enum in the Phase 7 submission system. Casting them now, with no consumers, would be inventing scope ahead of need. When those phases arrive, add the enum in the module's `Domain/` and swap the string for an enum cast in one line.

---

## 10. Verification Checklist

There's no seeded content yet (that's Phase 4), so verification here is **structural** — the models resolve, autoload cleanly, and their casts/relationships are wired. Run these in order.

**Autoload integrity**
- [ ] `php composer.phar dump-autoload -o` runs with **no** "does not comply with psr-4" warnings. (This is the check that catches a wrong namespace or misplaced file — the same class of bug as the `LearnerModel` move.)

**Models resolve** — `php artisan tinker`, then:
- [ ] `new \Src\Competency\Infrastructure\Persistence\Eloquent\Model\CompetenceDimensionModel;` — instantiates.
- [ ] `(new \Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel)->getCasts();` — shows the five array casts + the `DifficultyLevel` enum cast + booleans.
- [ ] `(new \Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel)->getCasts();` — shows `task_type => ...TaskType`.

**Casts & keys**
- [ ] `(new CompetenceDimensionModel)->getKeyType()` returns `'string'` and `->getIncrementing()` returns `false`.
- [ ] Every model with a `uuid` PK returns `getIncrementing() === false` (HasUuids handles this).
- [ ] `(new BacklogItemTemplateModel)->usesTimestamps()` returns `false` (spot-check one no-timestamp model).

**Relationships return the right types** (no DB rows needed — this checks the relation objects build):
- [ ] `(new ProjectTemplateModel)->scenarios()` is a `HasMany`; `->organisation()` is a `BelongsTo`; `->rubricSet()` is a `HasOne`.
- [ ] `(new TaskModel)->rubricCriteria()` is a `HasMany` (7 relations total).
- [ ] `(new TaskDependencyModel)->prerequisite()` and `->task()` are both `BelongsTo` pointing at `tasks`.
- [ ] `(new RubricCriterionModel)->parentDimension()` is a `BelongsTo` and its owner key is `id` (string).

**Structural sanity**
- [ ] `\Illuminate\Support\Facades\Schema::hasTable('rubric_criteria')` is `true` for each modelled table (confirms model ↔ table name match).
- [ ] Known Issues section (§2) all boxes checked — especially the `LearnerRepository` binding and the `CommandBus` singleton.

**Rollback / no-migration check**
- [ ] This phase added **no migrations** — confirm `git status` shows only new files under `src/**/Eloquent/Model/` plus the two provider edits and the administrator-migration deletions. If a migration appears, something from a later phase leaked in.

---

## 11. What the Next Phase Builds on Top of This

Phase 4 (Content Management / Admin UI) is the first phase that *uses* these models through controllers. It will:
- add `StoreProjectRequest`-style form requests and a thin `ProjectService` that wraps project + rubric-set creation in one transaction (which is why `ProjectTemplateModel` and `RubricSetModel` both needed to exist first);
- build the HTMX admin screens for authoring projects, scenarios, tasks, criteria, and vault items;
- seed the **six real competence dimensions** and the **MedQueue** sample project — the moment these models stop being empty shells and start holding content.

Phase 3 deliberately stops at "the tables are addressable." No behaviour, no validation, no transactions — those belong with the use cases that need them, and adding them now would be guessing at requirements Phase 4 will make concrete.

---

## 12. Scope Discipline Self-Check (Guidance Prompt §7)

Reporting each box before you accept this document:

- [x] **Every table introduced exists in Data Model v2 with the same name and fields.** No new tables — this phase only models Phase 2's existing tables. Verified each `$table` and `$fillable` against the live migrations.
- [x] **Every table assigned to the correct bounded context.** Competency (dimensions, role defs), Simulation (project/scenario/task/rubric blueprints), EvalEngine (follow-up prompts) — per the Integration Spec map. The one arguable case (`follow_up_prompt_templates`) is flagged as an open decision in §4, not silently decided.
- [x] **No table from a later phase pulled forward.** `LearnerModel` was *not* extended with `LearnerProfile`/`RoleEnrolment` relations (those tables are Phase 5–6). Relationships only reference tables that exist now.
- [x] **No forbidden classes for the phase's scope.** This *is* the models phase; only Eloquent models were created (plus the two provider fixes from Known Issues). No controllers, no domain entities, no repositories.
- [x] **The `users`/`learners` split respected, not "corrected."** Untouched. `ProjectTemplate.organisation_id` → `OrganizationModel` (table `organisations`); no identity tables merged or split.
- [x] **Every enum cast matches the DB enum exactly.** Only `task_type`, `difficulty_level`, `category` cast — each verified case-for-case against its migration's `enum([...])`. All other enum columns left as strings (§9) rather than invent enums.
- [x] **Every new domain enum mirrors its DB enum.** No new enums created this phase (deferred, §9).
- [x] **FK/relationship reasoning stated, not just declared.** Self-referential `TaskDependency`, the string-key `parentDimension`, the deliberately one-directional Competency↔Simulation link, and the project↔rubric_set ownership direction are each explained.
- [x] **Closes with a mechanically runnable verification checklist** (§10).
- [x] **Anything unresolved is listed as an open question**, not decided — the `follow_up_prompt_templates` context placement (§4).

One box needs *your* confirmation, not mine: the **`follow_up_prompt_templates` → EvalEngine vs Simulation** placement in §4. Tell me which you want and I'll lock it (and note it in the guidance file's §4 if you diverge from the map).
