<?php

namespace App\Traits;

/**
 * Used by controllers that generate PDFs using Browsershot
 */
trait ManagesChromeForPdf
{
    /**
     * Get the Chrome executable path with fallback logic.
     * If none found, returns null to let Browsershot use auto-detection.
     *
     * Strategy:
     * - Try primary path from config
     * - Try fallback paths from config
     * - Auto-detection via Browsershot if no path found
     */
    protected function getChromePath(): ?string
    {
        $primaryPath = config('scholarship.browsershot.chrome_path');

        if ($primaryPath && file_exists($primaryPath)) {
            return $primaryPath;
        }

        $fallbackPaths = config('scholarship.browsershot.fallback_paths', []);
        foreach ($fallbackPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Return null to let Browsershot use auto-detection and Puppeteer to download Chrome if needed
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
