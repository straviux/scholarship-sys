<?php

namespace App\Services;

use App\Models\ScholarshipProfile;

class JpmTaggingService
{
    public const MEMBER_BOOLEAN_FIELDS = [
        'is_jpm_member',
        'is_father_jpm',
        'is_mother_jpm',
        'is_guardian_jpm',
    ];

    public const STATUS_BOOLEAN_FIELDS = [
        'is_jpm_member',
        'is_father_jpm',
        'is_mother_jpm',
        'is_guardian_jpm',
        'is_not_jpm',
        'is_unrenewed_jpm',
    ];

    public const UPDATABLE_FIELDS = [
        'is_jpm_member',
        'is_father_jpm',
        'is_mother_jpm',
        'is_guardian_jpm',
        'is_not_jpm',
        'is_unrenewed_jpm',
        'jpm_remarks',
    ];

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function normalizeAttributes(array $attributes, bool $autoTagNotJpmWhenNoMemberTags = false): array
    {
        $normalized = array_intersect_key($attributes, array_flip(self::UPDATABLE_FIELDS));

        foreach (self::STATUS_BOOLEAN_FIELDS as $field) {
            if (array_key_exists($field, $normalized)) {
                $normalized[$field] = (bool) $normalized[$field];
            }
        }

        if (array_key_exists('jpm_remarks', $normalized)) {
            $remarks = trim((string) ($normalized['jpm_remarks'] ?? ''));
            $normalized['jpm_remarks'] = $remarks !== '' ? $remarks : null;
        }

        $hasAnyMemberTag = false;
        foreach (self::MEMBER_BOOLEAN_FIELDS as $field) {
            if (($normalized[$field] ?? false) === true) {
                $hasAnyMemberTag = true;
                break;
            }
        }

        if (($normalized['is_not_jpm'] ?? false) === true) {
            foreach (self::MEMBER_BOOLEAN_FIELDS as $field) {
                $normalized[$field] = false;
            }

            $normalized['is_unrenewed_jpm'] = false;
        } elseif ($hasAnyMemberTag || (($normalized['is_unrenewed_jpm'] ?? false) === true)) {
            $normalized['is_not_jpm'] = false;
        } elseif ($autoTagNotJpmWhenNoMemberTags) {
            foreach (self::MEMBER_BOOLEAN_FIELDS as $field) {
                $normalized[$field] = (bool) ($normalized[$field] ?? false);
            }

            $normalized['is_not_jpm'] = true;
            $normalized['is_unrenewed_jpm'] = false;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{updated: bool, changes: array<string, array{old: mixed, new: mixed}>, attributes: array<string, mixed>}
     */
    public function updateProfile(ScholarshipProfile $profile, array $attributes, bool $autoTagNotJpmWhenNoMemberTags = false): array
    {
        $normalized = $this->normalizeAttributes($attributes, $autoTagNotJpmWhenNoMemberTags);
        $changes = [];

        foreach ($normalized as $field => $newValue) {
            $oldValue = $profile->{$field};

            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];

                $profile->{$field} = $newValue;
            }
        }

        if ($changes !== []) {
            $profile->save();

            ActivityLogService::logJpmTagging(
                profileId: $profile->profile_id,
                changes: $changes,
                remarks: strip_tags((string) ($normalized['jpm_remarks'] ?? '')) ?: null,
            );
        }

        return [
            'updated' => $changes !== [],
            'changes' => $changes,
            'attributes' => $normalized,
        ];
    }
}