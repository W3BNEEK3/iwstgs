<?php
namespace Src\Submission\Domain\Submission;

final class SubmissionArtifactDetail
{
    public function __construct(
        public readonly string $id,
        public readonly string $filename,
        public readonly string $type,
        public readonly string $storagePath,
    ) {}
}
