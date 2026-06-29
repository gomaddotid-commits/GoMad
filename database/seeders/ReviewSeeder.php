<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        echo "⭐ GENERATING REVIEWS...\n";
        echo "═══════════════════════════════════════════\n\n";

        $bookings = Booking::where('status', 'paid')
            ->whereDoesntHave('review')
            ->with(['schedule.agency', 'customer'])
            ->get();

        if ($bookings->isEmpty()) {
            echo "⚠️  Tidak ada booking untuk direview\n";
            return;
        }

        $customersReviewed = [];
        $reviewCount = 0;

        $positiveComments = [
            'Pelayanan sangat memuaskan! Sopir ramah dan tepat waktu.',
            'Mobil bersih dan nyaman. Perjalanan menyenangkan.',
            'Recommended banget! Harga terjangkau, fasilitas lengkap.',
            'Sopir sangat profesional dan hati-hati dalam berkendara.',
            'Tepat waktu dan amanah. Pasti booking lagi di sini.',
            'Pelayanan prima! Fasilitas sesuai dengan yang dijanjikan.',
            'Sangat puas dengan pelayanannya. Sopir ramah banget!',
            'Perjalanan lancar, mobil nyaman, sopir sopan. Top!',
            'Booking mudah, pelayanan bagus. Recommended!',
            'Armada bagus dan terawat. Perjalanan jadi nyaman.',
        ];

        $neutralComments = [
            'Cukup baik, tapi AC kurang dingin.',
            'Perjalanan oke, tapi sedikit terlambat 15 menit.',
            'Lumayan, sesuai dengan harga.',
            'Biasa saja, tidak ada yang spesial.',
            'Cukup memuaskan, tapi musik di mobil terlalu keras.',
        ];

        $negativeComments = [
            'Sopir kurang ramah dan ugal-ugalan.',
            'Mobil kotor dan berbau rokok.',
            'Terlambat 1 jam! Tidak tepat waktu.',
            'AC tidak berfungsi dengan baik.',
            'Kebersihan mobil perlu ditingkatkan.',
        ];

        foreach ($bookings as $booking) {
            $customerId = $booking->customer_id;
            $agencyId = $booking->schedule->agency_id;

            if (in_array("{$customerId}-{$agencyId}", $customersReviewed)) {
                continue;
            }

            // 60% positif, 25% netral, 15% negatif
            $ratingRand = rand(1, 100);

            if ($ratingRand <= 60) {
                $rating = rand(4, 5);
                $comment = $positiveComments[array_rand($positiveComments)];
            } elseif ($ratingRand <= 85) {
                $rating = rand(3, 4);
                $comment = $neutralComments[array_rand($neutralComments)];
            } else {
                $rating = rand(1, 2);
                $comment = $negativeComments[array_rand($negativeComments)];
            }

            Review::create([
                'booking_id' => $booking->id,
                'agency_id' => $agencyId,
                'customer_id' => $customerId,
                'rating' => $rating,
                'review' => $comment,
                'created_at' => $booking->created_at->addDays(rand(1, 3)),
                'updated_at' => $booking->created_at->addDays(rand(1, 3)),
            ]);

            $customersReviewed[] = "{$customerId}-{$agencyId}";
            $reviewCount++;

            if ($reviewCount >= 100) break;
        }

        echo "✅ {$reviewCount} Reviews created\n\n";

        echo "📊 RATING BREAKDOWN:\n";
        echo "──────────────────────────────────────────────\n";
        for ($i = 5; $i >= 1; $i--) {
            $count = Review::where('rating', $i)->count();
            $bar = str_repeat('█', $count > 0 ? ceil($count / max(1, $reviewCount) * 20) : 0);
            echo "⭐ {$i}: {$bar} ({$count})\n";
        }
        echo "──────────────────────────────────────────────\n";
    }
}
