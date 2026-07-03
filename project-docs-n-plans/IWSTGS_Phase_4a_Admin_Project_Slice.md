# IWSTGS — Phase 4a: Admin Foundation & the Project Authoring Slice

> **Goal of this sub-phase:** stand up the admin shell (layout + `role:content_author` protection), the feature-flag toggle screen, and **one complete authoring vertical** — creating, editing, and publishing a `ProjectTemplate` — end to end through your real DDD stack: form request → controller → `CommandBus`/`QueryBus` → handler → repository → Eloquent, with Blade + HTMX on top.
>
> This is the *reference slice*. Phase 4b (Scenario + Task) and 4c (rubric criteria + vault + MedQueue seeder) repeat this exact shape, so the effort you spend understanding it here pays off three times over.

---

## 0. Where This Sits & What It Assumes

Phase 3 gave every content table an Eloquent model. Those models are inert — nothing writes to them yet. Phase 4a is the first phase that *drives* them, and the first with controllers, routes, and views since Phase 1's auth.

We deliberately keep to **one aggregate** (`ProjectTemplate`) so you meet every moving part once. The pattern is lifted straight from your Identity module: `User` (aggregate) ← `UserRepository` (interface) ← `EloquentUserRepository` + `UserMapper` (infrastructure), with `RegisterUserCommand`/`RegisterUserHandler` orchestrating. We're building the `ProjectTemplate` equivalent.

**Prerequisites:** Phases 0–3 complete; the 17 Phase-3 models exist; HTMX + hyperscript are already loaded in `resources/views/layouts/app.blade.php`; the `role` middleware alias is registered in `bootstrap/app.php`.

---

## 1. Known Issues to Fix First

Two of these are **blocking** — Phase 4a literally cannot dispatch a command until #1 is fixed — so do them before writing any new code.

### 1.1 — 🔴 BLOCKER: the bus resolves handlers to the wrong namespace

`SynchronousCommandBus` derives the handler like this:
```php
$handlerClass = str_replace('Command', 'Handler', get_class($command));
```
`str_replace` replaces **every** occurrence of `Command` in the fully-qualified class name. Your handler lives at `Src\Identity\Application\Command\RegisterUser\RegisterUserHandler` — note the `\Command\` folder. So the FQCN `…\Application\Command\RegisterUser\RegisterUserCommand` becomes:

```
…\Application\Handler\RegisterUser\RegisterUserHandler   ← the folder got rewritten too
```

…which doesn't exist. Every `dispatch()` currently throws a "class not found" resolution error — registration and login included. The `QueryBus` has the identical bug with `'Query'`.

**The fix:** replace only the **suffix** of the class name, not the folder. Anchor the replacement to the end of the string with `preg_replace` and a `$` anchor.

`src/Shared/Application/Bus/SynchronousCommandBus.php`
```php
public function dispatch(object $command): void
{
    // Replace only the trailing 'Command' in the class name — NOT the \Command\
    // folder segment. The $ anchor is the whole point.
    $handlerClass = preg_replace('/Command$/', 'Handler', get_class($command));
    $handler = $this->container->make($handlerClass);
    $handler->handle($command);
}
```

`src/Shared/Application/Bus/SynchronousQueryBus.php`
```php
public function ask(object $query): mixed
{
    $handlerClass = preg_replace('/Query$/', 'Handler', get_class($query));
    $handler = $this->container->make($handlerClass);
    return $handler->handle($query);
}
```

**Why `preg_replace('/Command$/', …)` and not something cleverer:** the `$` anchors the match to the end of the string, so only the final `Command` (the class-name suffix) is touched. `Src\...\Command\RegisterUser\RegisterUserCommand` → `Src\...\Command\RegisterUser\RegisterUserHandler`. Folder preserved, class renamed. This keeps handlers living *next to* their commands — the convention your code already follows.

- [ ] Both buses patched; `RegisterUserCommand` still dispatches (test by registering a user — it should work now, possibly for the first time).

### 1.2 — 🔴 BLOCKER: `OrganizationModel` points at a non-existent table

The "organisation → organization" rename was applied to the model but **not** the migration. `OrganizationModel` declares `protected $table = 'organizations'`, but the migration creates `organisations` (British 's'), and every FK column is `organisation_id`. Any org query throws "table doesn't exist."

**The fix (one line):** the Data Model is your schema source of truth and uses British spelling, so map the class to the real table name — a class named `OrganizationModel` pointing at table `organisations` is completely normal:
```php
// src/Organizations/Infrastructure/Persistence/Eloquent/Model/OrganizationModel.php
protected $table = 'organisations';
```
Your FK columns (`organisation_id`) already match, so nothing else changes. (If you ever want `z` in the database too, that's a separate, larger migration — rename the table and every `organisation_id` column — and a documented divergence from the Data Model. Not now.)

- [ ] `OrganizationModel::$table` set to `'organisations'`.

### 1.3 — 🟡 Remove the `administrator` tables

As agreed: a platform admin is a `users` row with the `super_admin` role via `user_role`, not a separate identity table. Delete both migration files:
```
database/migrations/2026_05_05_135843_create_administrator_table.php
database/migrations/2026_05_05_140323_create_administrator_role_table.php
```
Nothing in `src/` references them. Run `php artisan migrate:fresh` afterward.

- [ ] Both administrator migrations deleted; `migrate:fresh` clean.

### 1.4 — 🟡 Clean up `SimulationServiceProvider`

It has a dead `$test = "test string";` line and commented-out route loading. We replace both when we wire admin routes in §7.

---

## 2. New Concepts Introduced This Phase

**The aggregate owns its children — and its rubric set.** A `ProjectTemplate` and its `rubric_set` are created together and never exist apart (the schema enforces `rubric_sets.project_id` unique). So the rubric set is *part of the Project aggregate*, not a separate thing to manage. The Project's **repository** creates both rows in one transaction. You never write a `RubricSetRepository` — reaching the rubric set goes *through* the project. This is aggregate-boundary thinking: one repository per aggregate root, not per table.

**Write path vs read path (CQRS-lite, which your buses already assume).** Writes go through the aggregate and its repository so invariants are protected (`CommandBus` → handler → `ProjectTemplateRepository`). Reads never mutate, so they take the cheaper route (`QueryBus` → handler → repository read method → view data). Both still go through the repository interface — per your `FeatureFlagModel` docblock rule, **Application code never imports an Eloquent model directly**.

**`DB::transaction()`** — wrapping the project+rubric-set insert so a half-created project can never exist. If anything inside throws, the whole thing rolls back.

**Form Requests** — `StoreProjectRequest` holds validation rules; Laravel runs them before your controller method is even entered, and auto-redirects back with errors if they fail. Your controller only runs on valid input.

**HTMX `hx-patch`** — the publish toggle and feature-flag switches issue a background `PATCH` and swap in the returned HTML fragment, no full page reload. The controller returns a *partial* Blade view for these.

---

## 3. Domain Layer — `src/Simulation/Domain/Project/`

### 3.1 — `ProjectTemplateId` (value object)

Mirrors `UserId` exactly.

```php
<?php
namespace Src\Simulation\Domain\Project;

use Src\Shared\Domain\ValueObject;
use Src\Shared\Infrastructure\Id\UuidGenerator;

final class ProjectTemplateId extends ValueObject
{
    public function __construct(private readonly string $uuid) {}

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public static function generate(): self
    {
        return new self((string) UuidGenerator::generate());
    }

    public function value(): string
    {
        return $this->uuid;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->uuid === $this->uuid;
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}
```

### 3.2 — `ProjectTemplate` (aggregate root)

Content aggregates are data-heavy — that's normal; a project template *is* mostly structured configuration. We hold every persisted field, expose a `toPrimitives()` helper (so the mapper stays short), and keep the two factories your codebase uses: `create()` for new (records an event) and `reconstitute()` for loading from the DB.

```php
<?php
namespace Src\Simulation\Domain\Project;

use Src\Shared\Domain\AggregateRoot;

final class ProjectTemplate extends AggregateRoot
{
    public function __construct(
        private readonly ProjectTemplateId $id,
        private string   $title,
        private string   $projectType,
        private string   $businessContext,
        private array    $specializationTags,
        private string   $difficultyLevel,
        private ?string  $tagline = null,
        private ?string  $businessDomain = null,
        private ?array   $stakeholders = null,
        private ?array   $overarchingConstraints = null,
        private ?array   $techContext = null,
        private ?string  $organisationId = null,
        private ?string  $codingGuidelines = null,
        private ?array   $velocityEstimate = null,
        private bool     $isPublished = false,
        private bool     $isActive = true,
    ) {}

    public static function create(
        ProjectTemplateId $id,
        string $title,
        string $projectType,
        string $businessContext,
        array  $specializationTags,
        string $difficultyLevel,
        ?string $tagline = null,
        ?string $businessDomain = null,
        ?array  $stakeholders = null,
        ?array  $overarchingConstraints = null,
        ?array  $techContext = null,
        ?string $organisationId = null,
        ?string $codingGuidelines = null,
        ?array  $velocityEstimate = null,
    ): self {
        $project = new self(
            $id, $title, $projectType, $businessContext, $specializationTags,
            $difficultyLevel, $tagline, $businessDomain, $stakeholders,
            $overarchingConstraints, $techContext, $organisationId,
            $codingGuidelines, $velocityEstimate,
            isPublished: false, isActive: true,
        );

        $project->recordEvent(new ProjectTemplateCreated((string) $id, $title));

        return $project;
    }

    public static function reconstitute(
        ProjectTemplateId $id,
        string $title,
        string $projectType,
        string $businessContext,
        array  $specializationTags,
        string $difficultyLevel,
        ?string $tagline,
        ?string $businessDomain,
        ?array  $stakeholders,
        ?array  $overarchingConstraints,
        ?array  $techContext,
        ?string $organisationId,
        ?string $codingGuidelines,
        ?array  $velocityEstimate,
        bool    $isPublished,
        bool    $isActive,
    ): self {
        return new self(
            $id, $title, $projectType, $businessContext, $specializationTags,
            $difficultyLevel, $tagline, $businessDomain, $stakeholders,
            $overarchingConstraints, $techContext, $organisationId,
            $codingGuidelines, $velocityEstimate, $isPublished, $isActive,
        );
    }

    // --- Behaviour (this is why it's an entity, not an array) ---

    public function rename(string $title): void          { $this->title = $title; }
    public function updateBusinessContext(string $c): void { $this->businessContext = $c; }
    public function publish(): void                       { $this->isPublished = true; }
    public function unpublish(): void                     { $this->isPublished = false; }

    public function id(): string { return (string) $this->id; }
    public function projectTemplateId(): ProjectTemplateId { return $this->id; }
    public function isPublished(): bool { return $this->isPublished; }

    /** Flat representation for the mapper — keeps persistence code short. */
    public function toPrimitives(): array
    {
        return [
            'id'                      => (string) $this->id,
            'title'                   => $this->title,
            'project_type'            => $this->projectType,
            'business_context'        => $this->businessContext,
            'specialization_tags'     => $this->specializationTags,
            'difficulty_level'        => $this->difficultyLevel,
            'tagline'                 => $this->tagline,
            'business_domain'         => $this->businessDomain,
            'stakeholders'            => $this->stakeholders,
            'overarching_constraints' => $this->overarchingConstraints,
            'tech_context'            => $this->techContext,
            'organisation_id'         => $this->organisationId,
            'coding_guidelines'       => $this->codingGuidelines,
            'velocity_estimate'       => $this->velocityEstimate,
            'is_published'            => $this->isPublished,
            'is_active'               => $this->isActive,
        ];
    }
}
```

*(As the entity grows behaviour in later phases, some of those raw arrays — `stakeholders`, `techContext` — are natural candidates to become value objects. Not needed yet; introduce them when logic depends on their shape.)*

### 3.3 — `ProjectTemplateCreated` (domain event) & the repository interface

```php
<?php
namespace Src\Simulation\Domain\Project;

use Src\Shared\Domain\DomainEvent;

final class ProjectTemplateCreated extends DomainEvent
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $title,
    ) {
        parent::__construct();
    }
}
```
> If `DomainEvent`'s constructor signature differs in your `Shared\Domain`, match it — check `UserRegistered` for the exact base call. The point is parity with the existing event.

```php
<?php
namespace Src\Simulation\Domain\Project;

interface ProjectTemplateRepository
{
    public function save(ProjectTemplate $project): void;

    public function findById(ProjectTemplateId $id): ?ProjectTemplate;

    /** @return ProjectTemplate[] */
    public function all(): array;
}
```

- [ ] `ProjectTemplateId`, `ProjectTemplate`, `ProjectTemplateCreated`, `ProjectTemplateRepository` created.

---

## 4. Infrastructure Layer — persistence

### 4.1 — `ProjectTemplateMapper`

`src/Simulation/Infrastructure/Persistence/Eloquent/Mapper/ProjectTemplateMapper.php`
```php
<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper;

use Src\Simulation\Domain\Project\ProjectTemplate;
use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;

final class ProjectTemplateMapper
{
    public function toEntity(ProjectTemplateModel $m): ProjectTemplate
    {
        return ProjectTemplate::reconstitute(
            id:                     ProjectTemplateId::fromString($m->id),
            title:                  $m->title,
            projectType:            $m->project_type,
            businessContext:        $m->business_context,
            specializationTags:     $m->specialization_tags ?? [],
            difficultyLevel:        $m->difficulty_level instanceof \BackedEnum ? $m->difficulty_level->value : $m->difficulty_level,
            tagline:                $m->tagline,
            businessDomain:         $m->business_domain,
            stakeholders:           $m->stakeholders,
            overarchingConstraints: $m->overarching_constraints,
            techContext:            $m->tech_context,
            organisationId:         $m->organisation_id,
            codingGuidelines:       $m->coding_guidelines,
            velocityEstimate:       $m->velocity_estimate,
            isPublished:            (bool) $m->is_published,
            isActive:               (bool) $m->is_active,
        );
    }
}
```
> `difficulty_level` is enum-cast on the model, so it comes back as a `DifficultyLevel` case; the entity stores the string value. The `instanceof \BackedEnum` guard handles both a cast enum and a raw string safely.

### 4.2 — `EloquentProjectTemplateRepository` (the transaction lives here)

`src/Simulation/Infrastructure/Persistence/Eloquent/Repository/EloquentProjectTemplateRepository.php`
```php
<?php
namespace Src\Simulation\Infrastructure\Persistence\Eloquent\Repository;

use Illuminate\Support\Facades\DB;
use Src\Shared\Infrastructure\Id\UuidGenerator;
use Src\Simulation\Domain\Project\ProjectTemplate;
use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Mapper\ProjectTemplateMapper;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricSetModel;

final class EloquentProjectTemplateRepository implements ProjectTemplateRepository
{
    public function __construct(private readonly ProjectTemplateMapper $mapper) {}

    public function save(ProjectTemplate $project): void
    {
        DB::transaction(function () use ($project) {
            // Upsert the project row. updateOrCreate handles both create and edit:
            // it matches on the primary key and inserts or updates accordingly.
            $data = $project->toPrimitives();
            $model = ProjectTemplateModel::updateOrCreate(['id' => $data['id']], $data);

            // A project's rubric set is part of the aggregate — created with it,
            // exactly once. firstOrCreate makes this idempotent on edits.
            $rubric = RubricSetModel::firstOrCreate(
                ['project_id' => $model->id],
                ['id' => (string) UuidGenerator::generate(), 'version' => '1.0'],
            );

            // Keep the convenience back-reference in sync.
            if ($model->rubric_set_id !== $rubric->id) {
                $model->rubric_set_id = $rubric->id;
                $model->save();
            }
        });
    }

    public function findById(ProjectTemplateId $id): ?ProjectTemplate
    {
        $model = ProjectTemplateModel::find((string) $id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    /** @return ProjectTemplate[] */
    public function all(): array
    {
        return ProjectTemplateModel::orderByDesc('created_at')
            ->get()
            ->map(fn (ProjectTemplateModel $m) => $this->mapper->toEntity($m))
            ->all();
    }
}
```

### 4.3 — Bind it (Simulation provider)

`src/Simulation/Infrastructure/Provider/SimulationServiceProvider.php` — replace the whole class:
```php
<?php
namespace Src\Simulation\Infrastructure\Provider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Repository\EloquentProjectTemplateRepository;

class SimulationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProjectTemplateRepository::class, EloquentProjectTemplateRepository::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'role:content_author'])
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/simulation.php'));
    }
}
```

- [ ] Mapper, repository, and binding created; dead `$test` line gone.

---

## 5. Application Layer — commands & queries

Handlers live **next to** their command/query (the `\Command\<UseCase>\` folder), which now resolves correctly thanks to §1.1.

### 5.1 — Create

`src/Simulation/Application/Command/CreateProject/CreateProjectCommand.php`
```php
<?php
namespace Src\Simulation\Application\Command\CreateProject;

final class CreateProjectCommand
{
    public function __construct(
        public readonly string  $title,
        public readonly string  $projectType,
        public readonly string  $businessContext,
        public readonly array   $specializationTags,
        public readonly string  $difficultyLevel,
        public readonly ?string $tagline = null,
        public readonly ?string $businessDomain = null,
        public readonly ?string $organisationId = null,
    ) {}
}
```

`src/Simulation/Application/Command/CreateProject/CreateProjectHandler.php`
```php
<?php
namespace Src\Simulation\Application\Command\CreateProject;

use Src\Simulation\Domain\Project\ProjectTemplate;
use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;

final class CreateProjectHandler
{
    public function __construct(private readonly ProjectTemplateRepository $repository) {}

    public function handle(CreateProjectCommand $command): void
    {
        $project = ProjectTemplate::create(
            id:                 ProjectTemplateId::generate(),
            title:              $command->title,
            projectType:        $command->projectType,
            businessContext:    $command->businessContext,
            specializationTags: $command->specializationTags,
            difficultyLevel:    $command->difficultyLevel,
            tagline:            $command->tagline,
            businessDomain:     $command->businessDomain,
            organisationId:     $command->organisationId,
        );

        $this->repository->save($project);

        foreach ($project->releaseEvents() as $event) {
            event($event);
        }
    }
}
```

### 5.2 — Update & Publish

`Command/UpdateProject/UpdateProjectCommand.php`
```php
<?php
namespace Src\Simulation\Application\Command\UpdateProject;

final class UpdateProjectCommand
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $title,
        public readonly string $businessContext,
    ) {}
}
```

`Command/UpdateProject/UpdateProjectHandler.php`
```php
<?php
namespace Src\Simulation\Application\Command\UpdateProject;

use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;
use Src\Simulation\Domain\Exceptions\ProjectNotFoundException;

final class UpdateProjectHandler
{
    public function __construct(private readonly ProjectTemplateRepository $repository) {}

    public function handle(UpdateProjectCommand $command): void
    {
        $project = $this->repository->findById(ProjectTemplateId::fromString($command->projectId));
        if ($project === null) {
            throw new ProjectNotFoundException($command->projectId);
        }

        $project->rename($command->title);
        $project->updateBusinessContext($command->businessContext);

        $this->repository->save($project);
    }
}
```

`Command/PublishProject/PublishProjectCommand.php`
```php
<?php
namespace Src\Simulation\Application\Command\PublishProject;

final class PublishProjectCommand
{
    public function __construct(
        public readonly string $projectId,
        public readonly bool   $publish,
    ) {}
}
```

`Command/PublishProject/PublishProjectHandler.php`
```php
<?php
namespace Src\Simulation\Application\Command\PublishProject;

use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;
use Src\Simulation\Domain\Exceptions\ProjectNotFoundException;

final class PublishProjectHandler
{
    public function __construct(private readonly ProjectTemplateRepository $repository) {}

    public function handle(PublishProjectCommand $command): void
    {
        $project = $this->repository->findById(ProjectTemplateId::fromString($command->projectId));
        if ($project === null) {
            throw new ProjectNotFoundException($command->projectId);
        }

        $command->publish ? $project->publish() : $project->unpublish();
        $this->repository->save($project);
    }
}
```

Add the exception at `src/Simulation/Domain/Exceptions/ProjectNotFoundException.php`:
```php
<?php
namespace Src\Simulation\Domain\Exceptions;

final class ProjectNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Project template [{$id}] was not found.");
    }
}
```

### 5.3 — Queries (read side)

`Query/ListProjects/ListProjectsQuery.php`
```php
<?php
namespace Src\Simulation\Application\Query\ListProjects;

final class ListProjectsQuery {}
```

`Query/ListProjects/ListProjectsHandler.php`
```php
<?php
namespace Src\Simulation\Application\Query\ListProjects;

use Src\Simulation\Domain\Project\ProjectTemplateRepository;

final class ListProjectsHandler
{
    public function __construct(private readonly ProjectTemplateRepository $repository) {}

    /** @return \Src\Simulation\Domain\Project\ProjectTemplate[] */
    public function handle(ListProjectsQuery $query): array
    {
        return $this->repository->all();
    }
}
```

`Query/GetProject/GetProjectQuery.php`
```php
<?php
namespace Src\Simulation\Application\Query\GetProject;

final class GetProjectQuery
{
    public function __construct(public readonly string $projectId) {}
}
```

`Query/GetProject/GetProjectHandler.php`
```php
<?php
namespace Src\Simulation\Application\Query\GetProject;

use Src\Simulation\Domain\Project\ProjectTemplate;
use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;

final class GetProjectHandler
{
    public function __construct(private readonly ProjectTemplateRepository $repository) {}

    public function handle(GetProjectQuery $query): ?ProjectTemplate
    {
        return $this->repository->findById(ProjectTemplateId::fromString($query->projectId));
    }
}
```

- [ ] Three commands + handlers, two queries + handlers, and the exception created. Handler files sit in the `\Command\`/`\Query\` folders (the §1.1 fix makes this resolve).

---

## 6. Presentation — controller & form requests

`src/Simulation/Presentation/Http/Request/StoreProjectRequest.php`
```php
<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool { return true; } // route middleware already enforces role

    public function rules(): array
    {
        return [
            'title'                 => ['required', 'string', 'max:300'],
            'project_type'          => ['required', 'string', 'max:100'],
            'business_context'      => ['required', 'string'],
            'specialization_tags'   => ['required', 'array', 'min:1'],
            'specialization_tags.*' => ['string'],
            'difficulty_level'      => ['required', 'in:beginner,intermediate,advanced'],
            'tagline'               => ['nullable', 'string', 'max:500'],
            'business_domain'       => ['nullable', 'string', 'max:200'],
        ];
    }
}
```

`Request/UpdateProjectRequest.php`
```php
<?php
namespace Src\Simulation\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:300'],
            'business_context' => ['required', 'string'],
        ];
    }
}
```

`src/Simulation/Presentation/Http/Controller/Admin/ProjectController.php`
```php
<?php
namespace Src\Simulation\Presentation\Http\Controller\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Command\CreateProject\CreateProjectCommand;
use Src\Simulation\Application\Command\PublishProject\PublishProjectCommand;
use Src\Simulation\Application\Command\UpdateProject\UpdateProjectCommand;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Simulation\Application\Query\ListProjects\ListProjectsQuery;
use Src\Simulation\Presentation\Http\Request\StoreProjectRequest;
use Src\Simulation\Presentation\Http\Request\UpdateProjectRequest;

class ProjectController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function index(): View
    {
        $projects = $this->queryBus->ask(new ListProjectsQuery());
        return view('admin.projects.index', ['projects' => $projects]);
    }

    public function create(): View
    {
        return view('admin.projects.create');
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $this->commandBus->dispatch(new CreateProjectCommand(
            title:              $request->string('title')->toString(),
            projectType:        $request->string('project_type')->toString(),
            businessContext:    $request->string('business_context')->toString(),
            specializationTags: $request->input('specialization_tags', []),
            difficultyLevel:    $request->string('difficulty_level')->toString(),
            tagline:            $request->input('tagline'),
            businessDomain:     $request->input('business_domain'),
        ));

        return redirect()->route('admin.projects.index')
            ->with('success', 'Project created.');
    }

    public function edit(string $id): View
    {
        $project = $this->queryBus->ask(new GetProjectQuery($id));
        abort_if($project === null, 404);
        return view('admin.projects.edit', ['project' => $project]);
    }

    public function update(UpdateProjectRequest $request, string $id): RedirectResponse
    {
        $this->commandBus->dispatch(new UpdateProjectCommand(
            projectId:       $id,
            title:           $request->string('title')->toString(),
            businessContext: $request->string('business_context')->toString(),
        ));

        return redirect()->route('admin.projects.index')->with('success', 'Project updated.');
    }

    public function publish(string $id): View
    {
        // Toggle: read current state, flip it.
        $project = $this->queryBus->ask(new GetProjectQuery($id));
        abort_if($project === null, 404);

        $this->commandBus->dispatch(new PublishProjectCommand($id, ! $project->isPublished()));

        // HTMX expects the updated row fragment back.
        $project = $this->queryBus->ask(new GetProjectQuery($id));
        return view('admin.projects._row', ['project' => $project]);
    }
}
```

- [ ] Controller + both form requests created.

---

## 7. Routes

`routes/simulation.php` (replace the placeholder — these load under `admin` prefix + `admin.` name + `role:content_author`, per the provider in §4.3):
```php
<?php
use Illuminate\Support\Facades\Route;
use Src\Simulation\Presentation\Http\Controller\Admin\ProjectController;

Route::get('projects',            [ProjectController::class, 'index'])->name('projects.index');
Route::get('projects/create',     [ProjectController::class, 'create'])->name('projects.create');
Route::post('projects',           [ProjectController::class, 'store'])->name('projects.store');
Route::get('projects/{id}/edit',  [ProjectController::class, 'edit'])->name('projects.edit');
Route::patch('projects/{id}',     [ProjectController::class, 'update'])->name('projects.update');
Route::patch('projects/{id}/publish', [ProjectController::class, 'publish'])->name('projects.publish');
```

Resulting URLs: `/admin/projects`, `/admin/projects/create`, etc.; route names `admin.projects.*`.

---

## 8. Feature-Flag Admin (Shared)

Your `FeatureFlag` domain + `FeatureFlagRepository` + `EloquentFeatureFlagRepository` already exist, so this is thin. Add a controller and a route file loaded by the Shared provider.

`src/Shared/Presentation/Http/Controller/Admin/FeatureFlagController.php`
```php
<?php
namespace Src\Shared\Presentation\Http\Controller\Admin;

use Illuminate\View\View;
use Src\Shared\Domain\Feature\FeatureFlagRepository;

class FeatureFlagController
{
    public function __construct(private readonly FeatureFlagRepository $flags) {}

    public function index(): View
    {
        return view('admin.feature-flags.index', ['flags' => $this->flags->all()]);
    }

    public function toggle(string $key): View
    {
        $flag = $this->flags->toggle($key); // flips is_enabled, returns the updated flag
        return view('admin.feature-flags._row', ['flag' => $flag]);
    }
}
```
> Check the exact method names on your `FeatureFlagRepository` (`all()`, `toggle()` / `findByKey()`), and adjust to match. If it lacks an `all()` or `toggle()`, add them to the interface + `EloquentFeatureFlagRepository` — that's the right place, not the controller.

Load its routes — add to `SharedServiceProvider::boot()`:
```php
public function boot(): void
{
    Route::middleware(['web', 'auth', 'role:content_author'])
        ->prefix('admin')
        ->name('admin.')
        ->group(base_path('routes/shared.php'));
}
```
`routes/shared.php`:
```php
<?php
use Illuminate\Support\Facades\Route;
use Src\Shared\Presentation\Http\Controller\Admin\FeatureFlagController;

Route::get('feature-flags',                [FeatureFlagController::class, 'index'])->name('feature-flags.index');
Route::patch('feature-flags/{key}/toggle', [FeatureFlagController::class, 'toggle'])->name('feature-flags.toggle');
```
> `SharedServiceProvider` currently only has `register()`. Add the `boot()` above and `use Illuminate\Support\Facades\Route;`. Bindings stay in `register()`.

- [ ] Feature-flag controller + `routes/shared.php` + Shared provider `boot()` added.

---

## 9. Views — `resources/views/admin/`

**`layouts/admin.blade.php`** — standalone admin shell (HTMX + hyperscript via CDN, matching `app.blade.php`):
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>IWSTGS Admin — @yield('title', 'Dashboard')</title>
    <script src="https://unpkg.com/htmx.org@1.9.12" defer></script>
    <script src="https://unpkg.com/hyperscript.org@0.9.12" defer></script>
    {{-- htmx needs the CSRF token on every non-GET request --}}
    <script>
      document.addEventListener('htmx:configRequest', (e) => {
        e.detail.headers['X-CSRF-TOKEN'] =
          document.querySelector('meta[name=csrf-token]').content;
      });
    </script>
</head>
<body>
    <nav>
        <strong>IWSTGS Admin</strong>
        <a href="{{ route('admin.projects.index') }}">Projects</a>
        <a href="{{ route('admin.feature-flags.index') }}">Feature Flags</a>
        {{-- Scenarios | Tasks | Roles arrive in 4b/4c --}}
    </nav>

    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif

    <main>
        @yield('content')
    </main>
</body>
</html>
```

**`projects/index.blade.php`**
```blade
@extends('admin.layouts.admin')
@section('title', 'Projects')
@section('content')
    <h1>Project Templates</h1>
    <a href="{{ route('admin.projects.create') }}">+ New Project</a>

    <table>
        <thead><tr><th>Title</th><th>Type</th><th>Difficulty</th><th>Published</th><th></th></tr></thead>
        <tbody>
            @forelse ($projects as $project)
                @include('admin.projects._row', ['project' => $project])
            @empty
                <tr><td colspan="5">No projects yet.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
```

**`projects/_row.blade.php`** (its own file so the publish toggle can swap just this row)
```blade
<tr id="project-row-{{ $project->id() }}">
    <td>{{ $project->toPrimitives()['title'] }}</td>
    <td>{{ $project->toPrimitives()['project_type'] }}</td>
    <td>{{ $project->toPrimitives()['difficulty_level'] }}</td>
    <td>
        <button
            hx-patch="{{ route('admin.projects.publish', $project->id()) }}"
            hx-target="#project-row-{{ $project->id() }}"
            hx-swap="outerHTML">
            {{ $project->isPublished() ? 'Published ✓' : 'Draft' }}
        </button>
    </td>
    <td><a href="{{ route('admin.projects.edit', $project->id()) }}">Edit</a></td>
</tr>
```

**`projects/create.blade.php`**
```blade
@extends('admin.layouts.admin')
@section('title', 'New Project')
@section('content')
    <h1>New Project Template</h1>

    @if ($errors->any())
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif

    <form method="POST" action="{{ route('admin.projects.store') }}">
        @csrf
        <label>Title <input name="title" value="{{ old('title') }}" required></label>
        <label>Project type <input name="project_type" value="{{ old('project_type') }}" required></label>
        <label>Tagline <input name="tagline" value="{{ old('tagline') }}"></label>
        <label>Business domain <input name="business_domain" value="{{ old('business_domain') }}"></label>
        <label>Business context <textarea name="business_context" required>{{ old('business_context') }}</textarea></label>
        <label>Difficulty
            <select name="difficulty_level">
                <option value="beginner">beginner</option>
                <option value="intermediate">intermediate</option>
                <option value="advanced">advanced</option>
            </select>
        </label>
        {{-- specialization_tags[] — one input for now; a repeatable HTMX sub-form comes in 4b --}}
        <label>Specialization tag <input name="specialization_tags[]" value="{{ old('specialization_tags.0') }}" required></label>
        <button type="submit">Create Project</button>
    </form>
@endsection
```

**`projects/edit.blade.php`** — same shape, `@method('PATCH')`, action `admin.projects.update`, pre-filled from `$project->toPrimitives()`. (Left as a small exercise — mirror `create.blade.php`.)

**`feature-flags/index.blade.php`** and **`feature-flags/_row.blade.php`** — a table of flags, each row a toggle button `hx-patch`ing `admin.feature-flags.toggle` with `hx-swap="outerHTML"`, mirroring the project row.

- [ ] Admin layout + project views + feature-flag views created.

---

## 10. Verification Checklist

**Blockers cleared**
- [ ] Registering/logging in works (proves the §1.1 bus fix — dispatch resolves handlers).
- [ ] `php artisan tinker` → `\Src\Organizations\Infrastructure\Persistence\Eloquent\Model\OrganizationModel::count()` runs without "table not found" (§1.2).
- [ ] `php artisan migrate:fresh` clean with the administrator migrations removed (§1.3).

**The Project slice**
- [ ] `composer dump-autoload -o` — no PSR-4 warnings (catches any misplaced handler/namespace).
- [ ] `php artisan route:list --path=admin` shows `admin.projects.*` and `admin.feature-flags.*`, all behind `role:content_author`.
- [ ] As a user **without** `content_author`, `/admin/projects` returns 403; with it, 200.
- [ ] Create a project via the form → row appears in the list, and a matching `rubric_sets` row exists with that `project_id` (proves the aggregate transaction), and `project_templates.rubric_set_id` is populated.
- [ ] Edit updates title/context. The publish button flips Draft ↔ Published ✓ **without a full page reload** (HTMX row swap).
- [ ] Invalid create (blank title) re-renders the form with errors and no DB write.

**Structural**
- [ ] Container resolves the repository: `app(\Src\Simulation\Domain\Project\ProjectTemplateRepository::class)` returns an `EloquentProjectTemplateRepository`.
- [ ] No Eloquent model is imported anywhere under `Application/` (grep) — reads and writes both go through the repository interface.

---

## 11. What 4b / 4c Build on This

- **4b — Scenario & Task authoring.** Same slice shape for `ScenarioTemplate` and `Task` aggregates (each owns its children — reference materials; deliverables/CAC variants/knowledge anchors/guidance prompts — persisted through the root's repository). This is where the **`situation_trigger` UI rendering** open decision (Integration Spec §20) gets resolved — we'll pause on it there. Nested HTMX sub-forms (add/remove deliverable rows) also arrive here.
- **4c — Rubric criteria, artifact vault, and the `MedQueueSeeder`** that finally fills these tables with real content, turning the admin UI from "empty CRUD" into "the thing that loads MedQueue."

Each reuses this phase's command/query/repository/mapper rhythm, so 4b and 4c are mostly repetition with new fields.

---

## 12. Scope Discipline Self-Check (Guidance §7)

- [x] **No new tables** — 4a only writes to Phase-3 tables (`project_templates`, `rubric_sets`). Verified against the live schema.
- [x] **Correct bounded context** — Project authoring in Simulation (owns the tables); feature-flag admin in Shared (owns `FeatureFlagModel`). No cross-context writes.
- [x] **Nothing pulled forward** — no Scenario/Task/criteria code (that's 4b/4c); no runtime/session tables touched.
- [x] **`users`/`learners` split respected** — untouched; `organisation_id` maps to the `organisations` table via the §1.2 fix.
- [x] **Enum integrity** — `difficulty_level` validated against `beginner/intermediate/advanced` (matches the DB enum and `DifficultyLevel`); no invented values.
- [x] **Established patterns mirrored, not reinvented** — entity/VO/repository/mapper/command/handler copy the Identity module; controllers use the existing `CommandBus`/`QueryBus`.
- [x] **FK/aggregate reasoning stated** — the project↔rubric-set 1:1 is modelled as one aggregate with a transactional repository, explained in §2 and §4.2.
- [x] **Runnable verification checklist** (§10), including the RBAC 403 check and the transaction check.
- [x] **Open decisions flagged, not decided** — `situation_trigger` UI deferred to 4b with an explicit note; the `follow_up_prompt_templates` placement you chose (Simulation) is recorded as a documented divergence from the context map.

**Two confirmations before 4b:** (1) the §1.1 bus fix is the load-bearing one — verify registration works after it, because everything downstream depends on it; (2) confirm you're keeping `FollowUpPromptTemplateModel` in Simulation so I note it in the guidance file's §4 as a deliberate divergence.
