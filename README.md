Saya paham. Anda butuh **README.md** yang profesional untuk repo GitHub GOMAD.

Saya akan buatkan **README.md lengkap** dengan konten yang sesuai dengan kode yang sudah saya baca (baik website maupun app).

---

## 📄 README.md untuk GOMAD

```markdown
# 🚐 GoMad - Platform Booking Travel Antar Kota di Madura

![GoMad Banner](https://via.placeholder.com/1200x400/DC2626/FFFFFF?text=GoMad+-+Mobilitas+or%C3%A8ng+Madhur%C3%A2)

> **GoMad** adalah platform booking travel antar kota di Madura dengan konsep **door-to-door service**.  
> Dijemput di rumah, diantar ke tujuan. Tersedia untuk rute antar kota di Madura dan sekitarnya.

---

## 📱 **Tentang GoMad**

GoMad menghubungkan **customer**, **agency travel**, **driver**, dan **payment agent (Warung GoMad)** dalam satu ekosistem digital. Dengan GoMad, customer bisa booking travel dengan mudah, agency bisa mengelola operasional, driver mendapatkan jadwal jelas, dan warung bisa menjadi mitra pembayaran.

### 🎯 **Visi**
Mobilitas orèng Madhurâ — menjadi platform mobilitas nomor 1 di Madura.

### ✨ **Fitur Utama**

| Role | Fitur |
|------|-------|
| **Customer** | Cari jadwal, booking tiket, bayar online/cash/COD, e-ticket digital, review |
| **Agency** | Kelola jadwal, kendaraan, driver, booking, dompet digital, penarikan dana |
| **Driver** | Lihat jadwal harian, kelola penumpang (jemput/antar/konfirmasi COD) |
| **Payment Agent** | Konfirmasi pembayaran cash, kelola settlement mingguan |

---

## 🛠️ **Tech Stack**

### Backend (Website)
| Layer | Teknologi |
|-------|-----------|
| Framework | Laravel 11 |
| Database | MySQL |
| Authentication | Laravel Sanctum (API Token) |
| Payment Gateway | Midtrans (Snap + IRIS Disbursement) |
| Notification | WhatsApp (Twilio) + FCM + In-app |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| Maps | Leaflet.js (OpenStreetMap) |

### Mobile App
| Layer | Teknologi |
|-------|-----------|
| Framework | Flutter 3.x |
| State Management | BLoC |
| Networking | Dio + Interceptor |
| Local Storage | SharedPreferences + FlutterSecureStorage |
| UI | Material 3 + Google Fonts (Poppins) |

---

## 📂 **Struktur Repository**

```
gomad/
├── website/                      # Laravel Backend + Web Dashboard
│   ├── app/
│   │   ├── Console/              # Command scheduler (overload, reminder, settlement)
│   │   ├── Enums/                # BookingStatus, PaymentStatus, UserRole, etc.
│   │   ├── Exceptions/           # Custom exceptions
│   │   ├── Helpers/              # BookingCodeGenerator, PaymentCodeGenerator
│   │   ├── Http/
│   │   │   ├── Controllers/      # API + Web Controllers (Admin, Agency, Customer, Driver, PaymentAgent)
│   │   │   ├── Middleware/       # Role-based middleware (Admin, Agency, Driver, PaymentAgent)
│   │   │   └── Resources/        # API Resources
│   │   ├── Models/               # 22+ Models (User, Agency, Booking, Schedule, etc.)
│   │   ├── Providers/            # Service providers
│   │   └── Services/             # Business logic (BookingService, WalletService, etc.)
│   ├── config/                   # Laravel config files
│   ├── database/
│   │   ├── migrations/           # 40+ migration files
│   │   └── seeders/              # Database seeders
│   ├── resources/
│   │   └── views/                # Blade views (Admin, Agency, Customer, Driver, PaymentAgent, Public)
│   ├── routes/
│   │   ├── api.php               # 118+ API endpoints
│   │   └── web.php               # Web routes
│   └── public/                   # Public assets
│
├── app/                          # Flutter Mobile App
│   ├── lib/
│   │   ├── core/
│   │   │   ├── constants/        # ApiEndpoints, AppConstants, ColorConstants
│   │   │   ├── di/               # Dependency Injection (GetIt)
│   │   │   ├── network/          # DioClient, ApiInterceptor
│   │   │   ├── storage/          # SecureStorage, AppPreferences
│   │   │   ├── themes/           # AppTheme, TextStyles
│   │   │   └── utils/            # CurrencyFormatter, DateFormatter, Logger, Result, Validators
│   │   ├── data/
│   │   │   ├── datasources/      # Auth, Booking, Driver, Payment, PaymentAgent, Schedule
│   │   │   ├── models/           # All API models (Auth, Booking, Driver, Payment, Schedule, etc.)
│   │   │   └── repositories/     # Repository implementations
│   │   ├── domain/
│   │   │   └── repositories/     # Abstract repository interfaces
│   │   └── presentation/
│   │       ├── bloc/             # BLoC: Auth, Booking, Driver, Payment, PaymentAgent, Schedule
│   │       ├── screens/          # All screens (Auth, Customer, Driver, PaymentAgent)
│   │       └── widgets/          # Reusable widgets (AppButton, AppTextField, etc.)
│   └── pubspec.yaml              # Flutter dependencies
│
├── .env.example                  # Environment variables template
├── docker-compose.yml            # Docker setup
└── README.md                     # This file
```

---

## 🚀 **Quick Start**

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+
- Flutter 3.x
- Docker (optional)

### 1. Clone Repository
```bash
git clone https://github.com/yourusername/gomad.git
cd gomad
```

### 2. Backend Setup (Laravel)
```bash
cd website

# Install dependencies
composer install
npm install
npm run build

# Environment configuration
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate
php artisan db:seed

# Storage link
php artisan storage:link

# Run server
php artisan serve --port=8000
# API server runs on port 8001
```

### 3. Mobile App Setup (Flutter)
```bash
cd app

# Install dependencies
flutter pub get

# Run the app
flutter run
```

### 4. Environment Variables (.env)
```env
APP_NAME=GoMad
APP_ENV=local
APP_DEBUG=true
APP_URL=http://web.gomad.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gomad
DB_USERNAME=root
DB_PASSWORD=

# Midtrans
MIDTRANS_SERVER_KEY=your_server_key
MIDTRANS_CLIENT_KEY=your_client_key
MIDTRANS_IS_PRODUCTION=false

# Twilio (WhatsApp)
TWILIO_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886

# FCM (Push Notification)
FCM_SERVER_KEY=your_fcm_server_key
```

---

## 📡 **API Documentation**

GoMad menyediakan **118+ API endpoints** dengan 6 role:

| Role | Prefix | Auth |
|------|--------|------|
| Public | `/api/v1` | ❌ |
| Customer | `/api/v1/customer` | ✅ Sanctum |
| Agency | `/api/v1/agency` | ✅ Sanctum + AgencyMiddleware |
| Driver | `/api/v1/driver` | ✅ Sanctum + DriverMiddleware |
| Payment Agent | `/api/v1/payment-agent` | ✅ Sanctum + PaymentAgentMiddleware |
| Admin | `/api/v1/admin` | ✅ Sanctum + AdminMiddleware |

**Authentication:** Bearer Token (Laravel Sanctum)

```bash
curl -X POST /api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

---

## 🎨 **Screenshots**

| Customer Home | Booking Form | Driver Schedule |
|---------------|--------------|-----------------|
| ![Home](https://via.placeholder.com/300x600/DC2626/FFFFFF?text=Customer+Home) | ![Booking](https://via.placeholder.com/300x600/DC2626/FFFFFF?text=Booking+Form) | ![Driver](https://via.placeholder.com/300x600/DC2626/FFFFFF?text=Driver+Schedule) |

| Agency Dashboard | Payment Agent | Admin Panel |
|------------------|---------------|-------------|
| ![Agency](https://via.placeholder.com/300x600/DC2626/FFFFFF?text=Agency+Dashboard) | ![Warung](https://via.placeholder.com/300x600/DC2626/FFFFFF?text=Payment+Agent) | ![Admin](https://via.placeholder.com/300x600/DC2626/FFFFFF?text=Admin+Panel) |

---

## 🔗 **Related Repositories**

| Repository | Description |
|------------|-------------|
| [gomad-website](https://github.com/yourusername/gomad-website) | Laravel Backend + Web Dashboard |
| [gomad-app](https://github.com/yourusername/gomad-app) | Flutter Mobile App |

---

## 🧑‍💻 **Contributing**

Kami terbuka untuk kontribusi! Silakan ikuti langkah-langkah berikut:

1. Fork repository
2. Buat branch fitur (`git checkout -b feature/amazing-feature`)
3. Commit perubahan (`git commit -m 'Add amazing feature'`)
4. Push ke branch (`git push origin feature/amazing-feature`)
5. Buka Pull Request

### Coding Standards
- **Backend**: PSR-12, Laravel Coding Standard
- **Frontend**: Flutter Lints, Dart Format

---

## 📄 **License**

Dilindungi hak cipta. Hak penggunaan sepenuhnya milik **GoMad**.

---

## 📞 **Support**

| Channel | Contact |
|---------|---------|
| Email | support@gomad.id |
| WhatsApp | 0812-3456-7890 |
| Website | [gomad.id](https://gomad.id) |

---

## 🏆 **Credits**

- **Backend Team**: Laravel 11 + MySQL
- **Mobile Team**: Flutter + BLoC
- **Design Team**: Figma + Tailwind CSS
- **Business Team**: GoMad

---

<div align="center">
  <sub>Built with ❤️ for Madura</sub>
  <br>
  <sub>© 2026 GoMad. All rights reserved.</sub>
</div>
```

