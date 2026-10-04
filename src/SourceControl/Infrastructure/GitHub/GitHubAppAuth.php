<?php
namespace Src\SourceControl\Infrastructure\GitHub;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Src\SourceControl\Domain\HostUnavailable;

/**
 * Authenticates as the Areyna GitHub App (design doc v2-01 §3): a short JWT
 * signed with the App's private key, exchanged for an installation token
 * that is cached for 50 minutes (they last 60). Nothing long-lived is stored.
 */
final class GitHubAppAuth
{
    private const API = 'https://api.github.com';

    public function __construct(private readonly array $config) {}

    public function isConfigured(): bool
    {
        return ! empty($this->config['app_id']) && $this->privateKey() !== null;
    }

    /** A JWT identifying the App itself (valid ~9 minutes, backdated a minute for clock drift). */
    public function appJwt(): string
    {
        $key = $this->privateKey() ?? throw new HostUnavailable('The GitHub App is not configured (GITHUB_APP_ID / GITHUB_APP_PRIVATE_KEY_PATH).');

        $segments = [
            $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64Url(json_encode(['iat' => time() - 60, 'exp' => time() + 540, 'iss' => (string) $this->config['app_id']])),
        ];
        if (! openssl_sign(implode('.', $segments), $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new HostUnavailable('The GitHub App private key could not sign a token.');
        }
        $segments[] = $this->base64Url($signature);

        return implode('.', $segments);
    }

    public function installationToken(int $installationId): string
    {
        return Cache::remember("sourcecontrol.github.installation_token.{$installationId}", 3000, function () use ($installationId) {
            $response = Http::withToken($this->appJwt())
                ->withHeaders(['Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => '2022-11-28'])
                ->timeout(15)
                ->post(self::API . "/app/installations/{$installationId}/access_tokens");

            if (! $response->successful() || ! is_string($response->json('token'))) {
                throw new HostUnavailable("GitHub refused an installation token ({$response->status()}).");
            }

            return $response->json('token');
        });
    }

    public function forgetInstallationToken(int $installationId): void
    {
        Cache::forget("sourcecontrol.github.installation_token.{$installationId}");
    }

    private function privateKey(): ?\OpenSSLAsymmetricKey
    {
        $path = $this->config['private_key_path'] ?? null;
        if (! $path) {
            return null;
        }
        $path = str_starts_with($path, '/') ? $path : base_path($path);
        if (! is_readable($path)) {
            return null;
        }

        return openssl_pkey_get_private((string) file_get_contents($path)) ?: null;
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
