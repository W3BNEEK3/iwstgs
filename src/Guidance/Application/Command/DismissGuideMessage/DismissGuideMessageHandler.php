<?php
namespace Src\Guidance\Application\Command\DismissGuideMessage;

use Src\Guidance\Application\Service\GuideBackoff;
use Src\Guidance\Domain\Message\GuideMessageRepository;

final class DismissGuideMessageHandler
{
    public function __construct(
        private readonly GuideMessageRepository $messages,
        private readonly GuideBackoff $backoff,
    ) {}

    public function handle(DismissGuideMessageCommand $command): void
    {
        $message = $this->messages->find($command->messageId);
        if ($message === null || $message->userId !== $command->userId) {
            return;
        }

        $this->messages->markDismissed($message->id);
        $this->backoff->afterFeedback($command->userId, $message->kind);
    }
}
