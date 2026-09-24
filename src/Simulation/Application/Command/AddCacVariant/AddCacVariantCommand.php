<?php
namespace Src\Simulation\Application\Command\AddCacVariant;

final class AddCacVariantCommand
{
    public function __construct(
        public readonly string  $taskId,
        public readonly string  $complexityLevel,       // CacLevel value: low|mid|high
        public readonly string  $scenarioText,
        public readonly ?string $scaffoldingTextLow  = null,
        public readonly ?string $scaffoldingTextMid  = null,
        public readonly ?string $scaffoldingTextHigh = null,
        public readonly ?string $contextTextLow      = null,
        public readonly ?string $contextTextMid      = null,
        public readonly ?string $contextTextHigh     = null,
    ) {}
}
