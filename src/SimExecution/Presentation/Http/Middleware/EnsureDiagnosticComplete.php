<?php
namespace Src\SimExecution\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\SimExecution\Domain\Diagnostic\DiagnosticSessionRepository;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;
use Symfony\Component\HttpFoundation\Response;

/**
 * StartDiagnosticHandler only ever redirects a learner to /learn/diagnostic
 * ONCE, right after /learn/enrol completes — a genuine gap: nothing re-checks
 * "does this learner still have an incomplete diagnostic" on a normal
 * return visit (e.g. they closed the tab mid-diagnostic and logged back in
 * later), so they'd land straight on the real catalogue with an unfinished
 * diagnostic_sessions row sitting in_progress forever. This middleware
 * closes that gap on every subsequent request to a gated route, not just
 * the first one.
 */
class EnsureDiagnosticComplete
{
    public function __construct(
        private readonly FeatureFlagService $flags,
        private readonly LearnerRepository $learners,
        private readonly DiagnosticSessionRepository $diagnosticSessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->flags->isEnabled('simulation.diagnostic_assessment')) {
            return $next($request);
        }

        $userId = $request->user()?->id;
        $learner = $userId !== null ? $this->learners->findByUserId($userId) : null;
        if ($learner === null) {
            return $next($request); // not a learner yet — nothing to gate, /learn/enrol handles this case
        }

        $inProgress = $this->diagnosticSessions->findInProgressForLearner($learner->id());
        if ($inProgress !== null) {
            return redirect()->route('learn.diagnostic');
        }

        return $next($request);
    }
}
