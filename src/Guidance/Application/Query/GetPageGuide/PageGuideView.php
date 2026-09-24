<?php
namespace Src\Guidance\Application\Query\GetPageGuide;

final class PageGuideView
{
    /**
     * @param array<int, array{key: string, title: string, cards: array}> $steps every step relevant to this page right now
     * @param string[] $autoShowKeys steps to open automatically (enabled and not yet dismissed)
     */
    public function __construct(
        public readonly bool $isEnabled,
        public readonly array $steps,
        public readonly array $autoShowKeys,
    ) {}
}
