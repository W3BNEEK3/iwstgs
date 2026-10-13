<?php
namespace Src\Simulation\Application\Query\ListStackVariants;

final class ListStackVariantsQuery
{
    public function __construct(
        public readonly string $projectId,
        public readonly bool $publishedOnly = true,
    ) {}
}
