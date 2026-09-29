<?php
namespace Src\Guidance\Infrastructure\Persistence;

use Src\Guidance\Domain\Message\GuideKind;
use Src\Guidance\Domain\Message\GuideMessage;
use Src\Guidance\Domain\Message\GuideMessageRepository;
use Src\Guidance\Domain\Message\NewGuideMessage;

final class EloquentGuideMessageRepository implements GuideMessageRepository
{
    private const CAPPED_KINDS = ['nudge', 'tip', 'resource'];

    public function queue(NewGuideMessage $message, string $status): string
    {
        return GuideMessageModel::create([
            'user_id'         => $message->userId,
            'kind'            => $message->kind()->value,
            'trigger_key'     => $message->triggerKey,
            'context_ref'     => $message->contextRef,
            'facts'           => $message->facts,
            'title'           => $message->title,
            'body'            => $message->body,
            'cta_label'       => $message->ctaLabel,
            'cta_url'         => $message->ctaUrl,
            'tip_id'          => $message->tipId,
            'resource_id'     => $message->resourceId,
            'announcement_id' => $message->announcementId,
            'status'          => $status,
            'generated_by'    => 'authored',
        ])->id;
    }

    public function existsSince(string $userId, string $triggerKey, ?string $contextRef, \DateTimeInterface $since): bool
    {
        return GuideMessageModel::where('user_id', $userId)
            ->where('trigger_key', $triggerKey)
            ->when($contextRef !== null, fn ($q) => $q->where('context_ref', $contextRef))
            ->where('created_at', '>=', $since)
            ->exists();
    }

    public function deliverable(string $userId): array
    {
        return GuideMessageModel::where('user_id', $userId)
            ->whereIn('status', ['pending', 'ready'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (GuideMessageModel $m) => $this->toDomain($m))
            ->all();
    }

    public function countShownSince(string $userId, \DateTimeInterface $since, bool $cappedKindsOnly): int
    {
        return GuideMessageModel::where('user_id', $userId)
            ->where('shown_at', '>=', $since)
            ->when($cappedKindsOnly, fn ($q) => $q->whereIn('kind', self::CAPPED_KINDS))
            ->count();
    }

    public function find(string $id): ?GuideMessage
    {
        $model = GuideMessageModel::find($id);

        return $model === null ? null : $this->toDomain($model);
    }

    public function rewrite(string $id, string $title, string $body, string $generatedBy): void
    {
        GuideMessageModel::whereKey($id)->update([
            'title' => $title, 'body' => $body, 'generated_by' => $generatedBy, 'status' => 'ready',
        ]);
    }

    public function markShown(string $id): void
    {
        GuideMessageModel::whereKey($id)->whereIn('status', ['pending', 'ready'])->update(['status' => 'shown', 'shown_at' => now()]);
    }

    public function markDismissed(string $id): void
    {
        GuideMessageModel::whereKey($id)->whereNull('dismissed_at')->update(['status' => 'dismissed', 'dismissed_at' => now()]);
    }

    public function rate(string $id, string $rating): void
    {
        GuideMessageModel::whereKey($id)->update(['rating' => $rating]);
    }

    public function expireOlderThan(\DateTimeInterface $before): void
    {
        GuideMessageModel::whereIn('status', ['pending', 'ready'])
            ->where('kind', '!=', GuideKind::Announcement->value)
            ->where('created_at', '<', $before)
            ->update(['status' => 'expired']);
    }

    public function recentShownOfKind(string $userId, GuideKind $kind, int $limit): array
    {
        return GuideMessageModel::where('user_id', $userId)
            ->where('kind', $kind->value)
            ->whereNotNull('shown_at')
            ->orderByDesc('shown_at')
            ->limit($limit)
            ->get()
            ->map(fn (GuideMessageModel $m) => $this->toDomain($m))
            ->all();
    }

    public function recentForUser(string $userId, int $limit): array
    {
        return GuideMessageModel::where('user_id', $userId)
            ->whereNotNull('shown_at')
            ->orderByDesc('shown_at')
            ->limit($limit)
            ->get()
            ->map(fn (GuideMessageModel $m) => $this->toDomain($m))
            ->all();
    }

    public function usedIds(string $userId, string $column, ?\DateTimeInterface $since = null): array
    {
        if (! in_array($column, ['tip_id', 'resource_id', 'announcement_id'], true)) {
            throw new \InvalidArgumentException("Unknown guide message reference column: {$column}");
        }

        return GuideMessageModel::where('user_id', $userId)
            ->whereNotNull($column)
            ->when($since !== null, fn ($q) => $q->where('created_at', '>=', $since))
            ->pluck($column)
            ->unique()
            ->values()
            ->all();
    }

    public function shownAnnouncements(string $userId): array
    {
        return GuideMessageModel::where('user_id', $userId)
            ->where('kind', GuideKind::Announcement->value)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (GuideMessageModel $m) => ['title' => $m->title, 'body' => $m->body, 'created_at' => (string) $m->created_at])
            ->all();
    }

    public function statsByTrigger(\DateTimeInterface $since): array
    {
        $stats = [];
        GuideMessageModel::whereNotNull('shown_at')
            ->where('shown_at', '>=', $since)
            ->get(['trigger_key', 'rating', 'shown_at', 'dismissed_at'])
            ->each(function (GuideMessageModel $m) use (&$stats) {
                $s = $stats[$m->trigger_key] ?? ['shown' => 0, 'helpful' => 0, 'not_helpful' => 0, 'quick_dismiss' => 0];
                $s['shown']++;
                if ($m->rating === 'helpful') {
                    $s['helpful']++;
                } elseif ($m->rating === 'not_helpful') {
                    $s['not_helpful']++;
                }
                if ($m->dismissed_at !== null && $m->shown_at->diffInSeconds($m->dismissed_at) < 2) {
                    $s['quick_dismiss']++;
                }
                $stats[$m->trigger_key] = $s;
            });

        return $stats;
    }

    public function latest(int $limit, ?string $triggerKey = null): array
    {
        return GuideMessageModel::query()
            ->when($triggerKey !== null, fn ($q) => $q->where('trigger_key', $triggerKey))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (GuideMessageModel $m) => $this->toDomain($m))
            ->all();
    }

    private function toDomain(GuideMessageModel $m): GuideMessage
    {
        return new GuideMessage(
            id:             $m->id,
            userId:         $m->user_id,
            kind:           GuideKind::from($m->kind),
            triggerKey:     $m->trigger_key,
            contextRef:     $m->context_ref,
            facts:          $m->facts ?? [],
            title:          $m->title,
            body:           $m->body,
            ctaLabel:       $m->cta_label,
            ctaUrl:         $m->cta_url,
            tipId:          $m->tip_id,
            resourceId:     $m->resource_id,
            announcementId: $m->announcement_id,
            status:         $m->status,
            generatedBy:    $m->generated_by,
            rating:         $m->rating,
            createdAt:      (string) $m->created_at,
            shownAt:        $m->shown_at?->toDateTimeString(),
            dismissedAt:    $m->dismissed_at?->toDateTimeString(),
        );
    }
}
