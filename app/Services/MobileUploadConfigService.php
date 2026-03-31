<?php

namespace App\Services;

use App\Models\MobileUploadSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Mobile Upload Configuration Service
 *
 * Manages centralized mobile upload configuration including:
 * - URL resolution for different deployment environments
 * - Token settings and validation
 * - File upload constraints and validation
 * - Image optimization settings
 * - Entity-specific configuration
 *
 * This service provides a single source of truth for mobile upload config,
 * making it easy to manage deployment differences across environments.
 *
 * @example
 *     $service = app(MobileUploadConfigService::class);
 *
 *     // Get base URL for mobile uploads
 *     $url = $service->getBaseUrl();
 *
 *     // Build full mobile upload URL
 *     $fullUrl = $service->buildMobileUrl('mobile.disbursement.upload', ['token' => $token]);
 *
 *     // Get token expiry for entity type
 *     $days = $service->getTokenExpiryDays('disbursement');
 *
 *     // Validate file upload
 *     $isValid = $service->validateUploadFile($file, 'disbursement');
 *
 * @version 1.0
 * @author Scholarship System
 */
class MobileUploadConfigService
{
    /**
     * Cache key for base URL resolution
     */
    private const CACHE_KEY_BASE_URL = 'mobile_upload_base_url';

    /**
     * Cache key prefix for entity config
     */
    private const CACHE_KEY_ENTITY_CONFIG = 'mobile_upload_entity_config_';

    /**
     * Cache key for DB settings
     */
    private const CACHE_KEY_DB_SETTINGS = 'mobile_upload_db_settings';

    /**
     * Load settings from the DB (cached), falling back to an empty array on failure.
     */
    private function dbSettings(): array
    {
        return Cache::remember(self::CACHE_KEY_DB_SETTINGS, 300, function () {
            try {
                return MobileUploadSetting::getCurrent();
            } catch (\Throwable $e) {
                Log::warning('MobileUploadSetting DB read failed, using config defaults', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Resolve the base URL for mobile uploads.
     * Handles environment-specific URLs and LAN IP detection.
     *
     * @return string Base URL (scheme://host:port)
     */
    public function getBaseUrl(): string
    {
        $cacheEnabled = config('mobileupload.deployment.cache_url_resolution.enabled', true);
        $cacheTtl = config('mobileupload.deployment.cache_url_resolution.ttl_minutes', 5) * 60;

        if ($cacheEnabled) {
            return Cache::remember(self::CACHE_KEY_BASE_URL, $cacheTtl, function () {
                return $this->resolveBaseUrl();
            });
        }

        return $this->resolveBaseUrl();
    }

    /**
     * Resolve base URL without caching (internal use).
     * Determines URL based on environment settings and mobile LAN detection.
     *
     * @return string
     */
    private function resolveBaseUrl(): string
    {
        $db = $this->dbSettings();

        // If DB has a base_url, use it (with optional port override)
        if (! empty($db['general']['base_url'])) {
            $baseUrl = $db['general']['base_url'];
            $scheme  = parse_url($baseUrl, PHP_URL_SCHEME) ?? 'http';
            $host    = parse_url($baseUrl, PHP_URL_HOST) ?? 'localhost';
            $port    = ! empty($db['general']['port_override'])
                ? $db['general']['port_override']
                : (parse_url($baseUrl, PHP_URL_PORT) ?: null);
            $suffix  = $port ? ":{$port}" : '';

            // LAN IP
            $useLan = $db['general']['use_lan_ip'] ?? false;
            if ($useLan) {
                $ip = ! empty($db['general']['lan_ip_override'])
                    ? $db['general']['lan_ip_override']
                    : $this->detectLanIp();
                return "{$scheme}://{$ip}{$suffix}";
            }

            return "{$scheme}://{$host}{$suffix}";
        }

        // Fallback: config file + env
        $currentEnv = $this->getCurrentEnvironment();
        $envConfig = $this->getEnvironmentConfig($currentEnv);

        $appUrl = $envConfig['base_url'] ?? config('app.url', 'http://localhost:8000');
        $scheme  = $envConfig['scheme'] ?? parse_url($appUrl, PHP_URL_SCHEME) ?? 'http';
        $port    = $envConfig['port'] ?? env('MOBILE_UPLOAD_PORT') ?: parse_url($appUrl, PHP_URL_PORT);
        $suffix  = $port ? ":{$port}" : '';

        if ($this->shouldUseLanIp($currentEnv)) {
            $ip = $this->detectLanIp();
            return "{$scheme}://{$ip}{$suffix}";
        }

        $host = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';
        return "{$scheme}://{$host}{$suffix}";
    }

    /**
     * Build a full mobile upload URL for a specific route and parameters.
     *
     * @param string $routeName   Route name (e.g., 'mobile.disbursement.upload')
     * @param mixed  $parameters  Route parameters (token, entity_id, etc.)
     * @return string             Complete URL
     */
    public function buildMobileUrl(string $routeName, mixed $parameters = []): string
    {
        try {
            $path = route($routeName, $parameters, false);
            return $this->getBaseUrl() . $path;
        } catch (\Exception $e) {
            Log::error('Failed to build mobile URL', [
                'route' => $routeName,
                'params' => $parameters,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get token expiry in days for a specific entity type.
     *
     * @param string $entityType (disbursement, scholarship_record, profile, etc.)
     * @return int              Minutes until expiry
     */
    public function getTokenExpiryMinutes(string $entityType = ''): int
    {
        $db = $this->dbSettings();
        if ($entityType && ! empty($db['tokens'][$entityType])) {
            return (int) $db['tokens'][$entityType];
        }

        // Config file stores days — convert to minutes as fallback
        if ($entityType) {
            $days = config("mobileupload.tokens.expiry_by_type.{$entityType}")
                ?? config('mobileupload.tokens.expiry_days', 30);
            return (int) $days * 1440;
        }

        return (int) config('mobileupload.tokens.expiry_days', 30) * 1440;
    }

    /**
     * @deprecated Use getTokenExpiryMinutes() instead.
     */
    public function getTokenExpiryDays(string $entityType = ''): int
    {
        return (int) round($this->getTokenExpiryMinutes($entityType) / 1440);
    }

    /**
     * Get token configuration (length, validation rules, etc.).
     *
     * @return array
     */
    public function getTokenConfig(): array
    {
        return config('mobileupload.tokens', []);
    }

    /**
     * Get file upload configuration for a specific entity type.
     *
     * @param string|null $entityType
     * @return array              Upload config (max_size_kb, allowed_types, etc.)
     */
    public function getUploadConfig(string $entityType = null): array
    {
        if (!$entityType) {
            return config('mobileupload.uploads', []);
        }

        $cacheKey = self::CACHE_KEY_ENTITY_CONFIG . $entityType;

        return Cache::remember($cacheKey, 3600, function () use ($entityType) {
            // Check DB first
            $db = $this->dbSettings();
            if (! empty($db['uploads'][$entityType])) {
                return $db['uploads'][$entityType];
            }

            $config = config("mobileupload.uploads.validation_by_type.{$entityType}");

            if (!$config) {
                Log::warning('No upload config found for entity type', ['type' => $entityType]);
                return config('mobileupload.uploads.validation_by_type.disbursement', []);
            }

            return $config;
        });
    }

    /**
     * Get storage path for uploads of a specific type.
     *
     * @param string $entityType
     * @return string             Path (e.g., 'uploads/disbursements')
     */
    public function getStoragePath(string $entityType = null): string
    {
        if (!$entityType) {
            return config('mobileupload.uploads.storage_paths.base_path', 'uploads');
        }

        return config("mobileupload.uploads.storage_paths.by_type.{$entityType}") ??
            config('mobileupload.uploads.storage_paths.base_path', 'uploads') . "/{$entityType}";
    }

    /**
     * Validate if a file can be uploaded for a specific entity type.
     *
     * Returns validation error message if invalid, null if valid.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string                        $entityType
     * @return string|null                  Validation error or null
     */
    public function validateUploadFile(\Illuminate\Http\UploadedFile $file, string $entityType): ?string
    {
        $config = $this->getUploadConfig($entityType);
        $maxSizeKb = $config['max_size_kb'] ?? 25600;
        $allowedTypes = $config['allowed_types'] ?? [];

        // Check file size
        if ($file->getSize() > ($maxSizeKb * 1024)) {
            return "File exceeds maximum size of {$maxSizeKb}KB";
        }

        // Check file type
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $allowedTypes)) {
            $typesStr = implode(', ', $allowedTypes);
            return "File type '{$extension}' is not allowed. Allowed types: {$typesStr}";
        }

        return null;  // Valid
    }

    /**
     * Get image optimization settings.
     *
     * @return array Configuration for image compression and processing
     */
    public function getImageOptimizationConfig(): array
    {
        return config('mobileupload.image_optimization', []);
    }

    /**
     * Get QR code settings.
     *
     * @return array Configuration for QR code generation
     */
    public function getQrConfig(): array
    {
        return config('mobileupload.qr', []);
    }

    /**
     * Get security and rate limiting settings.
     *
     * @return array
     */
    public function getSecurityConfig(): array
    {
        return config('mobileupload.security', []);
    }

    /**
     * Check if file type is allowed for uploads.
     *
     * @param string $extension File extension (without dot)
     * @param string $entityType Optional entity type for specific validation
     * @return bool
     */
    public function isFileTypeAllowed(string $extension, string $entityType = null): bool
    {
        $extension = strtolower(ltrim($extension, '.'));

        if ($entityType) {
            $config = $this->getUploadConfig($entityType);
            return in_array($extension, $config['allowed_types'] ?? []);
        }

        $allowedTypes = config('mobileupload.uploads.allowed_types.all', []);
        return in_array($extension, $allowedTypes);
    }

    /**
     * Get maximum upload file size in KB for entity type.
     *
     * @param string $entityType
     * @return int                 Size in KB
     */
    public function getMaxUploadSizeKb(string $entityType = null): int
    {
        if (!$entityType) {
            return config('mobileupload.uploads.max_size_kb', 25600);
        }

        $config = $this->getUploadConfig($entityType);
        return $config['max_size_kb'] ?? config('mobileupload.uploads.max_size_kb', 25600);
    }

    /**
     * Get MIME type for file extension.
     *
     * @param string $extension (jpg, pdf, etc.)
     * @return string|null      MIME type (image/jpeg, application/pdf, etc.)
     */
    public function getMimeType(string $extension): ?string
    {
        $extension = strtolower(ltrim($extension, '.'));
        return config("mobileupload.uploads.mime_types.{$extension}");
    }

    /**
     * Get all configured entity types.
     *
     * @return array Entity type names
     */
    public function getEntityTypes(): array
    {
        return array_keys(config('mobileupload.uploads.validation_by_type', []));
    }

    /**
     * Check if image optimization is enabled.
     *
     * @return bool
     */
    public function isImageOptimizationEnabled(): bool
    {
        return config('mobileupload.image_optimization.enabled', true);
    }

    /**
     * Get JPEG quality setting for optimization.
     *
     * @return int Quality 1-100
     */
    public function getJpegQuality(): int
    {
        return config('mobileupload.image_optimization.jpeg_quality', 60);
    }

    /**
     * Check if debugging/detailed logging is enabled.
     *
     * @return bool
     */
    public function isDebugLoggingEnabled(): bool
    {
        return config('mobileupload.debug.enable_logging', false);
    }

    /**
     * Get current deployment environment.
     *
     * @return string (local, staging, production)
     */
    public function getCurrentEnvironment(): string
    {
        return config('mobileupload.deployment.current_env', env('APP_ENV', 'local'));
    }

    /**
     * Get environment-specific configuration.
     *
     * @param string $env
     * @return array
     */
    public function getEnvironmentConfig(string $env): array
    {
        return config("mobileupload.deployment.environments.{$env}", [
            'base_url' => config('app.url'),
            'port' => null,
            'scheme' => 'http',
            'use_lan_ip' => false,
        ]);
    }

    /**
     * Determine if LAN IP should be used for this environment.
     *
     * @param string $env
     * @return bool
     */
    private function shouldUseLanIp(string $env): bool
    {
        $lanDetectionEnabled = config('mobileupload.deployment.mobile_lan_detection.enabled', true);

        if (!$lanDetectionEnabled) {
            return false;  // Disabled globally
        }

        $envConfig = $this->getEnvironmentConfig($env);
        return $envConfig['use_lan_ip'] ?? false;
    }

    /**
     * Detect the server's LAN IP address for mobile access.
     *
     * Uses gethostbyname() first, then socket trick if available.
     *
     * @return string IP address or fallback to localhost
     */
    private function detectLanIp(): string
    {
        // Try hostname resolution first
        $hostname = gethostname();
        $ip = gethostbyname($hostname);

        if ($ip !== $hostname && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        // Socket trick: connect to a public IP to discover local interface
        try {
            $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_connect($sock, '8.8.8.8', 80);
            socket_getsockname($sock, $ip);
            socket_close($sock);

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        } catch (\Throwable $e) {
            Log::debug('Socket-based IP detection failed', ['error' => $e->getMessage()]);
        }

        // Fallback to localhost if detection fails
        $fallback = config('mobileupload.deployment.mobile_lan_detection.fallback_to_domain', true);
        if ($fallback) {
            return parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
        }

        return '127.0.0.1';
    }

    /**
     * Clear all caches for URL and config resolution.
     * Call this after updating environment settings.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_BASE_URL);
        Cache::forget(self::CACHE_KEY_DB_SETTINGS);

        // Clear entity config caches
        foreach ($this->getEntityTypes() as $entityType) {
            Cache::forget(self::CACHE_KEY_ENTITY_CONFIG . $entityType);
        }

        Log::info('Mobile upload configuration cache cleared');
    }

    /**
     * Get complete config array for debugging/inspection.
     *
     * @return array All mobile upload configuration
     */
    public function getAllConfig(): array
    {
        return config('mobileupload', []);
    }
}
