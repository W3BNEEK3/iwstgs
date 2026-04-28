<?php

namespace Src\Shared\Domain\Contract;

/**
 * Contract for content objects that have a published/active lifecycle.
 *
 * ProjectTemplate, ScenarioTemplate, and Task all follow the same two-gate
 * availability pattern: is_published (content author has approved it) and
 * is_active (has not been archived). Both must be true for a learner to see it.
 *
 * Extracting this as a shared interface means availability checks can be
 * written once and applied consistently across all three content types.
 */
interface HasAvailability
{
    public function isPublished(): bool;

    public function isActive(): bool;
}
