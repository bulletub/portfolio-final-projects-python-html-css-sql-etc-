/**
 * Mobile Menu Functionality
 * Handles hamburger menu toggle for mobile devices
 */

function toggleMobileMenu() {
  const menu = document.getElementById('mobile-menu');
  const menuIcon = document.getElementById('menu-icon');
  const closeIcon = document.getElementById('close-icon');
  
  if (!menu) return;
  
  if (menu.classList.contains('hidden')) {
    // Open menu
    menu.classList.remove('hidden');
    menu.classList.add('animate-slideDown');
    if (menuIcon) menuIcon.classList.add('hidden');
    if (closeIcon) closeIcon.classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent scroll when menu open
  } else {
    // Close menu
    menu.classList.add('hidden');
    menu.classList.remove('animate-slideDown');
    if (menuIcon) menuIcon.classList.remove('hidden');
    if (closeIcon) closeIcon.classList.add('hidden');
    document.body.style.overflow = ''; // Restore scroll
  }
  
  // Re-initialize feather icons
  if (window.feather) {
    feather.replace();
  }
}

// Close mobile menu when clicking outside
document.addEventListener('DOMContentLoaded', function() {
  document.addEventListener('click', function(e) {
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    
    if (!menu || !button) return;
    
    if (!menu.contains(e.target) && !button.contains(e.target) && !menu.classList.contains('hidden')) {
      toggleMobileMenu();
    }
  });
  
  // Close menu on link click (for smooth navigation)
  const mobileMenuLinks = document.querySelectorAll('#mobile-menu a');
  mobileMenuLinks.forEach(link => {
    link.addEventListener('click', function() {
      const menu = document.getElementById('mobile-menu');
      if (menu && !menu.classList.contains('hidden')) {
        setTimeout(() => toggleMobileMenu(), 200);
      }
    });
  });
});

// Close menu on window resize to desktop
window.addEventListener('resize', function() {
  if (window.innerWidth >= 768) { // md breakpoint
    const menu = document.getElementById('mobile-menu');
    if (menu && !menu.classList.contains('hidden')) {
      menu.classList.add('hidden');
      document.body.style.overflow = '';
      
      const menuIcon = document.getElementById('menu-icon');
      const closeIcon = document.getElementById('close-icon');
      if (menuIcon) menuIcon.classList.remove('hidden');
      if (closeIcon) closeIcon.classList.add('hidden');
    }
  }
});

