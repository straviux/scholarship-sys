<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Mobile Upload Configuration Facade
 *
 * Provides a convenient static interface to the MobileUploadConfigService.
 *
 * Usage:
 *     // Get base URL
 *     $url = MobileUploadConfig::getBaseUrl();
 *
 *     // Build full mobile URL
 *     $fullUrl = MobileUploadConfig::buildMobileUrl('mobile.disbursement.upload', $params);
 *
 *     // Get token expiry
 *     $minutes = MobileUploadConfig::getTokenExpiryMinutes('disbursement');
 *
 *     // Validate file
 *     $error = MobileUploadConfig::validateUploadFile($file, 'disbursement');
 *
 * @method static string getBaseUrl()
 * @method static string buildMobileUrl(string $routeName, mixed $parameters = [])
 * @method static int getTokenExpiryMinutes(string $entityType = '')
 * @method static int getTokenExpiryDays(string $entityType = '')
 * @method static array getTokenConfig()
 * @method static array getUploadConfig(string $entityType = null)
 * @method static string getStoragePath(string $entityType = null)
 * @method static string|null validateUploadFile(\Illuminate\Http\UploadedFile $file, string $entityType)
 * @method static array getImageOptimizationConfig()
 * @method static array getQrConfig()
 * @method static array getSecurityConfig()
 * @method static bool isFileTypeAllowed(string $extension, string $entityType = null)
 * @method static int getMaxUploadSizeKb(string $entityType = null)
 * @method static string|null getMimeType(string $extension)
 * @method static array getEntityTypes()
 * @method static bool isImageOptimizationEnabled()
 * @method static int getJpegQuality()
 * @method static bool isDebugLoggingEnabled()
 * @method static string getCurrentEnvironment()
 * @method static array getEnvironmentConfig(string $env)
 * @method static void clearCache()
 * @method static array getAllConfig()
 *
 * @see \App\Services\MobileUploadConfigService
 */
class MobileUploadConfig extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'mobile.upload.config';
    }
}
