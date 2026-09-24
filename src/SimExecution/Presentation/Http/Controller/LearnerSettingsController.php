<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Deliberately minimal — there is no preferences/notifications system to
 * manage yet. Account info is read-only; the theme control duplicates the
 * one already in the header profile dropdown (header.blade.php explicitly
 * has no room for a standalone toggle) purely for discoverability — same
 * [data-theme-choice] buttons, picked up by the same document-level
 * listener in theme-toggle.js, no new JS needed.
 */
class LearnerSettingsController
{
    public function show(): View
    {
        return view('learn.settings', ['user' => Auth::user()]);
    }
}
