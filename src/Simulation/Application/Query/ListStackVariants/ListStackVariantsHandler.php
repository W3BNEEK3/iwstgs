<?php
namespace Src\Simulation\Application\Query\ListStackVariants;

use Src\Simulation\Domain\Build\BuildContentRepository;
use Src\Simulation\Domain\Build\StackVariant;

final class ListStackVariantsHandler
{
    public function __construct(private readonly BuildContentRepository $content) {}

    /** @return StackVariant[] easiest first */
    public function handle(ListStackVariantsQuery $query): array
    {
        return $this->content->variantsForProject($query->projectId, $query->publishedOnly);
    }
}
