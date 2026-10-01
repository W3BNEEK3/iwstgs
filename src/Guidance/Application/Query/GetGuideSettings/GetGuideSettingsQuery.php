<?php
namespace Src\Guidance\Application\Query\GetGuideSettings;

final class GetGuideSettingsQuery
{
    public function __construct(public readonly string $userId) {}
}
