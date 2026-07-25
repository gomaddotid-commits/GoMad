<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Montserrat', Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #F9FAFB; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #1E40AF, #3B82F6); color: white; padding: 40px 30px; text-align: center; border-radius: 12px 12px 0 0; }
        .header .logo { font-size: 36px; font-weight: bold; letter-spacing: -1px; }
        .header .logo span { color: #FFD700; }
        .header h1 { margin: 15px 0 5px; font-size: 22px; }
        .content { background: #fff; padding: 30px; border: 1px solid #E5E7EB; border-top: none; border-radius: 0 0 12px 12px; }
        .steps { list-style: none; padding: 0; counter-reset: step; }
        .steps li { counter-increment: step; padding: 15px 15px 15px 60px; position: relative; margin-bottom: 10px; background: #F9FAFB; border-radius: 10px; border: 1px solid #E5E7EB; }
        .steps li::before { content: counter(step); position: absolute; left: 15px; top: 15px; width: 30px; height: 30px; background: #1E40AF; color: white; border-radius: 50%; text-align: center; line-height: 30px; font-weight: bold; font-size: 14px; }
        .steps li h3 { margin: 0 0 5px; font-size: 16px; }
        .steps li p { margin: 0; font-size: 13px; color: #777; }
        .cta-button { display: block; background: #1E40AF; color: white; text-align: center; padding: 15px 30px; text-decoration: none; border-radius: 10px; font-weight: bold; font-size: 16px; margin: 25px 0; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #E5E7EB; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">GO<span>MAD</span></div>
            <h1>Selamat Datang, {{ $agency->agency_name }}! 🏢</h1>
            <p>GoMad Agency Partner</p>
        </div>
        
        <div class="content">
            <p>Halo <strong>{{ $user->name }}</strong>,</p>
            <p>Selamat bergabung sebagai <strong>Agency Partner GoMad</strong>! Berikut langkah untuk memulai:</p>
            
            <ol class="steps">
                <li>
                    <h3>Lengkapi Profil Agency</h3>
                    <p>Isi data agency, upload logo, cover image, dan dokumen pendukung.</p>
                </li>
                <li>
                    <h3>Upload Dokumen Verifikasi</h3>
                    <p>Upload surat izin usaha atau dokumen legalitas agency Anda (format PDF).</p>
                </li>
                <li>
                    <h3>Tambah Kendaraan & Driver</h3>
                    <p>Daftarkan armada dan driver Anda untuk mulai beroperasi.</p>
                </li>
                <li>
                    <h3>Buat Jadwal Perjalanan</h3>
                    <p>Buka rute, tentukan harga, dan terima booking dari customer.</p>
                </li>
                <li>
                    <h3>Setup Kendaraan Rental</h3>
                    <p>Aktifkan fitur rental untuk menambah pendapatan.</p>
                </li>
            </ol>
            
            <a href="{{ config('app.url') }}/agency/dashboard" class="cta-button">
                🚀 Ke Dashboard Agency
            </a>
            
            <p style="font-size: 13px; color: #777; text-align: center;">
                Admin akan memverifikasi agency Anda dalam <strong>1-3 hari kerja</strong>.
            </p>
            
            <div class="footer">
                <p>© {{ date('Y') }} GoMad. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>