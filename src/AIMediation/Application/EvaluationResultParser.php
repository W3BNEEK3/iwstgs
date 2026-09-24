<?php
namespace Src\AIMediation\Application;

use Src\AIMediation\Domain\Exceptions\AiProviderException;

/**
 * Turns the AI provider's already-JSON-decoded response array into a typed
 * DTO, validating shape and the fixed tier/gap-type vocabulary rather than
 * trusting the model's output blindly. Same parser regardless of which
 * provider (Claude/Gemini) produced the response — both are prompted for
 * the identical JSON schema.
 */
final class EvaluationResultParser
{
    private const VALID_TIERS = ['beginning', 'developing', 'proficient', 'distinguished'];
    private const VALID_GAP_TYPES = ['knowledge_gap', 'strategy_gap'];

    public function parse(array $response): ParsedEvaluationResult
    {
        $overallTier = $response['overall_tier'] ?? null;
        if (! in_array($overallTier, self::VALID_TIERS, true)) {
            throw new AiProviderException('AI evaluator', 'response overall_tier is missing or invalid: ' . json_encode($overallTier));
        }

        $gapType = $response['gap_type'] ?? null;
        if ($gapType !== null && ! in_array($gapType, self::VALID_GAP_TYPES, true)) {
            $gapType = null;
        }

        $dimensions = [];
        foreach ($response['dimensions'] ?? [] as $d) {
            $tier = $d['tier_achieved'] ?? null;
            if (! in_array($tier, self::VALID_TIERS, true)) {
                continue; // skip an unparseable dimension rather than fail the whole evaluation
            }

            $dimensions[] = new ParsedDimensionEvaluation(
                dimensionId:         (string) ($d['dimension_id'] ?? ''),
                taskDimensionLabel:  (string) ($d['task_dimension_label'] ?? ''),
                tierAchieved:        $tier,
                criteriaMet:         array_values((array) ($d['criteria_met'] ?? [])),
                criteriaMissed:      array_values((array) ($d['criteria_missed'] ?? [])),
                layerScores:         (array) ($d['layer_scores'] ?? []),
                evaluatorNotes:      isset($d['evaluator_notes']) ? (string) $d['evaluator_notes'] : null,
            );
        }

        if ($dimensions === []) {
            throw new AiProviderException('AI evaluator', 'response contained no parseable dimension evaluations');
        }

        $knowledgeAnchors = [];
        foreach ($response['knowledge_anchors_detected'] ?? [] as $anchor) {
            $knowledgeAnchors[] = [
                'conceptId' => $anchor['concept_id'] ?? null,
                'met'       => (bool) ($anchor['met'] ?? false),
            ];
        }

        return new ParsedEvaluationResult(
            dimensions:               $dimensions,
            overallTier:              $overallTier,
            passesThreshold:          (bool) ($response['passes_threshold'] ?? false),
            gapType:                  $gapType,
            isUncertain:              (bool) ($response['is_uncertain'] ?? false),
            knowledgeAnchorsDetected: $knowledgeAnchors,
        );
    }
}
