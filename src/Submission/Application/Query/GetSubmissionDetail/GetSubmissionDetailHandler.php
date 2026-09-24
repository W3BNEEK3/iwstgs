<?php
namespace Src\Submission\Application\Query\GetSubmissionDetail;

use Src\Submission\Domain\Submission\SubmissionArtifactRepository;
use Src\Submission\Domain\Submission\SubmissionPackageRepository;

final class GetSubmissionDetailHandler
{
    public function __construct(
        private readonly SubmissionPackageRepository $packages,
        private readonly SubmissionArtifactRepository $artifacts,
    ) {}

    public function handle(GetSubmissionDetailQuery $query): ?SubmissionDetailView
    {
        $detail = $this->packages->findDetailById($query->submissionId);
        if ($detail === null) {
            return null;
        }

        return new SubmissionDetailView(
            submission: $detail,
            artifacts:  $this->artifacts->findAllForSubmission($query->submissionId),
        );
    }
}
