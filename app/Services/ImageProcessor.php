<?php

declare(strict_types=1);

namespace Garage\Services;

use GdImage;

/**
 * Turns one uploaded photo into clean WebP variants.
 *
 * 1. Checks the real type with finfo (the extension and the browser's MIME are ignored).
 * 2. Decodes with GD and applies the EXIF orientation, if present.
 * 3. Re-encodes from raw pixels: GD never copies metadata, so EXIF, GPS location,
 *    camera serials and embedded thumbnails are all dropped.
 * 4. Writes lg/md/sm WebP (and a tiny JPEG for e-mails). The original is never stored;
 *    PHP deletes the temporary upload at the end of the request.
 */
final class ImageProcessor
{
    public const MAX_BYTES = 10 * 1024 * 1024;
    private const MAX_PIXELS = 30_000_000;
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];

    /** True when a slot actually carries a file. */
    public static function hasFile(?array $file): bool
    {
        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Validate an uploaded file without decoding it. Throws PhotoException.
     * Used to reject a whole form before any photo is written.
     */
    public static function validate(array $file): void
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if (in_array($error, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new PhotoException('photo.too_big');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
            throw new PhotoException('photo.upload_failed');
        }
        if ((int) $file['size'] > self::MAX_BYTES) {
            throw new PhotoException('photo.too_big');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        if (!in_array($mime, self::ALLOWED, true)) {
            throw new PhotoException('photo.bad_type');
        }

        $info = @getimagesize((string) $file['tmp_name']);
        if ($info === false || $info[0] < 1 || $info[1] < 1) {
            throw new PhotoException('photo.bad_type');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw new PhotoException('photo.too_many_pixels');
        }
    }

    /**
     * @return array{key: string, width: int, height: int, bytes: int}
     */
    public static function process(array $file, bool $emailThumb = false): array
    {
        self::validate($file);
        if (!function_exists('imagewebp')) {
            throw new \RuntimeException('GD was compiled without WebP support');
        }

        @ini_set('memory_limit', '512M');
        $tmp = (string) $file['tmp_name'];
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);

        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            'image/webp' => @imagecreatefromwebp($tmp),
        };
        if (!$image instanceof GdImage) {
            throw new PhotoException('photo.bad_type');
        }
        if ($mime === 'image/jpeg') {
            $image = self::applyOrientation($image, $tmp);
        }
        if (!imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        $key = PhotoStorage::newKey();
        $dir = dirname(PhotoStorage::path($key, 'lg'));
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create upload directory');
        }

        $bytes = 0;
        $lgSize = [0, 0];
        try {
            foreach (PhotoStorage::SIZES as $size => [$maxSide, $quality]) {
                $variant = self::fit($image, $maxSide, true);
                $path = PhotoStorage::path($key, $size);
                if (!imagewebp($variant, $path, $quality)) {
                    throw new \RuntimeException('imagewebp failed');
                }
                if ($size === 'lg') {
                    $lgSize = [imagesx($variant), imagesy($variant)];
                }
                $bytes += (int) filesize($path);
            }

            if ($emailThumb) {
                [$maxSide, $quality] = PhotoStorage::EMAIL;
                $thumb = self::fit($image, $maxSide, false);
                $path = PhotoStorage::path($key, 'em');
                if (!imagejpeg($thumb, $path, $quality)) {
                    throw new \RuntimeException('imagejpeg failed');
                }
                $bytes += (int) filesize($path);
            }
        } catch (\Throwable $e) {
            PhotoStorage::delete($key);
            throw $e;
        }

        return ['key' => $key, 'width' => $lgSize[0], 'height' => $lgSize[1], 'bytes' => $bytes];
    }

    /** Scale so the longest side is at most $maxSide (never upscale). */
    private static function fit(GdImage $src, int $maxSide, bool $keepAlpha): GdImage
    {
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, $maxSide / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        if ($keepAlpha) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        } else {
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    /** Rotate/flip according to the EXIF Orientation tag (only that tag is read). */
    private static function applyOrientation(GdImage $image, string $path): GdImage
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($path, 'IFD0');
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        return match ($orientation) {
            2 => self::flip($image, IMG_FLIP_HORIZONTAL),
            3 => imagerotate($image, 180, 0),
            4 => self::flip($image, IMG_FLIP_VERTICAL),
            5 => self::flip(imagerotate($image, -90, 0), IMG_FLIP_HORIZONTAL),
            6 => imagerotate($image, -90, 0),
            7 => self::flip(imagerotate($image, 90, 0), IMG_FLIP_HORIZONTAL),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }

    private static function flip(GdImage $image, int $mode): GdImage
    {
        imageflip($image, $mode);

        return $image;
    }
}
