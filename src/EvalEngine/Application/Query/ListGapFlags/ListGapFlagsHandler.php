<?php
namespace Src\EvalEngine\Application\Query\ListGapFlags;

use Src\EvalEngine\Domain\Gap\GapFlagRepository;
use Src\EvalEngine\Domain\Gap\GapFlagSummary;

final class ListGapFlagsHandler
{
    public function __construct(private readonly GapFlagRepository $gapFlags) {}

    /** @return GapFlagSummary[] */
    public function handle(ListGapFlagsQuery $query): array
    {
        return $this->gapFlags->findAllForLearner($query->learnerId);
    }
}
