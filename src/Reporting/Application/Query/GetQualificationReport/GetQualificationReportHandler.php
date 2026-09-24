<?php
namespace Src\Reporting\Application\Query\GetQualificationReport;

use Src\Competency\Application\Query\ListRoleDefinitions\ListRoleDefinitionsQuery;
use Src\LearnerProfile\Application\Query\GetDimensionScores\GetDimensionScoresQuery;
use Src\Reporting\Application\Service\QualificationAssessor;
use Src\Reporting\Application\Service\RoleQualificationResult;
use Src\Shared\Application\Bus\QueryBus;
use Src\SimExecution\Application\Query\GetLearnerIdForUser\GetLearnerIdForUserQuery;

final class GetQualificationReportHandler
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly QualificationAssessor $assessor,
    ) {}

    /** @return RoleQualificationResult[]|null */
    public function handle(GetQualificationReportQuery $query): ?array
    {
        if ($query->userId === null) {
            return null;
        }

        $learnerId = $this->queryBus->ask(new GetLearnerIdForUserQuery($query->userId));
        if ($learnerId === null) {
            return null;
        }

        $roles = $this->queryBus->ask(new ListRoleDefinitionsQuery());
        $scores = $this->queryBus->ask(new GetDimensionScoresQuery($learnerId));

        return $this->assessor->assess($roles, $scores);
    }
}
