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
            // Check if path is a direct file
            if (file_exists($path) && is_file($path)) {
                return $path;
            }

            // Search recursively in directory for Chrome executable
            if (is_dir($path)) {
                $chromePath = $this->findChromeInDirectory($path);
                if ($chromePath && file_exists($chromePath)) {
                    return $chromePath;
                }
            }
        }

        // Return null to let Browsershot use auto-detection and Puppeteer to download Chrome if needed
        // This is safer than throwing an exception as Browsershot can find Chrome automatically
        return null;
    }

    /**
     * Recursively search for chrome.exe in a directory
     * Compatible with Windows and Unix-like systems
     *
     * @param string $directory
     * @return string|null
     */
    private function findChromeInDirectory($directory)
    {
        try {
            $iterator = new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS);
            $recursiveIterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);

            foreach ($recursiveIterator as $file) {
                if ($file->getFilename() === 'chrome.exe' && $file->isFile()) {
                    return $file->getRealPath();
                }
            }
        } catch (\Exception $e) {
            // If directory cannot be read, silently continue
        }

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
