<?php
// File: app/Exceptions/InvalidRouteStopException.php
// Deskripsi: Exception untuk kombinasi stop yang tidak valid

namespace App\Exceptions;

use Exception;

class InvalidRouteStopException extends Exception
{
    protected $message = 'Kombinasi stop tidak valid.';
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