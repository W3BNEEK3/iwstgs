<?php
namespace Src\Guidance\Application\Query\GetNextGuideMessage;

final class GuideMessageView
{
    public function __construct(
        public readonly string $id,
        public readonly string $kind,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $ctaLabel,
        public readonly ?string $ctaUrl,
        public readonly bool $ctaIsExternal,
        public readonly string $why,
        public readonly bool $canOptOutOfResource,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
