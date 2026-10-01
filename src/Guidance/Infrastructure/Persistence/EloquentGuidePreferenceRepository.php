<?php
namespace Src\Guidance\Infrastructure\Persistence;

use Src\Guidance\Domain\GuidePreference;
use Src\Guidance\Domain\GuidePreferenceRepository;
use Src\Guidance\Domain\Message\GuideKind;

final class EloquentGuidePreferenceRepository implements GuidePreferenceRepository
{
    public function forUser(string $userId): GuidePreference
    {
        $model = GuidePreferenceModel::find($userId);

        return $model === null
            ? GuidePreference::default()
            : new GuidePreference(
                $model->is_enabled,
                $model->dismissed_steps ?? [],
                $model->muted_kinds ?? [],
                $model->paused_until ?? [],
                $model->last_active_at?->toIso8601String(),
            );
    }

    public function dismissStep(string $userId, string $stepKey): void
    {
        $model = $this->model($userId);
        $model->dismissed_steps = array_values(array_unique([...($model->dismissed_steps ?? []), $stepKey]));
        $model->save();
    }

    public function setEnabled(string $userId, bool $enabled): void
    {
        $model = $this->model($userId);
        $model->is_enabled = $enabled;
        $model->save();
    }

    public function resetDismissed(string $userId): void
    {
        $model = $this->model($userId);
        $model->dismissed_steps = [];
        $model->is_enabled = true;
        $model->save();
    }

    public function setMutedKinds(string $userId, array $mutedKinds): void
    {
        $model = $this->model($userId);
        $model->muted_kinds = array_values(array_unique($mutedKinds));
        $model->save();
    }

    public function pauseKind(string $userId, GuideKind $kind, \DateTimeInterface $until): void
    {
        $model = $this->model($userId);
        $model->paused_until = [...($model->paused_until ?? []), $kind->value => $until->format(DATE_ATOM)];
        $model->save();
    }

    public function touchActive(string $userId): ?string
    {
        $model = $this->model($userId);
        $previous = $model->last_active_at?->toIso8601String();
        $model->last_active_at = now();
        $model->save();

        return $previous;
    }

    private function model(string $userId): GuidePreferenceModel
    {
        return GuidePreferenceModel::firstOrNew(['user_id' => $userId], ['is_enabled' => true, 'dismissed_steps' => []]);
    }
}
