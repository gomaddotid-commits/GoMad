/* ═══════════════════════════════════════
   GOMAD LANDING PAGE - MAIN JAVASCRIPT
   ═══════════════════════════════════════ */

// ==================== THEME MANAGER ====================
const theme = {
    init() {
        const saved = localStorage.getItem('gomad-theme') || 'light';
        // Cek preferensi sistem
        if (!localStorage.getItem('gomad-theme')) {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.apply(prefersDark ? 'dark' : 'light');
            return;
        }
        this.apply(saved);
    },
    
    toggle() {
        const current = document.documentElement.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';
        this.apply(next);
        localStorage.setItem('gomad-theme', next);
        
        // Update mobile theme button text
        const mobileThemeIcon = document.getElementById('mobile-theme-icon');
        const mobileThemeText = document.getElementById('mobile-theme-text');
        if (mobileThemeIcon && mobileThemeText) {
            if (next === 'dark') {
                mobileThemeIcon.textContent = '☀️';
                mobileThemeText.textContent = 'Light Mode';
            } else {
                mobileThemeIcon.textContent = '🌙';
                mobileThemeText.textContent = 'Dark Mode';
            }
        }
    },
    
    apply(mode) {
        document.documentElement.setAttribute('data-theme', mode);
        
        // Update header logo
        const logo = document.getElementById('header-logo');
        if (logo) {
            logo.src = mode === 'dark' 
                ? '/landing/assets/images/logo-putih.png' 
                : '/landing/assets/images/logo-merah.png';
        }
        
        // Update mobile logo
        const mobileLogo = document.getElementById('mobile-logo');
        if (mobileLogo) {
            mobileLogo.src = mode === 'dark' 
                ? '/landing/assets/images/logo-putih.png' 
                : '/landing/assets/images/logo-merah.png';
        }
        
        // Update mermaid theme if loaded
        if (window.mermaid) {
            this.updateMermaid(mode);
        }
    },
    
    updateMermaid(mode) {
        const mermaidElements = document.querySelectorAll('.mermaid');
        if (mermaidElements.length > 0) {
            mermaid.initialize({
                startOnLoad: true,
                theme: mode === 'dark' ? 'dark' : 'default',
                securityLevel: 'loose',
            });
            // Re-render
            mermaidElements.forEach(el => {
                if (el.getAttribute('data-processed')) {
                    el.removeAttribute('data-processed');
                }
            });
            try {
                mermaid.run();
            } catch (e) {
                console.log('Mermaid re-render skipped');
            }
        }
    }
};

// ==================== SPA ROUTER ====================
const router = {
    routes: {
        '/':              'pages/home.html',
        '/platform':      'pages/platform.html',
        '/agency':        'pages/agency.html',
        '/customer':      'pages/customer.html',
        '/contact':       'pages/contact.html',
        '/docs':          'documentations/index.html',
        '/docs/customer': 'documentations/customer.html',
        '/docs/agency':   'documentations/agency.html',
        '/docs/driver':   'documentations/driver.html',
        '/docs/warung':   'documentations/warung.html',
        '/docs/flow':     'documentations/flow.html',
    },
    
    async load(path) {
        const mainContent = document.getElementById('main-content');
        const url = this.routes[path];
        
        if (!url) {
            mainContent.innerHTML = `
                <div class="text-center py-20">
                    <div class="text-6xl font-bold text-[#C1121F] mb-4">404</div>
                    <h2 class="text-xl font-bold mb-2">Halaman Tidak Ditemukan</h2>
                    <p class="text-gray-500 mb-6 font-light">Halaman yang Anda cari tidak tersedia.</p>
                    <a href="#/" class="btn-primary inline-flex">← Kembali ke Beranda</a>
                </div>
            `;
            return;
        }
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'instant' });
        
        // Show loading
        mainContent.innerHTML = `
            <div class="flex items-center justify-center h-64">
                <div class="text-center">
                    <div class="animate-spin w-10 h-10 border-4 border-[#C1121F] border-t-transparent rounded-full mx-auto mb-4"></div>
                    <p class="text-gray-500 dark:text-gray-400 font-light">Memuat...</p>
                </div>
            </div>
        `;
        
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Page not found');
            const html = await response.text();
            mainContent.innerHTML = html;
            
            // Update active nav link
            this.updateActiveNav(path);
            
            // Re-init page scripts
            this.initPageScripts();
            
            // Scroll animations
            this.initScrollAnimations();
            
        } catch (error) {
            mainContent.innerHTML = `
                <div class="text-center py-20">
                    <div class="text-6xl font-bold text-[#C1121F] mb-4">404</div>
                    <h2 class="text-xl font-bold mb-2">Halaman Tidak Ditemukan</h2>
                    <p class="text-gray-500 mb-6 font-light">${error.message}</p>
                    <a href="#/" class="btn-primary inline-flex">← Kembali ke Beranda</a>
                </div>
            `;
        }
    },
    
    updateActiveNav(path) {
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
            const href = link.getAttribute('href');
            if (href === `#${path}` || (path === '/' && href === '#/')) {
                link.classList.add('active');
            }
        });
    },
    
    initPageScripts() {
        // Re-init Mermaid jika ada
        if (window.mermaid) {
            const mermaidElements = document.querySelectorAll('.mermaid');
            if (mermaidElements.length > 0) {
                const mode = document.documentElement.getAttribute('data-theme') || 'light';
                mermaid.initialize({
                    startOnLoad: true,
                    theme: mode === 'dark' ? 'dark' : 'default',
                    securityLevel: 'loose',
                });
                try {
                    mermaid.run();
                } catch (e) {
                    console.log('Mermaid init skipped');
                }
            }
        }
    },
    
    initScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.fade-in-up').forEach(el => {
            observer.observe(el);
        });
    }
};

// ==================== MOBILE MENU ====================
const mobileMenu = {
    open() {
        const overlay = document.getElementById('mobile-overlay');
        const drawer = document.getElementById('mobile-drawer');
        if (overlay) overlay.classList.add('open');
        if (drawer) drawer.classList.add('open');
        document.body.style.overflow = 'hidden';
    },
    
    close() {
        const overlay = document.getElementById('mobile-overlay');
        const drawer = document.getElementById('mobile-drawer');
        if (overlay) overlay.classList.remove('open');
        if (drawer) drawer.classList.remove('open');
        document.body.style.overflow = '';
    }
};

// ==================== FLOATING HEADER SCROLL BEHAVIOR ====================
function initHeaderScroll() {
    let lastScrollY = 0;
    const header = document.querySelector('.floating-header');
    
    if (!header) return;
    
    window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;
        
        if (currentScrollY > lastScrollY && currentScrollY > 100) {
            // Scroll ke bawah → sembunyikan header
            header.style.transform = 'translateY(-120%)';
            header.style.opacity = '0';
            header.style.transition = 'transform 0.4s ease, opacity 0.3s ease';
        } else {
            // Scroll ke atas → tampilkan header
            header.style.transform = 'translateY(0)';
            header.style.opacity = '1';
            header.style.transition = 'transform 0.4s ease, opacity 0.3s ease';
        }
        
        lastScrollY = currentScrollY;
    });
}

// ==================== INIT ====================
document.addEventListener('DOMContentLoaded', () => {
    // Load header & footer
    Promise.all([
        fetch('components/header.html').then(r => r.text()),
        fetch('components/footer.html').then(r => r.text())
    ]).then(([headerHtml, footerHtml]) => {
        const headerContainer = document.getElementById('header-container');
        const footerContainer = document.getElementById('footer-container');
        
        if (headerContainer) headerContainer.innerHTML = headerHtml;
        if (footerContainer) footerContainer.innerHTML = footerHtml;
        
        // Bind mobile menu events after header loaded
        setTimeout(() => {
            const menuBtn = document.getElementById('mobile-menu-btn');
            const overlay = document.getElementById('mobile-overlay');
            const drawerClose = document.getElementById('mobile-drawer-close');
            
            if (menuBtn) menuBtn.addEventListener('click', mobileMenu.open);
            if (overlay) overlay.addEventListener('click', mobileMenu.close);
            if (drawerClose) drawerClose.addEventListener('click', mobileMenu.close);
            
            // Init floating header scroll behavior
            initHeaderScroll();
        }, 200);
    }).catch(err => {
        console.error('Failed to load header/footer:', err);
    });
    
    // Init theme
    theme.init();
    
    // Init router on hash change
    window.addEventListener('hashchange', () => {
        const hash = location.hash.slice(1) || '/';
        router.load(hash);
    });
    
    // Load initial page
    const hash = location.hash.slice(1) || '/';
    router.load(hash);
    
    // WA tooltip - show after 3 seconds, hide after 5 more
    setTimeout(() => {
        const tooltip = document.getElementById('wa-tooltip');
        if (tooltip) {
            tooltip.style.opacity = '1';
            setTimeout(() => { 
                tooltip.style.opacity = '0'; 
            }, 5000);
        }
    }, 3000);
    
    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem('gomad-theme')) {
            theme.apply(e.matches ? 'dark' : 'light');
        }
    });
});

// ==================== KEYBOARD SHORTCUTS ====================
document.addEventListener('keydown', (e) => {
    // ESC to close mobile menu
    if (e.key === 'Escape') {
        mobileMenu.close();
    }
    
    // Ctrl/Cmd + T to toggle theme
    if ((e.ctrlKey || e.metaKey) && e.key === 't') {
        e.preventDefault();
        theme.toggle();
    }
});