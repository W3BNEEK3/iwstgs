<?php
namespace Src\SimExecution\Domain\Story;

interface LearnerScenarioEventRepository
{
    /**
     * Records that an event fired for a session, with a copy of what was shown.
     * Returns false when it had already fired: every event fires once per session.
     *
     * @param array<string, mixed> $result
     */
    public function recordFired(string $learnerSessionId, string $scenarioEventId, array $result): bool;

    /** @return StoryMessage[] newest first */
    public function feed(string $learnerSessionId): array;
}
