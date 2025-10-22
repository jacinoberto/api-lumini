<?php
namespace App\Application\Services;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    public function handleBase64Upload(?string $base64Image, string $folder): ?string
    {
        if (empty($base64Image) || !str_contains($base64Image, ';base64,')) {
            return null;
        }

        $image_parts = explode(";base64,", $base64Image);
        $image_type_aux = explode("image/", $image_parts[0]);
        $image_type = $image_type_aux[1];
        $image_base64 = base64_decode($image_parts[1]);
        $fileName = Str::uuid() . '.' . $image_type;
        $path = $folder . '/' . $fileName;

        Storage::disk('public')->put($path, $image_base64);
        return Storage::disk('public')->url($path);
    }
}
