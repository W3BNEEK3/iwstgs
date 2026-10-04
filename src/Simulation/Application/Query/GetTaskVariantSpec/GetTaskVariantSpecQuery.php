<?php
namespace Src\Simulation\Application\Query\GetTaskVariantSpec;

final class GetTaskVariantSpecQuery
{
    public function __construct(
        public readonly string $taskId,
        public readonly string $variantId,
    ) {}
}
