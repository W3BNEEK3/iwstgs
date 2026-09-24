<?php
namespace Src\Simulation\Application\Command\RecordOnboardingBriefing;

use Src\Simulation\Domain\Project\ProjectTemplateId;
use Src\Simulation\Domain\Project\ProjectTemplateRepository;

final class RecordOnboardingBriefingHandler
{
    public function __construct(private readonly ProjectTemplateRepository $projects) {}

    public function handle(RecordOnboardingBriefingCommand $command): void
    {
        $project = $this->projects->findById(ProjectTemplateId::fromString($command->projectId));
        if ($project === null || $project->onboardingBriefing() !== null) {
            return; // already set — never overwrite a generated briefing
        }

        $project->recordOnboardingBriefing($command->text);
        $this->projects->save($project);
    }
}
