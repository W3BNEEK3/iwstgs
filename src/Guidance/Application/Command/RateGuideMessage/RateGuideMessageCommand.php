<?php
namespace Src\Guidance\Application\Command\RateGuideMessage;

final class RateGuideMessageCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $messageId,
        public readonly bool $helpful,
    ) {}
}
