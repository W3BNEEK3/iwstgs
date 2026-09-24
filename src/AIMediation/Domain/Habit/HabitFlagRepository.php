<?php
namespace Src\AIMediation\Domain\Habit;

/**
 * habit_flags has no domain aggregate — HabitPatternDetector creates or
 * increments an existing flag per learner+description; SuggestionTaskInjector
 * (called at scenario transition, not immediately) later reads the queued,
 * not-yet-suggested ones and attaches the card it injects.
 */
interface HabitFlagRepository
{
    /** Creates (count=1) or increments an existing unresolved flag for this learner+description. Returns its id. */
    public function recordObservation(string $learnerId, string $habitDescription, ?string $sourceTaskId): string;

    /** @return HabitFlagSummary[] unresolved, no suggestion card attached yet */
    public function findQueuedForSuggestion(string $learnerId): array;

    public function attachSuggestionCard(string $habitFlagId, string $injectedCardId): void;

    /**
     * Gap 1 / BLD §11.4 — Returns the canonical dimension_id that has the
     * highest unresolved HabitFlag observation_count for this learner in the
     * given session, or null if no habit flags exist. Used by
     * ScenarioTransitionService to determine which dimension to target next.
     */
    public function findDominantWeaknessDimension(string $learnerId, string $sessionId): ?string;
}
