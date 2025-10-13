<?php

return [
    'approval_statuses' => [
        'pending' => [
            'label' => 'Pending Review',
            'color' => 'warning',
            'icon' => 'pi-clock',
            'description' => 'Application is awaiting review',
            'auto_approve' => false
        ],
        'approved' => [
            'label' => 'Approved',
            'color' => 'success',
            'icon' => 'pi-check-circle',
            'description' => 'Application has been approved',
            'auto_approve' => false
        ],
        'declined' => [
            'label' => 'Declined',
            'color' => 'danger',
            'icon' => 'pi-times-circle',
            'description' => 'Application has been declined',
            'auto_approve' => false
        ],
        'conditional' => [
            'label' => 'Conditional Approval',
            'color' => 'info',
            'icon' => 'pi-info-circle',
            'description' => 'Approved with conditions',
            'auto_approve' => false
        ],
        'resubmitted' => [
            'label' => 'Resubmitted',
            'color' => 'secondary',
            'icon' => 'pi-refresh',
            'description' => 'Application resubmitted after decline',
            'auto_approve' => true // Auto-approve resubmissions
        ],
        'withdrawn' => [
            'label' => 'Withdrawn',
            'color' => 'secondary',
            'icon' => 'pi-minus-circle',
            'description' => 'Application withdrawn by applicant',
            'auto_approve' => false
        ]
    ],

    'completion_statuses' => [
        'pending' => [
            'label' => 'Pending',
            'color' => 'warning',
            'icon' => 'pi-clock',
            'description' => 'Completion status pending review'
        ],
        'active' => [
            'label' => 'Active Scholarship',
            'color' => 'success',
            'icon' => 'pi-play-circle',
            'description' => 'Currently receiving scholarship benefits'
        ],
        'completed' => [
            'label' => 'Completed',
            'color' => 'info',
            'icon' => 'pi-check-circle',
            'description' => 'Successfully completed the program'
        ],
        'declined' => [
            'label' => 'Declined',
            'color' => 'danger',
            'icon' => 'pi-times-circle',
            'description' => 'Completion declined or not approved'
        ],
        'suspended' => [
            'label' => 'Suspended',
            'color' => 'danger',
            'icon' => 'pi-ban',
            'description' => 'Temporarily suspended'
        ],
        'discontinued' => [
            'label' => 'Discontinued',
            'color' => 'warning',
            'icon' => 'pi-pause-circle',
            'description' => 'Scholarship discontinued due to various reasons'
        ],
        'transferred' => [
            'label' => 'Transferred',
            'color' => 'secondary',
            'icon' => 'pi-arrow-right-arrow-left',
            'description' => 'Transferred to another program/school'
        ]
    ],

    'decline_reasons' => [
        'incomplete_documents' => 'Incomplete Required Documents',
        'ineligible_program' => 'Ineligible for Selected Program',
        'grade_requirements' => 'Does Not Meet Grade Requirements',
        'financial_ineligible' => 'Does Not Meet Financial Criteria',
        'duplicate_application' => 'Duplicate Application',
        'quota_exceeded' => 'Program Quota Already Filled',
        'conditional_deadline_expired' => 'Conditional Approval Deadline Expired',
        'other' => 'Other (See Details)'
    ],

    'degree_levels' => [
        'undergraduate' => [
            'label' => 'Undergraduate',
            'order' => 1,
            'next_levels' => ['graduate', 'masteral']
        ],
        'graduate' => [
            'label' => 'Graduate Studies',
            'order' => 2,
            'next_levels' => ['masteral', 'doctoral']
        ],
        'masteral' => [
            'label' => 'Master\'s Degree',
            'order' => 3,
            'next_levels' => ['doctoral']
        ],
        'doctoral' => [
            'label' => 'Doctoral Degree',
            'order' => 4,
            'next_levels' => []
        ]
    ],

    'application_cycle_limits' => [
        'max_cycles' => 4, // Maximum number of scholarship cycles per person
        'waiting_period_months' => 6, // Months to wait between applications
        'grade_requirement' => 2.0 // Minimum grade to qualify for next level
    ],

    // Auto-approval settings
    'auto_approval' => [
        'enabled' => true, // Master switch for auto-approval
        'conditions' => [
            'new_applications' => true, // Auto-approve new applications
            'resubmissions' => true, // Auto-approve resubmissions
            'renewals' => true, // Auto-approve renewal applications
            'grade_threshold' => 2.5, // Minimum grade for auto-approval
        ],
        'excluded_programs' => [], // Program IDs that should not auto-approve
        'notification_delay_minutes' => 5, // Delay before sending approval notifications
    ],

    // Browsershot / PDF Generation settings
    'browsershot' => [
        // Chrome executable path - can be overridden via CHROME_PATH env variable
        'chrome_path' => env('CHROME_PATH', 'C:\\Users\\Administrator\\.cache\\puppeteer\\chrome\\win64-140.0.7339.82\\chrome-win64\\chrome.exe'),

        // Alternative paths to try if the primary path fails
        'fallback_paths' => [
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Users\\Administrator\\.cache\\puppeteer\\chrome-headless-shell\\win64-140.0.7339.82\\chrome-headless-shell-win64\\chrome-headless-shell.exe',
        ],

        // Node binary path (usually auto-detected)
        'node_path' => env('NODE_PATH', null),

        // NPM binary path (usually auto-detected)
        'npm_path' => env('NPM_PATH', null),
    ]
];
