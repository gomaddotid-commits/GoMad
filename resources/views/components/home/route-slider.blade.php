@props(['routes' => []])

@php
    $routeData = $routes->map(function($r) {
        return [
            'id' => $r->id,
            'route_name' => $r->route_name,
            'origin' => $r->origin_city_name,
            'destination' => $r->destination_city_name,
            'photo' => $r->photo_url,
            'schedules_count' => $r->schedules_count,
            'url' => route('search', [
                'origin' => $r->origin_city_name,
                'destination' => $r->destination_city_name
            ]),
        ];
    })->values()->toArray();
@endphp

@if(!empty($routeData))
<section class="section py-8 md:py-12 container-magazine border-b border-[#E5E7EB] overflow-hidden" 
         x-data="routeSlider({{ json_encode($routeData) }})" 
         x-init="initRouteSlider()"
         x-cloak>
    
    {{-- ✅ SKELETON LOADING --}}
    <div x-show="loading" x-cloak>
        <div class="flex items-center gap-3 md:gap-4 mb-8 md:mb-12">
            <div class="h-px w-8 md:w-12 bg-gray-200"></div>
            <div class="h-6 md:h-8 bg-gray-200 rounded w-32 md:w-48 animate-pulse"></div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-4">
            <x-skeleton-card type="schedule" :count="3" />
        </div>
    </div>
    
    {{-- ✅ KONTEN ASLI --}}
    <div x-show="!loading" x-cloak>
        <div class="flex flex-wrap items-center justify-between gap-3 md:gap-4 mb-6 md:mb-8 lg:mb-12">
            <div class="flex items-center gap-2 md:gap-4">
                <div class="h-px w-6 md:w-12 bg-[#BA1826]"></div>
                <h2 class="text-lg sm:text-xl md:text-2xl lg:text-3xl font-bold tracking-tight text-[#111827]">Rute Populer</h2>
            </div>
            
            <div class="flex items-center gap-1 md:gap-2">
                <button @click="prevRoute()" 
                        :disabled="currentRouteIndex === 0"
                        class="w-8 h-8 md:w-10 md:h-10 rounded-full border border-[#E5E7EB] hover:border-[#BA1826] hover:bg-[#BA1826]/5 transition flex items-center justify-center text-[#111827] hover:text-[#BA1826] disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="currentRouteIndex === 0 ? 'opacity-40 cursor-not-allowed' : ''"
                        aria-label="Rute sebelumnya">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7 7-7 7"/>
                    </svg>
                </button>
                
                <button @click="nextRoute()" 
                        :disabled="currentRouteIndex >= maxRouteIndex"
                        class="w-8 h-8 md:w-10 md:h-10 rounded-full border border-[#E5E7EB] hover:border-[#BA1826] hover:bg-[#BA1826]/5 transition flex items-center justify-center text-[#111827] hover:text-[#BA1826] disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="currentRouteIndex >= maxRouteIndex ? 'opacity-40 cursor-not-allowed' : ''"
                        aria-label="Rute berikutnya">
                    <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                
                <div class="flex gap-1 md:gap-1.5 ml-1 md:ml-3">
                    <template x-for="(_, index) in routePages" :key="index">
                        <button @click="goToRoutePage(index)" 
                                class="w-2 h-2 md:w-2.5 md:h-2.5 rounded-full transition-all duration-300"
                                :class="currentRoutePage === index ? 'w-4 md:w-8 bg-[#BA1826]' : 'bg-[#D1D5DB] hover:bg-[#9CA3AF]'"
                                :aria-label="'Go to page ' + (index + 1)">
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden w-full">
            <div class="flex transition-transform duration-500 ease-in-out"
                 :style="'transform: translateX(-' + (currentRouteIndex * (100 / itemsPerView)) + '%)'">
                <template x-for="route in routeItems" :key="route.id">
                    <a :href="route.url" 
                       class="flex-shrink-0 px-1 md:px-2"
                       :style="'width: ' + (100 / itemsPerView) + '%'">
                        <div class="card-gomad overflow-hidden flex flex-col h-32 sm:h-36 md:h-40 lg:h-48 group/card p-0 hover:border-[#BA1826]">
                            <div class="flex h-full">
                                <div class="w-1/3 h-full overflow-hidden bg-[#F9FAFB] flex-shrink-0">
                                    <template x-if="route.photo">
                                        <img :src="route.photo" :alt="route.route_name" class="w-full h-full object-cover transition-transform duration-500 group-hover/card:scale-105">
                                    </template>
                                    <template x-if="!route.photo">
                                        <div class="w-full h-full bg-gradient-to-br from-[#BA1826]/10 to-[#BA1826]/5 flex items-center justify-center text-2xl md:text-3xl">🗺️</div>
                                    </template>
                                </div>
                                <div class="w-2/3 p-2 md:p-3 lg:p-4 flex flex-col justify-between">
                                    <div>
                                        <h3 class="font-bold text-[#111827] text-xs sm:text-sm md:text-base truncate" x-text="route.route_name"></h3>
                                        <p class="text-[10px] sm:text-xs md:text-sm text-gray-500 truncate" x-text="route.origin + ' → ' + route.destination"></p>
                                    </div>
                                    <div class="flex justify-between items-center border-t border-[#E5E7EB] pt-1 md:pt-2 mt-1 md:mt-2">
                                        <p class="text-[8px] sm:text-[10px] md:text-xs font-mono uppercase tracking-wider text-[#BA1826] font-medium" x-text="route.schedules_count + ' jadwal'"></p>
                                        <span class="text-[#BA1826] group-hover/card:translate-x-1 transition-transform text-sm md:text-base">→</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </template>
            </div>
            
            <div class="flex justify-between items-center mt-3 md:mt-4 text-[10px] md:text-xs text-gray-400 font-mono uppercase tracking-wider">
                <span x-text="(currentRouteIndex + 1) + ' dari ' + routeItems.length + ' rute'"></span>
                <span x-text="'Halaman ' + (currentRoutePage + 1) + ' dari ' + routePages.length"></span>
            </div>
        </div>
    </div>
</section>
@endif