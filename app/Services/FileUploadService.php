<?php

namespace App\Services;

use App\Models\MobileUploadSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Process uploaded file (image or PDF)
     * 
     * Handles:
     * - Image: EXIF orientation fixing, portrait enforcement, resize, 40% JPEG compression
     * - PDF: gzip compression level 9
     * 
     * @param UploadedFile $file The uploaded file
     * @param string $type 'image' or 'pdf' (auto-detected from mime type if not specified)
     * @return FileProcessingResult
     * @throws \Exception
     */
    public function processUpload(UploadedFile $file, string $type = ''): FileProcessingResult
    {
        $mimeType = $file->getMimeType();

        // Auto-detect type if not specified
        if (empty($type)) {
            $type = str_starts_with($mimeType, 'image/') ? 'image' : 'pdf';
        }

        if ($type === 'image') {
            return $this->processImage($file);
        } elseif ($type === 'pdf') {
            return $this->processPdf($file);
        }

        return new FileProcessingResult(
            success: false,
            error: "Unsupported file type: $type",
        );
    }

    /**
     * Process image file with compression and orientation fix.
     *
     * PNG and GIF are preserved in their original format (lossless).
     * JPEG and other formats are compressed using the configured quality.
     * Quality, maximum dimensions, and format preferences are read from
     * MobileUploadSetting (admin settings page).
     */
    private function processImage(UploadedFile $file): FileProcessingResult
    {
        try {
            $fileContent = file_get_contents($file->getRealPath());

            // Read image settings from admin configuration (DB-first, config fallback)
            $imgSettings    = MobileUploadSetting::getCurrent()['image'];
            $jpegQuality    = $imgSettings['jpeg_quality']             ?? 60;
            $maxWidth       = $imgSettings['max_width']                ?? 1920;
            $maxHeight      = $imgSettings['max_height']               ?? 1920;
            $autoRotate     = $imgSettings['auto_rotate']              ?? true;
            $preserveFormat = $imgSettings['preserve_original_format'] ?? true;

            // Determine source format
            $sourceMime = $file->getMimeType();
            $isPng      = $sourceMime === 'image/png';
            $isGif      = $sourceMime === 'image/gif';
            $keepFormat = $preserveFormat && ($isPng || $isGif);

            // Step 1: Load image
            $image = @imagecreatefromstring($fileContent);

            if ($image === false) {
                return new FileProcessingResult(
                    success: false,
                    error: 'Failed to load image file',
                );
            }

            // Step 2: Fix EXIF orientation (only relevant for JPEG/camera photos)
            if ($autoRotate && !$isPng && !$isGif) {
                $image = $this->fixImageOrientation($image, $file->getRealPath());
            }

            // Step 3: Enforce portrait orientation for JPEG/camera photos only
            $width  = imagesx($image);
            $height = imagesy($image);

            if (!$keepFormat && $width > $height) {
                $image = imagerotate($image, -90, 0);
                [$width, $height] = [$height, $width];
            }

            // Step 4: Resize if exceeds max dimensions (maintain aspect ratio)
            if ($width > $maxWidth || $height > $maxHeight) {
                $ratio     = min($maxWidth / $width, $maxHeight / $height);
                $newWidth  = (int) round($width  * $ratio);
                $newHeight = (int) round($height * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);

                if ($isPng && $keepFormat) {
                    // Preserve alpha channel transparency for PNG
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                    $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                    imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
                }

                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            // Step 5: Encode to appropriate format
            ob_start();
            if ($isPng && $keepFormat) {
                imagepng($image, null, 9);   // Lossless deflate — level 9 = maximum compression
                $ext        = 'png';
                $outputMime = 'image/png';
            } elseif ($isGif && $keepFormat) {
                imagegif($image);            // GIF — no quality parameter
                $ext        = 'gif';
                $outputMime = 'image/gif';
            } else {
                imagejpeg($image, null, $jpegQuality);
                $ext        = 'jpg';
                $outputMime = 'image/jpeg';
            }
            $compressed = ob_get_clean();
            imagedestroy($image);

            // Step 6: Calculate processing metrics
            $originalSize     = $file->getSize();
            $compressedSize   = strlen($compressed);
            $compressionRatio = $originalSize > 0
                ? round(($originalSize - $compressedSize) / $originalSize * 100, 2)
                : 0;

            // Step 7: Generate unique filename with correct extension
            $filename = 'image_' . Str::random(12) . '_' . now()->timestamp . '.' . $ext;

            return new FileProcessingResult(
                success: true,
                filename: $filename,
                content: $compressed,
                originalSize: $originalSize,
                compressedSize: $compressedSize,
                compressionRatio: $compressionRatio,
                mimeType: $outputMime,
            );
        } catch (\Exception $e) {
            return new FileProcessingResult(
                success: false,
                error: 'Image processing failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Process PDF file with gzip compression
     */
    private function processPdf(UploadedFile $file): FileProcessingResult
    {
        try {
            // Read PDF content
            $content = file_get_contents($file->getRealPath());

            if ($content === false) {
                return new FileProcessingResult(
                    success: false,
                    error: 'Failed to read PDF file',
                );
            }

            // Compress with gzip level 9 (maximum compression)
            $compressed = gzencode($content, 9);

            $originalSize = strlen($content);
            $compressedSize = strlen($compressed);
            $compressionRatio = $originalSize > 0
                ? round(($originalSize - $compressedSize) / $originalSize * 100, 2)
                : 0;

            // Generate unique filename
            $filename = 'document_' . Str::random(12) . '_' . now()->timestamp . '.pdf.gz';

            return new FileProcessingResult(
                success: true,
                filename: $filename,
                content: $compressed,
                originalSize: $originalSize,
                compressedSize: $compressedSize,
                compressionRatio: $compressionRatio,
                mimeType: 'application/pdf',
            );
        } catch (\Exception $e) {
            return new FileProcessingResult(
                success: false,
                error: 'PDF processing failed: ' . $e->getMessage(),
            );
        }
    }

    /**
     * Fix image rotation based on EXIF Orientation tag
     * 
     * Mobile phones store orientation in EXIF data, not actual pixel rotation
     * This function rotates/flips the image to match the intended orientation.
     */
    private function fixImageOrientation($image, string $filePath)
    {
        try {
            if (!function_exists('exif_read_data')) {
                return $image;  // EXIF not available
            }

            $exif = @exif_read_data($filePath);

            if (!$exif || !isset($exif['Orientation'])) {
                return $image;  // No orientation data
            }

            $orientation = $exif['Orientation'];

            return match ($orientation) {
                2 => $this->imageFlipHorizontal($image),
                3 => imagerotate($image, 180, 0),
                4 => $this->imageFlipVertical($image),
                5 => $this->imageFlipHorizontal(imagerotate($image, 90, 0)),
                6 => imagerotate($image, -90, 0),  // 90 CW
                7 => $this->imageFlipHorizontal(imagerotate($image, -90, 0)),
                8 => imagerotate($image, 90, 0),   // 90 CCW
                default => $image,
            };
        } catch (\Exception $e) {
            // If EXIF reading fails, return image unchanged
            return $image;
        }
    }

    /**
     * Flip image horizontally (GD doesn't have built-in flip)
     */
    private function imageFlipHorizontal($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $newImage = imagecreatetruecolor($width, $height);

        for ($x = 0; $x < $width; $x++) {
            imagecopy($newImage, $image, $width - $x - 1, 0, $x, 0, 1, $height);
        }

        imagedestroy($image);
        return $newImage;
    }

    /**
     * Flip image vertically (GD doesn't have built-in flip)
     */
    private function imageFlipVertical($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $newImage = imagecreatetruecolor($width, $height);

        for ($y = 0; $y < $height; $y++) {
            imagecopy($newImage, $image, 0, $height - $y - 1, 0, $y, $width, 1);
        }

        imagedestroy($image);
        return $newImage;
    }
}

/**
 * Data Transfer Object for file processing results
 * 
 * Encapsulates the result of FileUploadService processing
 */
class FileProcessingResult
{
    public readonly string $extension;

    public function __construct(
        public bool $success,
        public string $filename = '',
        public mixed $content = null,
        public int $originalSize = 0,
        public int $compressedSize = 0,
        public float $compressionRatio = 0.0,
        public string $mimeType = '',
        public string $error = '',
    ) {
        // Extract extension from filename (e.g., "image.jpg" -> "jpg")
        $this->extension = pathinfo($this->filename, PATHINFO_EXTENSION) ?: 'bin';
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getErrorMessage(): string
    {
        return $this->error;
    }

    public function getCompressionRatio(): string
    {
        return $this->compressionRatio . '%';
    }
}
