<?php
namespace Database\Seeders;

use Database\Seeders\Concerns\AuthorsSimulationContent;
use Illuminate\Database\Seeder;

/**
 * PantryLink — beginner project (backend + frontend). A volunteer-run food
 * bank replacing a spreadsheet. Every core task has a consequence task wired
 * for failures, and each scenario has a suggestion task for habit patterns,
 * so the adaptive engine has real content to inject.
 */
class PantryLinkSeeder extends Seeder
{
    use AuthorsSimulationContent;

    private const EXPIRING_SOON_CODE = <<<'PHP'
        // Returns items expiring within the next 3 days
        function expiringSoon($items, $today) {
            $result = [];
            foreach ($items as $item) {
                $daysLeft = (strtotime($item['expiry_date']) - strtotime($today)) / 86400;
                if ($daysLeft > 0 && $daysLeft < 3) {
                    $result[] = $item;
                }
            }
            return $result;
        }

        // Called as: expiringSoon($items, date('Y-m-d H:i:s'));
        PHP;

    public function run(): void
    {
        [$project, $rubric] = $this->project('PantryLink — Community Food Bank Inventory', [
            'tagline'          => 'Help a volunteer-run food bank stop wasting food.',
            'project_type'     => 'web_application',
            'business_domain'  => 'non_profit',
            'business_context' => "Harbourside Community Pantry feeds around 300 families a week. Donations are logged in a shared spreadsheet that volunteers edit on a tablet by the door. Last month the spreadsheet was overwritten twice, and a crate of yoghurt expired on the shelf because nobody noticed the date.\n\nThe pantry wants a simple web app: log donations as they arrive, see what is about to expire, record what is given out, and produce a short report for their funders.",
            'stakeholders'     => [
                ['name' => 'Grace Okafor', 'role' => 'Pantry Coordinator', 'priority' => 'high', 'concern' => 'nothing gets wasted'],
                ['name' => 'Tom Reyes', 'role' => 'Volunteer Lead', 'priority' => 'high', 'concern' => 'volunteers can use it without training'],
                ['name' => 'Harbourside Trust', 'role' => 'Funder', 'priority' => 'medium', 'concern' => 'accurate quarterly impact numbers'],
            ],
            'overarching_constraints' => [
                ['type' => 'technical', 'description' => 'Volunteers share one tablet at the door, so the app must work well on a touch screen.'],
                ['type' => 'regulatory', 'description' => 'Information about the families who receive food is private and must never appear in reports.'],
                ['type' => 'business', 'description' => 'No budget for paid services. Keep the stack simple.'],
            ],
            'tech_context'        => ['existing_stack' => ['PHP', 'Laravel', 'MySQL', 'Blade'], 'team_size' => 2, 'deployment_environment' => 'shared hosting', 'special_requirements' => null],
            'specialization_tags' => ['backend', 'frontend'],
            'coding_guidelines'   => 'Validate every input on the server. Name things the way volunteers talk about them (donation, shelf, given out). Small, readable functions over clever ones.',
            'velocity_estimate'   => ['sprint_days' => 5],
            'difficulty_level'    => 'beginner',
        ]);

        $this->vaultItem($project, 'prd', 'PantryLink PRD', "Goal: no food wasted because nobody noticed it.\n\nMust have: log donations (item, category, quantity, unit, expiry date), see stock sorted by expiry, record items given out, a monthly report for funders.\n\nCategories: fresh, chilled, frozen, tinned, dry, toiletries. Fresh, chilled and frozen items are perishable and always have an expiry date.\n\nOut of scope: recipient accounts, online ordering.", 1);
        $this->vaultItem($project, 'glossary', 'Pantry glossary', "Donation: items received in one drop-off.\nGiven out (distribution): items handed to a family.\nExpiring soon: expiry date is today or within the next 3 days.\nWasted: items whose expiry date passed before they were given out.", 2);
        $this->vaultItem($project, 'sprint_goal_template', 'Sprint goal template', 'By the end of this sprint, volunteers will be able to ___ so that ___.', 3);

        $roles = ['backend'];
        $front = ['frontend'];
        $both = ['backend', 'frontend'];

        // ── Scenario 1 ───────────────────────────────────────────────────────
        $s1 = $this->scenario($project, 1, [
            'title'        => 'Sprint 1 — Logging Donations',
            'narrative'    => "It's your first week on PantryLink. The database has a basic items table and nothing else. Saturday is the busiest donation day, and Grace wants volunteers to stop using the spreadsheet by then.",
            'trigger'      => "Email from Grace Okafor (Pantry Coordinator): \"The spreadsheet got overwritten again on Tuesday and we lost a whole morning of donations. Could we have a proper way to log donations by Saturday? And a list we can actually sort, because right now the milk is hidden under the tins.\"",
            'trigger_type' => 'email',
            'role_label'   => 'Junior Developer',
            'autonomy'     => 'low',
        ], [
            ['type' => 'ticket', 'title' => 'PL-3: Log a donation', 'content' => "As a volunteer, I want to log each donated item so the pantry knows what it has.\n\nFields: item name, category, quantity (whole number), unit (e.g. tins, kg, packs), expiry date, donor name (optional).\n\nRules:\n- Quantity must be at least 1.\n- Fresh, chilled and frozen items must have an expiry date.\n- An expiry date in the past means the item cannot be accepted.", 'signals' => ['perishable categories require an expiry date', 'reject past expiry dates']],
            ['type' => 'notes', 'title' => 'Walkthrough notes from Tom', 'content' => "Watched two volunteers at the door. They mostly log 5–10 items per donation. They type fast and make typos in quantities (\"1O\" instead of \"10\"). The tablet is often held in one hand, so buttons need to be big.", 'signals' => ['bad quantity input is common', 'touch-friendly UI']],
        ]);

        $s1a = $this->coreTask($s1, $rubric, 1, [
            'title' => 'Build the "log a donation" endpoint',
            'brief' => "Implement POST /donations following ticket PL-3. Store valid donations and reject invalid ones with a clear message the volunteer can act on. Explain which rules you enforced and how.",
            'domain' => 'backend', 'roles' => $roles, 'tools' => ['php', 'laravel'], 'concepts' => ['validation', 'http status codes'],
            'answer' => 'Server-side validation for every field, with perishable categories requiring an expiry date and past dates rejected. Invalid input returns 422 with field-specific, human-readable messages; valid input is stored and returns 201.',
            'starter' => 'Start with the three rules in the ticket. Write each one as a validation rule before writing the controller.',
            'stretch' => 'Volunteers often log several items per donation. Accept a list of items in one request and explain what happens if only one item in the list is invalid.',
            'hint_low' => 'Which categories count as perishable? The PRD in the vault lists them.',
            'hint_mid' => 'Think about what a volunteer sees when they make a typo.',
            'criteria' => [
                ['dim_implementation', 'Validation correctness', 'All ticket rules are enforced on the server and valid donations are stored.', 'Check for: quantity ≥ 1 and whole number, expiry required only for fresh/chilled/frozen, past expiry rejected. Missing the conditional expiry rule is at most developing.', ['All rules enforced with clear, specific error messages and correct status codes.', 'All rules enforced.', 'Some rules enforced; the conditional expiry rule or past-date rule is missing.', 'Only the happy path works.']],
                ['dim_problem_analysis', 'Reading the requirement', 'Notices that the expiry rule depends on the category and explains how that was handled.', 'Look for an explicit mention that non-perishables (tinned, dry, toiletries) may have no expiry date.', ['Explains the conditional rule and a sensible choice for edge cases (e.g. tinned items that do have a date).', 'Identifies and handles the conditional rule.', 'Treats expiry as always required or never required.', 'No evidence the requirement was read closely.']],
            ],
        ]);

        $s1b = $this->coreTask($s1, $rubric, 2, [
            'title' => 'Build the donations list page',
            'brief' => "Build the page volunteers use to see what is on the shelves. Show each item's name, category, quantity and expiry date, sorted so the soonest-expiring items come first. Volunteers must be able to filter by category. Show a friendly message when there is nothing to show.",
            'domain' => 'frontend', 'roles' => $front, 'tools' => ['blade', 'css'], 'concepts' => ['sorting', 'empty states', 'responsive design'],
            'answer' => 'A list sorted by expiry ascending with items without an expiry date at the end, a category filter that keeps the sort, a clear empty state, and layout/buttons that work on a tablet.',
            'starter' => 'Sort in the database query, not in the template. Decide where items without an expiry date should go.',
            'stretch' => 'Visually flag items expiring within 3 days without relying on colour alone, and explain how a volunteer who is colour-blind would still notice them.',
            'hint_low' => 'Where should a tin with no expiry date appear: top or bottom?',
            'hint_mid' => 'Remember the tablet is held in one hand at the door.',
            'criteria' => [
                ['dim_implementation', 'List behaviour', 'Items are sorted by expiry (soonest first), filtering by category works, and the empty state is handled.', 'Items with no expiry date should not appear first. Filtering that loses the sort order is developing.', ['Correct sort with sensible handling of missing dates, working filter, empty state, and tablet-friendly layout.', 'Correct sort, working filter and empty state.', 'Sort or filter partly broken, or no empty state.', 'Unsorted list only.']],
                ['dim_design', 'Designing for volunteers', 'Layout choices reflect how volunteers use the page (touch screen, quick scanning).', 'Look for reasoning about tap-target size, readable text, and what the eye sees first.', ['Clear reasoning tying layout decisions to the walkthrough notes.', 'Layout works on a tablet with some reasoning.', 'Desktop-only thinking.', 'No consideration of the user.']],
            ],
        ]);

        $s1aFix = $this->adaptiveTask('consequence', $s1, $rubric, 3, [
            'title' => 'Follow-up: impossible donations in the database',
            'brief' => "Grace forwarded this: \"The stock page says we have -5 tins of soup, and there's a carton of milk that expired in 2019.\" Both entries came in through the donation endpoint. Find out how they got past validation, fix it, and add automated tests so it can't happen again.",
            'domain' => 'backend', 'roles' => $roles,
            'answer' => 'Identifies the missing or weak rules (minimum quantity, past expiry), fixes them, and adds tests for boundaries: quantity 0, -1 and 1, and expiry yesterday, today and tomorrow.',
            'hint' => 'For each rule, test the value just inside and just outside the limit.',
            'criteria' => [
                ['dim_testing', 'Tests that prevent regressions', 'Adds tests covering the boundaries of each rule that failed.', 'Boundary values (0, 1, yesterday, today) are what matters. A single happy-path test is beginning.', ['Tests cover both sides of every boundary and would have caught the original bug.', 'Tests cover the two reported cases and their boundaries.', 'Tests cover only the reported values.', 'No tests.']],
                ['dim_debugging', 'Finding the gap', 'Explains exactly which rule was missing or wrong.', 'Look for a specific cause, not "validation was broken".', ['Names the exact missing/incorrect rule and why it let the data through.', 'Names the missing rule.', 'Vague cause.', 'No cause identified.']],
            ],
        ]);

        $s1bFix = $this->adaptiveTask('consequence', $s1, $rubric, 4, [
            'title' => 'Follow-up: the yoghurt expired again',
            'brief' => "Tom writes: \"We threw away six pots of yoghurt today. They were on the list, but somewhere in the middle, and nobody scrolled that far.\" Look at the list page again. Make sure the items that need attention first are impossible to miss, and explain what you changed and why.",
            'domain' => 'frontend', 'roles' => $front,
            'answer' => 'Verifies the sort order is actually by expiry ascending (fixing it if not), and makes expiring-soon items stand out with text or icon plus colour, possibly as a separate section at the top.',
            'hint' => 'Open the page as a volunteer would, with 60 items in the list.',
            'criteria' => [
                ['dim_problem_analysis', 'Understanding the real problem', 'Recognises that the issue is visibility and ordering for a busy volunteer, not just data.', 'A good answer checks the sort, then addresses scanning with a clear "expiring soon" treatment.', ['Diagnoses both ordering and visibility, and justifies the fix from the volunteer\'s point of view.', 'Fixes the ordering and adds a clear highlight.', 'Only one of ordering or highlighting is addressed.', 'No meaningful change.']],
            ],
        ]);

        $s1Focus = $this->adaptiveTask('suggestion', $s1, $rubric, 5, [
            'title' => 'Development focus: write the rules down as tests first',
            'brief' => "Pick one of the endpoints you've built. Before touching the code, write a list of test cases in plain language: what goes in, what should come out. Then explain how writing these first would have changed your implementation.",
            'domain' => 'testing', 'roles' => $both, 'deliver' => 'written',
            'answer' => 'A table-like list of cases including boundaries and invalid inputs, each with an expected result, plus a short reflection on how it would change the implementation.',
            'hint' => 'Include at least one case for every rule, plus one that should succeed.',
            'criteria' => [
                ['dim_testing', 'Test design', 'Test cases are concrete, cover boundaries, and state expected results.', 'Look for exact inputs and exact expected outputs.', ['Complete, concrete cases with boundaries and a thoughtful reflection.', 'Concrete cases with expected results.', 'Cases without expected results.', 'No real cases.']],
            ],
        ]);

        $this->linkAdaptive($s1a, [$s1aFix], [$s1Focus]);
        $this->linkAdaptive($s1b, [$s1bFix], [$s1Focus]);

        // ── Scenario 2 ───────────────────────────────────────────────────────
        $s2 = $this->scenario($project, 2, [
            'title'        => 'Sprint 2 — Expiry Alerts and Stock Counts',
            'narrative'    => 'The donation log is live and volunteers like it. But now that people trust the numbers, they have noticed the numbers are wrong: stock counts never go down, and the "expiring soon" box is unreliable.',
            'trigger'      => "Slack message from Tom Reyes (Volunteer Lead): \"The dashboard says 40 tins of beans. I just counted the shelf: 12. Nothing we give out gets taken off. Also the 'expiring soon' box said the bread was fine this morning and it went off TODAY.\"",
            'trigger_type' => 'slack_message',
            'role_label'   => 'Developer',
        ], [
            ['type' => 'report', 'title' => 'expiringSoon() — current code', 'content' => self::EXPIRING_SOON_CODE, 'signals' => ['today excluded by > 0', '3 days excluded by < 3', 'time of day makes daysLeft fractional']],
            ['type' => 'notes', 'title' => 'Saturday distribution notes', 'content' => 'On Saturdays two volunteers hand out food at the same time from two tables. Each records what they gave on their own tablet.', 'signals' => ['concurrent distributions']],
        ]);

        $s2a = $this->coreTask($s2, $rubric, 1, [
            'title' => 'Record items given out and keep stock accurate',
            'brief' => "Add a way to record items given out (POST /distributions with item and quantity). Stock must go down when items are given out and must never go below zero. Explain how stock is calculated and why you chose that approach.",
            'domain' => 'backend', 'roles' => $roles, 'tools' => ['php', 'laravel', 'mysql'], 'concepts' => ['database transactions', 'derived vs stored data'],
            'answer' => 'Distributions are recorded as their own rows. Stock either derives from donations minus distributions, or is a counter updated in a transaction with a guard against going negative. Giving out more than is in stock is rejected with a clear message.',
            'starter' => 'Decide first: will you store the stock number, or calculate it from donations and distributions?',
            'stretch' => 'Two volunteers may give out the last tin at the same moment (see the Saturday notes). Make sure stock still cannot go negative, and explain how.',
            'hint_low' => 'What should happen if a volunteer tries to give out 5 tins when only 3 are left?',
            'hint_mid' => 'Think about keeping a history of what was given out, not just the current number.',
            'criteria' => [
                ['dim_implementation', 'Stock correctness', 'Stock decreases correctly and can never go negative.', 'Check for a guard on quantity available. A negative stock being possible is at most developing.', ['Correct, guarded, and safe under concurrent requests.', 'Correct and guarded against going negative.', 'Stock updates but can go negative.', 'Stock does not change.']],
                ['dim_design', 'Choosing a model for stock', 'Explains the choice between storing and deriving stock, with a trade-off.', 'Either choice is fine if the trade-off is stated (simplicity and history vs speed).', ['Clear trade-off stated and tied to the pantry\'s needs.', 'A reasonable choice with some justification.', 'A choice with no justification.', 'No discernible model.']],
            ],
        ]);

        $s2b = $this->coreTask($s2, $rubric, 2, [
            'title' => 'Fix the "expiring soon" alert',
            'brief' => "The 'expiring soon' box is supposed to show items that expire today or within the next 3 days (see the glossary). Read expiringSoon() in the reference materials, explain every reason it gives wrong results, fix it, and list the test cases you would use to prove it now works.",
            'domain' => 'debugging', 'roles' => $both, 'tools' => ['php'], 'concepts' => ['dates', 'off-by-one errors', 'boundary testing'],
            'answer' => 'Finds that today is excluded (> 0), day 3 is excluded (< 3), and passing a time of day makes daysLeft fractional. Compares dates only and includes 0..3. Tests: expiry yesterday, today, tomorrow, +3, +4.',
            'starter' => 'Work out what $daysLeft is for an item expiring today when the function runs at 10:00.',
            'stretch' => 'The server runs on UTC but the pantry is in a different time zone. Explain when this could still show the wrong result and how you would handle it.',
            'hint_low' => 'Try the function by hand with an item expiring today.',
            'hint_mid' => 'Check both ends of the range.',
            'criteria' => [
                ['dim_debugging', 'Root causes', 'Identifies each cause of wrong results and fixes them.', 'Three problems: > 0 excludes today, < 3 excludes day 3, time of day creates fractions. All three is distinguished; two is proficient.', ['All three causes found and fixed with a clear explanation.', 'Two causes found and fixed.', 'One cause found.', 'No cause found.']],
                ['dim_testing', 'Boundary tests', 'Lists tests at each boundary with expected results.', 'Look for yesterday, today, +3 and +4 days specifically.', ['All boundaries with expected results, plus a time-of-day case.', 'Main boundaries with expected results.', 'Some tests, missing boundaries.', 'No tests.']],
            ],
        ]);

        $s2aFix = $this->adaptiveTask('consequence', $s2, $rubric, 3, [
            'title' => 'Follow-up: stock went negative on Saturday',
            'brief' => "During the Saturday rush the stock for rice showed -3. Two volunteers each gave out the last bags within the same second. Explain how this happened step by step, then fix it so it cannot happen even when two requests arrive together.",
            'domain' => 'backend', 'roles' => $roles,
            'answer' => 'Explains the read-then-write race (both read stock 2, both write). Fixes with a transaction plus row lock, or a single conditional UPDATE (… WHERE stock >= :qty) and checks the affected row count.',
            'hint' => 'Write out what each request reads and writes, in order, on a timeline.',
            'criteria' => [
                ['dim_debugging', 'Explaining the race', 'Explains the interleaving of the two requests that led to negative stock.', 'A step-by-step timeline of reads and writes is ideal.', ['Clear timeline and a fix that is safe under concurrency, explained.', 'Correct explanation and a working fix.', 'Explanation or fix is partly wrong.', 'No understanding of the concurrency issue.']],
            ],
        ]);

        $s2bFix = $this->adaptiveTask('consequence', $s2, $rubric, 4, [
            'title' => 'Follow-up: the alert is wrong after midnight',
            'brief' => "A volunteer doing a late stock check at 00:30 saw items listed as 'expiring soon' that had actually expired yesterday. The server clock is in UTC; the pantry is one hour ahead. Explain the cause and fix it.",
            'domain' => 'debugging', 'roles' => $both,
            'answer' => 'Identifies that "today" is computed in UTC rather than the pantry\'s local time zone, and fixes by computing today in the configured app time zone (and storing dates without time).',
            'hint' => 'What date is it on the server at 00:30 in the pantry?',
            'criteria' => [
                ['dim_debugging', 'Time-zone reasoning', 'Identifies the time-zone mismatch and fixes where "today" is calculated.', 'Look for explicit reasoning about UTC vs local date at the reported time.', ['Correct cause, correct fix, and a test at the midnight boundary.', 'Correct cause and fix.', 'Suspects time zones but the fix is incomplete.', 'Cause not found.']],
            ],
        ]);

        $s2Focus = $this->adaptiveTask('suggestion', $s2, $rubric, 5, [
            'title' => 'Development focus: reproduce it before you fix it',
            'brief' => "Think back to a bug from this sprint. Write down how you would reproduce it reliably before changing any code: the exact steps, data, and time. Explain why a reliable reproduction makes the fix safer.",
            'domain' => 'debugging', 'roles' => $both, 'deliver' => 'written',
            'answer' => 'Concrete reproduction steps with specific data and conditions, and a reflection on proving the fix by re-running them.',
            'hint' => 'A good reproduction lets someone else see the bug without asking you anything.',
            'criteria' => [
                ['dim_debugging', 'Reproduction discipline', 'Gives concrete, repeatable steps and explains their value.', 'Look for specific data and conditions, not "try it and see".', ['Precise steps plus how they will verify the fix.', 'Precise steps.', 'Vague steps.', 'No steps.']],
            ],
        ]);

        $this->linkAdaptive($s2a, [$s2aFix], [$s2Focus]);
        $this->linkAdaptive($s2b, [$s2bFix], [$s2Focus]);

        // ── Scenario 3 ───────────────────────────────────────────────────────
        $s3 = $this->scenario($project, 3, [
            'title'        => 'Sprint 3 — Volunteers and the Funder Report',
            'narrative'    => "PantryLink is now the pantry's single source of truth. The funder has asked for proper numbers, and the volunteer rota (still on a whiteboard) keeps getting overbooked.",
            'trigger'      => "Email from Grace Okafor: \"Harbourside Trust wants our quarterly numbers by the 15th: what came in, what went out, and how much we wasted. Please make sure nothing about the families is in there. Also, could volunteers sign up for shifts in the app? Last Saturday we had nine people for a shift that needed four.\"",
            'trigger_type' => 'email',
            'role_label'   => 'Developer',
            'autonomy'     => 'high',
        ], [
            ['type' => 'document', 'title' => 'Funder reporting requirements', 'content' => "Per quarter: total items received by category; total items given out by category; items wasted (expired before being given out) as a number and a percentage of items received; number of distribution days.\n\nThe report must not contain any personal information about recipients.", 'signals' => ['wasted = expired before given out', 'no personal data']],
            ['type' => 'notes', 'title' => 'Shift rota rules', 'content' => 'Each shift has a maximum number of volunteers. Volunteers can cancel up to 12 hours before a shift. Some volunteers use screen readers.', 'signals' => ['capacity', 'accessibility']],
        ]);

        $s3a = $this->coreTask($s3, $rubric, 1, [
            'title' => 'Build the quarterly impact report',
            'brief' => "Produce the funder report described in the reference materials for a given quarter (a page or a CSV download is fine). Explain exactly how you calculated 'wasted' and any assumptions you made.",
            'domain' => 'backend', 'roles' => $roles, 'tools' => ['php', 'laravel', 'sql'], 'concepts' => ['aggregation', 'data privacy'],
            'answer' => 'Aggregates received and given-out totals by category for the date range, calculates wasted as expired-and-not-given-out, guards the percentage against division by zero, and includes no personal fields.',
            'starter' => 'Write down the definition of each number in plain words before writing any queries.',
            'stretch' => 'The trust may ask for a different date range next year. Make the range a parameter and explain how you would check the numbers are right.',
            'hint_low' => 'What if nothing was received in the quarter? What is the waste percentage then?',
            'hint_mid' => 'Be explicit about which date decides which quarter an item belongs to.',
            'criteria' => [
                ['dim_implementation', 'Correct numbers', 'Totals and waste are calculated correctly for the quarter.', 'Check the waste definition and the zero-received edge case.', ['All figures correct with edge cases handled.', 'All figures correct.', 'Some figures wrong or edge cases crash.', 'Report not produced.']],
                ['dim_communication', 'Explaining the numbers', 'Clearly states definitions and assumptions so the coordinator can defend the numbers.', 'Look for explicit definitions (what counts as wasted, which date is used).', ['Precise definitions and assumptions written for a non-technical reader.', 'Definitions stated.', 'Assumptions implied but not stated.', 'No explanation.']],
            ],
        ]);

        $s3b = $this->coreTask($s3, $rubric, 2, [
            'title' => 'Build volunteer shift sign-up',
            'brief' => "Build the page where volunteers see upcoming shifts, how many spots are left, and can sign up or cancel. A full shift must not accept more volunteers, and the page must clearly explain why. Follow the rota rules in the reference materials.",
            'domain' => 'frontend', 'roles' => $both, 'tools' => ['blade', 'css', 'laravel'], 'concepts' => ['forms', 'accessibility', 'ui states'],
            'answer' => 'Shows each shift with spots left, disables or hides sign-up when full with a text explanation, supports cancelling within the 12-hour rule, and uses real buttons with labels so screen readers work.',
            'starter' => 'List every state a shift can be in for a volunteer (open, full, already joined, too late to cancel) and design each.',
            'stretch' => 'Two volunteers may take the last spot at the same time. Make sure the server enforces the limit, not just the page.',
            'hint_low' => 'What should the button say when the shift is full?',
            'hint_mid' => 'Check the page with the keyboard only.',
            'criteria' => [
                ['dim_implementation', 'Sign-up rules', 'Capacity and cancellation rules are enforced.', 'Server-side enforcement of capacity is required for proficient.', ['All rules enforced server-side, with clear messages.', 'Capacity and cancellation rules work.', 'Rules only enforced in the page.', 'Rules missing.']],
                ['dim_design', 'Clear, accessible states', 'Each state is clear and usable with a screen reader or keyboard.', 'Look for text (not colour only), labelled buttons, and a considered set of states.', ['All states designed and accessible, with reasoning.', 'Main states clear and accessible.', 'States unclear or colour-only.', 'Single state only.']],
            ],
        ]);

        $s3aFix = $this->adaptiveTask('consequence', $s3, $rubric, 3, [
            'title' => 'Follow-up: a family\'s name appeared in the funder report',
            'brief' => "The trust emailed: \"Row 14 of your report says 'extra nappies for the Mensah family'.\" A volunteer had typed a note into a free-text field, and the report included it. Fix the report, and explain how you'll make sure personal data can't leak into reports in future.",
            'domain' => 'backend', 'roles' => $roles,
            'answer' => 'Removes free-text fields from the report and builds it from an explicit allow-list of aggregate fields; explains data minimisation and suggests checking reports in tests.',
            'hint' => 'Is it safer to remove fields you know are risky, or only include fields you know are safe?',
            'criteria' => [
                ['dim_design', 'Privacy by design', 'The fix prevents the whole class of leak, not just this row.', 'An allow-list of fields is distinguished; removing only the notes column is proficient.', ['Allow-list approach with a clear prevention strategy.', 'Leak fixed with a sensible explanation.', 'Only this instance fixed.', 'Not fixed.']],
            ],
        ]);

        $s3bFix = $this->adaptiveTask('consequence', $s3, $rubric, 4, [
            'title' => 'Follow-up: a volunteer couldn\'t sign up with a screen reader',
            'brief' => "Amara, a long-time volunteer who uses a screen reader, says the sign-up buttons are all read out as \"button\" and she can't tell which shift is full. Find the accessibility problems in your page and fix them. Explain how you checked.",
            'domain' => 'frontend', 'roles' => $front,
            'answer' => 'Adds accessible names to buttons (including the shift time), exposes the full state as text, and describes checking with a screen reader or accessibility tooling and the keyboard.',
            'hint' => 'Each button needs a name that makes sense on its own, e.g. "Sign up for Saturday 9am".',
            'criteria' => [
                ['dim_implementation', 'Accessibility fixes', 'Buttons have meaningful names and state is available as text.', 'Look for labelled controls and non-colour state indicators, plus a described checking method.', ['All issues fixed and a real checking method described.', 'Main issues fixed.', 'Partial fixes.', 'No fixes.']],
            ],
        ]);

        $s3Focus = $this->adaptiveTask('suggestion', $s3, $rubric, 5, [
            'title' => 'Development focus: explain your assumptions',
            'brief' => "Choose a task from this project. Write a short note to Grace (who is not technical) explaining one decision you made, the assumption behind it, and what would change if the assumption were wrong.",
            'domain' => 'communication', 'roles' => $both, 'deliver' => 'written',
            'answer' => 'A short, jargon-free note naming the decision, the assumption, and the consequence if it is wrong.',
            'hint' => 'Imagine Grace reading this on her phone between deliveries.',
            'criteria' => [
                ['dim_communication', 'Clear explanation', 'The note is clear to a non-technical reader and states the assumption and its impact.', 'Penalise jargon and missing "what if I\'m wrong".', ['Clear, jargon-free, with assumption and impact.', 'Clear with the assumption stated.', 'Some jargon or missing impact.', 'Unclear.']],
            ],
        ]);

        $this->linkAdaptive($s3a, [$s3aFix], [$s3Focus]);
        $this->linkAdaptive($s3b, [$s3bFix], [$s3Focus]);

        // ── Backlog ─────────────────────────────────────────────────────────
        $order = 1;
        foreach ([[$s1a, 'must_have', $roles], [$s1b, 'must_have', $front], [$s2a, 'must_have', $roles], [$s2b, 'must_have', $both], [$s3a, 'must_have', $roles], [$s3b, 'should_have', $both]] as [$task, $priority, $tags]) {
            $this->backlogItem($project, $task->title, strtok($task->task_brief, "\n"), $priority, $tags, $task, $order++);
        }
        $this->backlogItem($project, 'Print shelf labels with expiry dates', 'Volunteers asked for printable labels. Not requested by the coordinator yet.', 'could_have', $front, null, $order++);
        $this->backlogItem($project, 'Donor thank-you emails', 'Automatically email donors. Needs a mail provider the pantry cannot pay for yet.', 'wont_have', $roles, null, $order);
    }
}
