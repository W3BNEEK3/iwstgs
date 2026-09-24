<?php
namespace Src\EvalEngine\Presentation\Http\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\EvalEngine\Application\Query\GetEvaluationResult\GetEvaluationResultQuery;
use Src\Shared\Application\Bus\QueryBus;

class EvaluationResultController
{
    public function __construct(private readonly QueryBus $queryBus) {}

    public function show(string $submission): View
    {
        $result = $this->queryBus->ask(new GetEvaluationResultQuery(Auth::id(), $submission));
        abort_if($result === null, 404);

        return view('learn.evaluation-result', ['result' => $result]);
    }
}
