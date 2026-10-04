<?php
namespace Src\Simulation\Application\Query\ListAcceptanceTestsByTask;

use Src\Simulation\Domain\Build\BuildContentRepository;

final class ListAcceptanceTestsByTaskHandler
{
    public function __construct(private readonly BuildContentRepository $content) {}

    /** @return array<string, string[]> task id => acceptance test IDs that task introduces */
    public function handle(ListAcceptanceTestsByTaskQuery $query): array
    {
        return $this->content->acceptanceTestsByTask($query->variantId);
    }
}
