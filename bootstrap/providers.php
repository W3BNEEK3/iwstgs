<?php

return [
    // Laravel core
    App\Providers\AppServiceProvider::class,

    // IWSTGS — Shared kernel (must be first: other modules depend on its bindings)
    Src\Shared\Infrastructure\Provider\SharedServiceProvider::class,

    // IWSTGS — Bounded context providers (alphabetical for readability)
    Src\AIMediation\Infrastructure\Provider\AIMediationServiceProvider::class,
    Src\Competency\Infrastructure\Provider\CompetencyServiceProvider::class,
    Src\Content\Infrastructure\Provider\ContentServiceProvider::class,
    Src\EvalEngine\Infrastructure\Provider\EvalEngineServiceProvider::class,
    Src\Identity\Infrastructure\Provider\IdentityServiceProvider::class,
    Src\LearnerProfile\Infrastructure\Provider\LearnerProfileServiceProvider::class,
    Src\Organizations\Infrastructure\Provider\OrganizationsServiceProvider::class,
    Src\Reporting\Infrastructure\Provider\ReportingServiceProvider::class,
    Src\SimExecution\Infrastructure\Provider\SimExecutionServiceProvider::class,
    Src\Simulation\Infrastructure\Provider\SimulationServiceProvider::class,
    Src\Submission\Infrastructure\Provider\SubmissionServiceProvider::class,
];
