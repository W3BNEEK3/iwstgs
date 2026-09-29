<?php
namespace Src\Guidance\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Guidance\Domain\Content\FeatureAnnouncement;
use Src\Guidance\Domain\Content\GuideContentRepository;
use Src\Guidance\Domain\Content\GuideResource;
use Src\Guidance\Domain\Content\GuideTip;

final class EloquentGuideContentRepository implements GuideContentRepository
{
    public function tips(bool $activeOnly): array
    {
        return GuideTipModel::query()
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->get()
            ->map(fn (GuideTipModel $m) => $this->tip($m))
            ->all();
    }

    public function findTip(string $id): ?GuideTip
    {
        $m = GuideTipModel::find($id);

        return $m === null ? null : $this->tip($m);
    }

    public function saveTip(?string $id, array $data): string
    {
        $model = $id === null ? new GuideTipModel([
            'key'        => Str::slug(Str::limit($data['text'], 40, '')) . '-' . Str::lower(Str::random(4)),
            'sort_order' => (int) GuideTipModel::max('sort_order') + 1,
        ]) : GuideTipModel::findOrFail($id);

        $model->fill($data)->save();

        return $model->id;
    }

    public function deleteTip(string $id): void
    {
        GuideTipModel::whereKey($id)->delete();
    }

    public function resources(bool $activeOnly): array
    {
        return GuideResourceModel::query()
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get()
            ->map(fn (GuideResourceModel $m) => $this->resource($m))
            ->all();
    }

    public function findResource(string $id): ?GuideResource
    {
        $m = GuideResourceModel::find($id);

        return $m === null ? null : $this->resource($m);
    }

    public function saveResource(?string $id, array $data): string
    {
        $model = $id === null
            ? new GuideResourceModel(['key' => Str::slug($data['name']) . '-' . Str::lower(Str::random(4))])
            : GuideResourceModel::findOrFail($id);

        if ($model->exists && $model->url !== $data['url']) {
            $data += ['last_checked_at' => null, 'last_check_ok' => null];
        }

        $model->fill($data)->save();

        return $model->id;
    }

    public function deleteResource(string $id): void
    {
        GuideResourceModel::whereKey($id)->delete();
    }

    public function recordLinkCheck(string $id, bool $ok): void
    {
        GuideResourceModel::whereKey($id)->update(['last_checked_at' => now(), 'last_check_ok' => $ok]);
    }

    public function optOutOfResource(string $userId, string $resourceId, string $reason): void
    {
        DB::table('guide_resource_opt_outs')->updateOrInsert(
            ['user_id' => $userId, 'resource_id' => $resourceId],
            ['reason' => $reason, 'created_at' => now()],
        );
    }

    public function optedOutResourceIds(string $userId): array
    {
        return DB::table('guide_resource_opt_outs')->where('user_id', $userId)->pluck('resource_id')->all();
    }

    public function announcements(): array
    {
        return FeatureAnnouncementModel::orderByDesc('created_at')->get()
            ->map(fn (FeatureAnnouncementModel $m) => $this->announcement($m))
            ->all();
    }

    public function publishedAnnouncements(): array
    {
        return FeatureAnnouncementModel::where('status', 'published')->orderByDesc('published_at')->get()
            ->map(fn (FeatureAnnouncementModel $m) => $this->announcement($m))
            ->all();
    }

    public function findAnnouncement(string $id): ?FeatureAnnouncement
    {
        $m = FeatureAnnouncementModel::find($id);

        return $m === null ? null : $this->announcement($m);
    }

    public function saveAnnouncement(?string $id, array $data, ?string $createdBy): string
    {
        // A caller may choose the id of a new draft, so it can go straight to its edit page.
        $model = ($id !== null ? FeatureAnnouncementModel::find($id) : null)
            ?? new FeatureAnnouncementModel(array_filter(['id' => $id, 'status' => 'draft', 'created_by' => $createdBy]));

        $model->fill($data)->save();

        return $model->id;
    }

    public function setAnnouncementStatus(string $id, string $status): void
    {
        $model = FeatureAnnouncementModel::findOrFail($id);
        $model->status = $status;
        if ($status === 'published' && $model->published_at === null) {
            $model->published_at = now();
        }
        $model->save();
    }

    private function tip(GuideTipModel $m): GuideTip
    {
        return new GuideTip($m->id, $m->key, $m->area, $m->text, $m->pages, $m->is_active, (int) $m->sort_order);
    }

    private function resource(GuideResourceModel $m): GuideResource
    {
        return new GuideResource(
            id:            $m->id,
            key:           $m->key,
            name:          $m->name,
            url:           $m->url,
            kind:          $m->kind,
            dimensions:    $m->dimensions ?? [],
            level:         $m->level,
            isFree:        $m->is_free,
            blurb:         $m->blurb,
            isActive:      $m->is_active,
            lastCheckedAt: $m->last_checked_at?->toDateTimeString(),
            lastCheckOk:   $m->last_check_ok,
        );
    }

    private function announcement(FeatureAnnouncementModel $m): FeatureAnnouncement
    {
        return new FeatureAnnouncement(
            id:            $m->id,
            internalTitle: $m->internal_title,
            notes:         $m->notes,
            title:         $m->title,
            body:          $m->body,
            linkUrl:       $m->link_url,
            featureFlag:   $m->feature_flag,
            status:        $m->status,
            publishedAt:   $m->published_at?->toDateTimeString(),
            createdAt:     (string) $m->created_at,
        );
    }
}
