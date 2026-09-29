<?php
namespace Src\Guidance\Domain\Message;

interface GuideMessageRepository
{
    public function queue(NewGuideMessage $message, string $status): string;

    /** Was a message for this trigger (and context, when given) created for the user after $since? */
    public function existsSince(string $userId, string $triggerKey, ?string $contextRef, \DateTimeInterface $since): bool;

    /** @return GuideMessage[] pending or ready, oldest first */
    public function deliverable(string $userId): array;

    public function countShownSince(string $userId, \DateTimeInterface $since, bool $cappedKindsOnly): int;

    public function find(string $id): ?GuideMessage;

    public function rewrite(string $id, string $title, string $body, string $generatedBy): void;

    public function markShown(string $id): void;

    public function markDismissed(string $id): void;

    public function rate(string $id, string $rating): void;

    /** Pending/ready messages older than $before are dropped: advice that late is stale. */
    public function expireOlderThan(\DateTimeInterface $before): void;

    /** @return GuideMessage[] the user's most recent shown messages of this kind, newest first */
    public function recentShownOfKind(string $userId, GuideKind $kind, int $limit): array;

    /** @return GuideMessage[] recent messages to the user, newest first (fed to the writer so it doesn't repeat itself) */
    public function recentForUser(string $userId, int $limit): array;

    /** @return string[] tip/resource/announcement ids already queued for the user (since $since, when given) */
    public function usedIds(string $userId, string $column, ?\DateTimeInterface $since = null): array;

    /** @return array<int, array{title: string, body: string, created_at: string}> the user's delivered announcements */
    public function shownAnnouncements(string $userId): array;

    /**
     * Admin health view: per trigger, how many were shown, rated helpful /
     * not helpful, and dismissed within 2 seconds of showing.
     *
     * @return array<string, array{shown: int, helpful: int, not_helpful: int, quick_dismiss: int}>
     */
    public function statsByTrigger(\DateTimeInterface $since): array;

    /** @return GuideMessage[] */
    public function latest(int $limit, ?string $triggerKey = null): array;
}
