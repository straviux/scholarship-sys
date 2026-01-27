<?php

return [
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
        // If CHROME_PATH is not set, Browsershot will use its built-in Puppeteer Chrome
        'chrome_path' => env('CHROME_PATH', null),

        // Alternative paths to try if the primary path fails
        // Browsershot will auto-detect Chrome installations in these directories
        'fallback_paths' => [
            // Windows standard installation paths
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',

            // Puppeteer cache directories (version-agnostic)
            'C:\\Users\\' . get_current_user() . '\\.cache\\puppeteer\\chrome',
            'C:\\Users\\' . get_current_user() . '\\.cache\\puppeteer\\chrome-headless-shell',

            // Node modules local installations
            base_path('node_modules/puppeteer/.cache/chrome'),
            base_path('node_modules/puppeteer-extra-plugin-stealth/node_modules/puppeteer/.cache/chrome'),
        ],

        // Node binary path (usually auto-detected)
        'node_path' => env('NODE_PATH', null),

        // NPM binary path (usually auto-detected)
        'npm_path' => env('NPM_PATH', null),

        // Timeout for PDF generation (in seconds)
        'timeout' => env('BROWSERSHOT_TIMEOUT', 120),

        // Enable headless mode (recommended for production)
        'headless' => env('BROWSERSHOT_HEADLESS', true),
    ]
];
