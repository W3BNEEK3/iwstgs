<?php
namespace Src\SourceControl\Infrastructure\GitHub;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Src\SourceControl\Domain\Comparison;
use Src\SourceControl\Domain\HostCommit;
use Src\SourceControl\Domain\HostRepository;
use Src\SourceControl\Domain\HostUnavailable;
use Src\SourceControl\Domain\HostUser;
use Src\SourceControl\Domain\RepositoryHost;
use Src\SourceControl\Domain\WorkflowRun;

/** RepositoryHost over the GitHub REST API, acting as the Areyna GitHub App. */
final class GitHubRepositoryHost implements RepositoryHost
{
    private const API = 'https://api.github.com';
    private const REPORT_ARTIFACT = 'areyna-report';
    private const REPORT_FILE = 'areyna-report.json';
    private const MAX_REPORT_BYTES = 2_000_000;

    public function __construct(
        private readonly GitHubAppAuth $auth,
        private readonly array $config,
    ) {}

    public function identifyUser(string $oauthCode): HostUser
    {
        $token = Http::asForm()->acceptJson()->timeout(15)->post('https://github.com/login/oauth/access_token', [
            'client_id'     => $this->config['client_id'] ?? '',
            'client_secret' => $this->config['client_secret'] ?? '',
            'code'          => $oauthCode,
        ])->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new HostUnavailable('GitHub did not confirm the sign-in. Please try connecting again.');
        }

        // The user token is used for this one request only and never stored.
        $user = $this->ok(Http::withToken($token)->withHeaders($this->headers())->timeout(15)->get(self::API . '/user'));

        return new HostUser((int) $user->json('id'), (string) $user->json('login'));
    }

    public function findInstallationForAccount(int $accountId): ?int
    {
        for ($page = 1; $page <= 10; $page++) {
            $installations = $this->ok($this->asApp()->get(self::API . '/app/installations', ['per_page' => 100, 'page' => $page]))->json();
            foreach ($installations as $installation) {
                if ((int) ($installation['account']['id'] ?? 0) === $accountId) {
                    return (int) $installation['id'];
                }
            }
            if (count($installations) < 100) {
                break;
            }
        }

        return null;
    }

    public function installationRepositories(int $installationId): array
    {
        $listed = $this->ok($this->asInstallation($installationId)->get(self::API . '/installation/repositories', ['per_page' => 100]))
            ->json('repositories') ?? [];

        return array_values(array_filter(array_map(
            fn (array $r) => $this->repository($installationId, $r['full_name']),
            $listed,
        )));
    }

    public function repository(int $installationId, string $fullName): ?HostRepository
    {
        $response = $this->asInstallation($installationId)->get(self::API . "/repos/{$fullName}");
        if ($response->status() === 404) {
            return null;
        }
        $repo = $this->ok($response)->json();
        $head = $this->ok($this->asInstallation($installationId)->get(self::API . "/repos/{$fullName}/commits/{$repo['default_branch']}"))->json('sha');

        return new HostRepository(
            id:               (int) $repo['id'],
            fullName:         $repo['full_name'],
            ownerId:          (int) $repo['owner']['id'],
            defaultBranch:    $repo['default_branch'],
            templateFullName: $repo['template_repository']['full_name'] ?? null,
            headSha:          (string) $head,
            htmlUrl:          $repo['html_url'],
        );
    }

    public function recentCommits(int $installationId, string $fullName, string $branch, int $limit): array
    {
        $commits = $this->ok($this->asInstallation($installationId)->get(self::API . "/repos/{$fullName}/commits", [
            'sha' => $branch, 'per_page' => min(100, $limit),
        ]))->json();

        return array_map(fn (array $c) => new HostCommit(
            sha:         $c['sha'],
            message:     $c['commit']['message'] ?? '',
            committedAt: $c['commit']['committer']['date'] ?? '',
            htmlUrl:     $c['html_url'] ?? '',
        ), $commits);
    }

    public function compare(int $installationId, string $fullName, string $baseSha, string $headSha): Comparison
    {
        $data = $this->ok($this->asInstallation($installationId)->get(self::API . "/repos/{$fullName}/compare/{$baseSha}...{$headSha}"))->json();

        return new Comparison(
            files: array_map(fn (array $f) => [
                'filename'  => $f['filename'],
                'status'    => $f['status'],
                'additions' => (int) ($f['additions'] ?? 0),
                'deletions' => (int) ($f['deletions'] ?? 0),
                'patch'     => $f['patch'] ?? null,
            ], $data['files'] ?? []),
            commitCount: (int) ($data['total_commits'] ?? 0),
        );
    }

    public function workflowRun(int $installationId, string $fullName, string $sha, string $workflowFile): ?WorkflowRun
    {
        $response = $this->asInstallation($installationId)->get(self::API . "/repos/{$fullName}/actions/workflows/{$workflowFile}/runs", [
            'head_sha' => $sha, 'per_page' => 1,
        ]);
        if ($response->status() === 404) {
            return null; // the workflow file doesn't exist in this repository
        }
        $run = $this->ok($response)->json('workflow_runs.0');

        return $run === null ? null : new WorkflowRun((int) $run['id'], $run['status'], $run['conclusion'] ?? null, $run['html_url']);
    }

    public function workflowReport(int $installationId, string $fullName, int $runId): ?array
    {
        $artifacts = $this->ok($this->asInstallation($installationId)->get(self::API . "/repos/{$fullName}/actions/runs/{$runId}/artifacts"))
            ->json('artifacts') ?? [];
        $artifact = collect($artifacts)->first(fn (array $a) => $a['name'] === self::REPORT_ARTIFACT && empty($a['expired']));
        if ($artifact === null) {
            return null;
        }

        // GitHub answers with a redirect to short-lived storage; the client drops our token on that hop.
        $zipBytes = $this->ok($this->asInstallation($installationId)->get($artifact['archive_download_url']))->body();

        return $this->readReportFromZip($zipBytes);
    }

    public function fileSha256(int $installationId, string $fullName, string $path, string $ref): ?string
    {
        $response = $this->asInstallation($installationId)->get(self::API . "/repos/{$fullName}/contents/{$path}", ['ref' => $ref]);
        if ($response->status() === 404) {
            return null;
        }
        $content = base64_decode((string) $this->ok($response)->json('content'), false);

        return $content === false ? null : hash('sha256', $content);
    }

    public function installUrl(): string
    {
        return 'https://github.com/apps/' . ($this->config['app_slug'] ?? 'areyna') . '/installations/new';
    }

    public function authorizeUrl(string $state): string
    {
        return 'https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id'    => $this->config['client_id'] ?? '',
            'redirect_uri' => route('github.callback'),
            'state'        => $state,
        ]);
    }

    private function readReportFromZip(string $bytes): ?array
    {
        $path = tempnam(sys_get_temp_dir(), 'areyna-report');
        try {
            file_put_contents($path, $bytes);
            $zip = new \ZipArchive();
            if ($zip->open($path) !== true) {
                return null;
            }
            $stat = $zip->statName(self::REPORT_FILE);
            $json = $stat !== false && $stat['size'] <= self::MAX_REPORT_BYTES ? $zip->getFromName(self::REPORT_FILE) : false;
            $zip->close();

            $report = $json === false ? null : json_decode($json, true);

            return is_array($report) ? $report : null;
        } finally {
            @unlink($path);
        }
    }

    private function asApp(): PendingRequest
    {
        return Http::withToken($this->auth->appJwt())->withHeaders($this->headers())->timeout(15);
    }

    private function asInstallation(int $installationId): PendingRequest
    {
        return Http::withToken($this->auth->installationToken($installationId))->withHeaders($this->headers())->timeout(20);
    }

    private function headers(): array
    {
        return ['Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => '2022-11-28', 'User-Agent' => 'Areyna'];
    }

    private function ok(Response $response): Response
    {
        if (! $response->successful()) {
            throw new HostUnavailable("GitHub returned {$response->status()} for {$response->effectiveUri()?->getPath()}.");
        }

        return $response;
    }
}
