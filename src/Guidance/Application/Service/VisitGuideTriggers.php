<?php
namespace Src\Guidance\Application\Service;

use Src\Guidance\Domain\Content\FeatureAnnouncement;
use Src\Guidance\Domain\Content\GuideContentRepository;
use Src\Guidance\Domain\GuidePreferenceRepository;
use Src\Guidance\Domain\Message\GuideMessageRepository;
use Src\Guidance\Domain\Message\NewGuideMessage;
use Src\Guidance\Domain\Message\TriggerCatalog;
use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;
use Src\SimExecution\Application\Query\GetLearnerSession\LearnerSessionView;
use Src\SimExecution\Application\Query\ListSessionsForLearner\ListSessionsForLearnerQuery;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;

/**
 * What Tiroco notices when a learner opens a page: coming back after a
 * break, a calm page where a tip fits, and announcements they haven't seen.
 * Runs from the guide's own background request, never while the page itself
 * renders, and only does cheap database checks.
 */
final class VisitGuideTriggers
{
    private const AWAY_DAYS = 5;

    /** Pages where nothing urgent is happening, so a general tip is welcome. */
    private const CALM_PAGES = ['catalogue', 'profile', 'sprint-board'];

    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly GuideMessageQueue $queue,
        private readonly GuideMessageRepository $messages,
        private readonly GuidePreferenceRepository $preferences,
        private readonly GuideContentRepository $content,
        private readonly ContentPicker $picker,
        private readonly FeatureFlagService $flags,
    ) {}

    public function observe(string $userId, ?string $page, \DateTimeInterface $userCreatedAt): void
    {
        $previousVisit = $this->preferences->touchActive($userId);

        $this->announcements($userId, $userCreatedAt);

        if ($previousVisit !== null && strtotime($previousVisit) <= now()->subDays(self::AWAY_DAYS)->getTimestamp()) {
            $this->welcomeBack($userId, $previousVisit);
        }

        if (in_array($page, self::CALM_PAGES, true)) {
            $this->quietMoment($userId, $page);
        }
    }

    private function announcements(string $userId, \DateTimeInterface $userCreatedAt): void
    {
        $seen = $this->messages->usedIds($userId, 'announcement_id');

        foreach (array_reverse($this->content->publishedAnnouncements()) as $announcement) {
            if (in_array($announcement->id, $seen, true) || ! $this->isLive($announcement)) {
                continue;
            }
            // Someone who joined after it shipped never knew life without it: nothing is "new" to them.
            if ($announcement->publishedAt === null || strtotime($announcement->publishedAt) < $userCreatedAt->getTimestamp()) {
                continue;
            }

            $this->queue->queue(new NewGuideMessage(
                userId:         $userId,
                triggerKey:     TriggerCatalog::ANNOUNCEMENT,
                title:          $announcement->title ?? $announcement->internalTitle,
                body:           $announcement->body ?? '',
                contextRef:     $announcement->id,
                ctaLabel:       $announcement->linkUrl !== null ? 'Take a look' : null,
                ctaUrl:         $announcement->linkUrl,
                announcementId: $announcement->id,
                needsWriting:   false, // approved text, shown exactly as published
            ));
        }
    }

    public function isLive(FeatureAnnouncement $announcement): bool
    {
        return $announcement->isPublished()
            && $announcement->title !== null && $announcement->body !== null
            && ($announcement->featureFlag === null || $this->flags->isEnabled($announcement->featureFlag));
    }

    private function welcomeBack(string $userId, string $previousVisit): void
    {
        $days = (int) floor((now()->getTimestamp() - strtotime($previousVisit)) / 86400);
        $facts = ['days_away' => $days];
        $cta = [null, null];

        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery($userId));
        if ($learnerId !== null) {
            /** @var LearnerSessionView[] $sessions */
            $sessions = array_filter(
                $this->queryBus->ask(new ListSessionsForLearnerQuery($learnerId)),
                fn (LearnerSessionView $s) => $s->status === 'active',
            );
            $session = end($sessions) ?: null;
            $project = $session !== null ? $this->queryBus->ask(new GetProjectQuery($session->projectId)) : null;
            if ($project !== null) {
                $facts['current_project'] = $project->title();
                $cta = ['Back to your board', route('learn.sprint-board', $session->projectId, false)];
            }
        }

        $where = isset($facts['current_project'])
            ? 'Your board on ' . trim(explode('—', $facts['current_project'])[0]) . ' is just as you left it.'
            : 'Pick up where you left off whenever you are ready.';

        $this->queue->queue(new NewGuideMessage(
            userId:     $userId,
            triggerKey: TriggerCatalog::WELCOME_BACK,
            title:      'Welcome back',
            body:       "It's been {$days} days. {$where} A quick look at your last result's feedback is a good way to warm up.",
            facts:      $facts,
            ctaLabel:   $cta[0],
            ctaUrl:     $cta[1],
        ));
    }

    private function quietMoment(string $userId, string $page): void
    {
        $tip = $this->picker->tipFor($userId, $page);
        if ($tip === null) {
            return;
        }

        $this->queue->queue(new NewGuideMessage(
            userId:     $userId,
            triggerKey: TriggerCatalog::QUIET_MOMENT,
            title:      "Tip: {$tip->area}",
            body:       $tip->text,
            facts:      ['tip' => $tip->text, 'page' => $page],
            tipId:      $tip->id,
        ));
    }
}
