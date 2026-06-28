<?php
// File: app/Http/Controllers/Api/Agency/WalletController.php
// Deskripsi: API Controller untuk wallet agency

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;
        $balance = $this->walletService->getBalance($agency);

        return response()->json([
            'success' => true,
            'message' => 'Saldo wallet berhasil diambil.',
            'data' => $balance,
            'meta' => null,
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $transactions = $this->walletService->getTransactionHistory(
            $agency,
            $request->limit ?? 50
        );

        return response()->json([
            'success' => true,
            'message' => 'Riwayat transaksi berhasil diambil.',
            'data' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'type_label' => $transaction->type === 'credit' ? 'Masuk' : 'Keluar',
                    'amount' => (float) $transaction->amount,
                    'amount_formatted' => 'Rp ' . number_format($transaction->amount, 0, ',', '.'),
                    'balance_before' => (float) $transaction->balance_before,
                    'balance_after' => (float) $transaction->balance_after,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'total' => $transactions->count(),
            ],
        ]);
    }
}

// End of file