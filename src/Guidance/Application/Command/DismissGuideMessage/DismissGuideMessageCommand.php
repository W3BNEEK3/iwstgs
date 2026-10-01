<?php
namespace Src\Guidance\Application\Command\DismissGuideMessage;

final class DismissGuideMessageCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $messageId,
    ) {}
}
