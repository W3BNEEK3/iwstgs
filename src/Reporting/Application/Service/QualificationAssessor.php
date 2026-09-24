<?php
namespace Src\Reporting\Application\Service;

use Src\Competency\Domain\Role\RoleDefinitionSummary;
use Src\LearnerProfile\Domain\Profile\DimensionScoreEntry;
use Src\LearnerProfile\Domain\Profile\DimensionTier;

/**
 * Implementation Plan §10.2: for each role, compare its dimension_thresholds
 * against the learner's own dimension_scores. A dimension never scored is
 * Untested, which never clears any threshold — pure comparison logic, no
 * I/O, so the handler assembles the inputs via QueryBus and this just
 * decides qualified vs not.
 *
 * role_definitions.dimension_thresholds is authored in EvalEngine's
 * four-tier evaluation vocabulary (beginning/developing/proficient/
 * distinguished) — confirmed against real seeded data (RoleDefinitionSeeder,
 * ShiftBoardSeeder), not DimensionTier's own vocabulary the migration
 * comment implied. Comparing the raw strings directly silently broke
 * qualification (an unrecognised threshold fell back to rank 0, i.e.
 * trivially satisfied by anyone) — converted through the same
 * evaluation-tier -> DimensionTier mapping UpdateDimensionScoreHandler
 * already established, so both sides compare on one real scale.
 */
final class QualificationAssessor
{
    private const THRESHOLD_TIER_MAP = [
        'beginning'     => 'basic',
        'developing'    => 'intermediate',
        'proficient'    => 'advanced',
        'distinguished' => 'advanced',
    ];

    private const TIER_RANK = [
        'untested'     => 0,
        'basic'        => 1,
        'intermediate' => 2,
        'advanced'     => 3,
    ];

    /**
     * @param RoleDefinitionSummary[] $roles
     * @param DimensionScoreEntry[] $scores
     * @return RoleQualificationResult[]
     */
    public function assess(array $roles, array $scores): array
    {
        $scoresByDimension = [];
        foreach ($scores as $score) {
            $scoresByDimension[$score->dimensionId] = $score->tier;
        }

        return array_map(
            fn (RoleDefinitionSummary $role) => $this->assessRole($role, $scoresByDimension),
            $roles,
        );
    }

    /** @param array<string, DimensionTier> $scoresByDimension */
    private function assessRole(RoleDefinitionSummary $role, array $scoresByDimension): RoleQualificationResult
    {
        $met = [];
        $unmet = [];

        foreach ($role->dimensionThresholds as $dimensionId => $requiredTier) {
            $achievedTier = $scoresByDimension[$dimensionId] ?? DimensionTier::Untested;
            $achievedRank = self::TIER_RANK[$achievedTier->value] ?? 0;
            $normalizedRequiredTier = self::THRESHOLD_TIER_MAP[$requiredTier] ?? $requiredTier;
            $requiredRank = self::TIER_RANK[$normalizedRequiredTier] ?? 0;

            $entry = new DimensionRequirementResult(
                dimensionId:   $dimensionId,
                requiredTier:  $normalizedRequiredTier,
                achievedTier:  $achievedTier->value,
            );

            if ($achievedRank >= $requiredRank) {
                $met[] = $entry;
            } else {
                $unmet[] = $entry;
            }
        }

        return new RoleQualificationResult(
            roleId:      $role->id,
            roleTitle:   $role->title,
            isQualified: $unmet === [],
            met:         $met,
            unmet:       $unmet,
        );
    }
}
