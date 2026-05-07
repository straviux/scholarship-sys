<?php

namespace App\Services;

use App\Models\ScholarshipProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ScholarshipProfileListingService
{
    public const FILTER_KEYS = [
        'unified_status',
        'name',
        'program',
        'school',
        'course',
        'municipality',
        'barangay',
        'year_level',
        'academic_year',
        'term',
        'grant_provision',
        'review_status',
        'jpm_status',
        'needs_term_review',
        'contract_status',
        'voucher_status',
        'global_search',
        'records',
        'page',
    ];

    public function paginate(Request $request): LengthAwarePaginator
    {
        $legacyAcademicTermReviewService = app(LegacyAcademicTermReviewService::class);

        $query = ScholarshipProfile::with($this->buildRelationships());

        $this->applyFilters($query, $request, $legacyAcademicTermReviewService);

        $perPage = (int) $request->get('records', 10);

        $profiles = $query->orderBy('updated_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $this->transformProfiles($profiles, $legacyAcademicTermReviewService);

        return $profiles;
    }

    public function collectForReport(Request $request): Collection
    {
        $legacyAcademicTermReviewService = app(LegacyAcademicTermReviewService::class);

        $query = ScholarshipProfile::with($this->buildReportRelationships());

        $this->applyFilters($query, $request, $legacyAcademicTermReviewService);

        return $query->orderBy('updated_at', 'desc')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRelationships(): array
    {
        $with = [
            'latestScholarshipRecord' => function ($query) {
                $query->with(['program', 'course', 'school', 'attachments', 'approvalHistory']);
            },
            'academicEnrollments' => function ($query) {
                $query->select(['id', 'profile_id', 'graduation_date'])
                    ->whereNotNull('graduation_date')
                    ->orderByDesc('graduation_date')
                    ->orderByDesc('updated_at');
            },
            'scholarshipGrant' => function ($query) {
                $query->with([
                    'program' => fn($relationQuery) => $relationQuery->select('scholarship_programs.id', 'scholarship_programs.name', 'scholarship_programs.shortname'),
                    'course' => fn($relationQuery) => $relationQuery->select('courses.id', 'courses.name', 'courses.shortname'),
                    'school' => fn($relationQuery) => $relationQuery->select('schools.id', 'schools.name', 'schools.shortname'),
                ])
                    ->select('id', 'profile_id', 'unified_status', 'created_at', 'program_id', 'course_id', 'school_id', 'year_level', 'date_filed', 'date_approved', 'grant_provision', 'academic_year', 'term')
                    ->orderBy('created_at', 'desc');
            },
            'disbursements' => function ($query) {
                $query->with('attachments');
            },
        ];

        if (Schema::hasTable('return_of_service')) {
            $with['returnOfServiceRecords'] = function ($query) {
                $query->select(['id', 'profile_id', 'completion_status', 'service_start_date'])
                    ->where('completion_status', 'ongoing')
                    ->orderByDesc('service_start_date')
                    ->orderByDesc('updated_at');
            };
        }

        return $with;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportRelationships(): array
    {
        return [
            'latestScholarshipRecord' => function ($query) {
                $query->with([
                    'program' => fn($relationQuery) => $relationQuery->select('scholarship_programs.id', 'scholarship_programs.name', 'scholarship_programs.shortname'),
                    'course' => fn($relationQuery) => $relationQuery->select('courses.id', 'courses.name', 'courses.shortname'),
                    'school' => fn($relationQuery) => $relationQuery->select('schools.id', 'schools.name', 'schools.shortname'),
                ])
                    ->select('id', 'profile_id', 'unified_status', 'program_id', 'course_id', 'school_id', 'year_level', 'academic_year', 'term')
                    ->orderBy('created_at', 'desc');
            },
        ];
    }

    private function applyFilters(Builder $query, Request $request, LegacyAcademicTermReviewService $legacyAcademicTermReviewService): void
    {
        if ($request->filled('unified_status')) {
            $query->whereHas('latestScholarshipRecord', function ($relationQuery) use ($request) {
                $statuses = [$request->unified_status];

                if ($request->unified_status === 'active') {
                    $statuses[] = 'approved';
                }

                $relationQuery->whereIn('unified_status', array_unique($statuses));
            });
        }

        if ($request->filled('program')) {
            $query->whereHas('latestScholarshipRecord.program', function ($relationQuery) use ($request) {
                $relationQuery->where('scholarship_programs.shortname', 'like', '%' . $request->program . '%')
                    ->orWhere('scholarship_programs.name', 'like', '%' . $request->program . '%');
            });
        }

        if ($request->filled('school')) {
            $query->whereHas('latestScholarshipRecord.school', function ($relationQuery) use ($request) {
                $relationQuery->where('shortname', 'like', '%' . $request->school . '%')
                    ->orWhere('name', 'like', '%' . $request->school . '%');
            });
        }

        if ($request->filled('course')) {
            $query->whereHas('latestScholarshipRecord.course', function ($relationQuery) use ($request) {
                $relationQuery->where('courses.shortname', 'like', '%' . $request->course . '%')
                    ->orWhere('courses.name', 'like', '%' . $request->course . '%');
            });
        }

        if ($request->filled('year_level')) {
            $query->whereHas('latestScholarshipRecord', function ($relationQuery) use ($request) {
                $relationQuery->where('year_level', 'like', '%' . $request->year_level . '%');
            });
        }

        if ($request->filled('academic_year')) {
            $query->whereHas('latestScholarshipRecord', function ($relationQuery) use ($request) {
                $relationQuery->where('academic_year', $request->academic_year);
            });
        }

        if ($request->filled('term')) {
            $query->whereHas('latestScholarshipRecord', function ($relationQuery) use ($request) {
                $relationQuery->where('term', $request->term);
            });
        }

        if ($request->filled('municipality')) {
            $query->where('municipality', 'like', '%' . $request->municipality . '%');
        }

        if ($request->filled('barangay')) {
            $query->where('barangay', 'like', '%' . $request->barangay . '%');
        }

        if ($request->filled('name')) {
            $query->where(function ($nestedQuery) use ($request) {
                $nestedQuery->where('first_name', 'like', '%' . $request->name . '%')
                    ->orWhere('last_name', 'like', '%' . $request->name . '%')
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $request->name . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%' . $request->name . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name, ' ', middle_name) LIKE ?", ['%' . $request->name . '%']);
            });
        }

        if ($request->filled('grant_provision')) {
            $query->whereHas('latestScholarshipRecord', function ($relationQuery) use ($request) {
                $relationQuery->where('grant_provision', $request->grant_provision);
            });
        }

        if ($request->filled('review_status')) {
            if ($request->review_status === 'needs_review') {
                $this->applyUntaggedFilter($query);
            } elseif ($request->review_status === 'tagged') {
                $this->applyTaggedFilter($query);
            }
        }

        if ($request->filled('jpm_status')) {
            if ($request->jpm_status === 'jpm') {
                $this->applyJpmMemberFilter($query);
            } elseif ($request->jpm_status === 'not_member') {
                $query->where('is_not_jpm', true);
            }
        }

        if ($request->filled('contract_status')) {
            if ($request->contract_status === 'with') {
                $query->whereHas('latestScholarshipRecord.attachments');
            } elseif ($request->contract_status === 'without') {
                $query->whereDoesntHave('latestScholarshipRecord.attachments');
            }
        }

        if ($request->filled('voucher_status')) {
            if ($request->voucher_status === 'with') {
                $query->whereHas('disbursements.attachments');
            } elseif ($request->voucher_status === 'without') {
                $query->whereDoesntHave('disbursements.attachments');
            }
        }

        if ($request->input('needs_term_review') === 'needs_review') {
            $query->whereIn('profile_id', $legacyAcademicTermReviewService->profileIdsNeedingReviewQuery());
        }

        if ($request->filled('global_search')) {
            $searchTerm = $request->global_search;

            $query->where(function ($nestedQuery) use ($searchTerm) {
                $nestedQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('middle_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('extension_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('municipality', 'like', '%' . $searchTerm . '%')
                    ->orWhere('barangay', 'like', '%' . $searchTerm . '%')
                    ->orWhere('contact_no', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('latestScholarshipRecord.school', function ($schoolQuery) use ($searchTerm) {
                        $schoolQuery->where('schools.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('schools.shortname', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('latestScholarshipRecord.course', function ($courseQuery) use ($searchTerm) {
                        $courseQuery->where('courses.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('courses.shortname', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('latestScholarshipRecord.program', function ($programQuery) use ($searchTerm) {
                        $programQuery->where('scholarship_programs.name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('scholarship_programs.shortname', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $searchTerm . '%'])
                    ->orWhereRaw("CONCAT(last_name, ', ', first_name) LIKE ?", ['%' . $searchTerm . '%']);
            });
        }
    }

    private function transformProfiles(LengthAwarePaginator $profiles, LegacyAcademicTermReviewService $legacyAcademicTermReviewService): void
    {
        $profiles->getCollection()->transform(function ($profile) use ($legacyAcademicTermReviewService) {
            $latestRecord = $profile->latestScholarshipRecord;
            $graduatedEnrollment = $profile->academicEnrollments->first();
            $ongoingRosRecord = $profile->relationLoaded('returnOfServiceRecords')
                ? $profile->returnOfServiceRecords->first()
                : null;
            $legacyTermReview = $legacyAcademicTermReviewService->summarizeProfileRecords($profile->scholarshipGrant);

            $profile->latest_scholarship_record = $latestRecord;
            $profile->total_scholarships = $profile->scholarshipGrant->count();
            $profile->is_graduated = (bool) $graduatedEnrollment;
            $profile->graduation_date = $graduatedEnrollment?->graduation_date;
            $profile->has_ongoing_ros = (bool) $ongoingRosRecord;
            $profile->ros_start_date = $ongoingRosRecord?->service_start_date;
            $profile->needs_legacy_term_review = $legacyTermReview['needs_review'];
            $profile->legacy_term_review_group_count = $legacyTermReview['conflicting_group_count'];
            $profile->legacy_term_review_open_term_total = $legacyTermReview['conflicting_open_term_total'];
            $profile->legacy_term_review_highest_open_term_count = $legacyTermReview['highest_open_term_count'];

            $latestId = $latestRecord?->id;
            $previousRecordStatuses = $profile->scholarshipGrant
                ->filter(fn($record) => $record->id !== $latestId)
                ->groupBy('unified_status')
                ->map->count();

            if ($latestRecord?->relationLoaded('approvalHistory')) {
                foreach ($latestRecord->approvalHistory as $historyEntry) {
                    $previousStatus = $historyEntry->previous_status;

                    if (!$previousStatus || $previousStatus === $latestRecord->unified_status) {
                        continue;
                    }

                    $previousRecordStatuses[$previousStatus] = ($previousRecordStatuses[$previousStatus] ?? 0) + 1;
                }
            }

            $profile->previous_record_statuses = $previousRecordStatuses->toArray();

            $contractCount = $latestRecord && $latestRecord->attachments ? $latestRecord->attachments->count() : 0;

            $voucherCount = 0;
            if ($profile->disbursements) {
                foreach ($profile->disbursements as $disbursement) {
                    if ($disbursement->attachments) {
                        $voucherCount += $disbursement->attachments->count();
                    }
                }
            }

            $profile->contract_count = $contractCount;
            $profile->voucher_count = $voucherCount;
            $profile->has_contract = $contractCount > 0;
            $profile->has_voucher = $voucherCount > 0;

            return $profile;
        });
    }

    private function applyTaggedFilter(Builder $query): void
    {
        $query->where(function ($taggingQuery) {
            $this->applyJpmMemberFilter($taggingQuery);
            $taggingQuery->orWhere('is_not_jpm', true)
                ->orWhere(function ($remarksQuery) {
                    $remarksQuery->whereNotNull('jpm_remarks')
                        ->whereRaw("TRIM(jpm_remarks) <> ''");
                });
        });
    }

    private function applyUntaggedFilter(Builder $query): void
    {
        $query->where(function ($untaggedQuery) {
            $untaggedQuery->where(function ($memberQuery) {
                $memberQuery->where(function ($q) {
                    $q->where('is_jpm_member', false)->orWhereNull('is_jpm_member');
                })
                ->where(function ($q) {
                    $q->where('is_father_jpm', false)->orWhereNull('is_father_jpm');
                })
                ->where(function ($q) {
                    $q->where('is_mother_jpm', false)->orWhereNull('is_mother_jpm');
                })
                ->where(function ($q) {
                    $q->where('is_guardian_jpm', false)->orWhereNull('is_guardian_jpm');
                });
            })
            ->where(function ($q) {
                $q->where('is_not_jpm', false)->orWhereNull('is_not_jpm');
            })
            ->where(function ($q) {
                $q->where('is_unrenewed_jpm', false)->orWhereNull('is_unrenewed_jpm');
            })
            ->where(function ($remarksQuery) {
                $remarksQuery->whereNull('jpm_remarks')
                    ->orWhereRaw("TRIM(jpm_remarks) = ''");
            });
        });
    }

    private function applyJpmMemberFilter(Builder $query): void
    {
        $query->where(function ($memberQuery) {
            $memberQuery->where('is_jpm_member', true)
                ->orWhere('is_father_jpm', true)
                ->orWhere('is_mother_jpm', true)
                ->orWhere('is_guardian_jpm', true);
        });
    }
}
