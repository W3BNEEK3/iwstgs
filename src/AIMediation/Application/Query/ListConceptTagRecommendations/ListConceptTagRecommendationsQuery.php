<?php
namespace Src\AIMediation\Application\Query\ListConceptTagRecommendations;

final class ListConceptTagRecommendationsQuery
{
    public function __construct(
        public readonly string $status = 'pending', // pending | approved | dismissed
    ) {}
}
