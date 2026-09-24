<?php
namespace Src\Reporting\Application\Query\GetLearnerProfileDashboard;

final class GetLearnerProfileDashboardQuery
{
    public function __construct(public readonly ?string $userId) {}
}
