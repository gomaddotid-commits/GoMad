@extends('layouts.agency')

@section('title', 'Setup Kendaraan Rental')
@section('content')

@php
    $setting = $vehicle->rentalSetting;
    $isEdit = $setting && $setting->is_available_for_rental;
    
    function parseJson($value) {
        if (is_array($value)) return $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
    
    $customTerms = parseJson($setting->terms_conditions ?? []);
    $customRefund = parseJson($setting->refund_policy ?? []);
    $specs = parseJson($setting->specifications ?? []);
    $reqs = parseJson($setting->requirements ?? []);
@endphp

<div class="max-w-3xl mx-auto">
    <a href="{{ route('agency.rental.vehicles') }}" class="text-[#C1121F] text-sm mb-4 inline-block hover:underline">
        ← Kembali ke Daftar Kendaraan
    </a>

    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-20 h-16 bg-[#F5F5F5] rounded-[12px] overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                @if($vehicle->vehicle_image)
                <img src="{{ $vehicle->vehicle_image }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center text-2xl">🚗</div>
                @endif
            </div>
            <div>
                <h1 class="text-xl font-bold text-[#111111]">{{ $vehicle->brand }} {{ $vehicle->model }}</h1>
                <p class="text-sm text-gray-500 font-mono">{{ $vehicle->plate_number }}</p>
                <p class="text-xs text-gray-400 font-light">{{ $vehicle->year }} • {{ $vehicle->capacity }} seat • {{ ucfirst($vehicle->type) }}</p>
            </div>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[12px] mb-6 text-sm">
            @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
        </div>
        @endif

        <form action="{{ route('agency.rental.vehicle-setup.save', $vehicle) }}" method="POST">
            @csrf

            {{-- Status Aktif --}}
            <div class="mb-6 p-4 bg-[#F5F5F5] rounded-[12px] border border-[#E5E5E5]">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_available_for_rental" value="0">
                    <input type="checkbox" name="is_available_for_rental" value="1" 
                           class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                           {{ $isEdit ? 'checked' : '' }}>
                    <div>
                        <span class="font-semibold text-[#111111]">Tersedia untuk Rental</span>
                        <p class="text-xs text-gray-500 font-light">Aktifkan agar customer bisa menyewa kendaraan ini</p>
                    </div>
                </label>
            </div>

            {{-- Deskripsi --}}
            <div class="mb-6">
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Deskripsi Kendaraan</label>
                <textarea name="description" rows="3" 
                          class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] transition"
                          placeholder="Deskripsikan kendaraan Anda...">{{ old('description', $setting->description ?? '') }}</textarea>
            </div>

            {{-- Spesifikasi --}}
            <div class="mb-6">
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">Spesifikasi</label>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 font-light">Transmisi</label>
                        <select name="specifications[transmission]" class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] text-sm">
                            <option value="manual" {{ ($specs['transmission'] ?? '') == 'manual' ? 'selected' : '' }}>Manual</option>
                            <option value="automatic" {{ ($specs['transmission'] ?? '') == 'automatic' ? 'selected' : '' }}>Automatic</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 font-light">Bahan Bakar</label>
                        <select name="specifications[fuel]" class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] text-sm">
                            <option value="bensin" {{ ($specs['fuel'] ?? '') == 'bensin' ? 'selected' : '' }}>Bensin</option>
                            <option value="solar" {{ ($specs['fuel'] ?? '') == 'solar' ? 'selected' : '' }}>Solar</option>
                            <option value="electric" {{ ($specs['fuel'] ?? '') == 'electric' ? 'selected' : '' }}>Electric</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-4 mt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="specifications[ac]" value="1" class="w-4 h-4 rounded border-[#E5E5E5] text-[#C1121F]" {{ ($specs['ac'] ?? true) ? 'checked' : '' }}>
                            <span class="text-sm text-[#111111]">AC</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="specifications[music]" value="1" class="w-4 h-4 rounded border-[#E5E5E5] text-[#C1121F]" {{ ($specs['music'] ?? true) ? 'checked' : '' }}>
                            <span class="text-sm text-[#111111]">Audio/Music</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Harga --}}
            <div class="mb-6 border-t border-[#E5E5E5] pt-6">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">💰 Harga Sewa</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Harga per Hari (Rp)</label>
                        <input type="number" name="price_per_day" value="{{ old('price_per_day', $setting->price_per_day ?? '') }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" placeholder="300000" min="0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Harga per Jam (Rp)</label>
                        <input type="number" name="price_per_hour" value="{{ old('price_per_hour', $setting->price_per_hour ?? '') }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" placeholder="50000" min="0">
                    </div>
                </div>
            </div>

            {{-- Tipe Rental --}}
            <div class="mb-6 border-t border-[#E5E5E5] pt-6">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🚗 Tipe Sewa</h3>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                        <input type="checkbox" name="allow_self_drive" value="1" class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]" {{ ($setting->allow_self_drive ?? false) ? 'checked' : '' }}>
                        <div><span class="font-semibold text-[#111111]">🚗 Lepas Kunci (Self Drive)</span><p class="text-xs text-gray-500 font-light">Customer menyetir sendiri. Wajib KTP & SIM.</p></div>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                        <input type="checkbox" name="allow_with_driver" value="1" class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]" {{ ($setting->allow_with_driver ?? true) ? 'checked' : '' }} onchange="toggleDriverFee()">
                        <div><span class="font-semibold text-[#111111]">👨‍✈️ Dengan Supir</span><p class="text-xs text-gray-500 font-light">Termasuk supir profesional dari agency.</p></div>
                    </label>
                    <div id="driverFeeSection" class="grid grid-cols-2 gap-4 pl-8 {{ ($setting->allow_with_driver ?? true) ? '' : 'hidden' }}">
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Biaya Supir/Hari (Rp)</label>
                            <input type="number" name="driver_fee_per_day" value="{{ old('driver_fee_per_day', $setting->driver_fee_per_day ?? '') }}"
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" placeholder="100000" min="0">
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Biaya Supir/Jam (Rp)</label>
                            <input type="number" name="driver_fee_per_hour" value="{{ old('driver_fee_per_hour', $setting->driver_fee_per_hour ?? '') }}"
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" placeholder="20000" min="0">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Lokasi Pengambilan --}}
            <div class="border-t border-[#E5E5E5] pt-6 mt-6">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">📍 Lokasi Pengambilan Mobil</h3>
                
                <div class="mb-4 p-4 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px]">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="use_agency_address" value="0">
                        <input type="checkbox" name="use_agency_address" value="1" id="useAgencyAddress"
                               class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                               {{ ($setting->use_agency_address ?? true) ? 'checked' : '' }} onchange="togglePickupLocation()">
                        <div>
                            <span class="font-semibold text-[#111111] text-sm">Gunakan Alamat Agency</span>
                            <p class="text-xs text-gray-500 font-light mt-1">{{ auth()->user()->agency->address ?? 'Alamat agency belum diatur' }}</p>
                        </div>
                    </label>
                </div>
                
                <div id="customPickupLocation" class="{{ ($setting->use_agency_address ?? true) ? 'hidden' : '' }}">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Alamat Pengambilan Kustom</label>
                            <textarea name="pickup_location" rows="2" 
                                      class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] transition text-sm"
                                      placeholder="Alamat lengkap pengambilan mobil...">{{ old('pickup_location', $setting->pickup_location ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Link Google Maps</label>
                            <input type="url" name="pickup_maps_link" value="{{ old('pickup_maps_link', $setting->pickup_maps_link ?? '') }}"
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition text-sm"
                                   placeholder="https://maps.google.com/?q=...">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Syarat Penyewa --}}
            <div class="mb-6 border-t border-[#E5E5E5] pt-6">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">📋 Syarat Penyewa</h3>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                        <input type="checkbox" name="requirements[ktp]" value="1" class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F]" {{ ($reqs['ktp'] ?? true) ? 'checked' : '' }}>
                        <div><span class="font-semibold text-[#111111]">Wajib KTP</span></div>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                        <input type="checkbox" name="requirements[sim]" value="1" class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F]" {{ ($reqs['sim'] ?? true) ? 'checked' : '' }}>
                        <div><span class="font-semibold text-[#111111]">Wajib SIM</span></div>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                        <input type="checkbox" name="requirements[npwp]" value="1" class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F]" {{ ($reqs['npwp'] ?? false) ? 'checked' : '' }}>
                        <div><span class="font-semibold text-[#111111]">Wajib NPWP</span></div>
                    </label>
                    <div class="p-3 border border-[#E5E5E5] rounded-[12px]">
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Minimal Usia</label>
                        <input type="number" name="requirements[min_age]" value="{{ old('requirements.min_age', $reqs['min_age'] ?? 21) }}"
                               class="w-32 px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" min="17" max="65">
                        <span class="text-xs text-gray-400 ml-2 font-light">tahun</span>
                    </div>
                </div>
            </div>

            {{-- Syarat & Ketentuan --}}
            <div class="border-t border-[#E5E5E5] pt-6 mt-6">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">📄 Syarat & Ketentuan Rental</h3>
                
                <div class="mb-4 p-4 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px]">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="use_system_terms" value="0">
                        <input type="checkbox" name="use_system_terms" value="1" id="useSystemTerms"
                               class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                               {{ ($setting->use_system_terms ?? true) ? 'checked' : '' }} onchange="toggleTermsFields()">
                        <div>
                            <span class="font-semibold text-[#111111] text-sm">Gunakan Syarat & Ketentuan Default Sistem</span>
                            <p class="text-xs text-gray-500 font-light mt-1">Jika dicentang, sistem akan menampilkan syarat & ketentuan standar GoMad Rental.</p>
                        </div>
                    </label>
                </div>
                
                <div id="systemTermsPreview" class="mb-4 bg-blue-50 border border-blue-200 rounded-[12px] p-4 {{ ($setting->use_system_terms ?? true) ? '' : 'hidden' }}">
                    <h4 class="font-mono uppercase tracking-wider text-[10px] font-bold text-blue-800 mb-2">📋 Syarat & Ketentuan Sistem:</h4>
                    <ol class="list-decimal list-inside text-xs text-blue-700 space-y-1 font-light">
                        <li>Penyewa wajib memiliki KTP dan SIM yang masih berlaku.</li>
                        <li>Kendaraan hanya boleh digunakan untuk keperluan yang sah sesuai hukum Indonesia.</li>
                        <li>Penyewa bertanggung jawab penuh atas kerusakan, kehilangan, atau kecelakaan.</li>
                        <li>Kendaraan harus dikembalikan dalam kondisi bersih dan tangki bensin terisi penuh.</li>
                        <li>Keterlambatan pengembalian akan dikenakan denda sesuai ketentuan agency.</li>
                        <li>Penyewa dilarang meminjamkan atau menyewakan kembali kendaraan.</li>
                        <li>Segala pelanggaran lalu lintas menjadi tanggung jawab penyewa.</li>
                    </ol>
                </div>
                
                <div id="customTermsFields" class="{{ ($setting->use_system_terms ?? true) ? 'hidden' : '' }}">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">Syarat & Ketentuan Kustom <span class="text-xs text-gray-400 font-light">(Minimal 3)</span></label>
                    <div id="termsContainer" class="space-y-2">
                        @php $termsCount = max(3, count($customTerms)); @endphp
                        @for ($i = 0; $i < $termsCount; $i++)
                            @php $termValue = $customTerms[$i] ?? ''; @endphp
                            <div class="flex gap-2 items-start">
                                <span class="text-sm text-gray-400 mt-2 font-mono">{{ $i + 1 }}.</span>
                                <input type="text" name="terms_conditions[]" value="{{ old('terms_conditions.'.$i, $termValue) }}"
                                       class="flex-1 px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition text-sm"
                                       placeholder="Masukkan syarat & ketentuan...">
                                <button type="button" onclick="removeTerm(this)" class="text-red-500 hover:text-red-700 text-sm mt-2 {{ $termsCount <= 3 ? 'hidden' : '' }}">✕</button>
                            </div>
                        @endfor
                    </div>
                    <button type="button" onclick="addTerm()" class="mt-2 text-[#C1121F] text-sm hover:underline font-medium">+ Tambah Syarat</button>
                </div>
            </div>

            {{-- Kebijakan Refund --}}
            <div class="border-t border-[#E5E5E5] pt-6 mt-6">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🔄 Kebijakan Pembatalan & Refund</h3>
                
                <div class="mb-4 p-4 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px]">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="use_system_refund" value="0">
                        <input type="checkbox" name="use_system_refund" value="1" id="useSystemRefund"
                               class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                               {{ ($setting->use_system_refund ?? true) ? 'checked' : '' }} onchange="toggleRefundFields()">
                        <div>
                            <span class="font-semibold text-[#111111] text-sm">Gunakan Kebijakan Refund Default Sistem</span>
                            <p class="text-xs text-gray-500 font-light mt-1">Jika dicentang, sistem akan menampilkan kebijakan refund standar GoMad.</p>
                        </div>
                    </label>
                </div>
                
                <div id="systemRefundPreview" class="mb-4 bg-orange-50 border border-orange-200 rounded-[12px] p-4 {{ ($setting->use_system_refund ?? true) ? '' : 'hidden' }}">
                    <h4 class="font-mono uppercase tracking-wider text-[10px] font-bold text-orange-800 mb-2">🔄 Kebijakan Refund Sistem:</h4>
                    <ol class="list-decimal list-inside text-xs text-orange-700 space-y-1 font-light">
                        <li>Pembatalan sebelum pembayaran: Tidak dikenakan biaya.</li>
                        <li>Pembatalan setelah pembayaran: Dikenakan biaya 25% dari total sewa.</li>
                        <li>Refund akan diproses dalam 1-14 hari kerja ke rekening yang terdaftar.</li>
                        <li>Pembatalan setelah mobil diambil: Tidak dapat dibatalkan.</li>
                        <li>Force majeure: kebijakan khusus berlaku.</li>
                    </ol>
                </div>
                
                <div id="customRefundFields" class="{{ ($setting->use_system_refund ?? true) ? 'hidden' : '' }}">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">Kebijakan Refund Kustom <span class="text-xs text-gray-400 font-light">(Minimal 3)</span></label>
                    <div id="refundContainer" class="space-y-2">
                        @php $refundCount = max(3, count($customRefund)); @endphp
                        @for ($i = 0; $i < $refundCount; $i++)
                            @php $refundValue = $customRefund[$i] ?? ''; @endphp
                            <div class="flex gap-2 items-start">
                                <span class="text-sm text-gray-400 mt-2 font-mono">{{ $i + 1 }}.</span>
                                <input type="text" name="refund_policy[]" value="{{ old('refund_policy.'.$i, $refundValue) }}"
                                       class="flex-1 px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition text-sm"
                                       placeholder="Masukkan kebijakan refund...">
                                <button type="button" onclick="removeRefund(this)" class="text-red-500 hover:text-red-700 text-sm mt-2 {{ $refundCount <= 3 ? 'hidden' : '' }}">✕</button>
                            </div>
                        @endfor
                    </div>
                    <button type="button" onclick="addRefund()" class="mt-2 text-[#C1121F] text-sm hover:underline font-medium">+ Tambah Kebijakan</button>
                </div>
            </div>

            <button type="submit" class="w-full btn-gomad-primary py-3 rounded-[12px] font-semibold text-lg mt-6">
                💾 SIMPAN SETUP RENTAL
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleDriverFee() { var s = document.getElementById('driverFeeSection'); var cb = document.querySelector('input[name="allow_with_driver"]'); if (s && cb) s.classList.toggle('hidden', !cb.checked); }
function togglePickupLocation() { var use = document.getElementById('useAgencyAddress').checked; document.getElementById('customPickupLocation').classList.toggle('hidden', use); }
function toggleTermsFields() { var use = document.getElementById('useSystemTerms').checked; document.getElementById('systemTermsPreview').classList.toggle('hidden', !use); document.getElementById('customTermsFields').classList.toggle('hidden', use); }
function toggleRefundFields() { var use = document.getElementById('useSystemRefund').checked; document.getElementById('systemRefundPreview').classList.toggle('hidden', !use); document.getElementById('customRefundFields').classList.toggle('hidden', use); }

function addTerm() {
    var c = document.getElementById('termsContainer'), items = c.querySelectorAll('.flex'), count = items.length;
    var div = document.createElement('div'); div.className = 'flex gap-2 items-start';
    div.innerHTML = '<span class="text-sm text-gray-400 mt-2 font-mono">'+(count+1)+'.</span><input type="text" name="terms_conditions[]" class="flex-1 px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition text-sm" placeholder="Masukkan syarat..."><button type="button" onclick="removeTerm(this)" class="text-red-500 hover:text-red-700 text-sm mt-2">✕</button>';
    c.appendChild(div);
}
function removeTerm(btn) { var c = document.getElementById('termsContainer'); if (c.querySelectorAll('.flex').length > 3) { btn.closest('.flex').remove(); updateTermNumbers(); } }
function updateTermNumbers() { document.querySelectorAll('#termsContainer .flex').forEach((item, i) => { var s = item.querySelector('span'); if (s) s.textContent = (i+1)+'.'; }); }

function addRefund() {
    var c = document.getElementById('refundContainer'), items = c.querySelectorAll('.flex'), count = items.length;
    var div = document.createElement('div'); div.className = 'flex gap-2 items-start';
    div.innerHTML = '<span class="text-sm text-gray-400 mt-2 font-mono">'+(count+1)+'.</span><input type="text" name="refund_policy[]" class="flex-1 px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition text-sm" placeholder="Masukkan kebijakan..."><button type="button" onclick="removeRefund(this)" class="text-red-500 hover:text-red-700 text-sm mt-2">✕</button>';
    c.appendChild(div);
}
function removeRefund(btn) { var c = document.getElementById('refundContainer'); if (c.querySelectorAll('.flex').length > 3) { btn.closest('.flex').remove(); updateRefundNumbers(); } }
function updateRefundNumbers() { document.querySelectorAll('#refundContainer .flex').forEach((item, i) => { var s = item.querySelector('span'); if (s) s.textContent = (i+1)+'.'; }); }

document.addEventListener('DOMContentLoaded', function() { toggleDriverFee(); togglePickupLocation(); toggleTermsFields(); toggleRefundFields(); });
</script>
@endpush
@endsection