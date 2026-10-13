<?php
namespace Src\SourceControl\Application\Service;

use Src\Shared\Application\Bus\CommandBus;
use Src\SourceControl\Domain\LinkedRepository;
use Src\SourceControl\Domain\SourceControlRepository;
use Src\Submission\Application\Command\ResumePendingSubmissions\ResumePendingSubmissionsCommand;

/**
 * Acts on verified GitHub webhook deliveries (design doc v2-01 §3.4):
 *   installation / installation_repositories → link or unlink repositories
 *   workflow_run (completed)                 → resume submissions waiting on that commit's tests
 * Pull request events arrive with the Work Experience track.
 */
final class WebhookHandler
{
    public function __construct(
        private readonly SourceControlRepository $store,
        private readonly RepositoryLinker $linker,
        private readonly CommandBus $commandBus,
    ) {}

    public function handle(string $event, array $payload): void
    {
        match ($event) {
            'installation', 'installation_repositories' => $this->installation($event, $payload),
            'workflow_run'                              => $this->workflowRun($payload),
            default                                     => null,
        };
    }

    private function installation(string $event, array $payload): void
    {
        $action = $payload['action'] ?? '';
        $installationId = (int) ($payload['installation']['id'] ?? 0);

        if ($event === 'installation' && in_array($action, ['deleted', 'suspend'], true)) {
            foreach ($this->store->repositoriesForInstallation($installationId) as $repo) {
                $this->store->setStatus($repo->id, LinkedRepository::ACCESS_LOST);
            }

            return;
        }

        if ($event === 'installation_repositories' && $action === 'removed') {
            foreach ($payload['repositories_removed'] ?? [] as $removed) {
                foreach ($this->store->repositoriesByGitHubId((int) $removed['id']) as $repo) {
                    $this->store->setStatus($repo->id, LinkedRepository::ACCESS_LOST);
                }
            }

            return;
        }

        // created, added, unsuspend, new_permissions_accepted: try to link whatever is waiting.
        $connection = $this->store->connectionForGitHubUser((int) ($payload['installation']['account']['id'] ?? 0));
        if ($connection !== null) {
            $this->linker->linkForUser($connection->userId);
        }
    }

    private function workflowRun(array $payload): void
    {
        if (($payload['action'] ?? '') !== 'completed') {
            return;
        }

        $sha = (string) ($payload['workflow_run']['head_sha'] ?? '');
        foreach ($this->store->repositoriesByGitHubId((int) ($payload['repository']['id'] ?? 0)) as $repo) {
            if ($repo->isUsable() && $sha !== '') {
                $this->commandBus->dispatch(new ResumePendingSubmissionsCommand($repo->learnerSessionId, $sha));
            }
        }
    }
}
