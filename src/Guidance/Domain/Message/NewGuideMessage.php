<?php
namespace Src\Guidance\Domain\Message;

/**
 * A message a trigger wants to queue. `title`/`body` are the authored
 * fallback, always valid on their own; when the AI writer is available it
 * rewrites them from `facts` at delivery time.
 */
final class NewGuideMessage
{
    /** @param array<string, mixed> $facts */
    public function __construct(
        public readonly string $userId,
        public readonly string $triggerKey,
        public readonly string $title,
        public readonly string $body,
        public readonly array $facts = [],
        public readonly ?string $contextRef = null,
        public readonly ?string $ctaLabel = null,
        public readonly ?string $ctaUrl = null,
        public readonly ?string $tipId = null,
        public readonly ?string $resourceId = null,
        public readonly ?string $announcementId = null,
        public readonly bool $needsWriting = true,
    ) {}

    public function kind(): GuideKind
    {
        return TriggerCatalog::find($this->triggerKey)['kind'];
    }
}
