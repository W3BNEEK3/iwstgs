<?php
namespace Src\Guidance\Application\Command\OptOutOfResource;

final class OptOutOfResourceCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $messageId,
        public readonly string $reason, // already_use | not_for_me
    ) {}
}
