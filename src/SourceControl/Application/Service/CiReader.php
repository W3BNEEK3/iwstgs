<?php
namespace Src\SourceControl\Application\Service;

use Src\SourceControl\Domain\CiSnapshot;
use Src\SourceControl\Domain\LinkedRepository;
use Src\SourceControl\Domain\RepositoryHost;

/**
 * Reads the Areyna workflow's result for a commit. Results are only trusted
 * when the workflow file is the template's (design doc v2-01 §4.2); a
 * changed workflow is reported, not silently accepted.
 */
final class CiReader
{
    public const WORKFLOW_FILE = 'areyna.yml';
    public const WORKFLOW_PATH = '.github/workflows/areyna.yml';

    public function __construct(private readonly RepositoryHost $host) {}

    public function read(LinkedRepository $repo, string $sha, ?string $expectedWorkflowSha256): CiSnapshot
    {
        $run = $this->host->workflowRun($repo->installationId, $repo->fullName, $sha, self::WORKFLOW_FILE);
        if ($run === null) {
            return new CiSnapshot(CiSnapshot::NO_RUN);
        }
        if (! $run->isFinished()) {
            return new CiSnapshot(CiSnapshot::PENDING, $run->htmlUrl);
        }

        $intact = $expectedWorkflowSha256 === null
            ? null
            : $this->host->fileSha256($repo->installationId, $repo->fullName, self::WORKFLOW_PATH, $sha) === $expectedWorkflowSha256;

        return new CiSnapshot(
            state:          CiSnapshot::FINISHED,
            runUrl:         $run->htmlUrl,
            conclusion:     $run->conclusion,
            report:         $intact === false ? null : $this->host->workflowReport($repo->installationId, $repo->fullName, $run->id),
            workflowIntact: $intact,
        );
    }
}
