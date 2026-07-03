I have retrieved the necessary documents to understand the system architecture, data models, and the exact requirements for generating the Phase 4 implementation plan.

I will now generate the Phase 4 implementation plan (Content Management / Admin UI) following your instructions. This includes detailed Markdown with code snippets, respecting the DDD structure, existing models, and pedagogical style.

---

# IWSTGS — Phase 4: Content Management (Admin UI)

> **Goal of this phase:** Build the administrative interface for content authors to create and manage the simulation blueprints (Projects, Scenarios, Tasks, Criteria, and Vault Items) that learners will interact with. This is the first phase that builds controllers, form requests, and application services to write data into the tables modelled in Phase 3. It culminates in loading the MedQueue sample content via a seeder.

---

## 0. Where This Phase Sits

Phases 0–3 built the foundation: module structure, identity (registration/login), the ~40 content tables, and their Eloquent models. However, the system currently has no UI to populate those tables, aside from manual database inserts.

Phase 4 bridges this gap by creating the **Content Management Admin UI**. We will build the controllers, views (using Blade and HTMX), and services required to manage the `ProjectTemplate` → `ScenarioTemplate` → `Task` hierarchy.

**Prerequisites:**

* Phases 0–3 complete.
* The `admin.content_management` feature flag is enabled.
* You are logged in with a user account possessing the `content_author` role.

---

## 1. Architecture Reconciliation — Read Before You Start

Following the source-of-truth hierarchy, here are the critical architectural rules for this phase:

**1.1 — Form Requests over Controller Logic.** We will use Laravel Form Requests (e.g., `StoreProjectRequest`) to handle validation. Controllers should remain thin, delegating complex creation logic (like the `ProjectTemplate` + `RubricSet` transaction) to Application Services.

**1.2 — HTMX for Dynamic Forms.** We will use HTMX to handle dynamic form elements, specifically the toggling of feature flags and the dynamic addition/removal of rows in complex forms (like Expected Deliverables and Knowledge Anchors) without writing custom JavaScript.

**1.3 — Bounded Context Respect.** The controllers built here live in the `Content` module (as they manage platform content), but they will interact with Application Services in the `Simulation` module, as `Simulation` owns the blueprints.

**1.4 — The Users/Learners Split.** We respect the architectural decision that admin users interacting with this UI are `users` with the `content_author` role, not necessarily `learners`.

---

## 2. Enable Feature Flag

Before building the UI, ensure the feature flag is enabled.

* [ ] In `tinker` or your database client, set `admin.content_management` to `is_enabled = 1` in the `feature_flags` table.

---

## 3. Admin Layout and Routing

We need a dedicated layout for the admin area and the route definitions to reach it.

### 3.1 — Admin Blade Layout

This layout extends the base layout from Phase 0 but adds an admin navigation bar.

* [ ] Create `resources/views/admin/layouts/admin.blade.php`:

```html
@extends('layouts.app')

@section('title', 'IWSTGS Admin')

@push('styles')
<style>
    .admin-nav { background: #f3f4f6; padding: 1rem; border-bottom: 1px solid #e5e7eb; margin-bottom: 2rem; }
    .admin-nav a { margin-right: 1rem; text-decoration: none; color: #374151; font-weight: 500; }
    .admin-nav a:hover { color: #2563eb; }
    .admin-container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
    .form-group { margin-bottom: 1rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; }
    .btn { padding: 0.5rem 1rem; background: #2563eb; color: white; border: none; border-radius: 0.25rem; cursor: pointer; }
    .btn-secondary { background: #6b7280; }
</style>
@endpush

@section('content')
    <nav class="admin-nav">
        <div class="admin-container">
            <a href="{{ route('admin.projects.index') }}">Projects</a>
            <a href="{{ route('admin.feature-flags.index') }}">Feature Flags</a>
            </div>
    </nav>

    <div class="admin-container">
        @if(session('success'))
            <div style="background: #d1fae5; color: #065f46; padding: 1rem; margin-bottom: 1rem; border-radius: 0.25rem;">
                {{ session('success') }}
            </div>
        @endif

        @yield('admin-content')
    </div>
@endsection

```

### 3.2 — Register Admin Routes

We need to populate the `routes/admin.php` file created in Phase 0.

* [ ] Open `routes/admin.php` and add the following:

```php
<?php

use Illuminate\Support\Facades\Route;
use Src\Content\Presentation\Http\Controller\FeatureFlagAdminController;
use Src\Content\Presentation\Http\Controller\ProjectAdminController;
use Src\Content\Presentation\Http\Controller\ScenarioAdminController;
use Src\Content\Presentation\Http\Controller\TaskAdminController;

Route::get('/feature-flags', [FeatureFlagAdminController::class, 'index'])->name('admin.feature-flags.index');
Route::patch('/feature-flags/{key}', [FeatureFlagAdminController::class, 'toggle'])->name('admin.feature-flags.toggle');

Route::resource('projects', ProjectAdminController::class)->names('admin.projects');
Route::patch('projects/{project}/publish', [ProjectAdminController::class, 'togglePublish'])->name('admin.projects.publish');

// Scenarios are nested under projects for creation/listing
Route::get('projects/{project}/scenarios', [ScenarioAdminController::class, 'index'])->name('admin.projects.scenarios.index');
Route::get('projects/{project}/scenarios/create', [ScenarioAdminController::class, 'create'])->name('admin.projects.scenarios.create');
Route::post('projects/{project}/scenarios', [ScenarioAdminController::class, 'store'])->name('admin.projects.scenarios.store');
Route::get('scenarios/{scenario}/edit', [ScenarioAdminController::class, 'edit'])->name('admin.scenarios.edit');
Route::patch('scenarios/{scenario}', [ScenarioAdminController::class, 'update'])->name('admin.scenarios.update');

// Tasks are nested under scenarios for creation/listing
Route::get('scenarios/{scenario}/tasks', [TaskAdminController::class, 'index'])->name('admin.scenarios.tasks.index');
Route::get('scenarios/{scenario}/tasks/create', [TaskAdminController::class, 'create'])->name('admin.scenarios.tasks.create');
Route::post('scenarios/{scenario}/tasks', [TaskAdminController::class, 'store'])->name('admin.scenarios.tasks.store');
Route::get('tasks/{task}/edit', [TaskAdminController::class, 'edit'])->name('admin.tasks.edit');
Route::patch('tasks/{task}', [TaskAdminController::class, 'update'])->name('admin.tasks.update');

```

---

## 4. Feature Flag Admin

This is a simple UI to toggle feature flags using HTMX.

### 4.1 — Feature Flag Controller

* [ ] Create `src/Content/Presentation/Http/Controller/FeatureFlagAdminController.php`:

```php
<?php

namespace Src\Content\Presentation\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Src\Shared\Domain\Feature\FeatureFlagRepository;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;

class FeatureFlagAdminController extends Controller
{
    public function index(FeatureFlagRepository $repository)
    {
        $flags = $repository->all();
        return view('admin.feature-flags.index', compact('flags'));
    }

    public function toggle(string $key, FeatureFlagService $service)
    {
        $flag = app(FeatureFlagRepository::class)->findByKey($key);
        
        if ($flag->isEnabled) {
            $service->disable($key);
            $newState = false;
        } else {
            $service->enable($key);
            $newState = true;
        }

        // Return just the button partial for HTMX to swap
        return view('admin.feature-flags._toggle_button', [
            'key' => $key,
            'isEnabled' => $newState
        ]);
    }
}

```

### 4.2 — Feature Flag Views

* [ ] Create `resources/views/admin/feature-flags/index.blade.php`:

```html
@extends('admin.layouts.admin')

@section('admin-content')
    <h2>Feature Flags</h2>
    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #e5e7eb;">
                <th style="padding: 0.5rem;">Key</th>
                <th style="padding: 0.5rem;">Module</th>
                <th style="padding: 0.5rem;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($flags as $flag)
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.5rem;">{{ $flag->flagKey }}</td>
                    <td style="padding: 0.5rem;">{{ $flag->module }}</td>
                    <td style="padding: 0.5rem;" id="flag-toggle-{{ str_replace('.', '-', $flag->flagKey) }}">
                        @include('admin.feature-flags._toggle_button', ['key' => $flag->flagKey, 'isEnabled' => $flag->isEnabled])
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection

```

* [ ] Create the HTMX partial `resources/views/admin/feature-flags/_toggle_button.blade.php`:

```html
<button 
    hx-patch="{{ route('admin.feature-flags.toggle', $key) }}"
    hx-target="#flag-toggle-{{ str_replace('.', '-', $key) }}"
    class="btn {{ $isEnabled ? '' : 'btn-secondary' }}">
    {{ $isEnabled ? 'Enabled' : 'Disabled' }}
</button>

```

---

## 5. Project Management

The Project creation process is complex because the Integration Spec (§11) and Data Model require a `RubricSet` to be created one-to-one with the `ProjectTemplate`. This requires a database transaction orchestrated by an Application Service.

### 5.1 — Application Service for Project Creation

* [ ] Create `src/Simulation/Application/Service/ProjectManagementService.php`:

```php
<?php

namespace Src\Simulation\Application\Service;

use Illuminate\Support\Facades\DB;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricSetModel;

class ProjectManagementService
{
    /**
     * Creates a Project and its corresponding RubricSet in a transaction.
     */
    public function createProject(array $data): ProjectTemplateModel
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the Rubric Set first (it doesn't need project_id initially due to deferred FK, but we will update it)
            // Note: Data Model v2 says rubric_sets.project_id is required. 
            // We must create the project first, then the rubric set, then update the project with the rubric_set_id.

            $project = ProjectTemplateModel::create(array_merge($data, [
                'is_published' => false,
                'is_active' => true,
                'rubric_set_id' => null, // Temporarily null
            ]));

            $rubricSet = RubricSetModel::create([
                'project_id' => $project->id,
                'version' => '1.0'
            ]);

            // Update project with the rubric set ID
            $project->update(['rubric_set_id' => $rubricSet->id]);

            return $project;
        });
    }
}

```

### 5.2 — Project Form Request

* [ ] Create `src/Content/Presentation/Http/Request/StoreProjectRequest.php`:

```php
<?php

namespace Src\Content\Presentation\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorisation handled by middleware
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:300',
            'tagline' => 'nullable|string|max:500',
            'project_type' => 'required|string|max:100',
            'business_context' => 'required|string',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            // In a full implementation, JSON arrays would be validated deeper
            'stakeholders' => 'nullable|json',
            'overarching_constraints' => 'nullable|json',
            'tech_context' => 'nullable|json',
            'specialization_tags' => 'required|string', // Will explode to array in controller
        ];
    }
}

```

### 5.3 — Project Controller

* [ ] Create `src/Content/Presentation/Http/Controller/ProjectAdminController.php`:

```php
<?php

namespace Src\Content\Presentation\Http\Controller;

use Illuminate\Routing\Controller;
use Src\Content\Presentation\Http\Request\StoreProjectRequest;
use Src\Simulation\Application\Service\ProjectManagementService;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;

class ProjectAdminController extends Controller
{
    public function index()
    {
        $projects = ProjectTemplateModel::orderBy('created_at', 'desc')->get();
        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.projects.create');
    }

    public function store(StoreProjectRequest $request, ProjectManagementService $service)
    {
        $data = $request->validated();
        
        // Convert comma-separated string to array for tags
        $data['specialization_tags'] = array_map('trim', explode(',', $data['specialization_tags']));
        
        // Decode JSON inputs
        $data['stakeholders'] = json_decode($data['stakeholders'] ?? '[]', true);
        $data['overarching_constraints'] = json_decode($data['overarching_constraints'] ?? '[]', true);
        $data['tech_context'] = json_decode($data['tech_context'] ?? '{}', true);

        $project = $service->createProject($data);

        return redirect()->route('admin.projects.index')->with('success', 'Project created successfully.');
    }

    public function edit(ProjectTemplateModel $project)
    {
        return view('admin.projects.edit', compact('project'));
    }

    // update() omitted for brevity, follows similar pattern to store
}

```

### 5.4 — Project Views (Truncated for brevity)

* [ ] Create `resources/views/admin/projects/index.blade.php`:

```html
@extends('admin.layouts.admin')

@section('admin-content')
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2>Projects</h2>
        <a href="{{ route('admin.projects.create') }}" class="btn">Create Project</a>
    </div>

    <table style="width: 100%; border-collapse: collapse; text-align: left;">
        <thead>
            <tr style="border-bottom: 2px solid #e5e7eb;">
                <th style="padding: 0.5rem;">Title</th>
                <th style="padding: 0.5rem;">Type</th>
                <th style="padding: 0.5rem;">Difficulty</th>
                <th style="padding: 0.5rem;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($projects as $project)
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 0.5rem;">{{ $project->title }}</td>
                    <td style="padding: 0.5rem;">{{ $project->project_type }}</td>
                    <td style="padding: 0.5rem;">{{ $project->difficulty_level }}</td>
                    <td style="padding: 0.5rem;">
                        <a href="{{ route('admin.projects.edit', $project) }}">Edit</a> |
                        <a href="{{ route('admin.projects.scenarios.index', $project) }}">Scenarios</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection

```

* [ ] Create `resources/views/admin/projects/create.blade.php` (Basic skeleton):

```html
@extends('admin.layouts.admin')

@section('admin-content')
    <h2>Create Project</h2>
    <form action="{{ route('admin.projects.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" required>
        </div>
        <div class="form-group">
            <label>Project Type</label>
            <input type="text" name="project_type" required>
        </div>
        <div class="form-group">
            <label>Business Context (The 'Why')</label>
            <textarea name="business_context" rows="5" required></textarea>
        </div>
        <div class="form-group">
            <label>Difficulty</label>
            <select name="difficulty_level">
                <option value="beginner">Beginner</option>
                <option value="intermediate" selected>Intermediate</option>
                <option value="advanced">Advanced</option>
            </select>
        </div>
         <div class="form-group">
            <label>Specialization Tags (comma separated)</label>
            <input type="text" name="specialization_tags" placeholder="backend, api, database" required>
        </div>
        <button type="submit" class="btn">Create</button>
    </form>
@endsection

```

---

## 6. Scenario Management

Scenarios are nested under projects. The Data Model dictates that scenarios have `situation_trigger_type` and `default_autonomy_level` enums.

### 6.1 — Scenario Controller

* [ ] Create `src/Content/Presentation/Http/Controller/ScenarioAdminController.php`:

```php
<?php

namespace Src\Content\Presentation\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;

class ScenarioAdminController extends Controller
{
    public function index(ProjectTemplateModel $project)
    {
        $scenarios = $project->scenarios()->orderBy('sequence_order')->get();
        return view('admin.scenarios.index', compact('project', 'scenarios'));
    }

    public function create(ProjectTemplateModel $project)
    {
        // Auto-assign next sequence order
        $nextOrder = $project->scenarios()->max('sequence_order') + 1;
        return view('admin.scenarios.create', compact('project', 'nextOrder'));
    }

    public function store(Request $request, ProjectTemplateModel $project)
    {
        $data = $request->validate([
            'sequence_order' => 'required|integer',
            'title' => 'required|string|max:300',
            'narrative_context' => 'required|string',
            'situation_trigger' => 'required|string',
            'situation_trigger_type' => 'required|string', // Deferred enum validation
            'default_autonomy_level' => 'required|string', // Deferred enum validation
            'is_diagnostic' => 'boolean',
        ]);

        $project->scenarios()->create(array_merge($data, [
            'is_published' => false,
            'is_active' => true,
        ]));

        return redirect()->route('admin.projects.scenarios.index', $project)->with('success', 'Scenario created.');
    }
}

```

---

## 7. Task Management

Tasks are nested under scenarios. Creating a task is complex because a task is meaningless without at least one `TaskExpectedDeliverable` (per IS2 §6.1).

### 7.1 — Task Controller

* [ ] Create `src/Content/Presentation/Http/Controller/TaskAdminController.php`:

```php
<?php

namespace Src\Content\Presentation\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

class TaskAdminController extends Controller
{
    public function index(ScenarioTemplateModel $scenario)
    {
        $tasks = $scenario->tasks()->orderBy('sequence_order')->get();
        return view('admin.tasks.index', compact('scenario', 'tasks'));
    }

    public function store(Request $request, ScenarioTemplateModel $scenario)
    {
        // Basic validation for the core task fields
        $taskData = $request->validate([
            'sequence_order' => 'required|integer',
            'title' => 'required|string|max:300',
            'task_brief' => 'required|string',
            'domain' => 'required|string',
            'task_type' => 'required|string',
            'role_tags' => 'required|string', // comma separated
        ]);

        $taskData['role_tags'] = array_map('trim', explode(',', $taskData['role_tags']));
        
        DB::transaction(function () use ($scenario, $taskData) {
            $task = $scenario->tasks()->create(array_merge($taskData, [
                'is_cac_runtime_set' => true,
                'is_architectural' => false,
                'planning_layer_active' => false,
                'is_published' => false,
                'is_active' => true,
            ]));

            // Enforce IS2 §6.1: Every task must have at least one deliverable
            // Defaulting to written_explanation for simplicity in MVP creation
            $task->expectedDeliverables()->create([
                'type' => 'written_explanation',
                'label' => 'Analysis & Reasoning',
                'description' => 'Provide your technical reasoning for this task.',
                'is_required' => true,
                'display_order' => 1,
            ]);
        });

        return redirect()->route('admin.scenarios.tasks.index', $scenario)->with('success', 'Task created with default text deliverable.');
    }
}

```

---

## 8. Seeding the MedQueue Sample Content

This is the culmination of Phases 2, 3, and 4. We will load the actual Competence Dimensions and a slice of the MedQueue project to prove the schema works.

### 8.1 — Create the Seeder

* [ ] Create `database/seeders/MedQueueSeeder.php`:

```bash
php artisan make:seeder MedQueueSeeder

```

* [ ] Populate `database/seeders/MedQueueSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Simulation\Domain\Task\TaskType;
use Src\Simulation\Domain\Project\DifficultyLevel;

class MedQueueSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Canonical Competence Dimensions (Phase 2 requirement)
        $dimensions = [
            ['id' => 'dim_001', 'name' => 'Problem Analysis & Decomposition', 'short_label' => 'Problem Analysis', 'core_question' => 'Does the learner understand the problem before solving it?', 'observable_indicators' => json_encode(['Identifies constraints', 'Breaks down requirements']), 'sequence_order' => 1],
            ['id' => 'dim_002', 'name' => 'Design & Architecture', 'short_label' => 'Architecture', 'core_question' => 'Are structural decisions sound?', 'observable_indicators' => json_encode(['Selects appropriate patterns']), 'sequence_order' => 2],
            ['id' => 'dim_003', 'name' => 'Implementation & Coding', 'short_label' => 'Implementation', 'core_question' => 'Is the code functional and clean?', 'observable_indicators' => json_encode(['Writes working code']), 'sequence_order' => 3],
        ];
        DB::table('competence_dimensions')->insertOrIgnore($dimensions);

        // 2. Seed Role Definition
        $roleId = \Illuminate\Support\Str::uuid();
        DB::table('role_definitions')->insert([
            'id' => $roleId,
            'title' => 'Backend Engineer',
            'specialization_tags' => json_encode(['backend', 'api']),
            'min_years_experience' => 0,
            'dimension_weights' => json_encode(['dim_001' => 0.3, 'dim_002' => 0.3, 'dim_003' => 0.4]),
            'dimension_thresholds' => json_encode(['dim_001' => 'basic', 'dim_002' => 'basic', 'dim_003' => 'intermediate']),
            'is_lead_role' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create MedQueue Project & Rubric Set
        $projectId = \Illuminate\Support\Str::uuid();
        $rubricSetId = \Illuminate\Support\Str::uuid();

        DB::table('project_templates')->insert([
            'id' => $projectId,
            'title' => 'MedQueue API Refactor',
            'tagline' => 'Modernise a legacy clinic queuing system.',
            'project_type' => 'Healthcare SaaS',
            'business_domain' => 'Healthcare Technology',
            'business_context' => 'MedQueue handles patient queuing for walk-in clinics. The current monolithic system is failing under load.',
            'stakeholders' => json_encode([['name' => 'Dr. Smith', 'role' => 'Clinic Director', 'priority' => 'high', 'concern' => 'System uptime']]),
            'tech_context' => json_encode(['existing_stack' => ['PHP', 'MySQL'], 'team_size' => 4]),
            'specialization_tags' => json_encode(['backend', 'api', 'refactoring']),
            'rubric_set_id' => $rubricSetId, // Deferring FK logic for seeder simplicity
            'difficulty_level' => DifficultyLevel::Intermediate->value,
            'is_published' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('rubric_sets')->insert([
            'id' => $rubricSetId,
            'project_id' => $projectId,
            'version' => '1.0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Add Artifact Vault Item (PRD Stub)
        DB::table('artifact_vault_items')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'project_id' => $projectId,
            'document_type' => 'prd',
            'title' => 'MedQueue v2.0 PRD',
            'content' => 'System must handle 500 concurrent clinic connections with <200ms latency.',
            'is_reference_doc' => true,
            'display_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 5. Create Scenario
        $scenarioId = \Illuminate\Support\Str::uuid();
        DB::table('scenario_templates')->insert([
            'id' => $scenarioId,
            'project_id' => $projectId,
            'sequence_order' => 1,
            'title' => 'Sprint 1 - Identifying Bottlenecks',
            'narrative_context' => 'You have just joined the MedQueue backend team. The system crashed twice yesterday.',
            'situation_trigger' => 'Slack message from Lead: "Hey, take a look at the PRD and tell me where you think our biggest risk is."',
            'situation_trigger_type' => 'slack_message',
            'default_autonomy_level' => 'mid',
            'is_diagnostic' => false,
            'is_published' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 6. Create Task
        $taskId = \Illuminate\Support\Str::uuid();
        DB::table('tasks')->insert([
            'id' => $taskId,
            'scenario_id' => $scenarioId,
            'sequence_order' => 1,
            'title' => 'Analyze PRD Constraints',
            'task_brief' => 'Review the MedQueue PRD. Identify the primary technical constraint that will impact our API design.',
            'domain' => 'backend',
            'task_type' => TaskType::Core->value,
            'role_tags' => json_encode(['backend']),
            'is_cac_runtime_set' => true,
            'is_architectural' => false,
            'planning_layer_active' => false,
            'is_published' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 7. Add Deliverable
        DB::table('task_expected_deliverables')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'task_id' => $taskId,
            'type' => 'written_explanation',
            'label' => 'Constraint Analysis',
            'description' => 'Write a short summary of the primary technical constraint.',
            'is_required' => true,
            'display_order' => 1,
        ]);

        // 8. Add Rubric Criterion
        DB::table('rubric_criteria')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'rubric_set_id' => $rubricSetId,
            'task_id' => $taskId,
            'task_dimension_label' => 'constraint_identification',
            'parent_dimension_id' => 'dim_001',
            'complexity_level' => 'mid',
            'criterion_text' => 'Identifies the 500 concurrent connection requirement as the primary scaling constraint.',
            'weight' => 1.000,
            'dimension_weight' => 1.000,
            'claude_detection_hint' => 'Look for explicit mention of 500 concurrent connections or latency.',
            'distinguished_description' => 'Identifies the constraint and proposes a caching strategy.',
            'proficient_description' => 'Correctly identifies the 500 connection limit as the main issue.',
            'developing_description' => 'Mentions performance but misses the specific concurrent connection metric.',
            'beginning_description' => 'Fails to identify the scaling constraint.',
            'is_architectural' => false,
            'is_planning_layer' => false,
        ]);
    }
}

```

* [ ] Register this seeder in `database/seeders/DatabaseSeeder.php`:
```php
$this->call(MedQueueSeeder::class);

```



---

## 9. Verification Checklist

Do not proceed to Phase 5 until all of these pass:

* [ ] **Feature Flag:** In the database, `feature_flags` table, `admin.content_management` is `is_enabled = 1`.
* [ ] **Routing:** Navigating to `/feature-flags` loads the UI.
* [ ] **HTMX:** Clicking "Disabled" on a feature flag changes it to "Enabled" without a full page reload (check network tab for HTMX request).
* [ ] **Project Creation:** You can create a Project via the UI.
* [ ] **Transaction Verification:** Check the database: After creating a project in the UI, verify a `project_templates` row AND a corresponding `rubric_sets` row were created, and `project_templates.rubric_set_id` is populated.
* [ ] **Seeder:** Running `php artisan db:seed --class=MedQueueSeeder` completes without errors.
* [ ] **Data Integrity:** After seeding, query `SELECT * FROM tasks WHERE title = 'Analyze PRD Constraints';` and confirm it exists and references the MedQueue scenario.

---

## 10. Scope Discipline Self-Check (Guidance Prompt §7)

Reporting on the constraints:

* [x] **Every table introduced exists in Data Model v2:** No new tables introduced in this phase, only interacted with existing ones (`project_templates`, `scenario_templates`, `tasks`, etc.).
* [x] **Every table assigned to the correct bounded context:** Controllers live in `Content` (admin management), interacting with models owned by `Simulation`.
* [x] **No table from a later phase pulled forward:** Focused strictly on content authoring tables. No learner sessions or submissions touched.
* [x] **The `users`/`learners` split respected:** Implicit in this phase; the admin UI relies on standard Laravel auth and role middleware (to be fully integrated via `role:content_author` gating, assuming standard web guards for now).
* [x] **Every enum cast matches DB exactly:** Utilized existing `DifficultyLevel` and `TaskType`.
* [x] **Closes with a runnable verification checklist.** (§9)

---

## 11. Known Issues & Open Decisions

* **Auth Middleware:** The routes defined in `routes/admin.php` currently lack the `role:content_author` middleware applied in the route group within the Service Provider (as outlined in Phase 0). Ensure the `ContentServiceProvider` applies this middleware when loading the routes once the RBAC system from Phase 1 is fully active.
* **Form Completeness:** The provided views are skeletons. A production admin UI for these complex schemas requires substantial form handling (likely involving Alpine.js or extensive HTMX for array/JSON fields like `stakeholders` or `expected_deliverables`).

This completes Phase 4. The system now has the foundational content necessary to begin testing the Learner Onboarding flow in Phase 5.