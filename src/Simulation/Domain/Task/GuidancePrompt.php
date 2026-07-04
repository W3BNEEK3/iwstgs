<?php
namespace Src\Simulation\Domain\Task;

use Src\Simulation\Domain\Cac\CacLevel;

// GuidancePrompt — Decision 3: pre-authored, deterministically selected at runtime
final class GuidancePrompt
{
    public function __construct(
        private readonly string $id,
        private string $triggerDimension,
        private string $promptText,
        private ?CacLevel $autonomyLevelFilter,
        private DeliveryMode $deliveryMode,
        private int $displayOrder,
    ) {}

    public function id(): string { return $this->id; }

    public function toPrimitives(): array
    {
        return [
            'id'                    => $this->id,
            'trigger_dimension'     => $this->triggerDimension,
            'prompt_text'           => $this->promptText,
            'autonomy_level_filter' => $this->autonomyLevelFilter?->value,
            'delivery_mode'         => $this->deliveryMode->value,
            'display_order'         => $this->displayOrder,
        ];
    }
}
