<?php
namespace Src\SimExecution\Application\Query\GetStoryFeed;

final class GetStoryFeedQuery
{
    public function __construct(public readonly string $learnerSessionId) {}
}
