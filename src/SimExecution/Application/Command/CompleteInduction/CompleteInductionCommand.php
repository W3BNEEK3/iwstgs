<?php
namespace Src\SimExecution\Application\Command\CompleteInduction;

final class CompleteInductionCommand
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $projectId,
    ) {}
}
