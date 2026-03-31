<?php

namespace App\Providers;

use App\Services\MobileUploadConfigService;
use Illuminate\Support\ServiceProvider;

/**
 * Mobile Upload Service Provider
 *
 * Registers the MobileUploadConfigService into the service container.
 * This provider also handles any initialization logic needed for
 * mobile upload features.
 *
 * Bindings:
 * - MobileUploadConfigService: Singleton instance
 *
 * Usage:
 *     // Via dependency injection
 *     public function __construct(MobileUploadConfigService $config) {}
 *
 *     // Via service container
 *     app(MobileUploadConfigService::class)->getBaseUrl()
 *
 *     // Via facade (if registered)
 *     MobileUploadConfig::getBaseUrl()
 */
class MobileUploadServiceProvider extends ServiceProvider
{
    /**
     * Register the mobile upload configuration service.
     *
     * @return void
     */
    public function register(): void
    {
        // Register as singleton - same instance throughout request
        $this->app->singleton(MobileUploadConfigService::class, function () {
            return new MobileUploadConfigService();
        });

        // Alias for convenience
        $this->app->alias(
            MobileUploadConfigService::class,
            'mobile.upload.config'
        );
    }

    /**
     * Publish configuration files.
     *
     * @return void
     */
    public function boot(): void
    {
        // Configuration is auto-discovered in Laravel 11
        // No need for explicit publish calls
    }
}
