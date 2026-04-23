<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicEnrollmentTermUpsertRequest;
use App\Http\Requests\CompleteAcademicEnrollmentTermRequest;
use App\Models\AcademicEnrollment;
use App\Models\AcademicEnrollmentTerm;
use App\Services\AcademicEnrollmentTermService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AcademicEnrollmentTermController extends Controller
{
    public function __construct(private readonly AcademicEnrollmentTermService $academicEnrollmentTermService)
    {
    }

    public function show(AcademicEnrollmentTerm $academicEnrollmentTerm): JsonResponse
    {
        Gate::authorize('scholarships.view');

        return response()->json([
            'success' => true,
            'data' => $this->academicEnrollmentTermService->loadTerm($academicEnrollmentTerm->id),
        ]);
    }

    public function store(AcademicEnrollmentTermUpsertRequest $request, AcademicEnrollment $academicEnrollment): JsonResponse
    {
        Gate::authorize('scholarships.create');

        $term = $this->academicEnrollmentTermService->create($academicEnrollment, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Academic term created successfully.',
            'data' => $term,
        ], 201);
    }

    public function update(AcademicEnrollmentTermUpsertRequest $request, AcademicEnrollmentTerm $academicEnrollmentTerm): JsonResponse
    {
        Gate::authorize('scholarships.edit');

        $term = $this->academicEnrollmentTermService->update($academicEnrollmentTerm, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Academic term updated successfully.',
            'data' => $term,
        ]);
    }

    public function destroy(AcademicEnrollmentTerm $academicEnrollmentTerm): JsonResponse
    {
        Gate::authorize('scholarships.delete');

        $this->academicEnrollmentTermService->delete($academicEnrollmentTerm);

        return response()->json([
            'success' => true,
            'message' => 'Academic term deleted successfully.',
        ]);
    }

    public function complete(CompleteAcademicEnrollmentTermRequest $request, AcademicEnrollmentTerm $academicEnrollmentTerm): JsonResponse
    {
        Gate::authorize('scholarships.edit');

        $term = $this->academicEnrollmentTermService->complete($academicEnrollmentTerm, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Academic term marked as completed successfully.',
            'data' => $term,
        ]);
    }
}