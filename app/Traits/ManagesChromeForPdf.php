<?php

namespace App\Traits;

/**
 * Trait for managing Chrome/Chromium executable path detection
 * Used by controllers that generate PDFs using Browsershot
 */
trait ManagesChromeForPdf
{
    /**
     * Get the Chrome executable path with fallback logic
     * Tries the configured path first, then fallback paths
     * If none found, returns null to let Browsershot use auto-detection
     * 
     * This method handles:
     * - Environment variable override (CHROME_PATH)
     * - Configured primary path
     * - Fallback paths with glob pattern matching
     * - Auto-detection via Browsershot if no path found
     * 
     * @return string|null
     */
    protected function getChromePath()
    {
        $primaryPath = config('scholarship.browsershot.chrome_path');

        // Try primary path first if explicitly configured
        if ($primaryPath && file_exists($primaryPath)) {
            return $primaryPath;
        }

        // Try fallback paths
        $fallbackPaths = config('scholarship.browsershot.fallback_paths', []);
        foreach ($fallbackPaths as $path) {
            // Expand directory paths to find any Chrome installation
            if (is_dir($path)) {
                $files = glob($path . '/**/chrome.exe', \GLOB_RECURSIVE);
                if (!empty($files) && file_exists($files[0])) {
                    return $files[0];
                }
            } elseif (file_exists($path)) {
                return $path;
            }
        }

        // Return null to let Browsershot use auto-detection
        // This is safer than throwing an exception as Browsershot can find Chrome automatically
        return null;
    }

    /**
     * Configure a Browsershot instance with Chrome path if available
     * 
     * @param \Spatie\Browsershot\Browsershot $browsershot
     * @return \Spatie\Browsershot\Browsershot
     */
    protected function configureChromePath($browsershot)
    {
        $chromePath = $this->getChromePath();
        if ($chromePath) {
            $browsershot->setChromePath($chromePath);
        }
        return $browsershot;
    }
}
