<?php
namespace Src\Simulation\Application\Command\AddGuidancePrompt;

final class AddGuidancePromptCommand
{
    public function __construct(
        public readonly string  $taskId,
        public readonly string  $triggerDimension,
        public readonly string  $promptText,
        public readonly string  $deliveryMode,          // 'proactive' | 'reactive'
        public readonly ?string $autonomyLevelFilter,   // 'low'|'mid'|'high'|null
        public readonly int     $displayOrder = 0,
    ) {}
}
