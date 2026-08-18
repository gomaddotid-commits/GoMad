<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint upload generik untuk aplikasi mobile.
 * Mengunggah gambar ke Cloudinary dan mengembalikan URL.
 * Dipakai untuk upload dokumen penyewa (KTP, SIM, NPWP, Selfie) dsb.
 */
class UploadController extends Controller
{
    public function __construct(
        private readonly CloudinaryService $cloudinary,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'type' => ['nullable', 'string', 'in:ktp,sim,npwp,selfie,avatar,general'],
        ]);

        $type = $request->input('type', 'general');
        $folder = $type === 'general' ? 'documents/general' : 'documents/' . $type;

        try {
            $result = $this->cloudinary->upload($request->file('file'), $folder);

            return response()->json([
                'success' => true,
                'message' => 'File berhasil diupload.',
                'data' => ['url' => $result['url']],
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah file: ' . $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }
}
