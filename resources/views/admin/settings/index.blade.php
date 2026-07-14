@extends('layouts.admin')

@section('title', 'Pengaturan')
@section('content')

@php
    $settings = \App\Models\PlatformSetting::getAllSettings();

    $sections = [
        'branding' => [
            'title' => '🎨 Branding',
            'icon' => '🎨',
            'description' => 'Atur identitas dan tampilan platform GoMad.',
            'fields' => [
                'app_tagline' => ['label' => 'Tagline Aplikasi', 'type' => 'text', 'hint' => 'Tagline yang muncul di hero section'],
                'app_version' => ['label' => 'Versi Aplikasi', 'type' => 'text', 'hint' => 'Versi saat ini (contoh: 2.0.0)'],
            ],
        ],
        'commission' => [
            'title' => '💰 Komisi & Biaya',
            'icon' => '💰',
            'description' => 'Atur komisi platform, biaya layanan, dan persentase pendapatan.',
            'fields' => [
                'commission_rate' => ['label' => 'Komisi Travel (%)', 'type' => 'pct', 'hint' => 'Komisi dari setiap booking travel'],
                'rental_commission_rate' => ['label' => 'Komisi Rental (%)', 'type' => 'pct', 'hint' => 'Komisi dari setiap booking rental'],
                'warung_commission_rate' => ['label' => 'Komisi Warung (%)', 'type' => 'pct', 'hint' => 'Komisi untuk warung pembayaran'],
                'service_fee' => ['label' => 'Biaya Layanan (Rp)', 'type' => 'rp', 'hint' => 'Biaya tetap per booking travel'],
                'platform_fee_percent' => ['label' => 'Biaya Platform Travel (%)', 'type' => 'pct', 'hint' => 'Biaya platform dari total booking travel'],
                'rental_platform_fee_percent' => ['label' => 'Biaya Platform Rental (%)', 'type' => 'pct', 'hint' => 'Biaya platform dari total booking rental'],
            ],
        ],
        'payment' => [
            'title' => '💳 Pembayaran',
            'icon' => '💳',
            'description' => 'Atur timeout pembayaran, expiry kode bayar, dan prefix.',
            'fields' => [
                'payment_timeout' => ['label' => 'Timeout Midtrans (menit)', 'type' => 'number', 'hint' => 'Batas waktu pembayaran sebelum expired'],
                'payment_code_expiry_hours' => ['label' => 'Expiry Kode Bayar (jam)', 'type' => 'number', 'hint' => 'Batas waktu kode bayar Warung GoMad'],
                'payment_code_prefix' => ['label' => 'Prefix Kode Bayar', 'type' => 'text', 'hint' => 'Prefix kode bayar (default: WM)'],
                'topup_admin_fee' => ['label' => 'Biaya Admin Top Up (Rp)', 'type' => 'rp', 'hint' => 'Biaya admin setiap top up saldo agency'],
            ],
        ],
        'withdrawal' => [
            'title' => '🏦 Penarikan Dana',
            'icon' => '🏦',
            'description' => 'Atur minimal penarikan, biaya admin, dan auto-approve.',
            'fields' => [
                'minimal_withdrawal' => ['label' => 'Minimal Penarikan (Rp)', 'type' => 'rp', 'hint' => 'Saldo minimal yang bisa ditarik'],
                'withdrawal_admin_fee' => ['label' => 'Biaya Admin Penarikan (Rp)', 'type' => 'rp', 'hint' => 'Biaya potongan setiap penarikan'],
                'auto_approve_limit' => ['label' => 'Auto-Approve Limit (Rp)', 'type' => 'rp', 'hint' => 'Batas nominal auto-approve'],
            ],
        ],
        'travel' => [
            'title' => '🚐 Travel',
            'icon' => '🚐',
            'description' => 'Atur operasional modul travel.',
            'fields' => [
                'schedule_min_days' => ['label' => 'Minimal H- Jadwal', 'type' => 'number', 'hint' => 'Hari sebelum keberangkatan untuk buat jadwal'],
                'booking_code_prefix' => ['label' => 'Prefix Kode Booking', 'type' => 'text', 'hint' => 'Prefix kode booking (default: GM)'],
                'max_overload_economy' => ['label' => 'Max Overload Ekonomi', 'type' => 'number', 'hint' => 'Tambahan penumpang di kelas ekonomi'],
                'baggage_economy_kg' => ['label' => 'Batas Bagasi Ekonomi (kg)', 'type' => 'number', 'hint' => 'Maksimal bagasi per orang kelas ekonomi'],
                'baggage_premium_kg' => ['label' => 'Batas Bagasi Premium (kg)', 'type' => 'number', 'hint' => 'Maksimal bagasi per orang kelas premium'],
            ],
        ],
        'rental' => [
            'title' => '🚗 Rental',
            'icon' => '🚗',
            'description' => 'Atur operasional modul rental.',
            'fields' => [
                'rental_min_hours' => ['label' => 'Minimal Jam Sewa', 'type' => 'number', 'hint' => 'Durasi minimal sewa rental (jam)'],
                'rental_max_days' => ['label' => 'Maksimal Hari Sewa', 'type' => 'number', 'hint' => 'Durasi maksimal sewa rental (hari)'],
                'rental_max_advance_days' => ['label' => 'Maksimal Booking di Depan (hari)', 'type' => 'number', 'hint' => 'Maksimal hari ke depan untuk booking rental'],
                'self_drive_min_age' => ['label' => 'Minimal Usia Self-Drive', 'type' => 'number', 'hint' => 'Usia minimal untuk rental lepas kunci'],
            ],
        ],
        'cancellation' => [
            'title' => '🔄 Pembatalan & Refund',
            'icon' => '🔄',
            'description' => 'Atur kebijakan pembatalan dan refund.',
            'fields' => [
                'cancellation_fee_percent' => ['label' => 'Biaya Pembatalan Travel (%)', 'type' => 'pct', 'hint' => 'Biaya pembatalan booking travel'],
                'rental_cancellation_fee_percent' => ['label' => 'Biaya Pembatalan Rental (%)', 'type' => 'pct', 'hint' => 'Biaya pembatalan booking rental'],
                'cancellation_refund_days' => ['label' => 'Refund Diproses (hari kerja)', 'type' => 'number', 'hint' => 'Lama proses refund ke customer'],
                'cancellation_min_hours_before' => ['label' => 'Batas Cancel (jam sebelum)', 'type' => 'number', 'hint' => 'Minimal jam sebelum keberangkatan untuk bisa cancel'],
            ],
        ],
        'finance' => [
            'title' => '📊 Keuangan',
            'icon' => '📊',
            'description' => 'Atur settlement dan keuangan.',
            'fields' => [
                'settlement_day' => [
                    'label' => 'Hari Settlement',
                    'type' => 'select',
                    'options' => ['monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis', 'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu'],
                    'hint' => 'Hari generate tagihan settlement warung',
                ],
            ],
        ],
        'notification' => [
            'title' => '🔔 Notifikasi',
            'icon' => '🔔',
            'description' => 'Atur pengiriman notifikasi WhatsApp dan Push. Tes konfigurasi di bagian bawah.',
            'fields' => [
                'whatsapp_notifications' => ['label' => 'Notifikasi WhatsApp', 'type' => 'toggle', 'hint' => 'Aktifkan pengiriman WhatsApp'],
                'whatsapp_driver' => [
                    'label' => 'WhatsApp Driver',
                    'type' => 'select',
                    'options' => [
                        'log' => '🔇 Log Only (Development)',
                        'baileys' => '🤖 Baileys (Free - Self Hosted)',
                        'fonnte' => '💬 Fonnte (Rp 50k/bulan)',
                        'meta' => '📘 Meta Cloud API (Free 1k/bulan)',
                        'twilio' => '📞 Twilio (Enterprise)',
                    ],
                    'hint' => 'Pilih provider untuk notifikasi WhatsApp',
                ],
                'fonnte_token' => ['label' => 'Fonnte Token', 'type' => 'text', 'hint' => 'Token API dari dashboard Fonnte'],
                'push_notifications' => ['label' => 'Push Notification', 'type' => 'toggle', 'hint' => 'Aktifkan push notification ke aplikasi mobile'],
            ],
        ],
        'support' => [
            'title' => '📞 Support',
            'icon' => '📞',
            'description' => 'Atur kontak support.',
            'fields' => [
                'support_phone' => ['label' => 'Nomor Support', 'type' => 'text', 'hint' => 'Nomor yang ditampilkan di footer'],
                'support_email' => ['label' => 'Email Support', 'type' => 'email', 'hint' => 'Email yang ditampilkan di footer'],
            ],
        ],
        'integration' => [
            'title' => '🔌 Integrasi',
            'icon' => '🔌',
            'description' => 'Atur integrasi pihak ketiga.',
            'fields' => [
                'midtrans_is_production' => ['label' => 'Midtrans Production Mode', 'type' => 'toggle', 'hint' => 'Aktifkan mode production Midtrans'],
                'google_maps_api_key' => ['label' => 'Google Maps API Key', 'type' => 'text', 'hint' => 'API Key untuk Google Maps'],
            ],
        ],
    ];
@endphp

<div x-data="{ 
    activeTab: '{{ request('tab', 'branding') }}', 
    saved: false,
    saveSettings() {
        this.saved = true;
        setTimeout(() => this.saved = false, 3000);
    }
}">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 border-b border-[#E5E5E5] pb-4">
        <div>
            <h1 class="text-2xl font-bold text-[#111111]">Pengaturan Platform</h1>
            <p class="text-sm text-gray-500 font-light mt-1">Kelola semua pengaturan aplikasi GoMad</p>
        </div>
        
        <div x-show="saved" x-cloak 
             class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-[12px] text-sm font-medium flex items-center gap-2">
            <span>✅</span> Pengaturan berhasil disimpan!
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 mb-8 overflow-x-auto pb-2">
        @foreach($sections as $key => $section)
        <button @click="activeTab = '{{ $key }}'" 
                :class="activeTab === '{{ $key }}' ? 'bg-[#C1121F] text-white border-[#C1121F]' : 'bg-white text-gray-600 border-[#E5E5E5] hover:border-[#C1121F]'"
                class="px-4 py-2.5 rounded-[12px] text-sm font-medium border whitespace-nowrap transition flex items-center gap-2">
            <span>{{ $section['icon'] }}</span>
            <span class="hidden sm:inline">{{ $section['title'] }}</span>
        </button>
        @endforeach
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST" @submit="saveSettings()">
        @csrf
        @method('PUT')

        @foreach($sections as $key => $section)
        <div x-show="activeTab === '{{ $key }}'" x-cloak>
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
                <div class="flex items-center gap-3 mb-6 border-b border-[#E5E5E5] pb-4">
                    <span class="text-2xl">{{ $section['icon'] }}</span>
                    <div>
                        <h2 class="text-lg font-bold text-[#111111]">{{ $section['title'] }}</h2>
                        <p class="text-sm text-gray-500 font-light">{{ $section['description'] }}</p>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    @foreach($section['fields'] as $fieldKey => $field)
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                            {{ $field['label'] }}
                        </label>
                        
                        {{-- Toggle --}}
                        @if(($field['type'] ?? 'text') === 'toggle')
                        <label class="relative inline-flex items-center cursor-pointer mt-2">
                            <input type="hidden" name="{{ $fieldKey }}" value="0">
                            <input type="checkbox" name="{{ $fieldKey }}" value="1" 
                                   class="sr-only peer"
                                   {{ ($settings[$fieldKey] ?? '1') == '1' ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-[#C1121F]/20 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#C1121F]"></div>
                            <span class="ms-3 text-sm font-medium text-gray-500">
                                {{ ($settings[$fieldKey] ?? '1') == '1' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </label>
                        
                        {{-- Select --}}
                        @elseif(($field['type'] ?? 'text') === 'select')
                        <select name="{{ $fieldKey }}" 
                                class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition text-sm">
                            @foreach($field['options'] as $val => $label)
                            <option value="{{ $val }}" {{ ($settings[$fieldKey] ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        
                        {{-- Persentase --}}
                        @elseif(($field['type'] ?? 'text') === 'pct')
                        <div class="flex items-center">
                            <input type="number" name="{{ $fieldKey }}" 
                                   value="{{ $settings[$fieldKey] ?? '' }}"
                                   step="0.01" min="0" max="100"
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition font-mono text-lg">
                            <span class="ml-2 text-gray-500 font-mono text-sm">%</span>
                        </div>
                        
                        {{-- Rupiah --}}
                        @elseif(($field['type'] ?? 'text') === 'rp')
                        <div class="flex items-center">
                            <span class="mr-2 text-gray-500 font-mono text-sm">Rp</span>
                            <input type="number" name="{{ $fieldKey }}" 
                                   value="{{ $settings[$fieldKey] ?? '' }}"
                                   min="0"
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition font-mono text-lg">
                        </div>
                        
                        {{-- Number --}}
                        @elseif(($field['type'] ?? 'text') === 'number')
                        <input type="number" name="{{ $fieldKey }}" 
                               value="{{ $settings[$fieldKey] ?? '' }}"
                               min="0"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition font-mono text-lg">
                        
                        {{-- Text/Email --}}
                        @else
                        <input type="{{ $field['type'] ?? 'text' }}" name="{{ $fieldKey }}" 
                               value="{{ $settings[$fieldKey] ?? '' }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                        @endif
                        
                        <p class="text-[10px] text-gray-400 mt-1 font-light">{{ $field['hint'] ?? '' }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- ═══════════════════════════════════ --}}
                {{-- TEST WHATSAPP (Hanya di tab Notifikasi) --}}
                {{-- ═══════════════════════════════════ --}}
                @if($key === 'notification')
                <div class="mt-6 border-t border-[#E5E5E5] pt-6">
                    <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🧪 Test Kirim WhatsApp</h3>
                    <p class="text-[10px] text-gray-400 mb-4 font-light">
                        Kirim pesan test ke nomor WhatsApp untuk memastikan notifikasi berfungsi.
                        @php $activeDriver = config('gomad.whatsapp.driver', 'log'); @endphp
                        @if($activeDriver === 'log')
                            <br><span class="text-yellow-600">⚠️ Driver saat ini: LOG (pesan tidak benar-benar dikirim)</span>
                        @else
                            <br><span class="text-green-600">✅ Driver aktif: {{ strtoupper($activeDriver) }}</span>
                        @endif
                    </p>

                    <div x-data="whatsappTester()" class="space-y-4">
                        {{-- Nomor HP --}}
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                                Nomor WhatsApp <span class="text-[#C1121F]">*</span>
                            </label>
                            <div class="flex gap-2">
                                <div class="flex-1 relative">
                                    <span class="absolute left-0 top-1/2 -translate-y-1/2 text-sm text-gray-400 font-mono">+62</span>
                                    <input type="text" 
                                           x-model="phone" 
                                           placeholder="87806361420"
                                           maxlength="13"
                                           class="w-full pl-10 pr-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition font-mono text-lg">
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 font-light">Contoh: 87806361420 (tanpa 0, tanpa +62)</p>
                        </div>

                        {{-- Pesan --}}
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                                Pesan Test
                            </label>
                            <textarea x-model="message" 
                                      rows="3" 
                                      maxlength="500"
                                      class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] transition text-sm"
                                      placeholder="Ketik pesan test..."></textarea>
                            <div class="flex justify-between mt-1">
                                <p class="text-[10px] text-gray-400 font-light" x-text="message.length + ' karakter'"></p>
                                <div class="flex gap-2">
                                    <button type="button" @click="useTemplate('welcome')" class="text-[10px] text-[#C1121F] hover:underline font-mono">📋 Welcome</button>
                                    <button type="button" @click="useTemplate('booking')" class="text-[10px] text-[#C1121F] hover:underline font-mono">📋 Booking</button>
                                    <button type="button" @click="useTemplate('driver')" class="text-[10px] text-[#C1121F] hover:underline font-mono">📋 Driver</button>
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Kirim --}}
                        <div class="flex gap-3">
                            <button type="button" 
                                    @click="sendTest()" 
                                    :disabled="loading || !phone || !message"
                                    class="px-6 py-2.5 rounded-[12px] font-semibold text-sm transition flex items-center gap-2"
                                    :class="loading || !phone || !message ? 'bg-[#E5E5E5] text-gray-400 cursor-not-allowed' : 'bg-[#C1121F] text-white hover:bg-[#8A0F18]'">
                                <span x-show="!loading">📤</span>
                                <span x-show="loading">⏳</span>
                                <span x-text="loading ? 'Mengirim...' : 'Kirim Test WhatsApp'"></span>
                            </button>
                            
                            <button type="button" 
                                    @click="sendToAdmin()" 
                                    :disabled="loading"
                                    class="px-4 py-2.5 border border-[#E5E5E5] rounded-[12px] text-sm font-medium hover:bg-[#F5F5F5] transition"
                                    x-show="!loading">
                                📱 Kirim ke Saya
                            </button>
                        </div>

                        {{-- Status --}}
                        <div x-show="status" x-cloak
                             :class="statusType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'"
                             class="border rounded-[12px] p-4 text-sm">
                            <div class="flex items-center gap-2">
                                <span x-text="statusType === 'success' ? '✅' : '❌'"></span>
                                <span x-text="status"></span>
                            </div>
                            <div x-show="statusDetail" class="mt-2 text-xs font-mono opacity-75" x-text="statusDetail"></div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endforeach

        {{-- Save Button --}}
        <div class="mt-8 flex gap-4 justify-end">
            <button type="submit" 
                    class="px-8 py-3 bg-[#C1121F] text-white rounded-[12px] font-semibold hover:bg-[#8A0F18] transition text-sm">
                💾 SIMPAN SEMUA PENGATURAN
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
// ═══════════════════════════════════
// WHATSAPP TESTER (Alpine.js)
// ═══════════════════════════════════
function whatsappTester() {
    return {
        phone: '',
        message: '🧪 Test notifikasi WhatsApp dari GoMad!\n\n✅ Jika Anda menerima pesan ini, notifikasi WhatsApp berfungsi dengan baik.\n\n📅 Waktu: ' + new Date().toLocaleString('id-ID') + '\n🔧 Driver: {{ config("gomad.whatsapp.driver", "log") }}',
        loading: false,
        status: '',
        statusType: '',
        statusDetail: '',

        useTemplate(type) {
            const templates = {
                welcome: '🎉 Selamat Datang di GoMad!\n\nNomor WhatsApp Anda telah terhubung dengan platform GoMad.\n\n✅ Booking travel door-to-door\n✅ Sewa mobil lepas kunci / dengan supir\n✅ Bayar online atau di Warung GoMad\n\nSelamat bepergian! 🚐🚗',
                booking: '🎫 Booking GoMad *TEST-1234* berhasil!\n\n📅 Rute: Sumenep → Surabaya\n🕐 14 Jul 2026 08:00\n💰 Total: Rp 150.000\n\nSegera lakukan pembayaran untuk konfirmasi.',
                driver: '👨‍✈️ *Jadwal Baru!*\n\n📅 14 Jul 2026 08:00\n🚗 Rute: Sumenep → Surabaya\n🚙 Kendaraan: M 1234 AB\n\nCek aplikasi GoMad Driver untuk detail.',
            };
            this.message = templates[type] || templates.welcome;
        },

        sendToAdmin() {
            this.phone = '{{ auth()->user()->phone ?? "" }}';
            if (!this.phone) {
                alert('Nomor HP admin tidak ditemukan. Masukkan manual.');
                return;
            }
            // Clean phone for display
            let clean = this.phone.replace(/[^0-9]/g, '');
            if (clean.startsWith('0')) clean = clean.slice(1);
            if (clean.startsWith('62')) clean = clean.slice(2);
            this.phone = clean;
            this.sendTest();
        },

        async sendTest() {
            if (!this.phone || !this.message) return;
            
            this.loading = true;
            this.status = '';
            this.statusDetail = '';
            
            // Bersihkan nomor
            let phone = this.phone.replace(/[^0-9]/g, '');
            if (phone.startsWith('0')) phone = '62' + phone.slice(1);
            if (!phone.startsWith('62')) phone = '62' + phone;

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                
                const response = await fetch('{{ route("admin.test-whatsapp") }}', {                    
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        phone: phone,
                        message: this.message,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.status = data.message;
                    this.statusType = 'success';
                    this.statusDetail = 'Driver: {{ config("gomad.whatsapp.driver", "log") }} | Dikirim: ' + new Date().toLocaleTimeString('id-ID');
                } else {
                    this.status = data.message || 'Gagal mengirim pesan.';
                    this.statusType = 'error';
                }
            } catch (error) {
                this.status = 'Gagal terhubung ke server: ' + error.message;
                this.statusType = 'error';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush
@endsection