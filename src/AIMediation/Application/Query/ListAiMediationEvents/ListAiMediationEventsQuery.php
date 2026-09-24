<?php
namespace Src\AIMediation\Application\Query\ListAiMediationEvents;

final class ListAiMediationEventsQuery
{
    /** @param array<string, string> $filters */
    public function __construct(
        public readonly array $filters = [],
        public readonly int   $perPage = 30,
    ) {}
}
