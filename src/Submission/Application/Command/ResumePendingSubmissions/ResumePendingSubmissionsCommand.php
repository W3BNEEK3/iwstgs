<?php
namespace Src\Submission\Application\Command\ResumePendingSubmissions;

/** Check again whether CI has finished for submissions that are waiting (all, one session's, or one commit's). */
final class ResumePendingSubmissionsCommand
{
    public function __construct(
        public readonly ?string $learnerSessionId = null,
        public readonly ?string $commitSha = null,
    ) {}
}
