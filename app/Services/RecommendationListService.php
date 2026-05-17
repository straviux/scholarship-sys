<?php

namespace App\Services;

use App\Models\RecommendationList;
use App\Models\ScholarshipRecord;
use App\Models\SystemOption;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RecommendationListService
{
    public function __construct(
        private ScholarshipExpenseProjectionService $expenseProjectionService,
    ) {}

    public function update(RecommendationList $recommendationList, array $data): RecommendationList
    {
        return DB::transaction(function () use ($recommendationList, $data) {
            $budgetAllocation = $this->normalizeBudgetAllocation($data['budget_allocation'] ?? null);

            $recommendationList->fill([
                'report_title' => $this->cleanString($data['report_title'] ?? null) ?? 'RECOMMENDATION LIST FOR APPROVAL',
                'paper_size' => $data['paper_size'] ?? $recommendationList->paper_size ?? 'A4',
                'orientation' => $data['orientation'] ?? $recommendationList->orientation ?? 'landscape',
                'budget_allocation_key' => $budgetAllocation['key'] ?? null,
                'budget_program' => $this->cleanString($data['budget_program'] ?? null)
                    ?? ($budgetAllocation['program'] ?? $recommendationList->budget_program),
                'budget_fiscal_year' => $budgetAllocation['fiscal_year'] ?? null,
                'budget_rc_code' => $budgetAllocation['rc_code'] ?? null,
                'budget_rc_name' => $budgetAllocation['rc_name'] ?? null,
                'budget_allocation' => $budgetAllocation,
                'highlight_jpm_members' => (bool) ($data['highlight_jpm_members'] ?? $recommendationList->highlight_jpm_members ?? false),
                'prepared_by' => $this->cleanString($data['prepared_by'] ?? null),
                'prepared_by_position' => $this->cleanString($data['prepared_by_position'] ?? null),
                'prepared_by_office' => $this->cleanString($data['prepared_by_office'] ?? null),
                'approved_by' => $this->cleanString($data['approved_by'] ?? null),
                'approved_by_position' => $this->cleanString($data['approved_by_position'] ?? null),
            ]);

            $recommendationList->save();

            Log::info('recommendation_list_updated', [
                'id' => $recommendationList->id,
                'list_number' => $recommendationList->list_number,
                'record_count' => $recommendationList->record_count,
                'budget_allocation_key' => $recommendationList->budget_allocation_key,
            ]);

            return $recommendationList->fresh()->load('creator');
        });
    }

    public function create(array $data): RecommendationList
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $recordIds = collect($data['record_ids'] ?? [])
                        ->map(fn ($id) => (int) $id)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    $records = $this->getRecommendedInterviewedRecords($recordIds);
                    $this->ensureRecordsAreNotAlreadyIncluded($records, $recordIds);
                    $recordsSnapshot = $records->map(fn (ScholarshipRecord $record) => $this->snapshotRecord($record))
                        ->values()
                        ->all();

                    $budgetAllocation = $this->normalizeBudgetAllocation($data['budget_allocation'] ?? null);
                    $totalProjectedExpense = round((float) collect($recordsSnapshot)->sum(function (array $record) {
                        return (float) ($record['projected_total_expense'] ?? 0);
                    }), 2);

                    $recommendationList = RecommendationList::create([
                        'list_number' => $this->generateListNumber(),
                        'report_title' => $this->cleanString($data['report_title'] ?? null) ?? 'RECOMMENDATION LIST FOR APPROVAL',
                        'recommendation_status' => 'recommended',
                        'paper_size' => $data['paper_size'] ?? 'A4',
                        'orientation' => $data['orientation'] ?? 'landscape',
                        'record_count' => count($recordsSnapshot),
                        'total_projected_expense' => $totalProjectedExpense,
                        'selected_record_ids' => $recordIds,
                        'records_snapshot' => $recordsSnapshot,
                        'budget_allocation_key' => $budgetAllocation['key'] ?? null,
                        'budget_program' => $this->cleanString($data['budget_program'] ?? null)
                            ?? ($budgetAllocation['program'] ?? null),
                        'budget_fiscal_year' => $budgetAllocation['fiscal_year'] ?? null,
                        'budget_rc_code' => $budgetAllocation['rc_code'] ?? null,
                        'budget_rc_name' => $budgetAllocation['rc_name'] ?? null,
                        'budget_allocation' => $budgetAllocation,
                        'highlight_jpm_members' => (bool) ($data['highlight_jpm_members'] ?? false),
                        'prepared_by' => $this->cleanString($data['prepared_by'] ?? null),
                        'prepared_by_position' => $this->cleanString($data['prepared_by_position'] ?? null),
                        'prepared_by_office' => $this->cleanString($data['prepared_by_office'] ?? null),
                        'approved_by' => $this->cleanString($data['approved_by'] ?? null),
                        'approved_by_position' => $this->cleanString($data['approved_by_position'] ?? null),
                    ]);

                    Log::info('recommendation_list_created', [
                        'id' => $recommendationList->id,
                        'list_number' => $recommendationList->list_number,
                        'record_count' => $recommendationList->record_count,
                        'budget_allocation_key' => $recommendationList->budget_allocation_key,
                    ]);

                    return $recommendationList->load('creator');
                });
            } catch (QueryException $exception) {
                if ($attempt === $maxAttempts || ! $this->causedByDuplicateListNumber($exception)) {
                    throw $exception;
                }

                Log::warning('recommendation_list_number_collision', [
                    'attempt' => $attempt,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        throw new \RuntimeException('Unable to generate a unique recommendation list number.');
    }

    private function getRecommendedInterviewedRecords(array $recordIds): Collection
    {
        if ($recordIds === []) {
            throw ValidationException::withMessages([
                'record_ids' => 'Select at least one interviewed applicant.',
            ]);
        }

        $records = ScholarshipRecord::query()
            ->whereIn('id', $recordIds)
            ->where('unified_status', 'interviewed')
            ->where('recommendation', 'recommended')
            ->with([
                'profile' => function ($query) {
                    $query->select(
                        'profile_id',
                        'first_name',
                        'last_name',
                        'middle_name',
                        'contact_no',
                        'is_jpm_member',
                        'is_father_jpm',
                        'is_mother_jpm',
                        'is_guardian_jpm'
                    );
                },
                'program' => function ($query) {
                    $query->select('scholarship_programs.id', 'scholarship_programs.name', 'scholarship_programs.shortname');
                },
                'course' => function ($query) {
                    $query->select('courses.id', 'courses.name', 'courses.shortname');
                },
                'school' => function ($query) {
                    $query->select('schools.id', 'schools.name', 'schools.shortname');
                },
                'interviewer',
            ])
            ->get()
            ->map(function (ScholarshipRecord $record) {
                foreach ($this->expenseProjectionService->projectForRecord($record) as $key => $value) {
                    $record->setAttribute($key, $value);
                }

                $record->setAttribute(
                    'grant_provision_label',
                    SystemOption::formatGrantProvisionLabel($record->grant_provision, 'N/A')
                );

                return $record;
            })
            ->sortBy(function (ScholarshipRecord $record) {
                $lastName = mb_strtolower($record->profile?->last_name ?? '');
                $firstName = mb_strtolower($record->profile?->first_name ?? '');

                return $lastName . '|' . $firstName;
            })
            ->values();

        if ($records->count() !== count($recordIds)) {
            throw ValidationException::withMessages([
                'record_ids' => 'Only interviewed applicants marked as Recommended for Approval can be added to a recommendation list.',
            ]);
        }

        return $records;
    }

    private function ensureRecordsAreNotAlreadyIncluded(Collection $records, array $recordIds): void
    {
        $existingMatches = RecommendationList::query()
            ->select(['id', 'list_number', 'selected_record_ids'])
            ->get()
            ->map(function (RecommendationList $recommendationList) {
                $selectedRecordIds = collect($recommendationList->selected_record_ids ?? [])
                    ->map(fn ($recordId) => (int) $recordId)
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'list_number' => $recommendationList->list_number,
                    'selected_record_ids' => $selectedRecordIds,
                ];
            })
            ->filter(function (array $recommendationList) use ($recordIds) {
                return collect($recommendationList['selected_record_ids'])->intersect($recordIds)->isNotEmpty();
            })
            ->values();

        if ($existingMatches->isEmpty()) {
            return;
        }

        $duplicateRecordIds = $existingMatches
            ->flatMap(fn (array $recommendationList) => $recommendationList['selected_record_ids'])
            ->intersect($recordIds)
            ->map(fn ($recordId) => (int) $recordId)
            ->unique()
            ->values();

        $duplicateApplicants = $records
            ->filter(fn (ScholarshipRecord $record) => $duplicateRecordIds->contains((int) $record->id))
            ->map(function (ScholarshipRecord $record) {
                $lastName = $record->profile?->last_name ?? 'Unknown';
                $firstName = $record->profile?->first_name ?? '';

                return trim($lastName . ', ' . $firstName);
            })
            ->values();

        $existingListNumbers = $existingMatches
            ->pluck('list_number')
            ->filter()
            ->unique()
            ->values();

        $message = 'Some selected applicants are already included in recommendation list(s): '
            . $existingListNumbers->implode(', ') . '.';

        if ($duplicateApplicants->isNotEmpty()) {
            $message .= ' Remove these applicants first: ' . $duplicateApplicants->implode('; ') . '.';
        }

        throw ValidationException::withMessages([
            'record_ids' => $message,
        ]);
    }

    private function snapshotRecord(ScholarshipRecord $record): array
    {
        return [
            'id' => $record->id,
            'profile' => [
                'profile_id' => $record->profile?->profile_id,
                'first_name' => $record->profile?->first_name,
                'last_name' => $record->profile?->last_name,
                'middle_name' => $record->profile?->middle_name,
                'contact_no' => $record->profile?->contact_no,
                'is_jpm_member' => (bool) $record->profile?->is_jpm_member,
                'is_father_jpm' => (bool) $record->profile?->is_father_jpm,
                'is_mother_jpm' => (bool) $record->profile?->is_mother_jpm,
                'is_guardian_jpm' => (bool) $record->profile?->is_guardian_jpm,
            ],
            'program' => [
                'id' => $record->program?->id,
                'name' => $record->program?->name,
                'shortname' => $record->program?->shortname,
            ],
            'course' => [
                'id' => $record->course?->id,
                'name' => $record->course?->name,
                'shortname' => $record->course?->shortname,
            ],
            'school' => [
                'id' => $record->school?->id,
                'name' => $record->school?->name,
                'shortname' => $record->school?->shortname,
            ],
            'interviewer' => [
                'id' => $record->interviewer?->id,
                'name' => $record->interviewer?->name,
            ],
            'year_level' => $record->year_level,
            'term' => $record->term,
            'academic_year' => $record->academic_year,
            'grant_provision' => $record->grant_provision,
            'grant_provision_label' => $record->getAttribute('grant_provision_label'),
            'projected_term_count' => $record->getAttribute('projected_term_count'),
            'projected_total_expense' => $record->getAttribute('projected_total_expense'),
            'projected_completion_year' => $record->getAttribute('projected_completion_year'),
            'projected_completion_academic_year' => $record->getAttribute('projected_completion_academic_year'),
            'interviewed_at' => $record->interviewed_at?->toDateTimeString(),
            'recommendation' => $record->recommendation,
            'endorsed_by' => $record->endorsed_by,
        ];
    }

    private function normalizeBudgetAllocation(?array $budgetAllocation): ?array
    {
        if (! is_array($budgetAllocation) || $budgetAllocation === []) {
            return null;
        }

        return [
            'key' => $this->cleanString($budgetAllocation['key'] ?? null),
            'particular_id' => isset($budgetAllocation['particular_id']) && $budgetAllocation['particular_id'] !== ''
                ? (int) $budgetAllocation['particular_id']
                : null,
            'particular_name' => $this->cleanString($budgetAllocation['particular_name'] ?? null),
            'description' => $this->cleanString($budgetAllocation['description'] ?? null),
            'program_id' => isset($budgetAllocation['program_id']) && $budgetAllocation['program_id'] !== ''
                ? (int) $budgetAllocation['program_id']
                : null,
            'program' => $this->cleanString($budgetAllocation['program'] ?? null),
            'rc_code' => $this->cleanString($budgetAllocation['rc_code'] ?? null),
            'rc_name' => $this->cleanString($budgetAllocation['rc_name'] ?? null),
            'fiscal_year' => isset($budgetAllocation['fiscal_year']) && $budgetAllocation['fiscal_year'] !== ''
                ? (int) $budgetAllocation['fiscal_year']
                : null,
            'total_allotment' => $this->normalizeDecimal($budgetAllocation['total_allotment'] ?? null),
            'disbursed' => $this->normalizeDecimal($budgetAllocation['disbursed'] ?? null),
            'remaining' => $this->normalizeDecimal($budgetAllocation['remaining'] ?? null),
            'date_start' => $this->cleanString($budgetAllocation['date_start'] ?? null),
            'date_end' => $this->cleanString($budgetAllocation['date_end'] ?? null),
            'approved_scholars_to_date' => isset($budgetAllocation['approved_scholars_to_date'])
                ? (int) $budgetAllocation['approved_scholars_to_date']
                : null,
        ];
    }

    private function normalizeDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 2);
    }

    private function cleanString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmedValue = trim($value);

        return $trimmedValue === '' ? null : $trimmedValue;
    }

    private function generateListNumber(): string
    {
        $prefix = 'RFA-' . now()->format('Ymd');

        $latestListNumber = RecommendationList::withTrashed()
            ->where('list_number', 'like', $prefix . '-%')
            ->orderByDesc('list_number')
            ->value('list_number');

        $nextSequence = $latestListNumber
            ? ((int) substr($latestListNumber, -4)) + 1
            : 1;

        return sprintf('%s-%04d', $prefix, $nextSequence);
    }

    private function causedByDuplicateListNumber(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = $exception->errorInfo[2] ?? $exception->getMessage();

        if ($sqlState !== '23000' || $driverCode !== 1062) {
            return false;
        }

        return str_contains($message, 'recommendation_lists_list_number_unique');
    }
}