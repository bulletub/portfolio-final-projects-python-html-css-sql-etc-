// Splash Screen and Loading Screen Management
(function() {
    'use strict';

    // Check if splash screen has been shown
    const hasSeenSplash = sessionStorage.getItem('driftlens_splash_shown');

    // Show splash screen only on first visit
    if (!hasSeenSplash) {
        showSplashScreen();
    }

    // Wait for DOM to be ready before setting up loading screen
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupLoadingScreen);
    } else {
        setupLoadingScreen();
    }

    function showSplashScreen() {
        const splashHTML = `
            <div id="splash-screen" class="splash-screen active">
                <div class="splash-content">
                    <div class="splash-logo">
                        <img src="/static/images/DriftLens.png" alt="DriftLens" class="splash-logo-img">
                    </div>
                    <div class="splash-loader">
                        <div class="loader-bar"></div>
                    </div>
                    <p class="splash-text">Welcome to DriftLens</p>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('afterbegin', splashHTML);

        // Hide splash screen after 3 seconds
        setTimeout(() => {
            const splash = document.getElementById('splash-screen');
            splash.classList.add('fade-out');
            
            setTimeout(() => {
                splash.remove();
                sessionStorage.setItem('driftlens_splash_shown', 'true');
            }, 500);
        }, 3000);
    }

    function setupLoadingScreen() {
        // Create loading screen HTML
        const loadingHTML = `
            <div id="loading-screen" class="loading-screen">
                <div class="loading-content">
                    <div class="loading-logo">
                        <img src="/static/images/DriftLens.png" alt="DriftLens" class="loading-logo-img">
                    </div>
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('afterbegin', loadingHTML);

        // Intercept all navigation clicks
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            
            // Check if it's a navigation link (not external, not anchor, not download)
            if (link && 
                link.href && 
                link.href.startsWith(window.location.origin) &&
                !link.href.includes('#') &&
                !link.hasAttribute('download') &&
                link.target !== '_blank' &&
                !link.classList.contains('no-loading')) {
                
                e.preventDefault();
                showLoadingScreen();
                
                // Navigate after showing loading screen
                setTimeout(() => {
                    window.location.href = link.href;
                }, 200);
            }
        }, true);

        // Show loading screen on form submissions
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!form.classList.contains('no-loading')) {
                showLoadingScreen();
            }
        });

        // Hide loading screen when page loads
        window.addEventListener('load', function() {
            hideLoadingScreen();
        });

        // Hide loading screen on page show (for back/forward navigation)
        window.addEventListener('pageshow', function() {
            hideLoadingScreen();
        });
    }

    function showLoadingScreen() {
        const loading = document.getElementById('loading-screen');
        if (loading) {
            loading.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function hideLoadingScreen() {
        const loading = document.getElementById('loading-screen');
        if (loading) {
            loading.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    // Smooth page transitions
    document.addEventListener('DOMContentLoaded', function() {
        document.body.classList.add('page-loaded');
    });

})();

