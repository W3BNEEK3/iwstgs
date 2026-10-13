<?php
namespace Src\SourceControl\Domain;

/** What CI says about one commit right now. */
final class CiSnapshot
{
    public const PENDING = 'pending';     // a run is queued or in progress
    public const FINISHED = 'finished';   // a run completed
    public const NO_RUN = 'no_run';       // no Areyna workflow run for this commit (yet)

    public function __construct(
        public readonly string $state,
        public readonly ?string $runUrl = null,
        public readonly ?string $conclusion = null,
        public readonly ?array $report = null,
        /** false when .github/workflows/areyna.yml differs from the template's; null when not checked */
        public readonly ?bool $workflowIntact = null,
    ) {}
}
