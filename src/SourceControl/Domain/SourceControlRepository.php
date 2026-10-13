<?php
namespace Src\SourceControl\Domain;

/** Persistence for GitHub connections, linked repositories and webhook deliveries. */
interface SourceControlRepository
{
    public function connectionForUser(string $userId): ?GitHubConnection;

    public function connectionForGitHubUser(int $githubUserId): ?GitHubConnection;

    public function saveConnection(string $userId, int $githubUserId, string $githubLogin): void;

    public function deleteConnection(string $userId): void;

    public function repositoryForSession(string $learnerSessionId): ?LinkedRepository;

    /** @return LinkedRepository[] */
    public function repositoriesByGitHubId(int $githubRepoId): array;

    /** @return LinkedRepository[] */
    public function repositoriesForInstallation(int $installationId): array;

    public function link(string $learnerSessionId, HostRepository $repo, int $installationId, string $templateRepo): void;

    public function setStatus(string $repositoryId, string $status, ?int $installationId = null): void;

    public function markAccepted(string $learnerSessionId, string $sha): void;

    /** Records a webhook delivery; false if it was already received (GitHub retries). */
    public function recordDelivery(string $deliveryId, string $event): bool;
}
