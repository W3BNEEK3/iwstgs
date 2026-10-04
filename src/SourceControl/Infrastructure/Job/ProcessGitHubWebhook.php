<?php
namespace Src\SourceControl\Infrastructure\Job;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Src\SourceControl\Application\Service\WebhookHandler;

/**
 * Runs after the webhook response has been sent, so GitHub gets its 202 at
 * once even when a delivery leads to an AI evaluation. Dispatched "after
 * response" rather than onto a queue, so no queue worker is required.
 */
final class ProcessGitHubWebhook
{
    use Dispatchable;

    public function __construct(
        public readonly string $event,
        public readonly array $payload,
    ) {}

    public function handle(WebhookHandler $handler): void
    {
        try {
            $handler->handle($this->event, $this->payload);
        } catch (\Throwable $e) {
            Log::error("GitHub webhook {$this->event} failed: {$e->getMessage()}", ['exception' => $e]);
        }
    }
}
