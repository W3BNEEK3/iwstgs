<?php
namespace Src\AIMediation\Application\Command\InjectQueuedSuggestions;

use Src\AIMediation\Application\Service\SuggestionTaskInjector;
use Src\Shared\Infrastructure\Feature\FeatureFlagService;

final class InjectQueuedSuggestionsHandler
{
    public function __construct(
        private readonly SuggestionTaskInjector $injector,
        private readonly FeatureFlagService $flags,
    ) {}

    public function handle(InjectQueuedSuggestionsCommand $command): void
    {
        if (! $this->flags->isEnabled('adaptive.suggestion_tasks')) {
            return;
        }

        $this->injector->injectQueuedSuggestions(
            $command->learnerId,
            $command->learnerSessionId,
            $command->targetSprintId,
        );
    }
}
