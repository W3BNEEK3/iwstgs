<?php
namespace Src\Guidance\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Src\Guidance\Application\Command\DismissGuideStep\DismissGuideStepCommand;
use Src\Guidance\Application\Command\ResetGuide\ResetGuideCommand;
use Src\Guidance\Application\Command\SetGuideEnabled\SetGuideEnabledCommand;
use Src\Shared\Application\Bus\CommandBus;

class GuideController
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function dismiss(string $step): Response
    {
        $this->commandBus->dispatch(new DismissGuideStepCommand(Auth::id(), $step));

        return response()->noContent();
    }

    public function disable(): Response
    {
        $this->commandBus->dispatch(new SetGuideEnabledCommand(Auth::id(), false));

        return response()->noContent();
    }

    public function enable(): RedirectResponse
    {
        $this->commandBus->dispatch(new SetGuideEnabledCommand(Auth::id(), true));

        return back()->with('success', 'The guide is back on.');
    }

    public function reset(): RedirectResponse
    {
        $this->commandBus->dispatch(new ResetGuideCommand(Auth::id()));

        return back()->with('success', 'All tips will show again as you move around.');
    }
}
