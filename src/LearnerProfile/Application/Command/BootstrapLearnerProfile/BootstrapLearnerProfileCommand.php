<?php

namespace Src\LearnerProfile\Application\Command\BootstrapLearnerProfile;

final class BootstrapLearnerProfileCommand
{
    public function __construct(
        public readonly string $learnerId,
    ) {}
}
