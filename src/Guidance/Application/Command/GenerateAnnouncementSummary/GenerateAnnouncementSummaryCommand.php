<?php
namespace Src\Guidance\Application\Command\GenerateAnnouncementSummary;

final class GenerateAnnouncementSummaryCommand
{
    public function __construct(public readonly string $announcementId) {}
}
