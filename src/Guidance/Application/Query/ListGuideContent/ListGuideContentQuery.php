<?php
namespace Src\Guidance\Application\Query\ListGuideContent;

/** Admin: all tips, resources or announcements (`type`: tips | resources | announcements). */
final class ListGuideContentQuery
{
    public function __construct(public readonly string $type) {}
}
