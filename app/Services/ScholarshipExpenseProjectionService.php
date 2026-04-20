<?php

namespace App\Services;

use App\Models\ScholarshipRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ScholarshipExpenseProjectionService
{
    public function projectForRecord(ScholarshipRecord|array $record): array
    {
        $config = config('scholarship.expense_projection', []);

        $program = $this->getValue($record, 'program');
        $course = $this->getValue($record, 'course');
        $term = $this->getValue($record, 'term');
        $yearLevel = $this->getValue($record, 'year_level');
        $academicYear = $this->getValue($record, 'academic_year');

        $programMatch = $this->resolveProgramRule($program, $config);
        if (!$programMatch) {
            return $this->unconfiguredProjection($academicYear, 'Program projection rule is not configured.');
        }

        $courseOverride = $this->resolveCourseOverride($course, $config);
        $rule = array_merge($programMatch['rule'], $courseOverride['rule'] ?? []);

        $termSystem = $this->resolveTermSystem($term, $rule, $config);
        if (!$termSystem) {
            return $this->unconfiguredProjection($academicYear, 'Term system could not be determined.');
        }

        $termsPerYear = (int) Arr::get($config, "term_systems.{$termSystem}.terms_per_year", 0);
        if ($termsPerYear < 1) {
            return $this->unconfiguredProjection($academicYear, 'Terms per year is not configured.');
        }

        $currentYearLevel = $this->normalizeYearLevel($yearLevel);
        if (!$currentYearLevel) {
            return $this->unconfiguredProjection($academicYear, 'Year level could not be determined.');
        }

        $configuredTotalYears = (int) ($rule['total_years'] ?? $rule['default_total_years'] ?? 0);
        $totalYears = max($configuredTotalYears, $currentYearLevel);
        if ($totalYears < 1) {
            return $this->unconfiguredProjection($academicYear, 'Total course duration is not configured.');
        }

        $currentTermOrdinal = $this->resolveTermOrdinal($termSystem, $term, $config);
        $remainingTerms = max((($totalYears - $currentYearLevel) * $termsPerYear) + ($termsPerYear - $currentTermOrdinal + 1), 0);
        $completionAcademicYear = $this->calculateCompletionAcademicYear(
            $academicYear,
            max($totalYears - $currentYearLevel, 0),
        );
        $completionYear = $this->resolveCompletionYear($completionAcademicYear);

        $rateKey = Arr::get($rule, 'rate_key');
        $rateConfig = Arr::get($config, "rates.{$rateKey}");
        if (!$rateConfig) {
            return $this->unconfiguredProjection($academicYear, 'Projection rate is not configured.');
        }

        $projectedTotalExpense = match (Arr::get($rateConfig, 'mode')) {
            'annual_cap' => $this->calculateAnnualCapProjection(
                (float) Arr::get($rateConfig, 'annual_amount', 0),
                $termsPerYear,
                $currentTermOrdinal,
                $totalYears,
                $currentYearLevel,
            ),
            'per_term' => (float) Arr::get($rateConfig, 'term_amount', 0) * $remainingTerms,
            default => 0.0,
        };

        $projectedTotalExpense = round($projectedTotalExpense, 2);

        return [
            'projected_total_expense' => $projectedTotalExpense,
            'projected_total_expense_formatted' => number_format($projectedTotalExpense, 2),
            'projected_term_count' => $remainingTerms,
            'projected_remaining_terms' => $remainingTerms,
            'projected_term_system' => $termSystem,
            'projected_total_years' => $totalYears,
            'projected_completion_year' => $completionYear,
            'projected_completion_academic_year' => $completionAcademicYear,
            'projected_completion_year_level' => $this->formatYearLevel($totalYears),
            'projected_academic_year' => $academicYear,
            'projection_status' => 'configured',
            'projection_basis' => $courseOverride
                ? 'course_override'
                : ($programMatch['matched_key'] === '*' ? 'program_default' : 'program_rule'),
            'projection_note' => $courseOverride
                ? "Course override matched: {$courseOverride['matched_key']}"
                : ($programMatch['matched_key'] === '*'
                    ? 'Using default four-year semestral rule. Add a course override if this course uses a different duration.'
                    : "Program rule matched: {$programMatch['matched_key']}"),
        ];
    }

    private function calculateAnnualCapProjection(
        float $annualAmount,
        int $termsPerYear,
        int $currentTermOrdinal,
        int $totalYears,
        int $currentYearLevel,
    ): float {
        $currentYearTermsRemaining = max($termsPerYear - $currentTermOrdinal + 1, 0);
        $currentYearProjection = $termsPerYear > 0
            ? ($annualAmount / $termsPerYear) * $currentYearTermsRemaining
            : 0.0;

        $futureYears = max($totalYears - $currentYearLevel, 0);

        return $currentYearProjection + ($futureYears * $annualAmount);
    }

    private function resolveProgramRule(mixed $program, array $config): ?array
    {
        $rules = Arr::get($config, 'program_rules', []);
        $keys = [
            $this->normalizeKey(data_get($program, 'shortname')),
            $this->normalizeKey(data_get($program, 'name')),
            $this->normalizeKey($program),
        ];

        foreach (array_filter($keys) as $key) {
            if (isset($rules[$key])) {
                return [
                    'matched_key' => $key,
                    'rule' => $rules[$key],
                ];
            }
        }

        if (isset($rules['*'])) {
            return [
                'matched_key' => '*',
                'rule' => $rules['*'],
            ];
        }

        return null;
    }

    private function resolveCourseOverride(mixed $course, array $config): ?array
    {
        $overrides = Arr::get($config, 'course_overrides', []);
        $keys = [
            $this->normalizeKey(data_get($course, 'shortname')),
            $this->normalizeKey(data_get($course, 'name')),
            $this->normalizeKey($course),
        ];

        foreach (array_filter($keys) as $key) {
            if (isset($overrides[$key])) {
                return [
                    'matched_key' => $key,
                    'rule' => $overrides[$key],
                ];
            }
        }

        return null;
    }

    private function resolveTermSystem(mixed $term, array $rule, array $config): ?string
    {
        $normalizedTerm = $this->normalizeKey($term);
        $termSystems = Arr::get($config, 'term_systems', []);

        if ($normalizedTerm) {
            foreach ($termSystems as $system => $definition) {
                $termOrdinals = Arr::get($definition, 'term_ordinals', []);
                if (isset($termOrdinals[$normalizedTerm])) {
                    $allowedSystems = Arr::get($rule, 'allowed_term_systems', []);
                    if (empty($allowedSystems) || in_array($system, $allowedSystems, true)) {
                        return $system;
                    }
                }
            }
        }

        return Arr::get($rule, 'term_system');
    }

    private function resolveTermOrdinal(string $termSystem, mixed $term, array $config): int
    {
        $normalizedTerm = $this->normalizeKey($term);
        $mappedOrdinal = (int) Arr::get($config, "term_systems.{$termSystem}.term_ordinals.{$normalizedTerm}", 0);

        return $mappedOrdinal > 0 ? $mappedOrdinal : 1;
    }

    private function normalizeYearLevel(mixed $yearLevel): ?int
    {
        $normalized = $this->normalizeKey($yearLevel);
        if (!$normalized) {
            return null;
        }

        if (preg_match('/(\d+)/', $normalized, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function formatYearLevel(int $yearLevel): string
    {
        $suffix = match (true) {
            $yearLevel % 100 >= 11 && $yearLevel % 100 <= 13 => 'TH',
            $yearLevel % 10 === 1 => 'ST',
            $yearLevel % 10 === 2 => 'ND',
            $yearLevel % 10 === 3 => 'RD',
            default => 'TH',
        };

        return "{$yearLevel}{$suffix} YEAR";
    }

    private function calculateCompletionAcademicYear(mixed $academicYear, int $yearsToAdvance): ?string
    {
        if ($academicYear === null || $academicYear === '') {
            return null;
        }

        $normalizedAcademicYear = trim((string) $academicYear);
        if ($normalizedAcademicYear === '') {
            return null;
        }

        if (preg_match('/(\d{4})\s*-\s*(\d{4})/', $normalizedAcademicYear, $matches)) {
            $startYear = ((int) $matches[1]) + $yearsToAdvance;
            $endYear = ((int) $matches[2]) + $yearsToAdvance;

            return "{$startYear}-{$endYear}";
        }

        if (preg_match('/\b(\d{4})\b/', $normalizedAcademicYear, $matches)) {
            return (string) (((int) $matches[1]) + $yearsToAdvance);
        }

        return null;
    }

    private function resolveCompletionYear(?string $completionAcademicYear): ?int
    {
        if (!$completionAcademicYear) {
            return null;
        }

        if (preg_match('/(\d{4})\s*-\s*(\d{4})/', $completionAcademicYear, $matches)) {
            return (int) $matches[2];
        }

        if (preg_match('/\b(\d{4})\b/', $completionAcademicYear, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function unconfiguredProjection(?string $academicYear, string $reason): array
    {
        return [
            'projected_total_expense' => null,
            'projected_total_expense_formatted' => null,
            'projected_term_count' => null,
            'projected_remaining_terms' => null,
            'projected_term_system' => null,
            'projected_total_years' => null,
            'projected_completion_year' => null,
            'projected_completion_academic_year' => null,
            'projected_completion_year_level' => null,
            'projected_academic_year' => $academicYear,
            'projection_status' => 'unconfigured',
            'projection_basis' => null,
            'projection_note' => $reason,
        ];
    }

    private function normalizeKey(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return null;
        }

        if (is_object($value) && !method_exists($value, '__toString')) {
            return null;
        }

        return Str::upper(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }

    private function getValue(ScholarshipRecord|array $record, string $key): mixed
    {
        if (is_array($record)) {
            return $record[$key] ?? null;
        }

        return $record->{$key} ?? null;
    }
}
