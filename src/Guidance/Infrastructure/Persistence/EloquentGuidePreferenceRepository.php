<?php
namespace Src\Guidance\Infrastructure\Persistence;

use Src\Guidance\Domain\GuidePreference;
use Src\Guidance\Domain\GuidePreferenceRepository;

final class EloquentGuidePreferenceRepository implements GuidePreferenceRepository
{
    public function forUser(string $userId): GuidePreference
    {
        $model = GuidePreferenceModel::find($userId);

        return $model === null
            ? GuidePreference::default()
            : new GuidePreference($model->is_enabled, $model->dismissed_steps ?? []);
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

    private function model(string $userId): GuidePreferenceModel
    {
        return GuidePreferenceModel::firstOrNew(['user_id' => $userId], ['is_enabled' => true, 'dismissed_steps' => []]);
    }
}
