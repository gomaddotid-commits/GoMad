<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #10B981; color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .header .logo { font-size: 32px; font-weight: bold; }
        .content { background: #fff; padding: 30px; border: 1px solid #E5E7EB; border-top: none; }
        .price { font-size: 24px; font-weight: bold; color: #10B981; text-align: right; }
        .button { display: inline-block; background: #BA1826; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #E5E7EB; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">GO<span style="color: #FFD700;">MAD</span></div>
            <p style="margin: 10px 0 0;">Pembayaran Dikonfirmasi ✅</p>
        </div>
        
        <div class="content">
            <h2>✅ Pembayaran Berhasil!</h2>
            <p>Halo <strong>{{ $booking->customer->name }}</strong>,</p>
            <p>Pembayaran untuk booking Anda telah dikonfirmasi.</p>
            
            <div style="background: #F9FAFB; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <p><strong>Kode Booking:</strong> <span style="font-family: monospace; font-size: 18px;">{{ $booking->booking_code }}</span></p>
                <p><strong>Rute:</strong> {{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}</p>
                <p><strong>Jadwal:</strong> {{ $booking->schedule->departure_date->format('d M Y') }} — {{ $booking->schedule->departure_time }}</p>
            </div>
            
            <div class="price">
                Total: Rp {{ number_format($booking->total_price, 0, ',', '.') }}
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="{{ route('customer.booking.e-ticket', $booking) }}" class="button">
                    🎫 Lihat E-Ticket
                </a>
            </div>
            
            <div class="footer">
                <p>© {{ date('Y') }} GoMad. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>