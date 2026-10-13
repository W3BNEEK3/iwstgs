<?php
namespace Src\Simulation\Domain\Build;

/** One stack a project can be built in (design doc v2-01 §1.2). A harder stack is a harder version of the same project. */
final class StackVariant
{
    /** @param string[] $languages */
    public function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly string $key,
        public readonly string $name,
        public readonly array $languages,
        public readonly int $difficulty,
        public readonly ?string $minRankTier,
        public readonly ?int $minRankLevel,
        public readonly string $templateRepo,
        public readonly ?string $referenceRepo,
        public readonly ?string $acceptanceRef,
        public readonly ?string $workflowSha256,
        public readonly ?string $setupNotes,
        public readonly bool $isPublished,
    ) {}

    private const TIER_ORDER = ['Junior' => 1, 'Mid' => 2, 'Senior' => 3];

    /** Is a learner at this rank allowed to choose the variant? */
    public function isOpenTo(string $rankTier, int $rankLevel): bool
    {
        if ($this->minRankTier === null) {
            return true;
        }

        return [self::TIER_ORDER[$rankTier] ?? 1, $rankLevel]
            >= [self::TIER_ORDER[$this->minRankTier] ?? 1, $this->minRankLevel ?? 1];
    }

    public function rankGateLabel(): ?string
    {
        return $this->minRankTier === null ? null : $this->minRankTier . '-' . ($this->minRankLevel ?? 1);
    }

    /** The repository name we suggest: the project part of the template's name (taskly-express-ejs → taskly). */
    public function suggestedRepoName(): string
    {
        return explode('-', basename($this->templateRepo))[0];
    }

    /** GitHub's "use this template" page, with the repository name filled in. */
    public function templateUrl(string $repoName): string
    {
        return "https://github.com/{$this->templateRepo}/generate?name=" . rawurlencode($repoName);
    }
}
