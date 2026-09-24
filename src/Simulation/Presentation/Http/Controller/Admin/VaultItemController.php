<?php

namespace Src\Simulation\Presentation\Http\Controller\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Command\AddVaultItem\AddVaultItemCommand;
use Src\Simulation\Application\Command\RemoveVaultItem\RemoveVaultItemCommand;
use Src\Simulation\Application\Query\GetProject\GetProjectQuery;
use Src\Simulation\Application\Query\ListVaultItemsByProject\ListVaultItemsByProjectQuery;
use Src\Simulation\Presentation\Http\Request\StoreVaultItemRequest;

class VaultItemController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus
    ) {}

    public function index(string $projectId): View
    {
        $project = $this->queryBus->ask(new GetProjectQuery($projectId));
        $items = $this->queryBus->ask(new ListVaultItemsByProjectQuery($projectId));

        return view('admin.vault.index', compact('project', 'items', 'projectId'));
    }

    public function store(StoreVaultItemRequest $request, string $projectId): RedirectResponse
    {
        $id = Str::uuid()->toString();
        $this->commandBus->dispatch(new AddVaultItemCommand(
            $id,
            $projectId,
            $request->validated('document_type'),
            $request->validated('title'),
            $request->validated('content'),
            $request->validated('rank_gate'),
            $request->validated('phase_gate'),
            (bool) $request->validated('is_reference_doc'),
            (int) $request->validated('display_order')
        ));

        return redirect()->route('admin.projects.vault.index', $projectId)
            ->with('success', 'Vault item added.');
    }

    public function destroy(string $projectId, string $itemId): RedirectResponse
    {
        $this->commandBus->dispatch(new RemoveVaultItemCommand($itemId));

        return redirect()->route('admin.projects.vault.index', $projectId)
            ->with('success', 'Vault item removed.');
    }
}
