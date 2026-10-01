<?php
namespace Src\Guidance\Domain\Content;

final class FeatureAnnouncement
{
    public function __construct(
        public readonly string $id,
        public readonly string $internalTitle,
        public readonly string $notes,
        public readonly ?string $title,
        public readonly ?string $body,
        public readonly ?string $linkUrl,
        public readonly ?string $featureFlag,
        public readonly string $status,
        public readonly ?string $publishedAt,
        public readonly string $createdAt,
    ) {}

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
