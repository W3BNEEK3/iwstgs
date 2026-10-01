<?php
namespace Src\Guidance\Application\Query\GetGuideHealth;

final class GetGuideHealthQuery
{
    public function __construct(
        public readonly int $days = 30,
        public readonly ?string $triggerKey = null,
    ) {}
}
