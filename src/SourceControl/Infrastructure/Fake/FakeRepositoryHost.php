<?php
namespace Src\SourceControl\Infrastructure\Fake;

use Src\SourceControl\Domain\Comparison;
use Src\SourceControl\Domain\HostCommit;
use Src\SourceControl\Domain\HostRepository;
use Src\SourceControl\Domain\HostUnavailable;
use Src\SourceControl\Domain\HostUser;
use Src\SourceControl\Domain\RepositoryHost;
use Src\SourceControl\Domain\WorkflowRun;

/**
 * In-memory GitHub for tests (design doc v2-01 §3.3): lets the whole
 * connect → link → push → CI → evaluate flow run without the network.
 * Tests arrange state with the public helpers, e.g.
 *
 *     $host->addUser('code-1', 42, 'ada')->addRepository(42, 'ada/taskly', 'areyna-templates/taskly-express-ejs')
 *          ->push('ada/taskly', 'sha2', 'Show tasks')->finishRun('ada/taskly', 'sha2', $report);
 */
final class FakeRepositoryHost implements RepositoryHost
{
    /** @var array<string, HostUser> oauth code => user */
    public array $users = [];
    /** @var array<int, int> account id => installation id */
    public array $installations = [];
    /** @var array<string, array{repo: HostRepository, installation: int, commits: HostCommit[]}> */
    public array $repos = [];
    /** @var array<string, WorkflowRun> "repo@sha" => run */
    public array $runs = [];
    /** @var array<int, array> run id => report */
    public array $reports = [];
    /** @var array<string, string> "repo@sha:path" => sha256 */
    public array $files = [];
    public bool $down = false;
    private int $nextId = 1000;

    public function addUser(string $code, int $id, string $login): self
    {
        $this->users[$code] = new HostUser($id, $login);

        return $this;
    }

    /** Creates the repo (as if from the template) and installs the App on it. */
    public function addRepository(int $ownerId, string $fullName, ?string $template, string $startSha = 'start000'): self
    {
        $installation = $this->installations[$ownerId] ??= $this->nextId++;
        $this->repos[$fullName] = [
            'repo'         => new HostRepository($this->nextId++, $fullName, $ownerId, 'main', $template, $startSha, "https://github.com/{$fullName}"),
            'installation' => $installation,
            'commits'      => [new HostCommit($startSha, 'Initial commit', now()->toIso8601String(), "https://github.com/{$fullName}/commit/{$startSha}")],
        ];

        return $this;
    }

    public function push(string $fullName, string $sha, string $message): self
    {
        $entry = &$this->repos[$fullName];
        array_unshift($entry['commits'], new HostCommit($sha, $message, now()->toIso8601String(), "https://github.com/{$fullName}/commit/{$sha}"));
        $r = $entry['repo'];
        $entry['repo'] = new HostRepository($r->id, $r->fullName, $r->ownerId, $r->defaultBranch, $r->templateFullName, $sha, $r->htmlUrl);

        return $this;
    }

    public function startRun(string $fullName, string $sha): self
    {
        $this->runs["{$fullName}@{$sha}"] = new WorkflowRun($this->nextId++, 'in_progress', null, "https://github.com/{$fullName}/actions/runs/1");

        return $this;
    }

    public function finishRun(string $fullName, string $sha, ?array $report, string $conclusion = 'success'): self
    {
        $id = $this->runs["{$fullName}@{$sha}"]->id ?? $this->nextId++;
        $this->runs["{$fullName}@{$sha}"] = new WorkflowRun($id, 'completed', $conclusion, "https://github.com/{$fullName}/actions/runs/{$id}");
        if ($report !== null) {
            $this->reports[$id] = $report;
        }

        return $this;
    }

    public function setFile(string $fullName, string $sha, string $path, string $contents): self
    {
        $this->files["{$fullName}@{$sha}:{$path}"] = hash('sha256', $contents);

        return $this;
    }

    public function repoId(string $fullName): int
    {
        return $this->repos[$fullName]['repo']->id;
    }

    // ---- RepositoryHost ---------------------------------------------------------

    public function identifyUser(string $oauthCode): HostUser
    {
        $this->guard();

        return $this->users[$oauthCode] ?? throw new HostUnavailable('Unknown code');
    }

    public function findInstallationForAccount(int $accountId): ?int
    {
        $this->guard();

        return $this->installations[$accountId] ?? null;
    }

    public function installationRepositories(int $installationId): array
    {
        $this->guard();

        return array_values(array_map(
            fn (array $e) => $e['repo'],
            array_filter($this->repos, fn (array $e) => $e['installation'] === $installationId),
        ));
    }

    public function repository(int $installationId, string $fullName): ?HostRepository
    {
        $this->guard();

        return $this->repos[$fullName]['repo'] ?? null;
    }

    public function recentCommits(int $installationId, string $fullName, string $branch, int $limit): array
    {
        $this->guard();

        return array_slice($this->repos[$fullName]['commits'] ?? [], 0, $limit);
    }

    public function compare(int $installationId, string $fullName, string $baseSha, string $headSha): Comparison
    {
        $this->guard();

        return new Comparison([
            ['filename' => 'src/server.js', 'status' => 'modified', 'additions' => 12, 'deletions' => 2,
             'patch' => "@@ -1,3 +1,13 @@\n+app.get('/', (req, res) => res.render('index', { tasks }));"],
            ['filename' => 'package-lock.json', 'status' => 'modified', 'additions' => 400, 'deletions' => 10, 'patch' => '+ lots'],
            ['filename' => '.env', 'status' => 'added', 'additions' => 1, 'deletions' => 0, 'patch' => '+SECRET=hunter2'],
        ], 1);
    }

    public function workflowRun(int $installationId, string $fullName, string $sha, string $workflowFile): ?WorkflowRun
    {
        $this->guard();

        return $this->runs["{$fullName}@{$sha}"] ?? null;
    }

    public function workflowReport(int $installationId, string $fullName, int $runId): ?array
    {
        $this->guard();

        return $this->reports[$runId] ?? null;
    }

    public function fileSha256(int $installationId, string $fullName, string $path, string $ref): ?string
    {
        $this->guard();

        return $this->files["{$fullName}@{$ref}:{$path}"] ?? null;
    }

    public function installUrl(): string
    {
        return 'https://github.com/apps/areyna-test/installations/new';
    }

    public function authorizeUrl(string $state): string
    {
        return 'https://github.com/login/oauth/authorize?client_id=test&state=' . $state;
    }

    private function guard(): void
    {
        if ($this->down) {
            throw new HostUnavailable('GitHub is down (fake)');
        }
    }
}
