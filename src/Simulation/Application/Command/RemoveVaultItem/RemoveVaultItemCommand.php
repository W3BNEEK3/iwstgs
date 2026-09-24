<?php

namespace Src\Simulation\Application\Command\RemoveVaultItem;

final class RemoveVaultItemCommand
{
    public function __construct(public readonly string $id) {}
}
