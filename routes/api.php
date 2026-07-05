<?php
// File: routes/api.php
// Deskripsi: API routes untuk GoMad dengan Sanctum authentication (FINAL)

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Controllers
use App\Http\Controllers\Api\Auth\LoginController as ApiAuthLoginController;
use App\Http\Controllers\Api\Auth\RegisterController as ApiAuthRegisterController;
use App\Http\Controllers\Api\Auth\DeviceTokenController as ApiAuthDeviceTokenController;

// Public Controllers
use App\Http\Controllers\Api\Public\HomeController as ApiPublicHomeController;
use App\Http\Controllers\Api\Public\SearchController as ApiPublicSearchController;
use App\Http\Controllers\Api\Public\AgencyProfileController as ApiPublicAgencyProfileController;
use App\Http\Controllers\Api\Public\ListingController as ApiPublicListingController;
use App\Http\Controllers\Api\Public\ScheduleController as ApiPublicScheduleController;
use App\Http\Controllers\Api\Public\ETicketController as ApiPublicETicketController;

// Customer Controllers
use App\Http\Controllers\Api\Customer\BookingController as ApiCustomerBookingController;
use App\Http\Controllers\Api\Customer\ScheduleController as ApiCustomerScheduleController;
use App\Http\Controllers\Api\Customer\RouteController as ApiCustomerRouteController;
use App\Http\Controllers\Api\Customer\AgencyController as ApiCustomerAgencyController;
use App\Http\Controllers\Api\Customer\PaymentController as ApiCustomerPaymentController;
use App\Http\Controllers\Api\Customer\ProfileController as ApiCustomerProfileController;
use App\Http\Controllers\Api\Customer\PromoController as ApiCustomerPromoController;

// Agency Controllers
use App\Http\Controllers\Api\Agency\DashboardController as ApiAgencyDashboardController;
use App\Http\Controllers\Api\Agency\ProfileController as ApiAgencyProfileController;
use App\Http\Controllers\Api\Agency\ScheduleController as ApiAgencyScheduleController;
use App\Http\Controllers\Api\Agency\BookingController as ApiAgencyBookingController;
use App\Http\Controllers\Api\Agency\VehicleController as ApiAgencyVehicleController;
use App\Http\Controllers\Api\Agency\DriverController as ApiAgencyDriverController;
use App\Http\Controllers\Api\Agency\WalletController as ApiAgencyWalletController;
use App\Http\Controllers\Api\Agency\WithdrawalController as ApiAgencyWithdrawalController;
use App\Http\Controllers\Api\Agency\ReportController as ApiAgencyReportController;
use App\Http\Controllers\Api\Agency\PromoController as ApiAgencyPromoController;
use App\Http\Controllers\Api\Agency\TransferController as ApiAgencyTransferController;

// Driver Controllers
use App\Http\Controllers\Api\Driver\ScheduleController as ApiDriverScheduleController;
use App\Http\Controllers\Api\Driver\RouteController as ApiDriverRouteController;
use App\Http\Controllers\Api\Driver\PassengerController as ApiDriverPassengerController;
use App\Http\Controllers\Api\Driver\LocationController as ApiDriverLocationController;
use App\Http\Controllers\Api\Driver\BookingController as ApiDriverBookingController;

// Payment Agent Controllers
use App\Http\Controllers\Api\PaymentAgent\AuthController as ApiPaymentAgentAuthController;
use App\Http\Controllers\Api\PaymentAgent\DashboardController as ApiPaymentAgentDashboardController;
use App\Http\Controllers\Api\PaymentAgent\PaymentController as ApiPaymentAgentPaymentController;
use App\Http\Controllers\Api\PaymentAgent\SettlementController as ApiPaymentAgentSettlementController;
use App\Http\Controllers\Api\PaymentAgent\ProfileController as ApiPaymentAgentProfileController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\DashboardController as ApiAdminDashboardController;
use App\Http\Controllers\Api\Admin\AgencyController as ApiAdminAgencyController;
use App\Http\Controllers\Api\Admin\CustomerController as ApiAdminCustomerController;
use App\Http\Controllers\Api\Admin\DriverController as ApiAdminDriverController;
use App\Http\Controllers\Api\Admin\PaymentAgentController as ApiAdminPaymentAgentController;
use App\Http\Controllers\Api\Admin\RouteController as ApiAdminRouteController;
use App\Http\Controllers\Api\Admin\BookingController as ApiAdminBookingController;
use App\Http\Controllers\Api\Admin\WithdrawalController as ApiAdminWithdrawalController;
use App\Http\Controllers\Api\Admin\SettlementController as ApiAdminSettlementController;
use App\Http\Controllers\Api\Admin\ReportController as ApiAdminReportController;
use App\Http\Controllers\Api\Admin\SettingController as ApiAdminSettingController;
use App\Http\Controllers\Api\Admin\PromoController as ApiAdminPromoController;

/*
|--------------------------------------------------------------------------
| Public Routes (No Auth)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Auth Routes
    Route::post('/auth/login', [ApiAuthLoginController::class, 'login']);
    Route::post('/auth/register', [ApiAuthRegisterController::class, 'register']);
    Route::post('/auth/register-agency', [ApiAuthRegisterController::class, 'registerAgency']);
    Route::post('/auth/register-payment-agent', [ApiAuthRegisterController::class, 'registerPaymentAgent']);

    // Public Routes
    Route::get('/home', [ApiPublicHomeController::class, 'index']);
    Route::get('/search', [ApiPublicSearchController::class, 'search']);
    Route::get('/routes', [ApiPublicSearchController::class, 'routes']);
    Route::get('/routes/{id}', [ApiPublicSearchController::class, 'routeDetail']);
    Route::get('/cities', [ApiPublicSearchController::class, 'cities']);
    Route::get('/agencies', [ApiPublicListingController::class, 'index']);
    Route::get('/agencies/{slug}', [ApiPublicAgencyProfileController::class, 'show']);
    Route::get('/agencies/{slug}/reviews', [ApiPublicAgencyProfileController::class, 'reviews']);
    Route::get('/agencies/{slug}/schedules', [ApiPublicAgencyProfileController::class, 'schedules']);
    Route::get('/schedules', [ApiPublicSearchController::class, 'schedules']);
    Route::get('/schedules/{id}', [ApiPublicSearchController::class, 'scheduleDetail']);
    Route::get('/schedules/{schedule}/dropoffs/{originStopId}', [ApiPublicScheduleController::class, 'availableDropoffs']);
    Route::get('/nearby-warungs', [ApiPublicSearchController::class, 'nearbyWarungs']);

    // E-Ticket Public
    Route::post('/e-ticket/check', [ApiPublicETicketController::class, 'check']);
    Route::post('/e-ticket/send', [ApiPublicETicketController::class, 'send']);

    // Midtrans Callback (No Auth) — rate limit + IP whitelist
    Route::middleware(['throttle:60,1', 'midtrans.webhook'])->group(function () {
        Route::post('/midtrans/callback', [ApiCustomerPaymentController::class, 'midtransCallback']);
        Route::post('/midtrans/disbursement-callback', [ApiAdminWithdrawalController::class, 'disbursementCallback']);
        Route::post('/midtrans/settlement-callback', [ApiPaymentAgentSettlementController::class, 'settlementCallback']);
        Route::post('/midtrans/topup-callback', [ApiAgencyWalletController::class, 'topUpCallback']);
    });
    /*
    |--------------------------------------------------------------------------
    | Authenticated Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', \App\Http\Middleware\Api\ApiAuthenticate::class])->group(function () {

        // Device Token
        Route::post('/device-token', [ApiAuthDeviceTokenController::class, 'register']);
        Route::delete('/device-token', [ApiAuthDeviceTokenController::class, 'unregister']);

        // Auth Profile
        Route::get('/auth/me', [ApiAuthLoginController::class, 'me']);
        Route::put('/auth/profile', [ApiAuthLoginController::class, 'updateProfile']);
        Route::put('/auth/password', [ApiAuthLoginController::class, 'updatePassword']);
        Route::post('/auth/logout', [ApiAuthLoginController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | Customer Routes
        |--------------------------------------------------------------------------
        */
        Route::prefix('customer')->group(function () {
            // Bookings
            Route::get('/bookings', [ApiCustomerBookingController::class, 'index']);
            Route::get('/bookings/{booking}', [ApiCustomerBookingController::class, 'show']);
            Route::post('/bookings', [ApiCustomerBookingController::class, 'store']);
            Route::post('/bookings/{booking}/cancel', [ApiCustomerBookingController::class, 'cancel']);
            Route::get('/bookings/{booking}/e-ticket', [ApiCustomerBookingController::class, 'eTicket']);

            // Payments
            Route::post('/payments/midtrans', [ApiCustomerPaymentController::class, 'payWithMidtrans']);
            Route::post('/payments/cash', [ApiCustomerPaymentController::class, 'payWithCash']);
            Route::get('/payments/cash/{code}', [ApiCustomerPaymentController::class, 'checkCashPayment']);
            Route::get('/warungs/nearby', [ApiCustomerPaymentController::class, 'nearbyWarungs']);

            // Schedules
            Route::get('/schedules/available', [ApiCustomerScheduleController::class, 'availableSchedules']);
            Route::get('/schedules/{schedule}/stops', [ApiCustomerScheduleController::class, 'scheduleStops']);
            Route::get('/schedules/{schedule}/pricing', [ApiCustomerScheduleController::class, 'schedulePricing']);
            Route::get('/schedules/{schedule}/dropoffs/{originStopId}', [ApiCustomerScheduleController::class, 'availableDropoffs']);

            // Promos
            Route::get('/promos/available', [ApiCustomerPromoController::class, 'available']);
            Route::post('/promos/calculate', [ApiCustomerPromoController::class, 'calculate']);

            // Routes & Agencies
            Route::get('/routes', [ApiCustomerRouteController::class, 'index']);
            Route::get('/agencies', [ApiCustomerAgencyController::class, 'index']);

            // Reviews
            Route::post('/reviews', [ApiCustomerBookingController::class, 'createReview']);

            // Profile & Referral
            Route::get('/profile', [ApiCustomerProfileController::class, 'show']);
            Route::put('/profile', [ApiCustomerProfileController::class, 'update']);
            Route::get('/referral-code', [ApiCustomerProfileController::class, 'referralCode']);
        });

        /*
        |--------------------------------------------------------------------------
        | Agency Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware([\App\Http\Middleware\Api\AgencyMiddleware::class])->prefix('agency')->group(function () {
            // Dashboard
            Route::get('/dashboard', [ApiAgencyDashboardController::class, 'index']);

            // Profile
            Route::get('/profile', [ApiAgencyProfileController::class, 'show']);
            Route::put('/profile', [ApiAgencyProfileController::class, 'update']);
            Route::post('/profile/logo', [ApiAgencyProfileController::class, 'uploadLogo']);
            Route::post('/profile/cover', [ApiAgencyProfileController::class, 'uploadCover']);
            Route::post('/profile/gallery', [ApiAgencyProfileController::class, 'addGalleryPhoto']);
            Route::delete('/profile/gallery/{index}', [ApiAgencyProfileController::class, 'removeGalleryPhoto']);
            Route::post('/profile/verify', [ApiAgencyProfileController::class, 'submitVerification']);

            // Schedules
            Route::get('/schedules', [ApiAgencyScheduleController::class, 'index']);
            Route::post('/schedules', [ApiAgencyScheduleController::class, 'store']);
            Route::get('/schedules/{schedule}', [ApiAgencyScheduleController::class, 'show']);
            Route::put('/schedules/{schedule}', [ApiAgencyScheduleController::class, 'update']);
            Route::delete('/schedules/{schedule}', [ApiAgencyScheduleController::class, 'destroy']);
            Route::post('/schedules/{schedule}/assign-driver', [ApiAgencyScheduleController::class, 'assignDriver']);
            Route::get('/schedules/{schedule}/pricing', [ApiAgencyScheduleController::class, 'pricing']);
            Route::get('/schedules/{schedule}/required-pairs', [ApiAgencyScheduleController::class, 'requiredPairs']);
            Route::post('/schedules/{schedule}/start', [ApiAgencyScheduleController::class, 'startSchedule']);

            // Transfers
            Route::get('/schedules/{schedule}/transfer/available', [ApiAgencyTransferController::class, 'availableSchedules']);
            Route::post('/transfers', [ApiAgencyTransferController::class, 'create']);
            Route::get('/transfers', [ApiAgencyTransferController::class, 'index']);
            Route::post('/transfers/{transfer}/approve', [ApiAgencyTransferController::class, 'approve']);
            Route::post('/transfers/{transfer}/reject', [ApiAgencyTransferController::class, 'reject']);
            Route::post('/transfers/{transfer}/cancel', [ApiAgencyTransferController::class, 'cancel']);

            // Bookings
            Route::get('/bookings', [ApiAgencyBookingController::class, 'index']);
            Route::get('/bookings/{booking}', [ApiAgencyBookingController::class, 'show']);
            Route::put('/bookings/{booking}/status', [ApiAgencyBookingController::class, 'updateStatus']);

            // Vehicles
            Route::get('/vehicles', [ApiAgencyVehicleController::class, 'index']);
            Route::post('/vehicles', [ApiAgencyVehicleController::class, 'store']);
            Route::get('/vehicles/{vehicle}', [ApiAgencyVehicleController::class, 'show']);
            Route::put('/vehicles/{vehicle}', [ApiAgencyVehicleController::class, 'update']);
            Route::delete('/vehicles/{vehicle}', [ApiAgencyVehicleController::class, 'destroy']);

            // Drivers
            Route::get('/drivers', [ApiAgencyDriverController::class, 'index']);
            Route::post('/drivers', [ApiAgencyDriverController::class, 'store']);
            Route::get('/drivers/{user}', [ApiAgencyDriverController::class, 'show']);
            Route::put('/drivers/{user}', [ApiAgencyDriverController::class, 'update']);
            Route::delete('/drivers/{user}', [ApiAgencyDriverController::class, 'destroy']);

            // Wallet
            Route::get('/wallet', [ApiAgencyWalletController::class, 'index']);
            Route::get('/wallet/transactions', [ApiAgencyWalletController::class, 'transactions']);

            // Withdrawals
            Route::get('/withdrawals', [ApiAgencyWithdrawalController::class, 'index']);
            Route::post('/withdrawals', [ApiAgencyWithdrawalController::class, 'store']);
            Route::get('/withdrawals/{withdrawal}', [ApiAgencyWithdrawalController::class, 'show']);

            // Promos
            Route::get('/promos/available', [ApiAgencyPromoController::class, 'available']);
            Route::post('/promos/attach', [ApiAgencyPromoController::class, 'attach']);
            Route::delete('/promos/{promo}/detach/{schedule}', [ApiAgencyPromoController::class, 'detach']);

            // Reports
            Route::get('/reports', [ApiAgencyReportController::class, 'index']);
            Route::get('/reports/revenue', [ApiAgencyReportController::class, 'revenue']);
            Route::get('/reports/bookings', [ApiAgencyReportController::class, 'bookings']);
        });

        /*
        |--------------------------------------------------------------------------
        | Driver Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware([\App\Http\Middleware\Api\DriverMiddleware::class])->prefix('driver')->group(function () {
            Route::get('/schedule/today', [ApiDriverScheduleController::class, 'today']);
            Route::get('/schedules/upcoming', [ApiDriverScheduleController::class, 'upcoming']);
            Route::get('/schedules/{schedule}', [ApiDriverScheduleController::class, 'show']);
            Route::post('/schedules/{schedule}/finish', [ApiDriverScheduleController::class, 'finish']);

            Route::get('/schedules/{schedule}/route', [ApiDriverRouteController::class, 'routeDetail']);
            Route::get('/schedules/{schedule}/passengers', [ApiDriverPassengerController::class, 'index']);
            
            // Booking actions (Jemput, Antar, Selesai)
            Route::post('/bookings/{booking}/pickup', [ApiDriverBookingController::class, 'pickupBooking']);
            Route::post('/bookings/{booking}/dropoff', [ApiDriverBookingController::class, 'dropoffBooking']);
            Route::post('/bookings/{booking}/complete', [ApiDriverBookingController::class, 'completeBooking']);
            Route::post('/bookings/{booking}/confirm-cod', [ApiDriverBookingController::class, 'confirmCod']);

            Route::post('/location/update', [ApiDriverLocationController::class, 'update']);
            Route::get('/location/current', [ApiDriverLocationController::class, 'current']);
        });

        /*
        |--------------------------------------------------------------------------
        | Payment Agent Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware([\App\Http\Middleware\Api\PaymentAgentMiddleware::class])->prefix('payment-agent')->group(function () {
            Route::get('/dashboard', [ApiPaymentAgentDashboardController::class, 'index']);

            Route::get('/payments', [ApiPaymentAgentPaymentController::class, 'index']);
            Route::post('/payments/confirm', [ApiPaymentAgentPaymentController::class, 'confirm']);
            Route::get('/payments/{code}', [ApiPaymentAgentPaymentController::class, 'show']);
            Route::get('/payments/history', [ApiPaymentAgentPaymentController::class, 'history']);

            Route::get('/settlements', [ApiPaymentAgentSettlementController::class, 'index']);
            Route::get('/settlements/{settlement}', [ApiPaymentAgentSettlementController::class, 'show']);
            Route::post('/settlements/{settlement}/pay', [ApiPaymentAgentSettlementController::class, 'pay']);

            Route::get('/profile', [ApiPaymentAgentProfileController::class, 'show']);
            Route::put('/profile', [ApiPaymentAgentProfileController::class, 'update']);
            Route::put('/pin', [ApiPaymentAgentProfileController::class, 'updatePin']);
        });

        /*
        |--------------------------------------------------------------------------
        | Admin Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware([\App\Http\Middleware\Api\AdminMiddleware::class])->prefix('admin')->group(function () {
            Route::get('/dashboard', [ApiAdminDashboardController::class, 'index']);
            Route::get('/dashboard/stats', [ApiAdminDashboardController::class, 'stats']);

            // Agencies
            Route::get('/agencies', [ApiAdminAgencyController::class, 'index']);
            Route::get('/agencies/{agency}', [ApiAdminAgencyController::class, 'show']);
            Route::post('/agencies/{agency}/verify', [ApiAdminAgencyController::class, 'verify']);
            Route::post('/agencies/{agency}/reject', [ApiAdminAgencyController::class, 'reject']);
            Route::put('/agencies/{agency}/toggle-active', [ApiAdminAgencyController::class, 'toggleActive']);

            // Customers
            Route::get('/customers', [ApiAdminCustomerController::class, 'index']);
            Route::get('/customers/{user}', [ApiAdminCustomerController::class, 'show']);
            Route::put('/customers/{user}/toggle-active', [ApiAdminCustomerController::class, 'toggleActive']);
            Route::post('/customers/{user}/ban', [ApiAdminCustomerController::class, 'ban']);
            Route::post('/customers/{user}/unban', [ApiAdminCustomerController::class, 'unban']);

            // Drivers
            Route::get('/drivers', [ApiAdminDriverController::class, 'index']);
            Route::get('/drivers/{user}', [ApiAdminDriverController::class, 'show']);

            // Payment Agents
            Route::get('/payment-agents', [ApiAdminPaymentAgentController::class, 'index']);
            Route::get('/payment-agents/{agent}', [ApiAdminPaymentAgentController::class, 'show']);
            Route::post('/payment-agents/{agent}/verify', [ApiAdminPaymentAgentController::class, 'verify']);
            Route::post('/payment-agents/{agent}/reject', [ApiAdminPaymentAgentController::class, 'reject']);
            Route::put('/payment-agents/{agent}/toggle-active', [ApiAdminPaymentAgentController::class, 'toggleActive']);

            // Routes (dengan upload foto)
            Route::get('/routes', [ApiAdminRouteController::class, 'index']);
            Route::post('/routes', [ApiAdminRouteController::class, 'store']);
            Route::get('/routes/{route}', [ApiAdminRouteController::class, 'show']);
            Route::post('/routes/{route}', [ApiAdminRouteController::class, 'update']); // POST for multipart
            Route::delete('/routes/{route}', [ApiAdminRouteController::class, 'destroy']);
            Route::post('/routes/{route}/stops', [ApiAdminRouteController::class, 'addStop']);
            Route::delete('/routes/{route}/stops/{stop}', [ApiAdminRouteController::class, 'removeStop']);

            // Bookings
            Route::get('/bookings', [ApiAdminBookingController::class, 'index']);
            Route::get('/bookings/{booking}', [ApiAdminBookingController::class, 'show']);

            // Withdrawals
            Route::get('/withdrawals', [ApiAdminWithdrawalController::class, 'index']);
            Route::get('/withdrawals/pending', [ApiAdminWithdrawalController::class, 'pending']);
            Route::post('/withdrawals/{withdrawal}/approve', [ApiAdminWithdrawalController::class, 'approve']);
            Route::post('/withdrawals/{withdrawal}/reject', [ApiAdminWithdrawalController::class, 'reject']);

            // Settlements
            Route::get('/settlements', [ApiAdminSettlementController::class, 'index']);
            Route::get('/settlements/pending', [ApiAdminSettlementController::class, 'pending']);
            Route::post('/settlements/{settlement}/verify', [ApiAdminSettlementController::class, 'verify']);

            // Promos
            Route::get('/promos', [ApiAdminPromoController::class, 'index']);
            Route::post('/promos', [ApiAdminPromoController::class, 'store']);
            Route::get('/promos/{promo}', [ApiAdminPromoController::class, 'show']);
            Route::put('/promos/{promo}', [ApiAdminPromoController::class, 'update']);
            Route::delete('/promos/{promo}', [ApiAdminPromoController::class, 'destroy']);

            // Reports
            Route::get('/reports', [ApiAdminReportController::class, 'index']);
            Route::get('/reports/revenue', [ApiAdminReportController::class, 'revenue']);
            Route::get('/reports/bookings', [ApiAdminReportController::class, 'bookings']);
            Route::get('/reports/agencies', [ApiAdminReportController::class, 'agencies']);

            // Settings
            Route::get('/settings', [ApiAdminSettingController::class, 'index']);
            Route::put('/settings', [ApiAdminSettingController::class, 'update']);
        });
    });
});

// End of file