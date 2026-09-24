<?php
namespace Src\EvalEngine\Application\Service;

use Src\AIMediation\Domain\Audit\ActionTaken;
use Src\AIMediation\Domain\Audit\AimediationEventRepository;
use Src\AIMediation\Domain\Audit\TriggerType;
use Src\EvalEngine\Domain\Evaluation\EvaluationComplete;
use Src\EvalEngine\Domain\Mismatch\MismatchFlagRepository;
use Src\LearnerProfile\Application\Query\GetLearnerRank\GetLearnerRankQuery;
use Src\Shared\Application\Bus\QueryBus;

/**
 * Implementation Plan §9.4: "check if submission quality significantly
 * exceeds current rank." With no numeric definition of "significantly" in
 * the source docs, this uses the clearest unambiguous signal available:
 * hitting the single highest tier (distinguished) while not already at the
 * top rank (Senior) is a strong, simple underestimation signal. The
 * escalation-prompt UI this could drive doesn't exist yet — same disclosed
 * gap as Phase 8's follow-up prompts — so this only records the flag.
 */
final class MismatchDetector
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly MismatchFlagRepository $mismatchFlags,
        private readonly AimediationEventRepository $aimediationEvents,
    ) {}

    public function detect(EvaluationComplete $event): void
    {
        if ($event->overallTier !== 'distinguished') {
            return;
        }

        $rank = $this->queryBus->ask(new GetLearnerRankQuery($event->learnerId));
        if ($rank === null || $rank->rankTier === 'Senior') {
            return;
        }

        $this->mismatchFlags->create($event->learnerId, 'underestimation');

        $this->aimediationEvents->record(
            learnerId:        $event->learnerId,
            sessionId:        $event->learnerSessionId,
            triggerType:      TriggerType::MismatchDetected,
            triggerSourceId:  $event->submissionId,
            actionTaken:      ActionTaken::MismatchFlagged,
            actionDetail:     ['current_rank' => "{$rank->rankTier}-{$rank->rankLevel}", 'overall_tier' => $event->overallTier],
            isDeterministic:  true,
        );
    }
}
