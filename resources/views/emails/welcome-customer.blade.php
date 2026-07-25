<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #F9FAFB; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #BA1826, #E42535); color: white; padding: 40px 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .header .logo { font-size: 36px; font-weight: bold; letter-spacing: -1px; }
        .header .logo span { color: #FFD700; }
        .header h1 { margin: 15px 0 5px; font-size: 24px; }
        .header p { margin: 0; font-size: 14px; opacity: 0.9; }
        .content { background: #fff; padding: 30px; border: 1px solid #E5E7EB; border-top: none; border-radius: 0 0 12px 12px; }
        .welcome-text { font-size: 16px; margin-bottom: 25px; color: #555; }
        .feature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
        .feature-card { background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 10px; padding: 20px; text-align: center; }
        .feature-card .icon { font-size: 32px; margin-bottom: 10px; }
        .feature-card h3 { margin: 0 0 5px; font-size: 16px; color: #111; }
        .feature-card p { margin: 0; font-size: 12px; color: #777; }
        .cta-button { display: block; background: #BA1826; color: white; text-align: center; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 16px; margin: 25px 0; }
        .cta-button:hover { background: #8A0F18; }
        .support-box { background: #FEF3C7; border: 1px solid #F59E0B; border-radius: 10px; padding: 15px; margin-top: 20px; }
        .support-box p { margin: 5px 0; font-size: 13px; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #E5E7EB; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">GO<span>MAD</span></div>
            <h1>Selamat Datang, {{ $user->name }}! 🎉</h1>
            <p>Mobilitas orèng Madhurâ — Door to Door Service</p>
        </div>
        
        <div class="content">
            <p class="welcome-text">
                Halo <strong>{{ $user->name }}</strong>,<br><br>
                Terima kasih telah bergabung dengan <strong>GoMad</strong>! Akun Anda telah berhasil dibuat. 
                Kini Anda bisa menikmati berbagai layanan transportasi kami.
            </p>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="icon">🚐</div>
                    <h3>Travel Door to Door</h3>
                    <p>Booking tiket travel antar kota. Dijemput di rumah, diantar ke tujuan.</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🚗</div>
                    <h3>Rental Mobil</h3>
                    <p>Sewa mobil lepas kunci atau dengan supir. Bebas eksplorasi!</p>
                </div>
                <div class="feature-card">
                    <div class="icon">💳</div>
                    <h3>Multi Pembayaran</h3>
                    <p>Bayar online, di Warung GoMad, atau COD ke sopir.</p>
                </div>
                <div class="feature-card">
                    <div class="icon">🎫</div>
                    <h3>E-Ticket Digital</h3>
                    <p>Akses tiket kapan saja. Tanpa cetak, tanpa ribet.</p>
                </div>
            </div>
            
            <a href="{{ config('app.url') }}" class="cta-button">
                🚀 Mulai Booking Sekarang
            </a>
            
            <div class="support-box">
                <p><strong>📞 Butuh bantuan?</strong></p>
                <p>Hubungi kami di <strong>{{ config('gomad.support_phone', '081234567890') }}</strong></p>
                <p>Email: <strong>{{ config('gomad.support_email', 'support@gomad.id') }}</strong></p>
            </div>
            
            <div class="footer">
                <p>© {{ date('Y') }} GoMad. All rights reserved.</p>
                <p>Email ini dikirim otomatis. Mohon jangan membalas email ini.</p>
            </div>
        </div>
    </div>
</body>
</html>