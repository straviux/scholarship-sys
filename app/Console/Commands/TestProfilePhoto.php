<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Http\Controllers\SystemReportController;
use Illuminate\Http\Request;

class TestProfilePhoto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:profile-photo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test profile photo functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::find(1);

        if (!$user) {
            $this->error('User 1 not found');
            return;
        }

        $this->info('Testing Profile Photo Functionality');
        $this->info('===================================');

        // Test User model methods
        $this->info('User ID: ' . $user->id);
        $this->info('User Name: ' . $user->name);
        $this->info('Profile Photo (DB): ' . ($user->profile_photo ?? 'NULL'));
        $this->info('Profile Photo URL: ' . ($user->profile_photo_url ?? 'NULL'));
        $this->info('Has Profile Photo: ' . ($user->hasProfilePhoto() ? 'true' : 'false'));

        $this->info('');

        // Test what controller would return
        $controller = new SystemReportController();
        $request = new Request();
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Use reflection to call the private method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('generateUserSummaryData');
        $method->setAccessible(true);

        $userSummary = $method->invoke($controller, $user);

        // Create the report data like the controller does
        $reportData = [
            'user_summary' => $userSummary,
            'encoded_summary' => base64_encode(json_encode($userSummary)),
            'generated_at' => now()->toDateTimeString(),
            'user_id' => $user->id,
            'user_name' => $user->name,
            'profile_photo_url' => $user->profile_photo_url,
            'has_profile_photo' => $user->hasProfilePhoto()
        ];

        $this->info('Controller Data:');
        $this->info('user_name: ' . $reportData['user_name']);
        $this->info('profile_photo_url: ' . ($reportData['profile_photo_url'] ?? 'NULL'));
        $this->info('has_profile_photo: ' . ($reportData['has_profile_photo'] ? 'true' : 'false'));

        $this->info('');
        $this->info('File System Check:');

        if ($user->profile_photo) {
            $fullPath = storage_path('app/public/' . $user->profile_photo);
            $this->info('Expected file path: ' . $fullPath);
            $this->info('File exists: ' . (file_exists($fullPath) ? 'YES' : 'NO'));

            if (file_exists($fullPath)) {
                $this->info('File size: ' . filesize($fullPath) . ' bytes');
            }
        }

        $this->info('Test completed!');
    }
}
