<?php
namespace Src\SimExecution\Presentation\Http\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Shared\Application\Bus\QueryBus;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;
use Src\SimExecution\Application\Query\GetBuildBoard\GetBuildBoardQuery;

/** A Build project's home: the milestone path, the story so far, and the learner's repository. */
class BuildBoardController
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly FeatureFlagService $flags,
    ) {}

    public function show(string $project): View
    {
        $board = $this->queryBus->ask(new GetBuildBoardQuery(Auth::id(), $project));
        abort_if($board === null, 404);

        return view('learn.build-board', [
            'board'        => $board,
            'githubOn'     => $this->flags->isEnabled('sourcecontrol.github'),
            'guideContext' => ['needsRepo' => $board->needsRepository()],
        ]);
    }
}
