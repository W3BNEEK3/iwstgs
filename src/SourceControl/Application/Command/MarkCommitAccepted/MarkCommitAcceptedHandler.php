<?php
namespace Src\SourceControl\Application\Command\MarkCommitAccepted;

use Src\SourceControl\Domain\SourceControlRepository;

/** A passed milestone's commit becomes the base the next milestone's changes are compared with. */
final class MarkCommitAcceptedHandler
{
    public function __construct(private readonly SourceControlRepository $store) {}

    public function handle(MarkCommitAcceptedCommand $command): void
    {
        $this->store->markAccepted($command->learnerSessionId, $command->sha);
    }
}
