<?php
namespace Src\Simulation\Application\Command\AddExpectedDeliverable;

final class AddExpectedDeliverableCommand
{
    public function __construct(
        public readonly string  $taskId,
        public readonly string  $type,          // DeliverableType value
        public readonly string  $label,
        public readonly ?string $description = null,
        public readonly bool    $isRequired = true,
        public readonly int     $displayOrder = 0,
    ) {}
}
