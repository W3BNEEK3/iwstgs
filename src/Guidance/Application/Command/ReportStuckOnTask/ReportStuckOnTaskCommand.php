<?php
namespace Src\Guidance\Application\Command\ReportStuckOnTask;

final class ReportStuckOnTaskCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $sessionId,
        public readonly string $taskId,
        public readonly int $minutes,
    ) {}
}
