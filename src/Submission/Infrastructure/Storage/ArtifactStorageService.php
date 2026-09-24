<?php
namespace Src\Submission\Infrastructure\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores Layer 2 artifact uploads on the private 'local' disk under
 * submissions/{sessionId}/{taskId}/ — never public, since these can be a
 * learner's own work product. Keyed by session+task rather than the
 * submission package's own id, since files are uploaded before that id
 * exists (SubmitTaskHandler generates it). The physical filename is a
 * random unique name from store() (collision-proof); the human-readable
 * original name is recorded separately in submission_artifacts.filename.
 */
final class ArtifactStorageService
{
    /** @return array{filename: string, storagePath: string} */
    public function store(string $sessionId, string $taskId, UploadedFile $file): array
    {
        $storagePath = $file->store("submissions/{$sessionId}/{$taskId}", 'local');

        return [
            'filename'    => $file->getClientOriginalName(),
            'storagePath' => $storagePath,
        ];
    }
}
