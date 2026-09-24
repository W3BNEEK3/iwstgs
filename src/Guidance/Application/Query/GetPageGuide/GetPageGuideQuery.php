<?php
namespace Src\Guidance\Application\Query\GetPageGuide;

final class GetPageGuideQuery
{
    /** @param array<string, bool> $context milestone flags reported by the page */
    public function __construct(
        public readonly string $userId,
        public readonly string $page,
        public readonly array $context = [],
    ) {}
}
