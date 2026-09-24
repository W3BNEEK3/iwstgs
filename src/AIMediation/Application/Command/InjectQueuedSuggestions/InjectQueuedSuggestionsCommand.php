<?php
namespace Src\AIMediation\Application\Command\InjectQueuedSuggestions;

final class InjectQueuedSuggestionsCommand
{
    public function __construct(
        public readonly string $learnerId,
        public readonly string $learnerSessionId,
        public readonly ?string $targetSprintId,
    ) {}
}
