<!-- ========================================
     DISCLAIMER POPUP
     Shows once per session when user opens website
     ======================================== -->

<!-- Disclaimer Modal -->
<div id="disclaimerModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black bg-opacity-50 p-4 overflow-y-auto" style="display: none;">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 md:p-8 relative transform scale-95 opacity-0 transition-all duration-300 my-8 mx-auto max-h-[90vh] overflow-y-auto" id="disclaimerContent">
        
        <!-- Header with Logo -->
        <div class="text-center mb-6">
            <div class="flex items-center justify-center mb-4">
                <img src="images/logo.png" alt="PetPantry+ Logo" class="h-16 w-16 rounded-full border-4 border-orange-500">
            </div>
            <h2 class="text-3xl font-bold text-orange-600 mb-2">Welcome to PetPantry+</h2>
            <div class="h-1 w-24 bg-orange-500 mx-auto rounded"></div>
        </div>

        <!-- Disclaimer Content -->
        <div class="space-y-4 text-gray-700">
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-semibold text-blue-900 mb-2">Academic Project Notice</h3>
                        <p class="text-sm text-blue-800 leading-relaxed">
                            This website is an <strong>academic Finals project</strong> developed for the course 
                            <strong>System Integration and Architecture</strong> at 
                            <strong>Technological Institute of the Philippines (TIP) - Quezon City</strong>.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-semibold text-orange-900 mb-2">Purpose & Scope</h3>
                        <p class="text-sm text-orange-800 leading-relaxed">
                            PetPantry+ is created for <strong>educational and demonstration purposes only</strong>. 
                            It showcases system integration concepts, e-commerce functionality, and modern web architecture.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">🎓 Project Information:</h3>
                <ul class="text-sm text-gray-700 space-y-1.5">
                    <li class="flex items-start">
                        <span class="text-orange-500 mr-2">•</span>
                        <span><strong>Course:</strong> System Integration and Architecture</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-orange-500 mr-2">•</span>
                        <span><strong>Institution:</strong> Technological Institute of the Philippines (TIP) - Quezon City</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-orange-500 mr-2">•</span>
                        <span><strong>Year:</strong> 2025</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-orange-500 mr-2">•</span>
                        <span><strong>Type:</strong>Final Project</span>
                    </li>
                </ul>
            </div>

            <div class="text-center text-xs text-gray-500 pt-2">
                <p>By continuing, you acknowledge that this is a student project for academic evaluation.</p>
            </div>
        </div>

        <!-- Close Button -->
        <div class="mt-6 text-center">
            <button onclick="closeDisclaimer()" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white font-semibold px-8 py-3 rounded-full hover:from-orange-600 hover:to-orange-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                I Understand - Continue to PetPantry+
            </button>
        </div>

        <!-- Academic Badge -->
        <div class="mt-4 text-center">
            <div class="inline-flex items-center gap-2 bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-xs font-semibold">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                </svg>
                TIP-QC Academic Project 2025
            </div>
        </div>
    </div>
</div>

<style>
/* Prevent overflow on all screen sizes */
html, body {
    overflow-x: hidden;
    max-width: 100vw;
}

/* Disclaimer Modal Animations */
#disclaimerModal {
    overflow-y: auto;
    overflow-x: hidden;
}

#disclaimerModal.show {
    display: flex !important;
}

#disclaimerModal.show #disclaimerContent {
    transform: scale(1);
    opacity: 1;
}

/* Modal content with overflow protection */
#disclaimerContent {
    max-height: 90vh;
    overflow-y: auto;
    overflow-x: hidden;
    animation: slideIn 0.3s ease-out;
    /* Smooth scrolling */
    -webkit-overflow-scrolling: touch;
}

/* Prevent body scroll when modal is open */
body.modal-open {
    overflow: hidden;
    position: fixed;
    width: 100%;
}

/* Smooth animations */
@keyframes slideIn {
    from {
        transform: scale(0.95) translateY(-20px);
        opacity: 0;
    }
    to {
        transform: scale(1) translateY(0);
        opacity: 1;
    }
}

/* Responsive padding and sizing */
@media (max-width: 640px) {
    #disclaimerContent {
        padding: 1rem !important;
        margin: 1rem;
        max-height: 95vh;
    }
    
    #disclaimerContent h2 {
        font-size: 1.5rem;
    }
    
    #disclaimerContent .text-sm {
        font-size: 0.875rem;
    }
    
    #disclaimerContent img {
        height: 3rem;
        width: 3rem;
    }
}

/* Tablet */
@media (min-width: 641px) and (max-width: 1024px) {
    #disclaimerContent {
        padding: 1.5rem;
        max-height: 90vh;
    }
}

/* Small screens - reduce spacing */
@media (max-height: 700px) {
    #disclaimerContent {
        padding: 1rem;
        margin: 0.5rem;
    }
    
    #disclaimerContent .space-y-4 > * + * {
        margin-top: 0.75rem;
    }
}

/* Very small screens */
@media (max-height: 600px) {
    #disclaimerContent {
        max-height: 98vh;
        padding: 0.75rem;
    }
}
</style>

<script>
// Disclaimer Popup Logic
(function() {
    // Check if disclaimer has been shown this session
    const disclaimerShown = sessionStorage.getItem('disclaimerShown');
    
    // Only show if not shown in this session
    if (!disclaimerShown) {
        // Wait for page to load
        window.addEventListener('load', function() {
            setTimeout(function() {
                showDisclaimer();
            }, 500); // Small delay for better UX
        });
    }
})();

function showDisclaimer() {
    const modal = document.getElementById('disclaimerModal');
    const body = document.body;
    
    if (modal) {
        modal.classList.add('show');
        body.classList.add('modal-open');
    }
}

function closeDisclaimer() {
    const modal = document.getElementById('disclaimerModal');
    const content = document.getElementById('disclaimerContent');
    const body = document.body;
    
    if (modal && content) {
        // Fade out animation
        content.style.transform = 'scale(0.95)';
        content.style.opacity = '0';
        
        setTimeout(function() {
            modal.classList.remove('show');
            body.classList.remove('modal-open');
            
            // Mark as shown in this session
            sessionStorage.setItem('disclaimerShown', 'true');
        }, 300);
    }
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('disclaimerModal');
        if (modal && modal.classList.contains('show')) {
            closeDisclaimer();
        }
    }
});

// Optional: Close on backdrop click
document.getElementById('disclaimerModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDisclaimer();
    }
});
</script>

