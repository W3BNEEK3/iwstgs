<?php
namespace Src\Simulation\Application\Query\GetStackVariant;

use Src\Simulation\Domain\Build\BuildContentRepository;
use Src\Simulation\Domain\Build\StackVariant;

final class GetStackVariantHandler
{
    public function __construct(private readonly BuildContentRepository $content) {}

    public function handle(GetStackVariantQuery $query): ?StackVariant
    {
        return $this->content->findVariant($query->variantId);
    }
}
