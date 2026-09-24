<?php
namespace Src\SimExecution\Application\Query\GetLatestInjectedCardId;

use Src\SimExecution\Domain\Board\InjectedTaskCardRepository;

final class GetLatestInjectedCardIdHandler
{
    public function __construct(private readonly InjectedTaskCardRepository $cards) {}

    public function handle(GetLatestInjectedCardIdQuery $query): ?string
    {
        return $this->cards->findLatestId($query->learnerSessionId, $query->sourceTaskId);
    }
}
