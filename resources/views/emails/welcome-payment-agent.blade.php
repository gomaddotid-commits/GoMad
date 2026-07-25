<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #F9FAFB; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #D97706, #F59E0B); color: white; padding: 40px 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .header .logo { font-size: 36px; font-weight: bold; letter-spacing: -1px; }
        .header .logo span { color: #FFF; }
        .content { background: #fff; padding: 30px; border: 1px solid #E5E7EB; border-top: none; border-radius: 0 0 12px 12px; }
        .commission-box { background: #FEF3C7; border: 2px solid #F59E0B; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .commission-box .rate { font-size: 48px; font-weight: bold; color: #D97706; }
        .steps { list-style: none; padding: 0; counter-reset: step; }
        .steps li { counter-increment: step; padding: 12px 12px 12px 50px; position: relative; margin-bottom: 8px; background: #F9FAFB; border-radius: 8px; border: 1px solid #E5E7EB; }
        .steps li::before { content: counter(step); position: absolute; left: 12px; top: 12px; width: 24px; height: 24px; background: #D97706; color: white; border-radius: 50%; text-align: center; line-height: 24px; font-weight: bold; font-size: 12px; }
        .cta-button { display: block; background: #D97706; color: white; text-align: center; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 16px; margin: 25px 0; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #E5E7EB; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">GO<span>MAD</span></div>
            <h1>Selamat Datang, {{ $agent->agent_name }}! 🏪</h1>
            <p>GoMad Warung Partner</p>
        </div>
        
        <div class="content">
            <p>Halo <strong>{{ $user->name }}</strong>,</p>
            <p>Selamat bergabung sebagai <strong>Mitra Warung GoMad</strong>! Warung Anda akan menjadi tempat pembayaran resmi GoMad.</p>
            
            <div class="commission-box">
                <p style="margin: 0; font-size: 14px;">Komisi per Transaksi</p>
                <div class="rate">{{ $agent->commission_rate }}%</div>
                <p style="margin: 5px 0 0; font-size: 13px;">Dari setiap pembayaran yang dikonfirmasi</p>
            </div>
            
            <p>Langkah selanjutnya:</p>
            <ol class="steps">
                <li>Lengkapi profil warung</li>
                <li>Tunggu verifikasi admin (1-3 hari kerja)</li>
                <li>Setelah diverifikasi, Anda bisa menerima pembayaran</li>
            </ol>
            
            <a href="{{ config('app.url') }}/payment-agent/dashboard" class="cta-button">
                🚀 Ke Dashboard Warung
            </a>
            
            <div class="footer">
                <p>© {{ date('Y') }} GoMad. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>