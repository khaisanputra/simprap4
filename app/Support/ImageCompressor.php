<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Auto kompres foto untuk Absen Masuk, Absen Pulang, dan Laporan.
 *
 * Alur: Validasi -> Resize (max 1600x1600, keep aspect, no upscale) -> Compress (quality 80%) -> Simpan JPEG/WEBP
 * Hanya file hasil kompresi yang disimpan, original tidak disimpan.
 * Mendukung JPG, JPEG, PNG, WEBP input. Output JPEG untuk JPEG/PNG, WEBP untuk WEBP input (efisien, ~100-500KB) namun tetap kompatibel.
 * Batas awal 10 MB, target hasil 100-500KB tanpa merusak kualitas verifikasi.
 */
class ImageCompressor
{
    public const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB
    public const MAX_DIMENSION = 1600; // 1600x1600 px
    public const QUALITY = 80; // JPEG quality 80%
    public const ALLOWED_MIMES = ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp'];
    public const ALLOWED_EXTS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Kompres dan simpan foto.
     *
     * @param UploadedFile $file      File upload dari request
     * @param string       $directory Direktori tujuan di disk public (mis: absen/foto, absen/foto-pulang, laporan/foto)
     * @param int          $maxDimension Maksimal dimensi panjang/lebar (default 1600)
     * @param int          $quality      Kualitas JPEG 0-100 (default 80)
     * @return string Path relatif yang tersimpan (untuk disimpan ke DB)
     *
     * @throws \RuntimeException Jika validasi gagal atau proses GD gagal
     */
    public static function compressAndStore(UploadedFile $file, string $directory, int $maxDimension = self::MAX_DIMENSION, int $quality = self::QUALITY): string
    {
        // ---- 1. Validasi file & ukuran awal 10 MB ----
        if (!$file->isValid()) {
            throw new \RuntimeException('File foto tidak valid atau gagal diupload.');
        }

        $size = $file->getSize();
        if ($size !== null && $size > self::MAX_SIZE_BYTES) {
            throw new \RuntimeException('Ukuran foto terlalu besar. Maksimal ukuran foto adalah 10 MB.');
        }

        // Validasi getSize juga via error jika file melebihi post_max_size (getSize null) -> tetap cek via validation max:10240
        // ---- 2. Validasi MIME type & ekstensi (jangan hanya percaya ekstensi) ----
        $mime = strtolower($file->getMimeType() ?? $file->getClientMimeType() ?? '');
        $ext = strtolower($file->getClientOriginalExtension());
        // Fallback mime detection via finfo jika mime kosong atau generik
        if (empty($mime) || $mime === 'application/octet-stream') {
            $finfoMime = @mime_content_type($file->getPathname());
            if ($finfoMime) {
                $mime = strtolower($finfoMime);
            }
        }

        $isMimeAllowed = in_array($mime, self::ALLOWED_MIMES, true);
        $isExtAllowed = in_array($ext, self::ALLOWED_EXTS, true);

        // Harus salah satu allowed, dan jika mime terdeteksi harus image/*
        if (!$isMimeAllowed && !$isExtAllowed) {
            throw new \RuntimeException('Format foto harus JPG, JPEG, PNG, atau WEBP.');
        }
        // Jika mime terdeteksi tapi bukan image, tolak (cegah spoofing)
        if (!empty($mime) && !str_starts_with($mime, 'image/')) {
            throw new \RuntimeException('File harus berupa gambar yang valid.');
        }

        // ---- 3. Validasi file benar-benar image (getimagesize) ----
        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo === false) {
            throw new \RuntimeException('File foto harus berupa gambar yang valid.');
        }
        // Pastikan mime dari getimagesize juga image
        if (!isset($imageInfo['mime']) || !str_starts_with(strtolower($imageInfo['mime']), 'image/')) {
            throw new \RuntimeException('File foto harus berupa gambar yang valid.');
        }

        // ---- 4. Load image resource via GD ----
        $src = null;
        $tempPath = null;

        try {
            // Coba load dengan imagecreatefromstring (universal, menangani JPEG/PNG/WEBP jika GD support)
            $data = @file_get_contents($file->getPathname());
            if ($data === false) {
                throw new \RuntimeException('Gagal membaca file foto.');
            }

            $src = @imagecreatefromstring($data);
            // Fallback spesifik per format jika imagecreatefromstring gagal (misal webp tidak support)
            if ($src === false) {
                Log::warning('imagecreatefromstring gagal, coba fallback per MIME', ['mime' => $mime, 'ext' => $ext]);
                $src = self::createFromFileByMime($file->getPathname(), $mime, $ext);
            }

            if ($src === false || $src === null) {
                Log::warning('Gagal memproses foto: imagecreatefromstring dan fallback gagal', ['mime' => $mime, 'ext' => $ext]);
                throw new \RuntimeException('Foto tidak dapat dibaca. Pastikan file adalah gambar JPG, PNG, atau WEBP yang valid dan tidak rusak. Silakan ambil foto ulang.');
            }

            // ---- 4a. Handle EXIF orientation untuk JPEG (foto HP sering rotated) ----
            if (function_exists('exif_read_data') && in_array($mime, ['image/jpeg', 'image/jpg', 'image/pjpeg'], true)) {
                try {
                    $exif = @exif_read_data($file->getPathname());
                    if (!empty($exif['Orientation'])) {
                        $src = self::applyExifOrientation($src, (int) $exif['Orientation']);
                    }
                } catch (\Throwable $e) {
                    // Abaikan error EXIF, lanjutkan tanpa rotasi
                    Log::warning('Gagal membaca EXIF orientation', ['msg' => $e->getMessage()]);
                }
            }

            $width = imagesx($src);
            $height = imagesy($src);

            if ($width === false || $height === false || $width <= 0 || $height <= 0) {
                Log::warning('Dimensi gambar tidak valid', ['w' => $width, 'h' => $height, 'mime' => $mime]);
                throw new \RuntimeException('Foto tidak valid. Dimensi gambar tidak terbaca. Silakan ambil foto ulang.');
            }

            // ---- 5. Resize jika resolusi terlalu besar (max 1600x1600, keep aspect, no upscale) ----
            $newW = $width;
            $newH = $height;

            if ($width > $maxDimension || $height > $maxDimension) {
                $scale = min($maxDimension / $width, $maxDimension / $height);
                $newW = (int) round($width * $scale);
                $newH = (int) round($height * $scale);
                // Jaga minimal 1px
                $newW = max(1, $newW);
                $newH = max(1, $newH);

                $dst = @imagecreatetruecolor($newW, $newH);
                if ($dst === false) {
                    Log::error('imagecreatetruecolor gagal (memory)', ['newW' => $newW, 'newH' => $newH]);
                    throw new \RuntimeException('Foto terlalu besar atau memori server tidak cukup. Silakan coba lagi dengan foto beresolusi lebih kecil.');
                }

                // Isi background putih untuk JPEG (tangani PNG/WebP transparan)
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);

                // Untuk kualitas lebih baik, set blending
                imagealphablending($dst, true);

                $resampled = @imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
                if (!$resampled) {
                    @imagedestroy($dst);
                    Log::error('imagecopyresampled gagal', ['newW' => $newW, 'newH' => $newH, 'w' => $width, 'h' => $height]);
                    throw new \RuntimeException('Gagal memproses foto (resize). Silakan coba lagi.');
                }

                imagedestroy($src);
                $src = $dst;
            }

            // ---- 6. Compress & Simpan ke temp file JPEG/WEBP quality 80% ----
            // 1 upload -> 1 resize -> 1 compression -> 1 save (sesuai spec optimasi)
            // Output: WEBP jika input WEBP (lebih efisien), JPEG untuk lainnya
            $inputIsWebp = str_contains($mime, 'webp') || $ext === 'webp';
            $outputIsWebp = $inputIsWebp && function_exists('imagewebp');
            $outputExt = $outputIsWebp ? 'webp' : 'jpg';

            $tempPath = tempnam(sys_get_temp_dir(), 'cmp_');
            if ($tempPath === false) {
                throw new \RuntimeException('Gagal membuat file sementara.');
            }
            $tempOut = $tempPath . '.' . $outputExt;
            @unlink($tempPath);
            $tempPath = $tempOut;

            if ($outputIsWebp) {
                $saved = @imagewebp($src, $tempPath, $quality);
            } else {
                $saved = @imagejpeg($src, $tempPath, $quality);
            }
            if (!$saved) {
                Log::error('Gagal kompres foto ke temp file', ['ext' => $outputExt, 'quality' => $quality]);
                throw new \RuntimeException('Gagal kompres foto di server. Silakan coba lagi.');
            }

            // Bersihkan resource GD
            imagedestroy($src);
            $src = null;

            // ---- 7. Simpan hasil kompresi ke storage Laravel (disk public) ----
            $filename = Str::random(40) . '.' . $outputExt;
            $directory = trim($directory, '/');
            $storedPath = $directory . '/' . $filename;

            // Pastikan direktori ada (Storage::put akan otomatis buat, tapi pastikan disk ok)
            $contents = @file_get_contents($tempPath);
            if ($contents === false) {
                Log::error('Gagal membaca hasil kompresi dari temp', ['temp' => $tempPath]);
                throw new \RuntimeException('Gagal membaca hasil kompresi foto di server.');
            }

            // Validasi hasil tidak kosong dan ukuran wajar
            if (strlen($contents) === 0) {
                Log::error('Hasil kompresi kosong', ['temp' => $tempPath]);
                throw new \RuntimeException('Hasil kompresi foto kosong di server. Silakan coba lagi.');
            }

            $stored = Storage::disk('public')->put($storedPath, $contents);
            if (!$stored) {
                Log::error('Gagal menyimpan foto terkompresi ke storage', ['path' => $storedPath]);
                throw new \RuntimeException('Gagal menyimpan foto ke penyimpanan server. Periksa izin folder storage.');
            }

            // Hapus temp file
            @unlink($tempPath);
            $tempPath = null;

            // Kembalikan path relatif untuk disimpan ke DB (mis: absen/foto/xxx.jpg)
            return $storedPath;

        } catch (\RuntimeException $e) {
            // Bersihkan resource & temp jika ada error
            if ($src !== null && (is_resource($src) || $src instanceof \GdImage)) {
                @imagedestroy($src);
            }
            if ($tempPath !== null && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            throw $e;
        } catch (\Throwable $e) {
            if ($src !== null && (is_resource($src) || $src instanceof \GdImage)) {
                @imagedestroy($src);
            }
            if ($tempPath !== null && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            Log::error('ImageCompressor unexpected error', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw new \RuntimeException('Gagal memproses foto di server. Silakan coba lagi dengan foto yang lebih kecil.');
        }
    }

    /**
     * Fallback create image dari file berdasarkan mime/ext jika imagecreatefromstring gagal.
     */
    private static function createFromFileByMime(string $path, string $mime, string $ext)
    {
        $mime = strtolower($mime);
        $ext = strtolower($ext);

        // Prioritaskan mime
        if (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') || $ext === 'jpg' || $ext === 'jpeg') {
            if (function_exists('imagecreatefromjpeg')) {
                return @imagecreatefromjpeg($path);
            }
        }
        if (str_contains($mime, 'png') || $ext === 'png') {
            if (function_exists('imagecreatefrompng')) {
                return @imagecreatefrompng($path);
            }
        }
        if (str_contains($mime, 'webp') || $ext === 'webp') {
            if (function_exists('imagecreatefromwebp')) {
                return @imagecreatefromwebp($path);
            }
        }
        return false;
    }

    /**
     * Terapkan EXIF orientation ke resource GD.
     */
    private static function applyExifOrientation($image, int $orientation)
    {
        // Orientation values: 1=normal, 3=180, 6=90 CW, 8=90 CCW
        // GD rotate berlawanan arah jarum jam, jadi perlu sesuaikan
        switch ($orientation) {
            case 3:
                $rotated = imagerotate($image, 180, 0);
                if ($rotated !== false) {
                    imagedestroy($image);
                    return $rotated;
                }
                break;
            case 6:
                $rotated = imagerotate($image, -90, 0);
                if ($rotated !== false) {
                    imagedestroy($image);
                    return $rotated;
                }
                break;
            case 8:
                $rotated = imagerotate($image, 90, 0);
                if ($rotated !== false) {
                    imagedestroy($image);
                    return $rotated;
                }
                break;
            // 2,4,5,7 adalah flip (mirror) - jarang dari HP, abaikan untuk kesederhanaan
            default:
                break;
        }
        return $image;
    }

    /**
     * Helper untuk mendapatkan pesan error ukuran 10MB yang konsisten.
     */
    public static function maxSizeMessage(): string
    {
        return 'Ukuran foto terlalu besar. Maksimal ukuran foto adalah 10 MB.';
    }
}
