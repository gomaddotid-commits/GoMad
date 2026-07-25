<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #EF4444; color: white; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #E5E7EB; border-top: none; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #E5E7EB; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>❌ Booking Dibatalkan</h2>
        </div>
        
        <div class="content">
            <p>Halo <strong>{{ $booking->customer->name }}</strong>,</p>
            <p>Booking <strong>{{ $booking->booking_code }}</strong> telah dibatalkan.</p>
            <p><strong>Alasan:</strong> {{ $reason }}</p>
            
            @if($booking->payment && $booking->cancellation_refund > 0)
            <div style="background: #FEF3C7; border: 1px solid #F59E0B; border-radius: 8px; padding: 15px; margin: 20px 0;">
                <p><strong>💰 Refund:</strong> Rp {{ number_format($booking->cancellation_refund, 0, ',', '.') }}</p>
                <p style="font-size: 12px; color: #92400E;">Refund akan diproses dalam 1-14 hari kerja.</p>
            </div>
            @endif
            
            <div class="footer">
                <p>© {{ date('Y') }} GoMad. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>