<?php

namespace Src\Shared\Domain\Feature;

/**
 * Domain representation of a feature flag.
 *
 * This is a pure domain object — no Eloquent, no database concerns.
 * The repository maps database rows to this class. Application code
 * always works with this class, never with the Eloquent model directly.
 *
 * Why readonly? Feature flags are fetched and checked — never mutated
 * in place. Enable/disable goes through the service, which fetches fresh data.
 */
final class FeatureFlag
{
    public function __construct(
        public readonly string $flagKey,
        public readonly bool   $isEnabled,
        public readonly string $module,
    ) {}
}
