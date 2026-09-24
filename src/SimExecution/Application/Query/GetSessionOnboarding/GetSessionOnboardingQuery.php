<?php
namespace Src\SimExecution\Application\Query\GetSessionOnboarding;

final class GetSessionOnboardingQuery
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $projectId,
    ) {}
}
