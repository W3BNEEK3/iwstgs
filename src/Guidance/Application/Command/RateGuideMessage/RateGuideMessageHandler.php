<?php
namespace Src\Guidance\Application\Command\RateGuideMessage;

use Src\Guidance\Application\Service\GuideBackoff;
use Src\Guidance\Domain\Message\GuideMessageRepository;

final class RateGuideMessageHandler
{
    public function __construct(
        private readonly GuideMessageRepository $messages,
        private readonly GuideBackoff $backoff,
    ) {}

    public function handle(RateGuideMessageCommand $command): void
    {
        $message = $this->messages->find($command->messageId);
        if ($message === null || $message->userId !== $command->userId) {
            return;
        }

        $this->messages->rate($message->id, $command->helpful ? 'helpful' : 'not_helpful');
        $this->backoff->afterFeedback($command->userId, $message->kind);
    }
}
