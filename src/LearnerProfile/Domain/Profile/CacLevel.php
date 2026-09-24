<?php
namespace Src\LearnerProfile\Domain\Profile;

/**
 * Deliberately a separate declaration from Src\Simulation\Domain\Cac\CacLevel, not a
 * reuse of it — LearnerProfile and Simulation are different bounded contexts, and
 * neither one's Domain layer imports the other's (see Integration Spec's context map).
 * Same three values, same reason both contexts need them independently.
 */
enum CacLevel: string
{
    case Low  = 'low';
    case Mid  = 'mid';
    case High = 'high';

    /** One step up, ceiling at High (mirrors LearnerProfile::escalate()'s Senior-3 ceiling). */
    public function next(): self
    {
        return match ($this) {
            self::Low  => self::Mid,
            self::Mid  => self::High,
            self::High => self::High,
        };
    }

    /** One step down, floor at Low. */
    public function previous(): self
    {
        return match ($this) {
            self::Low  => self::Low,
            self::Mid  => self::Low,
            self::High => self::Mid,
        };
    }
}
