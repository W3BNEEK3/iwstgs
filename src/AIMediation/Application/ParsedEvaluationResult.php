<?php
namespace Src\AIMediation\Application;

final class ParsedEvaluationResult
{
    /**
     * @param ParsedDimensionEvaluation[] $dimensions
     * @param array<int, array{conceptId: ?string, met: bool}> $knowledgeAnchorsDetected
     */
    public function __construct(
        public readonly array $dimensions,
        public readonly string $overallTier,
        public readonly bool $passesThreshold,
        public readonly ?string $gapType,
        public readonly bool $isUncertain,
        public readonly array $knowledgeAnchorsDetected,
    ) {}
}
