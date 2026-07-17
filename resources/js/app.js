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
// Agar chart tidak putih semua, set warna teks dan grid menjadi gelap
Chart.defaults.color = '#111827'; // Warna teks (label, legend, tooltip) - gomad-dark
Chart.defaults.borderColor = '#E5E7EB'; // Warna grid line - gomad-divider

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