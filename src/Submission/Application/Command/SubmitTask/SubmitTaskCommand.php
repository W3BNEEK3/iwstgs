<?php
namespace Src\Submission\Application\Command\SubmitTask;

final class SubmitTaskCommand
{
    /** @param array<int, array{filename: string, storagePath: string, type: string}> $artifacts */
    public function __construct(
        public readonly ?string $userId,
        public readonly string $sessionId,
        public readonly string $taskId,
        public readonly ?string $layer1Text,
        public readonly ?string $layer3Code,
        public readonly array $artifacts,
    ) {}
}
