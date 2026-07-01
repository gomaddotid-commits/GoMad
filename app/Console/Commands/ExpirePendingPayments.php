<?php
// File: app/Console/Commands/ExpirePendingPayments.php
// Deskripsi: Command untuk expire pembayaran yang melebihi batas waktu

namespace App\Console\Commands;

use App\Models\CashPayment;
use App\Models\Payment;
use App\Services\CashPaymentService;
use App\Services\PaymentService;
use Illuminate\Console\Command;

class ExpirePendingPayments extends Command
{
    protected $signature = 'gomad:expire-payments';
    protected $description = 'Expire pembayaran pending yang sudah melebihi batas waktu';

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly CashPaymentService $cashPaymentService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Checking for expired payments...');

        // Expire Midtrans payments (>30 menit)
        $expiredMidtransPayments = Payment::where('payment_type', 'midtrans')
            ->where('status', 'pending')
            ->where('expired_at', '<', now())
            ->get();

        $midtransCount = 0;
        foreach ($expiredMidtransPayments as $payment) {
            $this->paymentService->expirePayment($payment);
            $midtransCount++;
            $this->info("Midtrans payment #{$payment->id} expired.");
        }

        // Expire Cash payments (>24 jam)
        $expiredCashPayments = CashPayment::where('status', 'pending')
            ->where('expired_at', '<', now())
            ->get();

        $cashCount = 0;
        foreach ($expiredCashPayments as $cashPayment) {
            $this->cashPaymentService->expireCashPayment($cashPayment);
            $cashCount++;
            $this->info("Cash payment #{$cashPayment->id} - {$cashPayment->payment_code} expired.");
        }

        // ========================================
        // 👇 TAMBAHKAN INI - RELEASE COD DEPOSIT UNTUK JADWAL EXPIRED
        // ========================================
        $expiredSchedules = Schedule::where('allow_cod', true)
            ->where('cod_min_balance', '>', 0)
            ->where('departure_date', '<', now()->subDay()->toDateString())
            ->where('is_active', true)
            ->get();

        $codReleaseCount = 0;
        foreach ($expiredSchedules as $schedule) {
            $walletService = app(\App\Services\WalletService::class);
            $walletService->releaseCodDeposit(
                $schedule->agency,
                $schedule->cod_min_balance,
                $schedule->id
            );
            $codReleaseCount++;
            $this->info("COD deposit released for schedule #{$schedule->id} - {$schedule->route->route_name}");
        }
        $this->info("COD deposits released: {$codReleaseCount}");
        // ========================================


        $this->info("Expired Midtrans payments: {$midtransCount}");
        $this->info("Expired Cash payments: {$cashCount}");

        return Command::SUCCESS;
    }
}

// End of file