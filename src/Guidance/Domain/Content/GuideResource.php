<?php
namespace Src\Guidance\Domain\Content;

final class GuideResource
{
    public const KINDS = ['reference', 'practice', 'course', 'tool', 'community'];
    public const LEVELS = ['beginner', 'intermediate', 'advanced'];

    /** @param string[] $dimensions competence dimension ids */
    public function __construct(
        public readonly string $id,
        public readonly string $key,
        public readonly string $name,
        public readonly string $url,
        public readonly string $kind,
        public readonly array $dimensions,
        public readonly string $level,
        public readonly bool $isFree,
        public readonly string $blurb,
        public readonly bool $isActive,
        public readonly ?string $lastCheckedAt,
        public readonly ?bool $lastCheckOk,
    ) {}
}
