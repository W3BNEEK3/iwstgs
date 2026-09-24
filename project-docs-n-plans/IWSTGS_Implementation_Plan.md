# IWSTGS — Full Implementation Plan
**Solo Build · Laravel 13 · HTMX · _HyperScript · MySQL**

> **How to use this file:** Work top to bottom. Every phase must be fully complete before starting the next. Within a phase, tasks are ordered — do not skip ahead. Each task has a checkbox. Check it off when done.
>
> **Schema vs Migrations:** Always write the migration first. Never create a schema diagram and then write migrations from it — that creates drift. The migration IS the schema. Design in your head, write in the migration file, verify with `php artisan migrate:fresh` and inspect the actual tables.
>
> **Feature flags:** A feature flag table is set up in Phase 1. Every feature listed in later phases has a flag name. Before building a feature, register its flag. This lets you deploy dead code safely and turn things on when ready.

---

## Phase Index

| Phase | Name | What You're Building |
|-------|------|---------------------|
| 0 | Foundation | Laravel skeleton, module structure, shared kernel |
| 1 | Identity & Access | Auth, RBAC, organisations, feature flags |
| 2 | Core Content Schema | All database tables for Simulation content |
| 3 | Competency Definitions | Six canonical dimensions, roles |
| 4 | Content Management | Project, scenario, task authoring (admin) |
| 5 | Learner Onboarding | Registration, diagnostic, rank assignment |
| 6 | Session & Sprint Engine | SimExecution — sessions, sprints, backlog |
| 7 | Submission System | Four-layer submission, Docker code execution |
| 8 | Evaluation Engine | Rubric criteria, AIMediation, scoring |
| 9 | Adaptive Engine | Consequence tasks, gap flags, rank events |
| 10 | Reporting & Profile | Competency graph, qualification reports |
| 11 | Polish & Launch Prep | Feature flag cleanup, performance, hardening |

---

## PHASE 0 — Foundation

> Goal: A running Laravel 13 app with the correct folder structure, autoloading, database connection, and shared kernel in place. Nothing domain-specific yet.

### 0.1 — Laravel Setup
- [ ] `composer create-project laravel/laravel iwstgs`
- [ ] Set PHP 8.3+ requirement in `composer.json`
- [ ] Configure `.env` — MySQL connection (use SQLite for local if needed)
- [ ] Run `php artisan key:generate`
- [ ] Confirm `php artisan serve` runs without errors
- [ ] Install HTMX via CDN reference in base Blade layout (no npm required for MVP)
- [ ] Install _HyperScript via CDN reference in base Blade layout
- [ ] Create base Blade layout: `resources/views/layouts/app.blade.php`

### 0.2 — Module Directory Structure
- [ ] Create all module directories:
```
mkdir -p src/{Shared,Identity,Organizations,Content,Simulation,SimExecution,Competency,LearnerProfile,EvalEngine,Submission,AIMediation,Reporting}
```
- [ ] Inside each module, create subdirectories:
```
mkdir -p src/[Module]/{Domain,Application,Infrastructure,Presentation}
mkdir -p src/[Module]/Domain/{Models,ValueObjects,Events,Repositories}
mkdir -p src/[Module]/Application/{Commands,Queries,DTOs}
mkdir -p src/[Module]/Infrastructure/{Persistence,Services}
mkdir -p src/[Module]/Presentation/{Controllers,Requests,Resources,Views}
```
- [ ] Add `src/` to PSR-4 autoload in `composer.json`:
```json
"autoload": {
  "psr-4": {
    "App\\": "app/",
    "Src\\": "src/"
  }
}
```
- [ ] Run `composer dump-autoload`
- [ ] Verify autoload works by creating a test class in `src/Shared/` and resolving it

### 0.3 — Shared Kernel
- [ ] Create `src/Shared/Domain/AggregateRoot.php` — base class with domain event collection
- [ ] Create `src/Shared/Domain/ValueObject.php` — base abstract class
- [ ] Create `src/Shared/Domain/EntityId.php` — UUID wrapper value object
- [ ] Create `src/Shared/Domain/DomainException.php` — base domain exception
- [ ] Create `src/Shared/Application/CommandBus.php` — simple synchronous command bus
- [ ] Create `src/Shared/Application/QueryBus.php` — simple synchronous query bus
- [ ] Create `src/Shared/Infrastructure/BaseRepository.php` — Eloquent base repository

### 0.4 — Service Providers
- [ ] Create a service provider per module in `app/Providers/`:
  - `IdentityServiceProvider.php`
  - `SimulationServiceProvider.php`
  - `SimExecutionServiceProvider.php`
  - `EvalEngineServiceProvider.php`
  - `AIMediation ServiceProvider.php`
  - `ReportingServiceProvider.php`
- [ ] Register all providers in `bootstrap/providers.php`
- [ ] Each provider registers its own routes file and binds its repository interfaces

### 0.5 — Route Structure
- [ ] Create route files per module in `routes/`:
  - `routes/identity.php`
  - `routes/learn.php` (SimExecution)
  - `routes/admin.php`
  - `routes/reporting.php`
  - `routes/ai_mediation.php`
- [X] **Revised during Phase 1:** route files are NOT loaded from `AppServiceProvider::boot()`.
  Each module loads its own route file from its own service provider's `boot()`:
  ```php
  public function boot(): void
  {
      Route::middleware('web')->group(base_path('routes/identity.php'));
  }
  ```
  This was originally left as an empty `boot(): void {}` stub in both `IdentityServiceProvider`
  and `SimExecutionServiceProvider`, which meant `routes/identity.php` and `routes/learn.php`
  existed on disk but were never registered — every route in them 404'd. `bootstrap/app.php`
  only ever loads `routes/web.php`. **Rule going forward: a module that owns routes is
  responsible for loading them itself. No central route loader.**
- [X] `ModulesServiceProvider` (in `Src\Shared\Infrastructure\Providers`) is a **legacy** central
  loader kept only for modules that haven't migrated to self-loading routes yet (currently just
  `aimediation`). Do not add a module to it once that module's own provider loads its routes —
  doing so double-registers the routes under two different prefixes.

### 0.6 — Feature Flag Table
> This is done in Phase 0 because every subsequent phase depends on it.

- [ ] Create migration: `create_feature_flags_table`
```
feature_flags
  id              BIGINT UNSIGNED AUTO_INCREMENT PK
  flag_key        VARCHAR(200) UNIQUE NOT NULL     -- e.g. 'simulation.task_submission'
  description     TEXT NULL
  is_enabled      BOOLEAN NOT NULL DEFAULT FALSE
  module          VARCHAR(100) NOT NULL              -- e.g. 'Simulation'
  created_at      TIMESTAMP
  updated_at      TIMESTAMP
```
- [ ] Create `FeatureFlag` Eloquent model in `src/Shared/Infrastructure/`
- [ ] Create `FeatureFlagService` with `isEnabled(string $key): bool`
- [ ] Create Blade directive `@feature('flag.key')` / `@endfeature`
- [ ] Seed all flags as DISABLED — see **Appendix A: Feature Flag Registry** at end of this file
- [ ] Run `php artisan migrate` and confirm table exists

---

## PHASE 1 — Identity & Access

> Goal: Users can register, log in, and be assigned roles. Organisations exist. RBAC controls who can do what.

### 1.1 — Identity Migrations
- [ ] Create migration: `create_learners_table`
```
learners
  id                  UUID PK
  full_name           VARCHAR(200) NOT NULL
  email               VARCHAR(320) UNIQUE NOT NULL
  password            VARCHAR(255) NOT NULL
  entry_category      ENUM('inexperienced','experienced') NOT NULL
  years_experience    SMALLINT UNSIGNED NULL
  organisation_id     UUID NULL FK→organisations.id
  remember_token      VARCHAR(100) NULL
  created_at          TIMESTAMP
  updated_at          TIMESTAMP
  last_active_at      TIMESTAMP NULL
```
- [ ] Create migration: `create_organisations_table`
```
organisations
  id          UUID PK
  name        VARCHAR(300) NOT NULL
  slug        VARCHAR(100) UNIQUE NOT NULL
  is_active   BOOLEAN NOT NULL DEFAULT TRUE
  created_at  TIMESTAMP
  updated_at  TIMESTAMP
```
- [ ] Run migrations, verify tables

### 1.2 — Auth
- [ ] Scaffold auth routes manually (no Laravel Breeze — keep control):
  - `GET /register` → `RegistrationController@show`
  - `POST /register` → `RegistrationController@store`
  - `GET /login` → `LoginController@show`
  - `POST /login` → `LoginController@store`
  - `POST /logout` → `LoginController@destroy`
- [ ] Create `RegistrationController` in `src/Identity/Presentation/Controllers/`
- [ ] Create `LoginController`
- [ ] Create `StoreRegistrationRequest` — validates email, password, entry_category
- [ ] Use Laravel's built-in `Auth` facade with the `learners` table (update `config/auth.php` to use `learner` guard)
- [ ] Create registration Blade view
- [ ] Create login Blade view
- [ ] Test: register → login → logout flow works end-to-end

### 1.3 — RBAC
- [ ] Create migration: `create_roles_table`
```
roles
  id           BIGINT UNSIGNED AUTO_INCREMENT PK
  name         VARCHAR(100) UNIQUE NOT NULL  -- 'super_admin','org_admin','content_author','learner'
  description  TEXT NULL
```
- [ ] Create migration: `create_learner_role_table` (pivot)
```
learner_role
  learner_id   UUID FK→learners.id
  role_id      BIGINT UNSIGNED FK→roles.id
  PRIMARY KEY (learner_id, role_id)
```
- [ ] Seed four roles: `super_admin`, `org_admin`, `content_author`, `learner`
- [ ] Create `HasRoles` trait on Learner model: `hasRole(string $role): bool`
- [ ] Create Laravel Gate policies:
  - `AdminPolicy` — gates for content management routes
  - `LearnerPolicy` — gates for simulation routes
- [ ] Create `RoleMiddleware` — redirect if user doesn't have required role
- [ ] Register middleware alias `role` in `bootstrap/app.php`
- [ ] Apply `role:learner` middleware to learner routes, `role:content_author` to admin routes

### 1.4 — Organisation
- [ ] Create `OrganisationController` (admin only)
- [ ] Routes: `GET /admin/organisations`, `POST /admin/organisations`
- [ ] Basic Blade views: list and create form
- [ ] Feature flag: `organisations.multi_tenancy` — gate the org-scoping logic behind this flag; build the models but don't enforce org scoping until flag is enabled

---

## PHASE 2 — Core Content Schema

> Goal: Every content table exists in the database. No UI yet — just migrations in the right order. This is the most important phase for getting the foundation right.
>
> **Migration order matters.** Create tables in this exact sequence to avoid FK constraint errors.

### 2.1 — Competency Foundation
- [ ] Create migration: `create_competence_dimensions_table`
```
competence_dimensions
  id              VARCHAR(20) PK              -- 'dim_001' through 'dim_006'
  name            VARCHAR(200) NOT NULL
  short_label     VARCHAR(100) NOT NULL
  core_question   TEXT NOT NULL
  observable_indicators  JSON NOT NULL        -- array of strings
  sequence_order  TINYINT UNSIGNED NOT NULL
```
- [ ] Seed immediately — all 6 dimensions (these are static, never change):
  - `dim_001` Problem Analysis & Decomposition
  - `dim_002` Design & Architecture
  - `dim_003` Implementation & Coding
  - `dim_004` Testing & Quality Assurance
  - `dim_005` Debugging & Problem-Solving
  - `dim_006` Communication & Documentation
- [ ] Run migration + seeder, verify 6 rows exist

### 2.2 — Role Definitions
- [ ] Create migration: `create_role_definitions_table`
```
role_definitions
  id                    UUID PK
  title                 VARCHAR(200) NOT NULL
  specialization_tags   JSON NOT NULL         -- array of strings
  min_years_experience  TINYINT UNSIGNED NOT NULL DEFAULT 0
  dimension_weights     JSON NOT NULL         -- {dim_001: 0.2, ...}
  dimension_thresholds  JSON NOT NULL         -- {dim_001: 'basic', ...}
  is_lead_role          BOOLEAN NOT NULL DEFAULT FALSE
  created_at            TIMESTAMP
  updated_at            TIMESTAMP
```

### 2.3 — Project & Scenario Content
- [ ] Create migration: `create_concept_tags_table`
```
concept_tags
  id           UUID PK
  tag          VARCHAR(200) UNIQUE NOT NULL
  category     ENUM('se_concept','cs_fundamental','tool_technology','professional_practice','regulatory') NOT NULL
  description  TEXT NULL
  source       ENUM('manual','ai_recommended_accepted') NOT NULL DEFAULT 'manual'
  created_at   TIMESTAMP
  updated_at   TIMESTAMP
```
- [ ] Create migration: `create_project_templates_table`
```
project_templates
  id                      UUID PK
  title                   VARCHAR(300) NOT NULL
  tagline                 VARCHAR(500) NULL
  project_type            VARCHAR(100) NOT NULL
  business_domain         VARCHAR(200) NULL
  business_context        TEXT NOT NULL
  stakeholders            JSON NULL
  overarching_constraints JSON NULL
  tech_context            JSON NULL
  specialization_tags     JSON NOT NULL
  organisation_id         UUID NULL FK→organisations.id
  rubric_set_id           UUID NULL              -- FK added after rubric_sets created
  coding_guidelines       TEXT NULL
  velocity_estimate       JSON NULL
  difficulty_level        ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'intermediate'
  is_published            BOOLEAN NOT NULL DEFAULT FALSE
  is_active               BOOLEAN NOT NULL DEFAULT TRUE
  created_at              TIMESTAMP
  updated_at              TIMESTAMP
```
- [ ] Create migration: `create_scenario_templates_table`
```
scenario_templates
  id                      UUID PK
  project_id              UUID NOT NULL FK→project_templates.id
  sequence_order          SMALLINT UNSIGNED NOT NULL
  title                   VARCHAR(300) NOT NULL
  narrative_context       TEXT NOT NULL
  situation_trigger       TEXT NOT NULL
  situation_trigger_type  ENUM('slack_message','email','meeting_summary','incident_report','ticket','handover_note') NOT NULL
  learner_role_label      VARCHAR(200) NULL
  default_autonomy_level  ENUM('low','mid','high') NOT NULL DEFAULT 'mid'
  is_diagnostic           BOOLEAN NOT NULL DEFAULT FALSE
  is_published            BOOLEAN NOT NULL DEFAULT FALSE
  is_active               BOOLEAN NOT NULL DEFAULT TRUE
  created_at              TIMESTAMP
  updated_at              TIMESTAMP
  UNIQUE (project_id, sequence_order)
```
- [ ] Create migration: `create_scenario_reference_materials_table`
```
scenario_reference_materials
  id               UUID PK
  scenario_id      UUID NOT NULL FK→scenario_templates.id
  material_type    ENUM('document','email','slack_message','ticket','report','notes') NOT NULL
  title            VARCHAR(300) NOT NULL
  content          TEXT NOT NULL
  embedded_signals JSON NULL
  display_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0
  created_at       TIMESTAMP
  updated_at       TIMESTAMP
```
- [ ] Create migration: `create_artifact_vault_items_table`
```
artifact_vault_items
  id               UUID PK
  project_id       UUID NOT NULL FK→project_templates.id
  document_type    ENUM('business_context','prd','srs','sad','coding_guidelines','glossary','sprint_goal_template') NOT NULL
  title            VARCHAR(300) NOT NULL
  content          TEXT NOT NULL
  rank_gate        VARCHAR(20) NULL
  phase_gate       ENUM('pre_induction','post_induction','post_sprint_1','mid_session','advanced_only') NULL
  is_reference_doc BOOLEAN NOT NULL DEFAULT FALSE
  display_order    SMALLINT UNSIGNED NOT NULL DEFAULT 0
  created_at       TIMESTAMP
  updated_at       TIMESTAMP
```

### 2.4 — Task Content
- [ ] Create migration: `create_tasks_table`
```
tasks
  id                    UUID PK
  scenario_id           UUID NOT NULL FK→scenario_templates.id
  sequence_order        SMALLINT UNSIGNED NOT NULL
  title                 VARCHAR(300) NOT NULL
  task_brief            TEXT NOT NULL
  domain                VARCHAR(100) NOT NULL
  task_type             ENUM('core','consequence','suggestion','diagnostic_scenario','diagnostic_consequence') NOT NULL DEFAULT 'core'
  role_tags             JSON NOT NULL
  tools                 JSON NULL
  prerequisite_concepts JSON NULL
  is_cac_runtime_set    BOOLEAN NOT NULL DEFAULT TRUE
  fixed_complexity      ENUM('low','mid','high') NULL
  fixed_autonomy        ENUM('low','mid','high') NULL
  fixed_context_fidelity ENUM('low','mid','high') NULL
  consequence_task_ids  JSON NULL
  suggestion_task_ids   JSON NULL
  is_architectural      BOOLEAN NOT NULL DEFAULT FALSE
  planning_layer_active BOOLEAN NOT NULL DEFAULT FALSE
  code_execution_config JSON NULL
  model_response_summary TEXT NULL
  time_limit_minutes    INT UNSIGNED NULL
  is_published          BOOLEAN NOT NULL DEFAULT FALSE
  is_active             BOOLEAN NOT NULL DEFAULT TRUE
  created_at            TIMESTAMP
  updated_at            TIMESTAMP
  UNIQUE (scenario_id, sequence_order)
```
- [ ] Create migration: `create_task_expected_deliverables_table`
```
task_expected_deliverables
  id            UUID PK
  task_id       UUID NOT NULL FK→tasks.id
  type          ENUM('written_explanation','artifact','code','diagram','document') NOT NULL
  label         VARCHAR(200) NOT NULL
  description   TEXT NOT NULL
  is_required   BOOLEAN NOT NULL DEFAULT TRUE
  display_order TINYINT UNSIGNED NOT NULL DEFAULT 0
```
- [ ] Create migration: `create_task_cac_variants_table`
```
task_cac_variants
  id                    UUID PK
  task_id               UUID NOT NULL FK→tasks.id
  complexity_level      ENUM('low','mid','high') NOT NULL
  scenario_text         TEXT NOT NULL
  scaffolding_text_low  TEXT NULL
  scaffolding_text_mid  TEXT NULL
  scaffolding_text_high TEXT NULL
  context_text_low      TEXT NULL
  context_text_mid      TEXT NULL
  context_text_high     TEXT NULL
  UNIQUE (task_id, complexity_level)
```
- [ ] Create migration: `create_task_dependencies_table`
```
task_dependencies
  id                   UUID PK
  task_id              UUID NOT NULL FK→tasks.id
  prerequisite_task_id UUID NOT NULL FK→tasks.id
  UNIQUE (task_id, prerequisite_task_id)
```
- [ ] Create migration: `create_task_knowledge_anchors_table`
```
task_knowledge_anchors
  id                      UUID PK
  task_id                 UUID NOT NULL FK→tasks.id
  concept_id              UUID NOT NULL FK→concept_tags.id
  concept_name            VARCHAR(200) NOT NULL
  domain                  VARCHAR(200) NOT NULL
  application_expectation TEXT NOT NULL
  is_required             BOOLEAN NOT NULL DEFAULT FALSE
  remediation_hint        TEXT NOT NULL
```
- [ ] Create migration: `create_task_guidance_prompts_table`
```
task_guidance_prompts
  id                    UUID PK
  task_id               UUID NOT NULL FK→tasks.id
  trigger_dimension     VARCHAR(200) NOT NULL
  prompt_text           TEXT NOT NULL
  autonomy_level_filter ENUM('low','mid','high') NOT NULL
  delivery_mode         ENUM('proactive','reactive') NOT NULL
  display_order         TINYINT UNSIGNED NOT NULL DEFAULT 0
```

### 2.5 — Rubric & Criteria
- [ ] Create migration: `create_rubric_sets_table`
```
rubric_sets
  id          UUID PK
  project_id  UUID UNIQUE NOT NULL FK→project_templates.id
  version     VARCHAR(20) NOT NULL DEFAULT '1.0'
  created_at  TIMESTAMP
  updated_at  TIMESTAMP
```
- [ ] Create migration: `alter_project_templates_add_rubric_set_fk`
  - Add FK constraint `rubric_set_id → rubric_sets.id` (deferred because rubric_sets didn't exist when project_templates was created)
- [ ] Create migration: `create_rubric_criteria_table`
```
rubric_criteria
  id                        UUID PK
  rubric_set_id             UUID NOT NULL FK→rubric_sets.id
  task_id                   UUID NOT NULL FK→tasks.id
  task_dimension_label      VARCHAR(200) NOT NULL
  parent_dimension_id       VARCHAR(20) NOT NULL FK→competence_dimensions.id
  complexity_level          ENUM('low','mid','high') NOT NULL
  criterion_text            TEXT NOT NULL
  weight                    DECIMAL(4,3) NOT NULL              -- criterion weight within dimension
  dimension_weight          DECIMAL(4,3) NOT NULL              -- dimension weight within task (0–1, sums to 1 per task)
  claude_detection_hint     TEXT NOT NULL
  distinguished_description TEXT NOT NULL
  proficient_description    TEXT NOT NULL
  developing_description    TEXT NOT NULL
  beginning_description     TEXT NOT NULL
  is_architectural          BOOLEAN NOT NULL DEFAULT FALSE
  is_planning_layer         BOOLEAN NOT NULL DEFAULT FALSE
  reference_doc_anchor      TEXT NULL
```
- [ ] Create migration: `create_follow_up_prompt_templates_table`
```
follow_up_prompt_templates
  id                UUID PK
  domain            VARCHAR(100) NOT NULL
  prompt_text       TEXT NOT NULL
  trigger_condition TEXT NOT NULL
  is_anti_copy      BOOLEAN NOT NULL DEFAULT FALSE
```

### 2.6 — Backlog Templates
- [ ] Create migration: `create_backlog_item_templates_table`
```
backlog_item_templates
  id                  UUID PK
  project_id          UUID NOT NULL FK→project_templates.id
  title               VARCHAR(300) NOT NULL
  description         TEXT NOT NULL
  default_priority    ENUM('must_have','should_have','could_have','wont_have') NOT NULL
  role_tags           JSON NOT NULL
  task_id             UUID NULL FK→tasks.id
  dependency_item_ids JSON NULL
  display_order       SMALLINT UNSIGNED NOT NULL DEFAULT 0
```

### 2.7 — Run All Migrations
- [ ] `php artisan migrate:fresh` — all tables must create cleanly with no errors
- [ ] Open MySQL client and verify every table exists with correct columns
- [ ] Fix any migration errors before proceeding

---

## PHASE 3 — Eloquent Models

> Goal: Every table has an Eloquent model with correct relationships, casts, and fillable fields. No controllers yet.

### 3.1 — Shared / Identity Models
- [ ] `Learner` model (`src/Identity/Domain/Models/Learner.php`)
  - Relationships: `belongsTo(Organisation)`, `hasOne(LearnerProfile)`, `hasMany(RoleEnrolment)`, `belongsToMany(Role)`
  - Casts: `entry_category` → string enum, `last_active_at` → datetime
- [ ] `Organisation` model
- [ ] `Role` model (RBAC role)
- [ ] `RoleDefinition` model (`src/Competency/`)
  - Casts: `specialization_tags`, `dimension_weights`, `dimension_thresholds` → array

### 3.2 — Content Models
- [ ] `ProjectTemplate` model (`src/Simulation/Domain/Models/`)
  - Casts: `stakeholders`, `overarching_constraints`, `tech_context`, `specialization_tags`, `velocity_estimate` → array
  - Relationships: `hasMany(ScenarioTemplate)`, `hasOne(RubricSet)`, `hasMany(ArtifactVaultItem)`, `hasMany(BacklogItemTemplate)`
- [ ] `ScenarioTemplate` model
  - Relationships: `belongsTo(ProjectTemplate)`, `hasMany(Task)`, `hasMany(ScenarioReferenceMaterial)`
- [ ] `ScenarioReferenceMaterial` model
  - Casts: `embedded_signals` → array
- [ ] `ArtifactVaultItem` model
- [ ] `BacklogItemTemplate` model
  - Casts: `role_tags`, `dependency_item_ids` → array

### 3.3 — Task Models
- [ ] `Task` model
  - Casts: `role_tags`, `tools`, `prerequisite_concepts`, `consequence_task_ids`, `suggestion_task_ids`, `code_execution_config` → array
  - Relationships: `belongsTo(ScenarioTemplate)`, `hasMany(TaskExpectedDeliverable)`, `hasMany(TaskCacVariant)`, `hasMany(TaskKnowledgeAnchor)`, `hasMany(TaskGuidancePrompt)`, `hasMany(RubricCriterion)`, `hasMany(TaskDependency)`
- [ ] `TaskExpectedDeliverable` model
- [ ] `TaskCacVariant` model
- [ ] `TaskDependency` model
- [ ] `TaskKnowledgeAnchor` model
- [ ] `TaskGuidancePrompt` model

### 3.4 — Rubric Models
- [ ] `RubricSet` model
  - Relationships: `belongsTo(ProjectTemplate)`, `hasMany(RubricCriterion)`
- [ ] `RubricCriterion` model
  - Relationships: `belongsTo(RubricSet)`, `belongsTo(Task)`, `belongsTo(CompetenceDimension, 'parent_dimension_id', 'id')`
- [ ] `FollowUpPromptTemplate` model
- [ ] `CompetenceDimension` model
  - Casts: `observable_indicators` → array
- [ ] `ConceptTag` model
- [ ] `FeatureFlag` model

---

## PHASE 4 — Content Management (Admin UI)

> Goal: A super_admin or content_author can create and manage projects, scenarios, and tasks through a basic admin UI. This is how you load the MedQueue content.
>
> **Feature flag:** `admin.content_management` — enable before starting this phase.

### 4.1 — Admin Layout
- [ ] Create `resources/views/admin/layouts/admin.blade.php`
- [ ] Admin nav: Projects | Scenarios | Tasks | Roles | Feature Flags
- [ ] Apply `role:content_author` middleware to all `/admin/*` routes

### 4.2 — Feature Flag Admin
- [ ] `GET /admin/feature-flags` — list all flags with toggle
- [ ] `PATCH /admin/feature-flags/{key}` — toggle enabled/disabled (HTMX request, returns updated row partial)
- [ ] Blade partial: `_flag_row.blade.php` — single row with toggle switch using HTMX `hx-patch`

### 4.3 — Project Management
- [ ] `GET /admin/projects` — list all projects (title, type, status, published)
- [ ] `GET /admin/projects/create` — create form
- [ ] `POST /admin/projects` — store (validates required fields, creates ProjectTemplate + RubricSet in same transaction)
- [ ] `GET /admin/projects/{id}/edit` — edit form
- [ ] `PATCH /admin/projects/{id}` — update
- [ ] `PATCH /admin/projects/{id}/publish` — toggle is_published (HTMX)
- [ ] Create `StoreProjectRequest` with validation rules
- [ ] Create `ProjectService` in `src/Simulation/Application/` — wraps DB transaction for project + rubric_set creation

### 4.4 — Scenario Management
- [ ] `GET /admin/projects/{project}/scenarios` — list scenarios for project
- [ ] `GET /admin/projects/{project}/scenarios/create` — create form (pre-filled with project_id)
- [ ] `POST /admin/projects/{project}/scenarios` — store
- [ ] `GET /admin/scenarios/{id}/edit` — edit form
- [ ] `PATCH /admin/scenarios/{id}` — update
- [ ] Handle `sequence_order` — auto-assign next integer, allow drag-to-reorder later

### 4.5 — Task Management
- [ ] `GET /admin/scenarios/{scenario}/tasks` — list tasks in scenario
- [ ] `GET /admin/scenarios/{scenario}/tasks/create` — create form
- [ ] `POST /admin/scenarios/{scenario}/tasks` — store (creates Task + at least one TaskExpectedDeliverable)
- [ ] `GET /admin/tasks/{id}/edit` — full task editor (multi-section form)
- [ ] `PATCH /admin/tasks/{id}` — update
- [ ] Sub-forms (HTMX-powered, add/remove rows dynamically):
  - Expected Deliverables section
  - CAC Variants section (one per complexity level)
  - Knowledge Anchors section
  - Guidance Prompts section

### 4.6 — Rubric Criterion Management
- [ ] `GET /admin/tasks/{task}/criteria` — list criteria for task
- [ ] `POST /admin/tasks/{task}/criteria` — create new criterion
- [ ] `PATCH /admin/criteria/{id}` — update criterion
- [ ] `DELETE /admin/criteria/{id}` — delete criterion (HTMX, removes row from DOM)
- [ ] Each criterion form must include the `claude_detection_hint` field prominently — it is required

### 4.7 — Artifact Vault Item Management
- [ ] `GET /admin/projects/{project}/vault` — list vault items for project
- [ ] `POST /admin/projects/{project}/vault` — add vault item
- [ ] `PATCH /admin/vault/{id}` — update
- [ ] `DELETE /admin/vault/{id}` — delete

### 4.8 — Load MedQueue Sample Content
- [ ] Create database seeder: `MedQueueSeeder`
- [ ] Seed `proj_001` project template with all stakeholders, constraints, tech_context
- [ ] Seed `scen_001` scenario with narrative_context and situation_trigger
- [ ] Seed `task_001` with expected deliverables, knowledge anchors, guidance prompts
- [ ] Seed rubric criteria for task_001 (all three dimensions from the template)
- [ ] Seed artifact vault items (PRD, SRS stubs)
- [ ] Run `php artisan db:seed --class=MedQueueSeeder` and verify all records in DB

---

## PHASE 5 — Learner Profile & Session Tables

> Goal: All runtime tables exist. Learner profile is created on registration. Diagnostic flow is wired up.

### 5.1 — Profile & Rank Migrations
- [ ] Create migration: `create_learner_profiles_table`
```
learner_profiles
  id                   UUID PK
  learner_id           UUID UNIQUE NOT NULL FK→learners.id
  current_rank_tier    ENUM('Junior','Mid','Senior') NOT NULL DEFAULT 'Junior'
  current_rank_level   TINYINT UNSIGNED NOT NULL DEFAULT 1
  cac_complexity       ENUM('low','mid','high') NOT NULL DEFAULT 'low'
  cac_autonomy         ENUM('low','mid','high') NOT NULL DEFAULT 'mid'
  cac_context_fidelity ENUM('low','mid','high') NOT NULL DEFAULT 'low'
  mismatch_flag_active BOOLEAN NOT NULL DEFAULT FALSE
  failure_streak       TINYINT UNSIGNED NOT NULL DEFAULT 0
  updated_at           TIMESTAMP
```
- [ ] Create migration: `create_rank_events_table`
```
rank_events
  id                UUID PK
  learner_id        UUID NOT NULL FK→learners.id
  event_type        ENUM('initial_assignment','escalation','de_escalation','sub_level_progression','review_triggered') NOT NULL
  from_rank_tier    VARCHAR(20) NULL
  from_rank_level   TINYINT UNSIGNED NULL
  to_rank_tier      VARCHAR(20) NOT NULL
  to_rank_level     TINYINT UNSIGNED NOT NULL
  trigger_reason    TEXT NOT NULL
  source_session_id UUID NULL
  created_at        TIMESTAMP
```
- [ ] Create migration: `create_dimension_scores_table`
```
dimension_scores
  id              UUID PK
  learner_id      UUID NOT NULL FK→learners.id
  dimension_id    VARCHAR(20) NOT NULL FK→competence_dimensions.id
  tier            ENUM('untested','basic','intermediate','advanced') NOT NULL DEFAULT 'untested'
  evidence_count  SMALLINT UNSIGNED NOT NULL DEFAULT 0
  last_updated_at TIMESTAMP NULL
  UNIQUE (learner_id, dimension_id)
```
- [ ] Create migration: `create_concept_mastery_records_table`
```
concept_mastery_records
  id                UUID PK
  learner_id        UUID NOT NULL FK→learners.id
  concept_id        UUID NOT NULL FK→concept_tags.id
  status            ENUM('not_encountered','encountered','partially_met','mastered') NOT NULL DEFAULT 'not_encountered'
  tasks_encountered SMALLINT UNSIGNED NOT NULL DEFAULT 0
  tasks_met         SMALLINT UNSIGNED NOT NULL DEFAULT 0
  last_updated_at   TIMESTAMP NULL
  UNIQUE (learner_id, concept_id)
```

### 5.2 — Session & Enrolment Migrations
- [ ] Create migration: `create_role_enrolments_table`
```
role_enrolments
  id           UUID PK
  learner_id   UUID NOT NULL FK→learners.id
  role_id      UUID NOT NULL FK→role_definitions.id
  project_id   UUID NOT NULL FK→project_templates.id
  is_gated     BOOLEAN NOT NULL DEFAULT FALSE
  enrolled_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```
- [ ] Create migration: `create_learner_sessions_table`
```
learner_sessions
  id                     UUID PK
  learner_id             UUID NOT NULL FK→learners.id
  project_id             UUID NOT NULL FK→project_templates.id
  role_enrolment_id      UUID NOT NULL FK→role_enrolments.id
  status                 ENUM('diagnostic','active','complete','suspended') NOT NULL DEFAULT 'active'
  current_scenario_id    UUID NULL FK→scenario_templates.id
  current_task_id        UUID NULL FK→tasks.id
  current_sprint_id      UUID NULL
  induction_completed_at TIMESTAMP NULL
  started_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  completed_at           TIMESTAMP NULL
```
- [ ] Create migration: `create_diagnostic_sessions_table`
```
diagnostic_sessions
  id                  UUID PK
  learner_id          UUID NOT NULL FK→learners.id
  pathway             ENUM('interview','assessment_tasks','diagnostic_scenario') NOT NULL
  status              ENUM('in_progress','complete','abandoned') NOT NULL DEFAULT 'in_progress'
  assigned_rank_tier  VARCHAR(20) NULL
  assigned_rank_level TINYINT UNSIGNED NULL
  started_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  completed_at        TIMESTAMP NULL
```

### 5.3 — Profile Observers
- [ ] Create `LearnerObserver` in `src/Identity/`
- [ ] On `Learner::created`: create `LearnerProfile` with default Junior-1 values, create 6 `DimensionScore` rows (one per dimension, all `untested`)
- [ ] Register observer in `IdentityServiceProvider`
- [ ] Test: register new learner → verify learner_profile row and 6 dimension_score rows created automatically

### 5.4 — Project Catalogue & Enrolment
> Feature flag: `simulation.project_catalogue`

- [ ] `GET /learn` — project catalogue (lists published, active projects)
- [ ] `GET /learn/{project}` — project detail page (business context, difficulty, role options)
- [ ] `POST /learn/{project}/enrol` — select role and enrol; validates experience gate; creates `RoleEnrolment` + `LearnerSession`; redirects to induction
- [ ] Create `EnrolmentService` in `src/SimExecution/Application/`
  - Validates role min_years_experience against learner's years_experience
  - Creates RoleEnrolment + LearnerSession in transaction
- [ ] Create enrolment Blade views

### 5.5 — Diagnostic Flow
> Feature flag: `simulation.diagnostic_assessment`
> For MVP, implement `diagnostic_scenario` pathway only (the other two — interview and assessment_tasks — are behind their own flags).

- [ ] `GET /learn/diagnostic` — diagnostic entry point (creates DiagnosticSession)
- [ ] Diagnostic uses the first scenario in the project where `is_diagnostic = true`
- [ ] After diagnostic: DiagnosticSession completed → `RankAssignmentService` reads evaluation results → writes initial rank to LearnerProfile → creates `RankEvent` (event_type: `initial_assignment`)
- [ ] Create `RankAssignmentService` in `src/EvalEngine/Application/`

---

## PHASE 6 — SimExecution: Sprint & Backlog

> Goal: The Sprint Manager and Virtual Backlog are functional. Learners can plan sprints, move items, and progress through scenarios.

### 6.1 — Sprint & Backlog Migrations
- [ ] Create migration: `create_learner_sprints_table`
```
learner_sprints
  id                    UUID PK
  learner_session_id    UUID NOT NULL FK→learner_sessions.id
  learner_id            UUID NOT NULL FK→learners.id
  project_id            UUID NOT NULL FK→project_templates.id
  sprint_number         SMALLINT UNSIGNED NOT NULL
  sprint_goal           TEXT NULL
  sprint_goal_source    ENUM('system_defined','learner_defined','learner_completed_template') NOT NULL DEFAULT 'system_defined'
  status                ENUM('planning','active','submitted','evaluated') NOT NULL DEFAULT 'planning'
  scope_warning_issued  BOOLEAN NOT NULL DEFAULT FALSE
  started_at            TIMESTAMP NULL
  submitted_at          TIMESTAMP NULL
  UNIQUE (learner_session_id, sprint_number)
```
- [ ] Create migration: `create_learner_backlog_items_table`
```
learner_backlog_items
  id                 UUID PK
  learner_session_id UUID NOT NULL FK→learner_sessions.id
  learner_id         UUID NOT NULL FK→learners.id
  template_item_id   UUID NOT NULL FK→backlog_item_templates.id
  sprint_id          UUID NULL FK→learner_sprints.id
  status             ENUM('backlog','in_sprint','in_progress','done','blocked') NOT NULL DEFAULT 'backlog'
  priority           ENUM('must_have','should_have','could_have','wont_have') NOT NULL
  moved_to_sprint_at TIMESTAMP NULL
  completed_at       TIMESTAMP NULL
  learner_notes      TEXT NULL
  is_injected        BOOLEAN NOT NULL DEFAULT FALSE
  injected_card_id   UUID NULL
```
- [ ] Create migration: `create_sprint_board_events_table`
```
sprint_board_events
  id          UUID PK
  learner_id  UUID NOT NULL FK→learners.id
  session_id  UUID NOT NULL FK→learner_sessions.id
  sprint_id   UUID NULL FK→learner_sprints.id
  item_id     UUID NULL FK→learner_backlog_items.id
  event_type  ENUM('item_moved_to_sprint','item_status_changed','priority_changed','sprint_goal_written','sprint_confirmed','sprint_submitted') NOT NULL
  from_status VARCHAR(50) NULL
  to_status   VARCHAR(50) NULL
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```
- [ ] Add FK: `alter_learner_sessions_add_current_sprint_fk` — add FK constraint for `current_sprint_id → learner_sprints.id`

### 6.2 — Injected Task Cards Migration
- [ ] Create migration: `create_injected_task_cards_table`
```
injected_task_cards
  id                    UUID PK
  learner_session_id    UUID NOT NULL FK→learner_sessions.id
  learner_id            UUID NOT NULL FK→learners.id
  card_type             ENUM('consequence','suggestion','diagnostic_consequence') NOT NULL
  source_task_id        UUID NOT NULL FK→tasks.id
  injected_task_id      UUID NULL FK→tasks.id
  generated_task_content JSON NULL
  target_sprint_id      UUID NULL FK→learner_sprints.id
  visual_treatment      ENUM('consequence_amber','suggestion_teal','diagnostic_deep_blue') NOT NULL
  status                ENUM('pending','in_progress','submitted','skipped') NOT NULL DEFAULT 'pending'
  suggestion_type       ENUM('corrective','extensional') NULL
  injected_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  resolved_at           TIMESTAMP NULL
```

### 6.3 — Session Onboarding Flow
> Feature flag: `simulation.session_onboarding`

- [ ] `GET /learn/sessions/{session}` — session dashboard (current sprint, task card, progress)
- [ ] `GET /learn/sessions/{session}/induction` — induction view (shows PRD, SRS from Artifact Vault)
- [ ] `PATCH /learn/sessions/{session}/induction/complete` — marks induction done (HTMX, unlocks scenario onboarding)
- [ ] `GET /learn/sessions/{session}/scenario-briefing` — scenario onboarding view
  - Shows `narrative_context` as briefing panel
  - Shows `situation_trigger` formatted per `situation_trigger_type` (email/Slack/etc.)
  - Shows `scenario_reference_materials` as collapsible docs
- [ ] `POST /learn/sessions/{session}/scenario-briefing/acknowledge` — marks situation trigger acknowledged, unlocks sprint planning

### 6.4 — Sprint Planning
> Feature flag: `simulation.sprint_planning`

- [ ] `GET /learn/sessions/{session}/sprint/plan` — sprint planning view
  - Shows backlog (filtered by role_tags and learner rank)
  - Shows sprint goal field (pre-filled, blank, or template depending on rank — see IS2 §15)
  - Drag/drop or click-to-add items to sprint (HTMX `hx-post` on item click)
- [ ] `POST /learn/sessions/{session}/sprint/items/{item}/move` — move item to sprint (HTMX)
  - Creates `SprintBoardEvent` (item_moved_to_sprint)
  - Updates `LearnerBacklogItem.sprint_id` and `status`
- [ ] `PATCH /learn/sessions/{session}/sprint/goal` — save sprint goal (HTMX)
  - Creates `SprintBoardEvent` (sprint_goal_written)
- [ ] `POST /learn/sessions/{session}/sprint/confirm` — confirm sprint scope
  - Validates scope against `velocity_estimate` — if over threshold, returns HTMX partial with warning (non-blocking)
  - Creates `LearnerSprint` with `status = active`
  - Creates `SprintBoardEvent` (sprint_confirmed)
  - Initialises `LearnerBacklogItem` rows from `BacklogItemTemplate` records if not already created
- [ ] Create `SprintService` in `src/SimExecution/Application/` — owns all sprint state transitions

### 6.5 — Sprint Board (Task Execution View)
> Feature flag: `simulation.sprint_board`

- [ ] `GET /learn/sessions/{session}/sprint/board` — main sprint board view
  - Kanban columns: Backlog | In Sprint | In Progress | Done
  - Cards: core tasks (white/blue), consequence tasks (amber), suggestion tasks (teal), diagnostic tasks (deep blue)
  - Current task card shows the task_brief, expected_deliverables, and Artifact Vault button
- [ ] `PATCH /learn/sessions/{session}/sprint/items/{item}/status` — update item status (HTMX)
  - Creates `SprintBoardEvent` (item_status_changed)
- [ ] `GET /learn/sessions/{session}/vault/{item}` — Artifact Vault document viewer (HTMX modal/drawer)
  - Enforces rank_gate and phase_gate before showing content
- [ ] `GET /learn/sessions/{session}/references` — scenario reference materials panel (collapsible drawer)

---

## PHASE 7 — Submission System

> Goal: Learners can submit work. All four layers are captured. Code execution runs via Docker (behind feature flag for MVP).

### 7.1 — Submission Migrations
- [ ] Create migration: `create_submission_packages_table`
```
submission_packages
  id                     UUID PK
  learner_session_id     UUID NOT NULL FK→learner_sessions.id
  learner_id             UUID NOT NULL FK→learners.id
  task_id                UUID NOT NULL FK→tasks.id
  scenario_id            UUID NOT NULL FK→scenario_templates.id
  sprint_id              UUID NOT NULL FK→learner_sprints.id
  attempt_number         TINYINT UNSIGNED NOT NULL DEFAULT 1
  layer1_text            TEXT NULL
  layer2_artifact_ids    JSON NULL
  layer3_code            TEXT NULL
  layer3_execution_result JSON NULL
  layer4_planning_snapshot JSON NULL
  cac_complexity_at_sub  ENUM('low','mid','high') NOT NULL
  cac_autonomy_at_sub    ENUM('low','mid','high') NOT NULL
  cac_context_at_sub     ENUM('low','mid','high') NOT NULL
  rank_at_submission     VARCHAR(20) NOT NULL
  submitted_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```
- [ ] Create migration: `create_submission_artifacts_table`
```
submission_artifacts
  id            UUID PK
  submission_id UUID NOT NULL FK→submission_packages.id
  filename      VARCHAR(300) NOT NULL
  artifact_type ENUM('diagram','document','notes','other') NOT NULL
  storage_path  TEXT NOT NULL
  uploaded_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```

### 7.2 — Submission Form
> Feature flag: `simulation.task_submission`

- [ ] `GET /learn/sessions/{session}/tasks/{task}/submit` — submission form view
  - Renders only the input fields for this task's `task_expected_deliverables`
  - `written_explanation` → textarea
  - `artifact/diagram/document` → file upload
  - `code` → code textarea (Monaco or simple `<textarea>`)
  - Planning Layer: no UI — captured passively on submit
- [ ] `POST /learn/sessions/{session}/tasks/{task}/submit` — handle submission
  - Validates that all `is_required = true` deliverables are present
  - Assembles Planning Snapshot (queries LearnerSprint + LearnerBacklogItem for current sprint state)
  - Stores `SubmissionPackage` record
  - Stores `SubmissionArtifact` records for any uploaded files
  - Dispatches `SubmissionReceived` event (picked up by EvalEngine in Phase 8)
  - Returns HTMX partial: "Submission received — evaluation in progress"
- [ ] Create `SubmissionService` in `src/Submission/Application/`
- [ ] Create `PlanningSnapshotAssembler` — reads sprint/backlog tables and returns structured array

### 7.3 — Code Execution (Behind Feature Flag)
> Feature flag: `submission.code_execution` — **leave DISABLED for MVP**

- [ ] Create `DockerExecutionService` in `src/Submission/Infrastructure/Services/`
- [ ] Uses `code_execution_config` from task: `{language, entry_point, test_cases, docker_image}`
- [ ] Runs code in isolated Docker container, captures stdout/stderr/test results
- [ ] Returns `layer3_execution_result` JSON
- [ ] When flag is disabled: store code text only, skip execution, set `layer3_execution_result = null`

---

## PHASE 8 — Evaluation Engine

> Goal: Submitted work is evaluated by Claude. Evaluation results are stored. Performance levels are assigned.

### 8.1 — Evaluation Migrations
- [ ] Create migration: `create_evaluation_results_table`
```
evaluation_results
  id                  UUID PK
  submission_id       UUID UNIQUE NOT NULL FK→submission_packages.id
  learner_id          UUID NOT NULL FK→learners.id
  overall_tier        ENUM('beginning','developing','proficient','distinguished') NOT NULL
  passes_threshold    BOOLEAN NOT NULL
  gap_type            ENUM('knowledge_gap','strategy_gap') NULL
  is_uncertain        BOOLEAN NOT NULL DEFAULT FALSE
  follow_up_prompt_id UUID NULL FK→follow_up_prompt_templates.id
  evaluated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```
- [ ] Create migration: `create_dimension_evaluations_table`
```
dimension_evaluations
  id                   UUID PK
  evaluation_id        UUID NOT NULL FK→evaluation_results.id
  dimension_id         VARCHAR(20) NOT NULL FK→competence_dimensions.id
  task_dimension_label VARCHAR(200) NOT NULL
  tier_achieved        ENUM('beginning','developing','proficient','distinguished') NOT NULL
  criteria_met         JSON NOT NULL
  criteria_missed      JSON NOT NULL
  layer_scores         JSON NOT NULL
  evaluator_notes      TEXT NULL
```

### 8.2 — AIMediation Audit Migrations
- [ ] Create migration: `create_aimediation_events_table`
```
aimediation_events
  id                UUID PK
  learner_id        UUID NOT NULL FK→learners.id
  session_id        UUID NOT NULL FK→learner_sessions.id
  trigger_type      ENUM('submission_received','failure_detected','uncertain_evaluation','mismatch_detected','habit_detected','dimension_weakness','failure_threshold_exceeded','scenario_complete','knowledge_anchor_partial') NOT NULL
  trigger_source_id UUID NULL
  action_taken      ENUM('task_completed','consequence_injected','suggestion_queued','follow_up_prompt_issued','rank_review_triggered','mismatch_flagged','human_review_queued','dimension_targeted_next_scenario','knowledge_anchor_hint_issued','no_action') NOT NULL
  action_detail     JSON NULL
  is_deterministic  BOOLEAN NOT NULL DEFAULT TRUE
  confidence_score  DECIMAL(4,3) NULL
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```
- [ ] Create migration: `create_human_review_queue_table`
```
human_review_queue
  id               UUID PK
  submission_id    UUID NOT NULL FK→submission_packages.id
  evaluation_id    UUID NOT NULL FK→evaluation_results.id
  learner_id       UUID NOT NULL FK→learners.id
  status           ENUM('pending','in_review','resolved') NOT NULL DEFAULT 'pending'
  reviewer_id      UUID NULL
  reviewer_decision ENUM('proficient','not_proficient','escalate') NULL
  reviewer_notes   TEXT NULL
  queued_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  resolved_at      TIMESTAMP NULL
```
- [ ] Create migration: `create_concept_tag_recommendations_table`
```
concept_tag_recommendations
  id                  UUID PK
  source_submission_id UUID NOT NULL FK→submission_packages.id
  task_id             UUID NOT NULL FK→tasks.id
  recommendation_type ENUM('new_concept_tag','update_performance_levels','update_criteria') NOT NULL
  proposed_content    JSON NOT NULL
  status              ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending'
  curator_id          UUID NULL
  curator_notes       TEXT NULL
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  resolved_at         TIMESTAMP NULL
```

### 8.3 — Claude API Integration
> Feature flag: `aimediation.claude_evaluation` — enable when ready to test

- [ ] Add `ANTHROPIC_API_KEY` to `.env`
- [ ] Create `ClaudeApiClient` in `src/AIMediation/Infrastructure/Services/`
  - Uses `claude-sonnet-4-20250514`
  - `evaluate(array $submissionPackage): array` — sends prompt, returns structured response
- [ ] Create `EvaluationPromptBuilder` in `src/AIMediation/Application/`
  - Builds the full evaluation prompt from:
    - Submission layers (text, artifacts, code, planning snapshot)
    - Rubric criteria with `claude_detection_hint` fields
    - Reference document anchors
    - Scenario reference material embedded_signals
    - `model_response_summary`
    - Knowledge anchor `application_expectation` fields
  - Requests JSON response: `{dimensions: [{dimension_id, task_dimension_label, tier_achieved, criteria_met, criteria_missed, evaluator_notes}], overall_tier, gap_type, is_uncertain, knowledge_anchors_detected}`
- [ ] Create `EvaluationResultParser` — parses Claude's JSON response into domain objects
- [ ] Create `EvaluationService` in `src/EvalEngine/Application/`
  - Listens for `SubmissionReceived` event
  - Calls `EvaluationPromptBuilder` → `ClaudeApiClient` → `EvaluationResultParser`
  - Stores `EvaluationResult` + `DimensionEvaluation` rows
  - Logs `AimediationEvent`
  - Dispatches `EvaluationComplete` event

### 8.4 — Post-Evaluation Actions
- [ ] Create `PostEvaluationRouter` in `src/EvalEngine/Application/`
  - Listens for `EvaluationComplete` event
  - If `passes_threshold = true`: mark task done, unlock next task, update dimension_scores
  - If `passes_threshold = false`: call `ConsequenceTaskInjector`
  - If `is_uncertain = true`: create `HumanReviewQueue` entry, issue follow-up prompt
  - Update `LearnerProfile.failure_streak`
  - Check if failure_streak exceeds threshold → trigger rank review
- [ ] Create `DimensionScoreUpdater` — updates dimension_scores based on evaluation tiers
- [ ] Create `ConceptMasteryUpdater` — updates concept_mastery_records based on knowledge_anchors_detected

---

## PHASE 9 — Adaptive Engine

> Goal: Consequence tasks are injected, gap flags are recorded, habit flags are raised, ranks change.

### 9.1 — Gap & Habit Migrations
- [ ] Create migration: `create_gap_flags_table`
```
gap_flags
  id                UUID PK
  learner_id        UUID NOT NULL FK→learners.id
  dimension_id      VARCHAR(20) NOT NULL FK→competence_dimensions.id
  sub_criterion_id  UUID NULL FK→rubric_criteria.id
  source_task_id    UUID NOT NULL FK→tasks.id
  source_session_id UUID NOT NULL FK→learner_sessions.id
  is_resolved       BOOLEAN NOT NULL DEFAULT FALSE
  resolved_at       TIMESTAMP NULL
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```
- [ ] Create migration: `create_habit_flags_table`
```
habit_flags
  id                  UUID PK
  learner_id          UUID NOT NULL FK→learners.id
  habit_description   TEXT NOT NULL
  first_observed_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  observation_count   TINYINT UNSIGNED NOT NULL DEFAULT 1
  suggestion_task_id  UUID NULL FK→injected_task_cards.id
  is_resolved         BOOLEAN NOT NULL DEFAULT FALSE
```
- [ ] Create migration: `create_mismatch_flags_table`
```
mismatch_flags
  id                UUID PK
  learner_id        UUID NOT NULL FK→learners.id
  mismatch_type     ENUM('underestimation','overestimation') NOT NULL
  detected_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
  prompt_issued     BOOLEAN NOT NULL DEFAULT FALSE
  learner_response  ENUM('accepted_escalation','declined_escalation') NULL
  resolution        ENUM('auto_escalated','gradual_escalation','rank_review_triggered','unresolved') NULL
  resolved_at       TIMESTAMP NULL
```

### 9.2 — Consequence Task Injection
> Feature flag: `adaptive.consequence_tasks`

- [ ] Create `ConsequenceTaskInjector` in `src/EvalEngine/Application/`
  - Called by `PostEvaluationRouter` on failed submissions
  - Selects appropriate consequence task from `task.consequence_task_ids`
  - Selects gap-type-appropriate variant (knowledge_gap → conceptual task; strategy_gap → process task)
  - Creates `InjectedTaskCard` (card_type: consequence, visual_treatment: consequence_amber)
  - Creates `LearnerBacklogItem` (is_injected: true)
  - Creates `GapFlag` for the failing dimension
  - Logs `AimediationEvent` (action_taken: consequence_injected)

### 9.3 — Suggestion Task Injection
> Feature flag: `adaptive.suggestion_tasks`

- [ ] Create `HabitPatternDetector` in `src/AIMediation/Application/`
  - Called after each evaluation
  - Queries recent `DimensionEvaluation` records for the learner
  - Looks for patterns: same dimension below proficient 3+ times
  - When pattern detected: creates/increments `HabitFlag`
  - Queues suggestion task at next scenario transition
- [ ] Create `SuggestionTaskInjector` — injects suggestion at scenario start
  - Creates `InjectedTaskCard` (suggestion_teal)
  - Creates `LearnerBacklogItem` (is_injected: true)

### 9.4 — Rank Management
> Feature flag: `adaptive.rank_management`

- [ ] Create `RankEscalationService` in `src/EvalEngine/Application/`
  - Called after each evaluation
  - Checks consecutive strong submissions → escalate sub-level or tier
  - Checks `failure_streak` against threshold → trigger rank review
  - Creates `RankEvent` for every change
  - Updates `LearnerProfile.current_rank_tier` / `current_rank_level`
- [ ] Create `MismatchDetector`
  - After each evaluation: check if submission quality significantly exceeds current rank
  - If yes: create `MismatchFlag` and optionally surface escalation prompt to learner

### 9.5 — Scenario Transition
> Feature flag: `adaptive.scenario_transitions`

- [ ] Create `ScenarioTransitionService` in `src/SimExecution/Application/`
  - Called when all tasks in a scenario are attempted
  - Marks current scenario complete
  - Checks for queued suggestion tasks — injects them into next sprint
  - Checks for dimension weakness patterns across session → flags next scenario for targeting
  - Loads next scenario in `sequence_order`
  - Creates new `LearnerSprint` for next scenario
  - Updates `LearnerSession.current_scenario_id`

---

## PHASE 10 — Reporting & Profile

> Goal: Learners see their competency profile. Role qualification is assessed.

### 10.1 — Profile Dashboard
> Feature flag: `reporting.learner_profile`

- [ ] `GET /learn/profile` — learner profile page
  - Current rank (tier + level)
  - Radar/web chart: 6 dimensions, tier achieved per dimension
  - CAC trajectory
  - Concept mastery list
  - Open gap flags
  - Session history
- [ ] Build radar chart using SVG (no JS charting library needed for basic version — HTMX refresh)
- [ ] `GET /learn/profile/sessions/{session}` — individual session summary

### 10.2 — Role Qualification Report
> Feature flag: `reporting.qualification_report`

- [ ] Create `QualificationAssessor` in `src/Reporting/Application/`
  - Loads all `role_definitions`
  - For each role: compares `dimension_thresholds` against learner's `dimension_scores`
  - Returns: qualified roles, unqualified roles with reasons
- [ ] `GET /learn/profile/qualification` — qualification report view
  - Roles you qualify for (with qualifying dimensions)
  - Roles you don't qualify for (with specific missing criteria)
  - Recommended next focus areas

### 10.3 — Admin Reporting
> Feature flag: `reporting.admin_analytics`

- [ ] `GET /admin/reporting/learners` — learner progress overview (org admin)
- [ ] `GET /admin/reporting/tasks` — task performance (which tasks have high failure rates)
- [ ] `GET /admin/content-recommendations` — list pending `ConceptTagRecommendation` records for curator review

---

## PHASE 11 — Polish & Launch Prep

### 11.1 — Feature Flag Audit
- [ ] Review all flags — enable what is complete, disable what is not
- [ ] Add flag descriptions to all flags in the database
- [ ] Document which flags to enable for a minimal demo vs full experience

### 11.2 — Validation & Error Handling
- [ ] All form requests have complete validation rules
- [ ] All 422/403/404 errors return user-friendly Blade views or HTMX partials
- [ ] Claude API errors are caught, logged, and surface a "review pending" state to the learner (not a crash)

### 11.3 — Performance
- [ ] Add database indexes on all FK columns and frequently-queried fields:
  - `learner_backlog_items.learner_session_id`
  - `submission_packages.learner_id`
  - `dimension_scores.(learner_id, dimension_id)`
  - `evaluation_results.submission_id`
- [ ] Add eager loading to all N+1-prone relationships (check with Laravel Debugbar)
- [ ] Add query caching where appropriate (feature flags especially — cache for 60s)

### 11.4 — Security
- [ ] All routes have appropriate auth middleware
- [ ] All form submissions have CSRF protection (Laravel default)
- [ ] File uploads: validate MIME type and max size
- [ ] Claude API key is only ever read server-side — never exposed to frontend
- [ ] Learners cannot access other learners' sessions (scope all session queries by `learner_id`)

### 11.5 — Testing
- [ ] Feature tests for: registration, enrolment, submission, evaluation routing
- [ ] Unit tests for: `PlanningSnapshotAssembler`, `RankEscalationService`, `QualificationAssessor`
- [ ] Run `php artisan test` — all passing before launch

---

## Appendix A — Feature Flag Registry

Seed all of these as `is_enabled = FALSE` in Phase 0. Enable them as you complete each phase.

| Flag Key | Module | Enable At |
|----------|--------|-----------|
| `admin.content_management` | Content | Phase 4 start |
| `organisations.multi_tenancy` | Organizations | When needed |
| `simulation.project_catalogue` | SimExecution | Phase 5.4 |
| `simulation.diagnostic_assessment` | SimExecution | Phase 5.5 |
| `simulation.session_onboarding` | SimExecution | Phase 6.3 |
| `simulation.sprint_planning` | SimExecution | Phase 6.4 |
| `simulation.sprint_board` | SimExecution | Phase 6.5 |
| `simulation.task_submission` | Submission | Phase 7.2 |
| `submission.code_execution` | Submission | After Phase 11 |
| `aimediation.claude_evaluation` | AIMediation | Phase 8.3 |
| `adaptive.consequence_tasks` | EvalEngine | Phase 9.2 |
| `adaptive.suggestion_tasks` | EvalEngine | Phase 9.3 |
| `adaptive.rank_management` | EvalEngine | Phase 9.4 |
| `adaptive.scenario_transitions` | EvalEngine | Phase 9.5 |
| `reporting.learner_profile` | Reporting | Phase 10.1 |
| `reporting.qualification_report` | Reporting | Phase 10.2 |
| `reporting.admin_analytics` | Reporting | Phase 10.3 |

---

## Appendix B — Migration Execution Order

Run migrations in this exact order (within `php artisan migrate` Laravel handles ordering by timestamp, so name your files with this sequence in mind):

```
001_create_feature_flags_table
002_create_organisations_table
003_create_learners_table
004_create_roles_table
005_create_learner_role_table
006_create_competence_dimensions_table
007_create_role_definitions_table
008_create_concept_tags_table
009_create_project_templates_table
010_create_scenario_templates_table
011_create_scenario_reference_materials_table
012_create_artifact_vault_items_table
013_create_backlog_item_templates_table
014_create_tasks_table
015_create_task_expected_deliverables_table
016_create_task_cac_variants_table
017_create_task_dependencies_table
018_create_task_knowledge_anchors_table
019_create_task_guidance_prompts_table
020_create_rubric_sets_table
021_alter_project_templates_add_rubric_set_fk
022_create_rubric_criteria_table
023_create_follow_up_prompt_templates_table
024_create_role_enrolments_table
025_create_learner_sessions_table
026_create_diagnostic_sessions_table
027_create_learner_profiles_table
028_create_rank_events_table
029_create_dimension_scores_table
030_create_concept_mastery_records_table
031_create_learner_sprints_table
032_create_learner_backlog_items_table
033_create_sprint_board_events_table
034_alter_learner_sessions_add_current_sprint_fk
035_create_injected_task_cards_table
036_create_submission_packages_table
037_create_submission_artifacts_table
038_create_evaluation_results_table
039_create_dimension_evaluations_table
040_create_aimediation_events_table
041_create_human_review_queue_table
042_create_concept_tag_recommendations_table
043_create_gap_flags_table
044_create_habit_flags_table
045_create_mismatch_flags_table
```

> **Why `alter_project_templates_add_rubric_set_fk` is separate:** `project_templates` references `rubric_sets` but `rubric_sets` also references `project_templates`. You cannot create both FKs at the same time. Solution: create `project_templates` without the FK, create `rubric_sets` with its FK to `project_templates`, then use an `ALTER TABLE` migration to add the reverse FK.

---

## Appendix C — Recommended Build Verification Points

After each of these milestones, stop and verify the full flow works before continuing:

| Milestone | Verification |
|-----------|-------------|
| End of Phase 0 | `php artisan serve` runs, feature flag table exists, a flag can be toggled |
| End of Phase 1 | Register → Login → Logout works, roles are seeded, RBAC middleware blocks unauthorised routes |
| End of Phase 2 | `php artisan migrate:fresh` creates all 45 tables with no errors |
| End of Phase 3 | All models can be resolved via Eloquent, relationships return correct types |
| End of Phase 4 | MedQueueSeeder loads all content, admin can view project/scenario/task in UI |
| End of Phase 5 | Learner registers → profile + 6 dimension scores created → enrols in MedQueue → session created |
| End of Phase 6 | Learner can read briefing → acknowledge trigger → plan sprint → move items → view sprint board |
| End of Phase 7 | Learner can submit a written_explanation task → SubmissionPackage record created with all 4 layers |
| End of Phase 8 | Claude evaluates the submission → EvaluationResult + DimensionEvaluation rows created |
| End of Phase 9 | Failed submission → consequence card appears on sprint board → gap flag created |
| End of Phase 10 | Learner profile page shows radar chart with dimension scores |
