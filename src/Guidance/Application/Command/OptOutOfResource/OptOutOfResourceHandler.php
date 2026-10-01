<?php
namespace Src\Guidance\Application\Command\OptOutOfResource;

use Src\Guidance\Domain\Content\GuideContentRepository;
use Src\Guidance\Domain\Message\GuideMessageRepository;

/** "Already use it" / "Not for me" on a resource card: that resource is never suggested to them again. */
final class OptOutOfResourceHandler
{
    public function __construct(
        private readonly GuideMessageRepository $messages,
        private readonly GuideContentRepository $content,
    ) {}

    public function handle(OptOutOfResourceCommand $command): void
    {
        $message = $this->messages->find($command->messageId);
        if ($message === null || $message->userId !== $command->userId || $message->resourceId === null) {
            return;
        }

        $this->content->optOutOfResource($command->userId, $message->resourceId, $command->reason);
        $this->messages->markDismissed($message->id);
    }
}
