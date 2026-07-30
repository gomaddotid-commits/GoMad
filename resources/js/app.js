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
// ✅ ALPINE STORE: TOP BANNER (SMOOTH)
// ═══════════════════════════════════════════
document.addEventListener('alpine:init', () => {
    Alpine.store('banner', {
        // State
        open: true,
        text: '',
        link: '',
        active: false,
        isAnimating: false,
        autoCloseTimer: null,
        
        // Initialize from localStorage
        init() {
            // Ambil data banner dari hidden element
            const bannerData = document.querySelector('[data-banner]');
            if (bannerData) {
                this.active = bannerData.dataset.bannerActive === '1';
                this.text = bannerData.dataset.bannerText || '';
                this.link = bannerData.dataset.bannerLink || '';
            }
            
            // ✅ SELALU muncul jika aktif, IGNORE localStorage
            // Ini adalah Opsi 1: Banner selalu muncul setiap kali page reload
            this.open = this.active && !!this.text;
            
            // Auto-close after 5 seconds
            if (this.open) {
                this.startAutoClose();
            }
            
            // Debug log (hapus di production)
            console.log('🔔 Banner initialized:', {
                active: this.active,
                text: this.text,
                link: this.link,
                open: this.open
            });
        },
        
        // Open banner
        openBanner() {
            if (this.active && this.text && !this.isAnimating) {
                this.isAnimating = true;
                this.open = true;
                localStorage.removeItem('topBannerClosed');
                
                // Force reflow untuk smooth transition
                requestAnimationFrame(() => {
                    this.isAnimating = false;
                });
                
                this.startAutoClose();
            }
        },
        
        // Close banner
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
        
        // Start auto-close timer
        startAutoClose() {
            this.clearAutoClose();
            this.autoCloseTimer = setTimeout(() => {
                this.closeBanner();
            }, 5000);
        },
        
        // Clear auto-close timer
        clearAutoClose() {
            if (this.autoCloseTimer) {
                clearTimeout(this.autoCloseTimer);
                this.autoCloseTimer = null;
            }
        }
    });
});

window.Alpine = Alpine;
window.Chart = Chart;
Alpine.start();

// --- Connected Journey: Line Animation & Transformations ---
document.addEventListener('DOMContentLoaded', () => {
    // 1. Sticky Header Transformation (untuk customer/agency/driver layouts)
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