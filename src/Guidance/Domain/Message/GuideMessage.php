<?php
namespace Src\Guidance\Domain\Message;

final class GuideMessage
{
    /** @param array<string, mixed> $facts */
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly GuideKind $kind,
        public readonly string $triggerKey,
        public readonly ?string $contextRef,
        public readonly array $facts,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $ctaLabel,
        public readonly ?string $ctaUrl,
        public readonly ?string $tipId,
        public readonly ?string $resourceId,
        public readonly ?string $announcementId,
        public readonly string $status,
        public readonly string $generatedBy,
        public readonly ?string $rating,
        public readonly string $createdAt,
        public readonly ?string $shownAt = null,
        public readonly ?string $dismissedAt = null,
    ) {}

    /** Pending messages still carry only their authored fallback and may be rewritten by the AI writer. */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** Closed within 2 seconds of appearing: a sign the learner didn't want it. */
    public function wasDismissedUnread(): bool
    {
        return $this->shownAt !== null && $this->dismissedAt !== null
            && strtotime($this->dismissedAt) - strtotime($this->shownAt) < 2;
    }
}
