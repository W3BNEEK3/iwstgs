<?php
namespace Src\AIMediation\Application;

final class ParsedDimensionEvaluation
{
    /**
     * @param string[] $criteriaMet
     * @param string[] $criteriaMissed
     * @param array<string, string> $layerScores
     */
    public function __construct(
        public readonly string $dimensionId,
        public readonly string $taskDimensionLabel,
        public readonly string $tierAchieved,
        public readonly array $criteriaMet,
        public readonly array $criteriaMissed,
        public readonly array $layerScores,
        public readonly ?string $evaluatorNotes,
    ) {}
}
