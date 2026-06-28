<?php
// File: app/Exceptions/ScheduleFullException.php
// Deskripsi: Exception untuk jadwal yang sudah penuh

namespace App\Exceptions;

use Exception;

class ScheduleFullException extends Exception
{
    protected $message = 'Jadwal sudah penuh.';
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