<?php
namespace Src\SimExecution\Application\Query\IsTaskInjectedForSession;

use Src\SimExecution\Domain\Board\InjectedTaskCardRepository;

/**
 * Consequence and suggestion cards can outlive the scenario their task
 * belongs to (a suggestion is injected at the moment a scenario ends), so
 * submission scoping treats a task injected onto this session as in scope.
 */
final class IsTaskInjectedForSessionHandler
{
    public function __construct(private readonly InjectedTaskCardRepository $cards) {}

    public function handle(IsTaskInjectedForSessionQuery $query): bool
    {
        foreach ($this->cards->findAllForSession($query->learnerSessionId) as $card) {
            if ($card->injectedTaskId === $query->taskId) {
                return true;
            }
        }

        return false;
    }
}
