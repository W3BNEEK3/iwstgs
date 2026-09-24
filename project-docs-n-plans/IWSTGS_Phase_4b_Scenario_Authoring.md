# IWSTGS — Phase 4b: Scenario Authoring

> **Goal:** author `ScenarioTemplate`s under a project, including their **reference materials**. This introduces the one genuinely new DDD lesson beyond 4a — an aggregate that owns a *collection* of children, kept consistent through a single transactional `save()` — plus three backed enums, and the reusable `<x-reference-material>` component from your Decision 5.
>
> Structurally this mirrors 4a's Project slice. Where the shape is identical (value object, basic CRUD command/handler, form request), I'll say "as in 4a" rather than re-explain. The teaching budget goes to what's new: **child-collection sync**, **enum activation**, and **sequence-order handling** (your Decision 4).

---

## 0. Prerequisite — the Bus Fix Still Isn't Applied

4a's files are all committed, but the `str_replace` bus bug (4a §1.1) was **not** fixed — both buses still read `str_replace('Command'/'Query', 'Handler', …)`. That rewrites the `\Command\`/`\Query\` folder segment, so every `dispatch()`/`ask()` resolves to a class in a non-existent `\Handler\` folder. Nothing in 4a or 4b can run until this is fixed:

```php
// SynchronousCommandBus::dispatch()
$handlerClass = preg_replace('/Command$/', 'Handler', get_class($command));

// SynchronousQueryBus::ask()
$handlerClass = preg_replace('/Query$/', 'Handler', get_class($query));
```

The `$` anchor replaces only the trailing suffix, leaving the folder intact. **Verify by registering a user or creating a project — if that works, the fix is in.** (It likely hasn't worked yet, which is why this is easy to miss.)

- [ ] Both buses use `preg_replace('/…$/', …)`; a dispatch round-trips successfully.

---

## 1. Where Your Section-20 Decisions Land Here

- **D4 (strictly sequential)** — the `scenario_templates.unique(project_id, sequence_order)` constraint already exists; 4b's create flow must set `sequence_order` and handle a duplicate gracefully (§6).
- **D5 (reference-material component)** — built here as `<x-reference-material>` (§9) and used in an admin preview; Phase 6 reuses it in the learner sidebar.
- **D2 (situation-trigger modal)** — 4b *authors* `situation_trigger` + `situation_trigger_type`; the learner-facing modal is Phase 6 runtime and is **not** built here (that would pull Phase 6 forward).
- **D1 / D3** — not in 4b (Phase 6 / Task-authoring 4c respectively).

---

## 2. Enums — Activating the Phase-3 Deferrals

Phase 3 §9 deferred these casts "until the logic that uses them arrives." 4b is that moment. Create three backed enums whose cases match the DB `enum` values **exactly**.

`src/Simulation/Domain/Scenario/SituationTriggerType.php`
```php
<?php
namespace Src\Simulation\Domain\Scenario;

enum SituationTriggerType: string
{
    case SlackMessage   = 'slack_message';
    case Email          = 'email';
    case MeetingSummary = 'meeting_summary';
    case IncidentReport = 'incident_report';
    case Ticket         = 'ticket';
    case HandoverNote   = 'handover_note';
}
```

`src/Simulation/Domain/Scenario/MaterialType.php`
```php
<?php
namespace Src\Simulation\Domain\Scenario;

enum MaterialType: string
{
    case Document     = 'document';
    case Email        = 'email';
    case SlackMessage = 'slack_message';
    case Ticket       = 'ticket';
    case Report       = 'report';
    case Notes        = 'notes';
}
```

`src/Simulation/Domain/Cac/CacLevel.php` — the shared low/mid/high scale (autonomy now; complexity & context-fidelity reuse it in 4c):
```php
<?php
namespace Src\Simulation\Domain\Cac;

enum CacLevel: string
{
    case Low  = 'low';
    case Mid  = 'mid';
    case High = 'high';
}
```

Now activate the casts on the Phase-3 models (small addendum):

`ScenarioTemplateModel` — add to `$casts`:
```php
'situation_trigger_type' => \Src\Simulation\Domain\Scenario\SituationTriggerType::class,
'default_autonomy_level' => \Src\Simulation\Domain\Cac\CacLevel::class,
```
`ScenarioReferenceMaterialModel` — add to `$casts`:
```php
'material_type'    => \Src\Simulation\Domain\Scenario\MaterialType::class,
'embedded_signals' => 'array',   // (already present from Phase 3)
```

- [ ] Three enums created; two model `$casts` updated.

---

## 3. Domain — the Scenario aggregate & its children

### 3.1 — `ScenarioTemplateId`

As in 4a's `ProjectTemplateId` (copy it, swap the class name and namespace to `Src\Simulation\Domain\Scenario`).

### 3.2 — `ReferenceMaterial` (child entity within the aggregate)

A reference material has no life outside its scenario, so it's **not** a separate aggregate — it's a child the scenario owns. It carries its own id (so we can target it for removal) but is only ever reached *through* the scenario.

`src/Simulation/Domain/Scenario/ReferenceMaterial.php`
```php
<?php
namespace Src\Simulation\Domain\Scenario;

final class ReferenceMaterial
{
    public function __construct(
        private readonly string $id,
        private MaterialType    $type,
        private string          $title,
        private string          $content,
        private ?array          $embeddedSignals,
        private int             $displayOrder,
    ) {}

    public function id(): string { return $this->id; }

    public function toPrimitives(): array
    {
        return [
            'id'               => $this->id,
            'material_type'    => $this->type->value,
            'title'            => $this->title,
            'content'          => $this->content,
            'embedded_signals' => $this->embeddedSignals,
            'display_order'    => $this->displayOrder,
        ];
    }
}
```

### 3.3 — `ScenarioTemplate` (aggregate root, owns a collection)

The new pattern vs 4a: the root holds `array $referenceMaterials` and exposes `addReferenceMaterial()` / `removeReferenceMaterial()`. All child mutation goes through the root — that's what makes the aggregate the consistency boundary.

`src/Simulation/Domain/Scenario/ScenarioTemplate.php`
```php
<?php
namespace Src\Simulation\Domain\Scenario;

use Src\Shared\Domain\AggregateRoot;
use Src\Simulation\Domain\Cac\CacLevel;

final class ScenarioTemplate extends AggregateRoot
{
    /** @param ReferenceMaterial[] $referenceMaterials */
    public function __construct(
        private readonly ScenarioTemplateId $id,
        private readonly string   $projectId,
        private int               $sequenceOrder,
        private string            $title,
        private string            $narrativeContext,
        private string            $situationTrigger,
        private SituationTriggerType $situationTriggerType,
        private ?string           $learnerRoleLabel,
        private CacLevel          $defaultAutonomyLevel,
        private bool              $isDiagnostic,
        private bool              $isPublished,
        private bool              $isActive,
        private array             $referenceMaterials = [],
    ) {}

    public static function create(
        ScenarioTemplateId $id,
        string $projectId,
        int $sequenceOrder,
        string $title,
        string $narrativeContext,
        string $situationTrigger,
        SituationTriggerType $situationTriggerType,
        CacLevel $defaultAutonomyLevel,
        ?string $learnerRoleLabel = null,
        bool $isDiagnostic = false,
    ): self {
        $s = new self(
            $id, $projectId, $sequenceOrder, $title, $narrativeContext,
            $situationTrigger, $situationTriggerType, $learnerRoleLabel,
            $defaultAutonomyLevel, $isDiagnostic,
            isPublished: false, isActive: true,
        );
        $s->recordEvent(new ScenarioTemplateCreated((string) $id, $projectId, $title));
        return $s;
    }

    public static function reconstitute(
        ScenarioTemplateId $id, string $projectId, int $sequenceOrder, string $title,
        string $narrativeContext, string $situationTrigger,
        SituationTriggerType $situationTriggerType, ?string $learnerRoleLabel,
        CacLevel $defaultAutonomyLevel, bool $isDiagnostic, bool $isPublished,
        bool $isActive, array $referenceMaterials,
    ): self {
        return new self(
            $id, $projectId, $sequenceOrder, $title, $narrativeContext,
            $situationTrigger, $situationTriggerType, $learnerRoleLabel,
            $defaultAutonomyLevel, $isDiagnostic, $isPublished, $isActive,
            $referenceMaterials,
        );
    }

    // --- child management (the aggregate is the consistency boundary) ---
    public function addReferenceMaterial(ReferenceMaterial $m): void
    {
        $this->referenceMaterials[] = $m;
    }

    public function removeReferenceMaterial(string $materialId): void
    {
        $this->referenceMaterials = array_values(array_filter(
            $this->referenceMaterials,
            fn (ReferenceMaterial $m) => $m->id() !== $materialId,
        ));
    }

    // --- own behaviour ---
    public function rename(string $t): void { $this->title = $t; }
    public function publish(): void   { $this->isPublished = true; }
    public function unpublish(): void { $this->isPublished = false; }

    public function id(): string { return (string) $this->id; }
    public function scenarioTemplateId(): ScenarioTemplateId { return $this->id; }
    public function isPublished(): bool { return $this->isPublished; }
    /** @return ReferenceMaterial[] */
    public function referenceMaterials(): array { return $this->referenceMaterials; }

    public function toPrimitives(): array
    {
        return [
            'id'                     => (string) $this->id,
            'project_id'             => $this->projectId,
            'sequence_order'         => $this->sequenceOrder,
            'title'                  => $this->title,
            'narrative_context'      => $this->narrativeContext,
            'situation_trigger'      => $this->situationTrigger,
            'situation_trigger_type' => $this->situationTriggerType->value,
            'learner_role_label'     => $this->learnerRoleLabel,
            'default_autonomy_level' => $this->defaultAutonomyLevel->value,
            'is_diagnostic'          => $this->isDiagnostic,
            'is_published'           => $this->isPublished,
            'is_active'              => $this->isActive,
        ];
    }
}
```

### 3.4 — Event & repository interface

`ScenarioTemplateCreated` — same shape as `ProjectTemplateCreated` (three readonly props: `scenarioId`, `projectId`, `title`; `parent::__construct()`).

`src/Simulation/Domain/Scenario/ScenarioTemplateRepository.php`
```php
<?php
namespace Src\Simulation\Domain\Scenario;

interface ScenarioTemplateRepository
{
    public function save(ScenarioTemplate $scenario): void;
    public function findById(ScenarioTemplateId $id): ?ScenarioTemplate;
    /** @return ScenarioTemplate[] */
    public function findByProject(string $projectId): array;
    public function nextSequenceOrder(string $projectId): int; // Decision 4 helper
}
```

- [ ] `ScenarioTemplateId`, `ReferenceMaterial`, `ScenarioTemplate`, `ScenarioTemplateCreated`, `ScenarioTemplateRepository` created.

---

## 4. Infrastructure — mapper & the child-sync repository

### 4.1 — `ScenarioTemplateMapper`

`toEntity()` reconstitutes the scenario **and** maps its loaded `referenceMaterials` relation into `ReferenceMaterial` objects:

```php
<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper;

use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Scenario\MaterialType;
use Src\Simulation\Domain\Scenario\ReferenceMaterial;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Scenario\ScenarioTemplateId;
use Src\Simulation\Domain\Scenario\SituationTriggerType;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;

final class ScenarioTemplateMapper
{
    public function toEntity(ScenarioTemplateModel $m): ScenarioTemplate
    {
        $materials = $m->referenceMaterials->map(fn ($rm) => new ReferenceMaterial(
            id: $rm->id,
            type: $rm->material_type instanceof MaterialType ? $rm->material_type : MaterialType::from($rm->material_type),
            title: $rm->title,
            content: $rm->content,
            embeddedSignals: $rm->embedded_signals,
            displayOrder: (int) $rm->display_order,
        ))->all();

        return ScenarioTemplate::reconstitute(
            id: ScenarioTemplateId::fromString($m->id),
            projectId: $m->project_id,
            sequenceOrder: (int) $m->sequence_order,
            title: $m->title,
            narrativeContext: $m->narrative_context,
            situationTrigger: $m->situation_trigger,
            situationTriggerType: $m->situation_trigger_type instanceof SituationTriggerType ? $m->situation_trigger_type : SituationTriggerType::from($m->situation_trigger_type),
            learnerRoleLabel: $m->learner_role_label,
            defaultAutonomyLevel: $m->default_autonomy_level instanceof CacLevel ? $m->default_autonomy_level : CacLevel::from($m->default_autonomy_level),
            isDiagnostic: (bool) $m->is_diagnostic,
            isPublished: (bool) $m->is_published,
            isActive: (bool) $m->is_active,
            referenceMaterials: $materials,
        );
    }
}
```
> The `instanceof … ? … : …::from()` guard means the mapper works whether or not the enum cast is active on the model — defensive, and it stops a half-applied cast from crashing you.

### 4.2 — `EloquentScenarioTemplateRepository` (collection sync)

The new bit: `save()` persists the scenario **and reconciles its child rows** in one transaction — upsert the materials that are present, delete the ones that were removed. This is the "sync a collection" pattern.

```php
<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Scenario\ScenarioTemplateId;
use Src\Simulation\Domain\Scenario\ScenarioTemplateRepository;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper\ScenarioTemplateMapper;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioReferenceMaterialModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;

final class EloquentScenarioTemplateRepository implements ScenarioTemplateRepository
{
    public function __construct(private readonly ScenarioTemplateMapper $mapper) {}

    public function save(ScenarioTemplate $scenario): void
    {
        DB::transaction(function () use ($scenario) {
            ScenarioTemplateModel::updateOrCreate(
                ['id' => $scenario->id()],
                $scenario->toPrimitives(),
            );

            // Reconcile children: keep those present, delete the rest.
            $materials = $scenario->referenceMaterials();
            $keepIds = array_map(fn ($m) => $m->id(), $materials);

            ScenarioReferenceMaterialModel::where('scenario_id', $scenario->id())
                ->whereNotIn('id', $keepIds ?: ['__none__'])
                ->delete();

            foreach ($materials as $m) {
                ScenarioReferenceMaterialModel::updateOrCreate(
                    ['id' => $m->id()],
                    ['scenario_id' => $scenario->id()] + $m->toPrimitives(),
                );
            }
        });
    }

    public function findById(ScenarioTemplateId $id): ?ScenarioTemplate
    {
        $m = ScenarioTemplateModel::with('referenceMaterials')->find((string) $id);
        return $m ? $this->mapper->toEntity($m) : null;
    }

    /** @return ScenarioTemplate[] */
    public function findByProject(string $projectId): array
    {
        return ScenarioTemplateModel::with('referenceMaterials')
            ->where('project_id', $projectId)
            ->orderBy('sequence_order')
            ->get()
            ->map(fn ($m) => $this->mapper->toEntity($m))
            ->all();
    }

    public function nextSequenceOrder(string $projectId): int
    {
        return (int) ScenarioTemplateModel::where('project_id', $projectId)->max('sequence_order') + 1;
    }
}
```
> `->with('referenceMaterials')` eager-loads the child rows so the mapper can build the collection without an N+1. `nextSequenceOrder()` implements Decision 4's ordering — the next scenario slots after the last.

### 4.3 — Bind it

Add to `SimulationServiceProvider::register()`:
```php
$this->app->bind(
    \Src\Simulation\Domain\Scenario\ScenarioTemplateRepository::class,
    \Src\Simulation\Infrastructure\Persistence\Eloquent\Repository\EloquentScenarioTemplateRepository::class,
);
```

- [ ] Mapper, repository, binding done.

---

## 5. Application — commands & queries

Same shape as 4a. Scenario CRUD plus two child commands.

**Commands** (each in `Application/Command/<UseCase>/` with its handler alongside):
- `CreateScenario` — `CreateScenarioCommand(projectId, title, narrativeContext, situationTrigger, situationTriggerType, defaultAutonomyLevel, learnerRoleLabel?, isDiagnostic?)`. Handler resolves `sequenceOrder` via `$repo->nextSequenceOrder($projectId)`, builds enums from the string inputs (`SituationTriggerType::from(...)`, `CacLevel::from(...)`), creates the aggregate, saves, releases events.
- `UpdateScenario` — title + narrativeContext + situationTrigger(+type). Load, mutate, save.
- `PublishScenario` — as 4a's publish.
- `AddReferenceMaterial` — `AddReferenceMaterialCommand(scenarioId, materialType, title, content, displayOrder)`. Handler loads the scenario aggregate, calls `$scenario->addReferenceMaterial(new ReferenceMaterial(UuidGenerator::generate(), MaterialType::from(...), ...))`, saves. The repo's collection-sync inserts the new row.
- `RemoveReferenceMaterial` — `RemoveReferenceMaterialCommand(scenarioId, materialId)`. Load, `$scenario->removeReferenceMaterial($materialId)`, save. The sync deletes it.

**Example — `CreateScenarioHandler`** (the one with non-obvious bits):
```php
<?php
namespace Src\Simulation\Application\Command\CreateScenario;

use Src\Simulation\Domain\Cac\CacLevel;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Scenario\ScenarioTemplateId;
use Src\Simulation\Domain\Scenario\ScenarioTemplateRepository;
use Src\Simulation\Domain\Scenario\SituationTriggerType;

final class CreateScenarioHandler
{
    public function __construct(private readonly ScenarioTemplateRepository $repository) {}

    public function handle(CreateScenarioCommand $command): void
    {
        $scenario = ScenarioTemplate::create(
            id: ScenarioTemplateId::generate(),
            projectId: $command->projectId,
            sequenceOrder: $this->repository->nextSequenceOrder($command->projectId),
            title: $command->title,
            narrativeContext: $command->narrativeContext,
            situationTrigger: $command->situationTrigger,
            situationTriggerType: SituationTriggerType::from($command->situationTriggerType),
            defaultAutonomyLevel: CacLevel::from($command->defaultAutonomyLevel),
            learnerRoleLabel: $command->learnerRoleLabel,
            isDiagnostic: $command->isDiagnostic,
        );

        $this->repository->save($scenario);

        foreach ($scenario->releaseEvents() as $event) {
            event($event);
        }
    }
}
```

**Queries:** `ListScenariosByProject(projectId)` → `findByProject`; `GetScenario(scenarioId)` → `findById` (returns the aggregate with its materials).

- [ ] Five commands + handlers, two queries + handlers created.

---

## 6. Presentation — controller, requests, routes

Scenarios are nested under a project: `/admin/projects/{project}/scenarios`.

**`ScenarioController`** (`Presentation/Http/Controller/Admin/`) — injects `CommandBus` + `QueryBus`; methods `index($projectId)`, `create($projectId)`, `store($projectId)`, `edit($projectId, $id)`, `update`, `publish`, plus `addMaterial($id)` and `removeMaterial($id, $materialId)` returning the materials-list partial for HTMX. Same controller style as 4a's `ProjectController`.

**Decision 4 in practice — handle the unique constraint.** Two scenarios can't share `(project_id, sequence_order)`. `nextSequenceOrder()` avoids collisions on create, but a race or a manual reorder could still hit the DB unique index. Wrap the create dispatch and translate the DB error into a friendly validation message:
```php
public function store(StoreScenarioRequest $request, string $projectId)
{
    try {
        $this->commandBus->dispatch(new CreateScenarioCommand(/* … */));
    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
        return back()->withInput()->withErrors([
            'sequence_order' => 'That position is already taken in this project. Try again.',
        ]);
    }
    return redirect()->route('admin.projects.scenarios.index', $projectId)
        ->with('success', 'Scenario created.');
}
```

**`StoreScenarioRequest`** rules: `title` required; `narrative_context` required; `situation_trigger` required; `situation_trigger_type` `required|in:slack_message,email,meeting_summary,incident_report,ticket,handover_note`; `default_autonomy_level` `required|in:low,mid,high`; `learner_role_label` nullable; `is_diagnostic` boolean. **`StoreReferenceMaterialRequest`**: `material_type` `required|in:document,email,slack_message,ticket,report,notes`; `title` required; `content` required; `display_order` integer.

**`routes/simulation.php`** — add under the existing admin group:
```php
use Src\Simulation\Presentation\Http\Controller\Admin\ScenarioController;

Route::get('projects/{project}/scenarios',              [ScenarioController::class, 'index'])->name('projects.scenarios.index');
Route::get('projects/{project}/scenarios/create',       [ScenarioController::class, 'create'])->name('projects.scenarios.create');
Route::post('projects/{project}/scenarios',             [ScenarioController::class, 'store'])->name('projects.scenarios.store');
Route::get('projects/{project}/scenarios/{id}/edit',    [ScenarioController::class, 'edit'])->name('projects.scenarios.edit');
Route::patch('projects/{project}/scenarios/{id}',       [ScenarioController::class, 'update'])->name('projects.scenarios.update');
Route::patch('projects/{project}/scenarios/{id}/publish',[ScenarioController::class, 'publish'])->name('projects.scenarios.publish');
// reference materials (HTMX)
Route::post('scenarios/{id}/materials',                 [ScenarioController::class, 'addMaterial'])->name('scenarios.materials.add');
Route::delete('scenarios/{id}/materials/{materialId}',  [ScenarioController::class, 'removeMaterial'])->name('scenarios.materials.remove');
```

- [ ] Controller, two form requests, routes done.

---

## 7. Views + the `<x-reference-material>` component (Decision 5)

**`resources/views/components/reference-material.blade.php`** — the reusable, type-aware component. `@switch` on `$type` gives each material its own chrome. This same component renders in the admin preview now and the learner sidebar in Phase 6.
```blade
@props(['type', 'title', 'content'])

<article class="ref-material ref-material--{{ $type }}">
    @switch($type)
        @case('email')
            <header class="rm-email-header">✉️ <strong>{{ $title }}</strong></header>
            @break
        @case('slack_message')
            <header class="rm-slack-header"># <strong>{{ $title }}</strong></header>
            @break
        @case('ticket')
            <header class="rm-ticket-header">🎫 <strong>{{ $title }}</strong></header>
            @break
        @case('incident_report')
        @case('report')
            <header class="rm-report-header">📄 <strong>{{ $title }}</strong></header>
            @break
        @default
            <header class="rm-doc-header">📝 <strong>{{ $title }}</strong></header>
    @endswitch

    <div class="rm-body">{!! nl2br(e($content)) !!}</div>
</article>
```
> `e()` escapes the content (never trust authored HTML), then `nl2br` restores line breaks. Sender/channel metadata lives in `$content` as prose, per your Decision 5 — no extra columns.

**Scenario views** (`resources/views/admin/scenarios/`): `index.blade.php` (list under the project, ordered by `sequence_order`, "+ New Scenario"), `create.blade.php` / `edit.blade.php` (form with the `situation_trigger` textarea + a `situation_trigger_type` `<select>` of the six values + `default_autonomy_level` select), and `_row.blade.php` (publish toggle via `hx-patch`, as in 4a).

**Reference-material sub-form** on the edit page (nested HTMX): a `#materials` list where each row is rendered, an "add material" form that `hx-post`s to `scenarios.materials.add` with `hx-target="#materials" hx-swap="beforeend"`, and each row's remove button `hx-delete`s `scenarios.materials.remove` with `hx-target="closest .material-row" hx-swap="outerHTML swap:200ms"`. The controller's `addMaterial`/`removeMaterial` return the material-row / list partial. Use `<x-reference-material>` in a "preview" panel beside the list so authors see the rendered chrome live.

- [ ] Component + scenario views + material sub-form created.

---

## 8. Verification Checklist

- [ ] Bus fix confirmed (a dispatch works) — §0.
- [ ] `composer dump-autoload -o` clean (no PSR-4 warnings on the new Scenario classes/enums).
- [ ] `route:list --path=admin` shows `admin.projects.scenarios.*` behind `role:content_author`.
- [ ] Create a scenario under a project → it appears ordered by `sequence_order`; `nextSequenceOrder` gives the next slot.
- [ ] Creating a second scenario forced to the same `sequence_order` surfaces the friendly validation error, not a 500 (Decision 4).
- [ ] Add two reference materials via the HTMX sub-form → both persist with the right `scenario_id`; remove one → its row is deleted and the DB row is gone (proves collection sync).
- [ ] `GetScenario` returns the aggregate with its materials loaded (no N+1 — `->with('referenceMaterials')`).
- [ ] The `<x-reference-material type="email">` preview shows email chrome; `type="slack_message"` shows Slack chrome.
- [ ] Enum round-trip: a saved scenario's `situation_trigger_type` reads back as a `SituationTriggerType` case in tinker.
- [ ] No Eloquent model imported under `Application/` (grep).

---

## 9. What 4c / 4d Build Next

- **4c — Task authoring**, the big one: the `Task` aggregate owning **five** child collections (expected deliverables, CAC variants, dependencies, knowledge anchors, guidance prompts) plus rubric criteria. This is where **Decision 3** lands — authoring `task_guidance_prompts` with `delivery_mode` (`proactive`/`reactive`) and `autonomy_level_filter`; the word-count/inactivity *trigger* logic is Phase 6. The child-sync pattern from this phase repeats five times, and `CacLevel` gets reused for `fixed_complexity`/`fixed_autonomy`/`fixed_context_fidelity` and the CAC variants.
- **4d — Artifact vault + the `MedQueueSeeder`** that finally fills all this with real content.

---

## 10. Scope Discipline Self-Check (Guidance §7)

- [x] **No new tables** — writes only to `scenario_templates` + `scenario_reference_materials` (Phase-3 tables). Columns/enums verified against the live migrations.
- [x] **Correct context** — all in Simulation (owns the tables).
- [x] **Nothing pulled forward** — the situation-trigger *modal* (Phase 6) and guidance-prompt *timing* (Phase 6) are explicitly deferred; only authoring fields are captured.
- [x] **Enums match the DB exactly** — `SituationTriggerType`, `MaterialType`, `CacLevel` cases verified case-for-case against the `enum([...])` definitions; casts activated per Phase 3 §9's plan.
- [x] **Patterns mirrored** — VO/entity/repository/mapper/command/handler follow 4a and Identity; the new child-collection sync is the only added concept, and it's explained.
- [x] **Aggregate boundary reasoning stated** — reference materials are children of the Scenario aggregate, mutated only through the root, synced transactionally.
- [x] **Decision 4 honoured** — sequence-order uniqueness handled at both `nextSequenceOrder()` and the caught `UniqueConstraintViolationException`.
- [x] **Runnable verification** (§8), including the collection-sync and enum round-trip checks.
- [x] **Open decisions** — none newly opened; D2/D3 runtime pieces flagged as Phase 6, not silently built.

**Before 4c:** confirm the bus fix is actually in and a dispatch round-trips — I keep flagging it because *every* command/query in 4a, 4b, and 4c is dead until it is. Once Scenario authoring works end-to-end, say the word and I'll write 4c (Task), where Decision 3's guidance-prompt authoring comes home.
