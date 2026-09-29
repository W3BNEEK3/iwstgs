<?php
namespace Src\Guidance\Application\Query\GetGuideContentItem;

final class GetGuideContentItemQuery
{
    public function __construct(
        public readonly string $type, // tip | resource | announcement
        public readonly string $id,
    ) {}
}
