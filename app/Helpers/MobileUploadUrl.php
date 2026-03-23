<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class MobileUploadUrl
{
    /**
     * Resolve the base URL for mobile upload links.
     * Uses the server's LAN IP directly so mobile devices on the
     * same network can reach it without needing local DNS (e.g. app.scholars.local).
     * Result is cached for 5 minutes.
     */
    public static function resolveBaseUrl(): string
    {
        return Cache::remember('mobile_upload_base_url', 300, function () {
            $appUrl = config('app.url', 'http://localhost:8000');
            $scheme = parse_url($appUrl, PHP_URL_SCHEME) ?? 'http';
            $port   = parse_url($appUrl, PHP_URL_PORT);
            $suffix = $port ? ":{$port}" : '';

            $ip = self::getServerIp();
            return "{$scheme}://{$ip}{$suffix}";
        });
    }

    /**
     * Build an absolute mobile upload URL using the resolved base URL.
     * Uses route() with $absolute = false to get the path only,
     * then prepends the resolved base.
     *
     * @param string $routeName  Laravel route name
     * @param mixed  $parameters Route parameters (same as route() helper)
     */
    public static function build(string $routeName, mixed $parameters = []): string
    {
        $path = route($routeName, $parameters, false);
        return self::resolveBaseUrl() . $path;
    }

    /**
     * Detect the server's LAN IP address.
     */
    private static function getServerIp(): string
    {
        // gethostbyname() returns the hostname unchanged on failure
        $hostname = gethostname();
        $ip = gethostbyname($hostname);

        if ($ip !== $hostname && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        // Socket trick: open a UDP "connection" to a public IP to discover
        // which local interface would be used (no actual packet is sent).
        try {
            $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_connect($sock, '8.8.8.8', 80);
            socket_getsockname($sock, $ip);
            socket_close($sock);

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        } catch (\Throwable) {
            // Ignore — sockets extension not available
        }

        return '127.0.0.1';
    }
}
