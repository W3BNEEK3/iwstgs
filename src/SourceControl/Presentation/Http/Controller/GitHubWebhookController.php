<?php
namespace Src\SourceControl\Presentation\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Src\SourceControl\Domain\SourceControlRepository;
use Src\SourceControl\Infrastructure\GitHub\WebhookSignatureVerifier;
use Src\SourceControl\Infrastructure\Job\ProcessGitHubWebhook;

/** POST /github/webhook — one endpoint for every event the Areyna App subscribes to. */
class GitHubWebhookController
{
    public function __construct(
        private readonly WebhookSignatureVerifier $verifier,
        private readonly SourceControlRepository $store,
    ) {}

    public function __invoke(Request $request): Response
    {
        if (! $this->verifier->isValid($request->getContent(), $request->header('X-Hub-Signature-256'))) {
            return response('Invalid signature', 401);
        }

        $event = (string) $request->header('X-GitHub-Event', '');
        $delivery = (string) $request->header('X-GitHub-Delivery', '');
        if ($event === 'ping') {
            return response('pong', 200);
        }
        if ($delivery === '' || ! $this->store->recordDelivery($delivery, $event)) {
            return response('Already received', 200); // GitHub redelivers; act once
        }

        ProcessGitHubWebhook::dispatchAfterResponse($event, $request->json()->all());

        return response('Accepted', 202);
    }
}
