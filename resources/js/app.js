import './bootstrap';
import "@fontsource/league-spartan";
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

window.Alpine = Alpine;
window.Chart = Chart;
Alpine.start();

// Sticky Header
document.addEventListener('DOMContentLoaded', () => {
    const header = document.getElementById('mainHeader');
    if (header) {
        const updateHeader = () => {
            if (window.scrollY > 50) header.classList.add('scrolled');
            else header.classList.remove('scrolled');
        };
        updateHeader();
        window.addEventListener('scroll', updateHeader);
    }
    
    // Auto-hide alert
    const alertMsg = document.getElementById('alertMsg');
    if (alertMsg) setTimeout(() => alertMsg.style.display = 'none', 5000);
});