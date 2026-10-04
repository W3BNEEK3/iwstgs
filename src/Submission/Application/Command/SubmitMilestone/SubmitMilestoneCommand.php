<?php
namespace Src\Submission\Application\Command\SubmitMilestone;

/** v2 Build track: "this commit completes the milestone", with the learner's explanation of what they did and why. */
final class SubmitMilestoneCommand
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $sessionId,
        public readonly string $taskId,
        public readonly string $commitSha,
        public readonly string $explanation,
    ) {}
}
