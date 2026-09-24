<?php
namespace Src\EvalEngine\Application\Query\GetEvaluationResult;

use Src\EvalEngine\Domain\Evaluation\EvaluationResultRepository;
use Src\EvalEngine\Domain\FollowUp\FollowUpPromptRepository;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\Submission\Application\Query\GetSubmissionDetail\GetSubmissionDetailQuery;

final class GetEvaluationResultHandler
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly EvaluationResultRepository $evaluationResults,
        private readonly FollowUpPromptRepository $followUpPrompts,
    ) {}

    public function handle(GetEvaluationResultQuery $query): ?EvaluationResultDetailView
    {
        if ($query->userId === null) {
            return null;
        }

        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery($query->userId));
        if ($learnerId === null) {
            return null;
        }

        $submission = $this->queryBus->ask(new GetSubmissionDetailQuery($query->submissionId));
        if ($submission === null || $submission->submission->learnerId !== $learnerId) {
            return null;
        }

        $evaluation = $this->evaluationResults->findBySubmissionId($query->submissionId);
        if ($evaluation === null) {
            return null;
        }

        $task = $this->queryBus->ask(new GetTaskQuery($submission->submission->taskId));
        $session = $this->queryBus->ask(new GetLearnerSessionQuery($submission->submission->learnerSessionId));

        $followUpPromptText = $evaluation->followUpPromptId !== null
            ? $this->followUpPrompts->findTextById($evaluation->followUpPromptId)
            : null;

        return new EvaluationResultDetailView(
            taskTitle:            $task?->toPrimitives()['title'] ?? '(task unavailable)',
            attemptNumber:        $submission->submission->attemptNumber,
            overallTier:          $evaluation->overallTier,
            passesThreshold:      $evaluation->passesThreshold,
            gapType:              $evaluation->gapType,
            isUncertain:          $evaluation->isUncertain,
            followUpPromptText:   $followUpPromptText,
            dimensions:           $evaluation->dimensions,
            projectId:            $session->projectId ?? '',
        );
    }
}
