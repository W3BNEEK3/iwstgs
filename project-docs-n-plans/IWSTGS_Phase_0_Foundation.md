# IWSTGS — Phase 0: Foundation
**Laravel 13 · HTMX · _HyperScript · MySQL**

> **Goal:** A running Laravel 13 application with the correct folder structure, autoloading, database connection, and shared kernel in place. No domain-specific code yet. When this phase is complete, the app boots, the module structure exists, the shared kernel compiles, all service providers are registered, and the feature flag system is live.
>
> **Rule:** Do not move to Phase 1 until every checkbox in this document is ticked and the verification step at the bottom passes.

---

## Step 0.1 — Laravel Installation

Install the Laravel project and confirm it runs.

- [ ] Run: `composer create-project laravel/laravel iwstgs`
- [ ] Open `composer.json` and set the PHP version constraint:
```json
"require": {
    "php": "^8.3",
    ...
}
```
- [ ] Copy `.env.example` to `.env`
- [ ] Configure your database connection in `.env`:

**Option A — MySQL (recommended):**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iwstgs
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

**Option B — SQLite (quick local start):**
```env
DB_CONNECTION=sqlite
```
Then run: `touch database/database.sqlite`

- [ ] Run: `php artisan key:generate`
- [ ] Run: `php artisan serve`
- [ ] Open `http://127.0.0.1:8000` — confirm the Laravel welcome page loads
- [ ] Confirm no errors in the terminal

---

## Step 0.2 — Base Blade Layout

Create the single base layout that all views in the system will extend. This is also where HTMX and _HyperScript are loaded — no npm, no build step required for the MVP.

- [ ] Create `resources/views/layouts/app.blade.php` with the following structure:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'IWSTGS')</title>

    {{-- HTMX --}}
    <script src="https://unpkg.com/htmx.org@1.9.12" defer></script>

    {{-- _HyperScript --}}
    <script src="https://unpkg.com/hyperscript.org@0.9.12" defer></script>

    @stack('styles')
</head>
<body>
    @yield('content')

    @stack('scripts')
</body>
</html>
```

- [ ] Confirm the file saves without error

> **Note:** You will style this layout properly in Phase 11. For now it just needs to be a valid shell that loads HTMX and _HyperScript.

---

## Step 0.3 — Module Directory Structure

This is the most important structural step in the entire project. Get it right now so you never have to reorganise later.

### Why this structure

The system uses **Domain-Driven Design with a modular monolith** pattern. Each module is a vertical slice of the system — it owns its own domain logic, application services, database layer, and HTTP presentation. Modules do not reach into each other's databases or internal classes directly.

Each module has four layers:
- **Domain** — business rules, entities, value objects, events, repository interfaces
- **Application** — commands, queries, DTOs, orchestration services
- **Infrastructure** — Eloquent models, repositories, external services, service providers
- **Presentation** — controllers, form requests, middleware, Blade component classes

### The critical rule about Domain subfolders

Domain subfolders are named after **domain concepts**, not generic categories. You will never create a folder called `Models/`, `ValueObjects/`, or `Events/` anywhere in `src/`. Instead, a concept like `User` gets its own folder, and everything related to that concept — entity, value objects, domain events, repository interface — lives together inside it.

**Correct:**
```
Identity/Domain/User/
    Learner.php           ← the entity
    LearnerId.php         ← value object, belongs here with Learner
    LearnerRegistered.php ← domain event, belongs here with Learner
    LearnerRepository.php ← interface, belongs here in Domain
```

**Wrong:**
```
Identity/Domain/
    Models/Learner.php
    ValueObjects/LearnerId.php
    Events/LearnerRegistered.php
    Repositories/LearnerRepository.php
```

The wrong version scatters related things by technical type. The correct version groups related things by domain concept.

### Create the structure

Run these commands from your project root:

```bash
# Create all top-level module directories
mkdir -p src/{Shared,Identity,Organizations,Content,Simulation,SimExecution,Competency,EvalEngine,Submission,AIMediation,Reporting}

# Create the standard layer structure inside every module
for module in Shared Identity Organizations Content Simulation SimExecution Competency EvalEngine Submission AIMediation Reporting; do
  # Four layers
  mkdir -p src/$module/{Domain,Application,Infrastructure,Presentation}

  # Application sublayers
  mkdir -p src/$module/Application/{Command,Query,DTO,Service}

  # Infrastructure sublayers
  mkdir -p src/$module/Infrastructure/{Persistence,Provider}
  mkdir -p src/$module/Infrastructure/Persistence/Eloquent/{Model,Repository,Mapper}

  # Presentation sublayers
  mkdir -p src/$module/Presentation/{Http,View}
  mkdir -p src/$module/Presentation/Http/{Controller,Request,Middleware}
  mkdir -p src/$module/Presentation/View/Components
done
```

- [ ] Run the commands above
- [ ] Verify the structure was created: `find src/ -type d | sort | head -60`

You should see output like:
```
src/AIMediation
src/AIMediation/Application
src/AIMediation/Application/Command
src/AIMediation/Application/DTO
src/AIMediation/Application/Query
src/AIMediation/Application/Service
src/AIMediation/Domain
src/AIMediation/Infrastructure
src/AIMediation/Infrastructure/Persistence
src/AIMediation/Infrastructure/Persistence/Eloquent
src/AIMediation/Infrastructure/Persistence/Eloquent/Mapper
src/AIMediation/Infrastructure/Persistence/Eloquent/Model
src/AIMediation/Infrastructure/Persistence/Eloquent/Repository
src/AIMediation/Infrastructure/Provider
src/AIMediation/Presentation
src/AIMediation/Presentation/Http
src/AIMediation/Presentation/Http/Controller
src/AIMediation/Infrastructure/Persistence/Eloquent/Mapper
src/AIMediation/Presentation/Http/Middleware
src/AIMediation/Presentation/Http/Request
src/AIMediation/Presentation/View
src/AIMediation/Presentation/View/Components
...
```

> **Note:** Domain concept subfolders (e.g. `Identity/Domain/User/`, `Simulation/Domain/ProjectDefinition/`) are **not** created now. They are created in the phase where each concept is first built. The `Domain/` folder at this stage is intentionally empty.

### Add `src/` to PSR-4 autoloading

- [ ] Open `composer.json` and update the `autoload` section:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Src\\": "src/"
    }
}
```

- [ ] Run: `composer dump-autoload`

### Verify autoloading works

- [ ] Create a temporary test file: `src/Shared/Domain/AutoloadTest.php`

```php
<?php

namespace Src\Shared\Domain;

class AutoloadTest
{
    public function ping(): string
    {
        return 'autoload works';
    }
}
```

- [ ] Run: `php artisan tinker`
- [ ] In tinker, run: `(new Src\Shared\Domain\AutoloadTest)->ping()`
- [ ] Confirm it returns `'autoload works'`
- [ ] Delete `src/Shared/Domain/AutoloadTest.php` after confirming

---

## Step 0.4 — Shared Kernel

The Shared module contains the base classes that every other module builds on. These are not business logic — they are structural building blocks. Every entity, aggregate root, value object, and domain event in the system will extend or implement something from this kernel.

### Why these files exist

**AggregateRoot** — An aggregate root is the main entry point to a cluster of related domain objects. It is the object you load, change, and save. It also collects domain events that happened during its lifetime (e.g. "a learner was registered") so those events can be dispatched after the save. Without this base class, you would have to repeat this machinery in every entity.

**Entity** — Base class for domain objects that have identity (an ID that persists over time). Two `Learner` objects with the same ID are the same learner, even if their other properties differ.

**ValueObject** — Base class for domain objects defined entirely by their value, not their identity. A `LearnerId` is a value object — two `LearnerId` instances containing the same UUID string are equal. Value objects are immutable.

**DomainEvent** — Base class for things that happened in the domain. Events are facts: `LearnerRegistered`, `TaskSubmitted`. They carry data about what happened and when.

**DomainException** — Base class for business rule violations. Throwing a `DomainException` means "this operation is not allowed because of a business rule", distinct from a system error.

### Create the base domain files

**Note:** These files live directly in `src/Shared/Domain/` — they are not inside a concept subfolder because they belong to no single concept. They are shared primitives.

- [ ] Create `src/Shared/Domain/AggregateRoot.php`:

```php
<?php

namespace Src\Shared\Domain;

abstract class AggregateRoot extends Entity
{
    private array $domainEvents = [];

    protected function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
```

- [ ] Create `src/Shared/Domain/Entity.php`:

```php
<?php

namespace Src\Shared\Domain;

abstract class Entity
{
    public function equals(self $other): bool
    {
        return get_class($this) === get_class($other)
            && $this->id() === $other->id();
    }

    abstract public function id(): string;
}
```

- [ ] Create `src/Shared/Domain/ValueObject.php`:

```php
<?php

namespace Src\Shared\Domain;

abstract class ValueObject
{
    abstract public function equals(self $other): bool;

    abstract public function value(): mixed;
}
```

- [ ] Create `src/Shared/Domain/DomainEvent.php`:

```php
<?php

namespace Src\Shared\Domain;

abstract class DomainEvent
{
    public readonly \DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
    }
}
```

- [ ] Create `src/Shared/Domain/DomainException.php`:

```php
<?php

namespace Src\Shared\Domain;

abstract class DomainException extends \RuntimeException {}
```

### Create the Feature Flag domain objects

Feature flags need to live in the domain so they can be referenced from anywhere without creating circular dependencies. They go in their own concept subfolder inside Shared Domain.

- [ ] Create directory: `src/Shared/Domain/Feature/`

- [ ] Create `src/Shared/Domain/Feature/FeatureFlag.php`:

```php
<?php

namespace Src\Shared\Domain\Feature;

class FeatureFlag
{
    public function __construct(
        public readonly string $flagKey,
        public readonly bool   $isEnabled,
        public readonly string $module,
    ) {}
}
```

- [ ] Create `src/Shared/Domain/Feature/FeatureFlagRepository.php`:

```php
<?php

namespace Src\Shared\Domain\Feature;

interface FeatureFlagRepository
{
    public function findByKey(string $key): ?FeatureFlag;

    /** @return FeatureFlag[] */
    public function all(): array;
}
```

### Create the Availability contracts

These are used by `ProjectTemplate`, `ScenarioTemplate`, and `Task` to enforce the `is_published` / `is_active` pattern consistently.

- [ ] Create directory: `src/Shared/Domain/Contract/`

- [ ] Create `src/Shared/Domain/Contract/HasAvailability.php`:

```php
<?php

namespace Src\Shared\Domain\Contract;

interface HasAvailability
{
    public function isPublished(): bool;
    public function isActive(): bool;
}
```

- [ ] Create directory: `src/Shared/Domain/Enum/`

- [ ] Create `src/Shared/Domain/Enum/AvailabilityStatus.php`:

```php
<?php

namespace Src\Shared\Domain\Enum;

enum AvailabilityStatus: string
{
    case Published = 'published';
    case Draft     = 'draft';
    case Archived  = 'archived';
}
```

### Create the Application Bus

The command bus and query bus allow controllers and services to dispatch commands and queries without knowing which handler will process them. This keeps controllers thin and testable.

**Why a bus?** Without a bus, your controller would directly instantiate handlers:
```php
// Without bus — controller knows too much
$handler = new RegisterLearnerHandler($repo, $hasher);
$handler->handle($command);
```

With a bus, the controller just dispatches:
```php
// With bus — controller knows nothing about the handler
$this->commandBus->dispatch($command);
```

The bus resolves the handler from the Laravel container. This means you can swap handlers, add logging, wrap in transactions — all without touching the controller.

- [ ] Create directory: `src/Shared/Application/Bus/`

- [ ] Create `src/Shared/Application/Bus/CommandBus.php`:

```php
<?php

namespace Src\Shared\Application\Bus;

interface CommandBus
{
    public function dispatch(object $command): void;
}
```

- [ ] Create `src/Shared/Application/Bus/QueryBus.php`:

```php
<?php

namespace Src\Shared\Application\Bus;

interface QueryBus
{
    public function ask(object $query): mixed;
}
```

- [ ] Create `src/Shared/Application/Bus/SynchronousCommandBus.php`:

```php
<?php

namespace Src\Shared\Application\Bus;

use Illuminate\Contracts\Container\Container;

class SynchronousCommandBus implements CommandBus
{
    public function __construct(private readonly Container $container) {}

    public function dispatch(object $command): void
    {
        // Derive handler class name from command class name
        // e.g. RegisterLearnerCommand → RegisterLearnerHandler
        $handlerClass = str_replace('Command', 'Handler', get_class($command));
        $handler = $this->container->make($handlerClass);
        $handler->handle($command);
    }
}
```

- [ ] Create `src/Shared/Application/Bus/SynchronousQueryBus.php`:

```php
<?php

namespace Src\Shared\Application\Bus;

use Illuminate\Contracts\Container\Container;

class SynchronousQueryBus implements QueryBus
{
    public function __construct(private readonly Container $container) {}

    public function ask(object $query): mixed
    {
        $handlerClass = str_replace('Query', 'Handler', get_class($query));
        $handler = $this->container->make($handlerClass);
        return $handler->handle($query);
    }
}
```

### Create Infrastructure support files

- [ ] Create `src/Shared/Infrastructure/Persistence/BaseEloquentRepository.php`:

```php
<?php

namespace Src\Shared\Infrastructure\Persistence;

abstract class BaseEloquentRepository
{
    // Shared query helpers can be added here as needs arise.
    // Keep this empty for now — do not add anything until you have
    // a concrete duplication problem to solve.
}
```

- [ ] Create `src/Shared/Infrastructure/Id/UuidGenerator.php`:

```php
<?php

namespace Src\Shared\Infrastructure\Id;

use Illuminate\Support\Str;

class UuidGenerator
{
    public function generate(): string
    {
        return (string) Str::uuid();
    }
}
```

- [ ] Create `src/Shared/Infrastructure/Clock/SystemClock.php`:

```php
<?php

namespace Src\Shared\Infrastructure\Clock;

class SystemClock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public function nowAsString(): string
    {
        return now()->toDateTimeString();
    }
}
```

---

## Step 0.5 — Service Providers

Every module registers its own service provider. The service provider is where:
- Repository interfaces are bound to their Eloquent implementations
- Event listeners are registered
- Route files are loaded

Right now most providers are **stubs** — they exist and are registered but contain no bindings. You will fill them in during each phase. The `SharedServiceProvider` is the exception — it needs to be complete now because everything else depends on it.

### SharedServiceProvider (complete now)

- [ ] Create `src/Shared/Infrastructure/Provider/SharedServiceProvider.php`:

```php
<?php

namespace Src\Shared\Infrastructure\Provider;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Application\Bus\SynchronousCommandBus;
use Src\Shared\Application\Bus\SynchronousQueryBus;
use Src\Shared\Domain\Feature\FeatureFlagRepository;
use Src\Shared\Infrastructure\Feature\EloquentFeatureFlagRepository;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;

class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FeatureFlagRepository::class, EloquentFeatureFlagRepository::class);
        $this->app->bind(CommandBus::class, SynchronousCommandBus::class);
        $this->app->bind(QueryBus::class, SynchronousQueryBus::class);
    }

    public function boot(): void
    {
        // Feature flag Blade directive
        Blade::if('feature', function (string $key) {
            return app(FeatureFlagService::class)->isEnabled($key);
        });

        // Route files
        Route::middleware('web')->group(base_path('routes/identity.php'));
        Route::middleware(['web', 'auth'])->group(base_path('routes/learn.php'));
        Route::middleware(['web', 'auth'])->group(base_path('routes/reporting.php'));
        Route::middleware(['web', 'auth', 'role:content_author'])->group(base_path('routes/admin.php'));
    }
}
```

### Stub providers (all other modules)

Create each of these as an empty stub. Copy this template and change the namespace and class name for each:

```php
<?php

namespace Src\[Module]\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;

class [Module]ServiceProvider extends ServiceProvider
{
    public function register(): void {}
    public function boot(): void {}
}
```

- [ ] Create `src/Identity/Infrastructure/Provider/IdentityServiceProvider.php`
- [ ] Create `src/Organizations/Infrastructure/Provider/OrganizationsServiceProvider.php`
- [ ] Create `src/Content/Infrastructure/Provider/ContentServiceProvider.php`
- [ ] Create `src/Simulation/Infrastructure/Provider/SimulationServiceProvider.php`
- [ ] Create `src/SimExecution/Infrastructure/Provider/SimExecutionServiceProvider.php`
- [ ] Create `src/Competency/Infrastructure/Provider/CompetencyServiceProvider.php`
- [ ] Create `src/EvalEngine/Infrastructure/Provider/EvalEngineServiceProvider.php`
- [ ] Create `src/Submission/Infrastructure/Provider/SubmissionServiceProvider.php`
- [ ] Create `src/AIMediation/Infrastructure/Provider/AIMediationServiceProvider.php`
- [ ] Create `src/Reporting/Infrastructure/Provider/ReportingServiceProvider.php`

### Register all providers

- [ ] Open `bootstrap/providers.php` and add all providers:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,

    // IWSTGS modules
    Src\Shared\Infrastructure\Provider\SharedServiceProvider::class,
    Src\Identity\Infrastructure\Provider\IdentityServiceProvider::class,
    Src\Organizations\Infrastructure\Provider\OrganizationsServiceProvider::class,
    Src\Content\Infrastructure\Provider\ContentServiceProvider::class,
    Src\Simulation\Infrastructure\Provider\SimulationServiceProvider::class,
    Src\SimExecution\Infrastructure\Provider\SimExecutionServiceProvider::class,
    Src\Competency\Infrastructure\Provider\CompetencyServiceProvider::class,
    Src\EvalEngine\Infrastructure\Provider\EvalEngineServiceProvider::class,
    Src\Submission\Infrastructure\Provider\SubmissionServiceProvider::class,
    Src\AIMediation\Infrastructure\Provider\AIMediationServiceProvider::class,
    Src\Reporting\Infrastructure\Provider\ReportingServiceProvider::class,
];
```

- [ ] Run `php artisan serve` — confirm it still boots without errors after adding providers
- [ ] Run `php artisan config:clear` and `php artisan cache:clear` if you see any caching issues

---

## Step 0.6 — Route Files

Create four empty route files. They will be populated in later phases. They must exist now because `SharedServiceProvider` references them.

- [ ] Create `routes/identity.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

// Identity routes are registered here (Phase 1)
```

- [ ] Create `routes/learn.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

// Learner-facing simulation routes (Phases 5–9)
```

- [ ] Create `routes/reporting.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

// Profile and qualification routes (Phase 10)
```

- [ ] Create `routes/admin.php`:
```php
<?php

use Illuminate\Support\Facades\Route;

// Admin content management routes (Phase 4)
```

- [ ] Run `php artisan serve` — confirm it still boots

---

## Step 0.7 — Feature Flag System

The feature flag system is built in three parts: the database table, the Eloquent model and repository, and the service that wraps them with caching. This must be complete before seeding flags, which must happen before any feature-gated code can run.

### Migration

- [ ] Create migration with the name prefix `001`:
```bash
php artisan make:migration 001_create_feature_flags_table
```

- [ ] Open the generated file and write the `up()` method:

```php
public function up(): void
{
    Schema::create('feature_flags', function (Blueprint $table) {
        $table->id();
        $table->string('flag_key', 200)->unique();
        $table->text('description')->nullable();
        $table->boolean('is_enabled')->default(false);
        $table->string('module', 100);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('feature_flags');
}
```

- [ ] Run: `php artisan migrate`
- [ ] Verify: `SHOW TABLES;` in MySQL — `feature_flags` table exists

### Eloquent Model

- [ ] Create `src/Shared/Infrastructure/Persistence/Eloquent/Model/FeatureFlagModel.php`:

```php
<?php

namespace Src\Shared\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;

class FeatureFlagModel extends Model
{
    protected $table = 'feature_flags';

    protected $fillable = [
        'flag_key',
        'description',
        'is_enabled',
        'module',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];
}
```

### Repository Implementation

- [ ] Create `src/Shared/Infrastructure/Feature/EloquentFeatureFlagRepository.php`:

```php
<?php

namespace Src\Shared\Infrastructure\Feature;

use Src\Shared\Domain\Feature\FeatureFlag;
use Src\Shared\Domain\Feature\FeatureFlagRepository;
use Src\Shared\Infrastructure\Persistence\Eloquent\Model\FeatureFlagModel;

class EloquentFeatureFlagRepository implements FeatureFlagRepository
{
    public function findByKey(string $key): ?FeatureFlag
    {
        $model = FeatureFlagModel::where('flag_key', $key)->first();

        if ($model === null) {
            return null;
        }

        return new FeatureFlag(
            flagKey:   $model->flag_key,
            isEnabled: $model->is_enabled,
            module:    $model->module,
        );
    }

    public function all(): array
    {
        return FeatureFlagModel::all()
            ->map(fn($m) => new FeatureFlag($m->flag_key, $m->is_enabled, $m->module))
            ->all();
    }
}
```

### Feature Flag Service

- [ ] Create `src/Shared/Infrastructure/Feature/FeatureFlagService.php`:

```php
<?php

namespace Src\Shared\Infrastructure\Feature;

use Illuminate\Support\Facades\Cache;
use Src\Shared\Domain\Feature\FeatureFlagRepository;

class FeatureFlagService
{
    public function __construct(
        private readonly FeatureFlagRepository $repository
    ) {}

    public function isEnabled(string $key): bool
    {
        return Cache::remember(
            "feature_flag:{$key}",
            60,
            function () use ($key) {
                $flag = $this->repository->findByKey($key);
                return $flag?->isEnabled ?? false;
            }
        );
    }

    public function enable(string $key): void
    {
        \Src\Shared\Infrastructure\Persistence\Eloquent\Model\FeatureFlagModel
            ::where('flag_key', $key)
            ->update(['is_enabled' => true]);

        Cache::forget("feature_flag:{$key}");
    }

    public function disable(string $key): void
    {
        \Src\Shared\Infrastructure\Persistence\Eloquent\Model\FeatureFlagModel
            ::where('flag_key', $key)
            ->update(['is_enabled' => false]);

        Cache::forget("feature_flag:{$key}");
    }
}
```

### Seeder

- [ ] Create `database/seeders/FeatureFlagSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            ['flag_key' => 'admin.content_management',         'module' => 'Content',        'description' => 'Enables the admin content management UI for projects, scenarios, and tasks.'],
            ['flag_key' => 'organisations.multi_tenancy',       'module' => 'Organizations',  'description' => 'Enables organisation-scoped data filtering and multi-tenancy enforcement.'],
            ['flag_key' => 'simulation.project_catalogue',      'module' => 'SimExecution',   'description' => 'Enables the learner-facing project catalogue and enrolment flow.'],
            ['flag_key' => 'simulation.diagnostic_assessment',  'module' => 'SimExecution',   'description' => 'Enables the diagnostic scenario pathway for initial rank assignment.'],
            ['flag_key' => 'simulation.session_onboarding',     'module' => 'SimExecution',   'description' => 'Enables scenario briefing, situation trigger display, and induction flow.'],
            ['flag_key' => 'simulation.sprint_planning',        'module' => 'SimExecution',   'description' => 'Enables the sprint planning view with backlog and sprint goal interaction.'],
            ['flag_key' => 'simulation.sprint_board',           'module' => 'SimExecution',   'description' => 'Enables the Kanban sprint board and Artifact Vault access.'],
            ['flag_key' => 'simulation.task_submission',        'module' => 'Submission',     'description' => 'Enables the task submission form and four-layer submission assembly.'],
            ['flag_key' => 'submission.code_execution',         'module' => 'Submission',     'description' => 'Enables Docker-based code execution for code deliverable tasks. Leave disabled until Phase 11.'],
            ['flag_key' => 'aimediation.claude_evaluation',     'module' => 'AIMediation',    'description' => 'Enables live Claude API evaluation of submissions. When disabled, submissions are queued for human review.'],
            ['flag_key' => 'adaptive.consequence_tasks',        'module' => 'EvalEngine',     'description' => 'Enables automatic injection of consequence task cards on failed submissions.'],
            ['flag_key' => 'adaptive.suggestion_tasks',         'module' => 'EvalEngine',     'description' => 'Enables habit pattern detection and suggestion task injection between scenarios.'],
            ['flag_key' => 'adaptive.rank_management',          'module' => 'EvalEngine',     'description' => 'Enables automatic rank escalation, de-escalation, and mismatch detection.'],
            ['flag_key' => 'adaptive.scenario_transitions',     'module' => 'EvalEngine',     'description' => 'Enables automatic scenario transitions and dimension-targeted next scenario routing.'],
            ['flag_key' => 'reporting.learner_profile',         'module' => 'Reporting',      'description' => 'Enables the learner profile dashboard with radar chart and competency summary.'],
            ['flag_key' => 'reporting.qualification_report',    'module' => 'Reporting',      'description' => 'Enables the role qualification report comparing learner scores to role thresholds.'],
            ['flag_key' => 'reporting.admin_analytics',         'module' => 'Reporting',      'description' => 'Enables the admin reporting views for learner progress and task performance.'],
        ];

        foreach ($flags as $flag) {
            DB::table('feature_flags')->insertOrIgnore(array_merge($flag, [
                'is_enabled' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
```

- [ ] Run: `php artisan db:seed --class=FeatureFlagSeeder`
- [ ] Verify: `SELECT flag_key, is_enabled FROM feature_flags;` — 17 rows, all `is_enabled = 0`

---

## Phase 0 Verification Checklist

Do not proceed to Phase 1 until all of these pass:

- [ ] `php artisan serve` boots without errors
- [ ] `http://127.0.0.1:8000` loads the Laravel welcome page
- [ ] `find src/ -type d | wc -l` returns at least 88 directories (11 modules × ~8 folders each)
- [ ] `composer dump-autoload` runs without errors
- [ ] Tinker test: `(new Src\Shared\Domain\AggregateRoot)` — should show the class exists (it's abstract, but you can confirm the namespace resolves)
- [ ] `php artisan config:cache` runs without errors
- [ ] `SELECT COUNT(*) FROM feature_flags;` returns 17
- [ ] `SELECT is_enabled FROM feature_flags LIMIT 1;` returns `0`
- [ ] In `tinker`: `app(Src\Shared\Application\Bus\CommandBus::class)` — returns a `SynchronousCommandBus` instance
- [ ] In `tinker`: `app(Src\Shared\Infrastructure\Feature\FeatureFlagService::class)->isEnabled('admin.content_management')` — returns `false`
- [ ] Add `@feature('admin.content_management') <p>on</p> @endfeature` to a test Blade view — confirm nothing renders (flag is disabled)
- [ ] Remove the test Blade snippet after confirming
