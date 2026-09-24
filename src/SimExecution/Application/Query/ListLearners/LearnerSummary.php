<?php
namespace Src\SimExecution\Application\Query\ListLearners;

final class LearnerSummary
{
    public function __construct(
        public readonly string $id,
        public readonly string $fullname,
        public readonly string $entryCategory,
        public readonly ?int $yearsExperience,
    ) {}
}
