<?php
namespace Src\Guidance\Domain\Content;

final class GuideTip
{
    /** @param string[]|null $pages guide page keys the tip suits; null = anywhere */
    public function __construct(
        public readonly string $id,
        public readonly string $key,
        public readonly string $area,
        public readonly string $text,
        public readonly ?array $pages,
        public readonly bool $isActive,
        public readonly int $sortOrder,
    ) {}

    public function suitsPage(?string $page): bool
    {
        return $this->pages === null || $this->pages === [] || ($page !== null && in_array($page, $this->pages, true));
    }
}
