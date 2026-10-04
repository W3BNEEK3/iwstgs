<?php
namespace Src\SimExecution\Application\Query\GetBuildBoard;

use Src\SimExecution\Application\Query\GetBuildProgress\BuildProgressView;
use Src\SimExecution\Domain\Story\StoryMessage;
use Src\Simulation\Domain\Build\StackVariant;
use Src\SourceControl\Domain\GitHubConnection;
use Src\SourceControl\Domain\LinkedRepository;

/** Everything on a Build project's board: the milestone path, fixes to do, the story so far, and the repository. */
final class BuildBoardView
{
    /**
     * @param array<int, array{id: string, title: string, is_current: bool, milestones: array}> $chapters
     * @param array<int, array{task_id: string, title: string, type: string, attempted: bool, ticket: ?string}> $cards
     * @param StoryMessage[] $feed newest first
     */
    public function __construct(
        public readonly string $projectId,
        public readonly string $projectTitle,
        public readonly string $sessionId,
        public readonly string $sessionStatus,
        public readonly StackVariant $variant,
        public readonly BuildProgressView $progress,
        public readonly array $chapters,
        public readonly ?string $chapterTitle,
        public readonly ?string $chapterStory,
        public readonly array $cards,
        public readonly array $feed,
        public readonly ?GitHubConnection $github,
        public readonly ?LinkedRepository $repository,
        public readonly string $suggestedRepoName,
    ) {}

    public function needsRepository(): bool
    {
        return $this->repository === null || ! $this->repository->isUsable();
    }

    public function isComplete(): bool
    {
        return $this->sessionStatus === 'complete';
    }

    public function passedCount(): int
    {
        return count($this->progress->passedTaskIds);
    }

    public function milestoneCount(): int
    {
        return count($this->progress->milestones);
    }
}
