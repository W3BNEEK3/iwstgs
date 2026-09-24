<?php
namespace Src\LearnerProfile\Application\Command\AdjustCacFromSubmission;

final class AdjustCacFromSubmissionCommand
{
    public function __construct(
        public readonly string $learnerId,
        public readonly bool $passed,
    ) {}
}
