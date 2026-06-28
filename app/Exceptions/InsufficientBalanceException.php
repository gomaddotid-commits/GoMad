<?php
// File: app/Exceptions/InsufficientBalanceException.php
// Deskripsi: Exception untuk saldo tidak mencukupi

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    protected $message = 'Saldo tidak mencukupi.';
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