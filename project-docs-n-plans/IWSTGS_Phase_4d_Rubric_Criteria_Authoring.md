# IWSTGS — Phase 4d: Rubric Criteria Authoring

> **Goal:** author the `RubricCriterion`s that a task is evaluated against — each anchored to a **competence dimension** (a cross-context reference into Competency), scoped to a **complexity level**, carrying the **four performance-level descriptions** (`beginning` / `developing` / `proficient` / `distinguished`) and its weights.
>
> Two things make this distinct from 4b/4c and worth its own doc: it's a **leaf aggregate** (no children of its own), and it needs a **read across a context boundary** — the authoring form's dimension dropdown comes from the Competency context, fetched cleanly through the query bus rather than by importing Competency's internals.

---

## 0. Prerequisites

- [ ] The bus fix (4a §1.1) — still the standing blocker for all dispatch.
- [ ] 4a–4c in place; `CacLevel` (from 4b §2) exists; the Phase-3 `RubricCriterionModel`, `RubricSetModel`, and `CompetenceDimensionModel` exist.

---

## 1. What a Rubric Criterion Is (and the Four Levels)

A criterion says: *"for **this task**, on **this competence dimension**, at **this complexity level**, here's what to look for — and here's what beginning / developing / proficient / distinguished work looks like."* Those four descriptions are the graders' anchors; they map exactly to Data Model v2's `performance_level` enum, and the **pass threshold is `proficient`** (Phase 8's evaluator uses that; 4d only *authors* the descriptions).

Relationships (all by ID — a criterion **references**, never **owns**):
- `rubric_set_id` → the project's single rubric set (resolved from the task).
- `task_id` → the task being evaluated.
- `parent_dimension_id` → a `competence_dimensions` row (string PK like `dim_correctness`) — **cross-context into Competency**.

Because it owns nothing, `RubricCriterion` is a **leaf aggregate**: `create` / `update` / `delete`, no child-collection sync. Simpler than Task.

---

## 2. Competency — a small read slice for the dimension dropdown

The criterion form needs a list of dimensions to choose from. Simulation must not import Competency's Eloquent model, so Competency exposes a read through its own repository + a query the Simulation controller asks via the bus.

`src/Competency/Domain/Dimension/CompetenceDimensionSummary.php` (a read DTO — no identity, just data for the dropdown):
```php
<?php
namespace Src\Competency\Domain\Dimension;

final class CompetenceDimensionSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $shortLabel,
        public readonly string $coreQuestion,
    ) {}
}
```

`src/Competency/Domain/Dimension/CompetenceDimensionRepository.php`
```php
<?php
namespace Src\Competency\Domain\Dimension;

interface CompetenceDimensionRepository
{
    /** @return CompetenceDimensionSummary[] */
    public function all(): array;
}
```

`src/Competency/Infrastructure/Persistence/Eloquent/Repository/EloquentCompetenceDimensionRepository.php`
```php
<?php
namespace Src\Competency\Infrastructure\Persistence\Eloquent\Repository;

use Src\Competency\Domain\Dimension\CompetenceDimensionRepository;
use Src\Competency\Domain\Dimension\CompetenceDimensionSummary;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\CompetenceDimensionModel;

final class EloquentCompetenceDimensionRepository implements CompetenceDimensionRepository
{
    /** @return CompetenceDimensionSummary[] */
    public function all(): array
    {
        return CompetenceDimensionModel::orderBy('sequence_order')
            ->get()
            ->map(fn (CompetenceDimensionModel $m) => new CompetenceDimensionSummary(
                id: $m->id,
                shortLabel: $m->short_label,
                coreQuestion: $m->core_question,
            ))
            ->all();
    }
}
```

Bind it in `CompetencyServiceProvider::register()`:
```php
$this->app->bind(
    \Src\Competency\Domain\Dimension\CompetenceDimensionRepository::class,
    \Src\Competency\Infrastructure\Persistence\Eloquent\Repository\EloquentCompetenceDimensionRepository::class,
);
```

The query (Competency owns it):
```php
// src/Competency/Application/Query/ListCompetenceDimensions/ListCompetenceDimensionsQuery.php
final class ListCompetenceDimensionsQuery {}

// .../ListCompetenceDimensionsHandler.php
final class ListCompetenceDimensionsHandler
{
    public function __construct(private readonly CompetenceDimensionRepository $repository) {}
    /** @return CompetenceDimensionSummary[] */
    public function handle(ListCompetenceDimensionsQuery $query): array
    {
        return $this->repository->all();
    }
}
```
> This is the clean cross-context read: Simulation's controller calls `$queryBus->ask(new ListCompetenceDimensionsQuery())`, and Competency answers. Neither context imports the other's classes beyond the query object and its DTO. (Until the seeder runs in 4e, this list is empty — expected.)

- [ ] Competency read slice (DTO, repository, binding, query+handler) created.

---

## 3. Simulation Domain — the `RubricCriterion` leaf aggregate

`RubricCriterionId` — copy 4a's VO shape (namespace `Src\Simulation\Domain\Rubric`).

`src/Simulation/Domain/Rubric/RubricCriterion.php`
```php
<?php
namespace Src\Simulation\Domain\Rubric;

use Src\Shared\Domain\AggregateRoot;
use Src\Simulation\Domain\Cac\CacLevel;

final class RubricCriterion extends AggregateRoot
{
    public function __construct(
        private readonly RubricCriterionId $id,
        private readonly string $rubricSetId,
        private readonly string $taskId,
        private string  $taskDimensionLabel,
        private string  $parentDimensionId,
        private CacLevel $complexityLevel,
        private string  $criterionText,
        private string  $weight,           // decimal(4,3) — kept as string to preserve precision
        private string  $dimensionWeight,  // decimal(4,3)
        private string  $claudeDetectionHint,
        private string  $distinguishedDescription,
        private string  $proficientDescription,
        private string  $developingDescription,
        private string  $beginningDescription,
        private bool    $isArchitectural,
        private bool    $isPlanningLayer,
        private ?string $referenceDocAnchor,
    ) {}

    public static function create(
        RubricCriterionId $id, string $rubricSetId, string $taskId, string $taskDimensionLabel,
        string $parentDimensionId, CacLevel $complexityLevel, string $criterionText,
        string $weight, string $dimensionWeight, string $claudeDetectionHint,
        string $distinguishedDescription, string $proficientDescription,
        string $developingDescription, string $beginningDescription,
        bool $isArchitectural = false, bool $isPlanningLayer = false, ?string $referenceDocAnchor = null,
    ): self {
        $c = new self(
            $id, $rubricSetId, $taskId, $taskDimensionLabel, $parentDimensionId, $complexityLevel,
            $criterionText, $weight, $dimensionWeight, $claudeDetectionHint,
            $distinguishedDescription, $proficientDescription, $developingDescription, $beginningDescription,
            $isArchitectural, $isPlanningLayer, $referenceDocAnchor,
        );
        $c->recordEvent(new RubricCriterionCreated((string) $id, $taskId, $parentDimensionId));
        return $c;
    }

    // reconstitute(...) — same params, no event (mirror 4a/4b).

    public function updateText(string $criterionText): void { $this->criterionText = $criterionText; }
    public function reweight(string $weight, string $dimensionWeight): void
    {
        $this->weight = $weight;
        $this->dimensionWeight = $dimensionWeight;
    }

    public function id(): string { return (string) $this->id; }
    public function taskId(): string { return $this->taskId; }

    public function toPrimitives(): array
    {
        return [
            'id' => (string) $this->id, 'rubric_set_id' => $this->rubricSetId, 'task_id' => $this->taskId,
            'task_dimension_label' => $this->taskDimensionLabel, 'parent_dimension_id' => $this->parentDimensionId,
            'complexity_level' => $this->complexityLevel->value, 'criterion_text' => $this->criterionText,
            'weight' => $this->weight, 'dimension_weight' => $this->dimensionWeight,
            'claude_detection_hint' => $this->claudeDetectionHint,
            'distinguished_description' => $this->distinguishedDescription,
            'proficient_description' => $this->proficientDescription,
            'developing_description' => $this->developingDescription,
            'beginning_description' => $this->beginningDescription,
            'is_architectural' => $this->isArchitectural, 'is_planning_layer' => $this->isPlanningLayer,
            'reference_doc_anchor' => $this->referenceDocAnchor,
        ];
    }
}
```
> **Why `weight` is a `string`, not a `float`.** The column is `decimal(4,3)`; `decimal:3` on the model returns a string like `"0.250"`. Keeping it a string end-to-end avoids float rounding drift (`0.1 + 0.2 !== 0.3`), which matters because weights are summed during evaluation. Validate the numeric range at the edge (form request), carry it as a precise string through the domain.

`RubricCriterionCreated` event (three readonly props) + repository interface:
```php
<?php
namespace Src\Simulation\Domain\Rubric;

interface RubricCriterionRepository
{
    public function save(RubricCriterion $criterion): void;
    public function findById(RubricCriterionId $id): ?RubricCriterion;
    /** @return RubricCriterion[] */
    public function findByTask(string $taskId): array;
    public function remove(RubricCriterionId $id): void;

    /** Resolve the project's rubric_set for a task: task → scenario → project → rubric_set. */
    public function rubricSetIdForTask(string $taskId): ?string;
}
```

- [ ] `RubricCriterionId`, `RubricCriterion`, `RubricCriterionCreated`, `RubricCriterionRepository` created.

---

## 4. Simulation Infrastructure

`RubricCriterionMapper::toEntity()` — straightforward field mapping (no children), guarding the `complexity_level` enum cast like 4b did.

`EloquentRubricCriterionRepository` — `save()` is a plain `updateOrCreate` (no transaction needed — leaf, no children). The one interesting method is the cross-table resolve, which stays **inside Simulation** (tasks, scenarios, rubric_sets are all Simulation tables, so this join is intra-context and fine):
```php
public function rubricSetIdForTask(string $taskId): ?string
{
    // task_id → tasks.scenario_id → scenario_templates.project_id → rubric_sets.id
    return \Illuminate\Support\Facades\DB::table('tasks')
        ->join('scenario_templates', 'tasks.scenario_id', '=', 'scenario_templates.id')
        ->join('rubric_sets', 'scenario_templates.project_id', '=', 'rubric_sets.project_id')
        ->where('tasks.id', $taskId)
        ->value('rubric_sets.id');
}
```
Bind `RubricCriterionRepository → EloquentRubricCriterionRepository` in `SimulationServiceProvider::register()`.

- [ ] Mapper, repository, binding done.

---

## 5. Application

`CreateRubricCriterion` — the handler resolves the rubric set from the task, then builds the aggregate:
```php
public function handle(CreateRubricCriterionCommand $command): void
{
    $rubricSetId = $this->repository->rubricSetIdForTask($command->taskId);
    if ($rubricSetId === null) {
        throw new RubricSetMissingException($command->taskId); // project has no rubric set yet
    }

    $criterion = RubricCriterion::create(
        id: RubricCriterionId::generate(),
        rubricSetId: $rubricSetId,
        taskId: $command->taskId,
        taskDimensionLabel: $command->taskDimensionLabel,
        parentDimensionId: $command->parentDimensionId,
        complexityLevel: CacLevel::from($command->complexityLevel),
        criterionText: $command->criterionText,
        weight: $command->weight,
        dimensionWeight: $command->dimensionWeight,
        claudeDetectionHint: $command->claudeDetectionHint,
        distinguishedDescription: $command->distinguishedDescription,
        proficientDescription: $command->proficientDescription,
        developingDescription: $command->developingDescription,
        beginningDescription: $command->beginningDescription,
        isArchitectural: $command->isArchitectural,
        isPlanningLayer: $command->isPlanningLayer,
        referenceDocAnchor: $command->referenceDocAnchor,
    );

    $this->repository->save($criterion);
    foreach ($criterion->releaseEvents() as $event) { event($event); }
}
```
Plus `UpdateRubricCriterion`, `RemoveRubricCriterion` (loads, `remove()`), and queries `ListCriteriaByTask(taskId)`, `GetCriterion(id)`. `RubricSetMissingException` in `Domain/Exceptions/`.

- [ ] Create/Update/Remove commands + handlers, two queries + handlers, exception created.

---

## 6. Presentation & Routes

`RubricCriterionController` nested under tasks (`/admin/tasks/{task}/criteria`). `index($taskId)` asks **two** queries — `ListCriteriaByTask` and the cross-context `ListCompetenceDimensions` — and passes both to the view (the criteria list + the dimension options for the form).

`StoreRubricCriterionRequest` rules:
```php
'task_dimension_label'      => ['required','string','max:200'],
'parent_dimension_id'       => ['required','string','max:20'], // exists:competence_dimensions,id once seeded (4e)
'complexity_level'          => ['required','in:low,mid,high'],
'criterion_text'            => ['required','string'],
'weight'                    => ['required','numeric','between:0,1'],
'dimension_weight'          => ['required','numeric','between:0,1'],
'claude_detection_hint'     => ['required','string'],
'distinguished_description' => ['required','string'],
'proficient_description'    => ['required','string'],
'developing_description'    => ['required','string'],
'beginning_description'     => ['required','string'],
'is_architectural'          => ['boolean'],
'is_planning_layer'         => ['boolean'],
'reference_doc_anchor'      => ['nullable','string'],
```
> Add the `exists:competence_dimensions,id` rule on `parent_dimension_id` once the seeder (4e) has populated dimensions — until then it would reject everything.

Routes (admin group in `routes/simulation.php`):
```php
Route::get('tasks/{task}/criteria',            [RubricCriterionController::class,'index'])->name('tasks.criteria.index');
Route::post('tasks/{task}/criteria',           [RubricCriterionController::class,'store'])->name('tasks.criteria.store');
Route::get('tasks/{task}/criteria/{id}/edit',  [RubricCriterionController::class,'edit'])->name('tasks.criteria.edit');
Route::patch('criteria/{id}',                  [RubricCriterionController::class,'update'])->name('criteria.update');
Route::delete('criteria/{id}',                 [RubricCriterionController::class,'destroy'])->name('criteria.destroy');
```

- [ ] Controller, form requests, routes done.

---

## 7. Views

`resources/views/admin/criteria/` — an `index` under the task listing existing criteria (grouped by `complexity_level` reads well), and a create/edit form with:
- a **dimension `<select>`** populated from the `ListCompetenceDimensions` result (`value = id`, label = `shortLabel`, with `coreQuestion` as a hint);
- a **complexity_level** select (`low`/`mid`/`high`);
- `task_dimension_label`, `criterion_text`, `weight`, `dimension_weight`, `claude_detection_hint`;
- the **four performance-level textareas** stacked distinguished → proficient → developing → beginning, each labelled, so the author writes the anchor for each level;
- `is_architectural`, `is_planning_layer` checkboxes, `reference_doc_anchor`.

Removal via HTMX `hx-delete` swapping the row, as in prior phases.

- [ ] Criteria views created.

---

## 8. Verification Checklist

- [ ] Bus fix confirmed.
- [ ] `composer dump-autoload -o` clean on the new Competency + Rubric classes.
- [ ] `route:list --path=admin` shows `admin.tasks.criteria.*` and `admin.criteria.*` behind `role:content_author`.
- [ ] Container resolves both new repositories (`CompetenceDimensionRepository`, `RubricCriterionRepository`).
- [ ] The criterion form's dimension dropdown is fed by the query-bus cross-context read (empty until the 4e seeder — confirm the query returns `[]` without error).
- [ ] Create a criterion for a task → the row persists with the **resolved** `rubric_set_id` (proves `rubricSetIdForTask`), and `weight`/`dimension_weight` read back as precise decimal strings.
- [ ] `ListCriteriaByTask` returns them; delete removes one.
- [ ] A criterion whose task's project has no rubric set surfaces `RubricSetMissingException` gracefully (shouldn't happen — 4a always creates the set — but the guard proves the resolution).
- [ ] No Eloquent model imported under any `Application/`; Simulation does not import any `Competency\...\Model`.

---

## 9. What 4e Builds Next (the payoff)

**4e — Artifact vault authoring + the `MedQueueSeeder`.** The seeder is where all four authoring phases finally *do* something: it inserts the six real `competence_dimensions`, a `role_definitions` set, and the full **MedQueue** project → scenarios → tasks → CAC variants → guidance prompts → **rubric criteria** — turning the empty admin CRUD into a loaded, explorable simulation. Everything you've built in 4a–4d becomes the machinery that seeder drives. That's also the first point where the dimension dropdown here fills up and the `exists:` validation rule can be switched on.

---

## 10. Scope Discipline Self-Check (Guidance §7)

- [x] **No new tables** — writes only to `rubric_criteria` (Phase-3); reads `competence_dimensions`. Columns/enums verified against the live migrations.
- [x] **Correct contexts** — the criterion is Simulation; the dimension read slice is Competency; the cross-context link is a *reference by ID* + a *query-bus read*, never a direct model import.
- [x] **Nothing pulled forward** — the four performance descriptions are *authored*; the `proficient` pass-threshold *evaluation* is Phase 8, not built here.
- [x] **Enums match the DB** — `complexity_level` → `CacLevel` (low/mid/high), verified; no `performance_level` enum invented (it's four text columns at authoring time).
- [x] **Leaf-aggregate + cross-aggregate resolution reasoning stated** — no children; `rubricSetIdForTask` join is intra-Simulation, explained.
- [x] **Precision handling stated** — decimal weights carried as strings to avoid float drift.
- [x] **Patterns reused** — VO/aggregate/repository/mapper/command/handler mirror 4a–4c; the cross-context read mirrors CQRS-via-bus.
- [x] **Runnable verification** (§8), including the `rubric_set_id` resolution and the empty-dropdown-without-error check.
- [x] **Open items flagged** — the `exists:competence_dimensions,id` rule deferred until the 4e seeder populates dimensions, stated explicitly rather than shipped broken.

That's the authoring layer complete (projects → scenarios → tasks → CAC/guidance/deliverables → rubric criteria). Push 4a–4d whenever you're ready and I'll review the whole stack against the repo — the bus fix, the enum-cast activations, the aggregate wiring, and anything that drifted — before we build the 4e seeder that brings MedQueue to life.
