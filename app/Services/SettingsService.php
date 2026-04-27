<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingsService
{
    private const CACHE_KEY = 'system_settings';

    public static function all(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $settings = self::defaultSettings();

            if (Schema::hasTable('system_settings')) {
                SystemSetting::query()
                    ->get()
                    ->each(function (SystemSetting $setting) use (&$settings) {
                        $settings[$setting->key] = $setting;
                    });
            }

            return collect($settings);
        });
    }

    public static function get(string $key, mixed $fallback = null): mixed
    {
        return self::all()->get($key)?->value ?? $fallback;
    }

    public static function set(string $key, mixed $value, array $attributes = []): SystemSetting
    {
        $setting = SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            array_merge(['value' => $value], $attributes)
        );

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }

    private static function defaultSettings(): array
    {
        $defaults = [
            'app_name' => [
                'value' => config('app.name', 'Grant Request System'),
                'type' => 'text',
                'group' => 'branding',
                'label' => 'Application Name',
            ],
            'institution_name' => [
                'value' => config('system.branding.organization', 'Your Organization'),
                'type' => 'text',
                'group' => 'branding',
                'label' => 'Institution Name',
            ],
            'institution_tagline' => [
                'value' => '',
                'type' => 'text',
                'group' => 'branding',
                'label' => 'Institution Tagline',
            ],
            'app_logo' => [
                'value' => '',
                'type' => 'image',
                'group' => 'branding',
                'label' => 'Application Logo',
            ],
            'app_favicon' => [
                'value' => '',
                'type' => 'image',
                'group' => 'branding',
                'label' => 'Application Favicon',
            ],
            'primary_color' => [
                'value' => '#003087',
                'type' => 'color',
                'group' => 'branding',
                'label' => 'Primary Color',
            ],
            'accent_color' => [
                'value' => '#C8971E',
                'type' => 'color',
                'group' => 'branding',
                'label' => 'Accent Color',
            ],
            'footer_text' => [
                'value' => '',
                'type' => 'text',
                'group' => 'branding',
                'label' => 'Footer Text',
            ],
            'support_email' => [
                'value' => '',
                'type' => 'email',
                'group' => 'contact',
                'label' => 'Support Email',
            ],
            'allowed_email_domains' => [
                'value' => '',
                'type' => 'text',
                'group' => 'security',
                'label' => 'Allowed Email Domains',
            ],
            'mail_from_name' => [
                'value' => config('app.name', 'Grant Request System'),
                'type' => 'text',
                'group' => 'mail',
                'label' => 'Mail From Name',
            ],
        ];

        return collect($defaults)
            ->map(fn (array $setting, string $key) => new SystemSetting(array_merge(['key' => $key], $setting)))
            ->all();
    }
}
