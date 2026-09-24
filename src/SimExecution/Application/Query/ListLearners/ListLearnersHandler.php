<?php
namespace Src\SimExecution\Application\Query\ListLearners;

use Src\SimExecution\Domain\Enrollment\Learner;
use Src\SimExecution\Domain\Enrollment\LearnerRepository;

final class ListLearnersHandler
{
    public function __construct(private readonly LearnerRepository $learners) {}

    /** @return LearnerSummary[] */
    public function handle(ListLearnersQuery $query): array
    {
        return array_map(
            fn (Learner $l) => new LearnerSummary(
                id:                $l->id(),
                fullname:          $l->fullname(),
                entryCategory:     $l->entryCategory()->value,
                yearsExperience:   $l->yearsExperience(),
            ),
            $this->learners->all(),
        );
    }
}
