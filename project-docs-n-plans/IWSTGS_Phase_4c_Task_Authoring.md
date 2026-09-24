# IWSTGS — Phase 4c: Task Authoring

> **Goal:** author `Task`s under a scenario. The `Task` is the richest aggregate in the system — it owns **five child collections**: expected deliverables, CAC variants, dependencies, knowledge anchors, and guidance prompts. This is where **Decision 3** (guidance-prompt authoring) lands.
>
> There is **no new pattern** here — it's the 4b child-collection sync applied five times over one root. So this doc gives full code for the aggregate, the repository sync, and one representative child end-to-end (guidance prompts, because that's Decision 3), and specifies the other four compactly. Rubric criteria — which reach across into the Competency context — move to **4d**; artifact vault + the MedQueue seeder to **4e**.

---

## 0. Prerequisites

- [ ] **The bus fix.** Still on `str_replace` in your repo. Nothing authored in 4a–4c dispatches until both buses use `preg_replace('/Command$/', 'Handler', …)` / `'/Query$/'`. (4a §1.1.)
- [ ] 4a (Project) and 4b (Scenario) slices in place; `CacLevel` enum from 4b §2 exists at `Src\Simulation\Domain\Cac\CacLevel`. If it isn't there yet, create it now — 4c leans on it heavily.
- [ ] The Phase-3 `TaskModel` and the five child models exist (note your naming: **`TaskCacVariationModel`** for the `task_cac_variants` table).

---

## 1. New Enums + Cast Activation

Two new backed enums; the rest reuse `CacLevel` and the existing `TaskType`.

`src/Simulation/Domain/Task/DeliverableType.php`
```php
<?php
namespace Src\Simulation\Domain\Task;

enum DeliverableType: string
{
    case WrittenExplanation = 'written_explanation';
    case Artifact           = 'artifact';
    case Code               = 'code';
    case Diagram            = 'diagram';
    case Document           = 'document';
}
```

`src/Simulation/Domain/Task/DeliveryMode.php` (Decision 3)
```php
<?php
namespace Src\Simulation\Domain\Task;

enum DeliveryMode: string
{
    case Proactive = 'proactive';
    case Reactive  = 'reactive';
}
```

Activate the Phase-3 §9 deferrals on the models (`$casts` additions):
- `TaskModel`: `'fixed_complexity' => CacLevel::class`, `'fixed_autonomy' => CacLevel::class`, `'fixed_context_fidelity' => CacLevel::class` *(all nullable — Eloquent returns `null` untouched)*.
- `TaskCacVariationModel`: `'complexity_level' => CacLevel::class`.
- `TaskExpectedDeliverableModel`: `'type' => DeliverableType::class`.
- `TaskGuidancePromptModel`: `'delivery_mode' => DeliveryMode::class`, `'autonomy_level_filter' => CacLevel::class`.

- [ ] Two enums created; four models' casts extended.

---

## 2. Domain — the five child entities

All are child objects reached only through the `Task` root. Each carries its own `id` (so it can be targeted for removal) and a `toPrimitives()`. They're small; here they are together.

`src/Simulation/Domain/Task/` :
```php
// ExpectedDeliverable.php
final class ExpectedDeliverable
{
    public function __construct(
        private readonly string $id,
        private DeliverableType $type,
        private string $label,
        private ?string $description,
        private bool $isRequired,
        private int $displayOrder,
    ) {}
    public function id(): string { return $this->id; }
    public function toPrimitives(): array {
        return ['id'=>$this->id,'type'=>$this->type->value,'label'=>$this->label,
                'description'=>$this->description,'is_required'=>$this->isRequired,'display_order'=>$this->displayOrder];
    }
}

// CacVariant.php  (one per complexity level; unique(task_id, complexity_level))
final class CacVariant
{
    public function __construct(
        private readonly string $id,
        private CacLevel $complexityLevel,
        private string $scenarioText,
        private ?string $scaffoldingTextLow, private ?string $scaffoldingTextMid, private ?string $scaffoldingTextHigh,
        private ?string $contextTextLow, private ?string $contextTextMid, private ?string $contextTextHigh,
    ) {}
    public function id(): string { return $this->id; }
    public function toPrimitives(): array {
        return ['id'=>$this->id,'complexity_level'=>$this->complexityLevel->value,'scenario_text'=>$this->scenarioText,
                'scaffolding_text_low'=>$this->scaffoldingTextLow,'scaffolding_text_mid'=>$this->scaffoldingTextMid,'scaffolding_text_high'=>$this->scaffoldingTextHigh,
                'context_text_low'=>$this->contextTextLow,'context_text_mid'=>$this->contextTextMid,'context_text_high'=>$this->contextTextHigh];
    }
}

// TaskDependencyLink.php  (self-referential: this task depends on prerequisite_task_id)
final class TaskDependencyLink
{
    public function __construct(private readonly string $id, private string $prerequisiteTaskId) {}
    public function id(): string { return $this->id; }
    public function prerequisiteTaskId(): string { return $this->prerequisiteTaskId; }
    public function toPrimitives(): array { return ['id'=>$this->id,'prerequisite_task_id'=>$this->prerequisiteTaskId]; }
}

// KnowledgeAnchor.php  (references a concept_tag by concept_id)
final class KnowledgeAnchor
{
    public function __construct(
        private readonly string $id,
        private ?string $conceptId,
        private string $conceptName,
        private ?string $domain,
        private ?string $applicationExpectation,
        private bool $isRequired,
        private ?string $remediationHint,
    ) {}
    public function id(): string { return $this->id; }
    public function toPrimitives(): array {
        return ['id'=>$this->id,'concept_id'=>$this->conceptId,'concept_name'=>$this->conceptName,'domain'=>$this->domain,
                'application_expectation'=>$this->applicationExpectation,'is_required'=>$this->isRequired,'remediation_hint'=>$this->remediationHint];
    }
}

// GuidancePrompt.php  (Decision 3 — pre-authored, deterministically selected at runtime)
final class GuidancePrompt
{
    public function __construct(
        private readonly string $id,
        private string $triggerDimension,
        private string $promptText,
        private ?CacLevel $autonomyLevelFilter,
        private DeliveryMode $deliveryMode,
        private int $displayOrder,
    ) {}
    public function id(): string { return $this->id; }
    public function toPrimitives(): array {
        return ['id'=>$this->id,'trigger_dimension'=>$this->triggerDimension,'prompt_text'=>$this->promptText,
                'autonomy_level_filter'=>$this->autonomyLevelFilter?->value,'delivery_mode'=>$this->deliveryMode->value,'display_order'=>$this->displayOrder];
    }
}
```

- [ ] Five child entities + `TaskId` (copy 4a's VO shape) created.

---

## 3. Domain — the `Task` aggregate root

Holds all five collections; each has typed `add…()` / `remove…ById()` methods. The pattern is identical to 4b's reference materials, just five times.

`src/Simulation/Domain/Task/Task.php`
```php
<?php
namespace Src\Simulation\Domain\Task;

use Src\Shared\Domain\AggregateRoot;
use Src\Simulation\Domain\Cac\CacLevel;

final class Task extends AggregateRoot
{
    public function __construct(
        private readonly TaskId $id,
        private readonly string $scenarioId,
        private int    $sequenceOrder,
        private string $title,
        private string $taskBrief,
        private ?string $domain,
        private TaskType $taskType,
        private array  $roleTags,
        private array  $tools,
        private array  $prerequisiteConcepts,
        private bool   $isCacRuntimeSet,
        private ?CacLevel $fixedComplexity,
        private ?CacLevel $fixedAutonomy,
        private ?CacLevel $fixedContextFidelity,
        private array  $consequenceTaskIds,
        private array  $suggestionTaskIds,
        private bool   $isArchitectural,
        private bool   $planningLayerActive,
        private ?array $codeExecutionConfig,
        private ?string $modelResponseSummary,
        private ?int   $timeLimitMinutes,
        private bool   $isPublished,
        private bool   $isActive,
        // children
        private array $expectedDeliverables = [],
        private array $cacVariants = [],
        private array $dependencies = [],
        private array $knowledgeAnchors = [],
        private array $guidancePrompts = [],
    ) {}

    public static function create(
        TaskId $id, string $scenarioId, int $sequenceOrder, string $title, string $taskBrief,
        TaskType $taskType, bool $isCacRuntimeSet, bool $isArchitectural, bool $planningLayerActive,
        ?string $domain = null, array $roleTags = [], array $tools = [], array $prerequisiteConcepts = [],
        ?CacLevel $fixedComplexity = null, ?CacLevel $fixedAutonomy = null, ?CacLevel $fixedContextFidelity = null,
        array $consequenceTaskIds = [], array $suggestionTaskIds = [], ?array $codeExecutionConfig = null,
        ?string $modelResponseSummary = null, ?int $timeLimitMinutes = null,
    ): self {
        $t = new self(
            $id, $scenarioId, $sequenceOrder, $title, $taskBrief, $domain, $taskType,
            $roleTags, $tools, $prerequisiteConcepts, $isCacRuntimeSet,
            $fixedComplexity, $fixedAutonomy, $fixedContextFidelity,
            $consequenceTaskIds, $suggestionTaskIds, $isArchitectural, $planningLayerActive,
            $codeExecutionConfig, $modelResponseSummary, $timeLimitMinutes,
            isPublished: false, isActive: true,
        );
        $t->recordEvent(new TaskCreated((string) $id, $scenarioId, $title));
        return $t;
    }

    // reconstitute(...) — mirror create() but take all fields incl. isPublished/isActive
    //                     and the five child arrays. (Same shape as 4b.)

    // --- child management ---
    public function addExpectedDeliverable(ExpectedDeliverable $d): void { $this->expectedDeliverables[] = $d; }
    public function addCacVariant(CacVariant $v): void { $this->cacVariants[] = $v; }
    public function addDependency(TaskDependencyLink $d): void { $this->dependencies[] = $d; }
    public function addKnowledgeAnchor(KnowledgeAnchor $a): void { $this->knowledgeAnchors[] = $a; }
    public function addGuidancePrompt(GuidancePrompt $p): void { $this->guidancePrompts[] = $p; }

    public function removeChild(string $collection, string $childId): void
    {
        $map = [
            'deliverables' => 'expectedDeliverables', 'cacVariants' => 'cacVariants',
            'dependencies' => 'dependencies', 'anchors' => 'knowledgeAnchors', 'prompts' => 'guidancePrompts',
        ];
        $prop = $map[$collection] ?? null;
        if ($prop === null) return;
        $this->$prop = array_values(array_filter($this->$prop, fn ($c) => $c->id() !== $childId));
    }

    // --- own behaviour ---
    public function rename(string $t): void { $this->title = $t; }
    public function publish(): void { $this->isPublished = true; }
    public function unpublish(): void { $this->isPublished = false; }

    public function id(): string { return (string) $this->id; }
    public function isPublished(): bool { return $this->isPublished; }
    public function expectedDeliverables(): array { return $this->expectedDeliverables; }
    public function cacVariants(): array { return $this->cacVariants; }
    public function dependencies(): array { return $this->dependencies; }
    public function knowledgeAnchors(): array { return $this->knowledgeAnchors; }
    public function guidancePrompts(): array { return $this->guidancePrompts; }

    public function toPrimitives(): array
    {
        return [
            'id' => (string) $this->id, 'scenario_id' => $this->scenarioId, 'sequence_order' => $this->sequenceOrder,
            'title' => $this->title, 'task_brief' => $this->taskBrief, 'domain' => $this->domain,
            'task_type' => $this->taskType->value, 'role_tags' => $this->roleTags, 'tools' => $this->tools,
            'prerequisite_concepts' => $this->prerequisiteConcepts, 'is_cac_runtime_set' => $this->isCacRuntimeSet,
            'fixed_complexity' => $this->fixedComplexity?->value, 'fixed_autonomy' => $this->fixedAutonomy?->value,
            'fixed_context_fidelity' => $this->fixedContextFidelity?->value,
            'consequence_task_ids' => $this->consequenceTaskIds, 'suggestion_task_ids' => $this->suggestionTaskIds,
            'is_architectural' => $this->isArchitectural, 'planning_layer_active' => $this->planningLayerActive,
            'code_execution_config' => $this->codeExecutionConfig, 'model_response_summary' => $this->modelResponseSummary,
            'time_limit_minutes' => $this->timeLimitMinutes, 'is_published' => $this->isPublished, 'is_active' => $this->isActive,
        ];
    }
}
```

**A modelling note worth pausing on (CAC).** The `is_cac_runtime_set` flag decides whether the three `fixed_*` columns are used (author pins the CAC) or ignored (the runtime engine sets CAC per learner, in Phase 6). Authoring captures both possibilities; nothing here *computes* CAC — that's Phase 6. The invariant "if `is_cac_runtime_set` is false, the three `fixed_*` must be present" is a good candidate for a guard in `create()` later; for now the form enforces it.

`TaskCreated` event + `TaskRepository` interface (`save`, `findById`, `findByScenario`, `nextSequenceOrder`) — same shapes as 4b's scenario equivalents.

- [ ] `Task`, `TaskCreated`, `TaskRepository` created.

---

## 4. Infrastructure — mapper & the five-collection sync

`TaskMapper::toEntity()` reconstitutes the task and maps each of the five loaded relations into its child objects (same as 4b's single-collection map, five times).

`EloquentTaskRepository::save()` wraps everything in one transaction and reconciles **all five** child tables with the same upsert-present / delete-missing routine. To avoid five copy-pasted blocks, factor the sync:

```php
public function save(Task $task): void
{
    DB::transaction(function () use ($task) {
        TaskModel::updateOrCreate(['id' => $task->id()], $task->toPrimitives());

        $this->sync(TaskExpectedDeliverableModel::class, $task->id(), $task->expectedDeliverables());
        $this->sync(TaskCacVariationModel::class,        $task->id(), $task->cacVariants());
        $this->sync(TaskDependencyModel::class,          $task->id(), $task->dependencies());
        $this->sync(TaskKnowledgeAnchorModel::class,     $task->id(), $task->knowledgeAnchors());
        $this->sync(TaskGuidancePromptModel::class,      $task->id(), $task->guidancePrompts());
    });
}

/** Upsert the children present; delete the ones removed. */
private function sync(string $modelClass, string $taskId, array $children): void
{
    $keepIds = array_map(fn ($c) => $c->id(), $children);

    $modelClass::where('task_id', $taskId)
        ->whereNotIn('id', $keepIds ?: ['__none__'])
        ->delete();

    foreach ($children as $child) {
        $modelClass::updateOrCreate(
            ['id' => $child->id()],
            ['task_id' => $taskId] + $child->toPrimitives(),
        );
    }
}
```
> `findById()` / `findByScenario()` eager-load all five relations: `->with(['expectedDeliverables','cacVariants','dependencies','knowledgeAnchors','guidancePrompts'])` — the relation names you defined on `TaskModel` in Phase 3. Bind `TaskRepository → EloquentTaskRepository` in `SimulationServiceProvider::register()`.

- [ ] Mapper, repository (with factored `sync`), binding done.

---

## 5. Application — commands & queries

Task core: `CreateTask`, `UpdateTask`, `PublishTask` (same shape as 4a/4b). Queries: `ListTasksByScenario`, `GetTask`.

Child management: an add + remove command per collection. They're all the "load aggregate → mutate → save" shape. **Here's the Decision-3 one in full** — guidance prompts:

`Application/Command/AddGuidancePrompt/AddGuidancePromptCommand.php`
```php
<?php
namespace Src\Simulation\Application\Command\AddGuidancePrompt;

final class AddGuidancePromptCommand
{
    public function __construct(
        public readonly string  $taskId,
        public readonly string  $triggerDimension,
        public readonly string  $promptText,
        public readonly string  $deliveryMode,          // 'proactive' | 'reactive'
        public readonly ?string $autonomyLevelFilter,   // 'low'|'mid'|'high'|null
        public readonly int     $displayOrder = 0,
    ) {}
}
```

`Application/Command/AddGuidancePrompt/AddGuidancePromptHandler.php`
```php
<?php
namespace Src\Simulation\Application\Command\AddGuidancePrompt;

use Src\Shared\Infrastructure\Id\UuidGenerator;
use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Exceptions\TaskNotFoundException;
use Src\Simulation\Domain\Task\DeliveryMode;
use Src\Simulation\Domain\Task\GuidancePrompt;
use Src\Simulation\Domain\Task\TaskId;
use Src\Simulation\Domain\Task\TaskRepository;

final class AddGuidancePromptHandler
{
    public function __construct(private readonly TaskRepository $repository) {}

    public function handle(AddGuidancePromptCommand $command): void
    {
        $task = $this->repository->findById(TaskId::fromString($command->taskId));
        if ($task === null) {
            throw new TaskNotFoundException($command->taskId);
        }

        $task->addGuidancePrompt(new GuidancePrompt(
            id: (string) UuidGenerator::generate(),
            triggerDimension: $command->triggerDimension,
            promptText: $command->promptText,
            autonomyLevelFilter: $command->autonomyLevelFilter ? CacLevel::from($command->autonomyLevelFilter) : null,
            deliveryMode: DeliveryMode::from($command->deliveryMode),
            displayOrder: $command->displayOrder,
        ));

        $this->repository->save($task);
    }
}
```
> **Decision 3 in the authoring model:** the prompt is *content*, not AI-generated — `trigger_dimension` + `autonomy_level_filter` + `delivery_mode` are exactly the fields Phase 6's deterministic selector will match against (word-count ≥ 50 / idle ≥ 5 min → pick a `proactive` prompt whose dimension + autonomy fit). No AI, no partial eval. 4c just captures those fields cleanly.

The other four children (`AddExpectedDeliverable`, `AddCacVariant`, `AddDependency`, `AddKnowledgeAnchor`) are the same handler shape with their own fields. Removal is one shared command: `RemoveTaskChildCommand(taskId, collection, childId)` → loads the task, `$task->removeChild($collection, $childId)`, saves.

Add `TaskNotFoundException` at `Domain/Exceptions/` (mirror `ProjectNotFoundException`).

- [ ] Task CRUD (3) + child-add commands (5) + shared remove + two queries created.

---

## 6. Presentation — controller, requests, routes

`TaskController` under scenarios (`/admin/scenarios/{scenario}/tasks`), same controller style as 4b. `StoreTaskRequest` validates: `title`, `task_brief` required; `task_type` `in:core,consequence,suggestion,diagnostic_scenario,diagnostic_consequence`; `is_cac_runtime_set` boolean; the `fixed_*` fields `nullable|in:low,mid,high` **required unless `is_cac_runtime_set` is true** (`required_if:is_cac_runtime_set,false`); the json-array fields (`role_tags`, `tools`, etc.) as arrays. Child form requests validate each child's enum against its DB values (e.g. `delivery_mode` `in:proactive,reactive`).

Routes (add to `routes/simulation.php`, admin group): task CRUD nested under `scenarios/{scenario}/tasks`, plus HTMX child endpoints `tasks/{id}/prompts` (POST) and `tasks/{id}/children/{collection}/{childId}` (DELETE), etc.

- [ ] Controller, task + child form requests, routes done.

---

## 7. Views

Task index/create/edit + `_row` (publish toggle) exactly as 4b. The edit page hosts **five** nested HTMX sub-forms — one per child collection — each identical in mechanics to 4b's reference-material sub-form (`hx-post` to add → append row; `hx-delete` to remove → swap out row). The **guidance-prompt sub-form** (Decision 3) has: a `trigger_dimension` input, a `prompt_text` textarea, a `delivery_mode` select (`proactive`/`reactive`), and an `autonomy_level_filter` select (`—`/`low`/`mid`/`high`). The **CAC-variant sub-form** offers at most three rows (one per `low`/`mid`/`high`) since `(task_id, complexity_level)` is unique — disable a level once it's been added.

- [ ] Task views + five child sub-forms created.

---

## 8. Verification Checklist

- [ ] Bus fix confirmed (a dispatch round-trips).
- [ ] `composer dump-autoload -o` clean on the new Task classes + enums.
- [ ] `route:list --path=admin` shows `admin.scenarios.tasks.*` behind `role:content_author`.
- [ ] Create a task with `is_cac_runtime_set=false` → the three `fixed_*` are required and persist as `CacLevel` casts; with `true` → they're accepted null.
- [ ] Add one of each child (deliverable, CAC variant, dependency, anchor, guidance prompt) → all five rows persist with the right `task_id`; remove one → gone (proves the factored `sync` across all five tables).
- [ ] Adding a second CAC variant for an already-used level is blocked (unique `(task_id, complexity_level)`) — surface it as a friendly error, not a 500.
- [ ] `GetTask` returns the aggregate with all five collections eager-loaded (no N+1).
- [ ] Guidance prompt round-trips: `delivery_mode` reads back as `DeliveryMode` case; `autonomy_level_filter` as `CacLevel` or null.
- [ ] No Eloquent model imported under `Application/`.

---

## 9. What 4d / 4e Build Next

- **4d — Rubric criteria authoring.** The `RubricCriterion` per task reaches *across* into the Competency context (`parent_dimension_id` → `competence_dimensions`, a string FK) and carries the four performance-level descriptions (`beginning`/`developing`/`proficient`/`distinguished`) and decimal weights. It's distinct enough — cross-context reference + evaluation semantics — to stand alone.
- **4e — Artifact vault + `MedQueueSeeder`**: the seeder finally fills projects → scenarios → tasks → criteria with real content, turning this admin suite into the thing that loads MedQueue.

---

## 10. Scope Discipline Self-Check (Guidance §7)

- [x] **No new tables** — writes only to `tasks` + its five child tables (all Phase-3). Columns/enums verified against the live migrations.
- [x] **Correct context** — all Simulation; the self-referential `task_dependencies` and the `concept_id` anchor FK stay within/adjacent to Simulation vocabulary (rubric's cross-context reach is deferred to 4d).
- [x] **Nothing pulled forward** — CAC is *authored*, not *computed* (Phase 6); guidance-prompt *selection/timing* is Phase 6; only Decision 3's authoring fields are captured.
- [x] **Enums match the DB exactly** — `TaskType` (reused), `DeliverableType`, `DeliveryMode`, `CacLevel` verified case-for-case; casts activated per Phase 3 §9.
- [x] **Pattern reused, not reinvented** — the five child collections are 4b's sync pattern, factored into one `sync()` helper; aggregate/repository/mapper mirror 4a/4b.
- [x] **Aggregate reasoning stated** — all five collections mutated only through the `Task` root, synced in one transaction; the `is_cac_runtime_set` invariant flagged for a future guard.
- [x] **Constraint handling** — CAC-variant `(task_id, complexity_level)` uniqueness surfaced as a friendly error.
- [x] **Runnable verification** (§8).
- [x] **Open decisions** — none newly opened; Decision 3's runtime half explicitly left to Phase 6.

When you've laid down Task authoring, say the word for **4d (rubric criteria)** — the last authoring piece before the MedQueue seeder brings it all to life.
