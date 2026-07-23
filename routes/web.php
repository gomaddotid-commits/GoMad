<?php
// File: routes/web.php
// Deskripsi: Web routes untuk GoMad (FIXED - Setup routes)

use Illuminate\Support\Facades\Route;

// Public Controllers
use App\Http\Controllers\Web\Public\HomeController as WebPublicHomeController;
use App\Http\Controllers\Web\Public\SearchController as WebPublicSearchController;
use App\Http\Controllers\Web\Public\AgencyProfileController as WebPublicAgencyProfileController;
use App\Http\Controllers\Web\Public\ListingController as WebPublicListingController;
use App\Http\Controllers\Web\Public\RentalController as WebPublicRentalController;

// Auth Controllers
use App\Http\Controllers\Web\Auth\LoginController as WebAuthLoginController;
use App\Http\Controllers\Web\Auth\RegisterController as WebAuthRegisterController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

// Customer Controllers
use App\Http\Controllers\Web\Customer\HomeController as WebCustomerHomeController;
use App\Http\Controllers\Web\Customer\BookingController as WebCustomerBookingController;
use App\Http\Controllers\Web\Customer\ProfileController as WebCustomerProfileController;
use App\Http\Controllers\Web\Customer\RentalController as WebCustomerRentalController;

// Agency Controllers
use App\Http\Controllers\Web\Agency\DashboardController as WebAgencyDashboardController;
use App\Http\Controllers\Web\Agency\ProfileController as WebAgencyProfileController;
use App\Http\Controllers\Web\Agency\ScheduleController as WebAgencyScheduleController;
use App\Http\Controllers\Web\Agency\BookingController as WebAgencyBookingController;
use App\Http\Controllers\Web\Agency\VehicleController as WebAgencyVehicleController;
use App\Http\Controllers\Web\Agency\DriverController as WebAgencyDriverController;
use App\Http\Controllers\Web\Agency\WalletController as WebAgencyWalletController;
use App\Http\Controllers\Web\Agency\WithdrawalController as WebAgencyWithdrawalController;
use App\Http\Controllers\Web\Agency\ReportController as WebAgencyReportController;
use App\Http\Controllers\Web\Agency\PromoController as WebAgencyPromoController;
use App\Http\Controllers\Web\Agency\RentalController as WebAgencyRentalController;
use App\Http\Controllers\Web\Agency\RentalPromoController as WebAgencyRentalPromoController;

// Driver Controllers
use App\Http\Controllers\Web\Driver\ScheduleController as WebDriverScheduleController;
use App\Http\Controllers\Web\Driver\BookingController as WebDriverBookingController;
use App\Http\Controllers\Web\Driver\ProfileController as WebDriverProfileController;

// Payment Agent Controllers
use App\Http\Controllers\Web\PaymentAgent\DashboardController as WebPaymentAgentDashboardController;
use App\Http\Controllers\Web\PaymentAgent\PaymentController as WebPaymentAgentPaymentController;
use App\Http\Controllers\Web\PaymentAgent\SettlementController as WebPaymentAgentSettlementController;
use App\Http\Controllers\Web\PaymentAgent\ProfileController as WebPaymentAgentProfileController;

// Admin Controllers
use App\Http\Controllers\Web\Admin\DashboardController as WebAdminDashboardController;
use App\Http\Controllers\Web\Admin\AgencyController as WebAdminAgencyController;
use App\Http\Controllers\Web\Admin\CustomerController as WebAdminCustomerController;
use App\Http\Controllers\Web\Admin\DriverController as WebAdminDriverController;
use App\Http\Controllers\Web\Admin\PaymentAgentController as WebAdminPaymentAgentController;
use App\Http\Controllers\Web\Admin\RouteController as WebAdminRouteController;
use App\Http\Controllers\Web\Admin\BookingController as WebAdminBookingController;
use App\Http\Controllers\Web\Admin\PromoController as WebAdminPromoController;
use App\Http\Controllers\Web\Admin\WithdrawalController as WebAdminWithdrawalController;
use App\Http\Controllers\Web\Admin\SettlementController as WebAdminSettlementController;
use App\Http\Controllers\Web\Admin\ReportController as WebAdminReportController;
use App\Http\Controllers\Web\Admin\SettingController as WebAdminSettingController;
use App\Http\Controllers\Web\Admin\RentalController as WebAdminRentalController;
use App\Http\Controllers\Web\Admin\RentalDocumentController as WebAdminRentalDocumentController;

// Email Verification Routes
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('customer.home')
        ->with('success', 'Email berhasil diverifikasi!');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('success', 'Link verifikasi telah dikirim ulang!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', \App\Http\Middleware\Web\CustomerMiddleware::class])
    ->prefix('customer')
    ->name('customer.')
    ->group(function () {
        
        // SETUP PROFILE
        Route::get('/setup', [WebCustomerProfileController::class, 'setup'])->name('setup');
        Route::post('/setup', [WebCustomerProfileController::class, 'saveSetup'])->name('setup.save');
        
        // HOME & SEARCH
        Route::get('/home', [WebCustomerHomeController::class, 'index'])->name('home');
        Route::get('/search', [WebCustomerHomeController::class, 'search'])->name('search');
        
        // BOOKING
        Route::get('/booking/create/{schedule}', [WebCustomerBookingController::class, 'create'])->name('booking.create');
        Route::post('/booking', [WebCustomerBookingController::class, 'store'])->name('booking.store');
        Route::get('/booking/{booking}', [WebCustomerBookingController::class, 'show'])->name('booking.show');
        Route::get('/booking/{booking}/detail', [WebCustomerBookingController::class, 'detail'])->name('booking.detail');
        
        // Rental
        Route::get('/rentals', [WebCustomerRentalController::class, 'index'])->name('rentals');
        Route::get('/rentals/browse', [WebCustomerRentalController::class, 'browse'])->name('rental.browse');
        Route::get('/rentals/create/{vehicleSetting}', [WebCustomerRentalController::class, 'create'])->name('rental.create');
        Route::post('/rentals', [WebCustomerRentalController::class, 'store'])->name('rental.store');
        Route::get('/rentals/{rental}', [WebCustomerRentalController::class, 'show'])->name('rental.show');
        Route::post('/rentals/{rental}/cancel', [WebCustomerRentalController::class, 'cancel'])->name('rental.cancel');
        Route::post('/rentals/{rental}/pay', [WebCustomerRentalController::class, 'pay'])->name('rental.pay');

        // Dokumen
        Route::get('/documents', [WebCustomerRentalController::class, 'documents'])->name('documents');
        Route::post('/documents', [WebCustomerRentalController::class, 'submitDocuments'])->name('documents.submit');

        // PAYMENT PROCESS (dari dropdown)
        Route::post('/booking/{booking}/pay-process', [WebCustomerBookingController::class, 'payProcess'])->name('booking.pay-process');
        
        // CHANGE PAYMENT
        Route::post('/booking/{booking}/change-payment', [WebCustomerBookingController::class, 'changePayment'])->name('booking.change-payment');
        
        // CANCEL
        Route::post('/booking/{booking}/cancel', [WebCustomerBookingController::class, 'cancel'])->name('booking.cancel');
        
        // E-TICKET
        Route::get('/booking/{booking}/e-ticket', [WebCustomerBookingController::class, 'eTicket'])->name('booking.e-ticket');
        
        // REVIEW
        Route::post('/booking/{booking}/review', [WebCustomerBookingController::class, 'review'])->name('booking.review');
        
        // MY BOOKINGS
        Route::get('/my-bookings', [WebCustomerBookingController::class, 'index'])->name('bookings');
        
        // PROFILE
        Route::get('/profile', [WebCustomerProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [WebCustomerProfileController::class, 'update'])->name('profile.update');
    });

/*
|--------------------------------------------------------------------------
| Agency Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', \App\Http\Middleware\Web\AgencyMiddleware::class])
    ->prefix('agency')
    ->name('agency.')
    ->group(function () {
        
        // SETUP PROFILE (khusus agency) - HARUS di atas
        Route::get('/setup', [WebAgencyProfileController::class, 'setup'])->name('setup');
        Route::post('/setup', [WebAgencyProfileController::class, 'saveSetup'])->name('setup.save');
        
        Route::get('/dashboard', [WebAgencyDashboardController::class, 'index'])->name('dashboard');
        
        Route::get('/profile', [WebAgencyProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [WebAgencyProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/logo', [WebAgencyProfileController::class, 'uploadLogo'])->name('profile.logo');
        Route::post('/profile/cover', [WebAgencyProfileController::class, 'uploadCover'])->name('profile.cover');
        Route::post('/profile/license', [WebAgencyProfileController::class, 'uploadBusinessLicense'])->name('profile.license');
        Route::post('/profile/gallery', [WebAgencyProfileController::class, 'addGalleryPhoto'])->name('profile.gallery.add');
        Route::delete('/profile/gallery/{index}', [WebAgencyProfileController::class, 'removeGalleryPhoto'])->name('profile.gallery.remove');
        Route::post('/profile/verify', [WebAgencyProfileController::class, 'submitVerification'])->name('profile.verify');
        
        Route::get('/schedules', [WebAgencyScheduleController::class, 'index'])->name('schedules.index');
        Route::get('/schedules/create', [WebAgencyScheduleController::class, 'create'])->name('schedules.create');
        Route::post('/schedules', [WebAgencyScheduleController::class, 'store'])->name('schedules.store');
        Route::get('/schedules/{schedule}', [WebAgencyScheduleController::class, 'show'])->name('schedules.show');
        Route::delete('/schedules/{schedule}', [WebAgencyScheduleController::class, 'destroy'])->name('schedules.destroy');
        Route::post('/schedules/{schedule}/assign-driver', [WebAgencyScheduleController::class, 'assignDriver'])->name('schedules.assign-driver');
        Route::post('/schedules/{schedule}/start', [WebAgencyScheduleController::class, 'startSchedule'])->name('schedules.start');
        Route::delete('/schedules/{schedule}', [WebAgencyScheduleController::class, 'destroy'])->name('schedules.destroy');
        Route::delete('/schedules/{schedule}', [WebAgencyScheduleController::class, 'destroy'])->name('schedules.destroy');

        Route::get('/bookings', [WebAgencyBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [WebAgencyBookingController::class, 'show'])->name('bookings.show');
        Route::put('/bookings/{booking}/status', [WebAgencyBookingController::class, 'updateStatus'])->name('bookings.status');

        // Rental
        Route::get('/rental/dashboard', [WebAgencyRentalController::class, 'dashboard'])->name('rental.dashboard');
        Route::get('/rentals', [WebAgencyRentalController::class, 'index'])->name('rental.index');
        Route::get('/rentals/{rental}', [WebAgencyRentalController::class, 'show'])->name('rental.show');
        Route::post('/rentals/{rental}/verify-pickup', [WebAgencyRentalController::class, 'verifyPickup'])->name('rental.verify-pickup');
        Route::post('/rentals/{rental}/verify-return', [WebAgencyRentalController::class, 'verifyReturn'])->name('rental.verify-return');
        Route::post('/rentals/{rental}/complete', [WebAgencyRentalController::class, 'complete'])->name('rental.complete');
        Route::get('/rental-promos', [App\Http\Controllers\Web\Agency\RentalPromoController::class, 'index'])->name('rental.promos');
        Route::post('/rental-promos/attach', [App\Http\Controllers\Web\Agency\RentalPromoController::class, 'attach'])->name('rental.promos.attach');
        Route::delete('/rental-promos/{vehicle}/detach/{promo}', [App\Http\Controllers\Web\Agency\RentalPromoController::class, 'detach'])->name('rental.promos.detach');
        Route::post('/rentals/{rental}/assign-driver', [WebAgencyRentalController::class, 'assignDriver'])->name('rental.assign-driver');

        // Setup Kendaraan
        Route::get('/rental-vehicles', [WebAgencyRentalController::class, 'vehicles'])->name('rental.vehicles');
        Route::get('/rental-vehicles/{vehicle}/setup', [WebAgencyRentalController::class, 'vehicleSetup'])->name('rental.vehicle-setup');
        Route::post('/rental-vehicles/{vehicle}/setup', [WebAgencyRentalController::class, 'saveVehicleSetup'])->name('rental.vehicle-setup.save');

        Route::get('/promos', [WebAgencyPromoController::class, 'index'])->name('promos.index');
        Route::post('/promos/attach', [WebAgencyPromoController::class, 'attachToSchedule'])->name('promos.attach');
        Route::delete('/promos/{promo}/detach/{schedule}', [WebAgencyPromoController::class, 'detachFromSchedule'])->name('promos.detach');

        // Transfer Penumpang
        Route::get('/schedules/{schedule}/transfer', [WebAgencyScheduleController::class, 'transferPage'])->name('schedules.transfer');
        Route::post('/schedules/{schedule}/transfer/search', [WebAgencyScheduleController::class, 'searchTransfer'])->name('schedules.transfer.search');
        Route::post('/schedules/transfer-request', [WebAgencyScheduleController::class, 'createTransferRequest'])->name('schedules.transfer.request');
        Route::get('/transfers', [WebAgencyScheduleController::class, 'transfersIndex'])->name('transfers.index');
        Route::post('/transfers/{transfer}/approve', [WebAgencyScheduleController::class, 'approveTransfer'])->name('transfers.approve');
        Route::post('/transfers/{transfer}/reject', [WebAgencyScheduleController::class, 'rejectTransfer'])->name('transfers.reject');
        Route::post('/transfers/{transfer}/cancel', [WebAgencyScheduleController::class, 'cancelTransfer'])->name('transfers.cancel');

        Route::get('/vehicles', [WebAgencyVehicleController::class, 'index'])->name('vehicles.index');
        Route::get('/vehicles/create', [WebAgencyVehicleController::class, 'create'])->name('vehicles.create');
        Route::post('/vehicles', [WebAgencyVehicleController::class, 'store'])->name('vehicles.store');
        Route::get('/vehicles/{vehicle}/edit', [WebAgencyVehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('/vehicles/{vehicle}', [WebAgencyVehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{vehicle}', [WebAgencyVehicleController::class, 'destroy'])->name('vehicles.destroy');
        
        Route::get('/drivers', [WebAgencyDriverController::class, 'index'])->name('drivers.index');
        Route::get('/drivers/create', [WebAgencyDriverController::class, 'create'])->name('drivers.create');
        Route::post('/drivers', [WebAgencyDriverController::class, 'store'])->name('drivers.store');
        Route::get('/drivers/{user}/edit', [WebAgencyDriverController::class, 'edit'])->name('drivers.edit');
        Route::put('/drivers/{user}', [WebAgencyDriverController::class, 'update'])->name('drivers.update');
        Route::delete('/drivers/{user}', [WebAgencyDriverController::class, 'destroy'])->name('drivers.destroy');
        
        Route::get('/wallet', [WebAgencyWalletController::class, 'index'])->name('wallet.index');
        Route::get('/wallet/topup', [WebAgencyWalletController::class, 'topUpPage'])->name('wallet.topup');
        Route::post('/wallet/topup', [WebAgencyWalletController::class, 'processTopUp'])->name('wallet.topup.process');
        Route::post('/wallet/transfer-to-deposit', [WebAgencyWalletController::class, 'transferToDeposit'])->name('wallet.transfer-deposit');
        
        Route::get('/withdrawals', [WebAgencyWithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::get('/withdrawals/create', [WebAgencyWithdrawalController::class, 'create'])->name('withdrawals.create');
        Route::post('/withdrawals', [WebAgencyWithdrawalController::class, 'store'])->name('withdrawals.store');
        
        Route::get('/reports', [WebAgencyReportController::class, 'index'])->name('reports');
        Route::get('/reviews', [WebAgencyReportController::class, 'reviews'])->name('reviews');
    });

/*
|--------------------------------------------------------------------------
| Driver Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', \App\Http\Middleware\Web\DriverMiddleware::class])
    ->prefix('driver')
    ->name('driver.')
    ->group(function () {
        Route::get('/schedule', [WebDriverScheduleController::class, 'index'])->name('schedule');
        Route::get('/schedule/{schedule}', [WebDriverScheduleController::class, 'show'])->name('schedule.show');
        Route::post('/schedule/{schedule}/finish', [WebDriverScheduleController::class, 'finish'])->name('schedule.finish');

        // Halaman daftar penumpang
        Route::get('/bookings', [WebDriverBookingController::class, 'index'])->name('bookings');
        
        // Aksi per booking (Jemput, Antar, Selesai)
        Route::post('/bookings/{booking}/pickup', [WebDriverBookingController::class, 'pickupBooking'])->name('bookings.pickup');
        Route::post('/bookings/{booking}/dropoff', [WebDriverBookingController::class, 'dropoffBooking'])->name('bookings.dropoff');
        Route::post('/bookings/{booking}/complete', [WebDriverBookingController::class, 'completeBooking'])->name('bookings.complete');
        Route::post('/bookings/{booking}/confirm-cod', [WebDriverBookingController::class, 'confirmCod'])->name('bookings.confirm-cod');
        
        Route::get('/profile', [WebDriverProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [WebDriverProfileController::class, 'update'])->name('profile.update');
    });

/*
|--------------------------------------------------------------------------
| Payment Agent Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', \App\Http\Middleware\Web\PaymentAgentMiddleware::class])
    ->prefix('payment-agent')
    ->name('payment-agent.')
    ->group(function () {
        
        // SETUP PROFILE (khusus payment agent) - HARUS di atas
        Route::get('/setup', [WebPaymentAgentProfileController::class, 'setup'])->name('setup');
        Route::post('/setup', [WebPaymentAgentProfileController::class, 'saveSetup'])->name('setup.save');
        
        Route::get('/dashboard', [WebPaymentAgentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/payments', [WebPaymentAgentPaymentController::class, 'index'])->name('payments');
        Route::post('/payments/confirm', [WebPaymentAgentPaymentController::class, 'confirm'])->name('payments.confirm');
        Route::get('/settlements', [WebPaymentAgentSettlementController::class, 'index'])->name('settlements');
        Route::post('/settlements/{settlement}/pay', [WebPaymentAgentSettlementController::class, 'paySettlement'])->name('settlements.pay');
        Route::get('/profile', [WebPaymentAgentProfileController::class, 'show'])->name('profile');
    });

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', \App\Http\Middleware\Web\AdminMiddleware::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [WebAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/agencies', [WebAdminAgencyController::class, 'index'])->name('agencies.index');
        Route::get('/agencies/{agency}', [WebAdminAgencyController::class, 'show'])->name('agencies.show');
        Route::post('/agencies/{agency}/verify', [WebAdminAgencyController::class, 'verify'])->name('agencies.verify');
        Route::post('/agencies/{agency}/reject', [WebAdminAgencyController::class, 'reject'])->name('agencies.reject');
        
        Route::get('/customers', [WebAdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{user}', [WebAdminCustomerController::class, 'show'])->name('customers.show');
        Route::put('/customers/{user}/toggle-active', [WebAdminCustomerController::class, 'toggleActive'])->name('customers.toggle-active');
        Route::post('/customers/{user}/ban', [WebAdminCustomerController::class, 'ban'])->name('customers.ban');
        Route::post('/customers/{user}/unban', [WebAdminCustomerController::class, 'unban'])->name('customers.unban');
        
        Route::get('/drivers', [WebAdminDriverController::class, 'index'])->name('drivers.index');
        
        Route::get('/payment-agents', [WebAdminPaymentAgentController::class, 'index'])->name('payment-agents.index');
        Route::get('/payment-agents/{agent}', [WebAdminPaymentAgentController::class, 'show'])->name('payment-agents.show');
        Route::post('/payment-agents/{agent}/verify', [WebAdminPaymentAgentController::class, 'verify'])->name('payment-agents.verify');
        Route::post('/payment-agents/{agent}/reject', [WebAdminPaymentAgentController::class, 'reject'])->name('payment-agents.reject');
        Route::put('/payment-agents/{agent}/toggle-active', [WebAdminPaymentAgentController::class, 'toggleActive'])->name('payment-agents.toggle-active');
        
        Route::get('/routes', [WebAdminRouteController::class, 'index'])->name('routes.index');
        Route::get('/routes/create', [WebAdminRouteController::class, 'create'])->name('routes.create');
        Route::post('/routes', [WebAdminRouteController::class, 'store'])->name('routes.store');
        Route::get('/routes/{route}', [WebAdminRouteController::class, 'show'])->name('routes.show');
        Route::get('/routes/{route}/edit', [WebAdminRouteController::class, 'edit'])->name('routes.edit');
        Route::put('/routes/{route}', [WebAdminRouteController::class, 'update'])->name('routes.update');
        Route::delete('/routes/{route}', [WebAdminRouteController::class, 'destroy'])->name('routes.destroy');
        Route::post('/routes/{route}/stops', [WebAdminRouteController::class, 'addStop'])->name('routes.stops.add');
        Route::delete('/routes/{route}/stops/{stop}', [WebAdminRouteController::class, 'removeStop'])->name('routes.stops.remove');

        Route::get('/bookings', [WebAdminBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [WebAdminBookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{booking}/refund-approve', [WebAdminBookingController::class, 'approveRefund'])->name('admin.refund.approve');
        Route::post('/bookings/{booking}/refund-reject', [WebAdminBookingController::class, 'rejectRefund'])->name('admin.refund.reject');

        Route::get('/promos', [WebAdminPromoController::class, 'index'])->name('promos.index');
        Route::get('/promos/create', [WebAdminPromoController::class, 'create'])->name('promos.create');
        Route::post('/promos', [WebAdminPromoController::class, 'store'])->name('promos.store');
        Route::get('/promos/{promo}', [WebAdminPromoController::class, 'show'])->name('promos.show');
        Route::get('/promos/{promo}/edit', [WebAdminPromoController::class, 'edit'])->name('promos.edit');
        Route::put('/promos/{promo}', [WebAdminPromoController::class, 'update'])->name('promos.update');
        Route::delete('/promos/{promo}', [WebAdminPromoController::class, 'destroy'])->name('promos.destroy');

        Route::get('/withdrawals', [WebAdminWithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::post('/withdrawals/{withdrawal}/approve', [WebAdminWithdrawalController::class, 'approve'])->name('withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject', [WebAdminWithdrawalController::class, 'reject'])->name('withdrawals.reject');
        
        Route::get('/settlements', [WebAdminSettlementController::class, 'index'])->name('settlements.index');
        Route::post('/settlements/{settlement}/verify', [WebAdminSettlementController::class, 'verify'])->name('settlements.verify');
        
        Route::get('/reports', [WebAdminReportController::class, 'index'])->name('reports');
        Route::get('/settings', [WebAdminSettingController::class, 'index'])->name('settings');
        Route::put('/settings', [WebAdminSettingController::class, 'update'])->name('settings.update');
        Route::post('/test-whatsapp', [WebAdminSettingController::class, 'testWhatsApp'])->name('test-whatsapp');      
    });
// ADMIN - RENTAL ROUTES
Route::middleware(['auth', \App\Http\Middleware\Web\AdminMiddleware::class])
    ->prefix('admin/rental')
    ->name('admin.rental.')
    ->group(function () {
        
        Route::get('/dashboard', [App\Http\Controllers\Web\Admin\RentalController::class, 'dashboard'])->name('dashboard');
        Route::get('/rentals', [App\Http\Controllers\Web\Admin\RentalController::class, 'index'])->name('index');
        Route::get('/rentals/{rental}', [App\Http\Controllers\Web\Admin\RentalController::class, 'show'])->name('show');
        
        // Verifikasi Dokumen
        Route::get('/documents', [App\Http\Controllers\Web\Admin\RentalController::class, 'documents'])->name('documents');
        Route::post('/documents/{document}/verify', [App\Http\Controllers\Web\Admin\RentalController::class, 'verifyDocument'])->name('documents.verify');
        Route::post('/documents/{document}/reject', [App\Http\Controllers\Web\Admin\RentalController::class, 'rejectDocument'])->name('documents.reject');
    });
/*
|--------------------------------------------------------------------------
| Public Routes (No Auth)
|--------------------------------------------------------------------------
*/

Route::get('/', [WebPublicHomeController::class, 'index'])->name('home');
Route::get('/search', [WebPublicSearchController::class, 'search'])->name('search');
Route::get('/listing', [WebPublicListingController::class, 'index'])->name('listing');
Route::get('/agency/{slug}', [WebPublicAgencyProfileController::class, 'show'])->name('agency.profile');
Route::get('/rental', [App\Http\Controllers\Web\Public\RentalController::class, 'index'])->name('rental.public');
Route::get('/rental/{vehicleSetting}', [App\Http\Controllers\Web\Public\RentalController::class, 'show'])->name('rental.public.show');
Route::get('/download-app', [WebPublicHomeController::class, 'downloadApp'])->name('download-app');
Route::get('/e-ticket', [WebPublicHomeController::class, 'eTicketPage'])->name('eticket.public');
Route::post('/e-ticket/check', [WebPublicHomeController::class, 'checkETicket'])->name('eticket.check');
Route::post('/e-ticket/send', [WebPublicHomeController::class, 'sendETicket'])->name('eticket.send');

// Auth Routes
Route::get('/login', [WebAuthLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [WebAuthLoginController::class, 'login']);
Route::get('/register', [WebAuthRegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [WebAuthRegisterController::class, 'register']);
Route::post('/logout', [WebAuthLoginController::class, 'logout'])->name('logout');
Route::get('/auth/google', [App\Http\Controllers\Web\Auth\GoogleController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [App\Http\Controllers\Web\Auth\GoogleController::class, 'callback'])->name('google.callback');
// Forgot Password
Route::get('/forgot-password', [App\Http\Controllers\Web\Auth\ForgotPasswordController::class, 'showForgotForm'])
    ->middleware('guest')
    ->name('password.request');

Route::post('/forgot-password', [App\Http\Controllers\Web\Auth\ForgotPasswordController::class, 'sendResetLink'])
    ->middleware('guest')
    ->name('password.email');

Route::get('/reset-password/{token}', [App\Http\Controllers\Web\Auth\ForgotPasswordController::class, 'showResetForm'])
    ->middleware('guest')
    ->name('password.reset');

Route::post('/reset-password', [App\Http\Controllers\Web\Auth\ForgotPasswordController::class, 'resetPassword'])
    ->middleware('guest')
    ->name('password.update');
    
// routes/web.php
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'time' => now()]);
});


// End of file