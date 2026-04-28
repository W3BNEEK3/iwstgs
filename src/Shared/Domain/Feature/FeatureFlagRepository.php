<?php

namespace Src\Shared\Domain\Feature;

/**
 * Repository interface for feature flags.
 *
 * This interface lives in the Domain layer — it defines WHAT the domain needs
 * from storage, not HOW it is stored. The Eloquent implementation lives in
 * Infrastructure and is bound in SharedServiceProvider.
 *
 * This is the Repository pattern: the domain defines the contract,
 * infrastructure satisfies it. Domain code never imports Eloquent.
 */
interface FeatureFlagRepository
{
    public function findByKey(string $key): ?FeatureFlag;

    /** @return FeatureFlag[] */
    public function all(): array;
}
