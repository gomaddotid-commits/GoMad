<?php
// File: database/seeders/UserSeeder.php
// Deskripsi: Seeder untuk user (admin, agency, customer, driver)

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencyVerification;
use App\Models\AgencyWallet;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        $admin = User::create([
            'name' => 'Admin GoMad',
            'email' => 'admin@gomad.id',
            'phone' => '081111111111',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Agency 1: Travel Jaya Abadi (Verified)
        $agencyUser1 = User::create([
            'name' => 'H. Ahmad Jaya',
            'email' => 'jayaabadi@test.com',
            'phone' => '081222222222',
            'password' => Hash::make('password'),
            'role' => 'agency',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $agency1 = Agency::create([
            'user_id' => $agencyUser1->id,
            'agency_name' => 'Travel Jaya Abadi',
            'slug' => 'travel-jaya-abadi',
            'address' => 'Jl. Trunojoyo No. 45, Sumenep',
            'description' => 'Travel Jaya Abadi adalah penyedia layanan transportasi antar kota di Madura dengan pengalaman lebih dari 10 tahun. Kami melayani rute Sumenep ke berbagai kota di Jawa Timur dan sekitarnya.',
            'founded_year' => 2014,
            'fleet_size' => 5,
            'contact_person' => 'H. Ahmad Jaya',
            'contact_alternate' => '087877777777',
            'email_alternate' => 'cs@jayaabadi.com',
            'services' => json_encode([
                'bagasi_ekstra' => true,
                'charger' => true,
                'air_mineral' => true,
                'wifi' => true,
                'selimut' => false,
            ]),
            'social_media' => json_encode([
                'facebook' => 'https://facebook.com/traveljayaabadi',
                'instagram' => 'https://instagram.com/traveljayaabadi',
                'tiktok' => null,
                'youtube' => null,
            ]),
            'business_hours' => json_encode([
                'senin' => '06:00-20:00',
                'selasa' => '06:00-20:00',
                'rabu' => '06:00-20:00',
                'kamis' => '06:00-20:00',
                'jumat' => '06:00-18:00',
                'sabtu' => '06:00-20:00',
                'minggu' => '07:00-18:00',
            ]),
            'zone_coverage' => json_encode(['Sumenep', 'Pamekasan', 'Bangkalan', 'Surabaya']),
            'is_verified' => true,
            'rating' => 4.50,
            'total_bookings' => 150,
        ]);

        AgencyVerification::create([
            'agency_id' => $agency1->id,
            'verified_by' => $admin->id,
            'status' => 'approved',
            'verified_at' => now(),
        ]);

        AgencyWallet::create([
            'agency_id' => $agency1->id,
            'available_balance' => 2500000,
            'pending_balance' => 500000,
            'total_earned' => 15000000,
            'total_withdrawn' => 12000000,
        ]);

        // Kendaraan Agency 1
        Vehicle::create([
            'agency_id' => $agency1->id,
            'plate_number' => 'M 1234 AB',
            'brand' => 'Toyota',
            'model' => 'Hiace Commuter',
            'year' => 2022,
            'capacity' => 8,
            'type' => 'economy',
            'is_active' => true,
        ]);

        Vehicle::create([
            'agency_id' => $agency1->id,
            'plate_number' => 'M 5678 CD',
            'brand' => 'Isuzu',
            'model' => 'ELF',
            'year' => 2023,
            'capacity' => 8,
            'type' => 'economy',
            'is_active' => true,
        ]);

        // Driver 1 untuk Agency 1
        $driver1 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'supir1@test.com',
            'phone' => '081333333331',
            'password' => Hash::make('password'),
            'role' => 'driver',
            'agency_id' => $agency1->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Driver 2 untuk Agency 1
        $driver2 = User::create([
            'name' => 'Dedi Kurniawan',
            'email' => 'supir2@test.com',
            'phone' => '081333333332',
            'password' => Hash::make('password'),
            'role' => 'driver',
            'agency_id' => $agency1->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Agency 2: Makmur Travel (Unverified)
        $agencyUser2 = User::create([
            'name' => 'Ibu Siti Makmur',
            'email' => 'makmurtravel@test.com',
            'phone' => '081444444444',
            'password' => Hash::make('password'),
            'role' => 'agency',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $agency2 = Agency::create([
            'user_id' => $agencyUser2->id,
            'agency_name' => 'Makmur Travel',
            'slug' => 'makmur-travel',
            'address' => 'Jl. Diponegoro No. 12, Pamekasan',
            'description' => 'Makmur Travel melayani perjalanan antar kota dengan armada nyaman.',
            'founded_year' => 2024,
            'fleet_size' => 2,
            'contact_person' => 'Ibu Siti Makmur',
            'contact_alternate' => '085555555555',
            'zone_coverage' => json_encode(['Pamekasan', 'Sumenep', 'Bangkalan']),
            'is_verified' => false,
        ]);

        AgencyVerification::create([
            'agency_id' => $agency2->id,
            'status' => 'pending',
        ]);

        AgencyWallet::create([
            'agency_id' => $agency2->id,
            'available_balance' => 0,
            'pending_balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
        ]);

        Vehicle::create([
            'agency_id' => $agency2->id,
            'plate_number' => 'M 9999 XY',
            'brand' => 'Suzuki',
            'model' => 'APV',
            'year' => 2021,
            'capacity' => 7,
            'type' => 'economy',
            'is_active' => true,
        ]);

        // Customer 1
        User::create([
            'name' => 'Budi Prasetyo',
            'email' => 'budi@test.com',
            'phone' => '081555555555',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Customer 2
        User::create([
            'name' => 'Ani Rahmawati',
            'email' => 'ani@test.com',
            'phone' => '081666666666',
            'password' => Hash::make('password'),
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}

// End of file