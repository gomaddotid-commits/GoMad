<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #BA1826; color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .header h1 { margin: 0; font-size: 24px; }
        .header .logo { font-size: 32px; font-weight: bold; }
        .content { background: #fff; padding: 30px; border: 1px solid #E5E7EB; border-top: none; }
        .info-card { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .info-card h3 { margin-top: 0; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 1px; }
        .info-card p { margin: 5px 0; font-size: 16px; font-weight: 600; }
        .passenger-list { background: #F9FAFB; border-radius: 8px; padding: 15px; }
        .passenger-item { padding: 8px 0; border-bottom: 1px solid #E5E7EB; }
        .price { font-size: 24px; font-weight: bold; color: #BA1826; text-align: right; }
        .button { display: inline-block; background: #BA1826; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #E5E7EB; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">GO<span style="color: #FFD700;">MAD</span></div>
            <p style="margin: 10px 0 0;">Booking Travel — Door to Door Service</p>
        </div>
        
        <div class="content">
            <h2>🎫 Booking Berhasil!</h2>
            <p>Halo <strong>{{ $booking->customer->name }}</strong>,</p>
            <p>Booking Anda telah berhasil dibuat. Berikut detailnya:</p>
            
            <div class="info-card">
                <h3>Kode Booking</h3>
                <p style="font-family: monospace; font-size: 20px;">{{ $booking->booking_code }}</p>
            </div>
            
            <div class="info-card">
                <h3>Rute</h3>
                <p>{{ $booking->originStop->city_name ?? '-' }} → {{ $booking->destinationStop->city_name ?? '-' }}</p>
            </div>
            
            <div class="info-card">
                <h3>Jadwal</h3>
                <p>{{ $booking->schedule->departure_date->format('d M Y') }} — {{ $booking->schedule->departure_time }}</p>
            </div>
            
            <div class="info-card">
                <h3>Agency</h3>
                <p>{{ $booking->schedule->agency->agency_name ?? '-' }}</p>
            </div>
            
            <div class="info-card">
                <h3>Kendaraan</h3>
                <p style="font-family: monospace;">{{ $booking->schedule->vehicle->plate_number ?? '-' }} — {{ $booking->schedule->vehicle->brand ?? '' }} {{ $booking->schedule->vehicle->model ?? '' }}</p>
            </div>
            
            <div class="passenger-list">
                <h3 style="margin-top: 0; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 1px;">Penumpang ({{ $booking->total_passengers }} orang)</h3>
                @foreach($booking->passengers as $p)
                <div class="passenger-item">
                    <strong>{{ $p->passenger_name }}</strong> — Seat {{ $p->seat_number }}
                </div>
                @endforeach
            </div>
            
            <div class="info-card" style="margin-top: 15px;">
                <h3>📍 Alamat Jemput</h3>
                <p>{{ $booking->pickup_address }}</p>
            </div>
            
            <div class="info-card">
                <h3>🎯 Alamat Tujuan</h3>
                <p>{{ $booking->destination_address }}</p>
            </div>
            
            <div class="price" style="margin-top: 20px;">
                Total: Rp {{ number_format($booking->total_price, 0, ',', '.') }}
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="{{ route('customer.booking.show', $booking) }}" class="button">
                    💳 Bayar Sekarang
                </a>
            </div>
            
            <div class="footer">
                <p>© {{ date('Y') }} GoMad. All rights reserved.</p>
                <p>Email ini dikirim otomatis. Mohon jangan membalas email ini.</p>
            </div>
        </div>
    </div>
</body>
</html>