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
                sans: ['Montserrat', 'sans-serif'],
                mono: ['Montserrat', 'monospace'],
            },
            colors: {
                gomad: {
                    primary: '#BA1826',
                    cta: '#E42535',
                    dark: '#111827',
                    body: '#4B5563',
                    bg: '#F9FAFB',
                    accent: '#F5A623',
                    success: '#10B981',
                    warning: '#F59E0B',
                    divider: '#E5E7EB',
                }
            },
            borderRadius: {
                'gomad': '10px',
                'gomad-lg': '12px',
            },
            boxShadow: {
                'gomad': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)',
                'gomad-lg': '0 10px 25px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025)',
            },
            // ✅ TAMBAHKAN INI - Custom spacing untuk banner
            spacing: {
                'banner': '40px',           // Tinggi banner
                'banner-header': '104px',   // Banner + header mobile (40 + 64)
                'banner-header-md': '120px', // Banner + header desktop (40 + 80)
            },
        },
    },
    plugins: [],
}