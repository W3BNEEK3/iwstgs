<?php
namespace Src\SourceControl\Domain;

final class HostRepository
{
    public function __construct(
        public readonly int $id,
        public readonly string $fullName,
        public readonly int $ownerId,
        public readonly string $defaultBranch,
        public readonly ?string $templateFullName, // the template it was generated from
        public readonly string $headSha,           // default-branch head
        public readonly string $htmlUrl,
    ) {}
}
