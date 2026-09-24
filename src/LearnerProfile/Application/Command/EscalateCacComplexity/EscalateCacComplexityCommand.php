<?php
namespace Src\LearnerProfile\Application\Command\EscalateCacComplexity;

final class EscalateCacComplexityCommand
{
    public function __construct(
        public readonly string $learnerId,
    ) {}
}
