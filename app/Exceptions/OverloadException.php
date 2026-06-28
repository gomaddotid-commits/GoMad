<?php
// File: app/Exceptions/OverloadException.php
// Deskripsi: Exception untuk overload kendaraan

namespace App\Exceptions;

use Exception;

class OverloadException extends Exception
{
    protected $message = 'Kapasitas kendaraan sudah penuh.';
    protected $code = 422;

    public function __construct(string $message = null, int $code = null)
    {
        parent::__construct($message ?? $this->message, $code ?? $this->code);
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'data' => null,
            'meta' => null,
        ], $this->getCode());
    }
}

// End of file