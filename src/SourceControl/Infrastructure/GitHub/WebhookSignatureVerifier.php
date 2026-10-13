<?php
namespace Src\SourceControl\Infrastructure\GitHub;

/** Checks GitHub's X-Hub-Signature-256 against the raw request body (constant-time). */
final class WebhookSignatureVerifier
{
    public function __construct(private readonly ?string $secret) {}

    public function isValid(string $rawBody, ?string $signatureHeader): bool
    {
        if ($this->secret === null || $this->secret === '' || $signatureHeader === null || ! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        return hash_equals('sha256=' . hash_hmac('sha256', $rawBody, $this->secret), $signatureHeader);
    }
}
