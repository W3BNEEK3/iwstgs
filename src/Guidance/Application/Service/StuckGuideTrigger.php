<?php
namespace Src\Guidance\Application\Service;

use Src\Guidance\Domain\Message\NewGuideMessage;
use Src\Guidance\Domain\Message\TriggerCatalog;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\FindDiagnosticSessionForTask\FindDiagnosticSessionForTaskQuery;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\GetLearnerSessionQuery;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;

/**
 * The task page reports when a learner has been on it for a long stretch
 * without submitting. The page can't be trusted blindly, so the session must
 * be the learner's own and the task must exist before anything is queued.
 */
final class StuckGuideTrigger
{
    public const MIN_MINUTES = 25;

    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly GuideMessageQueue $queue,
    ) {}

    public function observe(string $userId, string $sessionId, string $taskId, int $minutes): void
    {
        if ($minutes < self::MIN_MINUTES) {
            return;
        }

        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery($userId));
        $session = $this->queryBus->ask(new GetLearnerSessionQuery($sessionId));
        $task = $this->queryBus->ask(new GetTaskQuery($taskId));
        if ($learnerId === null || $session === null || $session->learnerId !== $learnerId || $task === null) {
            return;
        }

        $inCheckIn = $this->queryBus->ask(new FindDiagnosticSessionForTaskQuery($sessionId, $taskId)) !== null;

        $this->queue->queue(new NewGuideMessage(
            userId:     $userId,
            triggerKey: TriggerCatalog::STUCK_IDLE,
            title:      'Stuck? That happens',
            body:       $inCheckIn
                ? 'Take it one question at a time. The ticket in the reference materials has what you need, and a short, honest explanation of your thinking counts for more than a perfect answer.'
                : 'Try writing down the smallest first step and do just that. The hints and reference materials on this page are there to use, and a partial attempt with a clear explanation still gets you useful feedback.',
            facts:      ['task_title' => $task->toPrimitives()['title'], 'minutes_on_task' => $minutes, 'is_check_in' => $inCheckIn],
            contextRef: $taskId,
        ));
    }
}
