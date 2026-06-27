# IWSTGS — Phase 2: Core Content Schema

**Laravel 13 · HTMX · _HyperScript · MySQL**

> **Prerequisite:** Phase 1 must be fully complete. Users can register, log in, log out, and enrol as
> learners. The `users`, `learners`, `roles`, `user_role`, and `organisations` tables exist and are
> seeded. All 17 feature flags exist in the database, all disabled.
>
> **Goal:** Every content table that the simulation system needs exists in the database, each with its
> correct columns, constraints, and foreign keys. **No PHP classes yet.** This phase is entirely
> migrations. If you get the schema right here, everything that follows will be straightforward. If you
> get it wrong, you will fight your own database for the rest of the project.
>
> **Rule:** Do not move to Phase 3 until `php artisan migrate:fresh` runs cleanly with zero errors and
> every table described below exists with the correct structure.

---

## Why Phase 2 Is Migrations Only

It might feel strange to write 15+ migrations without writing any PHP classes to use them.
Here is why this is the right approach:

**Migrations are the ground truth.** Your PHP classes, repositories, and Eloquent models will
be built on top of this schema in Phase 3. If you write the models first and the migrations second,
the models become the ground truth and the migrations become an afterthought — then they drift apart.
Always build schema first, classes second.

**Foreign keys must exist before they can be referenced.** If you tried to create the
`rubric_criteria` table (which references `tasks`, `rubric_sets`, and `competence_dimensions`) before
those tables existed, the migration would fail with a foreign key constraint error. The exact order
in this document is correct — do not reorder the steps.

**Running `migrate:fresh` at the end proves the whole sequence is valid.** This is your safety net.

---

## What You're Building

The simulation system has four groups of content:

**Competency content** — the six canonical skill dimensions and the role definitions that map
to them. These are mostly static data.

**Project and scenario content** — the projects (e.g. MedQueue), the scenarios within them
(e.g. Scenario 1: Sprint Kickoff), the reference materials a learner can read, and the vault
documents they are granted access to over time.

**Task content** — the individual work cards inside each scenario. Each task has expected
deliverables, CAC variants (different complexity levels), knowledge anchors, guidance prompts,
and dependency links to other tasks.

**Rubric and evaluation content** — the scoring criteria used by the AI evaluation engine
to assess each submission against each competency dimension.

The diagram below shows how these groups relate:

```
competence_dimensions (6 rows, static)
        ↑ FK
role_definitions ← uses dimension weights/thresholds (JSON)

concept_tags  ←──────────────────────────────────────────┐
                                                          │
project_templates                                         │
    ↓                                                     │
    scenario_templates                                    │
        ↓                          ↓                      │
        scenario_reference_materials  artifact_vault_items│
        ↓                                                 │
        tasks ─→ task_expected_deliverables               │
             ─→ task_cac_variants                         │
             ─→ task_dependencies (self-referencing)      │
             ─→ task_knowledge_anchors ──────────────────→┘
             ─→ task_guidance_prompts

rubric_sets (one per project)
    ↓
rubric_criteria → task + competence_dimension

backlog_item_templates → project + tasks
follow_up_prompt_templates (standalone)
```

---

## PHP 8+ Syntax Used in This Phase

Before writing any code, here is a reference for the newer PHP syntax you will see across the
migration files. Understanding what each piece of syntax does will help you read them correctly.

### 1. Anonymous Migration Classes

In older Laravel, every migration was a named class:

```php
// OLD WAY — before Laravel 9 / PHP 8
class CreateOrganisationsTable extends Migration
{
    public function up() { ... }
}
```

In Laravel 9+ with PHP 8.0+, migrations use anonymous classes:

```php
// NEW WAY — anonymous class, no name needed
return new class extends Migration
{
    public function up(): void { ... }
};
```

**Why anonymous?** Named migration classes caused naming conflicts when two migrations in different
packages had the same class name. With anonymous classes, Laravel identifies migrations by their
file name (the timestamp prefix), not the class name. You can have a thousand migration files with
`return new class extends Migration` and there is no conflict.

### 2. Return Type Declarations on `void`

```php
public function up(): void { ... }
public function down(): void { ... }
```

The `: void` after the parentheses tells PHP (and your IDE) that this method returns nothing.
PHP 7.1+ added `void`. It is not just documentation — PHP will throw a `TypeError` if a `void`
method accidentally returns a value.

### 3. Named Arguments

```php
$table->decimal('weight', precision: 4, scale: 3);
```

Named arguments (PHP 8.0+) let you pass arguments by name rather than by position. The benefit:
you can skip optional arguments in the middle of a parameter list, and the code is self-documenting.
`precision: 4` tells you exactly what that number means without reading the function signature.

### 4. The Nullsafe Operator `?->`

```php
$model?->entry_category
```

The `?->` operator (PHP 8.0+) means: "if `$model` is null, return null immediately instead of
calling `->entry_category` on null." Without it, calling a method on null causes a
`Fatal error: Attempt to access property on null`. You will see this used later in mapper classes
when records may or may not exist.

### 5. Backed Enums

```php
enum ConceptTagCategory: string
{
    case SeConcept = 'se_concept';
    case CsFundamental = 'cs_fundamental';
}
```

PHP 8.1 added enums. A **backed enum** has an underlying scalar type (`:string` or `:int`). The
`value` property gives you the raw string: `ConceptTagCategory::SeConcept->value` returns
`'se_concept'`. You already saw `EntryCategory` in Phase 1 — Phase 2 will add more enums for
domain concepts. Enums replace the old pattern of `const CATEGORY_SE_CONCEPT = 'se_concept'`
while adding type safety: a function that accepts `ConceptTagCategory` will reject plain strings.

### 6. Constructor Property Promotion

```php
// WITHOUT promotion
class ConceptTag {
    private string $id;
    private string $tag;

    public function __construct(string $id, string $tag) {
        $this->id = $id;
        $this->tag = $tag;
    }
}

// WITH promotion (PHP 8.0+)
class ConceptTag {
    public function __construct(
        private readonly string $id,
        private readonly string $tag,
    ) {}
}
```

The `private readonly string $id` inside the constructor parameter list *declares* the property
AND assigns it in one step. The `readonly` keyword (PHP 8.1+) means the property can only be
assigned once — in the constructor — and is immutable after that. You saw this throughout Phase 0
and Phase 1; it becomes the standard throughout Phase 2 onward.

### 7. Intersection Types and Union Types

```php
// Union type: accepts string OR int
function accept(string|int $value): void {}

// Nullable (a common union with null)
function maybeNull(?string $value): void {}
```

Union types (PHP 8.0+) let a parameter or return type accept multiple types. The `?string`
shorthand for `string|null` has been in PHP since 7.1, but the general `A|B` syntax is PHP 8.0+.

---

## The Migration Order

**Follow this exact order.** Each group depends on tables created in the previous group.

```
Step 2.1 → competence_dimensions (no FK dependencies)
Step 2.2 → role_definitions      (no FK dependencies)
Step 2.3 → concept_tags          (no FK dependencies)
Step 2.4 → project_templates     (FK → organisations)
Step 2.5 → scenario_templates    (FK → project_templates)
Step 2.6 → scenario_reference_materials (FK → scenario_templates)
Step 2.7 → artifact_vault_items  (FK → project_templates)
Step 2.8 → tasks                 (FK → scenario_templates)
Step 2.9 → task_expected_deliverables (FK → tasks)
Step 2.10 → task_cac_variants    (FK → tasks)
Step 2.11 → task_dependencies    (FK → tasks × 2)
Step 2.12 → task_knowledge_anchors (FK → tasks, concept_tags)
Step 2.13 → task_guidance_prompts  (FK → tasks)
Step 2.14 → rubric_sets          (FK → project_templates)
Step 2.15 → alter project_templates: add rubric_set_id FK
Step 2.16 → rubric_criteria      (FK → rubric_sets, tasks, competence_dimensions)
Step 2.17 → follow_up_prompt_templates (no FK dependencies)
Step 2.18 → backlog_item_templates (FK → project_templates, tasks)
```

---

## Step 2.1 — Competence Dimensions

**What this table stores:** The six canonical skill dimensions that the entire evaluation engine
is built on. These are permanent, fixed reference data. They never change at runtime — they are
seeded once and read many times.

**Why a `VARCHAR` primary key instead of an auto-increment integer?**  
The dimension IDs (`dim_001` through `dim_006`) are human-readable identifiers that appear in
foreign keys, JSON configuration, and API responses. Using readable string IDs makes debugging
far easier: when you see `dim_003` in a rubric criterion, you know immediately it refers to
Implementation & Coding without looking it up. Auto-increment integers (`1`, `2`, `3`) force
you to memorise or look up what each number means.

### Migration 008 — competence_dimensions

```bash
php artisan make:migration 008_create_competence_dimensions_table
```

> **Naming convention note:** Migrations are numbered sequentially starting from `001`.
> Phase 0 created `001_create_feature_flags_table`. Phase 1 created `002` through `007`.
> Phase 2 starts at `008`. If your Phase 1 migrations are numbered differently, adjust
> the prefix here to continue the sequence without gaps.

```php
public function up(): void
{
    Schema::create('competence_dimensions', function (Blueprint $table) {
        // String PK: 'dim_001' through 'dim_006'
        // No auto-increment — these IDs are intentional and permanent.
        $table->string('id', 20)->primary();

        $table->string('name', 200)->notNull();

        // Short label used in UI badges, charts, and column headers
        $table->string('short_label', 100)->notNull();

        // The guiding question evaluators ask when scoring this dimension
        // e.g. "Did the learner correctly analyse and decompose the problem?"
        $table->text('core_question')->notNull();

        // JSON array of strings — observable indicators of this dimension
        // e.g. ["Identifies constraints", "Separates concerns correctly"]
        $table->json('observable_indicators')->notNull();

        // Controls the order dimensions appear in charts and reports
        $table->tinyInteger('sequence_order')->unsigned()->notNull();
    });
}

public function down(): void
{
    Schema::dropIfExists('competence_dimensions');
}
```

> **Why no `timestamps()`?** These rows are static seed data. Tracking `created_at` /
> `updated_at` on rows that never change adds unnecessary noise to every query and every
> database dump. Only add `timestamps()` to tables whose rows are regularly modified.

### Seeder — CompetenceDimensionSeeder

```bash
php artisan make:seeder CompetenceDimensionSeeder
```

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompetenceDimensionSeeder extends Seeder
{
    public function run(): void
    {
        $dimensions = [
            [
                'id'                   => 'dim_001',
                'name'                 => 'Problem Analysis & Decomposition',
                'short_label'          => 'Analysis',
                'core_question'        => 'Did the learner correctly analyse and decompose the problem?',
                'observable_indicators' => json_encode([
                    'Identifies all relevant constraints and requirements',
                    'Separates the problem into independently solvable sub-problems',
                    'Recognises ambiguity and asks clarifying questions',
                    'Identifies data flows and dependencies',
                    'Spots hidden assumptions in the problem statement',
                ]),
                'sequence_order'       => 1,
            ],
            [
                'id'                   => 'dim_002',
                'name'                 => 'Design & Architecture',
                'short_label'          => 'Design',
                'core_question'        => 'Did the learner produce a coherent, maintainable design?',
                'observable_indicators' => json_encode([
                    'Chooses appropriate patterns and abstractions for the problem',
                    'Justifies architectural decisions with clear reasoning',
                    'Considers scalability and change over time',
                    'Designs appropriate interfaces between components',
                    'Avoids over-engineering and premature optimisation',
                ]),
                'sequence_order'       => 2,
            ],
            [
                'id'                   => 'dim_003',
                'name'                 => 'Implementation & Coding',
                'short_label'          => 'Implementation',
                'core_question'        => 'Did the learner produce correct, readable, and maintainable code?',
                'observable_indicators' => json_encode([
                    'Code is correct and handles edge cases',
                    'Follows language idioms and project conventions',
                    'Names are clear and self-documenting',
                    'Functions are focused and appropriately sized',
                    'No unnecessary complexity or duplication',
                ]),
                'sequence_order'       => 3,
            ],
            [
                'id'                   => 'dim_004',
                'name'                 => 'Testing & Quality Assurance',
                'short_label'          => 'Testing',
                'core_question'        => 'Did the learner verify correctness and consider failure modes?',
                'observable_indicators' => json_encode([
                    'Writes meaningful tests that prove the code works',
                    'Tests edge cases and boundary conditions',
                    'Considers what could go wrong in production',
                    'Uses appropriate testing levels (unit, integration)',
                    'Tests are readable and maintainable',
                ]),
                'sequence_order'       => 4,
            ],
            [
                'id'                   => 'dim_005',
                'name'                 => 'Debugging & Problem-Solving',
                'short_label'          => 'Debugging',
                'core_question'        => 'Did the learner diagnose and resolve problems systematically?',
                'observable_indicators' => json_encode([
                    'Isolates the fault rather than guessing',
                    'Forms and tests hypotheses methodically',
                    'Reads and interprets error messages correctly',
                    'Uses debugging tools effectively',
                    'Documents what was tried and what worked',
                ]),
                'sequence_order'       => 5,
            ],
            [
                'id'                   => 'dim_006',
                'name'                 => 'Communication & Documentation',
                'short_label'          => 'Communication',
                'core_question'        => 'Did the learner communicate their work clearly and completely?',
                'observable_indicators' => json_encode([
                    'Writes clear technical documentation',
                    'Explains decisions and trade-offs',
                    'Adapts communication style to the intended audience',
                    'Produces accurate commit messages and PR descriptions',
                    'Comments code at the right level of abstraction',
                ]),
                'sequence_order'       => 6,
            ],
        ];

        // insertOrIgnore: safe to run multiple times — skips rows that already exist.
        // This is better than truncate + insert because it protects any manual edits
        // made to a live database without overwriting them.
        DB::table('competence_dimensions')->insertOrIgnore($dimensions);
    }
}
```

- [ ] Create `database/migrations/008_create_competence_dimensions_table.php`
- [ ] Create `database/seeders/CompetenceDimensionSeeder.php`
- [ ] Run: `php artisan migrate`
- [ ] Run: `php artisan db:seed --class=CompetenceDimensionSeeder`
- [ ] Verify: `SELECT id, name FROM competence_dimensions;` — exactly 6 rows

---

## Step 2.2 — Role Definitions

**What this table stores:** The target job roles learners are working towards — `Junior PHP
Developer`, `Mid-Level Systems Engineer`, etc. Each role specifies which competency dimensions
matter most (weights) and how high a learner must score on each dimension to qualify (thresholds).
These are content-layer definitions, not the `roles` table from Phase 1 (which handles RBAC —
who can access what in the application).

**JSON columns for `dimension_weights` and `dimension_thresholds`:** Rather than creating six
separate columns (one per dimension), weights and thresholds are stored as JSON objects. The
structure looks like:

```json
{
  "dim_001": 0.20,
  "dim_002": 0.25,
  "dim_003": 0.30,
  "dim_004": 0.10,
  "dim_005": 0.10,
  "dim_006": 0.05
}
```

This approach is correct here because the number of dimensions is fixed and the whole object
is always read or written together — you never query `WHERE dim_003 > 0.2` on this column.
If you needed to query by individual dimension values, a separate table would be better.

### Migration 009 — role_definitions

```bash
php artisan make:migration 009_create_role_definitions_table
```

```php
public function up(): void
{
    Schema::create('role_definitions', function (Blueprint $table) {
        $table->uuid('id')->primary();

        // Display name: "Junior PHP Developer", "Mid-Level Backend Engineer"
        $table->string('title', 200)->notNull();

        // JSON array of strings: ["php", "laravel", "backend"]
        // Used to match a role to a project's specialization_tags.
        $table->json('specialization_tags')->notNull();

        // Minimum years of experience to enrol in a session targeting this role.
        // 0 means anyone can enrol regardless of experience.
        $table->tinyInteger('min_years_experience')->unsigned()->default(0);

        // JSON object: {"dim_001": 0.20, "dim_002": 0.25, ...}
        // Weights must sum to 1.0. The EvalEngine uses these to compute weighted scores.
        $table->json('dimension_weights')->notNull();

        // JSON object: {"dim_001": "basic", "dim_002": "intermediate", ...}
        // The minimum tier a learner must reach in each dimension to qualify for this role.
        // Valid tier values: "basic", "intermediate", "advanced"
        $table->json('dimension_thresholds')->notNull();

        // true = this is a lead role (e.g. Tech Lead, Senior Architect).
        // Lead roles may have additional qualifications in Phase 10.
        $table->boolean('is_lead_role')->default(false);

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('role_definitions');
}
```

- [ ] Create `database/migrations/009_create_role_definitions_table.php`
- [ ] Run: `php artisan migrate`
- [ ] Verify: `DESCRIBE role_definitions;` — columns match the spec above

> **Note:** No seeder yet. Role definitions are populated by a content author through the admin
> UI built in Phase 4. You are only creating the table structure here.

---

## Step 2.3 — Concept Tags

**What this table stores:** A vocabulary of technical concepts that tasks can tag themselves
with. Examples: `dependency-injection`, `sql-joins`, `rest-api-design`, `git-branching`.
When a task has a knowledge anchor pointing to `dependency-injection`, the system can track
whether a learner has encountered and demonstrated mastery of that concept.

**The `source` column:** Concept tags can be added manually by a content author (`'manual'`)
or the system may recommend new tags based on task content, with an author accepting the
recommendation (`'ai_recommended_accepted'`). This is a forward-looking field; for Phase 2
everything will be `'manual'`.

### Migration 010 — concept_tags

```bash
php artisan make:migration 010_create_concept_tags_table
```

```php
public function up(): void
{
    Schema::create('concept_tags', function (Blueprint $table) {
        $table->uuid('id')->primary();

        // The concept name itself. Must be unique across the whole tag vocabulary.
        // Use kebab-case: 'dependency-injection', 'sql-joins', 'rest-api-design'
        $table->string('tag', 200)->unique()->notNull();

        // Which area this concept belongs to
        $table->enum('category', [
            'se_concept',            // Software engineering: SOLID, DI, testing patterns
            'cs_fundamental',        // Computer science: algorithms, data structures, complexity
            'tool_technology',       // Tools: Git, Docker, Laravel, MySQL
            'professional_practice', // Process: PR reviews, standups, estimating
            'regulatory',            // Compliance: GDPR, HIPAA, accessibility standards
        ])->notNull();

        $table->text('description')->nullable();

        // How this tag was added to the system
        $table->enum('source', [
            'manual',
            'ai_recommended_accepted',
        ])->default('manual')->notNull();

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('concept_tags');
}
```

- [ ] Create `database/migrations/010_create_concept_tags_table.php`
- [ ] Run: `php artisan migrate`
- [ ] Verify: `DESCRIBE concept_tags;` — note the two `ENUM` columns

---

## Step 2.4 — Project Templates

**What this table stores:** The top-level container for a simulation project — for example,
the MedQueue pharmacy system. Every scenario and task in the simulation belongs to a project.
The "template" suffix is important: this is the blueprint. When a learner enrols, the system
will create a live session based on this template (in Phase 5 and 6).

**Why `rubric_set_id` is `NULL` here:** There is a circular dependency. A `project_template`
references a `rubric_set` (each project has one rubric set). But a `rubric_set` also references
a `project_template` (a rubric set belongs to one project). You cannot create both tables at
the same time. The solution is: create `project_templates` first with `rubric_set_id` as a
nullable column (no FK constraint yet). Create `rubric_sets` later. Then add the FK constraint
in a separate `ALTER TABLE` migration (Step 2.15 below).

**JSON columns:** Several columns store rich structured data as JSON. This is the right choice
when the data is always read/written as a whole object and you never need to filter by individual
fields inside it. For example, `stakeholders` is always displayed together — you would never
write `WHERE stakeholders->name = 'Alice'`.

### Migration 011 — project_templates

```bash
php artisan make:migration 011_create_project_templates_table
```

```php
public function up(): void
{
    Schema::create('project_templates', function (Blueprint $table) {
        $table->uuid('id')->primary();

        // "MedQueue Pharmacy Management System"
        $table->string('title', 300)->notNull();

        // One-line summary displayed in the project catalogue
        $table->string('tagline', 500)->nullable();

        // The domain/industry of the simulated company
        // e.g. "healthcare", "fintech", "logistics", "saas"
        $table->string('project_type', 100)->notNull();

        $table->string('business_domain', 200)->nullable();

        // The full business context document shown to learners during induction.
        // Multi-paragraph rich text.
        $table->text('business_context')->notNull();

        // JSON: array of {name, role, goals} objects
        // Represents the fictional stakeholders in the project
        $table->json('stakeholders')->nullable();

        // JSON: array of strings — constraints that apply to the whole project
        // e.g. ["Must support 500 concurrent users", "HIPAA compliant"]
        $table->json('overarching_constraints')->nullable();

        // JSON: {language, framework, database, deployment, ...}
        // The technical environment the learner is working in
        $table->json('tech_context')->nullable();

        // JSON: array of strings — role/skill tags to match against role_definitions
        // e.g. ["php", "laravel", "backend", "api"]
        $table->json('specialization_tags')->notNull();

        // FK to organisations — allows org-specific projects (Phase 5+)
        $table->uuid('organisation_id')->nullable();
        $table->foreign('organisation_id')
            ->references('id')
            ->on('organisations')
            ->nullOnDelete();

        // Intentionally no FK here yet — rubric_sets does not exist yet.
        // Step 2.15 adds the FK constraint after rubric_sets is created.
        $table->uuid('rubric_set_id')->nullable();

        // Optional coding guidelines shown in the Artifact Vault
        $table->text('coding_guidelines')->nullable();

        // JSON: {sprints: 3, tasks_per_sprint: 4, avg_task_points: 3}
        // Used during sprint planning to suggest how many items to pull in
        $table->json('velocity_estimate')->nullable();

        $table->enum('difficulty_level', ['beginner', 'intermediate', 'advanced'])
            ->default('intermediate')
            ->notNull();

        // is_published: a content author has approved this project for learners
        $table->boolean('is_published')->default(false);

        // is_active: the project has not been archived
        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('project_templates');
}
```

- [ ] Create `database/migrations/011_create_project_templates_table.php`
- [ ] Run: `php artisan migrate`
- [ ] Verify: `DESCRIBE project_templates;` — note `rubric_set_id` is nullable with no FK constraint yet

---

## Step 2.5 — Scenario Templates

**What this table stores:** The individual scenarios within a project. A project might have
three scenarios: an initial diagnostic, a sprint planning scenario, and a sprint execution
scenario. Each scenario has its own narrative, a situation trigger (the thing that kicks the
learner into action), and a sequence position within the project.

**The `UNIQUE (project_id, sequence_order)` constraint:** This is a composite unique constraint.
It means: within a single project, no two scenarios can have the same position number. Two
different projects CAN both have a scenario at position `1`. This is enforced at the database
level, not just application level.

**`situation_trigger_type` enum:** The trigger is the realistic workplace artifact that starts
the scenario — an email from a manager, a Slack message, an incident report. The `type` column
controls how the UI renders it (as an email thread, a Slack-style chat bubble, etc.).

### Migration 012 — scenario_templates

```bash
php artisan make:migration 012_create_scenario_templates_table
```

```php
public function up(): void
{
    Schema::create('scenario_templates', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->uuid('project_id')->notNull();
        $table->foreign('project_id')
            ->references('id')
            ->on('project_templates')
            ->cascadeOnDelete();
        // When a project is deleted, all its scenarios are deleted too.
        // This makes sense: scenarios have no meaning without their project.

        // Position of this scenario within the project (1, 2, 3, ...)
        $table->smallInteger('sequence_order')->unsigned()->notNull();

        $table->string('title', 300)->notNull();

        // Multi-paragraph text setting the scene before the situation trigger.
        // Read-aloud style: "You've just joined the MedQueue team as a junior
        // developer. It's your second week..."
        $table->text('narrative_context')->notNull();

        // The specific event that triggers the learner's task.
        // The actual content of the email, Slack message, etc.
        $table->text('situation_trigger')->notNull();

        // Controls how the UI renders the trigger
        $table->enum('situation_trigger_type', [
            'slack_message',
            'email',
            'meeting_summary',
            'incident_report',
            'ticket',
            'handover_note',
        ])->notNull();

        // The fictional title the learner holds in this scenario
        // e.g. "Junior Backend Developer", "Sole Developer"
        $table->string('learner_role_label', 200)->nullable();

        // The default autonomy level — how much the learner is left to figure
        // out on their own. Can be overridden per-task via CAC variants.
        $table->enum('default_autonomy_level', ['low', 'mid', 'high'])
            ->default('mid')
            ->notNull();

        // true = this is the diagnostic scenario used for initial rank assignment
        $table->boolean('is_diagnostic')->default(false);

        $table->boolean('is_published')->default(false);
        $table->boolean('is_active')->default(true);

        $table->timestamps();

        // Ensures no two scenarios in the same project share the same position.
        // This is a COMPOSITE unique constraint: both columns together must be unique.
        $table->unique(['project_id', 'sequence_order']);
    });
}

public function down(): void
{
    Schema::dropIfExists('scenario_templates');
}
```

- [ ] Create `database/migrations/012_create_scenario_templates_table.php`
- [ ] Run: `php artisan migrate`
- [ ] Verify: `SHOW CREATE TABLE scenario_templates\G` — confirm FK and composite UNIQUE exist

---

## Step 2.6 — Scenario Reference Materials

**What this table stores:** Documents a learner can open while working on a scenario. These
are additional context — a fictional PRD excerpt, an email thread, a report from a previous
sprint. They differ from Artifact Vault items (which are project-wide) in that reference
materials are scenario-specific.

**`embedded_signals` JSON column:** This stores hints for the evaluation engine — structured
data about what signals the AI should look for to confirm the learner read and used this
material. For example: `[{"type": "terminology", "value": "idempotent endpoints"}]`.

### Migration 013 — scenario_reference_materials

```bash
php artisan make:migration 013_create_scenario_reference_materials_table
```

```php
public function up(): void
{
    Schema::create('scenario_reference_materials', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->uuid('scenario_id')->notNull();
        $table->foreign('scenario_id')
            ->references('id')
            ->on('scenario_templates')
            ->cascadeOnDelete();

        // What kind of document this is — controls the UI rendering style
        $table->enum('material_type', [
            'document',
            'email',
            'slack_message',
            'ticket',
            'report',
            'notes',
        ])->notNull();

        $table->string('title', 300)->notNull();

        // The full text content of the document
        $table->text('content')->notNull();

        // JSON: array of signal objects for the evaluation engine
        // e.g. [{"type": "terminology", "value": "idempotent"}]
        $table->json('embedded_signals')->nullable();

        // Controls the order materials appear in the sidebar
        $table->smallInteger('display_order')->unsigned()->default(0);

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('scenario_reference_materials');
}
```

- [ ] Create `database/migrations/013_create_scenario_reference_materials_table.php`
- [ ] Run: `php artisan migrate`

---

## Step 2.7 — Artifact Vault Items

**What this table stores:** Project-wide documents that a learner can access from the
"Artifact Vault" — a sidebar of reference documents. Unlike scenario reference materials
(which are per-scenario), vault items belong to the whole project. Examples: the PRD, SRS,
coding guidelines, a glossary of domain terms.

**`rank_gate` and `phase_gate`:** Some documents are locked until the learner reaches a
certain rank or progresses to a certain point in the session. For example, the full SRS
might only be available after the learner completes induction. These columns store those
unlock conditions.

### Migration 014 — artifact_vault_items

```bash
php artisan make:migration 014_create_artifact_vault_items_table
```

```php
public function up(): void
{
    Schema::create('artifact_vault_items', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->uuid('project_id')->notNull();
        $table->foreign('project_id')
            ->references('id')
            ->on('project_templates')
            ->cascadeOnDelete();

        // The type of document — controls the icon shown in the vault sidebar
        $table->enum('document_type', [
            'business_context',
            'prd',
            'srs',
            'sad',               // Software Architecture Document
            'coding_guidelines',
            'glossary',
            'sprint_goal_template',
        ])->notNull();

        $table->string('title', 300)->notNull();
        $table->text('content')->notNull();

        // Optional: only available to learners above this rank
        // e.g. "Mid-2" — format is "{tier}-{level}"
        $table->string('rank_gate', 20)->nullable();

        // Optional: only available at or after this session phase
        $table->enum('phase_gate', [
            'pre_induction',
            'post_induction',
            'post_sprint_1',
            'mid_session',
            'advanced_only',
        ])->nullable();

        // true = shown alongside tasks as a reference, not just in the vault
        $table->boolean('is_reference_doc')->default(false);

        $table->smallInteger('display_order')->unsigned()->default(0);

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('artifact_vault_items');
}
```

- [ ] Create `database/migrations/014_create_artifact_vault_items_table.php`
- [ ] Run: `php artisan migrate`

---

## Step 2.8 — Tasks

**What this table stores:** The individual work cards a learner completes. This is the most
complex content table in the system. A task has a brief (what to do), a type (core work vs.
a consequence for a failed submission vs. a suggestion for growth), CAC settings (complexity,
autonomy, context fidelity — explained below), and various optional configuration fields.

**The CAC model:**  
CAC stands for **Complexity / Autonomy / Context-fidelity**. Each task has three dials:

- **Complexity:** How difficult is the work? `low` = simpler version, `mid` = normal, `high` = harder.
- **Autonomy:** How much guidance is provided? `low` = lots of scaffolding, `mid` = some hints, `high` = on your own.
- **Context fidelity:** How realistic is the simulated environment? `low` = simplified, `high` = close to real production.

These are set dynamically based on the learner's current rank (`is_cac_runtime_set = true`), or
fixed for specific tasks like diagnostics (`fixed_complexity`, `fixed_autonomy`, etc.).

**`task_type` enum:**
- `core` — normal work task in the scenario
- `consequence` — injected when a learner fails a core task
- `suggestion` — injected as an optional growth challenge
- `diagnostic_scenario` — used during initial rank assignment
- `diagnostic_consequence` — consequence task within the diagnostic flow

**Why `UNIQUE (scenario_id, sequence_order)`?** Same reason as scenarios within projects:
each task has a defined position within its scenario, and no two tasks in the same scenario
can share the same position.

### Migration 015 — tasks

```bash
php artisan make:migration 015_create_tasks_table
```

```php
public function up(): void
{
    Schema::create('tasks', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->uuid('scenario_id')->notNull();
        $table->foreign('scenario_id')
            ->references('id')
            ->on('scenario_templates')
            ->cascadeOnDelete();

        // Position within the scenario
        $table->smallInteger('sequence_order')->unsigned()->notNull();

        $table->string('title', 300)->notNull();

        // The main task description shown to the learner.
        // For CAC-enabled tasks, this is the base brief; variants are in task_cac_variants.
        $table->text('task_brief')->notNull();

        // Which technical domain this task belongs to
        // e.g. "backend", "database", "api-design", "testing"
        $table->string('domain', 100)->notNull();

        $table->enum('task_type', [
            'core',
            'consequence',
            'suggestion',
            'diagnostic_scenario',
            'diagnostic_consequence',
        ])->default('core')->notNull();

        // JSON: array of role tags — which roles would typically do this task
        // e.g. ["backend-developer", "full-stack-developer"]
        $table->json('role_tags')->notNull();

        // JSON: array of tool names the learner should use
        // e.g. ["git", "phpunit", "postman"]
        $table->json('tools')->nullable();

        // JSON: array of concept tag IDs — concepts the learner is expected to know
        $table->json('prerequisite_concepts')->nullable();

        // true = CAC levels are assigned at runtime based on learner's rank
        // false = use the fixed_* columns below
        $table->boolean('is_cac_runtime_set')->default(true);

        // Used when is_cac_runtime_set = false
        $table->enum('fixed_complexity', ['low', 'mid', 'high'])->nullable();
        $table->enum('fixed_autonomy', ['low', 'mid', 'high'])->nullable();
        $table->enum('fixed_context_fidelity', ['low', 'mid', 'high'])->nullable();

        // JSON: array of task IDs to inject as consequences if this task is failed
        $table->json('consequence_task_ids')->nullable();

        // JSON: array of task IDs to suggest as optional growth after this task
        $table->json('suggestion_task_ids')->nullable();

        // true = the learner must produce an architectural design artifact
        $table->boolean('is_architectural')->default(false);

        // true = the sprint planning layer is active for this task
        // (learner must plan their approach before implementing)
        $table->boolean('planning_layer_active')->default(false);

        // JSON: Docker execution configuration for code deliverable tasks
        // Phase 7 feature — null until code execution is built
        $table->json('code_execution_config')->nullable();

        // A short summary of what a model response looks like (for author reference)
        $table->text('model_response_summary')->nullable();

        // Optional time limit for the task — null means no limit
        $table->integer('time_limit_minutes')->unsigned()->nullable();

        $table->boolean('is_published')->default(false);
        $table->boolean('is_active')->default(true);

        $table->timestamps();

        $table->unique(['scenario_id', 'sequence_order']);
    });
}

public function down(): void
{
    Schema::dropIfExists('tasks');
}
```

- [ ] Create `database/migrations/015_create_tasks_table.php`
- [ ] Run: `php artisan migrate`
- [ ] Verify: `DESCRIBE tasks;` — the table has all columns above

---

## Step 2.9 — Task Expected Deliverables

**What this table stores:** The specific outputs a learner must produce for a task. A single
task might expect multiple deliverables: a written explanation AND a code file AND a diagram.
Each deliverable has a type, a label (what to call it), and a description (what specifically
to produce).

**No `timestamps()` here:** These rows are authored once as content and then read. They are
not modified after creation (editing a task's deliverables means deleting and recreating them).
Timestamps would add noise without value.

### Migration 016 — task_expected_deliverables

```bash
php artisan make:migration 016_create_task_expected_deliverables_table
```

```php
public function up(): void
{
    Schema::create('task_expected_deliverables', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->uuid('task_id')->notNull();
        $table->foreign('task_id')
            ->references('id')
            ->on('tasks')
            ->cascadeOnDelete();

        // What kind of deliverable this is — controls submission form rendering
        $table->enum('type', [
            'written_explanation', // Prose text answer
            'artifact',            // A named document (e.g. entity-relationship diagram)
            'code',                // Code snippet or file
            'diagram',             // Visual diagram (uploaded image)
            'document',            // Formatted document (e.g. PRD section)
        ])->notNull();

        // Short name shown as the deliverable label in the submission form
        // e.g. "System Design Diagram", "Test Plan"
        $table->string('label', 200)->notNull();

        // Explanation of what specifically to produce
        $table->text('description')->notNull();

        $table->boolean('is_required')->default(true);

        $table->tinyInteger('display_order')->unsigned()->default(0);
    });
}

public function down(): void
{
    Schema::dropIfExists('task_expected_deliverables');
}
```

- [ ] Create `database/migrations/016_create_task_expected_deliverables_table.php`
- [ ] Run: `php artisan migrate`

---

## Step 2.10 — Task CAC Variants

**What this table stores:** Three rows per task (one per complexity level: `low`, `mid`,
`high`). Each variant contains a version of the task brief tailored to that complexity level,
plus optional scaffolding text and context text that changes how much help and how much
environmental realism the learner receives.

**Why three rows instead of three JSON blobs on the task?** Querying is cleaner. When the
system needs to present a `mid`-complexity version, it does `WHERE task_id = ? AND
complexity_level = 'mid'`. If all three variants were JSON fields on the task, you would have to
parse the whole JSON object in PHP to get the one you need.

**`UNIQUE (task_id, complexity_level)`:** A task can have at most one variant per complexity
level. The database enforces this so application code can rely on at most one row matching any
given `(task_id, complexity_level)` combination.

### Migration 017 — task_cac_variants

```bash
php artisan make:migration 017_create_task_cac_variants_table
```

```php
public function up(): void
{
    Schema::create('task_cac_variants', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->uuid('task_id')->notNull();
        $table->foreign('task_id')
            ->references('id')
            ->on('tasks')
            ->cascadeOnDelete();

        $table->enum('complexity_level', ['low', 'mid', 'high'])->notNull();

        // The version of the task brief at this complexity level.
        // Replaces or augments task_brief from the tasks table.
        $table->text('scenario_text')->notNull();

        // Scaffolding: step-by-step hints that appear at each autonomy level.
        // null = no scaffolding at that autonomy level.
        $table->text('scaffolding_text_low')->nullable();  // most guidance
        $table->text('scaffolding_text_mid')->nullable();  // moderate guidance
        $table->text('scaffolding_text_high')->nullable(); // no guidance

        // Context: additional environmental detail at each context-fidelity level.
        $table->text('context_text_low')->nullable();   // simplified context
        $table->text('context_text_mid')->nullable();   // moderate realism
        $table->text('context_text_high')->nullable();  // full production realism

        $table->unique(['task_id', 'complexity_level']);
    });
}

public function down(): void
{
    Schema::dropIfExists('task_cac_variants');
}
```

- [ ] Create `database/migrations/017_create_task_cac_variants_table.php`
- [ ] Run: `php artisan migrate`

---

## Step 2.11 — Task Dependencies

**What this table stores:** A prerequisite relationship between two tasks. If `task_B` depends
on `task_A`, the learner must complete `task_A` before they can start `task_B`.

**Self-referencing foreign key:** Both `task_id` and `prerequisite_task_id` reference the same
`tasks` table. This is called a self-referencing (or recursive) FK. It is valid and common in
relational databases for hierarchical or ordered data.

**Why `CASCADE` but no cascade on `prerequisite_task_id`?** If a task is deleted, its own
dependency rows should disappear (cascade on `task_id`). But if a prerequisite is deleted,
we deliberately do NOT want the dependency row to disappear silently — we want the deletion
to fail with a FK constraint error, forcing the content author to manually fix the dependency
graph first. Hence `RESTRICT` on `prerequisite_task_id`.

### Migration 018 — task_dependencies

```bash
php artisan make:migration 018_create_task_dependencies_table
```

```php
public function up(): void
{
    Schema::create('task_dependencies', function (Blueprint $table) {
        $table->uuid('id')->primary();

        // The task that has a prerequisite
        $table->uuid('task_id')->notNull();
        $table->foreign('task_id')
            ->references('id')
            ->on('tasks')
            ->cascadeOnDelete();

        // The task that must be completed first
        $table->uuid('prerequisite_task_id')->notNull();
        $table->foreign('prerequisite_task_id')
            ->references('id')
            ->on('tasks')
            ->restrictOnDelete();
        // RESTRICT: if someone tries to delete a task that is a prerequisite
        // of another task, the database will refuse — forces explicit cleanup.

        // No two rows with the same (task_id, prerequisite_task_id) pair
        $table->unique(['task_id', 'prerequisite_task_id']);
    });
}

public function down(): void
{
    Schema::dropIfExists('task_dependencies');
}
```

- [ ] Create `database/migrations/018_create_task_dependencies_table.php`
- [ ] Run: `php artisan migrate`

---

## Step 2.12 — Task Knowledge Anchors

**What this table stores:** Links a task to a concept from `concept_tags`, specifying exactly
how the learner is expected to apply that concept in this task. This is richer than a simple
tag — it records the `application_expectation` (what specifically should be demonstrated) and
a `remediation_hint` (what to tell the learner if they miss it).

The evaluation engine uses knowledge anchors to assess concept-level mastery, not just
dimension-level scores.

### Migration 019 — task_knowledge_anchors

```bash
php artisan make:migration 019_create_task_knowledge_anchors_table
```

```php
public function up(): void
{
    Schema::create('task_knowledge_anchors', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->uuid('task_id')->notNull();
        $table->foreign('task_id')
            ->references('id')
            ->on('tasks')
            ->cascadeOnDelete();

        $table->uuid('concept_id')->notNull();
        $table->foreign('concept_id')
            ->references('id')
            ->on('concept_tags')
            ->restrictOnDelete();
        // RESTRICT: do not allow a concept to be deleted while tasks depend on it.

        // Denormalised copy of the concept name — avoids a JOIN when displaying
        // knowledge anchors in the admin UI without loading the concept_tags table.
        $table->string('concept_name', 200)->notNull();

        // Which technical area this anchor belongs to
        // e.g. "object-oriented-design", "database-normalisation"
        $table->string('domain', 200)->notNull();

        // What specifically should the learner demonstrate?
        // e.g. "Show understanding of dependency injection by using constructor injection
        // rather than service locator pattern"
        $table->text('application_expectation')->notNull();

        // Is demonstrating this concept required to pass the task?
        $table->boolean('is_required')->default(false);

        // What to tell the learner if they missed this concept
        $table->text('remediation_hint')->notNull();
    });
}

public function down(): void
{
    Schema::dropIfExists('task_knowledge_anchors');
}
```

- [ ] Create `database/migrations/019_create_task_knowledge_anchors_table.php`
- [ ] Run: `php artisan migrate`

---

## Step 2.13 — Task Guidance Prompts

**What this table stores:** Contextual guidance messages that the system can surface to a
learner during task execution. A guidance prompt is triggered by a specific competency
dimension (e.g. "if the learner is struggling with Testing"), filtered by autonomy level
(low-autonomy learners get more prompts), and delivered either proactively (shown automatically)
or reactively (shown when the learner asks for help).

### Migration 020 — task_guidance_prompts

```bash
php artisan make:migration 020_create_task_guidance_prompts_table
```

```php
public function up(): void
{
    Schema::create('task_guidance_prompts', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->uuid('task_id')->notNull();
        $table->foreign('task_id')
            ->references('id')
            ->on('tasks')
            ->cascadeOnDelete();

        // Which dimension this prompt is aimed at helping with
        // e.g. "dim_004" for Testing — or a descriptive label like "test-coverage"
        $table->string('trigger_dimension', 200)->notNull();

        // The actual guidance text shown to the learner
        $table->text('prompt_text')->notNull();

        // Which autonomy level should see this prompt
        // low = shown to learners with lots of scaffolding (beginners)
        // high = shown even to autonomous learners (expert-level hints)
        $table->enum('autonomy_level_filter', ['low', 'mid', 'high'])->notNull();

        // proactive = shown without the learner asking
        // reactive = shown only when the learner requests help
        $table->enum('delivery_mode', ['proactive', 'reactive'])->notNull();

        $table->tinyInteger('display_order')->unsigned()->default(0);
    });
}

public function down(): void
{
    Schema::dropIfExists('task_guidance_prompts');
}
```

- [ ] Create `database/migrations/020_create_task_guidance_prompts_table.php`
- [ ] Run: `php artisan migrate`

---

## Step 2.14 — Rubric Sets

**What this table stores:** The container for all rubric criteria belonging to a project.
Every project has exactly one rubric set. A rubric set exists as its own table (rather than
just being an ID on `project_templates`) because it has its own lifecycle: it can be versioned,
swapped out, and referenced by `rubric_criteria` directly.

**Why `project_id UNIQUE`?** This enforces the one-to-one relationship between a project
and its rubric set at the database level. Without `UNIQUE`, a project could accidentally end
up with two rubric sets.

### Migration 021 — rubric_sets

```bash
php artisan make:migration 021_create_rubric_sets_table
```

```php
public function up(): void
{
    Schema::create('rubric_sets', function (Blueprint $table) {
        $table->uuid('id')->primary();

        // UNIQUE enforces one-to-one: each project has exactly one rubric set
        $table->uuid('project_id')->unique()->notNull();
        $table->foreign('project_id')
            ->references('id')
            ->on('project_templates')
            ->cascadeOnDelete();

        // Version string — useful for tracking rubric revisions
        // e.g. "1.0", "1.1", "2.0"
        $table->string('version', 20)->default('1.0')->notNull();

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('rubric_sets');
}
```

- [ ] Create `database/migrations/021_create_rubric_sets_table.php`
- [ ] Run: `php artisan migrate`

---

## Step 2.15 — Add rubric_set_id FK to project_templates

**Why this is a separate migration:** When `project_templates` was created in Step 2.4,
`rubric_sets` did not exist yet. Now that it does, we can add the foreign key constraint that
enforces the relationship in both directions.

**`ALTER TABLE` vs. creating the column again:** The column `rubric_set_id` already exists
on `project_templates` (it was added as nullable in Step 2.4). This migration does not add
the column — it adds the FK constraint on the existing column.

### Migration 022 — alter_project_templates_add_rubric_set_fk

```bash
php artisan make:migration 022_alter_project_templates_add_rubric_set_fk
```

```php
public function up(): void
{
    Schema::table('project_templates', function (Blueprint $table) {
        // Add the FK constraint on the existing rubric_set_id column.
        // The column already exists as nullable UUID — we are only adding the constraint.
        $table->foreign('rubric_set_id')
            ->references('id')
            ->on('rubric_sets')
            ->nullOnDelete();
        // nullOnDelete: if a rubric_set is deleted, set project rubric_set_id to NULL
        // rather than deleting the project. Projects outlive their rubric sets.
    });
}

public function down(): void
{
    Schema::table('project_templates', function (Blueprint $table) {
        // Must drop the FK constraint before rolling back, or MySQL will complain.
        $table->dropForeign(['rubric_set_id']);
    });
}
```

- [ ] Create `database/migrations/022_alter_project_templates_add_rubric_set_fk.php`
- [ ] Run: `php artisan migrate`
- [ ] Verify: `SHOW CREATE TABLE project_templates\G` — `rubric_set_id` FK now appears

> **MySQL tip:** `SHOW CREATE TABLE tablename\G` shows you the full table definition including
> all FK constraints. The `\G` at the end formats the output vertically, which is easier to
> read for tables with many columns. This is the most reliable way to verify FKs are in place.

---

## Step 2.16 — Rubric Criteria

**What this table stores:** The individual scoring criteria that the EvalEngine uses to
evaluate a submission. Each criterion belongs to a rubric set AND a specific task AND a
specific competency dimension. It includes four descriptive paragraphs (one per score level:
Distinguished, Proficient, Developing, Beginning) that describe what a response looks like
at that level.

**`claude_detection_hint`:** This is one of the most important fields in the whole system.
It is the instruction given to the Claude API when evaluating a submission: "Here is what to
look for to assign a high score on this criterion." Getting this field right is what makes
the AI evaluation accurate. Every criterion MUST have a detection hint.

**`weight` vs `dimension_weight`:**
- `weight` — the weight of this *criterion* within its dimension. All criteria for the same task+dimension should sum to 1.0.
- `dimension_weight` — the weight of this *dimension* across the whole task. All dimension weights for the same task should sum to 1.0.

### Migration 023 — rubric_criteria

```bash
php artisan make:migration 023_create_rubric_criteria_table
```

```php
public function up(): void
{
    Schema::create('rubric_criteria', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->uuid('rubric_set_id')->notNull();
        $table->foreign('rubric_set_id')
            ->references('id')
            ->on('rubric_sets')
            ->cascadeOnDelete();

        $table->uuid('task_id')->notNull();
        $table->foreign('task_id')
            ->references('id')
            ->on('tasks')
            ->cascadeOnDelete();

        // A human-readable label for this criterion within the task
        // e.g. "Database schema design", "Error handling completeness"
        $table->string('task_dimension_label', 200)->notNull();

        // Which of the six competence dimensions this criterion measures
        $table->string('parent_dimension_id', 20)->notNull();
        $table->foreign('parent_dimension_id')
            ->references('id')
            ->on('competence_dimensions')
            ->restrictOnDelete();

        // Which complexity level this criterion applies to
        $table->enum('complexity_level', ['low', 'mid', 'high'])->notNull();

        // The criterion statement: "The learner correctly identified all entities..."
        $table->text('criterion_text')->notNull();

        // Weight of this criterion within its dimension for this task (0–1)
        $table->decimal('weight', 4, 3)->notNull();

        // Weight of the parent dimension across the whole task (0–1)
        // All dimension_weight values for the same task must sum to 1.0
        $table->decimal('dimension_weight', 4, 3)->notNull();

        // Instructions for Claude: "Look for evidence that the learner..."
        // This is the most important field — write it carefully.
        $table->text('claude_detection_hint')->notNull();

        // Prose description of what a Distinguished (4/4) response looks like
        $table->text('distinguished_description')->notNull();

        // Prose description of what a Proficient (3/4) response looks like
        $table->text('proficient_description')->notNull();

        // Prose description of what a Developing (2/4) response looks like
        $table->text('developing_description')->notNull();

        // Prose description of what a Beginning (1/4) response looks like
        $table->text('beginning_description')->notNull();

        // true = this criterion is specifically about architectural decisions
        $table->boolean('is_architectural')->default(false);

        // true = this criterion evaluates the planning layer deliverable
        $table->boolean('is_planning_layer')->default(false);

        // Optional anchor into a reference document — e.g. "See SRS section 3.2"
        $table->text('reference_doc_anchor')->nullable();
    });
}

public function down(): void
{
    Schema::dropIfExists('rubric_criteria');
}
```

- [ ] Create `database/migrations/023_create_rubric_criteria_table.php`
- [ ] Run: `php artisan migrate`

---

## Step 2.17 — Follow-Up Prompt Templates

**What this table stores:** Templates for follow-up questions that the AI evaluation engine
can ask a learner after reviewing their submission. These are used to probe deeper understanding
or to test whether the learner copied their answer from somewhere (`is_anti_copy = true`).

Unlike other tables, these have no FK to a specific task or scenario — they are reusable
templates that can be applied across many tasks based on domain and trigger conditions.

### Migration 024 — follow_up_prompt_templates

```bash
php artisan make:migration 024_create_follow_up_prompt_templates_table
```

```php
public function up(): void
{
    Schema::create('follow_up_prompt_templates', function (Blueprint $table) {
        $table->uuid('id')->primary();

        // Which technical domain this prompt applies to
        // e.g. "database-design", "testing", "api-design"
        $table->string('domain', 100)->notNull();

        // The follow-up question text
        // e.g. "You mentioned using a foreign key here — why did you choose that
        // over a soft reference?"
        $table->text('prompt_text')->notNull();

        // What condition triggers this follow-up to be selected
        // e.g. "Learner mentioned indexing but did not explain rationale"
        $table->text('trigger_condition')->notNull();

        // true = this question is specifically designed to detect copied answers
        // (questions only a person who actually did the work could answer)
        $table->boolean('is_anti_copy')->default(false);
    });
}

public function down(): void
{
    Schema::dropIfExists('follow_up_prompt_templates');
}
```

- [ ] Create `database/migrations/024_create_follow_up_prompt_templates_table.php`
- [ ] Run: `php artisan migrate`

---

## Step 2.18 — Backlog Item Templates

**What this table stores:** The initial backlog for a project — the list of user stories or
work items that will be visible to the learner in the virtual sprint board. These are templates:
when a learner enrols in a project, these template items are copied into live `learner_backlog_items`
rows for that learner's session (built in Phase 6).

**`default_priority` (MoSCoW):** Priority is expressed using the MoSCoW method common in
agile product management: Must Have, Should Have, Could Have, Won't Have.

**`task_id` nullable FK:** Some backlog items correspond directly to a task the learner will
work on (the learner drags the backlog item into a sprint, then completes the task). Others
are informational items with no corresponding task. Hence nullable.

### Migration 025 — backlog_item_templates

```bash
php artisan make:migration 025_create_backlog_item_templates_table
```

```php
public function up(): void
{
    Schema::create('backlog_item_templates', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->uuid('project_id')->notNull();
        $table->foreign('project_id')
            ->references('id')
            ->on('project_templates')
            ->cascadeOnDelete();

        $table->string('title', 300)->notNull();
        $table->text('description')->notNull();

        // MoSCoW priority
        $table->enum('default_priority', [
            'must_have',
            'should_have',
            'could_have',
            'wont_have',
        ])->notNull();

        // JSON: roles that would typically pick up this backlog item
        $table->json('role_tags')->notNull();

        // The task that gets unlocked when this backlog item is in-sprint.
        // Nullable: not all backlog items map directly to a task.
        $table->uuid('task_id')->nullable();
        $table->foreign('task_id')
            ->references('id')
            ->on('tasks')
            ->nullOnDelete();

        // JSON: array of backlog_item_template IDs that must be 'done' first
        $table->json('dependency_item_ids')->nullable();

        // Controls the order items appear in the backlog sidebar
        $table->smallInteger('display_order')->unsigned()->default(0);
    });
}

public function down(): void
{
    Schema::dropIfExists('backlog_item_templates');
}
```

- [ ] Create `database/migrations/025_create_backlog_item_templates_table.php`
- [ ] Run: `php artisan migrate`

---

## Step 2.19 — Register Seeders in DatabaseSeeder

Now that `CompetenceDimensionSeeder` is created, register it so it runs when you call
`php artisan db:seed`.

- [ ] Open `database/seeders/DatabaseSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Phase 0
        $this->call(FeatureFlagSeeder::class);

        // Phase 1
        $this->call(RoleSeeder::class);

        // Phase 2
        $this->call(CompetenceDimensionSeeder::class);
        // role_definitions, concept_tags, project content: seeded via admin UI in Phase 4
    }
}
```

- [ ] Run: `php artisan db:seed` — all three seeders should run without errors
- [ ] Verify: `SELECT COUNT(*) FROM competence_dimensions;` — returns 6

---

## Step 2.20 — Domain Value Objects and Enums (Stubs)

Phase 3 will build all the Eloquent models. But this phase creates a few small domain objects
that encode the domain's vocabulary. These are pure PHP — no database, no framework. Creating
them now means Phase 3 migrations can reference them correctly.

### ConceptTagCategory Enum

**What it does:** Represents the valid categories a concept tag can belong to. Using a backed
enum means: (a) you get autocomplete in your IDE when typing `ConceptTagCategory::`, (b) the
PHP type system will reject anything that is not a valid category, (c) you can call
`ConceptTagCategory::from('se_concept')` to safely parse a string from the database and get
an enum back.

- [ ] Create `src/Simulation/Domain/ConceptTag/ConceptTagCategory.php`:

```php
<?php

namespace Src\Simulation\Domain\ConceptTag;

/**
 * The five categories a concept tag can belong to.
 *
 * Backed enum (PHP 8.1+): each case has an underlying string value.
 * The value matches exactly what is stored in the concept_tags.category column.
 *
 * Usage:
 *   ConceptTagCategory::from('se_concept')         // parses safely from DB
 *   ConceptTagCategory::SeConcept->value           // returns 'se_concept'
 *   ConceptTagCategory::SeConcept->label()         // returns 'Software Engineering Concept'
 */
enum ConceptTagCategory: string
{
    case SeConcept            = 'se_concept';
    case CsFundamental        = 'cs_fundamental';
    case ToolTechnology       = 'tool_technology';
    case ProfessionalPractice = 'professional_practice';
    case Regulatory           = 'regulatory';

    /**
     * Returns a human-readable label for display in the admin UI.
     *
     * This is a method on the enum — enums in PHP 8.1+ can have methods,
     * just like classes. They cannot have mutable properties, but methods
     * and constants are allowed.
     */
    public function label(): string
    {
        // match expression (PHP 8.0+): like switch but requires exhaustive matching
        // and returns a value. Unlike switch, it throws UnhandledMatchError if no
        // arm matches — making it safer than switch's implicit fall-through.
        return match($this) {
            self::SeConcept            => 'Software Engineering Concept',
            self::CsFundamental        => 'Computer Science Fundamental',
            self::ToolTechnology       => 'Tool or Technology',
            self::ProfessionalPractice => 'Professional Practice',
            self::Regulatory           => 'Regulatory / Compliance',
        };
    }
}
```

### TaskType Enum

- [ ] Create `src/Simulation/Domain/Task/TaskType.php`:

```php
<?php

namespace Src\Simulation\Domain\Task;

/**
 * The five types a task can be.
 *
 * This enum is used both in the domain (to enforce valid task types in PHP)
 * and by the database (the ENUM column in the tasks table stores the ->value).
 *
 * PHP 8.1 backed enums let you convert between the PHP enum and the DB string
 * safely using from() and tryFrom():
 *   TaskType::from('core')         // returns TaskType::Core, throws on invalid
 *   TaskType::tryFrom('invalid')   // returns null instead of throwing
 */
enum TaskType: string
{
    case Core                   = 'core';
    case Consequence            = 'consequence';
    case Suggestion             = 'suggestion';
    case DiagnosticScenario     = 'diagnostic_scenario';
    case DiagnosticConsequence  = 'diagnostic_consequence';

    /**
     * true if this is an injected task (not part of the original content plan).
     * Consequence and suggestion tasks are injected at runtime by the EvalEngine.
     */
    public function isInjected(): bool
    {
        return match($this) {
            self::Core, self::DiagnosticScenario => false,
            default                              => true,
        };
    }

    /**
     * true if this task is part of the diagnostic pathway.
     */
    public function isDiagnostic(): bool
    {
        return match($this) {
            self::DiagnosticScenario, self::DiagnosticConsequence => true,
            default                                               => false,
        };
    }
}
```

### DifficultyLevel Enum

- [ ] Create `src/Simulation/Domain/Project/DifficultyLevel.php`:

```php
<?php

namespace Src\Simulation\Domain\Project;

/**
 * The difficulty level of a project template.
 *
 * Shown in the project catalogue so learners can choose appropriate projects.
 */
enum DifficultyLevel: string
{
    case Beginner     = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced     = 'advanced';
}
```

### HasAvailability Interface (already exists in Shared)

The `HasAvailability` interface was already created in Phase 1 in
`src/Shared/Domain/Contract/HasAvailability.php`. All three content domain entities
(`ProjectTemplate`, `ScenarioTemplate`, `Task`) will implement it in Phase 3.

---

## Step 2.21 — Service Provider Stubs for Content Modules

The `SimulationServiceProvider` and `ContentServiceProvider` are already registered in
`bootstrap/providers.php` (you can see them in the repo). They are currently empty stubs.
Leave them as-is — they will be populated in Phase 3 and Phase 4 when repository bindings
are added.

No action needed here. This note is here so you know you are not missing anything.

---

## Phase 2 Verification Checklist

Do not proceed to Phase 3 until every item below passes. Work through them top to bottom.

### Migration Integrity

- [ ] Run `php artisan migrate:fresh` — ALL migrations run from scratch with zero errors
- [ ] Run `php artisan db:seed` after `migrate:fresh` — all three seeders run without error

### Table Existence

Run `SHOW TABLES;` in MySQL and confirm every table below exists:

- [ ] `feature_flags` (Phase 0)
- [ ] `organisations` (Phase 1)
- [ ] `users` (Phase 1)
- [ ] `learners` (Phase 1)
- [ ] `roles` (Phase 1)
- [ ] `user_role` (Phase 1)
- [ ] `competence_dimensions` (Phase 2, Step 2.1)
- [ ] `role_definitions` (Phase 2, Step 2.2)
- [ ] `concept_tags` (Phase 2, Step 2.3)
- [ ] `project_templates` (Phase 2, Step 2.4)
- [ ] `scenario_templates` (Phase 2, Step 2.5)
- [ ] `scenario_reference_materials` (Phase 2, Step 2.6)
- [ ] `artifact_vault_items` (Phase 2, Step 2.7)
- [ ] `tasks` (Phase 2, Step 2.8)
- [ ] `task_expected_deliverables` (Phase 2, Step 2.9)
- [ ] `task_cac_variants` (Phase 2, Step 2.10)
- [ ] `task_dependencies` (Phase 2, Step 2.11)
- [ ] `task_knowledge_anchors` (Phase 2, Step 2.12)
- [ ] `task_guidance_prompts` (Phase 2, Step 2.13)
- [ ] `rubric_sets` (Phase 2, Step 2.14)
- [ ] `rubric_criteria` (Phase 2, Step 2.16)
- [ ] `follow_up_prompt_templates` (Phase 2, Step 2.17)
- [ ] `backlog_item_templates` (Phase 2, Step 2.18)

### Data Checks

- [ ] `SELECT COUNT(*) FROM competence_dimensions;` — returns **6**
- [ ] `SELECT id, short_label, sequence_order FROM competence_dimensions ORDER BY sequence_order;` — all 6 dimensions in order
- [ ] `SELECT COUNT(*) FROM feature_flags;` — returns **17** (unchanged from Phase 0)
- [ ] `SELECT COUNT(*) FROM roles;` — returns **4** (unchanged from Phase 1)

### Structural Checks

- [ ] `SHOW CREATE TABLE project_templates\G` — `rubric_set_id` has a FK constraint to `rubric_sets`
- [ ] `SHOW CREATE TABLE scenario_templates\G` — composite `UNIQUE (project_id, sequence_order)` exists
- [ ] `SHOW CREATE TABLE tasks\G` — composite `UNIQUE (scenario_id, sequence_order)` exists
- [ ] `SHOW CREATE TABLE task_dependencies\G` — two FKs to `tasks`, second one is `RESTRICT`
- [ ] `SHOW CREATE TABLE task_cac_variants\G` — composite `UNIQUE (task_id, complexity_level)` exists
- [ ] `SHOW CREATE TABLE rubric_sets\G` — `project_id` is `UNIQUE`

### Enum Checks

- [ ] Run in tinker: `Src\Simulation\Domain\ConceptTag\ConceptTagCategory::from('se_concept')` — returns `ConceptTagCategory::SeConcept`
- [ ] Run in tinker: `Src\Simulation\Domain\Task\TaskType::Core->isInjected()` — returns `false`
- [ ] Run in tinker: `Src\Simulation\Domain\Task\TaskType::Consequence->isInjected()` — returns `true`
- [ ] Run in tinker: `Src\Simulation\Domain\Project\DifficultyLevel::Intermediate->value` — returns `'intermediate'`

### Rollback Check

- [ ] Run `php artisan migrate:rollback --step=18` — rolls back all Phase 2 migrations without error
- [ ] Run `php artisan migrate` — re-applies all migrations without error

> If either rollback or re-apply fails, there is a bug in one of the `down()` methods.
> Fix it before continuing — a broken rollback means you cannot safely undo mistakes in production.

---

## What Phase 3 Will Build On Top of This

With the schema complete, Phase 3 creates an Eloquent model for every table. Each model will:

- Use `HasUuids` on tables with UUID primary keys
- Define `$fillable` arrays that match the migration columns
- Define `$casts` arrays to automatically cast JSON columns to arrays and enum columns to their PHP enum types
- Define relationship methods (`hasMany`, `belongsTo`, `hasOne`) that mirror the FK structure you just built

The models for the content layer go in:
```
src/Simulation/Infrastructure/Persistence/Eloquent/Model/
    ProjectTemplateModel.php
    ScenarioTemplateModel.php
    ArtifactVaultItemModel.php
    BacklogItemTemplateModel.php

src/Simulation/Domain/Task/
    TaskModel.php
    TaskExpectedDeliverableModel.php
    TaskCacVariantModel.php
    TaskDependencyModel.php
    TaskKnowledgeAnchorModel.php
    TaskGuidancePromptModel.php

src/EvalEngine/Infrastructure/Persistence/Eloquent/Model/
    RubricSetModel.php
    RubricCriterionModel.php
    FollowUpPromptTemplateModel.php

src/Competency/Infrastructure/Persistence/Eloquent/Model/
    CompetenceDimensionModel.php
    RoleDefinitionModel.php
    ConceptTagModel.php
```

You will not write those models now. That is Phase 3's job. But understanding this upcoming
structure explains why the migrations are designed the way they are.

---

## Known Issues in the Phase 1 Codebase to Fix Before Phase 3

While reading the existing code, the following bugs were identified. They do not affect Phase 2
(which is migrations only), but they will cause runtime errors in Phase 3 and beyond. Fix them
before moving on.

### Bug 1: `RegisterUserHandler` — wrong property access

In `src/Identity/Application/Command/RegisterUser/RegisterUserHandler.php`:

```php
// BUGGY lines:
$user = User::register(
    id: UserId::fromString($uuidGenerator->generate),  // ❌ property not method
    ...
    password: Hash::make(command->password),           // ❌ missing $
);

$$this->repository->save($user);  // ❌ double $ is a variable variable
```

Fix:

```php
$user = User::register(
    id:           UserId::fromString($this->uuidGenerator->generate()), // ✅ method call
    fullname:     $command->name,
    email:        $command->email,
    passwordHash: Hash::make($command->password),                        // ✅ $command
);

$this->repository->save($user);  // ✅ single $
```

Also: `throw new EmailAlreadyExistsException($command->Email)` should be `$command->email`
(lowercase — PHP properties are case-sensitive).

### Bug 2: `EnrolAsLearnerHandler` — wrong variable name

In `src/SimExecution/Application/Command/EnrolAsLearner/EnrolAsLearnerHandler.php`:

```php
// BUGGY:
if ($repositoy->existsForUser($command->userId)) {  // ❌ typo: $repositoy
    ...
    id: LearnerId::fromString($this->uuidGenerator),  // ❌ not calling generate()
    ...
    foreach ($this->learner->releaseEvents() ...     // ❌ $this->learner doesn't exist
```

Fix:

```php
if ($this->repository->existsForUser($command->userId)) {  // ✅ $this->repository
    ...
    id: LearnerId::fromString($this->uuidGenerator->generate()),  // ✅ ->generate()
    ...
    foreach ($learner->releaseEvents() as $event) {  // ✅ local $learner variable
```

Also: `Entrycategory` should be `EntryCategory` (capital C — PHP class names are
case-sensitive) and `entrycategory:` named argument should be `entryCategory:`.

### Bug 3: `EnrolAsLearnerCommand` — wrong namespace

In `src/SimExecution/Application/Command/EnrolAsLearner/EnrolAsLearnerCommand.php`:

```php
// BUGGY:
namespace Src\Application\Command\EnrolAsLearner;  // ❌ missing SimExecution

// FIXED:
namespace Src\SimExecution\Application\Command\EnrolAsLearner;
```

Also: the constructor properties use `private readonly` but are accessed in the handler as
`$command->userId` etc., which requires them to be `public readonly`. Fix the constructor:

```php
public function __construct(
    public readonly string  $userId,
    public readonly string  $entryCategory,
    public readonly ?int    $yearsExperience = null,
    public readonly ?string $organisationId = null,
) {}
```

### Bug 4: `LearnerId` — wrong namespace import

In `src/SimExecution/Domain/Enrollment/LearnerId.php`:

```php
// BUGGY:
use Src\Domain\Infrastructure\Id\UuidGenerator;  // ❌ this namespace doesn't exist

// FIXED:
use Src\Shared\Infrastructure\Id\UuidGenerator;  // ✅ correct path
```

### Bug 5: `UserMapper` — argument name mismatch

In `src/Identity/Infrastructure/Persistence/Eloquent/Mapper/UserMapper.php`,
`toEntity()` calls `User::reconstitute(name: ..., ...)` but the `User` constructor
uses the parameter name `fullname`. Both must match:

```php
// Either the mapper uses 'fullname:':
return User::reconstitute(
    id:           UserId::fromString($model->id),
    fullname:     $model->name,   // ← match the constructor parameter name
    email:        $model->email,
    passwordHash: $model->password,
);

// OR rename the User constructor parameter to 'name'.
// Pick one and be consistent everywhere.
```

> These bugs are all PHP mistakes — wrong variable names, missing `->`, wrong namespaces.
> They are natural in early development. The architecture and design are sound; it is the
> mechanical details that need tightening. Fix them file by file before Phase 3.
