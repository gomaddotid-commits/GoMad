<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #F9FAFB; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #059669, #10B981); color: white; padding: 40px 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .header .logo { font-size: 36px; font-weight: bold; letter-spacing: -1px; }
        .header .logo span { color: #FFD700; }
        .content { background: #fff; padding: 30px; border: 1px solid #E5E7EB; border-top: none; border-radius: 0 0 12px 12px; }
        .feature-list { list-style: none; padding: 0; }
        .feature-list li { padding: 10px 15px; margin-bottom: 8px; background: #F9FAFB; border-radius: 8px; border: 1px solid #E5E7EB; display: flex; align-items: center; gap: 10px; }
        .feature-list li .icon { font-size: 20px; }
        .cta-button { display: block; background: #059669; color: white; text-align: center; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 16px; margin: 25px 0; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #E5E7EB; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">GO<span>MAD</span></div>
            <h1>Selamat Datang, {{ $user->name }}! 👨‍✈️</h1>
            <p>GoMad Driver Partner</p>
        </div>
        
        <div class="content">
            <p>Halo <strong>{{ $user->name }}</strong>,</p>
            <p>Anda telah terdaftar sebagai <strong>Driver GoMad</strong> 
            @if($user->agencyBelongTo)
                di agency <strong>{{ $user->agencyBelongTo->agency_name }}</strong>
            @endif.</p>
            
            <p>Yang bisa Anda lakukan:</p>
            <ul class="feature-list">
                <li><span class="icon">📅</span> Lihat jadwal perjalanan</li>
                <li><span class="icon">👥</span> Kelola penumpang (jemput & antar)</li>
                <li><span class="icon">💰</span> Konfirmasi pembayaran COD</li>
                <li><span class="icon">📍</span> Lacak perjalanan dengan GPS</li>
                <li><span class="icon">📱</span> Akses via aplikasi mobile</li>
            </ul>
            
            <a href="{{ config('app.url') }}/driver/schedule" class="cta-button">
                🚀 Lihat Jadwal
            </a>
            
            <div class="footer">
                <p>© {{ date('Y') }} GoMad. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>