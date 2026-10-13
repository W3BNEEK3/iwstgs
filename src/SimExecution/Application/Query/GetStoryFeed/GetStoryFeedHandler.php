<?php
namespace Src\SimExecution\Application\Query\GetStoryFeed;

use Src\SimExecution\Domain\Story\LearnerScenarioEventRepository;
use Src\SimExecution\Domain\Story\StoryMessage;

final class GetStoryFeedHandler
{
    public function __construct(private readonly LearnerScenarioEventRepository $fired) {}

    /** @return StoryMessage[] newest first */
    public function handle(GetStoryFeedQuery $query): array
    {
        return $this->fired->feed($query->learnerSessionId);
    }
}
