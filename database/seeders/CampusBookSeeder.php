<?php
namespace Database\Seeders;

use Database\Seeders\Concerns\AuthorsSimulationContent;
use Illuminate\Database\Seeder;

/**
 * CampusBook — beginner project with backend, frontend and QA roles. Heavy on
 * boundaries, overlaps and bug reporting, so testing and communication
 * dimensions get real evidence, not just implementation.
 */
class CampusBookSeeder extends Seeder
{
    use AuthorsSimulationContent;

    public function run(): void
    {
        $this->role('Associate QA Engineer', ['qa'], 0, [
            'dim_problem_analysis' => 0.20, 'dim_design' => 0.05, 'dim_implementation' => 0.10,
            'dim_testing' => 0.35, 'dim_debugging' => 0.15, 'dim_communication' => 0.15,
        ]);

        [$project, $rubric] = $this->project('CampusBook — Study Room Reservations', [
            'tagline'          => 'Replace a paper sign-up sheet for 12 library study rooms.',
            'project_type'     => 'web_application',
            'business_domain'  => 'education',
            'business_context' => "Northgate College library has 12 bookable study rooms. Today students write their name on a paper sheet taped to each door. Rooms are double-booked, some students hold rooms all day, and staff spend an hour every morning settling arguments.\n\nThe library wants students to book rooms online, see what is free, and follow a fair-use policy.",
            'stakeholders'     => [
                ['name' => 'Dr. Helen Park', 'role' => 'Head Librarian', 'priority' => 'high', 'concern' => 'fair access for all students'],
                ['name' => 'Sam Ortiz', 'role' => 'Student Union Rep', 'priority' => 'medium', 'concern' => 'fast booking on a phone'],
                ['name' => 'Library IT (Joe)', 'role' => 'Support', 'priority' => 'medium', 'concern' => 'bug reports he can actually act on'],
            ],
            'overarching_constraints' => [
                ['type' => 'business', 'description' => 'Rooms open 08:00–22:00. A booking can be at most 2 hours long.'],
                ['type' => 'business', 'description' => 'Each student may book at most 6 hours per week.'],
                ['type' => 'technical', 'description' => 'Most students will book from their phone.'],
            ],
            'tech_context'        => ['existing_stack' => ['PHP', 'Laravel', 'MySQL', 'Blade', 'Alpine.js'], 'team_size' => 3, 'deployment_environment' => 'college server', 'special_requirements' => null],
            'specialization_tags' => ['backend', 'frontend', 'qa'],
            'coding_guidelines'   => 'Times are stored in the college time zone. Every business rule gets at least one automated test. Bug reports use the template in the vault.',
            'velocity_estimate'   => ['sprint_days' => 5],
            'difficulty_level'    => 'beginner',
        ]);

        $this->vaultItem($project, 'prd', 'CampusBook PRD', "Students book a room for a time slot. Rules: opening hours 08:00–22:00; max 2 hours per booking; no overlapping bookings in the same room (a booking ending at 14:00 and one starting at 14:00 do NOT overlap); max 6 booked hours per student per week (Monday–Sunday). Cancelled bookings do not count towards the weekly limit.", 1);
        $this->vaultItem($project, 'coding_guidelines', 'Bug report template', "Title: short, specific\nSteps to reproduce: numbered, with exact data\nExpected result\nActual result\nEnvironment: device, browser, account\nSeverity: blocker / major / minor\nEvidence: screenshot or log lines", 2);
        $this->vaultItem($project, 'sprint_goal_template', 'Sprint goal template', 'By the end of this sprint, students will be able to ___, and the library will see ___.', 3);

        $back = ['backend'];
        $front = ['frontend'];
        $qa = ['qa', 'frontend'];
        $all = ['backend', 'frontend', 'qa'];

        // ── Scenario 1 ───────────────────────────────────────────────────────
        $s1 = $this->scenario($project, 1, [
            'title'        => 'Sprint 1 — Booking a Room',
            'narrative'    => 'The team has a rooms table and a login page. This sprint is about the core booking flow, and getting the rules right from day one so the library trusts it.',
            'trigger'      => "Meeting summary (Sprint planning): Helen wants online booking live for the start of term. Rules are in the PRD. Joe asked that every rule is tested, because the last system \"worked in the demo and broke on day two\".",
            'trigger_type' => 'meeting_summary',
            'role_label'   => 'Junior Developer',
            'autonomy'     => 'low',
        ], [
            ['type' => 'ticket', 'title' => 'CB-2: Book a study room', 'content' => "As a student I want to book a room for a time slot.\n\nAcceptance criteria:\n- Start and end time are required and end must be after start.\n- Bookings must be within 08:00–22:00.\n- A booking is at most 2 hours (exactly 2 hours is allowed).\n- Show a clear error explaining which rule was broken.", 'signals' => ['exactly 2 hours allowed', 'boundary at 22:00']],
        ]);

        $s1a = $this->coreTask($s1, $rubric, 1, [
            'title' => 'Create a booking with the library\'s rules',
            'brief' => "Implement POST /bookings for ticket CB-2. Enforce every acceptance criterion and return a clear, specific error for each broken rule. Explain how you handled the edges (exactly 2 hours, ending at exactly 22:00).",
            'domain' => 'backend', 'roles' => $back, 'tools' => ['php', 'laravel'], 'concepts' => ['validation', 'date and time comparison'],
            'answer' => 'Validates required fields, end after start, start ≥ 08:00 and end ≤ 22:00, and duration ≤ 120 minutes (inclusive). Each rule has its own message. The explanation discusses inclusive boundaries.',
            'starter' => 'Write each acceptance criterion as its own rule and give each its own error message.',
            'stretch' => 'A student in a different time zone (studying abroad) books a room. Explain how you make sure the opening-hours check uses library time.',
            'hint_low' => 'Is a booking from 20:00 to 22:00 allowed? Check the ticket.',
            'hint_mid' => 'Look carefully at which limits are inclusive.',
            'criteria' => [
                ['dim_implementation', 'Rules enforced', 'All booking rules are enforced with specific error messages.', 'Inclusive boundaries matter: exactly 2 hours and ending at 22:00 are allowed. Getting either wrong is at most developing.', ['All rules correct including inclusive boundaries, with specific messages.', 'All rules correct.', 'A boundary is wrong or a rule is missing.', 'Only the happy path works.']],
                ['dim_problem_analysis', 'Edge-case thinking', 'Explicitly reasons about the edges of each rule.', 'Look for discussion of 2h exactly and 22:00 exactly.', ['Discusses all edges with reasons.', 'Discusses the main edges.', 'Mentions edges vaguely.', 'No edge-case thinking.']],
            ],
        ]);

        $s1b = $this->coreTask($s1, $rubric, 2, [
            'title' => 'Write the test plan for booking',
            'brief' => "Before the booking form ships, Joe wants a test plan. Write the test cases you would run for CB-2: for each, the input, the expected result, and which rule it checks. Include normal cases, boundary cases and invalid input.",
            'domain' => 'testing', 'roles' => $qa, 'deliver' => 'written', 'concepts' => ['test design', 'boundary value analysis'],
            'answer' => 'A structured list covering: valid booking; exactly 2h; 2h01; start 07:59 vs 08:00; end 22:00 vs 22:01; end before start; end equal to start; missing fields. Each case has an expected result and rule.',
            'starter' => 'For each rule, test the value just inside, exactly on, and just outside the limit.',
            'stretch' => 'Mark which cases you would automate and which you would check by hand on a phone, and explain why.',
            'hint_low' => 'A table with columns Input | Expected | Rule works well.',
            'hint_mid' => 'Don\'t forget invalid input like missing times.',
            'criteria' => [
                ['dim_testing', 'Coverage of boundaries', 'Test cases cover every rule at its boundaries with expected results.', 'Look for on-the-boundary cases (08:00, 22:00, exactly 120 min) and just-outside cases.', ['Every rule covered at and around its boundaries, all with expected results.', 'Most boundaries covered with expected results.', 'Only happy path plus a few invalid cases.', 'No real test cases.']],
                ['dim_problem_analysis', 'Understanding the rules', 'Cases show correct understanding of the rules (e.g. 2h exactly is valid).', 'Expected results must match the ticket.', ['All expected results correct with rule references.', 'Expected results correct.', 'Some expected results wrong.', 'Rules misunderstood.']],
            ],
        ]);

        $s1aFix = $this->adaptiveTask('consequence', $s1, $rubric, 3, [
            'title' => 'Follow-up: a booking ends before it starts',
            'brief' => "Room 4 shows a booking from 15:00 to 13:00, and it blocks the room for the whole afternoon. Find out how it got past the rules, fix it, and add automated tests for the case and its neighbours (end equal to start, end one minute after start).",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Adds or fixes the end-after-start rule and adds tests for end < start, end = start (rejected) and end = start + 1 minute (accepted).',
            'hint' => 'Test the equal case too. Is a 0-minute booking valid?',
            'criteria' => [
                ['dim_testing', 'Regression tests', 'Adds tests for the reported case and its boundaries.', 'Must include the end = start case.', ['Tests for all three neighbouring cases and the fix explained.', 'Tests for the reported case and one neighbour.', 'Only the reported case.', 'No tests.']],
            ],
        ]);

        $s1bFix = $this->adaptiveTask('consequence', $s1, $rubric, 4, [
            'title' => 'Follow-up: a bug slipped past the test plan',
            'brief' => "Students report they can't book 20:00–22:00; they get \"Rooms close at 22:00\". Nobody caught this in testing. Explain which test case was missing from the plan, why it was missed, and rewrite that part of the plan.",
            'domain' => 'testing', 'roles' => $qa, 'deliver' => 'written',
            'answer' => 'Identifies the missing "end exactly at closing time" case, explains the difference between inclusive and exclusive limits, and adds cases at, just before and just after 22:00.',
            'hint' => 'Look at the edge of the opening-hours rule, not the middle.',
            'criteria' => [
                ['dim_testing', 'Learning from the miss', 'Identifies the missing boundary case and fixes the plan systematically.', 'Look for a general lesson (test ON the boundary), not only this one case.', ['Missing case identified, root cause in the plan explained, and plan fixed for all similar limits.', 'Missing case identified and added.', 'Vague explanation.', 'Not identified.']],
            ],
        ]);

        $s1Focus = $this->adaptiveTask('suggestion', $s1, $rubric, 5, [
            'title' => 'Development focus: boundary value analysis',
            'brief' => "Pick any rule with a limit in this project (opening hours, 2-hour maximum, weekly hours). List the values you would test and explain why bugs cluster at boundaries.",
            'domain' => 'testing', 'roles' => $all, 'deliver' => 'written',
            'answer' => 'Lists below, on and above the boundary for the chosen rule and explains off-by-one and inclusive/exclusive mistakes.',
            'hint' => 'Three values per boundary is a good habit.',
            'criteria' => [
                ['dim_testing', 'Boundary thinking', 'Chooses the right values around a boundary and explains why.', 'Needs below/on/above.', ['Correct values and a clear explanation of off-by-one risk.', 'Correct values.', 'Some values missing.', 'No boundary values.']],
            ],
        ]);

        $this->linkAdaptive($s1a, [$s1aFix], [$s1Focus]);
        $this->linkAdaptive($s1b, [$s1bFix], [$s1Focus]);

        // ── Scenario 2 ───────────────────────────────────────────────────────
        $s2 = $this->scenario($project, 2, [
            'title'        => 'Sprint 2 — No More Double Bookings',
            'narrative'    => 'Bookings are live and students love it, but on the first busy Monday two groups turned up for Room 7 at the same time, each holding a confirmation.',
            'trigger'      => "Ticket from Library IT: \"Room 7, Monday 10:00. Two confirmed bookings: 09:30–11:00 and 10:00–12:00. Students were not happy. Please stop overlapping bookings. Also, can students see which rooms are free before trying to book?\"",
            'trigger_type' => 'ticket',
            'role_label'   => 'Developer',
        ], [
            ['type' => 'document', 'title' => 'What counts as an overlap', 'content' => "Two bookings in the same room overlap if one starts before the other ends AND ends after the other starts.\n\nExamples for an existing booking 10:00–12:00:\n- 09:00–10:00: allowed (touches, does not overlap)\n- 12:00–13:00: allowed\n- 11:00–13:00: overlaps\n- 09:00–13:00: overlaps (surrounds)\n- 10:30–11:30: overlaps (inside)", 'signals' => ['touching bookings are allowed', 'surrounding and inside cases']],
        ]);

        $s2a = $this->coreTask($s2, $rubric, 1, [
            'title' => 'Prevent overlapping bookings',
            'brief' => "Stop new bookings that overlap an existing booking in the same room, using the definition in the reference materials. Back-to-back bookings must still work. Explain your overlap check and prove it with tests for every example given.",
            'domain' => 'backend', 'roles' => $back, 'tools' => ['php', 'laravel', 'sql'], 'concepts' => ['interval overlap', 'query conditions'],
            'answer' => 'Uses the overlap condition new.start < existing.end AND new.end > existing.start, scoped to the same room and ignoring cancelled bookings, with tests for touching, partial, surrounding and inside cases.',
            'starter' => 'Draw the five examples on a timeline first, then write one condition that matches the three overlaps and not the two touches.',
            'stretch' => 'Two students press "Book" for the same slot at the same moment. Explain how your solution behaves and make it safe.',
            'hint_low' => 'Only two comparisons are needed.',
            'hint_mid' => 'Should cancelled bookings block a room?',
            'criteria' => [
                ['dim_implementation', 'Correct overlap rule', 'Overlaps are rejected and touching bookings are allowed, per room.', 'Using <= instead of < rejects back-to-back bookings, which is at most developing.', ['Correct condition, scoped per room, ignores cancelled bookings, safe under concurrency.', 'Correct condition scoped per room.', 'Condition wrong at the touching boundary or misses the surrounding case.', 'No overlap check.']],
                ['dim_testing', 'Proving it', 'Tests cover every example in the reference materials.', 'All five examples should be tests.', ['All examples plus a different-room case.', 'All five examples.', 'Some examples.', 'No tests.']],
            ],
        ]);

        $s2b = $this->coreTask($s2, $rubric, 2, [
            'title' => 'Show room availability for a day',
            'brief' => "Build a view where a student picks a room and a date and sees the day from 08:00 to 22:00, with booked and free time clearly shown. Tapping a free slot should start a booking with the times filled in. Handle loading, no bookings and errors.",
            'domain' => 'frontend', 'roles' => $front, 'tools' => ['blade', 'alpine.js', 'css'], 'concepts' => ['ui states', 'responsive layout'],
            'answer' => 'A day timeline with clear booked/free distinction (not colour-only), tap-to-book pre-filling times, and explicit loading, empty and error states that work on a phone.',
            'starter' => 'Sketch the three states (loading, empty day, error) before building the full timeline.',
            'stretch' => 'Explain how the page would behave if someone else books the slot while the student is looking at it.',
            'hint_low' => 'What does the page show before the data has loaded?',
            'hint_mid' => 'Most students are on a phone. Check the timeline at 375px wide.',
            'criteria' => [
                ['dim_implementation', 'Working availability view', 'Shows booked and free time correctly and pre-fills the booking form.', 'Check time alignment and that free slots respect opening hours.', ['Accurate view, pre-filling works, all states handled.', 'Accurate view with pre-filling.', 'View works but pre-fill or states are missing.', 'View does not work.']],
                ['dim_design', 'Clear states on a phone', 'Loading, empty and error states are designed and the layout works on a phone.', 'Look for each state and mobile layout reasoning.', ['All states designed with mobile reasoning.', 'Most states designed.', 'Only the happy path.', 'Unusable on a phone.']],
            ],
        ]);

        $s2aFix = $this->adaptiveTask('consequence', $s2, $rubric, 3, [
            'title' => 'Follow-up: back-to-back bookings are rejected',
            'brief' => "After the overlap fix, a study group can no longer book Room 3 from 12:00–14:00 because someone has 10:00–12:00. Students are complaining that the rooms look booked when they aren't. Find the cause, fix it, and add the missing test.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Finds a <= / >= comparison treating touching intervals as overlapping, switches to strict < / >, and adds a touching-bookings test on both sides.',
            'hint' => 'Compare 12:00 with 12:00 in your condition.',
            'criteria' => [
                ['dim_debugging', 'Boundary bug', 'Finds and fixes the inclusive comparison and adds tests.', 'Must identify the specific operator.', ['Operator identified, fixed, with tests on both touching sides.', 'Operator fixed with a test.', 'Fix without understanding why.', 'Not fixed.']],
            ],
        ]);

        $s2bFix = $this->adaptiveTask('consequence', $s2, $rubric, 4, [
            'title' => 'Follow-up: the timeline shows yesterday\'s bookings',
            'brief' => "Sam reports: \"I picked Friday and the timeline showed Thursday's bookings. Refreshing fixed it.\" Investigate what could cause this, explain your most likely cause, and fix it.",
            'domain' => 'frontend', 'roles' => $front,
            'answer' => 'Identifies a stale state problem (e.g. an old request finishing after a newer one, or the date not being part of the cache key) and fixes it by tying results to the selected date and ignoring stale responses.',
            'hint' => 'What happens if the student changes the date twice quickly?',
            'criteria' => [
                ['dim_debugging', 'Stale data diagnosis', 'Gives a plausible, specific cause and a fix that addresses it.', 'Race between requests or caching by room only are good answers.', ['Specific cause, fix, and a way to reproduce it.', 'Specific cause and fix.', 'Plausible guess without a fix.', 'No diagnosis.']],
            ],
        ]);

        $s2Focus = $this->adaptiveTask('suggestion', $s2, $rubric, 5, [
            'title' => 'Development focus: draw it before you code it',
            'brief' => "For a rule involving time ranges (like overlaps), explain how drawing the cases on a timeline before coding helps. Draw (in text) the five overlap examples and label which should pass.",
            'domain' => 'problem_solving', 'roles' => $all, 'deliver' => 'written',
            'answer' => 'Text timelines for each example with pass/fail labels and a reflection on catching boundary mistakes early.',
            'hint' => 'Something like |----| for each booking works fine.',
            'criteria' => [
                ['dim_problem_analysis', 'Visual reasoning', 'Uses a timeline to reason about cases correctly.', 'Labels must match the rule.', ['All examples drawn and labelled correctly with reflection.', 'All examples labelled correctly.', 'Some mistakes.', 'No attempt.']],
            ],
        ]);

        $this->linkAdaptive($s2a, [$s2aFix], [$s2Focus]);
        $this->linkAdaptive($s2b, [$s2bFix], [$s2Focus]);

        // ── Scenario 3 ───────────────────────────────────────────────────────
        $s3 = $this->scenario($project, 3, [
            'title'        => 'Sprint 3 — Fair Use and Better Bug Reports',
            'narrative'    => 'Exam season. A handful of students are booking rooms for most of the week, and Library IT is drowning in vague bug reports like "it doesn\'t work".',
            'trigger'      => "Email from Dr. Helen Park: \"We need the 6-hours-a-week limit enforced before exams start on Monday. And Joe tells me he can't act on half the bug reports we get. Could someone show us what a good one looks like?\"",
            'trigger_type' => 'email',
            'role_label'   => 'Developer',
            'autonomy'     => 'high',
        ], [
            ['type' => 'email', 'title' => 'Forwarded complaint from a student', 'content' => "From: priya.n@northgate.ac\nSubject: BROKEN!!!\n\nthe booking thing doesnt work, i tried loads of times and it just says error. fix it please i have an exam\n\n---\nServer log around that time:\n[2026-05-12 14:03:11] POST /bookings 422 room=9 start=2026-05-12T14:00 end=2026-05-12T16:30 user=5521\n[2026-05-12 14:03:40] POST /bookings 422 room=9 start=2026-05-12T14:00 end=2026-05-12T16:30 user=5521\n[2026-05-12 14:04:02] POST /bookings 422 room=9 start=2026-05-12T14:00 end=2026-05-12T16:15 user=5521", 'signals' => ['bookings over 2 hours', 'error message not clear to student']],
        ]);

        $s3a = $this->coreTask($s3, $rubric, 1, [
            'title' => 'Enforce the weekly booking limit',
            'brief' => "Enforce the 6-hours-per-week limit from the PRD. When a booking would go over the limit, reject it with a message that tells the student how many hours they have left this week. Explain exactly how you defined 'this week' and which bookings count.",
            'domain' => 'backend', 'roles' => $back, 'tools' => ['php', 'laravel', 'sql'], 'concepts' => ['aggregation', 'date ranges'],
            'answer' => 'Sums durations of the student\'s non-cancelled bookings from Monday 00:00 to Sunday 23:59 (library time) that contain the new booking\'s start, adds the new duration, compares to 360 minutes, and reports hours remaining.',
            'starter' => 'Write down in plain words which bookings count before writing the query.',
            'stretch' => 'The library is considering 24-hour opening during exams. Explain what your rule would do with a booking from Sunday 23:00 to Monday 01:00, and what you would recommend.',
            'hint_low' => 'Do cancelled bookings count? Check the PRD.',
            'hint_mid' => 'Which week does a booking belong to: the week it was made, or the week it takes place?',
            'criteria' => [
                ['dim_implementation', 'Correct limit', 'The weekly total is calculated correctly and the limit enforced with a helpful message.', 'Cancelled bookings must be excluded; the week must be the week of the booking, not of creation.', ['Correct calculation, correct week, helpful message with remaining hours.', 'Correct calculation and enforcement.', 'Counts cancelled bookings or uses the wrong week.', 'Limit not enforced.']],
                ['dim_problem_analysis', 'Defining the rule', 'Clearly defines "this week" and which bookings count.', 'Look for explicit definitions and time-zone awareness.', ['Precise definitions including time zone.', 'Clear definitions.', 'Partial definitions.', 'No definitions.']],
            ],
        ]);

        $s3b = $this->coreTask($s3, $rubric, 2, [
            'title' => 'Turn a complaint into a useful bug report',
            'brief' => "Using the forwarded complaint and server log in the reference materials, write the bug report Joe needs, using the template in the vault. Then explain what the real problem is (for the student and for the product) and what you would change.",
            'domain' => 'testing', 'roles' => $qa, 'deliver' => 'written', 'concepts' => ['bug reporting', 'log reading'],
            'answer' => 'A complete report: specific title, numbered steps with exact room/times, expected vs actual, environment, severity, log evidence. Identifies that the bookings exceed the 2-hour limit and the real product bug is an unclear error message.',
            'starter' => 'Read the log lines carefully: what do the three attempts have in common?',
            'stretch' => 'Suggest the exact error message the student should have seen, and how you would test it.',
            'hint_low' => 'Calculate the length of each attempted booking.',
            'hint_mid' => 'Is the system actually broken, or is it failing to explain itself?',
            'criteria' => [
                ['dim_communication', 'A report someone can act on', 'The report follows the template with specific, reproducible detail.', 'Exact steps and data, expected vs actual, and evidence are required for proficient.', ['Complete, precise, reproducible, with evidence and a sensible severity.', 'Complete and reproducible.', 'Missing key parts (steps or expected result).', 'Not a usable report.']],
                ['dim_debugging', 'Reading the evidence', 'Uses the log to find the real cause.', 'The durations are 2h30, 2h30 and 2h15, all over the 2h limit.', ['Finds the cause and reframes it as an unclear-error-message bug.', 'Finds the cause.', 'Suspects the right area without evidence.', 'Cause not found.']],
            ],
        ]);

        $s3aFix = $this->adaptiveTask('consequence', $s3, $rubric, 3, [
            'title' => 'Follow-up: students hit the limit after cancelling',
            'brief' => "Several students say they cancelled bookings but still can't book anything, and they have exams. Investigate, fix, and explain how you'll make sure this rule is covered by a test from now on.",
            'domain' => 'backend', 'roles' => $back,
            'answer' => 'Finds that cancelled bookings are included in the weekly sum, excludes them, and adds a test that cancels a booking and books again.',
            'hint' => 'Look at which bookings your weekly total includes.',
            'criteria' => [
                ['dim_problem_analysis', 'Rule vs implementation', 'Connects the complaint to the rule "cancelled bookings do not count".', 'Must reference the PRD rule.', ['Cause tied to the PRD, fixed, and a test added.', 'Cause found and fixed.', 'Workaround without understanding.', 'Not addressed.']],
            ],
        ]);

        $s3bFix = $this->adaptiveTask('consequence', $s3, $rubric, 4, [
            'title' => 'Follow-up: Joe couldn\'t reproduce your bug',
            'brief' => "Joe replied: \"Tried your steps, booking went through fine for me. What am I missing?\" Rewrite the report so anyone can reproduce the problem first time, and explain what was missing.",
            'domain' => 'testing', 'roles' => $qa, 'deliver' => 'written',
            'answer' => 'Adds the exact times that exceed 2 hours, the room, the account state, and the error text, and explains that vague steps hid the key condition.',
            'hint' => 'Which exact value makes the difference between success and failure?',
            'criteria' => [
                ['dim_communication', 'Reproducible steps', 'The rewritten report contains the exact condition that triggers the bug.', 'The key detail is the booking length.', ['Exact triggering data and a clear explanation of the gap.', 'Exact triggering data.', 'Still vague.', 'Not rewritten.']],
            ],
        ]);

        $s3Focus = $this->adaptiveTask('suggestion', $s3, $rubric, 5, [
            'title' => 'Development focus: writing for another developer',
            'brief' => "Write a short message to a teammate handing over one task you finished in this project: what it does, what is not done, and what to watch out for. Keep it under 150 words.",
            'domain' => 'communication', 'roles' => $all, 'deliver' => 'written',
            'answer' => 'A concise handover covering scope, gaps and risks.',
            'hint' => 'Your teammate will read this without you there to answer questions.',
            'criteria' => [
                ['dim_communication', 'Handover quality', 'Covers what is done, what is not, and risks, concisely.', 'All three parts needed for proficient.', ['All three parts, concise and specific.', 'All three parts.', 'Missing a part.', 'Unclear.']],
            ],
        ]);

        $this->linkAdaptive($s3a, [$s3aFix], [$s3Focus]);
        $this->linkAdaptive($s3b, [$s3bFix], [$s3Focus]);

        $order = 1;
        foreach ([[$s1a, 'must_have', $back], [$s1b, 'must_have', $qa], [$s2a, 'must_have', $back], [$s2b, 'should_have', $front], [$s3a, 'must_have', $back], [$s3b, 'must_have', $qa]] as [$task, $priority, $tags]) {
            $this->backlogItem($project, $task->title, strtok($task->task_brief, "\n"), $priority, $tags, $task, $order++);
        }
        $this->backlogItem($project, 'Email reminders 15 minutes before a booking', 'Nice to have; needs a mail service agreement with college IT.', 'could_have', $back, null, $order++);
        $this->backlogItem($project, 'Room photos and equipment list', 'Student union request. Content not provided yet.', 'wont_have', $front, null, $order);
    }
}
