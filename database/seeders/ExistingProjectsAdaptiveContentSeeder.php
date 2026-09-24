<?php
namespace Database\Seeders;

use Database\Seeders\Concerns\AuthorsSimulationContent;
use Illuminate\Database\Seeder;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ProjectTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\RubricSetModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\ScenarioTemplateModel;
use Src\Simulation\Infrastructure\Persistence\Eloquent\Model\TaskModel;

/**
 * MedQueue and ShiftBoard were authored before the adaptive engine had
 * content to work with: only MedQueue's first task had a consequence or
 * suggestion wired up, and ShiftBoard's later scenarios had a single backend
 * task. This adds a consequence task for every unwired core task, a
 * suggestion task per scenario, and a frontend core task for ShiftBoard
 * scenarios 2 and 3. Runs after the original seeders.
 */
class ExistingProjectsAdaptiveContentSeeder extends Seeder
{
    use AuthorsSimulationContent;

    public function run(): void
    {
        $this->medQueue();
        $this->shiftBoard();
    }

    private function medQueue(): void
    {
        $project = ProjectTemplateModel::where('title', 'MedQueue — Hospital Patient Queue System')->firstOrFail();
        $rubric = RubricSetModel::findOrFail($project->rubric_set_id);
        $back = ['backend'];

        // Scenario 1: enqueue is already wired; add position + triage.
        $s1 = $this->scenarioAt($project, 1);
        $position = $this->taskAt($s1, 4);
        $triage = $this->taskAt($s1, 5);
        $s1Focus = $this->taskAt($s1, 3);

        $positionFix = $this->adaptiveTask('consequence', $s1, $rubric, 6, [
            'title' => 'Follow-up: patients told the wrong wait time',
            'brief' => "A patient was told they were 2nd in line and waited 50 minutes; three urgent patients had been added ahead of them. Nurses say the position endpoint \"lies\". Find out why the position was wrong, fix it, and explain how position is now calculated.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Recognises that position ignored triage priority (or counted served patients), recalculates position using the same ordering as the queue itself, and adds tests with urgent patients inserted ahead.',
            'hint' => 'Is position calculated with the same ordering the nurses call patients in?',
            'criteria' => [
                ['dim_implementation', 'Consistent ordering', 'Position uses the same ordering as the real queue, including urgent patients.', 'Position and queue order must share one definition.', ['One shared ordering, tested with urgent insertions.', 'Correct position including urgent patients.', 'Fixes this case only.', 'Not fixed.']],
            ],
        ]);

        $triageFix = $this->adaptiveTask('consequence', $s1, $rubric, 7, [
            'title' => 'Follow-up: an urgent patient jumped ahead of an earlier urgent patient',
            'brief' => "Two patients were flagged urgent ten minutes apart. The one flagged second was called first. The ward manager wants to know why and wants it fixed today. Explain the cause and fix the ordering.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Identifies missing secondary ordering by enqueue/flag time among urgent patients and orders by (priority, enqueued_at) with a test for two urgent patients.',
            'hint' => 'What decides the order between two patients with the same priority?',
            'criteria' => [
                ['dim_design', 'Tie-breaking rule', 'Defines and implements a clear order among patients with equal priority.', 'Must state the tie-break explicitly.', ['Explicit tie-break with reasoning and tests.', 'Correct tie-break.', 'Order still ambiguous.', 'Not fixed.']],
            ],
        ]);

        $this->linkAdaptive($position, [$positionFix], [$s1Focus]);
        $this->linkAdaptive($triage, [$triageFix], [$s1Focus]);

        // Scenario 2.
        $s2 = $this->scenarioAt($project, 2);
        $perf = $this->taskAt($s2, 1);
        $health = $this->taskAt($s2, 2);

        $perfFix = $this->adaptiveTask('consequence', $s2, $rubric, 3, [
            'title' => 'Follow-up: the queue screen froze during the Monday rush',
            'brief' => "Monday 09:00, 180 patients in the queue: the waiting-room screen took 12 seconds to refresh and front desk reverted to paper. The slow query log shows the position lookup again. Explain why your previous fix didn't hold at this volume and fix it properly, with evidence it now scales.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Identifies an N+1 or per-patient count query, replaces it with a single ordered query or window function and a suitable index, and shows before/after timing or EXPLAIN output.',
            'hint' => 'Count how many queries run for one screen refresh.',
            'criteria' => [
                ['dim_debugging', 'Evidence-based performance fix', 'Finds the real bottleneck and proves the improvement.', 'Evidence (query count, EXPLAIN, timings) is required for proficient.', ['Bottleneck identified, fixed, and before/after evidence given.', 'Bottleneck fixed with some evidence.', 'Guess-based fix.', 'Not fixed.']],
            ],
        ]);

        $healthFix = $this->adaptiveTask('consequence', $s2, $rubric, 4, [
            'title' => 'Follow-up: health check said "OK" while the database was down',
            'brief' => "Last night the database ran out of connections for 40 minutes. The health-check endpoint kept returning 200 OK, so nobody was alerted. Explain why, and change the health check so it reflects what actually matters to the clinic.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'The check only confirmed the web process was up. It should check dependencies (a cheap DB query, queue) with a timeout, return 503 when critical ones fail, and distinguish liveness from readiness.',
            'hint' => 'What does "healthy" mean to a nurse using the system?',
            'criteria' => [
                ['dim_problem_analysis', 'Meaningful health', 'Defines health in terms of the dependencies the clinic relies on.', 'Needs a dependency check with a timeout and correct status code.', ['Dependency checks, timeouts, correct codes, liveness vs readiness explained.', 'Dependency checks with correct codes.', 'Adds a check without timeouts or codes.', 'Unchanged.']],
            ],
        ]);

        $s2Focus = $this->adaptiveTask('suggestion', $s2, $rubric, 5, [
            'title' => 'Development focus: measure before you optimise',
            'brief' => "Describe how you would investigate a \"the page is slow\" report from scratch: what you would measure first, which tools you would use, and how you would know your fix worked.",
            'domain' => 'debugging', 'roles' => $back, 'deliver' => 'written',
            'answer' => 'Reproduce and measure (timings, query count, profiler), find the biggest cost, change one thing, measure again.',
            'hint' => 'Start with numbers, not guesses.',
            'criteria' => [
                ['dim_debugging', 'Measurement discipline', 'Describes a measure-change-measure approach with concrete tools.', 'Must include a baseline measurement.', ['Baseline, targeted change, re-measure, with tools named.', 'Measure-first approach.', 'Mostly guessing.', 'No approach.']],
            ],
        ]);

        $this->linkAdaptive($perf, [$perfFix], [$s2Focus]);
        $this->linkAdaptive($health, [$healthFix], [$s2Focus]);

        // Scenario 3.
        $s3 = $this->scenarioAt($project, 3);
        $rbac = $this->taskAt($s3, 1);
        $audit = $this->taskAt($s3, 2);

        $rbacFix = $this->adaptiveTask('consequence', $s3, $rubric, 3, [
            'title' => 'Follow-up: a nurse viewed a patient from another ward',
            'brief' => "The DPO's follow-up audit found a nurse on Ward B opened a Ward A patient's record by changing the ID in the URL. Explain how your authorization missed this and close the gap for every endpoint, not just this one.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Finds an endpoint where the policy wasn\'t applied (or checked only the role, not the ward), applies the ward-scoped policy consistently (e.g. route model binding plus authorize), and adds tests per endpoint.',
            'hint' => 'List every endpoint that returns patient data and check each one.',
            'criteria' => [
                ['dim_implementation', 'Consistent authorization', 'Ward scoping is applied to every patient endpoint with tests.', 'Fixing one endpoint only is developing.', ['All endpoints covered with tests and a mechanism that makes forgetting hard.', 'All endpoints covered.', 'Only the reported endpoint fixed.', 'Not fixed.']],
            ],
        ]);

        $auditFix = $this->adaptiveTask('consequence', $s3, $rubric, 4, [
            'title' => 'Follow-up: an audit entry was edited',
            'brief' => "During an investigation the DPO noticed an audit entry's timestamp had been changed through the admin panel. Explain how that was possible and what you will change so the audit log really is immutable.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Removes any update path in the app (admin resource, model fillable), blocks updates at the database level (permissions or trigger), and adds a test that updating fails.',
            'hint' => 'Look for any generic admin screen or model method that can update rows.',
            'criteria' => [
                ['dim_design', 'Real immutability', 'Removes app-level update paths and adds a database-level guarantee.', 'App-only fixes are proficient at best.', ['App and database-level protection with a test.', 'App-level update paths removed.', 'Hides the button only.', 'Not addressed.']],
            ],
        ]);

        $s3Focus = $this->adaptiveTask('suggestion', $s3, $rubric, 5, [
            'title' => 'Development focus: thinking like an attacker',
            'brief' => "Pick one endpoint in MedQueue. List three ways someone could misuse it (for example by changing IDs, replaying requests, or sending unexpected values) and how your code prevents each.",
            'domain' => 'security', 'roles' => $back, 'deliver' => 'written',
            'answer' => 'Three realistic misuse cases (IDOR, replay, mass assignment or injection) with specific protections.',
            'hint' => 'What happens if you change a number in the URL?',
            'criteria' => [
                ['dim_problem_analysis', 'Threat thinking', 'Identifies realistic misuse and matching protections.', 'Protections must match the threats.', ['Three realistic threats with specific protections.', 'Three threats with protections.', 'Generic threats.', 'None.']],
            ],
        ]);

        $this->linkAdaptive($rbac, [$rbacFix], [$s3Focus]);
        $this->linkAdaptive($audit, [$auditFix], [$s3Focus]);
    }

    private function shiftBoard(): void
    {
        $project = ProjectTemplateModel::where('title', 'ShiftBoard — Staff Scheduling for Retail Teams')->firstOrFail();
        $rubric = RubricSetModel::findOrFail($project->rubric_set_id);
        $back = ['backend'];
        $front = ['frontend'];
        $both = ['backend', 'frontend'];

        // Scenario 1.
        $s1 = $this->scenarioAt($project, 1);
        $submit = $this->taskAt($s1, 1);
        $approve = $this->taskAt($s1, 2);
        $form = $this->taskAt($s1, 3);

        $submitFix = $this->adaptiveTask('consequence', $s1, $rubric, 4, [
            'title' => 'Follow-up: time-off requests for last year',
            'brief' => "Marcus found a pending time-off request for a date last December and another with no date at all. Both came through the submission endpoint. Tighten the validation, add tests for each case, and explain what a staff member now sees when they make these mistakes.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Requires dates, rejects past dates, validates type, returns friendly messages, and adds tests for missing, past, today and future dates.',
            'hint' => 'Is a request for today allowed? Decide and test it.',
            'criteria' => [
                ['dim_testing', 'Validation tests', 'Tests cover each invalid case and the boundary of "today".', 'Needs the today boundary.', ['All cases plus today boundary, with messages checked.', 'All reported cases tested.', 'Partial tests.', 'No tests.']],
            ],
        ]);

        $approveFix = $this->adaptiveTask('consequence', $s1, $rubric, 5, [
            'title' => 'Follow-up: a staff member approved their own request',
            'brief' => "Priya noticed that her colleague approved his own time-off request by calling the approval URL directly. Only managers of the same store should be able to approve. Fix it and explain how you tested it.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Adds an authorization check (manager role and same store, not the requester) returning 403, with tests for staff, other-store manager and correct manager.',
            'hint' => 'Hiding the button is not the same as protecting the endpoint.',
            'criteria' => [
                ['dim_implementation', 'Authorization', 'Only managers of the same store can approve; self-approval is blocked.', 'Server-side check with 403 required.', ['Complete rule with tests for each actor.', 'Correct rule on the server.', 'UI-only or partial rule.', 'Not fixed.']],
            ],
        ]);

        $formFix = $this->adaptiveTask('consequence', $s1, $rubric, 6, [
            'title' => 'Follow-up: staff can\'t submit the form on their phones',
            'brief' => "Three staff members say the request form \"does nothing\" on their phones. On desktop it works. Investigate likely causes, fix the form so it works and gives feedback on a phone, and explain how you tested it.",
            'domain' => 'frontend', 'roles' => $front,
            'answer' => 'Checks layout/overflow hiding the submit button, date input support, validation errors not visible on small screens; fixes with responsive layout, visible errors and a loading state; tests at phone width and on a real device or emulator.',
            'hint' => 'Open the form at 375px wide and submit it with a mistake in it.',
            'criteria' => [
                ['dim_debugging', 'Mobile diagnosis', 'Identifies plausible mobile-specific causes and verifies the fix at phone size.', 'Must describe how it was tested on a small screen.', ['Specific causes, fixes, and phone testing described.', 'Fix with phone testing.', 'Fix without testing on a phone.', 'Not fixed.']],
            ],
        ]);

        $s1Focus = $this->adaptiveTask('suggestion', $s1, $rubric, 7, [
            'title' => 'Development focus: never trust the client',
            'brief' => "Explain, using ShiftBoard as the example, why every rule must be checked on the server even if the form already checks it. Give two concrete examples of what could go wrong otherwise.",
            'domain' => 'security', 'roles' => $both, 'deliver' => 'written',
            'answer' => 'Explains that requests can be crafted directly, with examples like self-approval or invalid dates via a direct API call.',
            'hint' => 'Anyone can send a request without using your form.',
            'criteria' => [
                ['dim_problem_analysis', 'Server-side thinking', 'Explains why client checks are not enough, with concrete examples.', 'Examples must be specific to the project.', ['Clear reasoning with two project-specific examples.', 'Reasoning with one example.', 'Generic reasoning.', 'No understanding.']],
            ],
        ]);

        $this->linkAdaptive($submit, [$submitFix], [$s1Focus]);
        $this->linkAdaptive($approve, [$approveFix], [$s1Focus]);
        $this->linkAdaptive($form, [$formFix], [$s1Focus]);

        // Scenario 2: add a frontend task alongside the race-condition fix.
        $s2 = $this->scenarioAt($project, 2);
        $race = $this->taskAt($s2, 1);

        $claimUi = $this->coreTask($s2, $rubric, 2, [
            'title' => 'Show a clear message when a shift is already taken',
            'brief' => "When two people try to claim the same shift, the second now gets an error from the server. Update the open-shifts page so that person sees a clear, friendly explanation, the list refreshes to show the shift is taken, and they can pick another shift without starting over.",
            'domain' => 'frontend', 'roles' => $front, 'tools' => ['blade', 'alpine.js'], 'concepts' => ['error states', 'user feedback'],
            'answer' => 'Handles the conflict response specifically (not a generic error), shows a human message, refreshes the list, keeps the user on the page, and prevents double submission with a busy state.',
            'starter' => 'Find out exactly what the server returns when the shift is already taken, and handle that case separately from other errors.',
            'stretch' => 'Suggest how the page could warn people before they click, when a shift has just been claimed by someone else.',
            'hint_low' => 'What should the button do while the request is in progress?',
            'hint_mid' => 'Treat "already taken" differently from "something went wrong".',
            'criteria' => [
                ['dim_implementation', 'Conflict handling', 'The taken-shift case is handled specifically and the list updates.', 'Generic "error" handling is developing.', ['Specific handling, refreshed list, busy state, no lost context.', 'Specific handling with refreshed list.', 'Generic error only.', 'No handling.']],
                ['dim_communication', 'Helpful message', 'The message tells the person what happened and what to do next.', 'Look for plain, actionable wording.', ['Clear, kind, actionable wording with reasoning.', 'Clear and actionable.', 'Technical or vague.', 'No message.']],
            ],
        ]);

        $raceFix = $this->adaptiveTask('consequence', $s2, $rubric, 3, [
            'title' => 'Follow-up: double-booking is back after the lock fix',
            'brief' => "Two cashiers were again both approved for Saturday's 06:00 shift, even though the lock is in place. The code path that approves swaps between staff was added last week. Find the gap and fix it so every path that assigns a shift is safe.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Finds the swap path assigning shifts outside the locked transaction, moves assignment into one guarded method used by every path, and adds a concurrency-oriented test or explanation.',
            'hint' => 'List every place in the code that sets who works a shift.',
            'criteria' => [
                ['dim_design', 'Single guarded path', 'All shift assignments go through one concurrency-safe path.', 'Patching only the swap path is proficient; centralising is distinguished.', ['Centralised, guarded assignment used everywhere.', 'Swap path made safe.', 'Partial fix.', 'Not fixed.']],
            ],
        ]);

        $claimUiFix = $this->adaptiveTask('consequence', $s2, $rubric, 4, [
            'title' => 'Follow-up: staff think their claim worked when it didn\'t',
            'brief' => "A staff member showed up for a shift she thought she'd claimed. The page had shown \"Claiming…\" and then nothing. Find out why no result was shown, fix it, and make sure every outcome ends in a clear message.",
            'domain' => 'frontend', 'roles' => $front,
            'answer' => 'Finds an unhandled error/timeout path that leaves the busy state forever, handles success, conflict, validation, network error and timeout, always clearing the busy state with a message.',
            'hint' => 'List every way the request can end, including no response at all.',
            'criteria' => [
                ['dim_implementation', 'Every outcome handled', 'All request outcomes end in a clear state.', 'Must include network failure.', ['All outcomes including timeout handled and tested.', 'Main outcomes handled.', 'Only success and one error.', 'Not fixed.']],
            ],
        ]);

        $s2Focus = $this->adaptiveTask('suggestion', $s2, $rubric, 5, [
            'title' => 'Development focus: design for the unhappy path',
            'brief' => "For one feature in ShiftBoard, list every way it can go wrong for the user (slow network, conflict, invalid input, server error) and describe what the user should see in each case.",
            'domain' => 'design', 'roles' => $both, 'deliver' => 'written',
            'answer' => 'A list of failure modes with a specific user-facing response for each.',
            'hint' => 'The happy path is usually the smallest part of the design.',
            'criteria' => [
                ['dim_design', 'Failure-mode design', 'Covers realistic failure modes with specific user-facing outcomes.', 'Needs at least four distinct failure modes.', ['Four or more modes with specific, kind responses.', 'Several modes with responses.', 'Few modes.', 'None.']],
            ],
        ]);

        $this->linkAdaptive($race, [$raceFix], [$s2Focus]);
        $this->linkAdaptive($claimUi, [$claimUiFix], [$s2Focus]);

        // Scenario 3: add a frontend task alongside overtime validation.
        $s3 = $this->scenarioAt($project, 3);
        $overtime = $this->taskAt($s3, 1);

        $hoursUi = $this->coreTask($s3, $rubric, 2, [
            'title' => 'Show staff their weekly hours before they claim',
            'brief' => "Staff keep getting rejected for going over 40 hours without knowing how close they were. On the open-shifts page, show each person their hours this week and how many hours a shift would add, and warn them clearly before they claim a shift that would take them over the limit.",
            'domain' => 'frontend', 'roles' => $front, 'tools' => ['blade', 'alpine.js'], 'concepts' => ['derived values', 'warnings vs errors'],
            'answer' => 'Displays current weekly hours and the projected total per shift, flags shifts that would exceed 40 with text and icon, keeps the server as the source of truth, and uses the same week definition as the server.',
            'starter' => 'Get the current weekly total from the server rather than recalculating it in the browser.',
            'stretch' => 'Explain what should happen if a manager changes someone\'s shift while they are looking at this page.',
            'hint_low' => 'Which is more helpful: an error after clicking, or a warning before?',
            'hint_mid' => 'Make sure the browser and server agree on what "this week" means.',
            'criteria' => [
                ['dim_implementation', 'Accurate hours', 'Current and projected hours are correct and consistent with the server rule.', 'Recomputing the week differently from the server is developing.', ['Correct figures from a single source of truth with clear warnings.', 'Correct figures and warnings.', 'Figures sometimes wrong.', 'Not working.']],
                ['dim_design', 'Warning design', 'Warnings are clear, shown before the action, and not colour-only.', 'Look for text plus icon and placement near the action.', ['Clear, well-placed, accessible warnings with reasoning.', 'Clear warnings.', 'Unclear or colour-only.', 'No warnings.']],
            ],
        ]);

        $overtimeFix = $this->adaptiveTask('consequence', $s3, $rubric, 3, [
            'title' => 'Follow-up: overtime limit blocked a legal shift',
            'brief' => "Priya had 36 hours and tried to claim a 4-hour shift, which would make exactly 40. She was rejected. HR confirms 40 is allowed; over 40 is not. Fix the check, and explain which other boundaries you tested.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Changes >= to > (or equivalent), adds tests at 39.5, 40 and 40.5 hours, and checks the week boundary.',
            'hint' => 'Is "over 40" the same as "40 or more"?',
            'criteria' => [
                ['dim_testing', 'Boundary fix', 'Fixes the inclusive/exclusive mistake and tests around 40 hours.', 'Needs tests on both sides of 40.', ['Fix plus tests below, on and above 40 and at the week edge.', 'Fix plus test at exactly 40.', 'Fix only.', 'Not fixed.']],
            ],
        ]);

        $hoursUiFix = $this->adaptiveTask('consequence', $s3, $rubric, 4, [
            'title' => 'Follow-up: the page says 38 hours, the server says 42',
            'brief' => "A staff member was shown \"38 hours this week\" but the server rejected her claim for exceeding 40. It turns out the page and the server count the week differently. Find the difference and make sure they can never disagree again.",
            'domain' => 'frontend', 'roles' => $front,
            'answer' => 'Identifies differing week starts or time zones, and removes the duplicate calculation by fetching the figure from the server.',
            'hint' => 'Does your week start on Sunday or Monday? Does the server\'s?',
            'criteria' => [
                ['dim_debugging', 'One source of truth', 'Finds the mismatch and removes the duplicated rule.', 'Aligning both calculations is proficient; removing the duplicate is distinguished.', ['Mismatch found and the calculation lives in one place.', 'Mismatch found and aligned.', 'Partial fix.', 'Not found.']],
            ],
        ]);

        $s3Focus = $this->adaptiveTask('suggestion', $s3, $rubric, 5, [
            'title' => 'Development focus: one rule, one place',
            'brief' => "Find a business rule in ShiftBoard that could end up implemented in more than one place. Explain the risk and how you would structure the code so the rule lives in exactly one place.",
            'domain' => 'design', 'roles' => $both, 'deliver' => 'written',
            'answer' => 'Names a rule (e.g. weekly hours, week start), explains drift risk, and proposes a single service/endpoint used by all callers.',
            'hint' => 'Think about the 40-hour rule.',
            'criteria' => [
                ['dim_design', 'Avoiding duplication', 'Explains drift risk and proposes a single home for the rule.', 'Must be project-specific.', ['Specific rule, clear risk, concrete structure.', 'Specific rule and structure.', 'Generic answer.', 'No understanding.']],
            ],
        ]);

        $this->linkAdaptive($overtime, [$overtimeFix], [$s3Focus]);
        $this->linkAdaptive($hoursUi, [$hoursUiFix], [$s3Focus]);

        $this->backlogItem($project, $claimUi->title, strtok($claimUi->task_brief, "\n"), 'must_have', $front, $claimUi, 10);
        $this->backlogItem($project, $hoursUi->title, strtok($hoursUi->task_brief, "\n"), 'should_have', $front, $hoursUi, 11);
    }

    private function scenarioAt(ProjectTemplateModel $project, int $sequence): ScenarioTemplateModel
    {
        return ScenarioTemplateModel::where('project_id', $project->id)->where('sequence_order', $sequence)->firstOrFail();
    }

    private function taskAt(ScenarioTemplateModel $scenario, int $sequence): TaskModel
    {
        return TaskModel::where('scenario_id', $scenario->id)->where('sequence_order', $sequence)->firstOrFail();
    }
}
