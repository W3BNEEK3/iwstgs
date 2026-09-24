<?php
namespace Src\Submission\Application\Query\GetLatestSubmissionId;

use Src\Submission\Domain\Submission\SubmissionPackageRepository;

final class GetLatestSubmissionIdHandler
{
    public function __construct(private readonly SubmissionPackageRepository $packages) {}

    public function handle(GetLatestSubmissionIdQuery $query): ?string
    {
        return $this->packages->findLatestId($query->sessionId, $query->taskId);
    }
}
