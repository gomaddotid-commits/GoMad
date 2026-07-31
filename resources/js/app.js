import './bootstrap';
import Alpine from 'alpinejs';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import Chart from 'chart.js/auto';

// Fix Leaflet default icon
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: '/images/leaflet/marker-icon-2x.png',
    iconUrl: '/images/leaflet/marker-icon.png',
    shadowUrl: '/images/leaflet/marker-shadow.png',
});

// --- SETTING GLOBAL CHART.JS ---
Chart.defaults.color = '#111827';
Chart.defaults.borderColor = '#E5E7EB';

// ═══════════════════════════════════════════
// ✅ ALPINE STORE: TOP BANNER
// ═══════════════════════════════════════════
document.addEventListener('alpine:init', () => {
    Alpine.store('banner', {
        open: true,
        text: '',
        link: '',
        active: false,
        isAnimating: false,
        autoCloseTimer: null,
        
        init() {
            const bannerData = document.querySelector('[data-banner]');
            if (bannerData) {
                this.active = bannerData.dataset.bannerActive === '1';
                this.text = bannerData.dataset.bannerText || '';
                this.link = bannerData.dataset.bannerLink || '';
            }
            
            this.open = this.active && !!this.text;
            
            if (this.open) {
                this.startAutoClose();
            }
            
            console.log('🔔 Banner initialized:', {
                active: this.active,
                text: this.text,
                link: this.link,
                open: this.open
            });
        },
        
        openBanner() {
            if (this.active && this.text && !this.isAnimating) {
                this.isAnimating = true;
                this.open = true;
                localStorage.removeItem('topBannerClosed');
                requestAnimationFrame(() => {
                    this.isAnimating = false;
                });
                this.startAutoClose();
            }
        },
        
        closeBanner() {
            if (!this.isAnimating) {
                this.isAnimating = true;
                this.open = false;
                localStorage.setItem('topBannerClosed', 'true');
                this.clearAutoClose();
                requestAnimationFrame(() => {
                    this.isAnimating = false;
                });
            }
        },
        
        startAutoClose() {
            this.clearAutoClose();
            this.autoCloseTimer = setTimeout(() => {
                this.closeBanner();
            }, 5000);
        },
        
        clearAutoClose() {
            if (this.autoCloseTimer) {
                clearTimeout(this.autoCloseTimer);
                this.autoCloseTimer = null;
            }
        }
    });
});

// ═══════════════════════════════════════════
// ✅ ALPINE DATA: SEARCH HANDLER (Gabungan Travel + Rental)
// ═══════════════════════════════════════════
document.addEventListener('alpine:init', () => {
    Alpine.data('searchHandler', () => ({
        searchMode: 'travel',
        originSearch: '',
        originOpen: false,
        selectedOrigin: '',
        selectedOriginName: '',
        destinationSearch: '',
        destinationOpen: false,
        selectedDestination: '',
        selectedDestinationName: '',
        allCities: [],
        
        init() {
            const citiesData = document.querySelector('[data-cities]');
            if (citiesData) {
                try {
                    this.allCities = JSON.parse(citiesData.dataset.cities);
                } catch (e) {
                    this.allCities = [];
                }
            }
        },
        
        filteredCities(search) {
            var q = (search || '').toLowerCase();
            if (!q) return this.allCities;
            return this.allCities.filter(function(c) {
                return c.name.toLowerCase().includes(q) || c.province_name.toLowerCase().includes(q);
            });
        },
        
        selectOrigin(city) {
            this.selectedOrigin = city.code;
            this.selectedOriginName = city.name;
            this.originSearch = city.name + ' (' + city.province_name + ')';
        },
        
        selectDestination(city) {
            this.selectedDestination = city.code;
            this.selectedDestinationName = city.name;
            this.destinationSearch = city.name + ' (' + city.province_name + ')';
        }
    }));
});

// ═══════════════════════════════════════════
// ✅ ALPINE DATA: PROMO ROLLING
// ═══════════════════════════════════════════
document.addEventListener('alpine:init', () => {
    Alpine.data('promoRolling', (items) => ({
        promoItems: items || [],
        currentPromoIndex: 0,
        isPlaying: true,
        interval: null,
        isManualOverride: false,
        manualTimeout: null,
        loading: true,
        
        initPromoRolling() {
            setTimeout(() => {
                this.loading = false;
            }, 600);
            
            if (this.promoItems.length > 1) {
                this.startAutoRolling();
            }
        },
        
        startAutoRolling() {
            if (this.interval) clearInterval(this.interval);
            this.isPlaying = true;
            this.interval = setInterval(() => {
                if (this.isPlaying && !this.isManualOverride) {
                    this.nextPromo();
                }
            }, 3000);
        },
        
        stopAutoRolling() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
            this.isPlaying = false;
        },
        
        togglePlay() {
            if (this.isPlaying) {
                this.stopAutoRolling();
            } else {
                this.startAutoRolling();
            }
        },
        
        nextPromo() {
            if (this.promoItems.length <= 1) return;
            this.manualOverride();
            this.currentPromoIndex = (this.currentPromoIndex + 1) % this.promoItems.length;
        },
        
        prevPromo() {
            if (this.promoItems.length <= 1) return;
            this.manualOverride();
            this.currentPromoIndex = (this.currentPromoIndex - 1 + this.promoItems.length) % this.promoItems.length;
        },
        
        goToPromo(index) {
            if (index === this.currentPromoIndex) return;
            this.manualOverride();
            this.currentPromoIndex = index;
        },
        
        manualOverride() {
            this.isManualOverride = true;
            if (this.manualTimeout) {
                clearTimeout(this.manualTimeout);
                this.manualTimeout = null;
            }
            if (this.isPlaying) {
                this.manualTimeout = setTimeout(() => {
                    this.isManualOverride = false;
                    this.manualTimeout = null;
                }, 5000);
            }
        }
    }));
});

// ═══════════════════════════════════════════
// ✅ ALPINE DATA: ROUTE SLIDER
// ═══════════════════════════════════════════
document.addEventListener('alpine:init', () => {
    Alpine.data('routeSlider', (items) => ({
        routeItems: items || [],
        currentRouteIndex: 0,
        itemsPerView: 3,
        maxRouteIndex: 0,
        routePages: [],
        currentRoutePage: 0,
        loading: true,
        
        initRouteSlider() {
            setTimeout(() => {
                this.loading = false;
            }, 800);
            
            this.updateItemsPerView();
            this.maxRouteIndex = Math.max(0, this.routeItems.length - this.itemsPerView);
            
            var totalPages = Math.ceil(this.routeItems.length / this.itemsPerView);
            this.routePages = Array.from({ length: totalPages }, function(_, i) { return i; });
            
            this.currentRoutePage = Math.floor(this.currentRouteIndex / this.itemsPerView);
            
            var self = this;
            window.addEventListener('resize', function() {
                self.updateItemsPerView();
                self.maxRouteIndex = Math.max(0, self.routeItems.length - self.itemsPerView);
                
                if (self.currentRouteIndex > self.maxRouteIndex) {
                    self.currentRouteIndex = self.maxRouteIndex;
                }
                
                var totalPages = Math.ceil(self.routeItems.length / self.itemsPerView);
                self.routePages = Array.from({ length: totalPages }, function(_, i) { return i; });
                self.currentRoutePage = Math.floor(self.currentRouteIndex / self.itemsPerView);
            });
        },
        
        updateItemsPerView() {
            var width = window.innerWidth;
            if (width < 640) {
                this.itemsPerView = 1;
            } else if (width < 1024) {
                this.itemsPerView = 2;
            } else {
                this.itemsPerView = 3;
            }
        },
        
        nextRoute() {
            if (this.currentRouteIndex < this.maxRouteIndex) {
                this.currentRouteIndex++;
                this.currentRoutePage = Math.floor(this.currentRouteIndex / this.itemsPerView);
            }
        },
        
        prevRoute() {
            if (this.currentRouteIndex > 0) {
                this.currentRouteIndex--;
                this.currentRoutePage = Math.floor(this.currentRouteIndex / this.itemsPerView);
            }
        },
        
        goToRoutePage(page) {
            var newIndex = page * this.itemsPerView;
            if (newIndex <= this.maxRouteIndex) {
                this.currentRouteIndex = newIndex;
                this.currentRoutePage = page;
            }
        }
    }));
});

// ═══════════════════════════════════════════
// ✅ ALPINE DATA: RENTAL BY CITY (Loading State)
// ═══════════════════════════════════════════
document.addEventListener('alpine:init', () => {
    Alpine.data('rentalByCity', () => ({
        loading: true,
        
        init() {
            setTimeout(() => {
                this.loading = false;
            }, 800);
        }
    }));
});

window.Alpine = Alpine;
window.Chart = Chart;
Alpine.start();

// --- Connected Journey: Line Animation & Transformations ---
document.addEventListener('DOMContentLoaded', () => {
    // 1. Sticky Header Transformation
    const header = document.getElementById('mainHeader');
    if (header) {
        const updateHeader = () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
                header.classList.add('bg-white/90', 'backdrop-blur-md', 'border-b', 'border-[#E5E7EB]');
                header.classList.remove('bg-[#BA1826]', 'shadow-none');
            } else {
                header.classList.remove('scrolled');
                header.classList.remove('bg-white/90', 'backdrop-blur-md', 'border-b', 'border-[#E5E7EB]');
                header.classList.add('bg-[#BA1826]');
            }
        };
        updateHeader();
        window.addEventListener('scroll', updateHeader);
    }

    // 2. Scroll Reveal
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.querySelector('.reveal-line')?.classList.add('animate-line-draw');
            }
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));

    // 3. Auto-hide alerts
    const alertMsg = document.getElementById('alertMsg');
    if (alertMsg) setTimeout(() => alertMsg.style.display = 'none', 5000);
});