<?php
namespace Src\Simulation\Application\Query\GetBacklogItemTemplate;

use Src\Simulation\Domain\Backlog\BacklogItemTemplateRepository;
use Src\Simulation\Domain\Backlog\BacklogItemTemplateSummary;

final class GetBacklogItemTemplateHandler
{
    public function __construct(private readonly BacklogItemTemplateRepository $templates) {}

    public function handle(GetBacklogItemTemplateQuery $query): ?BacklogItemTemplateSummary
    {
        return $this->templates->findById($query->id);
    }
}
