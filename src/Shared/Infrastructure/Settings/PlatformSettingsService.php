<?php

namespace Src\Shared\Infrastructure\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Admin-editable platform settings (platform_settings: key => JSON value).
 * Same caching approach as FeatureFlagService: reads are cached briefly and
 * a write forgets its key, so a change takes effect on the next request.
 * Callers always pass the default, so a missing row never breaks anything.
 */
class PlatformSettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::remember("platform_setting:{$key}", 60, function () use ($key) {
            $row = DB::table('platform_settings')->where('key', $key)->value('value');

            return $row === null ? ['missing' => true] : ['value' => json_decode($row, true)];
        });

        return array_key_exists('value', $value) ? $value['value'] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        DB::table('platform_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value), 'updated_at' => now(), 'created_at' => now()],
        );

        Cache::forget("platform_setting:{$key}");
    }
}
