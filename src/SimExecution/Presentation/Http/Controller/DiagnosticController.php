<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Command\StartDiagnostic\StartDiagnosticCommand;
use Src\SimExecution\Application\Query\GetDiagnosticBoard\GetDiagnosticBoardQuery;
use Src\SimExecution\Domain\Exceptions\DiagnosticContentNotConfiguredException;
use Src\SimExecution\Domain\Exceptions\LearnerNotFoundException;

class DiagnosticController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function show(): View|RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new StartDiagnosticCommand(userId: Auth::id()));
        } catch (LearnerNotFoundException) {
            return redirect()->route('learn.enrol');
        } catch (DiagnosticContentNotConfiguredException) {
            // No diagnostic content authored yet — don't strand the learner, let them through.
            return redirect()->route('learn.catalogue');
        }

        $board = $this->queryBus->ask(new GetDiagnosticBoardQuery(Auth::id()));
        if ($board === null) {
            return redirect()->route('learn.catalogue');
        }

        if ($board->isComplete) {
            return view('learn.diagnostic-complete', ['board' => $board]);
        }

        return view('learn.diagnostic', ['board' => $board]);
    }
}
