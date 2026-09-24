<?php
namespace Src\Submission\Application\Query\CountSubmissionAttempts;

use Src\Submission\Domain\Submission\SubmissionPackageRepository;

final class CountSubmissionAttemptsHandler
{
    public function __construct(private readonly SubmissionPackageRepository $packages) {}

    public function handle(CountSubmissionAttemptsQuery $query): int
    {
        return $this->packages->countAttempts($query->learnerSessionId, $query->taskId);
    }
}
