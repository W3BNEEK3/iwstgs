<?php
namespace Src\SimExecution\Domain\Story;

/** A scripted event as the learner saw it: who said what, and when. */
final class StoryMessage
{
    public function __construct(
        public readonly string $eventType,
        public readonly string $sender,
        public readonly ?string $senderRole,
        public readonly string $text,
        public readonly string $firedAt,
    ) {}
}
