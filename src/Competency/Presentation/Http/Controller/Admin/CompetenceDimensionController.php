<?php
namespace Src\Competency\Presentation\Http\Controller\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Src\Competency\Application\Command\CreateCompetenceDimension\CreateCompetenceDimensionCommand;
use Src\Competency\Application\Query\ListCompetenceDimensions\ListCompetenceDimensionsQuery;
use Src\Competency\Presentation\Http\Request\StoreCompetenceDimensionRequest;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Query\ListAllRubricCriteria\ListAllRubricCriteriaQuery;

class CompetenceDimensionController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function index(): View
    {
        $dimensions = $this->queryBus->ask(new ListCompetenceDimensionsQuery());
        $criteria = $this->queryBus->ask(new ListAllRubricCriteriaQuery());

        $criteriaByDimension = [];
        foreach ($criteria as $criterion) {
            $criteriaByDimension[$criterion['parentDimensionId']][] = $criterion;
        }

        return view('admin.competence-dimensions.index', [
            'dimensions' => $dimensions,
            'criteriaByDimension' => $criteriaByDimension,
        ]);
    }

    public function create(): View
    {
        return view('admin.competence-dimensions.create');
    }

    public function store(StoreCompetenceDimensionRequest $request): RedirectResponse
    {
        $indicators = array_values(array_filter(array_map(
            'trim',
            explode("\n", str_replace("\r", '', $request->validated('observable_indicators'))),
        )));

        $this->commandBus->dispatch(new CreateCompetenceDimensionCommand(
            name: $request->validated('name'),
            shortLabel: $request->validated('short_label'),
            coreQuestion: $request->validated('core_question'),
            observableIndicators: $indicators,
        ));

        return redirect()->route('admin.competence-dimensions.index')->with('success', 'Dimension created.');
    }
}
