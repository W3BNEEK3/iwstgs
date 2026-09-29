<?php
namespace Src\Guidance\Application\Command\SaveGuideContent;

/** Admin: create (id null) or update a tip, resource or announcement draft. */
final class SaveGuideContentCommand
{
    /** @param array<string, mixed> $data validated fields for that type */
    public function __construct(
        public readonly string $type, // tip | resource | announcement
        public readonly ?string $id,
        public readonly array $data,
        public readonly ?string $actorUserId = null,
    ) {}
}
