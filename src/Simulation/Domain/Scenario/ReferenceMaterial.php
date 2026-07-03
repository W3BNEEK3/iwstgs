<?php
namespace Src\Simulation\Domain\Scenario;

final class ReferenceMaterial
{
    public function __construct(
        private readonly string $id,
        private MaterialType    $type,
        private string          $title,
        private string          $content,
        private ?array          $embeddedSignals,
        private int             $displayOrder,
    ) {}

    public function id(): string { return $this->id; }

    public function toPrimitives(): array
    {
        return [
            'id'               => $this->id,
            'material_type'    => $this->type->value,
            'title'            => $this->title,
            'content'          => $this->content,
            'embedded_signals' => $this->embeddedSignals,
            'display_order'    => $this->displayOrder,
        ];
    }
}
