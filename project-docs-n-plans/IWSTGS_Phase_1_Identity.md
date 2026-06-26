# IWSTGS — Phase 1: Identity & Access

**Laravel 13 · HTMX · _HyperScript · MySQL**

> **Prerequisite:** Phase 0 must be fully complete. The app boots, all module directories exist, the shared kernel compiles, all providers are registered, and the feature flag table is seeded with 17 flags all disabled.
>
> **Goal:** Users can register, log in, and log out. A registered user can then enrol as a learner by providing their experience information. The system knows each person's role. Route access is controlled by that role. Organisations exist as a stub. Every layer — domain, application, infrastructure, presentation — is wired together and tested end to end.
>
> **Rule:** Do not move to Phase 2 until the verification checklist at the bottom passes completely.

---

## The Architecture of This Phase

Before writing a single line of code, understand the conceptual model you are implementing. This understanding is the point — not just getting the code to work.

### The User / Learner Separation

The system now has two distinct concepts that used to be collapsed into one:

**User** — the person. Belongs to the `Identity` bounded context. Exists from the moment someone creates an account. Holds authentication data: name, email, password. Has no knowledge of simulations, projects, or skill levels.

**Learner** — the role a User plays within the simulation system. Belongs to the `SimExecution` bounded context (though the table is created here in Phase 1 since the FK dependency exists). A `Learner` record is created only when a `User` explicitly enrols in that capacity. It holds simulation-specific data: entry_category, years_experience, and eventually the profile built up over time.

**Why this matters beyond naming:** An organisation's HR manager registers as a `User`. They never become a `Learner` — they enrol as an `org_admin`. A lecturer who wants to run assessments also registers as a `User` and becomes a `content_author`. Neither of them should have entry_category or years_experience fields on their record. Those fields belong to the `Learner` record because they only make sense in that context.

**The one-to-one rule:** One `User` can have at most one `Learner` profile. The `user_id` column on `learners` is `UNIQUE`. A learner profile is permanent and grows over time — it is never replaced or duplicated.

### How a Request Flows Through the Layers

```
HTTP Request
    → Form Request (validates input)
        → Controller (dispatches command)
            → CommandBus (resolves handler)
                → Handler (orchestrates)
                    → Domain Entity (business rules)
                        → Repository Interface (contract)
                            → Eloquent Repository (implementation)
                                → Mapper (converts)
                                    → Eloquent Model (database row)
                                        → MySQL
```

Each layer has one job. The controller never touches the database. The domain entity never knows about HTTP. The Eloquent model never contains business rules. This separation is what makes each piece independently testable and replaceable.

---

## Step 1.1 — Migrations

Create all Identity migrations before writing any PHP classes. The database must exist before you can test anything. Run them in order.

### Migration 002 — organisations

```bash
php artisan make:migration 002_create_organisations_table
```

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

> **Notice:** Your existing SQL dump has `user_id` on `organisations`. That was removed. An organisation does not belong to a single user — it is its own top-level entity. Users are *members of* organisations, not owners. The relationship will go the other way when multi-tenancy is built.

### Migration 003 — users

```bash
php artisan make:migration 003_create_users_table
```

```php
public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name', 200);
        $table->string('email', 320)->unique();
        $table->string('password', 255);
        $table->rememberToken();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('users');
}
```

> **Why `name` not `full_name`?** Both are fine — pick one and be consistent everywhere. `name` is used here to keep it simple. Your domain entity can expose it as `name()`. If you prefer `fullname` to match your existing `User.php`, change it here and everywhere below.

### Migration 004 — learners

```bash
php artisan make:migration 004_create_learners_table
```

```php
public function up(): void
{
    Schema::create('learners', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('user_id')->unique(); // UNIQUE — one user, one learner profile
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        $table->uuid('organisation_id')->nullable();
        $table->foreign('organisation_id')->references('id')->on('organisations')->nullOnDelete();
        $table->enum('entry_category', ['inexperienced', 'experienced']);
        $table->smallInteger('years_experience')->unsigned()->nullable();
        $table->timestamp('last_active_at')->nullable();
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('learners');
}
```

> **The `unique()` on `user_id` enforces the one-to-one rule at the database level.** Even if application code had a bug and tried to create a second learner record for the same user, the database would reject it. Always enforce important constraints at the database level, not just application level.

### Migration 005 — roles

```bash
php artisan make:migration 005_create_roles_table
```

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

### Migration 006 — user_role pivot

```bash
php artisan make:migration 006_create_user_role_table
```

```php
public function up(): void
{
    Schema::create('user_role', function (Blueprint $table) {
        $table->uuid('user_id');
        $table->unsignedBigInteger('role_id');
        $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        $table->primary(['user_id', 'role_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('user_role');
}
```

> **Why `user_role` not `learner_role`?** Roles belong to Users, not Learners. A `content_author` role is assigned to a User. A `learner` role is also assigned to a User. The `Learner` table represents the learner profile, not the role assignment. Your existing SQL dump had `learner_role` — this is the correction.

### Migration 007 — administrator

Your SQL dump shows an `administrator` table separate from `users`. For now, administrators are simply users with the `super_admin` role. A separate `administrator` table creates duplication — the same person would need two records. Remove it. If you later need administrator-specific fields, add them to a separate `administrator_profiles` table linked to `users` via a one-to-one FK, exactly like `learners`.

> **Do not create the `administrator` or `administrator_role` tables from your SQL dump. They are superseded by the `users` + `user_role` pattern.**

### Run migrations and seed roles

```bash
php artisan migrate
```

Verify: `SHOW TABLES;` — confirm `organisations`, `users`, `learners`, `roles`, `user_role` all exist.

```bash
php artisan make:seeder RoleSeeder
```

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
            [
                'name'        => 'super_admin',
                'description' => 'Full system access. Manages all content, organisations, and users.',
            ],
            [
                'name'        => 'org_admin',
                'description' => 'Organisation-scoped admin. Manages learners within their organisation.',
            ],
            [
                'name'        => 'content_author',
                'description' => 'Creates and manages projects, scenarios, tasks, and rubric criteria.',
            ],
            [
                'name'        => 'learner',
                'description' => 'Has enrolled as a learner and can participate in simulation sessions.',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore($role);
        }
    }
}
```

```bash
php artisan db:seed --class=RoleSeeder
```

Verify: `SELECT * FROM roles;` — 4 rows.

---

## Step 1.2 — Domain Layer

The domain layer is pure PHP. No Laravel, no Eloquent, no HTTP. It defines what a `User` is, what rules govern it, and what operations exist on it. The `Learner` domain entity is also started here, though its full profile management comes in Phase 5.

### Create domain concept directories

```bash
mkdir -p src/Identity/Domain/User
mkdir -p src/Identity/Domain/Role
mkdir -p src/Identity/Domain/Auth
mkdir -p src/Identity/Domain/Exception
```

### UserId — Value Object

Your `UserId.php` had one bug: `_toString()` has only one underscore. PHP's magic method for string casting is `__toString()` with two underscores. With one underscore it is just a regular public method that nothing calls automatically. The fix:

```php
<?php

namespace Src\Identity\Domain\User;

use Src\Shared\Domain\ValueObject;
use Illuminate\Support\Str;

final class UserId extends ValueObject
{
    public function __construct(private readonly string $uuid) {}

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public static function generate(): self
    {
        return new self((string) Str::uuid());
    }

    public function value(): string
    {
        return $this->uuid;
    }

    public function equals(ValueObject $other): bool
    {
        return $other instanceof self && $other->uuid === $this->uuid;
    }

    // Fixed: two underscores, not one
    public function __toString(): string
    {
        return $this->uuid;
    }
}
```

- [ ] Create `src/Identity/Domain/User/UserId.php` with the corrected code above

### UserRegistered — Domain Event

Your `UserRegistered.php` had the namespace wrong: `Src\Domain\Identity\User` — the words `Domain` and `Identity` were swapped relative to your folder structure. The folder is `src/Identity/Domain/User/` which maps to `Src\Identity\Domain\User`. Always read your namespace directly from the folder path, left to right.

```php
<?php

// Fixed namespace — matches folder path src/Identity/Domain/User/
namespace Src\Identity\Domain\User;

use Src\Shared\Domain\DomainEvent;

final class UserRegistered extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $name,
    ) {
        parent::__construct();
    }
}
```

- [ ] Create `src/Identity/Domain/User/UserRegistered.php` with the corrected code above

### UserRepository — Interface

Your `UserRepository.php` had two bugs: the interface was misspelled as `UserRepoitory` (missing 's'), and all four method signatures were missing their terminating semicolons. PHP interfaces require a semicolon after each method — without it the file is a parse error.

```php
<?php

namespace Src\Identity\Domain\User;

// Fixed: correct spelling — UserRepository not UserRepoitory
interface UserRepository
{
    // Fixed: semicolons after every method signature
    public function save(User $user): void;

    public function findByEmail(string $email): ?User;

    public function findById(UserId $id): ?User;

    public function existsByEmail(string $email): bool;
}
```

- [ ] Create `src/Identity/Domain/User/UserRepository.php` with the corrected code above

### User — Aggregate Root

Your `User.php` had one critical bug: `register()` was missing the `static` keyword. Without `static`, you cannot call `User::register(...)` — PHP would require an existing instance to call it on, which defeats its purpose as a factory method. A static factory method creates the instance; it cannot itself require an instance to exist first.

The entity is also simplified to hold only authentication-level data — `name`, `email`, `password`. No `entry_category` or `years_experience` here. Those belong on `Learner`.

```php
<?php

namespace Src\Identity\Domain\User;

use Src\Shared\Domain\AggregateRoot;

final class User extends AggregateRoot
{
    private function __construct(
        private readonly UserId $userId,
        private string          $name,
        private string          $email,
        private string          $passwordHash,
    ) {}

    // Fixed: added 'static' keyword — this is a static factory method
    public static function register(
        UserId $id,
        string $name,
        string $email,
        string $passwordHash,
    ): self {
        $user = new self($id, $name, $email, $passwordHash);

        $user->recordEvent(new UserRegistered(
            userId: (string) $id,
            email:  $email,
            name:   $name,
        ));

        return $user;
    }

    // Reconstitute from persistence — no event, this already happened
    public static function reconstitute(
        UserId $id,
        string $name,
        string $email,
        string $passwordHash,
    ): self {
        return new self($id, $name, $email, $passwordHash);
    }

    public function id(): string { return (string) $this->userId; }

    public function userId(): UserId      { return $this->userId; }
    public function name(): string        { return $this->name; }
    public function email(): string       { return $this->email; }
    public function passwordHash(): string { return $this->passwordHash; }
}
```

- [ ] Create `src/Identity/Domain/User/User.php` with the corrected code above

> **Why is the constructor `private`?** This forces all creation to go through named factory methods (`register()` or `reconstitute()`). This is intentional. `register()` records a domain event. `reconstitute()` does not. If the constructor were public, you could create a `User` without going through either path, and domain events would be silently skipped. The private constructor makes the intent explicit and enforceable.

### LearnerId — Value Object

The `Learner` gets its own ID value object, separate from `UserId`. A `Learner` is a distinct entity with its own identity.

```bash
mkdir -p src/SimExecution/Domain/Enrollment
```

```php
<?php

namespace Src\SimExecution\Domain\Enrollment;

use Src\Shared\Domain\ValueObject;
use Illuminate\Support\Str;

final class LearnerId extends ValueObject
{
    public function __construct(private readonly string $uuid) {}

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public static function generate(): self
    {
        return new self((string) Str::uuid());
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

- [ ] Create `src/SimExecution/Domain/Enrollment/LearnerId.php`

### LearnerEnrolled — Domain Event

```php
<?php

namespace Src\SimExecution\Domain\Enrollment;

use Src\Shared\Domain\DomainEvent;

final class LearnerEnrolled extends DomainEvent
{
    public function __construct(
        public readonly string  $learnerId,
        public readonly string  $userId,
        public readonly string  $entryCategory,
        public readonly ?int    $yearsExperience,
    ) {
        parent::__construct();
    }
}
```

- [ ] Create `src/SimExecution/Domain/Enrollment/LearnerEnrolled.php`

### EntryCategory — Value Object Enum

```php
<?php

namespace Src\SimExecution\Domain\Enrollment;

enum EntryCategory: string
{
    case Inexperienced = 'inexperienced';
    case Experienced   = 'experienced';
}
```

- [ ] Create `src/SimExecution/Domain/Enrollment/EntryCategory.php`

### LearnerRepository — Interface

```php
<?php

namespace Src\SimExecution\Domain\Enrollment;

interface LearnerRepository
{
    public function save(Learner $learner): void;

    public function findByUserId(string $userId): ?Learner;

    public function findById(LearnerId $id): ?Learner;

    public function existsForUser(string $userId): bool;
}
```

- [ ] Create `src/SimExecution/Domain/Enrollment/LearnerRepository.php`

### Learner — Aggregate Root

```php
<?php

namespace Src\SimExecution\Domain\Enrollment;

use Src\Shared\Domain\AggregateRoot;

final class Learner extends AggregateRoot
{
    private function __construct(
        private readonly LearnerId     $learnerId,
        private readonly string        $userId,
        private EntryCategory          $entryCategory,
        private ?int                   $yearsExperience,
        private ?string                $organisationId,
    ) {}

    public static function enrol(
        LearnerId     $id,
        string        $userId,
        EntryCategory $entryCategory,
        ?int          $yearsExperience = null,
        ?string       $organisationId  = null,
    ): self {
        $learner = new self($id, $userId, $entryCategory, $yearsExperience, $organisationId);

        $learner->recordEvent(new LearnerEnrolled(
            learnerId:       (string) $id,
            userId:          $userId,
            entryCategory:   $entryCategory->value,
            yearsExperience: $yearsExperience,
        ));

        return $learner;
    }

    public static function reconstitute(
        LearnerId     $id,
        string        $userId,
        EntryCategory $entryCategory,
        ?int          $yearsExperience,
        ?string       $organisationId,
    ): self {
        return new self($id, $userId, $entryCategory, $yearsExperience, $organisationId);
    }

    public function id(): string { return (string) $this->learnerId; }

    public function learnerId(): LearnerId     { return $this->learnerId; }
    public function userId(): string           { return $this->userId; }
    public function entryCategory(): EntryCategory { return $this->entryCategory; }
    public function yearsExperience(): ?int    { return $this->yearsExperience; }
    public function organisationId(): ?string  { return $this->organisationId; }
}
```

- [ ] Create `src/SimExecution/Domain/Enrollment/Learner.php`

> **Why is the factory method called `enrol()` not `register()`?** Because the action is enrolment, not registration. The person already registered when they created their `User` account. When they choose to participate as a learner, they *enrol*. Method names should reflect the domain language, not generic programming terms. This matters more than it seems — when you read `Learner::enrol(...)` in code six months from now, the intent is instantly clear.

### Auth and Exception domain objects

```php
<?php

namespace Src\Identity\Domain\Auth;

use Src\Identity\Domain\User\User;

interface AuthenticationService
{
    public function authenticate(string $email, string $password): User;
    public function logout(): void;
}
```

- [ ] Create `src/Identity/Domain/Auth/AuthenticationService.php`

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

- [ ] Create `src/Identity/Domain/Exception/InvalidCredentialsException.php`

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

- [ ] Create `src/Identity/Domain/Exception/EmailAlreadyTakenException.php`

```php
<?php

namespace Src\SimExecution\Domain\Enrollment;

use Src\Shared\Domain\DomainException;

final class AlreadyEnrolledException extends DomainException
{
    public function __construct()
    {
        parent::__construct('This user already has a learner profile.');
    }
}
```

- [ ] Create `src/SimExecution/Domain/Enrollment/AlreadyEnrolledException.php`

---

## Step 1.3 — Application Layer

The application layer orchestrates domain objects. Handlers receive commands and coordinate the domain — they call factory methods, pass results to repositories, and dispatch events. They contain no business rules themselves.

### RegisterUser Command and Handler

```bash
mkdir -p src/Identity/Application/Command/RegisterUser
```

```php
<?php

namespace Src\Identity\Application\Command\RegisterUser;

final class RegisterUserCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
    ) {}
}
```

- [ ] Create `src/Identity/Application/Command/RegisterUser/RegisterUserCommand.php`

```php
<?php

namespace Src\Identity\Application\Command\RegisterUser;

use Illuminate\Support\Facades\Hash;
use Src\Identity\Domain\Exception\EmailAlreadyTakenException;
use Src\Identity\Domain\User\User;
use Src\Identity\Domain\User\UserId;
use Src\Identity\Domain\User\UserRepository;
use Src\Shared\Infrastructure\Id\UuidGenerator;

final class RegisterUserHandler
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly UuidGenerator  $uuidGenerator,
    ) {}

    public function handle(RegisterUserCommand $command): void
    {
        if ($this->repository->existsByEmail($command->email)) {
            throw new EmailAlreadyTakenException($command->email);
        }

        $user = User::register(
            id:           UserId::fromString($this->uuidGenerator->generate()),
            name:         $command->name,
            email:        $command->email,
            passwordHash: Hash::make($command->password),
        );

        $this->repository->save($user);

        // Release and dispatch domain events
        foreach ($user->releaseEvents() as $event) {
            event($event);
        }
    }
}
```

- [ ] Create `src/Identity/Application/Command/RegisterUser/RegisterUserHandler.php`

### LoginUser Command and Handler

```bash
mkdir -p src/Identity/Application/Command/LoginUser
```

```php
<?php

namespace Src\Identity\Application\Command\LoginUser;

final class LoginUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly bool   $remember = false,
    ) {}
}
```

- [ ] Create `src/Identity/Application/Command/LoginUser/LoginUserCommand.php`

```php
<?php

namespace Src\Identity\Application\Command\LoginUser;

use Src\Identity\Domain\Auth\AuthenticationService;

final class LoginUserHandler
{
    public function __construct(
        private readonly AuthenticationService $authService,
    ) {}

    public function handle(LoginUserCommand $command): void
    {
        $this->authService->authenticate($command->email, $command->password);
    }
}
```

- [ ] Create `src/Identity/Application/Command/LoginUser/LoginUserHandler.php`

### EnrolAsLearner Command and Handler

This command is dispatched when a registered user decides to enrol as a learner. It belongs in `SimExecution` because learner enrolment is a SimExecution concept.

```bash
mkdir -p src/SimExecution/Application/Command/EnrolAsLearner
```

```php
<?php

namespace Src\SimExecution\Application\Command\EnrolAsLearner;

final class EnrolAsLearnerCommand
{
    public function __construct(
        public readonly string  $userId,
        public readonly string  $entryCategory,
        public readonly ?int    $yearsExperience = null,
        public readonly ?string $organisationId  = null,
    ) {}
}
```

- [ ] Create `src/SimExecution/Application/Command/EnrolAsLearner/EnrolAsLearnerCommand.php`

```php
<?php

namespace Src\SimExecution\Application\Command\EnrolAsLearner;

use Src\SimExecution\Domain\Enrollment\AlreadyEnrolledException;
use Src\SimExecution\Domain\Enrollment\EntryCategory;
use Src\SimExecution\Domain\Enrollment\Learner;
use Src\SimExecution\Domain\Enrollment\LearnerId;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\Shared\Infrastructure\Id\UuidGenerator;

final class EnrolAsLearnerHandler
{
    public function __construct(
        private readonly LearnerRepository $repository,
        private readonly UuidGenerator     $uuidGenerator,
    ) {}

    public function handle(EnrolAsLearnerCommand $command): void
    {
        if ($this->repository->existsForUser($command->userId)) {
            throw new AlreadyEnrolledException();
        }

        $learner = Learner::enrol(
            id:              LearnerId::fromString($this->uuidGenerator->generate()),
            userId:          $command->userId,
            entryCategory:   EntryCategory::from($command->entryCategory),
            yearsExperience: $command->yearsExperience,
            organisationId:  $command->organisationId,
        );

        $this->repository->save($learner);

        foreach ($learner->releaseEvents() as $event) {
            event($event);
        }
    }
}
```

- [ ] Create `src/SimExecution/Application/Command/EnrolAsLearner/EnrolAsLearnerHandler.php`

### DTOs

```php
<?php

namespace Src\Identity\Application\DTO;

final class UserDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly bool   $isLearner,
        public readonly array  $roles,
    ) {}
}
```

- [ ] Create `src/Identity/Application/DTO/UserDTO.php`

---

## Step 1.4 — Infrastructure Layer

### Eloquent Models

> **Naming reminder:** Every Eloquent model is suffixed with `Model`. The domain entity is `User.php`. The Eloquent model is `UserModel.php`. They are different things connected only by the mapper.

```php
<?php

namespace Src\Identity\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserModel extends Authenticatable
{
    use HasUuids;

    protected $table = 'users';

    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RoleModel::class, 'user_role', 'user_id', 'role_id');
    }

    public function learner(): HasOne
    {
        return $this->hasOne(LearnerModel::class, 'user_id');
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    public function isLearner(): bool
    {
        return $this->learner()->exists();
    }
}
```

- [ ] Create `src/Identity/Infrastructure/Persistence/Eloquent/Model/UserModel.php`

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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(UserModel::class, 'user_role', 'role_id', 'user_id');
    }
}
```

- [ ] Create `src/Identity/Infrastructure/Persistence/Eloquent/Model/RoleModel.php`

```php
<?php

namespace Src\Identity\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerModel extends Model
{
    use HasUuids;

    protected $table = 'learners';

    protected $fillable = [
        'id',
        'user_id',
        'organisation_id',
        'entry_category',
        'years_experience',
        'last_active_at',
    ];

    protected $casts = [
        'years_experience' => 'integer',
        'last_active_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'user_id');
    }
}
```

- [ ] Create `src/Identity/Infrastructure/Persistence/Eloquent/Model/LearnerModel.php`

> **Why does `LearnerModel` live in the Identity module's Eloquent folder?** Because the Learner table exists here (Phase 1 creates it) and `UserModel` needs to reference it via `hasOne()`. When SimExecution grows in Phase 5, its repository and mapper will live in `SimExecution/Infrastructure/` and import this model. The model itself is a shared data representation — it is fine for two modules to reference the same Eloquent model. What they must NOT do is share domain entities across context boundaries.

### Mappers

```php
<?php

namespace Src\Identity\Infrastructure\Persistence\Eloquent\Mapper;

use Src\Identity\Domain\User\User;
use Src\Identity\Domain\User\UserId;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;

final class UserMapper
{
    public function toEntity(UserModel $model): User
    {
        return User::reconstitute(
            id:           UserId::fromString($model->id),
            name:         $model->name,
            email:        $model->email,
            passwordHash: $model->password,
        );
    }

    public function toModel(User $entity): UserModel
    {
        return new UserModel([
            'id'       => (string) $entity->userId(),
            'name'     => $entity->name(),
            'email'    => $entity->email(),
            'password' => $entity->passwordHash(),
        ]);
    }
}
```

- [ ] Create `src/Identity/Infrastructure/Persistence/Eloquent/Mapper/UserMapper.php`

```php
<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Mapper;

use Src\SimExecution\Domain\Enrollment\EntryCategory;
use Src\SimExecution\Domain\Enrollment\Learner;
use Src\SimExecution\Domain\Enrollment\LearnerId;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\LearnerModel;

final class LearnerMapper
{
    public function toEntity(LearnerModel $model): Learner
    {
        return Learner::reconstitute(
            id:              LearnerId::fromString($model->id),
            userId:          $model->user_id,
            entryCategory:   EntryCategory::from($model->entry_category),
            yearsExperience: $model->years_experience,
            organisationId:  $model->organisation_id,
        );
    }

    public function toModel(Learner $entity): LearnerModel
    {
        return new LearnerModel([
            'id'               => (string) $entity->learnerId(),
            'user_id'          => $entity->userId(),
            'organisation_id'  => $entity->organisationId(),
            'entry_category'   => $entity->entryCategory()->value,
            'years_experience' => $entity->yearsExperience(),
        ]);
    }
}
```

- [ ] Create `src/SimExecution/Infrastructure/Persistence/Eloquent/Mapper/LearnerMapper.php`

### Eloquent Repositories

```php
<?php

namespace Src\Identity\Infrastructure\Persistence\Eloquent\Repository;

use Src\Identity\Domain\User\User;
use Src\Identity\Domain\User\UserId;
use Src\Identity\Domain\User\UserRepository;
use Src\Identity\Infrastructure\Persistence\Eloquent\Mapper\UserMapper;
use Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel;

final class EloquentUserRepository implements UserRepository
{
    public function __construct(private readonly UserMapper $mapper) {}

    public function save(User $user): void
    {
        $model = $this->mapper->toModel($user);
        $model->save();
    }

    public function findByEmail(string $email): ?User
    {
        $model = UserModel::where('email', $email)->first();
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findById(UserId $id): ?User
    {
        $model = UserModel::find((string) $id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function existsByEmail(string $email): bool
    {
        return UserModel::where('email', $email)->exists();
    }
}
```

- [ ] Create `src/Identity/Infrastructure/Persistence/Eloquent/Repository/EloquentUserRepository.php`

```php
<?php

namespace Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository;

use Src\Identity\Infrastructure\Persistence\Eloquent\Model\LearnerModel;
use Src\SimExecution\Domain\Enrollment\Learner;
use Src\SimExecution\Domain\Enrollment\LearnerId;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Mapper\LearnerMapper;

final class EloquentLearnerRepository implements LearnerRepository
{
    public function __construct(private readonly LearnerMapper $mapper) {}

    public function save(Learner $learner): void
    {
        $model = $this->mapper->toModel($learner);
        $model->save();
    }

    public function findByUserId(string $userId): ?Learner
    {
        $model = LearnerModel::where('user_id', $userId)->first();
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findById(LearnerId $id): ?Learner
    {
        $model = LearnerModel::find((string) $id);
        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function existsForUser(string $userId): bool
    {
        return LearnerModel::where('user_id', $userId)->exists();
    }
}
```

- [ ] Create `src/SimExecution/Infrastructure/Persistence/Eloquent/Repository/EloquentLearnerRepository.php`

### Auth and Hashing Services

```php
<?php

namespace Src\Identity\Infrastructure\Auth;

use Illuminate\Support\Facades\Auth;
use Src\Identity\Domain\Auth\AuthenticationService;
use Src\Identity\Domain\Exception\InvalidCredentialsException;
use Src\Identity\Domain\User\User;
use Src\Identity\Domain\User\UserRepository;

final class LaravelAuthService implements AuthenticationService
{
    public function __construct(private readonly UserRepository $repository) {}

    public function authenticate(string $email, string $password): User
    {
        $success = Auth::attempt(['email' => $email, 'password' => $password]);

        if (! $success) {
            throw new InvalidCredentialsException();
        }

        $user = $this->repository->findByEmail($email);

        if ($user === null) {
            throw new InvalidCredentialsException();
        }

        return $user;
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
```

- [ ] Create `src/Identity/Infrastructure/Auth/LaravelAuthService.php`

### Update `config/auth.php`

```php
'guards' => [
    'web' => [
        'driver'   => 'session',
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => Src\Identity\Infrastructure\Persistence\Eloquent\Model\UserModel::class,
    ],
],
```

- [ ] Update `config/auth.php`

### Wire up Service Providers

- [X] Open `src/Identity/Infrastructure/Provider/IdentityServiceProvider.php` and replace the stub:

```php
<?php

namespace Src\Identity\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use Src\Identity\Domain\Auth\AuthenticationService;
use Src\Identity\Domain\User\UserRepository;
use Src\Identity\Infrastructure\Auth\LaravelAuthService;
use Src\Identity\Infrastructure\Persistence\Eloquent\Repository\EloquentUserRepository;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(AuthenticationService::class, LaravelAuthService::class);
    }

    public function boot(): void {}
}
```

- [ ] Open `src/SimExecution/Infrastructure/Provider/SimExecutionServiceProvider.php` and replace the stub:

```php
<?php

namespace Src\SimExecution\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\SimExecution\Infrastructure\Persistence\Eloquent\Repository\EloquentLearnerRepository;

class SimExecutionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LearnerRepository::class, EloquentLearnerRepository::class);
    }

    public function boot(): void {}
}
```

---

## Step 1.5 — Presentation Layer

### Middleware

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

- [ ] Create `src/Identity/Presentation/Http/Middleware/RoleMiddleware.php`
- [ ] Register alias in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \Src\Identity\Presentation\Http\Middleware\RoleMiddleware::class,
    ]);
})
```

### Form Requests

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
            'name'     => ['required', 'string', 'max:200'],
            'email'    => ['required', 'email:rfc,dns', 'max:320'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
```

- [ ] Create `src/Identity/Presentation/Http/Request/RegisterRequest.php`

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

- [ ] Create `src/Identity/Presentation/Http/Request/LoginRequest.php`

```php
<?php

namespace Src\SimExecution\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class EnrolAsLearnerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'entry_category'   => ['required', 'in:inexperienced,experienced'],
            'years_experience' => [
                'nullable',
                'required_if:entry_category,experienced',
                'integer',
                'min:1',
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'entry_category.in'               => 'Please select either inexperienced or experienced.',
            'years_experience.required_if'    => 'Please enter your years of experience.',
        ];
    }
}
```

- [ ] Create `src/SimExecution/Presentation/Http/Request/EnrolAsLearnerRequest.php`

### Controllers

```php
<?php

namespace Src\Identity\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use Src\Identity\Domain\Exception\EmailAlreadyTakenException;
use Src\Identity\Presentation\Http\Request\RegisterRequest;
use Src\Shared\Application\Bus\CommandBus;

class RegistrationController
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function show(): View
    {
        return view('identity.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new RegisterUserCommand(
                name:     $request->input('name'),
                email:    $request->input('email'),
                password: $request->input('password'),
            ));
        } catch (EmailAlreadyTakenException $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('login')
            ->with('success', 'Account created. Please log in.');
    }
}
```

- [ ] Create `src/Identity/Presentation/Http/Controller/RegistrationController.php`

```php
<?php

namespace Src\Identity\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Identity\Application\Command\LoginUser\LoginUserCommand;
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
            $this->commandBus->dispatch(new LoginUserCommand(
                email:    $request->input('email'),
                password: $request->input('password'),
                remember: $request->boolean('remember'),
            ));
        } catch (InvalidCredentialsException) {
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

- [ ] Create `src/Identity/Presentation/Http/Controller/LoginController.php`

```php
<?php

namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\SimExecution\Application\Command\EnrolAsLearner\EnrolAsLearnerCommand;
use Src\SimExecution\Domain\Enrollment\AlreadyEnrolledException;
use Src\SimExecution\Presentation\Http\Request\EnrolAsLearnerRequest;
use Src\Shared\Application\Bus\CommandBus;

class LearnerEnrolmentController
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function show(): View
    {
        // Show the enrolment form — entry_category and years_experience
        return view('learn.enrol');
    }

    public function store(EnrolAsLearnerRequest $request): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new EnrolAsLearnerCommand(
                userId:          auth()->id(),
                entryCategory:   $request->input('entry_category'),
                yearsExperience: $request->input('years_experience'),
            ));
        } catch (AlreadyEnrolledException $e) {
            return redirect()->route('learn.dashboard')
                ->with('info', 'You are already enrolled as a learner.');
        }

        // After enrolment, redirect to diagnostic (Phase 5)
        // For now redirect to a placeholder
        return redirect()->route('learn.enrol.success');
    }
}
```

- [ ] Create `src/SimExecution/Presentation/Http/Controller/LearnerEnrolmentController.php`

### Routes

- [ ] Open `routes/identity.php` and write:

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

- [ ] Open `routes/learn.php` and write:

```php
<?php

use Illuminate\Support\Facades\Route;
use Src\SimExecution\Presentation\Http\Controller\LearnerEnrolmentController;

// Learner enrolment — available to any authenticated user who has not yet enrolled
Route::get('/learn/enrol', [LearnerEnrolmentController::class, 'show'])->name('learn.enrol');
Route::post('/learn/enrol', [LearnerEnrolmentController::class, 'store']);

// Placeholder — replaced in Phase 5
Route::get('/learn/enrol/success', function () {
    return view('learn.enrol-success');
})->name('learn.enrol.success');

// Main learn dashboard — requires learner role (Phase 5+)
Route::middleware('role:learner')->group(function () {
    Route::get('/learn', function () {
        return view('learn.dashboard');
    })->name('learn.dashboard');
});
```

### Blade Views

Remember: Blade view **files** live in `resources/views/`, not in `src/`.

- [ ] Create `resources/views/identity/register.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Create Account')
@section('content')
<div style="max-width:480px;margin:80px auto;padding:0 1rem;">
    <h1>Create your account</h1>

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div>
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
        </div>
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="password_confirmation">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <button type="submit">Create Account</button>
    </form>

    <p>Already have an account? <a href="{{ route('login') }}">Log in</a></p>
</div>
@endsection
```

- [ ] Create `resources/views/identity/login.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Log In')
@section('content')
<div style="max-width:480px;margin:80px auto;padding:0 1rem;">
    <h1>Log in</h1>

    @if (session('success'))
        <p style="color:green">{{ session('success') }}</p>
    @endif

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label>
                <input type="checkbox" name="remember" value="1"> Remember me
            </label>
        </div>
        <button type="submit">Log In</button>
    </form>

    <p>No account? <a href="{{ route('register') }}">Create one</a></p>
</div>
@endsection
```

- [ ] Create `resources/views/learn/enrol.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Enrol as a Learner')
@section('content')
<div style="max-width:480px;margin:80px auto;padding:0 1rem;">
    <h1>Enrol as a Learner</h1>
    <p>Tell us about your experience so we can calibrate your starting level.</p>

    @if ($errors->any())
        <ul style="color:red">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('learn.enrol') }}">
        @csrf
        <div>
            <label>Experience Level</label>
            <select name="entry_category" id="entry_category" required>
                <option value="">Select...</option>
                <option value="inexperienced" {{ old('entry_category') === 'inexperienced' ? 'selected' : '' }}>
                    Inexperienced (less than 1 year)
                </option>
                <option value="experienced" {{ old('entry_category') === 'experienced' ? 'selected' : '' }}>
                    Experienced (1 or more years)
                </option>
            </select>
        </div>

        <div id="years-field" style="{{ old('entry_category') === 'experienced' ? '' : 'display:none' }}">
            <label for="years_experience">Years of Experience</label>
            <input type="number" id="years_experience" name="years_experience"
                   value="{{ old('years_experience') }}" min="1" max="50">
        </div>

        <button type="submit">Continue to Assessment</button>
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('entry_category').addEventListener('change', function () {
        document.getElementById('years-field').style.display =
            this.value === 'experienced' ? '' : 'none';
    });
</script>
@endpush
@endsection
```

- [ ] Create `resources/views/learn/enrol-success.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Enrolled')
@section('content')
<div style="max-width:480px;margin:80px auto;padding:0 1rem;">
    <h1>You are enrolled</h1>
    <p>Your learner profile has been created. Diagnostic assessment will be available in a future phase.</p>
</div>
@endsection
```

- [ ] Create `resources/views/learn/dashboard.blade.php`:

```blade
@extends('layouts.app')
@section('title', 'Learn')
@section('content')
<div style="max-width:800px;margin:80px auto;padding:0 1rem;">
    <h1>Dashboard</h1>
    <p>Welcome, {{ auth()->user()->name }}. Your simulation dashboard will be built in Phase 5.</p>
</div>
@endsection
```

---

## Step 1.6 — Organisation Stub

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

    public function id(): string      { return $this->id; }
    public function name(): string    { return $this->name; }
    public function slug(): string    { return $this->slug; }
    public function isActive(): bool  { return $this->isActive; }
}
```

- [ ] Create `src/Organizations/Domain/Organization/Organisation.php`

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

- [ ] Create `src/Organizations/Infrastructure/Persistence/Eloquent/Model/OrganisationModel.php`

---

## Phase 1 Verification Checklist

Do not proceed to Phase 2 until every item below passes.

- [ ] `php artisan serve` boots without errors after all Phase 1 files are created
- [ ] `php artisan route:list` shows: `GET /register`, `POST /register`, `GET /login`, `POST /login`, `POST /logout`, `GET /learn/enrol`, `POST /learn/enrol`
- [ ] Navigate to `/register` — form renders with name, email, password, confirm password fields
- [ ] Register a new user — row appears in `users` table, **no row** in `learners` table yet
- [ ] `SELECT id, name, email FROM users;` — your test user appears
- [ ] Navigate to `/login` — form renders
- [ ] Log in — redirected to `/learn` (will redirect to `/learn/enrol` logic later — 404 is fine for now)
- [ ] Navigate to `/learn/enrol` while logged in — enrolment form renders
- [ ] Submit the enrolment form with `entry_category = inexperienced` — row appears in `learners` table with `user_id` matching your user
- [ ] `SELECT id, user_id, entry_category FROM learners;` — one row, user_id FK matches users.id
- [ ] Try to enrol the same user a second time — redirected with "already enrolled" message, **no second row** created in learners
- [ ] `DESCRIBE learners;` — confirm `user_id` has a UNIQUE index
- [ ] Test validation: submit enrolment with `entry_category = experienced` and no `years_experience` — validation error appears
- [ ] Log out — redirected to `/login`
- [ ] Test RBAC: in `tinker`, run `app(Src\Identity\Domain\User\UserRepository::class)` — returns `EloquentUserRepository` instance
- [ ] In `tinker`: `app(Src\SimExecution\Domain\Enrollment\LearnerRepository::class)` — returns `EloquentLearnerRepository` instance
- [ ] In `tinker`: `Src\Identity\Domain\User\UserId::generate()` — returns a `UserId` instance, `(string)` cast returns a UUID string (confirming `__toString` works correctly)
- [ ] `SELECT * FROM roles;` — 4 rows
- [ ] `SELECT * FROM feature_flags;` — 17 rows, all `is_enabled = 0`
