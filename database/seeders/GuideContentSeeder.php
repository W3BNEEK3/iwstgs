<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Starter content for Tiroco's tips library and the curated outside
 * resources it may recommend (design doc v2-05 §4–5). Keyed by `key`, and
 * rows that already exist are left alone, so admins' edits survive re-seeding
 * and the migration that first creates the tables can call this safely.
 */
class GuideContentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ($this->tips() as $i => [$key, $area, $text, $pages]) {
            DB::table('guide_tips')->insertOrIgnore([
                'id' => (string) Str::uuid(), 'key' => $key, 'area' => $area, 'text' => $text,
                'pages' => $pages === null ? null : json_encode($pages),
                'is_active' => true, 'sort_order' => $i, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        foreach ($this->resources() as [$key, $name, $url, $kind, $dimensions, $level, $blurb]) {
            DB::table('guide_resources')->insertOrIgnore([
                'id' => (string) Str::uuid(), 'key' => $key, 'name' => $name, 'url' => $url, 'kind' => $kind,
                'dimensions' => json_encode($dimensions), 'level' => $level, 'is_free' => true, 'blurb' => $blurb,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    /** @return array<int, array{0: string, 1: string, 2: string, 3: ?array}> */
    private function tips(): array
    {
        return [
            ['explain-first', 'Submitting', 'Write your explanation before you polish the code. Explaining it often shows you the gap.', null],
            ['say-how-checked', 'Submitting', 'Say how you checked your work. Reviewers weigh that as much as the code itself.', null],
            ['hints-are-free', 'Hints', 'Hints cost you nothing. Give a task ten honest minutes first, then use one if you are stuck.', ['task-submit', 'sprint-board']],
            ['vault-first', 'Reference materials', 'The facts every task relies on live in the reference materials and the Vault. Skim them before you start, not after a miss.', ['sprint-board', 'task-submit']],
            ['criteria-missed', 'Feedback', 'On a result, read "criteria missed" first. It is the quickest route to a pass on the next attempt.', ['evaluation-result', 'sprint-board']],
            ['consequences-normal', 'Consequences', 'A consequence card means the story reacted to your work, like a real team would. It is practice, not punishment.', ['sprint-board']],
            ['short-sessions', 'Pacing', 'Short, regular sessions beat one long weekend push. Little and often is how skills stick.', null],
            ['weekly-profile', 'Profile', 'Check your skills chart once a week. Pick the lowest skill and pay extra attention to it in your next task.', ['profile', 'catalogue']],
            ['name-edge-cases', 'Problem analysis', 'Before you code, list three things that could go wrong with the input. Most missed criteria hide there.', ['task-submit', 'sprint-board']],
            ['resubmit-freely', 'Submitting', 'Resubmitting is normal. Each attempt is reviewed on its own, and improving between attempts is exactly what counts.', ['evaluation-result']],
            ['stakeholder-voice', 'Communication', 'When a task mentions a stakeholder, write part of your explanation for them: plain words, no jargon, what changes for them.', null],
            ['plan-the-sprint', 'Planning', 'When you plan a sprint, pull in the must-haves first. It is fine to leave nice-to-haves in the backlog.', ['sprint-planning', 'sprint-board']],
        ];
    }

    /** @return array<int, array{0: string, 1: string, 2: string, 3: string, 4: string[], 5: string, 6: string}> */
    private function resources(): array
    {
        return [
            ['mdn', 'MDN Web Docs', 'https://developer.mozilla.org/', 'reference',
                ['dim_implementation', 'dim_debugging'], 'beginner',
                'The most reliable reference for HTML, CSS, JavaScript and HTTP, with clear examples.'],
            ['javascript-info', 'javascript.info', 'https://javascript.info/', 'reference',
                ['dim_implementation', 'dim_testing'], 'beginner',
                'A modern JavaScript tutorial from basics to async code, with short tasks after each chapter.'],
            ['odin-project', 'The Odin Project', 'https://www.theodinproject.com/', 'course',
                ['dim_implementation', 'dim_design'], 'beginner',
                'A free, project-based full-stack path. A good companion to building real apps here.'],
            ['freecodecamp', 'freeCodeCamp', 'https://www.freecodecamp.org/learn', 'course',
                ['dim_implementation', 'dim_problem_analysis'], 'beginner',
                'Free interactive courses with small challenges; good for filling fundamentals gaps.'],
            ['exercism', 'Exercism', 'https://exercism.org/', 'practice',
                ['dim_problem_analysis', 'dim_implementation', 'dim_testing'], 'beginner',
                'Small coding exercises checked by tests, with optional feedback from volunteer mentors.'],
            ['test-pyramid', 'The Practical Test Pyramid (Martin Fowler)', 'https://martinfowler.com/articles/practical-test-pyramid.html', 'reference',
                ['dim_testing'], 'intermediate',
                'A clear explanation of which tests to write, how many, and why.'],
            ['devtools', 'Chrome DevTools documentation', 'https://developer.chrome.com/docs/devtools', 'reference',
                ['dim_debugging'], 'beginner',
                'How to use breakpoints, the console and the network panel to find out what your code is really doing.'],
            ['rubber-duck', 'Rubber duck debugging', 'https://rubberduckdebugging.com/', 'reference',
                ['dim_debugging', 'dim_problem_analysis'], 'beginner',
                'A one-page method: explain the problem out loud, line by line, until the bug shows itself.'],
            ['tech-writing', 'Google Technical Writing One', 'https://developers.google.com/tech-writing/one', 'course',
                ['dim_communication'], 'beginner',
                'A short free course on writing clear technical explanations, READMEs and documents.'],
            ['write-the-docs', 'Write the Docs guide', 'https://www.writethedocs.org/guide/', 'reference',
                ['dim_communication'], 'intermediate',
                'Practical advice on documentation that people actually read.'],
            ['refactoring-guru', 'Refactoring.Guru', 'https://refactoring.guru/design-patterns', 'reference',
                ['dim_design'], 'intermediate',
                'Illustrated explanations of design patterns and code smells, with the problem each one solves.'],
            ['owasp-cheatsheets', 'OWASP Cheat Sheet Series', 'https://cheatsheetseries.owasp.org/', 'reference',
                ['dim_design', 'dim_implementation'], 'intermediate',
                'Short, practical guides to input validation, authentication, SQL injection and privacy.'],
            ['roadmap-sh', 'roadmap.sh', 'https://roadmap.sh/', 'reference',
                ['dim_design', 'dim_problem_analysis'], 'beginner',
                'Visual roadmaps of what each developer role learns, to see where today\'s work fits.'],
        ];
    }
}
