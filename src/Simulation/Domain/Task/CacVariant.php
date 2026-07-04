<?php
namespace Src\Simulation\Domain\Task;

use Src\Simulation\Domain\Cac\CacLevel;

// CacVariant — one per complexity level; unique(task_id, complexity_level)
final class CacVariant
{
    public function __construct(
        private readonly string $id,
        private CacLevel $complexityLevel,
        private string $scenarioText,
        private ?string $scaffoldingTextLow,
        private ?string $scaffoldingTextMid,
        private ?string $scaffoldingTextHigh,
        private ?string $contextTextLow,
        private ?string $contextTextMid,
        private ?string $contextTextHigh,
    ) {}

    public function id(): string { return $this->id; }

    public function toPrimitives(): array
    {
        return [
            'id'                   => $this->id,
            'complexity_level'     => $this->complexityLevel->value,
            'scenario_text'        => $this->scenarioText,
            'scaffolding_text_low' => $this->scaffoldingTextLow,
            'scaffolding_text_mid' => $this->scaffoldingTextMid,
            'scaffolding_text_high'=> $this->scaffoldingTextHigh,
            'context_text_low'     => $this->contextTextLow,
            'context_text_mid'     => $this->contextTextMid,
            'context_text_high'    => $this->contextTextHigh,
        ];
    }
}
