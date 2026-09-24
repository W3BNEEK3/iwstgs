<?php
namespace Src\SimExecution\Application\Query\GetLearnerIdForUser;

final class GetLearnerIdForUserQuery
{
    public function __construct(public readonly string $userId) {}
}
