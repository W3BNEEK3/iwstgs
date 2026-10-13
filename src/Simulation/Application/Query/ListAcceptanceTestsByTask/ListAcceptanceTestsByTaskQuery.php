<?php
namespace Src\Simulation\Application\Query\ListAcceptanceTestsByTask;

final class ListAcceptanceTestsByTaskQuery
{
    public function __construct(public readonly string $variantId) {}
}
