<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemOption extends Model
{
    use HasFactory;

    public const GRANT_PROVISION_PROGRAMS = [
        'EFA',
        'MED',
        'TECH-VOC',
        'BAR EXAMINEES',
    ];

    protected $fillable = [
        'category',
        'value',
        'label',
        'program',
        'amount',
        'color',
        'sort_order',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'amount' => 'decimal:2',
    ];

    /**
     * Scope a query to only include active options.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('value');
    }

    /**
     * Get options for a specific category.
     */
    public static function getByCategory($category, $activeOnly = true)
    {
        $query = static::category($category)->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get all categories with their options.
     */
    public static function getAllGrouped($activeOnly = true)
    {
        $query = static::ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()->groupBy('category');
    }

    /**
     * Get available categories.
     */
    public static function getCategories()
    {
        return [
            'attachment_type' => 'Attachment Types',
            'grant_provision' => 'Grant Provisions',
            'obr_status' => 'OBR Status',
            'disbursement_type' => 'Disbursement Types',
            'priority_level' => 'Priority Levels',
            'term' => 'Terms',
            'year_level' => 'Year Levels',
            'academic_year' => 'Academic Years',
            'form_category' => 'Form Categories',
            'religion' => 'Religion',
        ];
    }

    public static function getGrantProvisionPrograms(): array
    {
        return self::GRANT_PROVISION_PROGRAMS;
    }

    public static function getGrantProvisionLabelMap(bool $activeOnly = true): array
    {
        static $cache = [];

        $cacheKey = $activeOnly ? 'active' : 'all';

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $query = static::query()->category('grant_provision')->ordered();

        if ($activeOnly) {
            $query->active();
        }

        $cache[$cacheKey] = $query->get()
            ->mapWithKeys(fn(self $option) => [$option->value => self::formatGrantProvisionOptionLabel($option)])
            ->all();

        return $cache[$cacheKey];
    }

    public static function formatGrantProvisionLabel(?string $value, string $fallback = ''): string
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        $labelMap = self::getGrantProvisionLabelMap(false);

        return $labelMap[$value] ?? ucwords(str_replace('_', ' ', $value));
    }

    private static function formatGrantProvisionOptionLabel(self $option): string
    {
        $baseLabel = $option->label ?: ucwords(str_replace('_', ' ', $option->value));

        if ($option->amount === null) {
            return $baseLabel;
        }

        return sprintf('%s (PHP %s)', $baseLabel, number_format((float) $option->amount, 2));
    }
}
