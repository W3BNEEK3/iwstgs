<?php
namespace Src\SourceControl\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\SourceControl\Domain\GitHubConnection;
use Src\SourceControl\Domain\HostRepository;
use Src\SourceControl\Domain\LinkedRepository;
use Src\SourceControl\Domain\SourceControlRepository;

final class DbSourceControlRepository implements SourceControlRepository
{
    public function connectionForUser(string $userId): ?GitHubConnection
    {
        $row = DB::table('github_connections')->where('user_id', $userId)->whereNull('revoked_at')->first();

        return $row === null ? null : $this->connection($row);
    }

    public function connectionForGitHubUser(int $githubUserId): ?GitHubConnection
    {
        $row = DB::table('github_connections')->where('github_user_id', $githubUserId)->whereNull('revoked_at')->first();

        return $row === null ? null : $this->connection($row);
    }

    public function saveConnection(string $userId, int $githubUserId, string $githubLogin): void
    {
        DB::table('github_connections')->updateOrInsert(
            ['user_id' => $userId],
            ['github_user_id' => $githubUserId, 'github_login' => $githubLogin, 'connected_at' => now(), 'revoked_at' => null],
        );
    }

    public function deleteConnection(string $userId): void
    {
        DB::table('github_connections')->where('user_id', $userId)->delete();
    }

    public function repositoryForSession(string $learnerSessionId): ?LinkedRepository
    {
        $row = DB::table('learner_repositories')->where('learner_session_id', $learnerSessionId)->first();

        return $row === null ? null : $this->repository($row);
    }

    public function repositoriesByGitHubId(int $githubRepoId): array
    {
        return DB::table('learner_repositories')->where('github_repo_id', $githubRepoId)->get()
            ->map(fn (object $r) => $this->repository($r))->all();
    }

    public function repositoriesForInstallation(int $installationId): array
    {
        return DB::table('learner_repositories')->where('installation_id', $installationId)->get()
            ->map(fn (object $r) => $this->repository($r))->all();
    }

    public function link(string $learnerSessionId, HostRepository $repo, int $installationId, string $templateRepo): void
    {
        $existing = DB::table('learner_repositories')->where('learner_session_id', $learnerSessionId)->first();
        $values = [
            'github_repo_id'  => $repo->id,
            'full_name'       => $repo->fullName,
            'installation_id' => $installationId,
            'default_branch'  => $repo->defaultBranch,
            'template_repo'   => $templateRepo,
            'status'          => LinkedRepository::LINKED,
            'updated_at'      => now(),
        ];

        if ($existing !== null) {
            // Relinking (e.g. after the App was reinstalled) keeps the learner's progress.
            DB::table('learner_repositories')->where('id', $existing->id)->update($values);

            return;
        }

        DB::table('learner_repositories')->insert($values + [
            'id'                 => (string) Str::uuid(),
            'learner_session_id' => $learnerSessionId,
            'start_sha'          => $repo->headSha,
            'created_at'         => now(),
        ]);
    }

    public function setStatus(string $repositoryId, string $status, ?int $installationId = null): void
    {
        DB::table('learner_repositories')->where('id', $repositoryId)->update(array_filter([
            'status'          => $status,
            'installation_id' => $installationId,
            'updated_at'      => now(),
        ], fn ($v) => $v !== null));
    }

    public function markAccepted(string $learnerSessionId, string $sha): void
    {
        DB::table('learner_repositories')->where('learner_session_id', $learnerSessionId)
            ->update(['last_accepted_sha' => $sha, 'updated_at' => now()]);
    }

    public function recordDelivery(string $deliveryId, string $event): bool
    {
        return DB::table('github_webhook_deliveries')->insertOrIgnore([
            'delivery_id' => $deliveryId, 'event' => $event, 'received_at' => now(),
        ]) === 1;
    }

    private function connection(object $row): GitHubConnection
    {
        return new GitHubConnection($row->user_id, (int) $row->github_user_id, $row->github_login, (string) $row->connected_at);
    }

    private function repository(object $r): LinkedRepository
    {
        return new LinkedRepository(
            id:               $r->id,
            learnerSessionId: $r->learner_session_id,
            githubRepoId:     (int) $r->github_repo_id,
            fullName:         $r->full_name,
            installationId:   (int) $r->installation_id,
            defaultBranch:    $r->default_branch,
            templateRepo:     $r->template_repo,
            status:           $r->status,
            startSha:         $r->start_sha,
            lastAcceptedSha:  $r->last_accepted_sha,
        );
    }
}
