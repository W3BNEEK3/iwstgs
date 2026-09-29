<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Guidance\Application\Query\GetGuideSettings\GetGuideSettingsQuery;
use Src\Guidance\Domain\Message\GuideKind;
use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;

/**
 * Account info (read-only), theme choice, and the in-app guide: on/off, and
 * which kinds of message Tiroco may pop up with.
 * The theme control duplicates the header profile dropdown for
 * discoverability, using the same [data-theme-choice] buttons.
 */
class LearnerSettingsController
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly FeatureFlagService $flags,
    ) {}

    public function show(): View
    {
        $nudgesOn = $this->flags->isEnabled('guide.ai_nudges');
        $announcementsOn = $this->flags->isEnabled('guide.announcements');

        return view('learn.settings', [
            'user'            => Auth::user(),
            'guide'           => $this->queryBus->ask(new GetGuideSettingsQuery((string) Auth::id())),
            'guideKinds'      => array_values(array_filter(
                GuideKind::cases(),
                fn (GuideKind $k) => $k === GuideKind::Announcement ? $announcementsOn : $nudgesOn,
            )),
            'announcementsOn' => $announcementsOn,
        ]);
    }
}
