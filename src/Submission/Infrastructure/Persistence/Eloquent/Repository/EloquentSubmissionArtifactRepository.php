<?php

namespace Src\Submission\Infrastructure\Persistence\Eloquent\Repository;

use Src\Submission\Domain\Submission\ArtifactType;
use Src\Submission\Domain\Submission\SubmissionArtifactDetail;
use Src\Submission\Domain\Submission\SubmissionArtifactRepository;
use Src\Submission\Infrastructure\Persistence\Eloquent\Model\SubmissionArtifactModel;

final class EloquentSubmissionArtifactRepository implements SubmissionArtifactRepository
{
    public function create(string $id, string $submissionId, string $filename, ArtifactType $type, string $storagePath): void
    {
        SubmissionArtifactModel::create([
            'id'            => $id,
            'submission_id' => $submissionId,
            'filename'      => $filename,
            'artifact_type' => $type,
            'storage_path'  => $storagePath,
        ]);
    }

    public function findAllForSubmission(string $submissionId): array
    {
        return SubmissionArtifactModel::where('submission_id', $submissionId)
            ->get()
            ->map(fn (SubmissionArtifactModel $m) => new SubmissionArtifactDetail(
                id:          $m->id,
                filename:    $m->filename,
                type:        $m->artifact_type->value,
                storagePath: $m->storage_path,
            ))
            ->all();
    }
}
