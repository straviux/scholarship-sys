<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileUploadSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class MobileUploadSettingController extends Controller
{
    private function checkAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('administrator')) {
            abort(403, 'Unauthorized');
        }
    }

    public function index()
    {
        $this->checkAdmin();

        return Inertia::render('Admin/MobileUploadSettings/Index', [
            'settings' => MobileUploadSetting::getCurrent(),
        ]);
    }

    public function update(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'general.base_url'        => 'required|string|max:255',
            'general.use_lan_ip'      => 'boolean',
            'general.lan_ip_override' => 'nullable|string|max:100',
            'general.port_override'   => 'nullable|string|max:10',

            'uploads.disbursement.max_size_kb'    => 'required|integer|min:512|max:102400',
            'uploads.disbursement.allowed_types'  => 'required|array|min:1',
            'uploads.scholarship_record.max_size_kb'   => 'required|integer|min:512|max:102400',
            'uploads.scholarship_record.allowed_types' => 'required|array|min:1',
            'uploads.profile.max_size_kb'    => 'required|integer|min:512|max:102400',
            'uploads.profile.allowed_types'  => 'required|array|min:1',
            'uploads.requirement.max_size_kb'    => 'required|integer|min:512|max:102400',
            'uploads.requirement.allowed_types'  => 'required|array|min:1',
            'uploads.fund_transaction.max_size_kb'    => 'required|integer|min:512|max:102400',
            'uploads.fund_transaction.allowed_types'  => 'required|array|min:1',

            'tokens.disbursement'       => 'required|integer|min:1|max:525600',
            'tokens.scholarship_record' => 'required|integer|min:1|max:525600',
            'tokens.profile'            => 'required|integer|min:1|max:525600',
            'tokens.requirement'        => 'required|integer|min:1|max:525600',
            'tokens.fund_transaction'   => 'required|integer|min:1|max:525600',

            'image.jpeg_quality'           => 'required|integer|min:10|max:100',
            'image.max_width'              => 'required|integer|min:320|max:8000',
            'image.max_height'             => 'required|integer|min:320|max:8000',
            'image.auto_rotate'            => 'boolean',
            'image.preserve_original_format' => 'boolean',
        ]);

        MobileUploadSetting::saveSettings($validated);

        // Clear all mobile upload caches so service picks up new values immediately
        Cache::forget('mobile_upload_base_url');
        Cache::forget('mobile_upload_db_settings');
        foreach (['disbursement', 'scholarship_record', 'profile', 'requirement', 'fund_transaction'] as $type) {
            Cache::forget("mobile_upload_entity_config_{$type}");
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Settings saved successfully.']);
        }

        return back()->with('success', 'Mobile upload settings saved.');
    }
}
