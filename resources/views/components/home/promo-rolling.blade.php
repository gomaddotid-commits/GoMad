@props(['promos' => []])

@php
    $promoData = $promos->map(function($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'image' => $p->image_url,
            'module' => $p->module,
            'module_label' => $p->module_label,
            'type' => $p->type,
            'period' => $p->start_date->format('d M') . ' - ' . $p->end_date->format('d M Y'),
            'discount_text' => $p->module == 'rental' && ($p->rental_discount_type ?? 'percent') == 'fixed' 
                ? 'Rp ' . number_format($p->rental_discount_amount, 0, ',', '.')
                : $p->discount_percent . '% OFF',
        ];
    })->values()->toArray();
@endphp

@if(!empty($promoData))
<section class="section py-8 md:py-12 container-magazine overflow-hidden" 
         x-data="promoRolling({{ json_encode($promoData) }})" 
         x-init="initPromoRolling()"
         x-cloak>
    
    {{-- ✅ SKELETON LOADING --}}
    <div x-show="loading" x-cloak>
        <div class="flex items-center gap-3 md:gap-4 mb-8 md:mb-12">
            <div class="h-px w-8 md:w-12 bg-gray-200"></div>
            <div class="h-6 md:h-8 bg-gray-200 rounded w-32 md:w-48 animate-pulse"></div>
        </div>
        <div class="relative max-w-3xl mx-auto">
            <div class="bg-white border border-[#E5E7EB] rounded-[12px] shadow-gomad animate-pulse" style="height: 300px; md:height: 420px;">
                <div class="w-full h-full bg-gray-200 rounded-[12px]"></div>
            </div>
        </div>
    </div>
    
    {{-- ✅ KONTEN ASLI --}}
    <div x-show="!loading" x-cloak>
        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3 md:gap-4 mb-6 md:mb-8 lg:mb-12">
            <div class="flex items-center gap-2 md:gap-4">
                <div class="h-px w-6 md:w-12 bg-[#BA1826]"></div>
                <h2 class="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold tracking-tight text-[#111827]">Promo Spesial</h2>
            </div>
            
            {{-- Controls --}}
            <div class="flex items-center gap-1 md:gap-2 flex-wrap">
                <button @click="prevPromo()" 
                        class="w-8 h-8 md:w-10 md:h-10 rounded-full border border-[#E5E7EB] hover:border-[#BA1826] hover:bg-[#BA1826]/5 transition flex items-center justify-center text-[#111827] hover:text-[#BA1826]"
                        aria-label="Promo sebelumnya">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                    </svg>
                </button>
                
                <button @click="togglePlay()" 
                        class="w-8 h-8 md:w-10 md:h-10 rounded-full border border-[#E5E7EB] hover:border-[#BA1826] hover:bg-[#BA1826]/5 transition flex items-center justify-center text-[#111827] hover:text-[#BA1826]"
                        :class="isPlaying ? 'text-[#BA1826]' : 'text-[#111827]'"
                        aria-label="Toggle auto-rolling">
                    <svg x-show="isPlaying" class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <svg x-show="!isPlaying" class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
                
                <button @click="nextPromo()" 
                        class="w-8 h-8 md:w-10 md:h-10 rounded-full border border-[#E5E7EB] hover:border-[#BA1826] hover:bg-[#BA1826]/5 transition flex items-center justify-center text-[#111827] hover:text-[#BA1826]"
                        aria-label="Promo berikutnya">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7 7"/>
                    </svg>
                </button>
                
                {{-- Indicator Dots --}}
                <div class="flex gap-1 md:gap-1.5 ml-1 md:ml-3">
                    <template x-for="(_, index) in promoItems" :key="index">
                        <button @click="goToPromo(index)" 
                                class="w-2 h-2 md:w-2.5 md:h-2.5 rounded-full transition-all duration-300"
                                :class="currentPromoIndex === index ? 'w-4 md:w-8 bg-[#BA1826]' : 'bg-[#D1D5DB] hover:bg-[#9CA3AF]'"
                                :aria-label="'Go to promo ' + (index + 1)">
                        </button>
                    </template>
                    <div x-show="promoItems.length === 0" class="flex gap-1 md:gap-1.5">
                        <span class="w-2 h-2 md:w-2.5 md:h-2.5 rounded-full bg-gray-200 animate-pulse"></span>
                        <span class="w-2 h-2 md:w-2.5 md:h-2.5 rounded-full bg-gray-200 animate-pulse"></span>
                        <span class="w-2 h-2 md:w-2.5 md:h-2.5 rounded-full bg-gray-200 animate-pulse"></span>
                        <span class="w-2 h-2 md:w-2.5 md:h-2.5 rounded-full bg-gray-200 animate-pulse"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Promo Card --}}
        <div class="relative max-w-3xl mx-auto">
            <div class="relative overflow-hidden rounded-[12px] bg-white border border-[#E5E7EB] shadow-gomad" style="height: 300px; md:height: 420px;">
                <div class="relative w-full h-full overflow-hidden">
                    <template x-for="(promo, index) in promoItems" :key="index">
                        <div x-show="currentPromoIndex === index"
                             x-transition:enter="transition-all duration-500 ease-in-out"
                             x-transition:enter-start="opacity-0 translate-y-full"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition-all duration-500 ease-in-out"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 -translate-y-full"
                             class="absolute inset-0">
                            <a href="{{ auth()->check() ? route('search') : route('login') }}" 
                               class="block w-full h-full group">
                                <div class="relative w-full h-full">
                                    <div class="w-full h-full overflow-hidden bg-[#F9FAFB]">
                                        <template x-if="promo.image">
                                            <img :src="promo.image" :alt="promo.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                                        </template>
                                        <template x-if="!promo.image">
                                            <div class="w-full h-full bg-gradient-to-br from-[#BA1826]/10 to-[#BA1826]/5 flex items-center justify-center">
                                                <span class="text-5xl md:text-7xl">🎫</span>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                                    <div class="absolute bottom-0 left-0 right-0 p-4 md:p-6 lg:p-8 text-white">
                                        <div class="flex flex-wrap items-center gap-1 md:gap-2 mb-2 md:mb-3">
                                            <span class="px-2 md:px-3 py-0.5 md:py-1 rounded-full text-[8px] md:text-[10px] font-mono uppercase tracking-wider border border-white/30"
                                                  :class="promo.module === 'travel' ? 'bg-blue-500/70' : promo.module === 'rental' ? 'bg-orange-500/70' : 'bg-purple-500/70'"
                                                  x-text="promo.module_label">
                                            </span>
                                            <span x-show="promo.type === 'selective'" 
                                                  class="px-2 md:px-3 py-0.5 md:py-1 rounded-full text-[8px] md:text-[10px] font-mono uppercase tracking-wider bg-purple-500/70 border border-white/30">
                                                Selektif
                                            </span>
                                            <span class="ml-auto bg-[#BA1826] text-white px-2 md:px-4 py-0.5 md:py-1.5 rounded-full text-[10px] md:text-sm font-bold shadow-lg"
                                                  x-text="promo.discount_text">
                                            </span>
                                        </div>
                                        <h3 class="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold mb-1 md:mb-2" x-text="promo.name"></h3>
                                        <p class="text-white/80 text-xs sm:text-sm md:text-base font-light line-clamp-2" x-text="promo.description || 'Nikmati potongan harga spesial untuk perjalanan Anda.'"></p>
                                        <div class="flex items-center gap-3 md:gap-4 mt-2 md:mt-4 text-[10px] md:text-sm text-white/60">
                                            <span x-text="'📅 ' + promo.period"></span>
                                            <span class="text-white/80 group-hover:translate-x-2 transition-transform inline-block">→ Selengkapnya</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </template>
                </div>
            </div>
            
            {{-- Status --}}
            <div class="flex flex-wrap justify-between items-center mt-3 md:mt-4 text-[10px] md:text-xs text-gray-400 font-mono uppercase tracking-wider">
                <span x-text="(currentPromoIndex + 1) + ' dari ' + promoItems.length"></span>
                <span x-show="isPlaying" class="flex items-center gap-1 md:gap-1.5">
                    <span class="w-1 h-1 md:w-1.5 md:h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                    Auto-rolling aktif
                </span>
                <span x-show="!isPlaying" class="flex items-center gap-1 md:gap-1.5 text-gray-500">
                    <span class="w-1 h-1 md:w-1.5 md:h-1.5 bg-gray-400 rounded-full"></span>
                    Di-pause
                </span>
            </div>
        </div>
    </div>
</section>
@endif