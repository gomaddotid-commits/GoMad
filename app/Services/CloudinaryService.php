<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryService
{
    public function upload(UploadedFile $file, string $folder = 'gomad'): array
    {
        $result = Cloudinary::upload($file->getRealPath(), [
            'folder' => $folder,
            'resource_type' => 'auto',
        ]);

        return [
            'public_id' => $result->getPublicId(),
            'url' => $result->getSecurePath(),
            'width' => $result->getWidth(),
            'height' => $result->getHeight(),
        ];
    }

    public function delete(string $publicId): bool
    {
        $result = Cloudinary::destroy($publicId);
        return $result === 'ok';
    }
}
