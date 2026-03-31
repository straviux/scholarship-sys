<?php

/**
 * Mobile Upload Configuration
 *
 * This module centralizes all mobile upload and file management settings.
 * Controls:
 * - URL generation and deployment endpoints
 * - Token configuration and security
 * - File upload limits and constraints
 * - Storage paths and optimization
 * - Environment-specific settings
 *
 * @version 1.0
 * @last_updated 2026-03-31
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Deployment & URL Configuration
    |--------------------------------------------------------------------------
    */

    'deployment' => [
        // Environment-specific URL configuration
        'environments' => [
            'local' => [
                'base_url' => env('APP_URL', 'http://localhost:8000'),
                'port' => env('MOBILE_UPLOAD_PORT', env('APP_PORT', 8000)),
                'use_lan_ip' => false,  // Use hostname in local
                'scheme' => 'http',
            ],
            'staging' => [
                'base_url' => env('APP_URL', 'https://staging.scholars.local'),
                'port' => env('MOBILE_UPLOAD_PORT', 443),
                'use_lan_ip' => false,
                'scheme' => 'https',
            ],
            'production' => [
                'base_url' => env('APP_URL', 'https://scholars.local'),
                'port' => env('MOBILE_UPLOAD_PORT', 443),
                'use_lan_ip' => false,  // Production uses domain, not IP
                'scheme' => 'https',
            ],
        ],

        // Current environment (fallback to Laravel's APP_ENV)
        'current_env' => env('APP_ENV', 'local'),

        // Cache settings for URL resolution
        'cache_url_resolution' => [
            'enabled' => env('APP_ENV') !== 'local',  // Disable cache in local
            'ttl_minutes' => 5,  // 5 minutes
        ],

        // Use LAN IP detection for mobile devices on same network
        'mobile_lan_detection' => [
            'enabled' => env('MOBILE_LAN_DETECTION', false),
            'fallback_to_domain' => true,  // Fallback to APP_URL if IP detection fails
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    */

    'tokens' => [
        // Default token expiration (in days)
        'expiry_days' => env('MOBILE_UPLOAD_TOKEN_EXPIRY_DAYS', 30),

        // Token expiry per entity type (override global default)
        'expiry_by_type' => [
            'disbursement' => env('DISBURSEMENT_TOKEN_EXPIRY_DAYS', 30),
            'scholarship_record' => env('SCHOLARSHIP_RECORD_TOKEN_EXPIRY_DAYS', 30),
            'profile' => env('PROFILE_UPLOAD_TOKEN_EXPIRY_DAYS', 5),  // Shorter for profile
            'requirement' => env('REQUIREMENT_TOKEN_EXPIRY_DAYS', 30),
            'fund_transaction' => env('FUND_TRANSACTION_TOKEN_EXPIRY_DAYS', 30),
        ],

        // Token length (alphanumeric, secure)
        'length' => 32,

        // Token validation settings
        'validation' => [
            'require_exact_match' => true,  // Must match exactly (not partial)
            'case_sensitive' => true,       // Tokens are case-sensitive
            'rate_limit_attempts' => 10,    // Max attempts before rate limit
            'rate_limit_window' => 3600,    // Window in seconds (1 hour)
        ],

        // Refresh token settings (optional: allow refresh before expiry)
        'refresh' => [
            'enabled' => true,
            'can_refresh_before_expiry_days' => 5,  // Can refresh 5 days before expiry
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    */

    'uploads' => [
        // Maximum file size (in KB)
        'max_size_kb' => env('MOBILE_UPLOAD_MAX_SIZE_KB', 25600),  // 25MB default

        // Allowed file types
        'allowed_types' => [
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'documents' => ['pdf'],
            'all' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'webp'],
        ],

        // File type MIME mapping
        'mime_types' => [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'gif' => 'image/gif',
        ],

        // Upload validation rules by entity type
        'validation_by_type' => [
            'disbursement' => [
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                'max_size_kb' => 25600,
                'required' => true,
            ],
            'scholarship_record' => [
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                'max_size_kb' => 25600,
                'required' => true,
            ],
            'profile' => [
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
                'max_size_kb' => 10240,  // 10MB for profile
                'required' => true,
            ],
            'requirement' => [
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                'max_size_kb' => 25600,
                'required' => true,
            ],
            'fund_transaction' => [
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                'max_size_kb' => 25600,
                'required' => true,
            ],
        ],

        // Storage paths
        'storage_paths' => [
            'disk' => 'public',  // Laravel filesystem disk
            'base_path' => 'uploads',
            'by_type' => [
                'disbursement' => 'uploads/disbursements',
                'scholarship_record' => 'uploads/scholarship-records',
                'profile' => 'uploads/profiles',
                'requirement' => 'uploads/requirements',
                'fund_transaction' => 'uploads/fund-transactions',
            ],
            'temp' => 'uploads/temp',  // Temporary files
        ],

        // Cleanup old uploads
        'cleanup' => [
            'enabled' => env('MOBILE_UPLOAD_CLEANUP_ENABLED', true),
            'delete_temp_after_days' => env('MOBILE_UPLOAD_CLEANUP_TEMP_DAYS', 7),  // 7 days
            'delete_failed_after_days' => env('MOBILE_UPLOAD_CLEANUP_FAILED_DAYS', 30),  // 30 days
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Optimization Configuration
    |--------------------------------------------------------------------------
    */

    'image_optimization' => [
        // Enable/disable image optimization
        'enabled' => env('MOBILE_IMAGE_OPTIMIZATION_ENABLED', true),

        // JPEG compression quality (1-100)
        'jpeg_quality' => env('MOBILE_IMAGE_JPEG_QUALITY', 60),

        // Maximum image dimensions (pixels)
        'max_width' => env('MOBILE_IMAGE_MAX_WIDTH', 1920),
        'max_height' => env('MOBILE_IMAGE_MAX_HEIGHT', 1920),

        // Portrait enforcement (for profile photos, etc.)
        'enforce_portrait' => [
            'enabled' => env('MOBILE_IMAGE_ENFORCE_PORTRAIT', false),
            'min_height_ratio' => 1.2,  // Height >= width * 1.2
        ],

        // Auto-rotate based on EXIF orientation
        'auto_rotate' => env('MOBILE_IMAGE_AUTO_ROTATE', true),

        // Default image format for conversion
        'output_format' => 'jpeg',
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Configuration
    |--------------------------------------------------------------------------
    */

    'pdf' => [
        // Enable PDF compression
        'compression' => env('MOBILE_PDF_COMPRESSION_ENABLED', true),

        // Compression method ('gzip' or 'brotli')
        'compression_method' => env('MOBILE_PDF_COMPRESSION_METHOD', 'gzip'),

        // Maximum downloadable size after compression
        'max_file_size_kb' => 25600,  // Match upload limit
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */

    'routes' => [
        // URL prefix for mobile upload routes
        'prefix' => env('MOBILE_UPLOAD_ROUTE_PREFIX', 'mobile/upload'),

        // Named routes (used in config for flexibility)
        'names' => [
            'disbursement_show' => 'mobile.disbursement.upload',
            'disbursement_submit' => 'mobile.disbursement.upload.submit',
            'scholarship_record_show' => 'mobile.scholarship-record.upload',
            'scholarship_record_submit' => 'mobile.scholarship-record.upload.submit',
            'profile_show' => 'mobile.profile.upload',
            'profile_submit' => 'mobile.profile.upload.submit',
            'requirement_show' => 'mobile.requirement.upload',
            'requirement_submit' => 'mobile.requirement.upload.submit',
            'fund_transaction_show' => 'mobile.upload.fund-transaction',
            'fund_transaction_show_typed' => 'mobile.upload.fund-transaction.with-type',
            'fund_transaction_submit' => 'mobile.upload.fund-transaction.submit',
        ],

        // Middleware for protected routes
        'middleware' => [
            'auth' => ['auth', 'verified'],
            'public' => [],  // Public requests need no auth
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | QR Code Configuration
    |--------------------------------------------------------------------------
    */

    'qr' => [
        // QR code generation settings
        'enabled' => true,

        // Default QR code size (pixels)
        'default_size' => 250,

        // Size variations
        'sizes' => [
            'small' => 150,
            'medium' => 250,
            'large' => 400,
        ],

        // Error correction level (L, M, Q, H)
        'error_correction' => 'M',

        // Cache QR codes
        'cache' => [
            'enabled' => env('MOBILE_QR_CACHE_ENABLED', true),
            'ttl_minutes' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & Rate Limiting
    |--------------------------------------------------------------------------
    */

    'security' => [
        // Rate limiting per IP
        'rate_limit' => [
            'enabled' => env('MOBILE_RATE_LIMIT_ENABLED', true),
            'requests_per_minute' => env('MOBILE_RATE_LIMIT_REQUESTS', 30),
            'window_minutes' => 1,
        ],

        // CSRF token handling
        'csrf' => [
            'enabled' => true,
            'exempt_paths' => [
                'mobile/upload/*',  // No CSRF for mobile uploads
            ],
        ],

        // IP validation for public uploads
        'ip_validation' => [
            'enabled' => env('MOBILE_IP_VALIDATION_ENABLED', false),
            'allowed_ips' => explode(',', env('MOBILE_ALLOWED_IPS', '')),  // Comma-separated
        ],

        // Logging sensitive operations
        'audit_logging' => [
            'enabled' => true,
            'log_token_generation' => true,
            'log_uploads' => true,
            'log_failures' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response & Notification Configuration
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        // Notify user after upload
        'send_upload_confirmation' => env('MOBILE_NOTIFY_ON_UPLOAD', true),

        // Notify admin of new uploads
        'send_admin_notification' => env('MOBILE_NOTIFY_ADMIN_ON_UPLOAD', true),

        // Email templates
        'email_templates' => [
            'upload_success' => 'emails.mobile-upload-success',
            'upload_failed' => 'emails.mobile-upload-failed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debugging & Development
    |--------------------------------------------------------------------------
    */

    'debug' => [
        // Enable detailed logging
        'enable_logging' => env('MOBILE_UPLOAD_DEBUG_LOGGING', env('APP_DEBUG', false)),

        // Log level (debug, info, warning, error)
        'log_level' => env('MOBILE_UPLOAD_LOG_LEVEL', 'info'),

        // Log channel (single, stack, daily, etc.)
        'log_channel' => env('LOG_CHANNEL', 'stack'),

        // Keep upload history
        'keep_history' => env('MOBILE_UPLOAD_KEEP_HISTORY', true),
    ],

];
