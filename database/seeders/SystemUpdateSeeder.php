<?php

namespace Database\Seeders;

use App\Models\SystemUpdate;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::role('administrator')->first();

        $updates = [
            [
                'title' => 'Welcome to the Updated Scholarship System',
                'content' => 'We\'ve implemented a new notification system to keep you informed about important updates and announcements. Click on the bell icon in the navigation to view your notifications.',
                'type' => 'info',
                'priority' => 'high',
                'is_global' => true,
                'created_by' => $adminUser?->id,
                'created_at' => now()->subDays(2),
            ],
            [
                'title' => 'System Maintenance Scheduled',
                'content' => 'System maintenance is scheduled for this weekend from 2:00 AM to 6:00 AM. During this time, the system may be temporarily unavailable. We apologize for any inconvenience.',
                'type' => 'warning',
                'priority' => 'urgent',
                'is_global' => true,
                'created_by' => $adminUser?->id,
                'created_at' => now()->subDays(1),
            ],
            [
                'title' => 'New Scholarship Program Available',
                'content' => 'A new scholarship program for Computer Science students has been added to the system. Check the Programs section for more details and application requirements.',
                'type' => 'success',
                'priority' => 'normal',
                'is_global' => true,
                'created_by' => $adminUser?->id,
                'created_at' => now()->subHours(12),
            ],
            [
                'title' => 'Profile Photo Feature Released',
                'content' => 'You can now upload and manage your profile photo! Go to your User Profile to add a professional photo that will appear throughout the system.',
                'type' => 'success',
                'priority' => 'normal',
                'is_global' => true,
                'created_by' => $adminUser?->id,
                'created_at' => now()->subHours(6),
            ],
            [
                'title' => 'Security Update Applied',
                'content' => 'We\'ve applied the latest security updates to ensure your data remains protected. No action is required from your end.',
                'type' => 'info',
                'priority' => 'low',
                'is_global' => true,
                'created_by' => $adminUser?->id,
                'created_at' => now()->subHours(2),
            ],
        ];

        foreach ($updates as $update) {
            SystemUpdate::create($update);
        }
    }
}
