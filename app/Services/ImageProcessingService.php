<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ImageProcessingService
{
    private const MAX_BYTES = 2 * 1024 * 1024;
    private const ANALYSIS_MAX_SIZE = 1000;

    /**
     * Processes an image: centers by visual mass on a square canvas and converts to WebP.
     * Returns the temp file path, or null if the file cannot be processed as a raster image.
     */
    public function process(UploadedFile $file): ?string
    {
        $content = file_get_contents($file->getRealPath());
        $img = @imagecreatefromstring($content);

        if (!$img) {
            return null;
        }

        $rgba = $this->toRgba($img);
        $width = imagesx($rgba);
        $height = imagesy($rgba);

        [$minX, $maxX, $minY, $maxY, $centerX, $centerY] = $this->analyzeAlpha($rgba, $width, $height);

        $canvas = $this->buildSquareCanvas($rgba, $minX, $maxX, $minY, $maxY, $centerX, $centerY);
        imagedestroy($rgba);

        $tmpPath = $this->saveWebpUnder2mb($canvas);
        imagedestroy($canvas);

        return $tmpPath;
    }

    private function toRgba(\GdImage $src): \GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);

        $dst = imagecreatetruecolor($w, $h);
        imagesavealpha($dst, true);
        imagealphablending($dst, false);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);
        imagedestroy($src);

        return $dst;
    }

    /**
     * Returns [minX, maxX, minY, maxY, centerX, centerY] of non-transparent pixels.
     * Uses a downsampled version for large images to keep the loop fast.
     */
    private function analyzeAlpha(\GdImage $img, int $width, int $height): array
    {
        $scale = 1.0;
        $aw = $width;
        $ah = $height;
        $analysis = $img;

        if ($width > self::ANALYSIS_MAX_SIZE || $height > self::ANALYSIS_MAX_SIZE) {
            $scale = self::ANALYSIS_MAX_SIZE / max($width, $height);
            $aw = (int) ($width * $scale);
            $ah = (int) ($height * $scale);
            $analysis = imagescale($img, $aw, $ah, IMG_BILINEAR_FIXED);
        }

        $minX = PHP_INT_MAX;
        $maxX = 0;
        $minY = PHP_INT_MAX;
        $maxY = 0;
        $sumX = 0;
        $sumY = 0;
        $count = 0;

        for ($y = 0; $y < $ah; $y++) {
            for ($x = 0; $x < $aw; $x++) {
                $color = imagecolorat($analysis, $x, $y);
                // GD alpha: 0 = fully opaque, 127 = fully transparent
                $alpha = ($color >> 24) & 0x7F;
                if ($alpha < 127) {
                    if ($x < $minX) $minX = $x;
                    if ($x > $maxX) $maxX = $x;
                    if ($y < $minY) $minY = $y;
                    if ($y > $maxY) $maxY = $y;
                    $sumX += $x;
                    $sumY += $y;
                    $count++;
                }
            }
        }

        if ($scale < 1.0) {
            imagedestroy($analysis);
        }

        if ($count === 0) {
            return [0, $width - 1, 0, $height - 1, intdiv($width, 2), intdiv($height, 2)];
        }

        $cx = (int) ($sumX / $count);
        $cy = (int) ($sumY / $count);

        if ($scale < 1.0) {
            $inv = 1 / $scale;
            $minX = (int) ($minX * $inv);
            $maxX = min((int) ($maxX * $inv), $width - 1);
            $minY = (int) ($minY * $inv);
            $maxY = min((int) ($maxY * $inv), $height - 1);
            $cx   = (int) ($cx * $inv);
            $cy   = (int) ($cy * $inv);
        }

        return [$minX, $maxX, $minY, $maxY, $cx, $cy];
    }

    private function buildSquareCanvas(
        \GdImage $img,
        int $minX, int $maxX,
        int $minY, int $maxY,
        int $centerX, int $centerY
    ): \GdImage {
        $cropW = $maxX - $minX + 1;
        $cropH = $maxY - $minY + 1;

        $cropped = imagecreatetruecolor($cropW, $cropH);
        imagesavealpha($cropped, true);
        imagealphablending($cropped, false);
        imagefill($cropped, 0, 0, imagecolorallocatealpha($cropped, 0, 0, 0, 127));
        imagecopy($cropped, $img, 0, 0, $minX, $minY, $cropW, $cropH);

        $size = max($cropW, $cropH);
        $canvas = imagecreatetruecolor($size, $size);
        imagesavealpha($canvas, true);
        imagealphablending($canvas, false);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));

        // Offset so the visual center of mass lands on the canvas center
        $dstX = intdiv($size, 2) - ($centerX - $minX);
        $dstY = intdiv($size, 2) - ($centerY - $minY);

        // Clip negative offsets into source coordinates
        $srcX = 0;
        $srcY = 0;
        $copyW = $cropW;
        $copyH = $cropH;

        if ($dstX < 0) { $srcX = -$dstX; $copyW -= $srcX; $dstX = 0; }
        if ($dstY < 0) { $srcY = -$dstY; $copyH -= $srcY; $dstY = 0; }

        if ($copyW > 0 && $copyH > 0) {
            imagecopy($canvas, $cropped, $dstX, $dstY, $srcX, $srcY, $copyW, $copyH);
        }

        imagedestroy($cropped);

        return $canvas;
    }

    private function saveWebpUnder2mb(\GdImage $img): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'imgproc_') . '.webp';
        $origW = imagesx($img);
        $origH = imagesy($img);

        $quality = 90;
        imagewebp($img, $tmpPath, $quality);

        while (filesize($tmpPath) > self::MAX_BYTES && $quality > 10) {
            $quality -= 10;
            imagewebp($img, $tmpPath, $quality);
        }

        if (filesize($tmpPath) > self::MAX_BYTES) {
            $scale = 0.8;
            while (filesize($tmpPath) > self::MAX_BYTES && $scale > 0.1) {
                $resized = imagescale($img, (int) ($origW * $scale), (int) ($origH * $scale), IMG_BILINEAR_FIXED);
                imagewebp($resized, $tmpPath, $quality);
                imagedestroy($resized);
                $scale -= 0.1;
            }
        }

        return $tmpPath;
    }
}
