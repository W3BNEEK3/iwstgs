<?php
namespace Src\Simulation\Application\Query\GetTaskVariantSpec;

use Src\Simulation\Domain\Build\BuildContentRepository;
use Src\Simulation\Domain\Build\TaskVariantSpec;

final class GetTaskVariantSpecHandler
{
    public function __construct(private readonly BuildContentRepository $content) {}

    public function handle(GetTaskVariantSpecQuery $query): ?TaskVariantSpec
    {
        return $this->content->findSpec($query->taskId, $query->variantId);
    }
}
