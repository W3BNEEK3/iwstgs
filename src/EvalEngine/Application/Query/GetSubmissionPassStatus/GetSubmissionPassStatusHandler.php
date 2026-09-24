<?php
namespace Src\EvalEngine\Application\Query\GetSubmissionPassStatus;

use Src\EvalEngine\Domain\Evaluation\EvaluationResultRepository;

/**
 * Unlike GetEvaluationResultQuery (learner-facing, userId-gated for the
 * evaluation-result page), this is a system-internal check with no HTTP
 * user in the loop — used by ScenarioTransitionService to gate CAC
 * Complexity escalation on BLD §7.3's "consistent performance" test.
 */
final class GetSubmissionPassStatusHandler
{
    public function __construct(private readonly EvaluationResultRepository $evaluationResults) {}

    public function handle(GetSubmissionPassStatusQuery $query): ?bool
    {
        return $this->evaluationResults->findBySubmissionId($query->submissionId)?->passesThreshold;
    }
}
