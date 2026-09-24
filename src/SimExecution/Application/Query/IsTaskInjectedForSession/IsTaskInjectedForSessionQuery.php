<?php
namespace Src\SimExecution\Application\Query\IsTaskInjectedForSession;

final class IsTaskInjectedForSessionQuery
{
    public function __construct(
        public readonly string $learnerSessionId,
        public readonly string $taskId,
    ) {}
}
