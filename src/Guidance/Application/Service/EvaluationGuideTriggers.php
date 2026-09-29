<?php
namespace Src\Guidance\Application\Service;

use Src\Competency\Application\Query\ListCompetenceDimensions\ListCompetenceDimensionsQuery;
use Src\EvalEngine\Application\Query\GetEvaluationResult\EvaluationResultDetailView;
use Src\EvalEngine\Application\Query\GetEvaluationResult\GetEvaluationResultQuery;
use Src\EvalEngine\Application\Query\GetRecentDimensionTiers\GetRecentDimensionTiersQuery;
use Src\EvalEngine\Domain\Evaluation\DimensionEvaluationSummary;
use Src\EvalEngine\Domain\Evaluation\EvaluationComplete;
use Src\Guidance\Domain\Message\NewGuideMessage;
use Src\Guidance\Domain\Message\TriggerCatalog;
use Src\LearnerProfile\Application\Query\GetLearnerRank\GetLearnerRankQuery;
use Src\LearnerProfile\Application\Query\ListRankEvents\ListRankEventsQuery;
use Src\LearnerProfile\Domain\Rank\RankEventSummary;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\FindDiagnosticSessionForTask\FindDiagnosticSessionForTaskQuery;
use Src\SimExecution\Application\Query\GetUserIdForLearner\GetUserIdForLearnerQuery;
use Src\Submission\Application\Query\CountSubmissionAttempts\CountSubmissionAttemptsQuery;
use Src\Submission\Application\Query\GetSubmissionDetail\GetSubmissionDetailQuery;

/**
 * What Tiroco notices when a submission has been evaluated (design doc v2-05
 * §3.1): a second failed attempt, short explanations with weak communication
 * scores, a skill that keeps scoring low, a rank change, and — after a pass —
 * a calm moment for a tip. Only facts go into each message; the fallback text
 * is written here so every message is useful even with no AI provider.
 */
final class EvaluationGuideTriggers
{
    private const PASSING_TIERS = ['proficient', 'distinguished'];
    private const THIN_EXPLANATION_WORDS = 60;
    private const WEAK_WINDOW = 5;
    private const WEAK_THRESHOLD = 3;
    private const COMMUNICATION = 'dim_communication';

    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly GuideMessageQueue $queue,
        private readonly ContentPicker $picker,
    ) {}

    public function observe(EvaluationComplete $event): void
    {
        $userId = $this->queryBus->ask(new GetUserIdForLearnerQuery($event->learnerId));
        if ($userId === null) {
            return;
        }

        // The check-in (diagnostic) has its own authored guidance and no retries.
        if ($this->queryBus->ask(new FindDiagnosticSessionForTaskQuery($event->learnerSessionId, $event->taskId)) !== null) {
            return;
        }

        /** @var EvaluationResultDetailView|null $result */
        $result = $this->queryBus->ask(new GetEvaluationResultQuery($userId, $event->submissionId));
        if ($result === null) {
            return;
        }

        $labels = [];
        foreach ($this->queryBus->ask(new ListCompetenceDimensionsQuery()) as $dimension) {
            $labels[$dimension->id] = $dimension->shortLabel;
        }

        $this->rankChange($userId, $event->learnerId);

        if ($event->passesThreshold) {
            $this->quietMoment($userId);
        } else {
            $this->repeatFail($userId, $event, $result, $labels);
        }

        $this->thinExplanations($userId, $event, $result);
        $this->weakDimension($userId, $event, $result, $labels);
    }

    private function repeatFail(string $userId, EvaluationComplete $event, EvaluationResultDetailView $result, array $labels): void
    {
        $attempts = (int) $this->queryBus->ask(new CountSubmissionAttemptsQuery($event->learnerSessionId, $event->taskId));
        if ($attempts < 2) {
            return;
        }

        $missed = [];
        $weakSkills = [];
        /** @var DimensionEvaluationSummary $d */
        foreach ($result->dimensions as $d) {
            if (in_array($d->tierAchieved, self::PASSING_TIERS, true)) {
                continue;
            }
            $weakSkills[] = $labels[$d->dimensionId] ?? $d->taskDimensionLabel;
            array_push($missed, ...array_slice($d->criteriaMissed, 0, 2));
        }
        $missed = array_slice(array_values(array_unique($missed)), 0, 4);

        $body = "Two tries in and it's not there yet, which is completely normal. "
            . ($missed !== []
                ? 'Pick one missed point to focus on first: "' . $this->shorten($missed[0]) . '". Re-read the brief and reference materials with just that in mind, then explain in your write-up how you covered it.'
                : 'Re-read the brief and reference materials, then check your work against each expected deliverable before submitting again.');

        $this->queue->queue(new NewGuideMessage(
            userId:     $userId,
            triggerKey: TriggerCatalog::REPEAT_FAIL,
            title:      'One thing at a time',
            body:       $body,
            facts:      [
                'task_title'      => $result->taskTitle,
                'attempts_so_far' => $attempts,
                'missed_criteria' => $missed,
                'weak_skills'     => array_values(array_unique($weakSkills)),
            ],
            contextRef: $event->taskId,
            ctaLabel:   'Back to the task',
            ctaUrl:     route('learn.task-submit', [$event->learnerSessionId, $event->taskId], false),
        ));
    }

    private function thinExplanations(string $userId, EvaluationComplete $event, EvaluationResultDetailView $result): void
    {
        $communication = null;
        foreach ($result->dimensions as $d) {
            if ($d->dimensionId === self::COMMUNICATION) {
                $communication = $d;
            }
        }
        if ($communication === null || in_array($communication->tierAchieved, self::PASSING_TIERS, true)) {
            return;
        }

        $recent = $this->queryBus->ask(new GetRecentDimensionTiersQuery($event->learnerId, self::COMMUNICATION, 2));
        if (count($recent) < 2 || array_intersect($recent, self::PASSING_TIERS) !== []) {
            return;
        }

        $text = $this->queryBus->ask(new GetSubmissionDetailQuery($event->submissionId))?->submission->layer1Text ?? '';
        $words = str_word_count(strip_tags($text));
        if ($words >= self::THIN_EXPLANATION_WORDS) {
            return;
        }

        $this->queue->queue(new NewGuideMessage(
            userId:     $userId,
            triggerKey: TriggerCatalog::THIN_EXPLANATIONS,
            title:      'Give your explanation three parts',
            body:       "Your last explanation was about {$words} words. Try three short parts: what you did, why you chose that approach, and how you checked it works. Reviewers can only credit reasoning they can see.",
            facts:      ['explanation_words' => $words, 'recent_communication_tiers' => $recent, 'task_title' => $result->taskTitle],
        ));
    }

    private function weakDimension(string $userId, EvaluationComplete $event, EvaluationResultDetailView $result, array $labels): void
    {
        $rankTier = $this->queryBus->ask(new GetLearnerRankQuery($event->learnerId))?->rankTier;

        foreach ($result->dimensions as $d) {
            if (in_array($d->tierAchieved, self::PASSING_TIERS, true)) {
                continue;
            }

            $recent = $this->queryBus->ask(new GetRecentDimensionTiersQuery($event->learnerId, $d->dimensionId, self::WEAK_WINDOW));
            $below = count(array_filter($recent, fn (string $t) => ! in_array($t, self::PASSING_TIERS, true)));
            if ($below < self::WEAK_THRESHOLD) {
                continue;
            }

            $resource = $this->picker->resourceFor($userId, $d->dimensionId, $rankTier);
            if ($resource === null) {
                continue;
            }

            $skill = $labels[$d->dimensionId] ?? $d->taskDimensionLabel;
            $queued = $this->queue->queue(new NewGuideMessage(
                userId:     $userId,
                triggerKey: TriggerCatalog::WEAK_DIMENSION,
                title:      "Extra practice: {$skill}",
                body:       "{$skill} has been tricky in a few recent tasks. {$resource->name} could help alongside your work here: {$resource->blurb}",
                facts:      [
                    'skill'              => $skill,
                    'below_proficient'   => "{$below} of the last " . count($recent) . ' results',
                    'resource_name'      => $resource->name,
                    'resource_blurb'     => $resource->blurb,
                    'resource_kind'      => $resource->kind,
                    'resource_is_free'   => $resource->isFree,
                ],
                contextRef: $d->dimensionId,
                ctaLabel:   "Open {$resource->name}",
                ctaUrl:     $resource->url,
                resourceId: $resource->id,
            ));

            if ($queued !== null) {
                return; // one resource at a time
            }
        }
    }

    private function rankChange(string $userId, string $learnerId): void
    {
        /** @var RankEventSummary[] $events */
        $events = $this->queryBus->ask(new ListRankEventsQuery($learnerId));
        $latest = end($events) ?: null;
        if ($latest === null || $latest->createdAt === null || strtotime($latest->createdAt) < now()->subMinutes(10)->getTimestamp()) {
            return;
        }

        $to = "{$latest->toRankTier}-{$latest->toRankLevel}";
        [$title, $body] = match ($latest->eventType) {
            'escalation', 'sub_level_progression' => [
                "You're now {$to}",
                "Your recent work moved you up to {$to}. Tasks will give you a little less hand-holding and a bit more complexity from here, and new roles may have unlocked in the project list.",
            ],
            'de_escalation' => [
                'A little more support for now',
                "Your rank is now {$to}. That's not a penalty: tasks will come with more guidance while you find your feet again, and strong submissions move you back up.",
            ],
            default => [null, null],
        };
        if ($title === null) {
            return;
        }

        $this->queue->queue(new NewGuideMessage(
            userId:     $userId,
            triggerKey: TriggerCatalog::RANK_CHANGE,
            title:      $title,
            body:       $body,
            facts:      [
                'change' => $latest->eventType === 'de_escalation' ? 'down' : 'up',
                'from'   => $latest->fromRankTier !== null ? "{$latest->fromRankTier}-{$latest->fromRankLevel}" : null,
                'to'     => $to,
            ],
            contextRef: $latest->id,
        ));
    }

    private function quietMoment(string $userId): void
    {
        $tip = $this->picker->tipFor($userId, 'evaluation-result');
        if ($tip === null) {
            return;
        }

        $this->queue->queue(new NewGuideMessage(
            userId:     $userId,
            triggerKey: TriggerCatalog::QUIET_MOMENT,
            title:      "Tip: {$tip->area}",
            body:       $tip->text,
            facts:      ['tip' => $tip->text, 'moment' => 'just passed a task'],
            tipId:      $tip->id,
        ));
    }

    private function shorten(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return mb_strlen($text) > 110 ? rtrim(mb_substr($text, 0, 107)) . '…' : rtrim($text, '.');
    }
}
