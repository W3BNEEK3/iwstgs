<?php
namespace Src\Guidance\Application\Service;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Src\AIMediation\Domain\Provider\AiTextGeneratorClient;
use Src\Guidance\Domain\Message\GuideMessage;
use Src\Guidance\Domain\Message\GuideMessageRepository;

/**
 * Turns a pending message's facts into Tiroco's words with the configured AI
 * provider, once, just before it is first shown. Any failure (no API key,
 * provider down, output rejected by the validator) keeps the authored
 * fallback — the learner always gets a sensible message — and a failing
 * provider is left alone for a few minutes rather than retried per message.
 */
final class GuideMessageWriter
{
    private const FAILURE_BACKOFF_SECONDS = 600;
    private const FAILURE_KEY = 'guidance.writer.failing';

    public function __construct(
        private readonly AiTextGeneratorClient $generator,
        private readonly GuidePromptBuilder $prompts,
        private readonly GuideMessageValidator $validator,
        private readonly LearnerSnapshotBuilder $snapshots,
        private readonly GuideMessageRepository $messages,
    ) {}

    public function write(GuideMessage $message): GuideMessage
    {
        if (! $message->isPending()) {
            return $message;
        }

        // Two tabs asking at once must not generate (and pay for) the same message twice.
        $lock = Cache::lock("guidance.writer.{$message->id}", 30);
        if (! $lock->get()) {
            return $message;
        }

        try {
            $written = $this->generate($message);
            $this->messages->rewrite(
                $message->id,
                $written['title'] ?? $message->title,
                $written['body'] ?? $message->body,
                $written === null ? 'fallback' : 'ai',
            );
        } finally {
            $lock->release();
        }

        return $this->messages->find($message->id) ?? $message;
    }

    /** @return array{title: string, body: string}|null */
    private function generate(GuideMessage $message): ?array
    {
        if (! $this->prompts->hasPurpose($message->triggerKey) || Cache::has(self::FAILURE_KEY)) {
            return null;
        }

        try {
            $raw = $this->generator->generate($this->prompts->systemPrompt(), [[
                'type' => 'text',
                'text' => $this->prompts->userContent(
                    $message,
                    $this->snapshots->build($message->userId),
                    $this->messages->recentForUser($message->userId, 5),
                ),
            ]]);
        } catch (\Throwable $e) {
            Log::warning("Guide message generation failed ({$message->triggerKey}): {$e->getMessage()}");
            Cache::put(self::FAILURE_KEY, true, self::FAILURE_BACKOFF_SECONDS);

            return null;
        }

        $written = $this->validator->parse($raw);
        if ($written === null) {
            Log::info("Guide message for {$message->triggerKey} kept its fallback text: the written version was skipped or rejected.");
        }

        return $written;
    }
}
