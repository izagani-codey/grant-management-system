<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public static function all(): Collection
    {
        try {
            $cached = Cache::get('v2_all_settings');
            if ($cached && !($cached instanceof \Illuminate\Support\Collection)) {
                Cache::forget('v2_all_settings');
                $cached = null;
            }
            if ($cached) {
                $first = $cached->first();
                if ($first && !($first instanceof \App\Models\SystemSetting)) {
                    Cache::forget('v2_all_settings');
                    $cached = null;
                }
            }
        } catch (\Throwable $e) {
            Cache::forget('v2_all_settings');
            $cached = null;
        }

        if (!$cached) {
            $cached = SystemSetting::all()->keyBy('key');
            Cache::forever('v2_all_settings', $cached);
        }

        if ($cached->isEmpty()) {
            return static::defaults();
        }

        return $cached;
    }

    private static function defaults(): Collection
    {
        return collect([
            'app_name'            => config('app.name', 'Grant Management System'),
            'institution_name'    => env('SYSTEM_ORGANIZATION', 'Your Organization'),
            'institution_tagline' => '',
            'primary_color'       => '#1d4ed8',
            'accent_color'        => '#7c3aed',
            'footer_text'         => '© ' . date('Y') . ' Grant Management System',
            'support_email'       => '',
            'app_logo'            => null,
            'app_favicon'         => null,
            'mail_from_name'      => config('app.name', 'Grant Management System'),
        ])->map(function ($value, $key) {
            return (object)['key' => $key, 'value' => $value];
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $cached = Cache::get("v2_setting_{$key}");
            if ($cached && !($cached instanceof \App\Models\SystemSetting)) {
                Cache::forget("v2_setting_{$key}");
                $cached = null;
            }
        } catch (\Throwable $e) {
            Cache::forget("v2_setting_{$key}");
            $cached = null;
        }

        if (!$cached) {
            $cached = SystemSetting::find($key);
            if ($cached) {
                Cache::forever("v2_setting_{$key}", $cached);
            }
        }

        return $cached?->value ?? $default;
    }

    public static function set(string $key, mixed $value, array $attributes = []): SystemSetting
    {
        $setting = SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            array_merge(['value' => $value], $attributes)
        );

        // FIX 1: forget both versioned keys on write
        Cache::forget("v2_setting_{$key}");
        Cache::forget('v2_all_settings');

        return $setting;
    }
}
