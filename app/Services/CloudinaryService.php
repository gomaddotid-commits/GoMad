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

    /**
     * Upload file ke Cloudinary (tanpa batasan)
     */
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

    /**
     * Upload file dengan batasan ukuran maksimum
     * 
     * @param UploadedFile $file
     * @param string $folder
     * @param int $maxSizeKB Maksimum ukuran dalam KB
     * @return array
     * @throws \Exception Jika ukuran melebihi batas
     */
    public function uploadWithLimit(UploadedFile $file, string $folder = 'gomad', int $maxSizeKB = 2048): array
    {
        // Validasi ukuran file
        $sizeKB = $file->getSize() / 1024;
        
        if ($sizeKB > $maxSizeKB) {
            $maxMB = round($maxSizeKB / 1024, 1);
            $fileMB = round($sizeKB / 1024, 1);
            throw new \Exception(
                "Ukuran file ({$fileMB}MB) melebihi batas maksimum ({$maxMB}MB). " .
                "Silakan kompres atau perkecil file Anda."
            );
        }
        
        // Lanjutkan upload
        return $this->upload($file, $folder);
    }

    /**
     * Hapus file dari Cloudinary
     */
    public function delete(string $publicId): bool
    {
        $result = $this->cloudinary->uploadApi()->destroy($publicId);
        return ($result['result'] ?? '') === 'ok';
    }

    /**
     * Dapatkan ukuran maksimum yang diizinkan (untuk info ke user)
     */
    public function getMaxSizeInfo(int $maxSizeKB): string
    {
        if ($maxSizeKB >= 1024) {
            return round($maxSizeKB / 1024, 1) . ' MB';
        }
        return $maxSizeKB . ' KB';
    }
}