<?php
namespace Src\Submission\Domain\Submission;

/**
 * What the acceptance tests say about a milestone submission (design doc
 * v2-01 §4, §5.2). A milestone needs every *required* test to pass: the
 * tests it introduces plus the tests of every milestone already passed, so a
 * change that breaks earlier work fails (a regression), not just new work.
 *
 * Built from the report the Areyna workflow uploads (areyna-report.json):
 * {suite, version, startedOk, tests: [{id, milestone, title, status, message}]}.
 * A required test missing from the report counts as failed.
 */
final class AcceptanceResult
{
    public const PASSED = 'passed';
    public const FAILED = 'failed';
    public const ERRORED = 'errored';
    public const NOT_RUN = 'not_run';

    /**
     * @param array<int, array{id: string, title: string, status: string, message: ?string, required: bool, regression: bool}> $tests
     * @param string[] $regressions
     */
    private function __construct(
        public readonly string $status,
        public readonly string $summary,
        public readonly array $tests,
        public readonly array $regressions,
        public readonly ?string $suite,
    ) {}

    /**
     * @param string[] $introduced test IDs this task introduces
     * @param string[] $earlier test IDs of milestones already passed
     */
    public static function fromReport(?array $report, array $introduced, array $earlier, ?string $conclusion, ?bool $workflowIntact): self
    {
        if ($workflowIntact === false) {
            return new self(self::ERRORED,
                'The test workflow (.github/workflows/areyna.yml) has been changed, so its results can\'t be trusted. Restore it from the template and push again.',
                [], [], null);
        }
        if ($report === null || ! isset($report['tests']) || ! is_array($report['tests'])) {
            return new self(self::ERRORED,
                $conclusion === 'success'
                    ? 'The tests ran but didn\'t produce a report. Make sure the workflow file is unchanged.'
                    : 'The test run failed before producing results. Open the run on GitHub to see why (often the app didn\'t install or start).',
                [], [], null);
        }

        $introduced = array_values(array_unique($introduced));
        $required = array_values(array_unique([...$earlier, ...$introduced]));
        $byId = [];
        foreach ($report['tests'] as $test) {
            if (is_array($test) && isset($test['id'])) {
                $byId[(string) $test['id']] = $test;
            }
        }

        $tests = [];
        $regressions = [];
        $failed = 0;
        foreach ($required as $id) {
            $test = $byId[$id] ?? null;
            $status = $test['status'] ?? 'missing';
            $ok = $status === 'passed';
            $isRegression = ! $ok && ! in_array($id, $introduced, true);
            if (! $ok) {
                $failed++;
            }
            if ($isRegression) {
                $regressions[] = $id;
            }
            $tests[] = [
                'id'         => $id,
                'title'      => (string) ($test['title'] ?? 'Not found in the test report'),
                'status'     => $status,
                'message'    => isset($test['message']) ? mb_substr((string) $test['message'], 0, 500) : null,
                'required'   => true,
                'regression' => $isRegression,
            ];
        }

        $startedOk = ($report['startedOk'] ?? true) !== false;
        $total = count($required);
        $passed = $total - $failed;

        if (! $startedOk) {
            $summary = 'Your app didn\'t start in the test run, so no test could reach it. Check that `npm start` works from a fresh clone.';
        } elseif ($total === 0) {
            $summary = 'This step has no automated tests; it\'s reviewed on your changes and explanation.';
        } else {
            $summary = "{$passed} of {$total} required tests passed."
                . ($regressions !== [] ? ' ' . count($regressions) . ' of the failures are things that worked before (a regression).' : '');
        }

        return new self(
            status:      $startedOk && $failed === 0 ? self::PASSED : self::FAILED,
            summary:     $summary,
            tests:       $tests,
            regressions: $regressions,
            suite:       isset($report['suite']) ? $report['suite'] . (isset($report['version']) ? '@' . $report['version'] : '') : null,
        );
    }

    /** CI never ran for the commit (no workflow run appeared in time). The milestone can't pass without tests. */
    public static function notRun(): self
    {
        return new self(self::NOT_RUN,
            'No test run appeared for this commit. Check the Actions tab on GitHub: workflows must be enabled and .github/workflows/areyna.yml present.',
            [], [], null);
    }

    public function passed(): bool
    {
        return $this->status === self::PASSED;
    }

    public function toArray(): array
    {
        return [
            'status'      => $this->status,
            'summary'     => $this->summary,
            'suite'       => $this->suite,
            'tests'       => $this->tests,
            'regressions' => $this->regressions,
        ];
    }
}
