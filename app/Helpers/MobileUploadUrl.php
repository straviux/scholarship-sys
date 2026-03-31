<?php

namespace App\Helpers;

use App\Services\MobileUploadConfigService;

/**
 * Mobile Upload URL Helper
 *
 * Provides convenient static methods for building mobile upload URLs.
 * This helper is a thin wrapper around the MobileUploadConfigService
 * for backward compatibility and quick access.
 *
 * For more advanced configuration and features, use MobileUploadConfigService directly.
 *
 * @deprecated Use \App\Services\MobileUploadConfigService or \App\Facades\MobileUploadConfig directly
 * @see \App\Services\MobileUploadConfigService
 * @see \App\Facades\MobileUploadConfig
 */
class MobileUploadUrl
{
    private static ?MobileUploadConfigService $configService = null;

    /**
     * Resolve the base URL for mobile upload links.
     * Delegates to MobileUploadConfigService for centralized management.
     *
     * @return string Base URL (scheme://host:port)
     */
    public static function resolveBaseUrl(): string
    {
        return self::getConfigService()->getBaseUrl();
    }

    /**
     * Build an absolute mobile upload URL using the resolved base URL.
     * Delegates to MobileUploadConfigService.
     *
     * @param string $routeName  Laravel route name
     * @param mixed  $parameters Route parameters (token, entity_id, etc.)
     * @return string            Complete absolute URL
     */
    public static function build(string $routeName, mixed $parameters = []): string
    {
        return self::getConfigService()->buildMobileUrl($routeName, $parameters);
    }

    /**
     * Get the configuration service instance.
     * Instantiates once and reuses for efficiency.
     *
     * @return MobileUploadConfigService
     */
    private static function getConfigService(): MobileUploadConfigService
    {
        if (self::$configService === null) {
            self::$configService = app(MobileUploadConfigService::class);
        }

        return self::$configService;
    }
}
