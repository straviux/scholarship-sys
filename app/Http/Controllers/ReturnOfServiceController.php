<?php

namespace App\Http\Controllers;

use App\Models\ReturnOfService;
use App\Models\ReturnOfServiceBatch;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipProfile;
use App\Models\Course;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ReturnOfServiceController extends Controller
{
    /**
     * Display a listing of ROS batches with their scholars.
     */
    public function index()
    {
        if (!Gate::allows('return-of-service.view')) {
            abort(403, 'User does not have the right permissions');
        }

        // Get all ROS batches with their records
        $batches = ReturnOfServiceBatch::with('rosRecords.profile', 'course', 'createdBy')
            ->ordered()
            ->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'batch_name' => $batch->batch_name,
                    'description' => $batch->description,
                    'course_id' => $batch->course_id,
                    'course_name' => $batch->course?->name ?? 'N/A',
                    'exam_date_from' => $batch->exam_date_from?->format('Y-m-d'),
                    'exam_date_to' => $batch->exam_date_to?->format('Y-m-d'),
                    'result_date' => $batch->result_date?->format('Y-m-d'),
                    'total_scholars' => $batch->rosRecords->count(),
                    'created_by' => $batch->createdBy?->name ?? 'System',
                    'created_at' => $batch->created_at?->format('Y-m-d H:i'),
                    'scholars' => $batch->rosRecords->map(function ($record) {
                        $profile = $record->profile;
                        $scholarName = '';
                        if ($profile) {
                            $scholarName = $profile->last_name . ', ' . $profile->first_name;
                            if ($profile->middle_name) {
                                $scholarName .= ' ' . $profile->middle_name;
                            }
                            if ($profile->extension_name) {
                                $scholarName .= ' ' . $profile->extension_name;
                            }
                        }
                        return [
                            'id' => $record->id,
                            'profile_id' => $record->profile_id,
                            'scholar_name' => $scholarName ?: 'N/A',
                            'years_of_service' => $record->years_of_service,
                            'service_start_date' => $record->service_start_date?->format('Y-m-d'),
                            'service_end_date' => $record->service_end_date?->format('Y-m-d'),
                            'completion_status' => $record->completion_status,
                            'remarks' => $record->remarks,
                        ];
                    })->values(),
                ];
            });

        // Get all courses for dropdown
        $courses = Course::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('ReturnOfService/Index', [
            'batches' => $batches,
            'courses' => $courses,
            'completionOptions' => [
                ['label' => 'Pending', 'value' => 'pending'],
                ['label' => 'Ongoing', 'value' => 'ongoing'],
                ['label' => 'Suspended', 'value' => 'suspended'],
                ['label' => 'Completed', 'value' => 'completed'],
            ],
        ]);
    }

    /**
     * Create a new return of service batch.
     */
    public function storeBatch(Request $request)
    {
        if (!Gate::allows('return-of-service.create')) {
            abort(403, 'User does not have the right permissions');
        }

        $validated = $request->validate([
            'batch_name' => 'required|string|max:255|unique:return_of_service_batches,batch_name',
            'description' => 'nullable|string',
            'exam_date_from' => 'nullable|date',
            'exam_date_to' => 'nullable|date|after_or_equal:exam_date_from',
            'result_date' => 'nullable|date',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        try {
            $batch = ReturnOfServiceBatch::create($validated);

            ActivityLogService::log(
                'Created ROS Batch: ' . $batch->batch_name,
                'return_of_service_batch',
                $batch->id,
                'created'
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Batch created successfully.',
                    'batch' => $batch,
                ]);
            }

            return back()->with('success', 'Batch created successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create batch: ' . $e->getMessage(),
                    'errors' => ['batch_name' => ['Failed to create batch']],
                ], 422);
            }

            return back()->with('error', 'Failed to create batch: ' . $e->getMessage());
        }
    }

    /**
     * Update a return of service batch.
     */
    public function updateBatch(Request $request, ReturnOfServiceBatch $batch)
    {
        if (!Gate::allows('return-of-service.edit')) {
            abort(403, 'User does not have the right permissions');
        }

        $validated = $request->validate([
            'batch_name' => 'required|string|max:255|unique:return_of_service_batches,batch_name,' . $batch->id,
            'description' => 'nullable|string',
            'exam_date_from' => 'nullable|date',
            'exam_date_to' => 'nullable|date|after_or_equal:exam_date_from',
            'result_date' => 'nullable|date',
            'course_id' => 'nullable|exists:courses,id',
        ]);

        try {
            $batch->update($validated);

            ActivityLogService::log(
                'Updated ROS Batch: ' . $batch->batch_name,
                'return_of_service_batch',
                $batch->id,
                'updated'
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Batch updated successfully.',
                    'batch' => $batch,
                ]);
            }

            return back()->with('success', 'Batch updated successfully.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update batch: ' . $e->getMessage(),
                    'errors' => ['batch_name' => ['Failed to update batch']],
                ], 422);
            }

            return back()->with('error', 'Failed to update batch: ' . $e->getMessage());
        }
    }

    /**
     * Delete a return of service batch.
     */
    public function destroyBatch(ReturnOfServiceBatch $batch)
    {
        if (!Gate::allows('return-of-service.delete')) {
            abort(403, 'User does not have the right permissions');
        }

        try {
            $batchName = $batch->batch_name;

            ActivityLogService::log(
                'Deleted ROS Batch: ' . $batchName,
                'return_of_service_batch',
                $batch->id,
                'deleted'
            );

            $batch->delete();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Batch deleted successfully.',
                ]);
            }

            return back()->with('success', 'Batch deleted successfully.');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete batch: ' . $e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Failed to delete batch: ' . $e->getMessage());
        }
    }

    /**
     * Get a single batch with its scholars.
     */
    public function batchShow(ReturnOfServiceBatch $batch)
    {
        if (!Gate::allows('return-of-service.view')) {
            abort(403, 'User does not have the right permissions');
        }

        $batch->load('rosRecords.profile', 'course');

        return response()->json([
            'id' => $batch->id,
            'batch_name' => $batch->batch_name,
            'description' => $batch->description,
            'course_id' => $batch->course_id,
            'course_name' => $batch->course?->name ?? 'N/A',
            'exam_date_from' => $batch->exam_date_from?->format('Y-m-d'),
            'exam_date_to' => $batch->exam_date_to?->format('Y-m-d'),
            'result_date' => $batch->result_date?->format('Y-m-d'),
            'total_scholars' => $batch->rosRecords->count(),
            'created_by' => $batch->createdBy?->name ?? 'System',
            'created_at' => $batch->created_at?->format('Y-m-d H:i'),
            'scholars' => $batch->rosRecords->map(function ($record) {
                $profile = $record->profile;
                $scholarName = '';
                if ($profile) {
                    $scholarName = $profile->last_name . ', ' . $profile->first_name;
                    if ($profile->middle_name) {
                        $scholarName .= ' ' . $profile->middle_name;
                    }
                    if ($profile->extension_name) {
                        $scholarName .= ' ' . $profile->extension_name;
                    }
                }
                return [
                    'id' => $record->id,
                    'profile_id' => $record->profile_id,
                    'scholar_name' => $scholarName ?: 'N/A',
                    'years_of_service' => $record->years_of_service,
                    'service_start_date' => $record->service_start_date?->format('Y-m-d'),
                    'service_end_date' => $record->service_end_date?->format('Y-m-d'),
                    'completion_status' => $record->completion_status,
                    'remarks' => $record->remarks,
                ];
            })->values(),
        ]);
    }

    /**
     * Add a scholar to a batch.
     */
    public function storeScholar(Request $request)
    {
        if (!Gate::allows('return-of-service.create')) {
            abort(403, 'User does not have the right permissions');
        }

        $validated = $request->validate([
            'batch_id' => 'required|exists:return_of_service_batches,id',
            'profile_id' => 'required|exists:scholarship_profiles,profile_id',
            'years_of_service' => 'nullable|integer|min:0',
            'service_start_date' => 'nullable|date',
            'service_end_date' => 'nullable|date',
            'completion_status' => 'required|in:pending,ongoing,suspended,completed',
            'remarks' => 'nullable|string',
        ]);

        // Get the latest scholarship record for the profile
        $profile = ScholarshipProfile::find($validated['profile_id']);
        if (!$profile) {
            return response()->json(['error' => 'Profile not found'], 404);
        }

        $latestRecord = $profile->latestScholarshipRecord()->first();
        if (!$latestRecord) {
            return response()->json(['error' => 'No scholarship record found for this profile'], 404);
        }

        // Add the scholarship_record_id to validated data
        $validated['scholarship_record_id'] = $latestRecord->id;

        $record = ReturnOfService::create($validated);

        ActivityLogService::log(
            'Added scholar to ROS Batch',
            'return_of_service',
            $record->id,
            'created',
            [],
            null,
            $validated['profile_id']
        );

        return response()->json(['success' => true, 'message' => 'Scholar added to batch successfully.']);
    }

    /**
     * Update a scholar's ROS record.
     */
    public function updateScholar(Request $request, ReturnOfService $record)
    {
        if (!Gate::allows('return-of-service.edit')) {
            abort(403, 'User does not have the right permissions');
        }

        $validated = $request->validate([
            'years_of_service' => 'nullable|integer|min:0',
            'service_start_date' => 'nullable|date',
            'service_end_date' => 'nullable|date',
            'completion_status' => 'required|in:pending,ongoing,suspended,completed',
            'remarks' => 'nullable|string',
        ]);

        $record->update($validated);
        ActivityLogService::log(
            'Updated ROS Scholar Record',
            'return_of_service',
            $record->id,
            'updated',
            [],
            null,
            $record->profile_id
        );

        return response()->json(['success' => true, 'message' => 'Scholar record updated successfully.']);
    }

    /**
     * Remove a scholar from a batch.
     */
    public function destroyScholar(ReturnOfService $record)
    {
        if (!Gate::allows('return-of-service.delete')) {
            abort(403, 'User does not have the right permissions');
        }

        try {
            ActivityLogService::log(
                'Removed scholar from ROS Batch',
                'return_of_service',
                $record->id,
                'deleted'
            );

            $record->delete();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Scholar removed from batch successfully.',
                ]);
            }

            return back()->with('success', 'Scholar removed from batch successfully.');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove scholar: ' . $e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Failed to remove scholar: ' . $e->getMessage());
        }
    }

    /**
     * Search for profiles to add to a batch.
     */
    public function searchRecords(Request $request)
    {
        if (!Gate::allows('return-of-service.create')) {
            abort(403, 'User does not have the right permissions');
        }

        $search = $request->get('q', '');
        $status = $request->get('status', null);
        $program = $request->get('program', null);

        // Query scholarship profiles with their scholarship records
        $query = ScholarshipProfile::with('scholarshipRecords')
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });

        // If status is provided, filter by scholarship record status
        if ($status) {
            $query->whereHas('scholarshipRecords', function ($q) use ($status, $program) {
                $q->where('scholarship_records.unified_status', $status);
                if ($program) {
                    // Filter by program name through the program relationship
                    $q->whereHas('program', function ($programQuery) use ($program) {
                        $programQuery->where('scholarship_programs.name', $program);
                    });
                }
            });
        } elseif ($program) {
            // If only program is provided
            $query->whereHas('scholarshipRecords', function ($q) use ($program) {
                $q->whereHas('program', function ($programQuery) use ($program) {
                    $programQuery->where('scholarship_programs.name', $program);
                });
            });
        }

        $profiles = $query->limit(20)
            ->get()
            ->map(fn($profile) => [
                'id' => $profile->profile_id,
                'label' => trim($profile->last_name . ', ' . $profile->first_name . ($profile->middle_name ? ' ' . $profile->middle_name : '') . ($profile->extension_name ? ' ' . $profile->extension_name : '')),
                'lastname' => $profile->last_name,
                'firstname' => $profile->first_name,
                'middlename' => $profile->middle_name ?? '',
                'ext' => $profile->extension_name ?? '',
            ]);

        return response()->json($profiles);
    }

    /**
     * Export ROS records to CSV.
     */
    public function export(Request $request)
    {
        if (!Gate::allows('return-of-service.export')) {
            abort(403, 'User does not have the right permissions');
        }

        $batchId = $request->get('batch_id');

        $records = ReturnOfService::with('batch', 'profile')
            ->when($batchId, function ($query) use ($batchId) {
                return $query->where('batch_id', $batchId);
            })
            ->ordered()
            ->get();

        $filename = 'return-of-service-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'Batch Name',
                'Scholar Name',
                'Course',
                'Years of Service',
                'Service Start Date',
                'Service End Date',
                'Completion Status',
                'Remarks'
            ]);

            // CSV Rows
            foreach ($records as $record) {
                fputcsv($file, [
                    $record->batch?->batch_name,
                    $record->profile?->full_name,
                    $record->batch?->course?->name,
                    $record->years_of_service,
                    $record->service_start_date?->format('Y-m-d'),
                    $record->service_end_date?->format('Y-m-d'),
                    ucfirst(str_replace('_', ' ', $record->completion_status)),
                    $record->remarks,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
