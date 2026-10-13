<?php
namespace Src\Simulation\Application\Query\ListScenarioEvents;

use Src\Simulation\Domain\Build\BuildContentRepository;
use Src\Simulation\Domain\Build\ScenarioEvent;

final class ListScenarioEventsHandler
{
    public function __construct(private readonly BuildContentRepository $content) {}

    /** @return ScenarioEvent[] */
    public function handle(ListScenarioEventsQuery $query): array
    {
        return $this->content->eventsForScenario($query->scenarioId);
    }
}
