<?php
// promo_popup.php - Smart promotion popup with cookie tracking
// Include this file in header.php or footer.php

// Ensure database connection exists
if (!isset($pdo)) {
    require_once __DIR__ . '/database.php';
}

// Fetch the LATEST promotion that has show_popup enabled
$latestPromo = null;
try {
    $stmt = $pdo->query("
        SELECT * FROM promos 
        WHERE is_active = 1 
        AND show_popup = 1
        AND (start_date IS NULL OR start_date <= NOW())
        AND (end_date IS NULL OR end_date >= NOW())
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $latestPromo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silently fail if table doesn't exist yet
    $latestPromo = null;
}

// Only show popup if there's a promotion and user hasn't seen it
if ($latestPromo):
    $promoId = $latestPromo['id'];
    $justLoggedIn = isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in'];
    // Clear the flag after we've checked it (will be cleared client-side too)
    if ($justLoggedIn) {
        // Flag will be cleared by JavaScript after popup logic runs
    }
?>

<!-- Promotion Popup Modal -->
<div id="promoPopup" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black bg-opacity-50 p-4 overflow-y-auto" style="display: none;">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden transform transition-all my-8 mx-auto max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button onclick="closePromoPopup()" class="absolute top-4 right-4 z-10 bg-white rounded-full p-2 hover:bg-gray-100 transition-colors shadow-lg">
      <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>

    <!-- Promo Image (if exists) -->
    <?php if ($latestPromo['image']): ?>
      <div class="relative">
        <img src="<?php echo htmlspecialchars($latestPromo['image']); ?>" 
             alt="<?php echo htmlspecialchars($latestPromo['title']); ?>"
             class="w-full h-64 object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
      </div>
    <?php else: ?>
      <div class="bg-gradient-to-br from-orange-400 to-orange-600 p-12 text-center">
        <div class="text-8xl mb-4">🎁</div>
      </div>
    <?php endif; ?>

    <!-- Promo Content -->
    <div class="p-6">
      <!-- Discount Badge -->
      <div class="flex justify-center mb-4">
        <div class="bg-orange-500 text-white px-6 py-3 rounded-full font-bold text-2xl shadow-lg">
          <?php 
            echo $latestPromo['discount_type'] === 'percent' 
              ? $latestPromo['discount_value'] . '% OFF' 
              : '₱' . number_format($latestPromo['discount_value']) . ' OFF';
          ?>
        </div>
      </div>

      <!-- Title & Description -->
      <h2 class="text-3xl font-extrabold text-gray-900 text-center mb-2">
        <?php echo htmlspecialchars($latestPromo['title']); ?>
      </h2>
      
      <?php if ($latestPromo['description']): ?>
        <p class="text-gray-600 text-center mb-4">
          <?php echo htmlspecialchars($latestPromo['description']); ?>
        </p>
      <?php endif; ?>

      <!-- Promo Code Box -->
      <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border-2 border-dashed border-orange-400 rounded-xl p-4 mb-4">
        <p class="text-xs text-gray-600 text-center mb-2">Use this code at checkout:</p>
        <div class="flex items-center justify-center gap-3">
          <p class="text-3xl font-mono font-bold text-orange-600" id="popup-code">
            <?php echo htmlspecialchars($latestPromo['code']); ?>
          </p>
          <button onclick="copyPopupCode('<?php echo htmlspecialchars($latestPromo['code']); ?>')" 
                  class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
            📋 Copy
          </button>
        </div>
      </div>

      <!-- Terms -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4 text-xs text-gray-600 space-y-1">
        <?php if ($latestPromo['min_purchase'] > 0): ?>
          <p>• Min. purchase: ₱<?php echo number_format($latestPromo['min_purchase']); ?></p>
        <?php endif; ?>
        <?php if ($latestPromo['max_discount']): ?>
          <p>• Max. discount: ₱<?php echo number_format($latestPromo['max_discount']); ?></p>
        <?php endif; ?>
        <?php if ($latestPromo['end_date']): ?>
          <p>• Valid until <?php echo date('M d, Y', strtotime($latestPromo['end_date'])); ?></p>
        <?php endif; ?>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-2">
        <a href="shop.php" onclick="closePromoPopup()" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white text-center font-bold py-3 rounded-lg transition-colors">
          Shop Now
        </a>
        <a href="promotions.php" onclick="closePromoPopup()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 text-center font-bold py-3 rounded-lg transition-colors">
          View All Promos
        </a>
      </div>

      <!-- Don't Show Again -->
      <div class="mt-4 text-center">
        <label class="flex items-center justify-center gap-2 text-sm text-gray-600 cursor-pointer hover:text-gray-800">
          <input type="checkbox" id="dontShowAgain" class="w-4 h-4">
          <span>Don't show this again</span>
        </label>
      </div>
    </div>
  </div>
</div>

<!-- Copy Success Toast -->
<div id="copyToast" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-y-full opacity-0 transition-all duration-300 z-[10000] pointer-events-none">
  <p class="font-semibold">✅ Code copied!</p>
</div>

<script>
(function() {
  const PROMO_ID = <?php echo $promoId; ?>;
  const COOKIE_NAME = 'promo_seen_' + PROMO_ID;
  const COOKIE_EXPIRY_DAYS = 30;
  const JUST_LOGGED_IN = <?php echo $justLoggedIn ? 'true' : 'false'; ?>;

  // Check if user has already seen this specific promo
  function hasSeenPromo() {
    // If user just logged in, always show popup (ignore cookies)
    if (JUST_LOGGED_IN) {
      return false;
    }
    return document.cookie.split(';').some(cookie => {
      return cookie.trim().startsWith(COOKIE_NAME + '=');
    });
  }

  // Set cookie to mark promo as seen
  function markPromoAsSeen(permanent = false) {
    const days = permanent ? COOKIE_EXPIRY_DAYS : 1; // If "don't show again" is checked, save for 30 days
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = COOKIE_NAME + '=1;expires=' + date.toUTCString() + ';path=/';
  }

  // Show popup
  function showPromoPopup() {
    const popup = document.getElementById('promoPopup');
    if (popup) {
      popup.style.display = 'flex';
      // Add animation
      setTimeout(() => {
        popup.classList.add('animate-fade-in');
      }, 100);
    }
  }

  // Close popup
  window.closePromoPopup = function() {
    const popup = document.getElementById('promoPopup');
    const dontShowAgain = document.getElementById('dontShowAgain');
    
    if (dontShowAgain && dontShowAgain.checked) {
      markPromoAsSeen(true); // Permanent cookie
    } else {
      markPromoAsSeen(false); // Session cookie (1 day)
    }
    
    if (popup) {
      popup.style.display = 'none';
    }
  };

  // Copy code function
  window.copyPopupCode = function(code) {
    navigator.clipboard.writeText(code).then(() => {
      const toast = document.getElementById('copyToast');
      toast.style.transform = 'translateY(0)';
      toast.style.opacity = '1';
      
      // Hide after 2 seconds with smooth fade out
      setTimeout(() => {
        toast.style.transform = 'translateY(150%)';
        toast.style.opacity = '0';
      }, 2000);
    }).catch(err => {
      alert('Code: ' + code);
    });
  };

  // Show popup after 2 seconds if not seen before
  if (!hasSeenPromo()) {
    setTimeout(showPromoPopup, 2000);
  }
  
  // Clear the just_logged_in flag after popup logic runs
  if (JUST_LOGGED_IN) {
    // Clear the session flag so it doesn't show again on page refresh
    fetch('clear_login_flag.php', { method: 'POST' }).catch(() => {});
  }

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      window.closePromoPopup();
    }
  });

  // Close on outside click
  document.getElementById('promoPopup')?.addEventListener('click', function(e) {
    if (e.target === this) {
      window.closePromoPopup();
    }
  });
})();
</script>

<style>
/* Prevent overflow */
#promoPopup {
  overflow-y: auto;
  overflow-x: hidden;
}

#promoPopup > div {
  max-height: 90vh;
  overflow-y: auto;
  overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
}

/* Animation */
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.9); }
  to { opacity: 1; transform: scale(1); }
}
.animate-fade-in > div {
  animation: fadeIn 0.3s ease-out;
}

/* Responsive sizing */
@media (max-width: 640px) {
  #promoPopup > div {
    max-height: 95vh;
    margin: 1rem;
  }
  
  #promoPopup img {
    max-height: 12rem;
    object-fit: cover;
  }
}

/* Small height screens */
@media (max-height: 700px) {
  #promoPopup > div {
    max-height: 98vh;
    margin: 0.5rem;
  }
  
  #promoPopup .p-6 {
    padding: 1rem;
  }
  
  #promoPopup img {
    max-height: 10rem;
  }
}

/* Very small screens */
@media (max-height: 600px) {
  #promoPopup > div {
    max-height: 99vh;
    margin: 0.25rem;
  }
  
  #promoPopup img {
    max-height: 8rem;
  }
}
</style>

<?php endif; ?>

