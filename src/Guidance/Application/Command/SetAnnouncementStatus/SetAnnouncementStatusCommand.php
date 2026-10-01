<?php
namespace Src\Guidance\Application\Command\SetAnnouncementStatus;

final class SetAnnouncementStatusCommand
{
    public function __construct(
        public readonly string $announcementId,
        public readonly string $status, // published | archived | draft
    ) {}
}
