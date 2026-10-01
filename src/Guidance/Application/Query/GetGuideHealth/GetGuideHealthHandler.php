<?php
namespace Src\Guidance\Application\Query\GetGuideHealth;

use Src\Guidance\Application\Service\GuideSettings;
use Src\Guidance\Domain\Message\GuideMessageRepository;
use Src\Guidance\Domain\Message\TriggerCatalog;

/** Admin overview: every trigger's settings next to how learners responded to it. */
final class GetGuideHealthHandler
{
    public function __construct(
        private readonly GuideMessageRepository $messages,
        private readonly GuideSettings $settings,
    ) {}

    public function handle(GetGuideHealthQuery $query): GuideHealthView
    {
        $stats = $this->messages->statsByTrigger(now()->subDays($query->days));

        $triggers = [];
        foreach (TriggerCatalog::all() as $key => $trigger) {
            $triggers[] = [
                'key'            => $key,
                'label'          => $trigger['label'],
                'kind'           => $trigger['kind']->value,
                'enabled'        => $this->settings->isTriggerEnabled($key),
                'cooldown_hours' => $this->settings->cooldownHours($key),
            ] + ($stats[$key] ?? ['shown' => 0, 'helpful' => 0, 'not_helpful' => 0, 'quick_dismiss' => 0]);
        }

        return new GuideHealthView(
            days:     $query->days,
            dailyCap: $this->settings->dailyCap(),
            triggers: $triggers,
            recent:   $this->messages->latest(50, $query->triggerKey),
        );
    }
}
