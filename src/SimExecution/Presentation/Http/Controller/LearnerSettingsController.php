<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Guidance\Domain\GuidePreferenceRepository;

/**
 * Account info (read-only), theme choice, and the in-app guide on/off switch.
 * The theme control duplicates the header profile dropdown for
 * discoverability, using the same [data-theme-choice] buttons.
 */
class LearnerSettingsController
{
    public function __construct(private readonly GuidePreferenceRepository $guidePreferences) {}

    public function show(): View
    {
        return view('learn.settings', [
            'user'  => Auth::user(),
            'guide' => $this->guidePreferences->forUser((string) Auth::id()),
        ]);
    }
}
