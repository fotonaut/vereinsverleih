<?php

namespace App\Services;

use App\Models\LoanReturnPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReturnPhotoStorage
{
    public const MAX_SIDE = 1600;

    public const MAX_PHOTOS = 4;

    /**
     * Speichert ein Foto privat. Es wird auf max. 1600 px verkleinert und als JPEG neu kodiert –
     * das entfernt EXIF-Daten (z. B. GPS-Standort) und spart Platz. Lässt sich das Bild nicht
     * verarbeiten (z. B. WebP ohne GD-Unterstützung), wird das Original abgelegt.
     *
     * @return string relativer Pfad auf dem privaten Datenträger
     */
    public function store(UploadedFile $file): string
    {
        $disk = Storage::disk(LoanReturnPhoto::DISK);
        $processed = $this->process($file);

        if ($processed === null) {
            return $disk->putFile('return-photos', $file);
        }

        $path = 'return-photos/'.Str::random(40).'.jpg';
        $disk->put($path, $processed);

        return $path;
    }

    private function process(UploadedFile $file): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $img = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if ($img === false) {
            return null;
        }

        // Ausrichtung laut EXIF anwenden, bevor die Metadaten wegfallen
        if ($file->getMimeType() === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = @exif_read_data($file->getRealPath())['Orientation'] ?? 1;
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle !== 0 && ($rotated = imagerotate($img, $angle, 0)) !== false) {
                $img = $rotated;
            }
        }

        [$w, $h] = [imagesx($img), imagesy($img)];
        if (max($w, $h) > self::MAX_SIDE) {
            $scale = self::MAX_SIDE / max($w, $h);
            $img = imagescale($img, (int) round($w * $scale), (int) round($h * $scale));
            [$w, $h] = [imagesx($img), imagesy($img)];
        }

        // Transparenz (PNG) auf weißen Grund setzen
        $canvas = imagecreatetruecolor($w, $h);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $img, 0, 0, 0, 0, $w, $h);

        ob_start();
        imagejpeg($canvas, null, 82);

        return (string) ob_get_clean();
    }
}
