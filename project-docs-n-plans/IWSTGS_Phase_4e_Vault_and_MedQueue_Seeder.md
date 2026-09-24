# IWSTGS — Phase 4e: Artifact Vault + the MedQueue Seeder

> **Goal:** finish the authoring layer with **artifact-vault** CRUD, then write the **`MedQueueSeeder`** that fills the empty admin with real content — the six canonical competence dimensions, a role definition, and a complete MedQueue project → scenario → task → CAC variants → guidance prompt → deliverables → rubric criteria → vault items. This is the payoff: after 4e, the CRUD you built across 4a–4d actually *has something in it*, the dimension dropdown fills up, and you can click through a real simulation blueprint.

---

## 0. Prerequisites

- [ ] The bus fix (4a §1.1) — still the standing blocker for dispatch.
- [ ] 4a–4d in place. The seeder inserts through Eloquent models, so it needs the Phase-3 models present (they are).

---

## 1. Artifact-Vault Authoring (the leaf pattern again)

Vault items are project reference documents (PRD, SRS, architecture doc, glossary…), rank- and phase-gated. Like 4d's rubric criterion, an item is a **leaf aggregate** — it references a project by ID and owns nothing — so this is the exact 4d shape with different fields. I'll specify the deltas rather than repeat the whole layering.

**Enums** (create as backed enums, matching the DB exactly):
```php
// src/Simulation/Domain/Vault/DocumentType.php  → business_context, prd, srs, sad, coding_guidelines, glossary, sprint_goal_template
// src/Simulation/Domain/Vault/PhaseGate.php      → pre_induction, post_induction, post_sprint_1, mid_session, advanced_only
```
Activate casts on `ArtifactVaultItemModel`: `'document_type' => DocumentType::class`, `'phase_gate' => PhaseGate::class` (nullable), `'is_reference_doc' => 'boolean'`, `'display_order' => 'integer'`.

**Aggregate** — `ArtifactVaultItem` with fields `id, projectId, documentType (DocumentType), title, content, rankGate (?string), phaseGate (?PhaseGate), isReferenceDoc (bool), displayOrder (int)`; `create`/`reconstitute`/`toPrimitives`. **Repository** — `save` (plain `updateOrCreate`, no children), `findById`, `findByProject`, `remove`. **Mapper**, binding in `SimulationServiceProvider::register()`.

**Application** — `AddVaultItem` / `UpdateVaultItem` / `RemoveVaultItem` commands + handlers; `ListVaultItemsByProject` query. Same shape as 4d.

**Presentation** — `VaultItemController` nested under projects (`/admin/projects/{project}/vault`). `StoreVaultItemRequest`: `document_type in:business_context,prd,srs,sad,coding_guidelines,glossary,sprint_goal_template`; `title` required; `content` required; `rank_gate` nullable string≤20; `phase_gate` `nullable|in:pre_induction,post_induction,post_sprint_1,mid_session,advanced_only`; `is_reference_doc` boolean; `display_order` integer. Routes + views mirror 4b's reference-material screens (list + add/remove via HTMX).

- [ ] Vault: two enums, aggregate, repository, mapper, binding, commands/handlers, query, controller, request, routes, views.

> **Modelling note:** vault items are conceptually *children of the Project aggregate*, but reopening the closed Project aggregate to add a collection is more churn than value here — the leaf-with-`project_id` shape (as in 4d) is the pragmatic choice and keeps the aggregate boundaries simple. If Project ever grows an invariant that spans its vault (e.g., "a published project must have a PRD"), promote it to a Project child then.

---

## 2. The MedQueue Seeder — Structure

Three seeders, run in dependency order. **Seeders are dev/infrastructure tooling, so they use Eloquent models directly** — this is the one place we deliberately *don't* go through commands/repositories, because seeding is not application behaviour and the ceremony would obscure the data.

```
database/seeders/
├── CompetenceDimensionSeeder.php   # the six REAL dimensions (canonical)
├── RoleDefinitionSeeder.php        # one role, illustrative weights
└── MedQueueSeeder.php              # project → scenario → task → children → criteria → vault
```
`DatabaseSeeder::run()` calls them in that order (dimensions first — rubric criteria FK to them).

---

## 3. `CompetenceDimensionSeeder` — the canonical six

These are **not illustrative** — they're the six dimensions from the Business Logic Document, verbatim. String slug IDs (≤ 20 chars) so they read well as FKs.

`database/seeders/CompetenceDimensionSeeder.php`
```php
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\CompetenceDimensionModel;

class CompetenceDimensionSeeder extends Seeder
{
    public function run(): void
    {
        $dimensions = [
            ['id' => 'dim_problem_analysis', 'name' => 'Problem Analysis & Decomposition', 'short_label' => 'Problem Analysis',
             'core_question' => 'Does the learner understand the problem before solving it?',
             'observable_indicators' => ['Requirements identification','Constraint acknowledgment','Sub-problem breakdown','Ambiguity navigation'],
             'sequence_order' => 1],
            ['id' => 'dim_design', 'name' => 'Design & Architecture', 'short_label' => 'Design & Architecture',
             'core_question' => 'Does the learner make justifiable structural decisions?',
             'observable_indicators' => ['Separation of concerns','Pattern selection','Scalability consideration','Trade-off articulation'],
             'sequence_order' => 2],
            ['id' => 'dim_implementation', 'name' => 'Implementation & Coding', 'short_label' => 'Implementation & Coding',
             'core_question' => 'Does the learner translate design into working, quality code?',
             'observable_indicators' => ['Correctness','Code structure','Readability','Error handling','Adherence to standards'],
             'sequence_order' => 3],
            ['id' => 'dim_testing', 'name' => 'Testing & Quality Assurance', 'short_label' => 'Testing & QA',
             'core_question' => 'Does the learner verify and validate their work?',
             'observable_indicators' => ['Test coverage','Test strategy','Edge case identification','Defect reporting'],
             'sequence_order' => 4],
            ['id' => 'dim_debugging', 'name' => 'Debugging & Problem-Solving', 'short_label' => 'Debugging & Problem-Solving',
             'core_question' => 'Does the learner identify and resolve failures methodically?',
             'observable_indicators' => ['Root cause isolation','Hypothesis testing','Log interpretation','Fix verification'],
             'sequence_order' => 5],
            ['id' => 'dim_communication', 'name' => 'Communication & Documentation', 'short_label' => 'Communication & Docs',
             'core_question' => 'Does the learner communicate their work clearly and professionally?',
             'observable_indicators' => ['Code comments','Technical documentation','Decision justification','Clarity of explanation'],
             'sequence_order' => 6],
        ];

        foreach ($dimensions as $d) {
            CompetenceDimensionModel::updateOrCreate(['id' => $d['id']], $d);
        }
    }
}
```
> `updateOrCreate` on the slug id makes the seeder **idempotent** — re-running it won't duplicate. `observable_indicators` is a `json` column with an `array` cast, so pass a PHP array.

- [ ] Six dimensions seed and are idempotent.

---

## 4. `RoleDefinitionSeeder` — one illustrative role

The weighting/threshold *values* here are illustrative starting points (the BLD gives Backend Engineer as an example but not exact numbers); the **structure** is real. Adjust the numbers to your model.

```php
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Competency\Infrastructure\Persistence\Eloquent\Model\RoleDefinitionModel;

class RoleDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        RoleDefinitionModel::updateOrCreate(
            ['title' => 'Backend Engineer'],
            [
                'specialization_tags'  => ['backend', 'api', 'databases'],
                'min_years_experience' => 1,
                // weights sum to 1.0 across the six dimensions (illustrative)
                'dimension_weights'    => [
                    'dim_problem_analysis' => 0.15, 'dim_design' => 0.20, 'dim_implementation' => 0.30,
                    'dim_testing' => 0.15, 'dim_debugging' => 0.15, 'dim_communication' => 0.05,
                ],
                // minimum performance level per dimension to qualify (illustrative)
                'dimension_thresholds' => [
                    'dim_problem_analysis' => 'proficient', 'dim_design' => 'proficient',
                    'dim_implementation' => 'proficient', 'dim_testing' => 'developing',
                    'dim_debugging' => 'developing', 'dim_communication' => 'developing',
                ],
                'is_lead_role' => false,
            ],
        );
    }
}
```

- [ ] One role definition seeds with real dimension keys.

---

## 5. `MedQueueSeeder` — the full chain

**Honesty note:** the six dimensions above are canonical. The MedQueue *narrative content below is an illustrative starter* — a working, end-to-end blueprint you can click through — not a spec-mandated dataset. Replace the scenario/task/criterion prose with your real MedQueue material; the **shape** (how the rows connect) is what matters here. MedQueue = a hospital patient-queue management system.

```php
<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricSetModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioReferenceMaterialModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskExpectedDeliverableModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskCacVariationModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskGuidancePromptModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricCriterionModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ArtifactVaultItemModel;

class MedQueueSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Project + its rubric set (1:1). In the app, the ProjectTemplateRepository
        //    creates both in a transaction; the seeder inserts models directly, so we
        //    wire them by hand here.
        $project = ProjectTemplateModel::create([
            'title'               => 'MedQueue — Hospital Patient Queue System',
            'tagline'             => 'Build the backend for a clinic patient-flow platform.',
            'project_type'        => 'web_application',
            'business_domain'     => 'healthcare',
            'business_context'    => 'A regional clinic needs to replace paper-based patient queuing...',
            'stakeholders'        => [['role' => 'Clinic Manager', 'concern' => 'throughput'], ['role' => 'Nurse', 'concern' => 'fairness']],
            'overarching_constraints' => ['HIPAA-style data privacy', 'Offline-tolerant'],
            'tech_context'        => ['stack' => ['PHP', 'Laravel', 'MySQL']],
            'specialization_tags' => ['backend', 'api'],
            'coding_guidelines'   => 'PSR-12; thin controllers.',
            'velocity_estimate'   => ['sprint_days' => 10],
            'difficulty_level'    => 'intermediate',
            'is_published'        => true,
            'is_active'           => true,
        ]);

        $rubricSet = RubricSetModel::create(['project_id' => $project->id, 'version' => '1.0']);
        $project->update(['rubric_set_id' => $rubricSet->id]);

        // 2) Vault item (a reference PRD)
        ArtifactVaultItemModel::create([
            'project_id' => $project->id, 'document_type' => 'prd', 'title' => 'MedQueue PRD',
            'content' => 'Product requirements: queue creation, priority triage, wait-time display...',
            'rank_gate' => null, 'phase_gate' => 'pre_induction', 'is_reference_doc' => true, 'display_order' => 1,
        ]);

        // 3) Scenario
        $scenario = ScenarioTemplateModel::create([
            'project_id' => $project->id, 'sequence_order' => 1, 'title' => 'Sprint 1 — The Queue API',
            'narrative_context' => 'You have joined the MedQueue team mid-sprint...',
            'situation_trigger' => "Slack from the Tech Lead: \"Can you take the queue-enqueue endpoint? Ticket MQ-14.\"",
            'situation_trigger_type' => 'slack_message',
            'learner_role_label' => 'Backend Engineer', 'default_autonomy_level' => 'mid',
            'is_diagnostic' => false, 'is_published' => true, 'is_active' => true,
        ]);

        ScenarioReferenceMaterialModel::create([
            'scenario_id' => $scenario->id, 'material_type' => 'ticket', 'title' => 'MQ-14: Enqueue patient',
            'content' => "As a nurse, I want to add a patient to the queue so that they are seen in order...",
            'embedded_signals' => ['priority triage is out of scope for MQ-14'], 'display_order' => 1,
        ]);

        // 4) Task + children
        $task = TaskModel::create([
            'scenario_id' => $scenario->id, 'sequence_order' => 1, 'title' => 'Implement the enqueue endpoint',
            'task_brief' => 'Add POST /queue/enqueue that validates and stores a patient in the queue.',
            'domain' => 'backend', 'task_type' => 'core',
            'role_tags' => ['backend'], 'tools' => ['php', 'laravel'], 'prerequisite_concepts' => ['http', 'validation'],
            'is_cac_runtime_set' => true, 'fixed_complexity' => null, 'fixed_autonomy' => null, 'fixed_context_fidelity' => null,
            'consequence_task_ids' => [], 'suggestion_task_ids' => [],
            'is_architectural' => false, 'planning_layer_active' => true,
            'code_execution_config' => ['language' => 'php'], 'model_response_summary' => 'A validated controller + form request + queue insert.',
            'time_limit_minutes' => 90, 'is_published' => true, 'is_active' => true,
        ]);

        TaskExpectedDeliverableModel::create([
            'task_id' => $task->id, 'type' => 'code', 'label' => 'Endpoint code',
            'description' => 'Controller + validation + persistence.', 'is_required' => true, 'display_order' => 1,
        ]);

        foreach (['low', 'mid', 'high'] as $i => $level) {
            TaskCacVariationModel::create([
                'task_id' => $task->id, 'complexity_level' => $level,
                'scenario_text' => "Enqueue variant at {$level} complexity.",
                'scaffolding_text_low' => 'Step-by-step guidance.', 'scaffolding_text_mid' => 'Hints only.', 'scaffolding_text_high' => 'No scaffolding.',
                'context_text_low' => 'Full context.', 'context_text_mid' => 'Partial context.', 'context_text_high' => 'Sparse context.',
            ]);
        }

        TaskGuidancePromptModel::create([
            'task_id' => $task->id, 'trigger_dimension' => 'dim_problem_analysis',
            'prompt_text' => 'Have you considered what happens when the queue is full?',
            'autonomy_level_filter' => 'low', 'delivery_mode' => 'proactive', 'display_order' => 1,
        ]);

        // 5) Rubric criteria (reference the REAL dimensions seeded earlier)
        RubricCriterionModel::create([
            'rubric_set_id' => $rubricSet->id, 'task_id' => $task->id,
            'task_dimension_label' => 'Endpoint correctness', 'parent_dimension_id' => 'dim_implementation',
            'complexity_level' => 'mid', 'criterion_text' => 'The endpoint validates input and persists the patient correctly.',
            'weight' => '0.400', 'dimension_weight' => '0.300',
            'claude_detection_hint' => 'Look for a form request / validation and a DB insert.',
            'distinguished_description' => 'Robust validation, clear errors, idempotent insert.',
            'proficient_description'    => 'Validates required fields and inserts correctly.',
            'developing_description'    => 'Inserts but with weak/partial validation.',
            'beginning_description'     => 'No validation or incorrect persistence.',
            'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => 'MedQueue PRD §2',
        ]);

        RubricCriterionModel::create([
            'rubric_set_id' => $rubricSet->id, 'task_id' => $task->id,
            'task_dimension_label' => 'Problem framing', 'parent_dimension_id' => 'dim_problem_analysis',
            'complexity_level' => 'mid', 'criterion_text' => 'The learner identifies edge cases (full queue, duplicate patient).',
            'weight' => '0.300', 'dimension_weight' => '0.200',
            'claude_detection_hint' => 'Look for acknowledgment of edge cases in the text layer.',
            'distinguished_description' => 'Names multiple edge cases and handles them.',
            'proficient_description'    => 'Names the key edge cases.',
            'developing_description'    => 'Mentions one edge case.',
            'beginning_description'     => 'No edge-case awareness.',
            'is_architectural' => false, 'is_planning_layer' => false, 'reference_doc_anchor' => null,
        ]);
    }
}
```

`database/seeders/DatabaseSeeder.php`
```php
public function run(): void
{
    $this->call([
        CompetenceDimensionSeeder::class,
        RoleDefinitionSeeder::class,
        MedQueueSeeder::class,
    ]);
}
```

- [ ] `php artisan db:seed` runs clean; re-running doesn't duplicate dimensions (idempotent) — note MedQueue's `create()` calls are *not* idempotent, so run the seeders against a fresh DB (`migrate:fresh --seed`) during development.

---

## 6. Turn On the Deferred Validation Rule

Now that dimensions exist, activate the 4d rule you deferred — in `StoreRubricCriterionRequest`:
```php
'parent_dimension_id' => ['required', 'string', 'max:20', 'exists:competence_dimensions,id'],
```
The criterion form's dimension dropdown is now populated by `ListCompetenceDimensions`, so authors pick a real dimension and the `exists` rule guards typos.

- [ ] `exists:competence_dimensions,id` enabled.

---

## 7. Verification Checklist

- [ ] Bus fix confirmed.
- [ ] `php artisan migrate:fresh --seed` completes with no errors.
- [ ] `competence_dimensions` has exactly **6** rows, `sequence_order` 1–6; re-seeding dimensions doesn't add duplicates.
- [ ] `/admin/projects` shows **MedQueue**; drilling in shows its scenario, the scenario shows its task, the task shows its CAC variants (3), guidance prompt (1), deliverable (1), and **2 rubric criteria** whose dimension dropdown resolves to real dimension labels.
- [ ] `/admin/projects/{id}/vault` lists the seeded PRD; the `<x-reference-material>`-style rendering (or vault view) shows it.
- [ ] Creating a *new* criterion in the UI: the dimension dropdown lists all six; picking one saves; a bogus `parent_dimension_id` is now rejected by `exists`.
- [ ] `route:list --path=admin` shows the vault routes behind `role:content_author`.
- [ ] `composer dump-autoload -o` clean on the vault classes + two enums + three seeders.

---

## 8. Phase 4 Is Complete — What You've Built

Across 4a–4e you built the entire **content-authoring layer** through your DDD stack:

| Sub-phase | Delivered |
|---|---|
| 4a | Admin shell + `role:content_author` + feature-flag toggle + **Project** slice (the reference pattern) |
| 4b | **Scenario** authoring + reference materials (aggregate-owns-a-collection) + the `<x-reference-material>` component |
| 4c | **Task** authoring + five child collections (deliverables, CAC variants, dependencies, anchors, guidance prompts) |
| 4d | **Rubric criteria** (leaf aggregate + cross-context dimension read) |
| 4e | **Vault** items + the **MedQueue seeder** (the six real dimensions + a full worked project) |

An author can now build a complete simulation blueprint, and the database ships with one. **Phase 5** turns to the *learner* side — profile, session, and enrolment tables — and Phase 6 is where the runtime finally *renders* all this: the situation-trigger modal (D2), the guidance-prompt selector (D3), and the sequential-scenario flow (D4) come alive.

---

## 9. Scope Discipline Self-Check (Guidance §7)

- [x] **No new tables** — vault writes to `artifact_vault_items` (Phase-3); the seeder populates existing tables only.
- [x] **Correct contexts** — vault is Simulation; dimensions/roles seed into Competency's tables via their models.
- [x] **Nothing pulled forward** — no learner/session/runtime tables touched; the seeder authors *content*, it doesn't simulate.
- [x] **Enums match the DB** — `DocumentType`, `PhaseGate` verified case-for-case against the migration.
- [x] **Canonical data is real** — the six competence dimensions are verbatim from the Business Logic Document; MedQueue narrative is clearly flagged as illustrative starter content, role weights as illustrative.
- [x] **Seeder-uses-Eloquent-directly justified** — seeders are dev infrastructure, not application behaviour; the deliberate exception to "go through the repository" is stated.
- [x] **Idempotency addressed** — dimensions/roles via `updateOrCreate`; MedQueue documented as fresh-DB-only.
- [x] **Deferred rule closed** — the `exists:competence_dimensions,id` validation from 4d is now switched on, as promised.
- [x] **Runnable verification** (§7) including the `migrate:fresh --seed` end-to-end and the 6-row dimension check.

Push all of Phase 4 (4a–4e) whenever you're ready. Then I'll pull `development` and do a **consolidated review of the whole authoring stack** against the real code — the outstanding bus fix, every enum-cast activation, the aggregate/repository/binding wiring, the seeder, and anything that drifted during implementation — and give you a single prioritised debug list before we start **Phase 5 (Learner Profile & Session tables)**.
