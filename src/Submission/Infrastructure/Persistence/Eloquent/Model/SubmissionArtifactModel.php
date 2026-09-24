<?php

namespace Src\Submission\Infrastructure\Persistence\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Submission\Domain\Submission\ArtifactType;

class SubmissionArtifactModel extends Model
{
    use HasUuids;

    protected $table = 'submission_artifacts';

    // uploaded_at is the only timestamp column — not created_at/updated_at.
    public $timestamps = false;

    protected $fillable = [
        'id', 'submission_id', 'filename', 'artifact_type', 'storage_path', 'uploaded_at',
    ];

    protected $casts = [
        'artifact_type' => ArtifactType::class,
        'uploaded_at'   => 'datetime',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SubmissionPackageModel::class, 'submission_id');
    }
}
