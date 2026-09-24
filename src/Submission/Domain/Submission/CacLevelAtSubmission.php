<?php
namespace Src\Submission\Domain\Submission;

/**
 * Mirrors Simulation\Domain\Cac\CacLevel by value — a separate type on
 * purpose. This is a historical snapshot column (the CAC level in effect
 * at the moment of submission), not a live reference into Simulation's
 * domain, so Submission keeps its own copy rather than depending on
 * another bounded context's enum.
 */
enum CacLevelAtSubmission: string
{
    case Low  = 'low';
    case Mid  = 'mid';
    case High = 'high';
}
