<?php
namespace Database\Seeders;

use Database\Seeders\Concerns\AuthorsSimulationContent;
use Illuminate\Database\Seeder;

/**
 * The one-time diagnostic every learner takes before choosing a project
 * (Business Logic §4.2). Written for developers in their first year: plain
 * language, a small piece of code to read instead of a system to design, and
 * hints up front. It still separates learners who reason about *why* from
 * those who only describe *what*, which is all rank assignment needs.
 *
 * Hidden from the catalogue (is_published: false); StartDiagnosticHandler
 * loads it directly. Tasks are pinned at mid CAC.
 */
class DiagnosticAssessmentSeeder extends Seeder
{
    use AuthorsSimulationContent;

    public function run(): void
    {
        $this->role('Diagnostic Candidate', ['diagnostic'], 0, [
            'dim_problem_analysis' => 0.25, 'dim_design' => 0.10, 'dim_implementation' => 0.05,
            'dim_testing' => 0.20, 'dim_debugging' => 0.25, 'dim_communication' => 0.15,
        ]);

        [$project, $rubricSet] = $this->project('Initial Skills Diagnostic', [
            'tagline'                 => 'Two short tasks that set your starting rank.',
            'project_type'            => 'assessment',
            'business_domain'         => 'general',
            'business_context'        => 'A one-time, beginner-friendly assessment used to choose a sensible starting rank before real project work begins.',
            'stakeholders'            => [],
            'overarching_constraints' => [],
            'tech_context'            => ['stack' => []],
            'specialization_tags'     => ['diagnostic'],
            'coding_guidelines'       => null,
            'velocity_estimate'       => null,
            'difficulty_level'        => 'beginner',
            'is_published'            => false,
        ]);

        $scenario = $this->scenario($project, 1, [
            'title'        => 'Welcome Check-in',
            'narrative'    => "Welcome! Before you join a project team, we'd like to see how you think through everyday developer work. There are two short tasks: reading a small piece of code with a bug in it, and planning a simple feature. Plain language is completely fine. We care about your reasoning, not perfect answers, and most people finish both in 20–30 minutes.",
            'trigger'      => "Message from your team lead, Ada: \"Hi and welcome aboard! Before your first sprint, please have a go at the two tasks below. There are no trick questions. Just explain what you'd do and why, as if you were talking me through it.\"",
            'trigger_type' => 'slack_message',
            'role_label'   => 'New Developer',
        ], [
            [
                'type'    => 'ticket',
                'title'   => 'BUG-101: Cart total is wrong',
                'content' => "Reported by: Customer Support\n\nCustomers say the checkout total is wrong:\n• A cart with just ONE item always shows a total of 0.\n• The SAVE10 discount code (10% off) barely changes the price.\n\nCode (JavaScript, but read it like any language):\n\nfunction cartTotal(items, discountCode) {\n  let total = 0;\n  for (let i = 1; i < items.length; i++) {\n    total += items[i].price * items[i].quantity;\n  }\n  if (discountCode === \"SAVE10\") {\n    total = total - 10 / 100;\n  }\n  return total;\n}",
                'signals' => ['loop starts at index 1, skipping the first item', 'discount subtracts 0.1 instead of 10% of the total'],
            ],
        ], isDiagnostic: true);

        $brief = "Read ticket BUG-101 in the reference materials. In your own words:\n1. What is wrong in the code, and which customer complaint does each problem cause?\n2. How would you check that you're right? Give one or two example carts and the total you'd expect.\n3. How would you fix it? (Describing the fix is enough. Code is optional.)";
        $this->task($scenario, $rubricSet, 1, [
            'title'  => 'Find the bug in the cart total',
            'brief'  => $brief,
            'type'   => 'diagnostic_scenario',
            'domain' => 'debugging',
            'role_tags' => ['diagnostic'],
            'concepts'  => ['reading code', 'loops', 'testing with examples'],
            'fixed'     => 'mid',
            'time'      => 15,
            'model_answer' => 'Spots both bugs (loop starts at 1 so the first item is skipped, which makes a one-item cart total 0; the discount subtracts 0.1 instead of 10% of the total) and ties each to a complaint. Proposes concrete checks with expected results, e.g. one item at 20 × 1 should total 20; a 100 total with SAVE10 should become 90. Fix: start the loop at 0 and use total * 0.9 (or total - total * 0.10).',
            'deliverables' => [
                ['written_explanation', 'Your explanation', 'What is wrong, how you would check it, and how you would fix it.'],
                ['code', 'Fixed code (optional)', 'A corrected version of the function, if you want to write one.', false],
            ],
            'variants' => array_fill_keys(['low', 'mid', 'high'], $brief),
            'scaffolding' => [
                'low'  => 'Try "running" the code in your head with a cart that has exactly one item. What happens on each line?',
                'mid'  => 'There is more than one problem. Check each complaint separately.',
                'high' => 'No extra guidance.',
            ],
            'context' => [
                'low'  => 'Everything you need is in the ticket.',
                'mid'  => 'Everything you need is in the ticket.',
                'high' => 'Everything you need is in the ticket.',
            ],
            'hints' => [
                ['dim_debugging', 'Arrays in most languages start counting at 0.'],
                ['dim_testing', 'A good check says what goes in AND what you expect to come out, e.g. "a cart of one £20 item should total £20".'],
            ],
            'criteria' => [
                ['dim_debugging', 'Finding the cause', 'Identifies the problems in the code and connects each one to the symptom it causes.', '0.600', '0.500',
                    'Two bugs exist: the loop starts at index 1 (first item skipped, so a one-item cart totals 0) and the discount subtracts 0.1 instead of 10% of the total. Naming both AND linking each to its complaint is distinguished. Naming both without the link is proficient. One bug is developing. Guessing without pointing at the code is beginning. Do not penalise plain or informal language.',
                    ['Finds both bugs and clearly explains which complaint each one causes.', 'Finds both bugs.', 'Finds one of the two bugs.', 'Does not identify a concrete problem in the code.']],
                ['dim_testing', 'Checking the fix', 'Proposes concrete example inputs with expected outputs that would confirm the bugs and the fix.', '0.400', '0.500',
                    'Look for specific carts with specific expected totals (e.g. one item at 20 × 1 → 20; total 100 with SAVE10 → 90). "I would test it" with no example is at most developing.',
                    ['Gives examples covering both bugs, each with the exact expected result.', 'Gives at least one concrete example with an expected result.', 'Mentions testing but without concrete expected results.', 'No way of checking is described.']],
            ],
        ]);

        $brief = "The product owner writes: \"Users keep forgetting their passwords. Please add a 'Forgot password?' link so they can reset it themselves.\"\n\nBefore writing any code:\n1. List 3–5 questions you would ask the product owner first.\n2. Break the feature into small steps you could build and check one at a time.\n3. Name one thing that could go wrong or be misused, and how you would prevent it.";
        $this->task($scenario, $rubricSet, 2, [
            'title'  => 'Plan a "Forgot password" feature',
            'brief'  => $brief,
            'type'   => 'diagnostic_scenario',
            'domain' => 'planning',
            'role_tags' => ['diagnostic'],
            'concepts'  => ['requirements', 'breaking down work', 'basic security'],
            'fixed'     => 'mid',
            'time'      => 15,
            'model_answer' => 'Asks useful clarifying questions (email or SMS? how long is the reset link valid? what happens if the email is not registered? any password rules?). Breaks the work into small, ordered, checkable steps: request form → create a random single-use token and store it with an expiry → email the link → reset form that validates the token → save the new hashed password → invalidate the token. Names a risk such as links that never expire, guessable tokens, or revealing which emails have accounts, with a sensible prevention.',
            'deliverables' => [
                ['written_explanation', 'Your plan', 'Your questions, your step-by-step breakdown, and one risk with how you would prevent it.'],
            ],
            'variants' => array_fill_keys(['low', 'mid', 'high'], $brief),
            'scaffolding' => [
                'low'  => 'Picture the feature from the user\'s side first: what do they click, what arrives in their inbox, what do they type?',
                'mid'  => 'Think about what the system has to remember between "send me a link" and "here is my new password".',
                'high' => 'No extra guidance.',
            ],
            'context' => [
                'low'  => 'The app already has user accounts with email addresses.',
                'mid'  => 'The app already has user accounts with email addresses.',
                'high' => 'The app already has user accounts.',
            ],
            'hints' => [
                ['dim_problem_analysis', 'Good questions uncover rules nobody wrote down, like "how long should a reset link work for?"'],
                ['dim_design', 'Each step should be small enough that you could show it working on its own.'],
            ],
            'criteria' => [
                ['dim_problem_analysis', 'Understanding the request', 'Asks questions that uncover missing rules or edge cases before building.', '0.350', '0.500',
                    'Relevant questions include delivery channel, link expiry, unknown email behaviour, password rules. Generic questions ("what language should I use?") do not count. Three or more relevant questions is proficient; if they also explain why a question matters, distinguished.',
                    ['Asks several relevant questions and explains why the answers matter.', 'Asks three or more relevant questions.', 'Asks one or two relevant questions.', 'Asks no relevant questions.']],
                ['dim_design', 'Breaking down the work', 'Splits the feature into small, ordered steps that could each be built and checked on their own.', '0.400', '0.500',
                    'Look for a sensible order and a step where something is remembered between requesting and using the link (a token or code with an expiry). Steps that are just "build the backend, build the frontend" are developing.',
                    ['Small, well-ordered steps including a single-use, expiring token, each checkable on its own.', 'Sensible ordered steps that would produce a working feature.', 'Steps are too big or out of order.', 'No real breakdown.']],
                ['dim_communication', 'Spotting a risk', 'Names a realistic risk or misuse and a sensible way to prevent it, explained clearly.', '0.250', '0.500',
                    'Good answers include: links that never expire, guessable tokens, telling attackers which emails have accounts, emailing the password itself. A risk plus a matching prevention is proficient; a clearly explained reason why it matters is distinguished.',
                    ['Realistic risk, matching prevention, and a clear reason why it matters.', 'Realistic risk with a matching prevention.', 'Names a risk but no prevention, or a vague one.', 'No risk identified.']],
            ],
        ]);
    }
}
