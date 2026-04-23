<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicEnrollmentUpsertRequest;
use App\Http\Requests\GraduateAcademicEnrollmentRequest;
use App\Models\AcademicEnrollment;
use App\Models\ScholarshipProfile;
use App\Services\AcademicEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AcademicEnrollmentController extends Controller
{
    public function __construct(private readonly AcademicEnrollmentService $academicEnrollmentService)
    {
    }

    public function show(AcademicEnrollment $academicEnrollment): JsonResponse
    {
        Gate::authorize('scholarships.view');

        return response()->json([
            'success' => true,
            'data' => $this->academicEnrollmentService->loadEnrollment($academicEnrollment->id),
        ]);
    }

    public function store(AcademicEnrollmentUpsertRequest $request, ScholarshipProfile $profile): JsonResponse
    {
        Gate::authorize('scholarships.create');

        $enrollment = $this->academicEnrollmentService->create($profile, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Academic enrollment created successfully.',
            'data' => $enrollment,
        ], 201);
    }

    public function update(AcademicEnrollmentUpsertRequest $request, AcademicEnrollment $academicEnrollment): JsonResponse
    {
        Gate::authorize('scholarships.edit');

        $enrollment = $this->academicEnrollmentService->update($academicEnrollment, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Academic enrollment updated successfully.',
            'data' => $enrollment,
        ]);
    }

    public function destroy(AcademicEnrollment $academicEnrollment): JsonResponse
    {
        Gate::authorize('scholarships.delete');

        $this->academicEnrollmentService->delete($academicEnrollment);

        return response()->json([
            'success' => true,
            'message' => 'Academic enrollment deleted successfully.',
        ]);
    }

    public function graduate(GraduateAcademicEnrollmentRequest $request, AcademicEnrollment $academicEnrollment): JsonResponse
    {
        Gate::authorize('scholarships.edit');

        $enrollment = $this->academicEnrollmentService->graduate($academicEnrollment, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Graduation details updated successfully.',
            'data' => $enrollment,
        ]);
    }
}