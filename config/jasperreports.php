<?php

/**
 * JasperReports Configuration
 * 
 * Configuration for JasperReports integration, including Java paths,
 * template locations, and output settings.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Java Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Java Runtime Environment (JRE) used by JasperReports.
    | Ensure Java 11+ is installed on the server.
    |
    */
    'java' => [
        'enabled' => env('JASPER_ENABLED', false),
        'path' => env('JAVA_HOME', 'java'), // Set JAVA_HOME environment variable on server
        'max_memory' => env('JASPER_JAVA_MEMORY', '512M'), // Maximum JVM memory
        'timeout' => env('JASPER_TIMEOUT', 60), // Seconds to wait for report generation
    ],

    /*
    |--------------------------------------------------------------------------
    | JasperStarter Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for JasperStarter CLI tool used to compile and execute
    | JasperReports. Download from: https://jasperstarter.sourceforge.io/
    |
    */
    'jasperstarter' => [
        'path' => env('JASPERSTARTER_PATH', 'jasperstarter'),
        'bin' => env('JASPERSTARTER_BIN', null), // Full path to jasperstarter.bat or jasperstarter.sh
        'version' => 'latest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Storage
    |--------------------------------------------------------------------------
    |
    | Directory paths for storing JasperReports templates (.jrxml files).
    | Templates can be organized by module/report type.
    |
    */
    'templates' => [
        'base_path' => storage_path('jasper-templates'),
        'compiled_path' => storage_path('jasper-compiled'),
        'cache_path' => storage_path('jasper-cache'),

        // Report-specific template locations
        'reports' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for report output formats and storage.
    |
    */
    'output' => [
        'path' => storage_path('jasper-output'),
        'formats' => ['pdf', 'xlsx', 'docx', 'html'], // Supported output formats
        'default_format' => 'pdf',
        'retention_days' => env('JASPER_RETENTION_DAYS', 7), // Delete old reports after X days
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Source Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for how data is passed to JasperReports.
    | Options: 'json', 'jdbc', 'csv', 'xml'
    |
    */
    'datasource' => [
        'type' => env('JASPER_DATASOURCE_TYPE', 'json'), // Default: JSON data export
        'jdbc' => [
            'enabled' => false,
            'driver' => env('JASPER_JDBC_DRIVER', 'com.mysql.jdbc.Driver'),
            'url' => env('JASPER_JDBC_URL', env('DB_CONNECTION') . '://' . env('DB_HOST') . ':' . env('DB_PORT') . '/' . env('DB_DATABASE')),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queuing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for async report generation using Laravel Queues.
    |
    */
    'queue' => [
        'enabled' => true,
        'connection' => env('QUEUE_CONNECTION', 'sync'),
        'queue' => 'jasper-reports',
        'tries' => 3,
        'timeout' => 300, // 5 minutes per job
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Debugging
    |--------------------------------------------------------------------------
    |
    | Configuration for logging and debugging JasperReports operations.
    |
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'stack',
        'log_queries' => env('JASPER_LOG_QUERIES', false), // Log all JasperReports queries
        'debug' => env('JASPER_DEBUG', false), // Keep temporary files for debugging
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for performance optimization.
    |
    */
    'performance' => [
        'batch_size' => 1000, // Records per batch when exporting large datasets
        'cache_templates' => true, // Cache compiled templates
        'parallel_execution' => false, // Use multiple threads (requires additional config)
    ],
];
