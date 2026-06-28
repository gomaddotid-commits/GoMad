<?php
// File: app/Exceptions/InvalidWithdrawalException.php
// Deskripsi: Exception untuk penarikan dana yang tidak valid

namespace App\Exceptions;

use Exception;

class InvalidWithdrawalException extends Exception
{
    protected $message = 'Penarikan dana tidak valid.';
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