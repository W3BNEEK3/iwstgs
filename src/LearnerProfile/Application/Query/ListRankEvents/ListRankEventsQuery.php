<?php
namespace Src\LearnerProfile\Application\Query\ListRankEvents;

final class ListRankEventsQuery
{
    public function __construct(public readonly string $learnerId) {}
}
