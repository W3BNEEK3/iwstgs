<?php
namespace Src\SourceControl\Domain;

/**
 * Everything Areyna asks of the place learners' code lives (design doc
 * v2-01 §3.3). GitHub today; the interface keeps GitHub specifics out of the
 * rest of the platform and lets tests run against an in-memory fake.
 *
 * Repository calls act through the Areyna App installation that grants access
 * to that repository; Areyna never holds a learner's personal token.
 */
interface RepositoryHost
{
    /** Exchanges the code from "Connect GitHub" for the learner's identity. The user token is discarded. */
    public function identifyUser(string $oauthCode): HostUser;

    /** The App installation on this GitHub account, if the learner has installed it. */
    public function findInstallationForAccount(int $accountId): ?int;

    /** @return HostRepository[] repositories the installation can access */
    public function installationRepositories(int $installationId): array;

    public function repository(int $installationId, string $fullName): ?HostRepository;

    /** @return HostCommit[] newest first */
    public function recentCommits(int $installationId, string $fullName, string $branch, int $limit): array;

    public function compare(int $installationId, string $fullName, string $baseSha, string $headSha): Comparison;

    /** The latest run of the Areyna workflow for a commit, if any. */
    public function workflowRun(int $installationId, string $fullName, string $sha, string $workflowFile): ?WorkflowRun;

    /** The decoded areyna-report.json uploaded by a finished run, or null if there isn't one. */
    public function workflowReport(int $installationId, string $fullName, int $runId): ?array;

    /** SHA-256 of a file's contents at a commit, or null if the file doesn't exist there. */
    public function fileSha256(int $installationId, string $fullName, string $path, string $ref): ?string;

    /** Where the learner installs the Areyna App (choosing which repositories it may see). */
    public function installUrl(): string;

    /** Where "Connect GitHub" sends the learner. */
    public function authorizeUrl(string $state): string;
}
