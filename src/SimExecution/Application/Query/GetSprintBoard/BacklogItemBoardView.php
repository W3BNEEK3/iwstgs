<?php
namespace Src\SimExecution\Application\Query\GetSprintBoard;

final class BacklogItemBoardView
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $priority,
        public readonly string $status,
        public readonly bool $isInjected,
        public readonly ?string $taskId,
        public readonly ?string $visualTreatment,
        public readonly int $submissionCount,
        public readonly ?string $incidentTicketText = null,
    ) {}
}
