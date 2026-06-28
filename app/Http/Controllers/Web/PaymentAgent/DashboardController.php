<?php
// File: app/Http/Controllers/Web/PaymentAgent/DashboardController.php
// Deskripsi: Web Controller untuk dashboard payment agent

namespace App\Http\Controllers\Web\PaymentAgent;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('payment-agent.dashboard');
    }
}

// End of file