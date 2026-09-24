<?php
namespace Src\EvalEngine\Domain\FollowUp;

interface FollowUpPromptRepository
{
    /** Picks a template matching the given domain, falling back to any template if none match. */
    public function selectForDomain(?string $domain): ?string;

    public function findTextById(string $id): ?string;
}
