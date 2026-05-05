# IWSTGS — Phase 1: Identity & Access
**Laravel 13 · HTMX · _HyperScript · MySQL**

> **Prerequisite:** Phase 0 must be fully complete. The app boots, all 11 module directories exist, the shared kernel compiles, all service providers are registered, and the feature flag table is seeded.
>
> **Goal:** Learners can register, log in, and log out. The system knows their role. Route access is controlled by that role. Organisations exist in the database as a stub for later multi-tenancy. The Identity bounded context is fully wired from domain entity through to HTTP controller.
>
> **Rule:** Do not move to Phase 2 until the verification checklist at the bottom passes completely.

---

## What You Are Building and Why

This phase builds the **Identity bounded context** — the part of the system that knows who is using it and what they are allowed to do.

The flow of a request through this context follows the DDD layers exactly:

```
HTTP Request
    → Controller (Presentation layer)
        → Command (Application layer)
            → Handler (Application layer)
                → Domain Entity (Domain layer)
                    → Repository Interface (Domain layer)
                        → Eloquent Repository (Infrastructure layer)
                            → Mapper (Infrastructure layer)
                                → Eloquent Model (Infrastructure layer)
                                    → MySQL
```

Every layer has a single responsibility. The controller knows nothing about database queries. The domain entity knows nothing about HTTP. The Eloquent model knows nothing about business rules. This separation is what makes the system testable and maintainable as it grows.

---

## Step 1.1 — Migrations

Create and run the four Identity migrations before writing any PHP classes. The database structure must exist before you can test anything.

### Migration 002 — organisations

- [ ] Run: `php artisan make:migration 002_create_organisations_table`
- [ ] Open the generated file and write:

```php
public function up(): void
{
    Schema::create('organisations', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name', 300);
        $table->string('slug', 100)->unique();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('organisations');
}
```

### Migration 003 — learners

- [ ] Run: `php artisan make:migration 003_create_learners_table`

```php
public function up(): void
{
    Schema::create('learners', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('full_name', 200);
        $table->string('email', 320)->unique();
        $table->string('password', 255);
        $table->enum('entry_category', ['inexperienced', 'experienced']);
        $table->smallInteger('years_experience')->unsigned()->nullable();
        $table->uuid('organisation_id')->nullable();
        $table->foreign('organisation_id')->references('id')->on('organisations')->nullOnDelete();
        $table->rememberToken();
        $table->timestamp('last_active_at')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('learners');
}
```

### Migration 004 — roles

- [ ] Run: `php artisan make:migration 004_create_roles_table`

```php
public function up(): void
{
    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name', 100)->unique();
        $table->text('description')->nullable();
    });
}

public function down(): void
{
    Schema::dropIfExists('roles');
}
```

### Migration 005 — learner_role pivot

- [ ] Run: `php artisan make:migration 005_create_learner_role_table`

```php
public function up(): void
{
    Schema::create('learner_role', function (Blueprint $table) {
        $table->uuid('learner_id');
        $table->unsignedBigInteger('role_id');
        $table->foreign('learner_id')->references('id')->on('learners')->cascadeOnDelete();
        $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        $table->primary(['learner_id', 'role_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('learner_role');
}
```

### Run and verify

- [ ] Run: `php artisan migrate`
- [ ] Verify in MySQL: `SHOW TABLES;` — confirm `organisations`, `learners`, `roles`, `learner_role` all exist
- [ ] Verify columns: `DESCRIBE learners;` — confirm `entry_category` is ENUM, `id` is CHAR(36)

### Role Seeder

- [ ] Create `database/seeders/RoleSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin',    'description' => 'Full system access. Can manage all content and all organisations.'],
            ['name' => 'org_admin',      'description' => 'Organisation-scoped admin. Can manage learners within their organisation.'],
            ['name' => 'content_author', 'description' => 'Can create and manage projects, scenarios, tasks, and rubric criteria.'],
            ['name' => 'learner',        'description' => 'Can enrol in projects and participate in simulations.'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore($role);
        }
    }
}
```

- [ ] Run: `php artisan db:seed --class=RoleSeeder`
- [ ] Verify: `SELECT * FROM roles;` — 4 rows

---

## Step 1.2 — Domain Layer

The domain layer defines what a `Learner` is, what it can do, and what business rules apply. It has no knowledge of Laravel, Eloquent, HTTP, or any external framework.

### Create the domain concept directory

- [ ] Create directory: `src/Identity/Domain/User/`
- [ ] Create directory: `src/Identity/Domain/Role/`
- [ ] Create directory: `src/Identity/Domain/Auth/`
- [ ] Create directory: `src/Identity/Domain/Event/`
- [ ] Create directory: `src/Identity/Domain/Exception/`
- [ ] Create directory: `src/Identity/Domain/Permission/`

### LearnerId — Value Object

A value object has no identity of its own — it is defined entirely by its value. `LearnerId` wraps a UUID string and gives it meaning in the domain.

- [ ] Create `src/Identity/Domain/User/LearnerId.php`:

```php
<?php

namespace Src\Identity\Domain\User;

use Src\Shared\Domain\ValueObject;

final class LearnerId extends ValueObject
{
    public function __construct(private readonly string $uuid) {}

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public static function generate(): self
    {
        return new self((string) \Illuminate\Support\Str::uuid());
    }

    public function value(): string
    {
        return $this->uuid;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $this->uuid === $other->uuid;
    }

    public function __toString(): string
    {
        return $this->uuid;
    }
}
```

### EntryCategory — Value Object Enum

- [ ] Create `src/Identity/Domain/User/EntryCategory.php`:

```php
<?php

namespace Src\Identity\Domain\User;

enum EntryCategory: string
{
    case Inexperienced = 'inexperienced';
    case Experienced   = 'experienced';
}
```

### LearnerRegistered — Domain Event

Domain events are facts. `LearnerRegistered` says "a learner was registered". It carries the data needed by listeners (e.g. the profile auto-creation observer in Phase 5).

- [ ] Create `src/Identity/Domain/User/LearnerRegistered.php`:

```php
<?php

namespace Src\Identity\Domain\User;

use Src\Shared\Domain\DomainEvent;

final class LearnerRegistered extends DomainEvent
{
    public function __construct(
        public readonly string $learnerId,
        public readonly string $email,
        public readonly string $fullName,
    ) {
        parent::__construct();
    }
}
```

### LearnerRepository — Interface

The repository interface lives in the domain because the domain defines what persistence operations it needs. The domain says "I need to be able to save a learner and find one by email" — it does not say how.

- [ ] Create `src/Identity/Domain/User/LearnerRepository.php`:

```php
<?php

namespace Src\Identity\Domain\User;

interface LearnerRepository
{
    public function save(Learner $learner): void;

    public function findByEmail(string $email): ?Learner;

    public function findById(LearnerId $id): ?Learner;

    public function existsByEmail(string $email): bool;
}
```

### Learner — Aggregate Root

The `Learner` is an Aggregate Root. It is the main domain entity for this context. It uses a static factory method (`Learner::register(...)`) rather than a constructor — this makes it explicit that registering a learner is a meaningful domain operation, not just object instantiation.

- [ ] Create `src/Identity/Domain/User/Learner.php`:

```php
<?php

namespace Src\Identity\Domain\User;

use Src\Shared\Domain\AggregateRoot;

final class Learner extends AggregateRoot
{
    private function __construct(
        private readonly LearnerId   $learnerId,
        private string               $fullName,
        private string               $email,
        private string               $hashedPassword,
        private EntryCategory        $entryCategory,
        private ?int                 $yearsExperience,
        private ?string              $organisationId,
    ) {}

    public static function register(
        LearnerId     $id,
        string        $fullName,
        string        $email,
        string        $hashedPassword,
        EntryCategory $entryCategory,
        ?int          $yearsExperience = null,
        ?string       $organisationId = null,
    ): self {
        $learner = new self(
            $id,
            $fullName,
            $email,
            $hashedPassword,
            $entryCategory,
            $yearsExperience,
            $organisationId,
        );

        $learner->recordEvent(new LearnerRegistered(
            learnerId: (string) $id,
            email:     $email,
            fullName:  $fullName,
        ));

        return $learner;
    }

    // Reconstitute from persistence (no event recorded — this already happened)
    public static function reconstitute(
        LearnerId     $id,
        string        $fullName,
        string        $email,
        string        $hashedPassword,
        EntryCategory $entryCategory,
        ?int          $yearsExperience,
        ?string       $organisationId,
    ): self {
        return new self($id, $fullName, $email, $hashedPassword, $entryCategory, $yearsExperience, $organisationId);
    }

    public function id(): string
    {
        return (string) $this->learnerId;
    }

    public function learnerId(): LearnerId    { return $this->learnerId; }
    public function fullName(): string        { return $this->fullName; }
    public function email(): string           { return $this->email; }
    public function hashedPassword(): string  { return $this->hashedPassword; }
    public function entryCategory(): EntryCategory { return $this->entryCategory; }
    public function yearsExperience(): ?int   { return $this->yearsExperience; }
    public function organisationId(): ?string { return $this->organisationId; }
}
```

### Role domain objects

- [ ] Create `src/Identity/Domain/Role/RoleName.php`:

```php
<?php

namespace Src\Identity\Domain\Role;

enum RoleName: string
{
    case SuperAdmin    = 'super_admin';
    case OrgAdmin      = 'org_admin';
    case ContentAuthor = 'content_author';
    case Learner       = 'learner';
}
```

- [ ] Create `src/Identity/Domain/Role/Role.php`:

```php
<?php

namespace Src\Identity\Domain\Role;

use Src\Shared\Domain\Entity;

final class Role extends Entity
{
    public function __construct(
        private readonly int      $roleId,
        private readonly RoleName $name,
        private readonly ?string  $description,
    ) {}

    public function id(): string { return (string) $this->roleId; }
    public function name(): RoleName { return $this->name; }
    public function description(): ?string { return $this->description; }
}
```

### Auth and Exception interfaces

- [ ] Create `src/Identity/Domain/Auth/AuthenticationService.php`:

```php
<?php

namespace Src\Identity\Domain\Auth;

use Src\Identity\Domain\User\Learner;

interface AuthenticationService
{
    public function authenticate(string $email, string $password): Learner;

    public function logout(): void;
}
```

- [ ] Create `src/Identity/Domain/Exception/InvalidCredentialsException.php`:

```php
<?php

namespace Src\Identity\Domain\Exception;

use Src\Shared\Domain\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The provided credentials are incorrect.');
    }
}
```

- [ ] Create `src/Identity/Domain/Exception/EmailAlreadyTakenException.php`:

```php
<?php

namespace Src\Identity\Domain\Exception;

use Src\Shared\Domain\DomainException;

final class EmailAlreadyTakenException extends DomainException
{
    public function __construct(string $email)
    {
        parent::__construct("The email address '{$email}' is already registered.");
    }
}
```

---

## Step 1.3 — Application Layer

The Application layer orchestrates the domain. Handlers receive commands, call domain entities and repository interfaces, and return nothing (commands) or data (queries). They know about the domain but nothing about HTTP.

### RegisterLearner Command and Handler

- [ ] Create directory: `src/Identity/Application/Command/RegisterLearner/`

- [ ] Create `src/Identity/Application/Command/RegisterLearner/RegisterLearnerCommand.php`:

```php
<?php

namespace Src\Identity\Application\Command\RegisterLearner;

final class RegisterLearnerCommand
{
    public function __construct(
        public readonly string  $fullName,
        public readonly string  $email,
        public readonly string  $password,
        public readonly string  $entryCategory,
        public readonly ?int    $yearsExperience = null,
        public readonly ?string $organisationId  = null,
    ) {}
}
```

- [ ] Create `src/Identity/Application/Command/RegisterLearner/RegisterLearnerHandler.php`:

```php
<?php

namespace Src\Identity\Application\Command\RegisterLearner;

use Src\Identity\Domain\Exception\EmailAlreadyTakenException;
use Src\Identity\Domain\User\EntryCategory;
use Src\Identity\Domain\User\Learner;
use Src\Identity\Domain\User\LearnerId;
use Src\Identity\Domain\User\LearnerRepository;
use Src\Shared\Infrastructure\Id\UuidGenerator;
use Illuminate\Support\Facades\Hash;

final class RegisterLearnerHandler
{
    public function __construct(
        private readonly LearnerRepository $repository,
        private readonly UuidGenerator     $uuidGenerator,
    ) {}

    public function handle(RegisterLearnerCommand $command): void
    {
        if ($this->repository->existsByEmail($command->email)) {
            throw new EmailAlreadyTakenException($command->email);
        }

        $learner = Learner::register(
            id:              LearnerId::fromString($this->uuidGenerator->generate()),
            fullName:        $command->fullName,
            email:           $command->email,
            hashedPassword:  Hash::make($command->password),
            entryCategory:   EntryCategory::from($command->entryCategory),
            yearsExperience: $command->yearsExperience,
            organisationId:  $command->organisationId,
        );

        $this->repository->save($learner);

        // Dispatch domain events via Laravel's event system
        foreach ($learner->releaseEvents() as $event) {
            event($event);
        }
    }
}
```

### LoginLearner Command and Handler

- [ ] Create directory: `src/Identity/Application/Command/LoginLearner/`

- [ ] Create `src/Identity/Application/Command/LoginLearner/LoginLearnerCommand.php`:

```php
<?php

namespace Src\Identity\Application\Command\LoginLearner;

final class LoginLearnerCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool   $remember = false,
    ) {}
}
```

- [ ] Create `src/Identity/Application/Command/LoginLearner/LoginLearnerHandler.php`:

```php
<?php

namespace Src\Identity\Application\Command\LoginLearner;

use Src\Identity\Domain\Auth\AuthenticationService;

final class LoginLearnerHandler
{
    public function __construct(
        private readonly AuthenticationService $authService,
    ) {}

    public function handle(LoginLearnerCommand $command): void
    {
        $this->authService->authenticate($command->email, $command->password);
    }
}
```

### LearnerDTO

DTOs carry data out of the application layer for use by controllers and views. They are plain data containers with no logic.

- [ ] Create `src/Identity/Application/DTO/LearnerDTO.php`:

```php
<?php

namespace Src\Identity\Application\DTO;

final class LearnerDTO
{
    public function __construct(
        public readonly string  $id,
        public readonly string  $fullName,
        public readonly string  $email,
        public readonly string  $entryCategory,
        public readonly ?int    $yearsExperience,
        public readonly string  $rankTier,
        public readonly int     $rankLevel,
    ) {}
}
```

### RegistrationService

- [ ] Create `src/Identity/Application/Service/RegistrationService.php`:

```php
<?php

namespace Src\Identity\Application\Service;

use Src\Identity\Application\Command\RegisterLearner\RegisterLearnerCommand;
use Src\Shared\Application\Bus\CommandBus;

final class RegistrationService
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function register(
        string  $fullName,
        string  $email,
        string  $password,
        string  $entryCategory,
        ?int    $yearsExperience = null,
        ?string $organisationId  = null,
    ): void {
        $this->commandBus->dispatch(new RegisterLearnerCommand(
            fullName:        $fullName,
            email:           $email,
            password:        $password,
            entryCategory:   $entryCategory,
            yearsExperience: $yearsExperience,
            organisationId:  $organisationId,
        ));
    }
}
```

---

## Step 1.4 — Infrastructure Layer

The Infrastructure layer contains the concrete implementations of interfaces defined in the Domain layer. This is where Eloquent, Laravel's Auth facade, and bcrypt hashing actually live.

### Eloquent Models

**Important:** Eloquent models in this architecture are persistence representations only. They are NOT domain entities. They hold no business logic. The `LearnerModel` knows about the database. The `Learner` domain entity knows about business rules. They are separate things connected only by the `LearnerMapper`.

- [ ] Create `src/Identity/Infrastructure/Persistence/Eloquent/Model/LearnerModel.php`:

```php
<?php

namespace Src\Identity\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class LearnerModel extends Authenticatable
{
    use HasUuids;

    protected $table = 'learners';

    protected $fillable = [
        'id',
        'full_name',
        'email',
        'password',
        'entry_category',
        'years_experience',
        'organisation_id',
        'last_active_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_active_at'   => 'datetime',
        'years_experience' => 'integer',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'learner_role', 'learner_id', 'role_id');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }
}
```

- [ ] Create `src/Identity/Infrastructure/Persistence/Eloquent/Model/RoleModel.php`:

```php
<?php

namespace Src\Identity\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RoleModel extends Model
{
    protected $table = 'roles';
    public $timestamps = false;

    protected $fillable = ['name', 'description'];

    public function learners(): BelongsToMany
    {
        return $this->belongsToMany(LearnerModel::class, 'learner_role', 'role_id', 'learner_id');
    }
}
```

### LearnerMapper

The mapper converts between the Eloquent model (a database row representation) and the domain entity (a business object). This is the bridge between the infrastructure and the domain.

- [ ] Create `src/Identity/Infrastructure/Persistence/Eloquent/Mapper/LearnerMapper.php`:

```php
<?php

namespace Src\Identity\Infrastructure\Persistence\Eloquent\Mapper;

use Src\Identity\Domain\User\EntryCategory;
use Src\Identity\Domain\User\Learner;
use Src\Identity\Domain\User\LearnerId;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\LearnerModel;

final class LearnerMapper
{
    public function toEntity(LearnerModel $model): Learner
    {
        return Learner::reconstitute(
            id:              LearnerId::fromString($model->id),
            fullName:        $model->full_name,
            email:           $model->email,
            hashedPassword:  $model->password,
            entryCategory:   EntryCategory::from($model->entry_category),
            yearsExperience: $model->years_experience,
            organisationId:  $model->organisation_id,
        );
    }

    public function toModel(Learner $entity): LearnerModel
    {
        return new LearnerModel([
            'id'               => (string) $entity->learnerId(),
            'full_name'        => $entity->fullName(),
            'email'            => $entity->email(),
            'password'         => $entity->hashedPassword(),
            'entry_category'   => $entity->entryCategory()->value,
            'years_experience' => $entity->yearsExperience(),
            'organisation_id'  => $entity->organisationId(),
        ]);
    }
}
```

### EloquentLearnerRepository

The repository implementation uses Eloquent internally but returns domain entities. The domain layer never sees an Eloquent model.

- [ ] Create `src/Identity/Infrastructure/Persistence/Eloquent/Repository/EloquentLearnerRepository.php`:

```php
<?php

namespace Src\Identity\Infrastructure\Persistence\Eloquent\Repository;

use Src\Identity\Domain\User\Learner;
use Src\Identity\Domain\User\LearnerId;
use Src\Identity\Domain\User\LearnerRepository;
use Src\Identity\Infrastructure\Persistence\Eloquent\Mapper\LearnerMapper;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\LearnerModel;

final class EloquentLearnerRepository implements LearnerRepository
{
    public function __construct(private readonly LearnerMapper $mapper) {}

    public function save(Learner $learner): void
    {
        $model = $this->mapper->toModel($learner);
        $model->save();
    }

    public function findByEmail(string $email): ?Learner
    {
        $model = LearnerModel::where('email', $email)->first();
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findById(LearnerId $id): ?Learner
    {
        $model = LearnerModel::find((string) $id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function existsByEmail(string $email): bool
    {
        return LearnerModel::where('email', $email)->exists();
    }
}
```

### LaravelAuthService

- [ ] Create `src/Identity/Infrastructure/Auth/LaravelAuthService.php`:

```php
<?php

namespace Src\Identity\Infrastructure\Auth;

use Illuminate\Support\Facades\Auth;
use Src\Identity\Domain\Auth\AuthenticationService;
use Src\Identity\Domain\Exception\InvalidCredentialsException;
use Src\Identity\Domain\User\Learner;
use Src\Identity\Domain\User\LearnerRepository;
use Src\Identity\Domain\User\LearnerId;
use Src\Identity\Infrastructure\Persistence\Eloquent\Mapper\LearnerMapper;

final class LaravelAuthService implements AuthenticationService
{
    public function __construct(
        private readonly LearnerRepository $repository,
    ) {}

    public function authenticate(string $email, string $password): Learner
    {
        $success = Auth::attempt(['email' => $email, 'password' => $password]);

        if (! $success) {
            throw new InvalidCredentialsException();
        }

        $learner = $this->repository->findByEmail($email);

        if ($learner === null) {
            throw new InvalidCredentialsException();
        }

        return $learner;
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
```

### BcryptHasher

- [ ] Create `src/Identity/Infrastructure/Hashing/BcryptHasher.php`:

```php
<?php

namespace Src\Identity\Infrastructure\Hashing;

use Illuminate\Support\Facades\Hash;

final class BcryptHasher
{
    public function make(string $plaintext): string
    {
        return Hash::make($plaintext);
    }

    public function check(string $plaintext, string $hashed): bool
    {
        return Hash::check($plaintext, $hashed);
    }
}
```

### Update `config/auth.php`

- [ ] Open `config/auth.php` and update the `guards` and `providers` sections:

```php
'guards' => [
    'web' => [
        'driver'   => 'session',
        'provider' => 'learners',
    ],
],

'providers' => [
    'learners' => [
        'driver' => 'eloquent',
        'model'  => Src\Identity\Infrastructure\Persistence\Eloquent\Model\LearnerModel::class,
    ],
],
```

### Wire up IdentityServiceProvider

- [ ] Open `src/Identity/Infrastructure/Provider/IdentityServiceProvider.php` and replace the stub:

```php
<?php

namespace Src\Identity\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use Src\Identity\Domain\Auth\AuthenticationService;
use Src\Identity\Domain\User\LearnerRepository;
use Src\Identity\Infrastructure\Auth\LaravelAuthService;
use Src\Identity\Infrastructure\Persistence\Eloquent\Repository\EloquentLearnerRepository;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LearnerRepository::class, EloquentLearnerRepository::class);
        $this->app->bind(AuthenticationService::class, LaravelAuthService::class);
    }

    public function boot(): void {}
}
```

---

## Step 1.5 — Presentation Layer

The Presentation layer handles HTTP. Controllers receive requests, dispatch commands or queries, and return responses (Blade views or HTMX partials). They know nothing about domain entities or database queries.

### Form Requests

Laravel Form Requests handle validation before it reaches the controller. If validation fails, Laravel automatically returns a 422 with errors — you do not write that logic yourself.

- [ ] Create `src/Identity/Presentation/Http/Request/RegisterRequest.php`:

```php
<?php

namespace Src\Identity\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'full_name'        => ['required', 'string', 'max:200'],
            'email'            => ['required', 'email:rfc,dns', 'max:320'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'entry_category'   => ['required', 'in:inexperienced,experienced'],
            'years_experience' => ['required_if:entry_category,experienced', 'nullable', 'integer', 'min:0', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'entry_category.in'                  => 'Please select either inexperienced or experienced.',
            'years_experience.required_if'        => 'Please enter your years of experience.',
        ];
    }
}
```

- [ ] Create `src/Identity/Presentation/Http/Request/LoginRequest.php`:

```php
<?php

namespace Src\Identity\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

### RegistrationController

- [ ] Create `src/Identity/Presentation/Http/Controller/RegistrationController.php`:

```php
<?php

namespace Src\Identity\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\Identity\Application\Service\RegistrationService;
use Src\Identity\Domain\Exception\EmailAlreadyTakenException;
use Src\Identity\Presentation\Http\Request\RegisterRequest;

class RegistrationController
{
    public function __construct(
        private readonly RegistrationService $registrationService
    ) {}

    public function show(): View
    {
        return view('identity.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        try {
            $this->registrationService->register(
                fullName:        $request->input('full_name'),
                email:           $request->input('email'),
                password:        $request->input('password'),
                entryCategory:   $request->input('entry_category'),
                yearsExperience: $request->input('years_experience'),
            );
        } catch (EmailAlreadyTakenException $e) {
            return back()
                ->withInput()
                ->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('login')
            ->with('success', 'Account created. Please log in.');
    }
}
```

### LoginController

- [ ] Create `src/Identity/Presentation/Http/Controller/LoginController.php`:

```php
<?php

namespace Src\Identity\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Identity\Application\Command\LoginLearner\LoginLearnerCommand;
use Src\Identity\Domain\Exception\InvalidCredentialsException;
use Src\Identity\Presentation\Http\Request\LoginRequest;
use Src\Shared\Application\Bus\CommandBus;

class LoginController
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function show(): View
    {
        return view('identity.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new LoginLearnerCommand(
                email:    $request->input('email'),
                password: $request->input('password'),
                remember: $request->boolean('remember'),
            ));
        } catch (InvalidCredentialsException $e) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        return redirect()->intended('/learn');
    }

    public function destroy(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
```

### RoleMiddleware

- [ ] Create `src/Identity/Presentation/Http/Middleware/RoleMiddleware.php`:

```php
<?php

namespace Src\Identity\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! $request->user()->hasRole($role)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
```

- [ ] Open `bootstrap/app.php` and register the middleware alias in the `withMiddleware` call:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \Src\Identity\Presentation\Http\Middleware\RoleMiddleware::class,
    ]);
})
```

### Blade Views

Blade view **files** live in `resources/views/` — not in `src/`. The `Presentation/View/` folder inside a module is only for Blade component **classes**. Views are in:

- [ ] Create `resources/views/identity/register.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Create Account')

@section('content')
<div style="max-width: 480px; margin: 80px auto; padding: 0 1rem;">
    <h1>Create your account</h1>

    @if ($errors->any())
        <div style="color: red; margin-bottom: 1rem;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name"
                   value="{{ old('full_name') }}" required>
        </div>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="{{ old('email') }}" required>
        </div>

        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div>
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>

        <div>
            <label>Experience Level</label>
            <select name="entry_category" required>
                <option value="">Select...</option>
                <option value="inexperienced" {{ old('entry_category') === 'inexperienced' ? 'selected' : '' }}>
                    Inexperienced (less than 1 year)
                </option>
                <option value="experienced" {{ old('entry_category') === 'experienced' ? 'selected' : '' }}>
                    Experienced (1 year or more)
                </option>
            </select>
        </div>

        <div id="years-field" style="{{ old('entry_category') === 'experienced' ? '' : 'display:none' }}">
            <label for="years_experience">Years of Experience</label>
            <input type="number" id="years_experience" name="years_experience"
                   value="{{ old('years_experience') }}" min="1" max="50">
        </div>

        <button type="submit">Create Account</button>
    </form>

    <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
</div>

@push('scripts')
<script>
    document.querySelector('[name="entry_category"]').addEventListener('change', function () {
        document.getElementById('years-field').style.display =
            this.value === 'experienced' ? '' : 'none';
    });
</script>
@endpush
@endsection
```

- [ ] Create `resources/views/identity/login.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Log In')

@section('content')
<div style="max-width: 480px; margin: 80px auto; padding: 0 1rem;">
    <h1>Log in</h1>

    @if (session('success'))
        <div style="color: green; margin-bottom: 1rem;">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div style="color: red; margin-bottom: 1rem;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="{{ old('email') }}" required autofocus>
        </div>

        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div>
            <label>
                <input type="checkbox" name="remember" value="1">
                Remember me
            </label>
        </div>

        <button type="submit">Log In</button>
    </form>

    <p>No account? <a href="{{ route('register') }}">Create one</a></p>
</div>
@endsection
```

### Routes

- [ ] Open `routes/identity.php` and add:

```php
<?php

use Illuminate\Support\Facades\Route;
use Src\Identity\Presentation\Http\Controller\LoginController;
use Src\Identity\Presentation\Http\Controller\RegistrationController;

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegistrationController::class, 'show'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store']);

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
```

---

## Step 1.6 — Organisation Stub

The Organisation context is needed by the learner FK in the database, and will be important later for multi-tenancy. For now, create the entity and model stubs so the FK constraint is satisfied and the binding can be added later.

- [ ] Create directory: `src/Organizations/Domain/Organization/`

- [ ] Create `src/Organizations/Domain/Organization/Organisation.php`:

```php
<?php

namespace Src\Organizations\Domain\Organization;

use Src\Shared\Domain\Entity;

final class Organisation extends Entity
{
    public function __construct(
        private readonly string $id,
        private string          $name,
        private string          $slug,
        private bool            $isActive,
    ) {}

    public function id(): string { return $this->id; }
    public function name(): string { return $this->name; }
    public function slug(): string { return $this->slug; }
    public function isActive(): bool { return $this->isActive; }
}
```

- [ ] Create `src/Organizations/Infrastructure/Persistence/Eloquent/Model/OrganisationModel.php`:

```php
<?php

namespace Src\Organizations\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrganisationModel extends Model
{
    use HasUuids;

    protected $table = 'organisations';

    protected $fillable = ['id', 'name', 'slug', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
```

> The `organisations.multi_tenancy` feature flag exists but is disabled. Organisation-scoped data filtering is not enforced until that flag is enabled. The stub above satisfies the FK relationship and nothing more.

---

## Phase 1 Verification Checklist

Do not proceed to Phase 2 until every item below passes:

- [ ] `php artisan serve` boots without errors after all Phase 1 files are created
- [ ] `php artisan route:list` shows: `GET /register`, `POST /register`, `GET /login`, `POST /login`, `POST /logout`
- [ ] Navigate to `http://127.0.0.1:8000/register` — registration form renders
- [ ] Submit the registration form with valid data — learner is created in the `learners` table
- [ ] Verify: `SELECT id, full_name, email, entry_category FROM learners;` — your test learner appears
- [ ] Navigate to `http://127.0.0.1:8000/login` — login form renders
- [ ] Log in with the registered learner — redirected to `/learn` (will 404 for now — that is correct)
- [ ] Log out (POST to `/logout`) — redirected to `/login`
- [ ] Test validation: submit registration with mismatched passwords — error appears, no DB record created
- [ ] Test validation: submit registration with the same email twice — "email already registered" error appears
- [ ] Test RBAC: in `tinker`, run `App\Models\User::first()` — **this should fail** (there is no `App\Models\User` — the guard uses `LearnerModel`)
- [ ] In `tinker`: `Src\Identity\Infrastructure\Persistence\Eloquent\Model\LearnerModel::first()` — returns your test learner
- [ ] Verify `IdentityServiceProvider` bindings: in `tinker`, `app(Src\Identity\Domain\User\LearnerRepository::class)` returns an `EloquentLearnerRepository` instance
- [ ] Navigate to any route with `role:content_author` middleware while logged in as a `learner` role — receives 403
- [ ] `SELECT * FROM roles;` — 4 rows (`super_admin`, `org_admin`, `content_author`, `learner`)
