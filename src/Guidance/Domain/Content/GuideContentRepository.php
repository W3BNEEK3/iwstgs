<?php
namespace Src\Guidance\Domain\Content;

/**
 * The admin-curated material Tiroco draws on: tips, outside resources and
 * feature announcements. One repository because the three are always
 * managed together from the admin Guide section and are all small tables.
 */
interface GuideContentRepository
{
    /** @return GuideTip[] ordered by sort_order */
    public function tips(bool $activeOnly): array;

    public function findTip(string $id): ?GuideTip;

    /** @param array{area: string, text: string, pages: ?array, is_active: bool} $data */
    public function saveTip(?string $id, array $data): string;

    public function deleteTip(string $id): void;

    /** @return GuideResource[] ordered by name */
    public function resources(bool $activeOnly): array;

    public function findResource(string $id): ?GuideResource;

    /** @param array{name: string, url: string, kind: string, dimensions: string[], level: string, is_free: bool, blurb: string, is_active: bool} $data */
    public function saveResource(?string $id, array $data): string;

    public function deleteResource(string $id): void;

    public function recordLinkCheck(string $id, bool $ok): void;

    public function optOutOfResource(string $userId, string $resourceId, string $reason): void;

    /** @return string[] resource ids the user opted out of */
    public function optedOutResourceIds(string $userId): array;

    /** @return FeatureAnnouncement[] newest first */
    public function announcements(): array;

    /** @return FeatureAnnouncement[] published, newest first */
    public function publishedAnnouncements(): array;

    public function findAnnouncement(string $id): ?FeatureAnnouncement;

    /**
     * Creates the draft when no announcement has this id (a null id gets a new one).
     *
     * @param array{internal_title: string, notes: string, title: ?string, body: ?string, link_url: ?string, feature_flag: ?string} $data
     */
    public function saveAnnouncement(?string $id, array $data, ?string $createdBy): string;

    public function setAnnouncementStatus(string $id, string $status): void;
}
