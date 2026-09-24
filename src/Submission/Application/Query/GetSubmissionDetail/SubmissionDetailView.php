<?php
namespace Src\Submission\Application\Query\GetSubmissionDetail;

use Src\Submission\Domain\Submission\SubmissionArtifactDetail;
use Src\Submission\Domain\Submission\SubmissionPackageDetail;

final class SubmissionDetailView
{
    /** @param SubmissionArtifactDetail[] $artifacts */
    public function __construct(
        public readonly SubmissionPackageDetail $submission,
        public readonly array $artifacts,
    ) {}
}
