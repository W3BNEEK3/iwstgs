<?php
namespace Src\Guidance\Application\Query\GetNextGuideMessage;

final class GetNextGuideMessageQuery
{
    public function __construct(
        public readonly string $userId,
        public readonly ?string $page,
        public readonly \DateTimeInterface $userCreatedAt,
    ) {}
}
