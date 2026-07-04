<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Production + Enrich Data
        $this->call([
            CompleteDataSeeder::class,
//            PlatformSettingSeeder::class,
  //          RouteSeeder::class,
    //        AdditionalRouteSeeder::class,
      //      UserSeeder::class,
        //    PaymentAgentSeeder::class,
          //  ScheduleSeeder::class,
//            PromoSeeder::class,
  //          EnrichVerifiedDataSeeder::class,
    //        WithdrawalSeeder::class,
      //      ReviewSeeder::class,              // ✅ REVIEWS
        //    NotificationSeeder::class,        // ✅ NOTIFICATIONS
            //CompletedDataSeeder::class,       // ✅ COMPLETED BOOKINGS + REVIEWS
            //CompletedBookingSeeder::class,       // ✅ COMPLETED BOOKINGS + REVIEWS
//            PassengerTransferSeeder::class,   // ✅ TRANSFERS
  //          WalletTransactionSeeder::class,   // ✅ WALLET TRANSACTIONS
        ]);

        // Demo Data (only local/staging)
      //  if (app()->environment('local', 'staging')) {
        //    $this->call([
          //      DemoDataSeeder::class,
            //]);
        //}
    }
}