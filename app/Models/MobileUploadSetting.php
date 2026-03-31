<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileUploadSetting extends Model
{
    protected $table = 'mobile_upload_settings';

    protected $fillable = ['settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * Default settings structure — used when no DB record exists.
     */
    public static function defaults(): array
    {
        return [
            'general' => [
                'base_url'         => config('app.url', 'http://localhost:8000'),
                'use_lan_ip'       => false,
                'lan_ip_override'  => '',
                'port_override'    => '',
            ],
            'uploads' => [
                'disbursement' => [
                    'max_size_kb'    => 25600,
                    'allowed_types'  => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                ],
                'scholarship_record' => [
                    'max_size_kb'    => 25600,
                    'allowed_types'  => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                ],
                'profile' => [
                    'max_size_kb'    => 10240,
                    'allowed_types'  => ['jpg', 'jpeg', 'png', 'gif'],
                ],
                'requirement' => [
                    'max_size_kb'    => 25600,
                    'allowed_types'  => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                ],
                'fund_transaction' => [
                    'max_size_kb'    => 25600,
                    'allowed_types'  => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                ],
            ],
            'tokens' => [
                'disbursement'       => 43200,  // 30 days in minutes
                'scholarship_record' => 43200,
                'profile'            => 7200,   // 5 days in minutes
                'requirement'        => 43200,
                'fund_transaction'   => 43200,
            ],
            'image' => [
                'jpeg_quality'           => 60,
                'max_width'              => 1920,
                'max_height'             => 1920,
                'auto_rotate'            => true,
                'preserve_original_format' => true,
            ],
        ];
    }

    /**
     * Get the singleton settings row, or return defaults if not saved yet.
     */
    public static function getCurrent(): array
    {
        $record = static::first();
        if (! $record) {
            return static::defaults();
        }

        // Deep-merge with defaults so newly added keys always exist
        return array_replace_recursive(static::defaults(), $record->settings ?? []);
    }

    /**
     * Save (upsert) the singleton row.
     */
    public static function saveSettings(array $settings): static
    {
        $record = static::first();

        if ($record) {
            $record->update(['settings' => $settings]);
        } else {
            $record = static::create(['settings' => $settings]);
        }

        return $record;
    }
}
