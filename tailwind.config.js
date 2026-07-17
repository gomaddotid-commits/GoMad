/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Montserrat', 'sans-serif'], // Mengganti Geist Sans -> Montserrat
                mono: ['Montserrat', 'monospace'], // Fallback mono agar konsisten
            },
            colors: {
                // GoMad Design System Palette
                gomad: {
                    primary: '#BA1826',    // Classic Crimson (Brand Utama)
                    cta: '#E42535',        // Vibrant Red (Tombol Aksi)
                    dark: '#111827',       // Jet Black (Teks Utama)
                    body: '#4B5563',       // Slate Grey (Teks Sekunder)
                    bg: '#F9FAFB',         // Off-White (Background Web)
                    accent: '#F5A623',     // Warm Amber (Wallet/Promo)
                    success: '#10B981',    // Emerald (Status Berhasil)
                    warning: '#F59E0B',    // Amber (Status Pending)
                    divider: '#E5E7EB',    // Cool Grey (Border/Divider)
                }
            },
            borderRadius: {
                'gomad': '10px',      // Tombol/Input umum
                'gomad-lg': '12px',   // Kartu/Modal
            },
            boxShadow: {
                'gomad': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)',
                'gomad-lg': '0 10px 25px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025)',
            }
        },
    },
    plugins: [],
}