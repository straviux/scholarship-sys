<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\ScholarshipRecord;
use App\Models\ScholarshipProfile;
use App\Models\FundTransaction;
use App\Models\ScholarshipProgram;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            // 'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $oldData = $user->getAttributes();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Log profile update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: $oldData,
            newData: $user->fresh()->getAttributes(),
            remarks: "Updated profile information"
        );

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $userData = $user->getAttributes();

        Auth::logout();

        // Log account deletion
        ActivityLogService::logRecordDeleted(
            profileId: null,
            recordData: $userData,
            remarks: "Deleted user account: {$userData['name']}"
        );

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update the user's profile name.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $oldName = $user->name;
        $user->update([
            'name' => $request->name,
        ]);

        // Log profile name update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: ['name' => $oldName],
            newData: ['name' => $request->name],
            remarks: "Updated profile name from '{$oldName}' to '{$request->name}'"
        );

        return Redirect::back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Update the user's profile photo.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        // Debug: Log the request details
        Log::info('Photo upload request received', [
            'has_file' => $request->hasFile('photo'),
            'files' => $request->allFiles(),
            'all_input' => $request->all()
        ]);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'], // Increased max size to 10MB for processing
        ]);

        $user = $request->user();

        try {
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
                $originalSize = $photo->getSize();

                // Delete old profile photo if exists
                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                    Log::info('Deleted old profile photo: ' . $user->profile_photo);
                }

                // Generate unique filename
                $filename = 'profile-' . $user->id . '-' . time() . '.jpg'; // Always save as JPG for better compression

                // Process and compress the image
                $processedImagePath = $this->processAndCompressImage($photo, $filename);

                // Update user record
                $user->update(['profile_photo' => $processedImagePath]);

                // Log attachment upload
                ActivityLogService::logAttachmentUploaded(
                    profileId: null,
                    attachmentName: 'profile_photo',
                    fileName: $filename,
                    remarks: "Updated profile photo: {$filename}"
                );

                // Get the final file size for logging
                $finalSize = Storage::disk('public')->size($processedImagePath);
                $compressionRatio = round((1 - ($finalSize / $originalSize)) * 100, 2);

                Log::info('Photo upload successful', [
                    'user_id' => $user->id,
                    'filename' => $filename,
                    'path' => $processedImagePath,
                    'original_size' => $originalSize,
                    'final_size' => $finalSize,
                    'compression_ratio' => $compressionRatio . '%',
                    'original_mime_type' => $photo->getMimeType()
                ]);

                return Redirect::back()->with('success', 'Profile photo updated successfully!');
            }
        } catch (\Exception $e) {
            Log::error('Photo upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Redirect::back()->withErrors(['photo' => 'Failed to upload photo. Please try again.']);
        }

        return Redirect::back()->withErrors(['photo' => 'No photo file was uploaded.']);
    }

    /**
     * Process and compress the uploaded image
     */
    private function processAndCompressImage($uploadedFile, $filename): string
    {
        // Create image resource from uploaded file
        $imageResource = $this->createImageResource($uploadedFile);

        if (!$imageResource) {
            throw new \Exception('Failed to create image resource from uploaded file');
        }

        // Get original dimensions
        $originalWidth = imagesx($imageResource);
        $originalHeight = imagesy($imageResource);

        // Calculate new dimensions (max 400x400 for profile photos)
        $maxSize = 400;
        $ratio = min($maxSize / $originalWidth, $maxSize / $originalHeight);
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);

        // Create new image with calculated dimensions
        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        // Set white background for transparency
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefill($newImage, 0, 0, $white);

        // Resize image with high quality
        imagecopyresampled(
            $newImage,
            $imageResource,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        // Save to temporary location
        $tempPath = sys_get_temp_dir() . '/' . $filename;

        // Save as JPEG with 85% quality for good compression
        imagejpeg($newImage, $tempPath, 85);

        // Clean up memory
        imagedestroy($imageResource);
        imagedestroy($newImage);

        // Store the processed image
        $storagePath = 'profile-photos/' . $filename;
        Storage::disk('public')->put($storagePath, file_get_contents($tempPath));

        // Clean up temporary file
        unlink($tempPath);

        return $storagePath;
    }

    /**
     * Create image resource from uploaded file based on MIME type
     */
    private function createImageResource($uploadedFile)
    {
        $tempPath = $uploadedFile->getPathname();
        $mimeType = $uploadedFile->getMimeType();

        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($tempPath);
            case 'image/png':
                return imagecreatefrompng($tempPath);
            case 'image/gif':
                return imagecreatefromgif($tempPath);
            case 'image/webp':
                return imagecreatefromwebp($tempPath);
            default:
                return false;
        }
    }

    /**
     * Generate QR code for mobile profile photo upload.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateQrCode(Request $request)
    {
        try {
            $user = $request->user();

            // Generate upload token (30 days expiry)
            $user->generateUploadToken(43200);

            return response()->json([
                'qr_code_svg' => $user->getUploadQrCode(250),
                'url' => $user->getMobileUploadUrl(),
                'expires_at' => $user->upload_token_expires_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate QR code for profile upload', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code'
            ], 500);
        }
    }

    /**
     * Show mobile upload page for profile photo.
     *
     * @param string $token
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showMobileUpload($token)
    {
        // Find user by upload token
        $user = \App\Models\User::where('upload_token', $token)->first();

        if (!$user) {
            return view('mobile.upload-error', [
                'message' => 'Invalid upload link. Please request a new QR code.'
            ]);
        }

        // Check if token has expired
        if ($user->upload_token_expires_at < now()) {
            return view('mobile.upload-error', [
                'message' => 'Upload link has expired. Please request a new QR code.'
            ]);
        }

        return view('mobile.profile-upload', [
            'token' => $token,
            'user' => $user,
            'expiresAt' => $user->upload_token_expires_at,
        ]);
    }

    /**
     * Process mobile profile photo upload.
     *
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function processMobileUpload(Request $request, $token)
    {
        try {
            // Find user by upload token
            $user = \App\Models\User::where('upload_token', $token)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid upload token'
                ], 404);
            }

            // Check if token has expired
            if ($user->upload_token_expires_at < now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Upload token has expired'
                ], 401);
            }

            // Validate the uploaded file
            $request->validate([
                'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:10240'],
            ]);

            // Process the upload
            $photo = $request->file('photo');
            $originalSize = $photo->getSize();

            // Delete old profile photo if exists
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
                Log::info('Deleted old profile photo: ' . $user->profile_photo);
            }

            // Generate unique filename
            $filename = 'profile-' . $user->id . '-' . time() . '.jpg';

            // Process and compress the image
            $processedImagePath = $this->processAndCompressImage($photo, $filename);

            // Update user record
            $user->update(['profile_photo' => $processedImagePath]);

            // Get the final file size for logging
            $finalSize = Storage::disk('public')->size($processedImagePath);
            $compressionRatio = round((1 - ($finalSize / $originalSize)) * 100, 2);

            Log::info('Mobile photo upload successful', [
                'user_id' => $user->id,
                'filename' => $filename,
                'path' => $processedImagePath,
                'original_size' => $originalSize,
                'final_size' => $finalSize,
                'compression_ratio' => $compressionRatio . '%',
                'original_mime_type' => $photo->getMimeType()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile photo uploaded successfully',
                'photo_url' => asset('storage/' . $processedImagePath)
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Mobile photo upload failed', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload photo. Please try again.'
            ], 500);
        }
    }

    /**
     * Display encoded summary report for logged-in user
     */
    public function getUserSummaryReport(Request $request): Response
    {
        $user = $request->user();

        // Defensive check: ensure user is authenticated
        if (!$user) {
            Log::warning('User is not authenticated in getUserSummaryReport');
            return Inertia::render('User/Reports', [
                'status' => 'Error: User not authenticated',
                'error' => 'User is not properly authenticated'
            ]);
        }

        // Fetch from two independent tables
        $profiles = ScholarshipProfile::where('scholarship_profiles.created_by', $user->id)
            ->leftJoin('scholarship_records', 'scholarship_records.profile_id', '=', 'scholarship_profiles.profile_id')
            ->select('scholarship_profiles.*', 'scholarship_records.unified_status')
            ->distinct()
            ->get();
        $vouchers = FundTransaction::where('created_by', $user->id)->get();

        // Get all scholarship records created by user (for legacy stats)
        $allRecords = ScholarshipRecord::where('created_by', $user->id)
            ->with(['profile', 'program', 'course', 'school'])
            ->get();

        // Build encoding statistics with profile status breakdown
        $programBreakdown = [];
        $courseBreakdown = [];
        $schoolBreakdown = [];
        $profileStatusBreakdown = [];
        $today = now()->toDateString();
        $thisMonthStart = now()->startOfMonth()->toDateString();
        $thisMonthEnd = now()->endOfMonth()->toDateString();

        $todayCount = 0;
        $totalCreated = 0;
        $totalUpdated = 0;
        $existingApproved = 0;
        $latestActivityDate = null;

        foreach ($allRecords as $record) {
            // Count today's records
            if ($record->created_at->toDateString() === $today) {
                $todayCount++;
            }

            // Track total created
            $totalCreated++;

            // Get profile status (pending = 0, approved = 1 maps to unified_status)
            $profileStatus = in_array($record->unified_status, ['active', 'approved']) ? 'Approved' : 'Pending';

            // Count approved existing profiles
            if (in_array($record->unified_status, ['active', 'approved']) && $record->profile && !is_null($record->profile->profile_id)) {
                $existingApproved++;
            }

            // Latest activity
            if (!$latestActivityDate || $record->created_at > \Carbon\Carbon::parse($latestActivityDate)) {
                $latestActivityDate = $record->created_at->toDateTimeString();
            }

            // Program breakdown with status
            $programKey = $record->program ? $record->program->name : 'No Program';
            if (!isset($programBreakdown[$programKey])) {
                $programBreakdown[$programKey] = ['pending' => 0, 'approved' => 0, 'total' => 0];
            }
            if ($profileStatus === 'Approved') {
                $programBreakdown[$programKey]['approved']++;
            } else {
                $programBreakdown[$programKey]['pending']++;
            }
            $programBreakdown[$programKey]['total']++;

            // Course breakdown with status
            $courseKey = $record->course ? $record->course->name : 'No Course';
            if (!isset($courseBreakdown[$courseKey])) {
                $courseBreakdown[$courseKey] = ['pending' => 0, 'approved' => 0, 'total' => 0];
            }
            if ($profileStatus === 'Approved') {
                $courseBreakdown[$courseKey]['approved']++;
            } else {
                $courseBreakdown[$courseKey]['pending']++;
            }
            $courseBreakdown[$courseKey]['total']++;

            // School breakdown with status
            $schoolKey = $record->school ? $record->school->name : 'No School';
            if (!isset($schoolBreakdown[$schoolKey])) {
                $schoolBreakdown[$schoolKey] = ['pending' => 0, 'approved' => 0, 'total' => 0];
            }
            if ($profileStatus === 'Approved') {
                $schoolBreakdown[$schoolKey]['approved']++;
            } else {
                $schoolBreakdown[$schoolKey]['pending']++;
            }
            $schoolBreakdown[$schoolKey]['total']++;

            // Overall profile status breakdown
            if (!isset($profileStatusBreakdown[$profileStatus])) {
                $profileStatusBreakdown[$profileStatus] = 0;
            }
            $profileStatusBreakdown[$profileStatus]++;
        }

        // Get current month records for breakdown
        $currentMonthRecords = ScholarshipRecord::where('created_by', $user->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$thisMonthStart, $thisMonthEnd])
            ->with(['profile', 'program', 'course', 'school'])
            ->get();

        $programBreakdownCurrentMonth = [];
        $courseBreakdownCurrentMonth = [];
        $schoolBreakdownCurrentMonth = [];

        foreach ($currentMonthRecords as $record) {
            // Program breakdown - current month
            $programKey = $record->program ? $record->program->name : 'No Program';
            if (!isset($programBreakdownCurrentMonth[$programKey])) {
                $programBreakdownCurrentMonth[$programKey] = ['pending' => 0, 'approved' => 0, 'total' => 0];
            }
            $profileStatus = in_array($record->unified_status, ['active', 'approved']) ? 'Approved' : 'Pending';
            if ($profileStatus === 'Approved') {
                $programBreakdownCurrentMonth[$programKey]['approved']++;
            } else {
                $programBreakdownCurrentMonth[$programKey]['pending']++;
            }
            $programBreakdownCurrentMonth[$programKey]['total']++;

            // Course breakdown - current month
            $courseKey = $record->course ? $record->course->name : 'No Course';
            if (!isset($courseBreakdownCurrentMonth[$courseKey])) {
                $courseBreakdownCurrentMonth[$courseKey] = ['pending' => 0, 'approved' => 0, 'total' => 0];
            }
            if ($profileStatus === 'Approved') {
                $courseBreakdownCurrentMonth[$courseKey]['approved']++;
            } else {
                $courseBreakdownCurrentMonth[$courseKey]['pending']++;
            }
            $courseBreakdownCurrentMonth[$courseKey]['total']++;

            // School breakdown - current month
            $schoolKey = $record->school ? $record->school->name : 'No School';
            if (!isset($schoolBreakdownCurrentMonth[$schoolKey])) {
                $schoolBreakdownCurrentMonth[$schoolKey] = ['pending' => 0, 'approved' => 0, 'total' => 0];
            }
            if ($profileStatus === 'Approved') {
                $schoolBreakdownCurrentMonth[$schoolKey]['approved']++;
            } else {
                $schoolBreakdownCurrentMonth[$schoolKey]['pending']++;
            }
            $schoolBreakdownCurrentMonth[$schoolKey]['total']++;
        }

        // Format breakdowns for frontend consumption
        $programData = array_map(function ($name, $stats) {
            return [
                'program_name' => $name,
                'count' => $stats['total'],
                'pending' => $stats['pending'],
                'approved' => $stats['approved']
            ];
        }, array_keys($programBreakdown), array_values($programBreakdown));

        $courseData = array_map(function ($name, $stats) {
            return [
                'course_name' => $name,
                'count' => $stats['total'],
                'pending' => $stats['pending'],
                'approved' => $stats['approved']
            ];
        }, array_keys($courseBreakdown), array_values($courseBreakdown));

        $schoolData = array_map(function ($name, $stats) {
            return [
                'school_name' => $name,
                'count' => $stats['total'],
                'pending' => $stats['pending'],
                'approved' => $stats['approved']
            ];
        }, array_keys($schoolBreakdown), array_values($schoolBreakdown));

        $programDataCurrentMonth = array_map(function ($name, $stats) {
            return [
                'program_name' => $name,
                'count' => $stats['total'],
                'pending' => $stats['pending'],
                'approved' => $stats['approved']
            ];
        }, array_keys($programBreakdownCurrentMonth), array_values($programBreakdownCurrentMonth));

        $courseDataCurrentMonth = array_map(function ($name, $stats) {
            return [
                'course_name' => $name,
                'count' => $stats['total'],
                'pending' => $stats['pending'],
                'approved' => $stats['approved']
            ];
        }, array_keys($courseBreakdownCurrentMonth), array_values($courseBreakdownCurrentMonth));

        $schoolDataCurrentMonth = array_map(function ($name, $stats) {
            return [
                'school_name' => $name,
                'count' => $stats['total'],
                'pending' => $stats['pending'],
                'approved' => $stats['approved']
            ];
        }, array_keys($schoolBreakdownCurrentMonth), array_values($schoolBreakdownCurrentMonth));

        // Build profiles array - from ScholarshipProfile
        $profilesArray = $profiles->map(function ($profile) {
            // Format name as: lastname, firstname middlename
            $firstName = $profile->first_name ?? '';
            $middleName = $profile->middle_name ?? '';
            $lastName = $profile->last_name ?? '';

            $nameFormatted = $lastName;
            if ($firstName || $middleName) {
                $nameFormatted .= ', ' . trim("$firstName $middleName");
            }
            $profileName = !empty($lastName) ? $nameFormatted : 'Unknown';

            // Use unified_status from related record, fallback to is_active
            $status = $profile->unified_status ?? ($profile->is_active ? 'active' : 'inactive');

            return [
                'profile_name' => $profileName,
                'status' => $status,
                'date_filed' => $profile->date_filed ? \Carbon\Carbon::parse($profile->date_filed)->toDateTimeString() : null,
                'created_at' => $profile->created_at->toDateTimeString(),
                'created_by' => $profile->created_by,
            ];
        })->values();

        // Build vouchers array - from Voucher table
        $vouchersArray = $vouchers->map(function ($voucher) {
            return [
                'voucher_id' => $voucher->id,
                'voucher_number' => $voucher->voucher_number,
                'scholar_ids' => $voucher->scholar_ids,
                'amount' => $voucher->amount,
                'transaction_status' => $voucher->transaction_status,
                'created_at' => $voucher->created_at->toDateTimeString(),
                'created_by' => $voucher->created_by,
            ];
        })->values();

        // Ultra-minimal profile - just basic user info
        $reportData = [
            'user_summary' => [
                'basic_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'created_at' => $user->created_at->toDateTimeString(),
                ],
                'encoding_statistics' => [
                    'applications' => [
                        'today' => $todayCount,
                        'total_created' => $totalCreated,
                        'total_updated' => $totalUpdated,
                        'existing_approved' => $existingApproved,
                    ],
                    'summary' => $profileStatusBreakdown,
                    'latest_activity_date' => $latestActivityDate,
                    'breakdowns' => [
                        'by_program' => $programData,
                        'by_course' => $courseData,
                        'by_school' => $schoolData,
                        'by_program_current_month' => $programDataCurrentMonth,
                        'by_course_current_month' => $courseDataCurrentMonth,
                        'by_school_current_month' => $schoolDataCurrentMonth,
                    ]
                ]
            ],
            'generated_at' => now()->toDateTimeString(),
            'user_id' => $user->id,
            'user_name' => $user->name,
            'profile_photo_url' => $user->profile_photo_url,
            'has_profile_photo' => $user->hasProfilePhoto(),
            // Add detailed records for reports
            'profiles' => $profilesArray->toArray(),
            'vouchers' => $vouchersArray->toArray()
        ];

        return Inertia::render('User/Reports', [
            'reportData' => $reportData,
            'stats' => [
                'total_profiles' => $profilesArray->count(),
                'total_vouchers' => $vouchersArray->count(),
            ]
        ]);
    }

    /**
     * Get encoding records for a specific date grouped by date.
     */
    public function getRecordsByDate(Request $request)
    {
        try {
            $request->validate([
                'date' => ['nullable', 'date'],
            ]);

            $user = $request->user();

            // Defensive check: ensure user is authenticated
            if (!$user) {
                Log::warning('User is not authenticated in getRecordsByDate');
                return response()->json([
                    'message' => 'Unauthenticated user',
                    'error' => 'User is not properly authenticated'
                ], 401);
            }

            $userId = $user->id ?? Auth::id();
            $date = $request->query('date') ? \Carbon\Carbon::parse($request->query('date')) : now();
            $dateString = $date->toDateString();

            // Log the query parameters for debugging
            Log::debug('getRecordsByDate called', [
                'user_id' => $userId,
                'date_string' => $dateString,
                'app_timezone' => config('app.timezone')
            ]);

            // Get all records created by the user on the specified date
            // whereDate() works correctly now that APP_TIMEZONE is set properly
            $records = ScholarshipRecord::where('created_by', $userId)
                ->whereDate('created_at', $dateString)
                ->with('profile')
                ->orderBy('created_at', 'desc')
                ->get();

            // Log results for debugging
            Log::debug('getRecordsByDate results', [
                'user_id' => $userId,
                'date_string' => $dateString,
                'record_count' => count($records),
                'records' => $records->pluck('id')->toArray()
            ]);

            $records = $records->map(function ($record) {
                $applicantName = 'Unknown';
                if ($record->profile) {
                    $parts = array_filter([
                        $record->profile->first_name,
                        $record->profile->middle_name,
                        $record->profile->last_name
                    ]);
                    $applicantName = implode(' ', $parts) ?: 'Unknown';
                }

                $programName = 'Unknown';
                if ($record->program_id) {
                    try {
                        $program = ScholarshipProgram::find($record->program_id);
                        if ($program) {
                            $programName = $program->name;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error loading program for record ' . $record->id . ': ' . $e->getMessage());
                    }
                }

                return [
                    'id' => $record->id,
                    'applicant_name' => $applicantName,
                    'program_name' => $programName,
                    'academic_year' => $record->academic_year,
                    'term' => $record->term,
                    'created_at' => $record->created_at->toDateTimeString(),
                    'created_time' => $record->created_at->format('H:i:s'),
                ];
            });

            // Get all dates that have records for calendar marking
            $recordDates = ScholarshipRecord::where('created_by', $userId)
                ->select(DB::raw('DATE(created_at) as record_date'))
                ->distinct()
                ->pluck('record_date');

            return response()->json([
                'date' => $date->toDateString(),
                'records' => $records,
                'record_count' => count($records),
                'all_record_dates' => $recordDates,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching records by date: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'date' => $request->query('date'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error fetching records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary of records grouped by date for calendar view.
     */
    public function getRecordsSummaryByMonth(Request $request)
    {
        try {
            $request->validate([
                'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
                'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            ]);

            $user = $request->user();

            // Defensive check: ensure user is authenticated
            if (!$user) {
                Log::warning('User is not authenticated in getRecordsSummaryByMonth');
                return response()->json([
                    'message' => 'Unauthenticated user',
                    'error' => 'User is not properly authenticated'
                ], 401);
            }

            $userId = $user->id ?? Auth::id();
            $year = $request->query('year') ? (int)$request->query('year') : now()->year;
            $month = $request->query('month') ? (int)$request->query('month') : now()->month;

            // Get records count grouped by date for the specified month
            $recordsByDate = ScholarshipRecord::where('created_by', $userId)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->date => $item->count];
                });

            return response()->json([
                'year' => $year,
                'month' => $month,
                'records_by_date' => $recordsByDate,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching records summary by month: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'year' => $request->query('year'),
                'month' => $request->query('month')
            ]);

            return response()->json([
                'message' => 'Error fetching records summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
