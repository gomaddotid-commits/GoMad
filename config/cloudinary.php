<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Cloudinary\Cloudinary as CloudinaryClient;

class CloudinaryService
{
    private CloudinaryClient $cloudinary;

    public function __construct()
    {
        $url = env('CLOUDINARY_URL');

        if (!$url) {
            throw new \RuntimeException('CLOUDINARY_URL tidak diset di .env');
        }

        $parsed = parse_url($url);

        $this->cloudinary = new \Cloudinary\Cloudinary([
            'cloud' => [
                'cloud_name' => $parsed['host'] ?? '',
                'api_key'    => $parsed['user'] ?? '',
                'api_secret' => $parsed['pass'] ?? '',
            ],
        ]);
    }
    public function upload(UploadedFile $file, string $folder = 'gomad'): array
    {
        $result = $this->cloudinary->uploadApi()->upload(
            $file->getRealPath(),
            [
                'folder' => $folder,
                'resource_type' => 'auto',
            ]
        );

        return [
            'public_id' => $result['public_id'],
            'url'       => $result['secure_url'],
            'width'     => $result['width'] ?? null,
            'height'    => $result['height'] ?? null,
        ];
    }

    public function delete(string $publicId): bool
    {
        $result = $this->cloudinary->uploadApi()->destroy($publicId);
        return ($result['result'] ?? '') === 'ok';
    }
}