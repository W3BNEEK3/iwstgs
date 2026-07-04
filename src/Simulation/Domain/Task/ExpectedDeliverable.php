<?php
namespace Src\Simulation\Domain\Task;

// ExpectedDeliverable — child entity of the Task aggregate.
final class ExpectedDeliverable
{
    public function __construct(
        private readonly string $id,
        private DeliverableType $type,
        private string $label,
        private ?string $description,
        private bool $isRequired,
        private int $displayOrder,
    ) {}

    public function id(): string { return $this->id; }

    public function toPrimitives(): array
    {
        return [
            'id'            => $this->id,
            'type'          => $this->type->value,
            'label'         => $this->label,
            'description'   => $this->description,
            'is_required'   => $this->isRequired,
            'display_order' => $this->displayOrder,
        ];
    }
}
