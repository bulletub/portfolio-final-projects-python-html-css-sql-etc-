<?php
// promotions.php - Customer-facing promotions page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database.php';

// Fetch all active promotions
$stmt = $pdo->query("
    SELECT * FROM promos 
    WHERE is_active = 1 
    AND (start_date IS NULL OR start_date <= NOW())
    AND (end_date IS NULL OR end_date >= NOW())
    ORDER BY created_at DESC
");
$promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Promotions & Discounts | PetPantry+</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="index.css">
</head>
<body class="bg-white">

<?php include 'header.php'; ?>

<main class="max-w-7xl mx-auto px-4 py-12 mt-24">
  <!-- Page Header -->
  <div class="text-center mb-12">
    <h1 class="text-4xl font-extrabold text-gray-900 mb-4">
      🎁 <span class="text-orange-500">Promotions</span> & Discounts
    </h1>
    <p class="text-gray-600 max-w-2xl mx-auto">
      Save more on your pet's favorite products! Browse our latest deals and exclusive offers.
    </p>
  </div>

  <?php if (!empty($promotions)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($promotions as $promo): ?>
        <div class="bg-gradient-to-br from-orange-50 to-white border-2 border-orange-200 rounded-xl p-6 hover:shadow-xl transition-shadow duration-300 relative overflow-hidden">
          <!-- Promo Image (if exists) -->
          <?php if ($promo['image']): ?>
            <div class="mb-4 rounded-lg overflow-hidden">
              <img src="<?php echo htmlspecialchars($promo['image']); ?>" 
                   alt="<?php echo htmlspecialchars($promo['title']); ?>"
                   class="w-full h-48 object-cover">
            </div>
          <?php endif; ?>

          <!-- Discount Badge -->
          <div class="absolute top-4 right-4 bg-orange-500 text-white px-4 py-2 rounded-full font-bold text-lg shadow-lg">
            <?php 
              echo $promo['discount_type'] === 'percent' 
                ? $promo['discount_value'] . '% OFF' 
                : '₱' . number_format($promo['discount_value']) . ' OFF';
            ?>
          </div>

          <!-- Promo Content -->
          <h3 class="text-2xl font-bold text-gray-900 mb-2">
            <?php echo htmlspecialchars($promo['title']); ?>
          </h3>
          
          <?php if ($promo['description']): ?>
            <p class="text-gray-600 mb-4">
              <?php echo htmlspecialchars($promo['description']); ?>
            </p>
          <?php endif; ?>

          <!-- Promo Code -->
          <div class="bg-white border-2 border-dashed border-orange-400 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs text-gray-500 mb-1">Use Code:</p>
                <p class="text-2xl font-mono font-bold text-orange-600" id="code-<?php echo $promo['id']; ?>">
                  <?php echo htmlspecialchars($promo['code']); ?>
                </p>
              </div>
              <button onclick="copyCode('<?php echo htmlspecialchars($promo['code']); ?>', <?php echo $promo['id']; ?>)" 
                      class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">
                📋 Copy
              </button>
            </div>
          </div>

          <!-- Conditions -->
          <div class="space-y-2 text-sm text-gray-600">
            <?php if ($promo['min_purchase'] > 0): ?>
              <p class="flex items-center gap-2">
                <span class="text-orange-500">•</span>
                Minimum purchase: <strong>₱<?php echo number_format($promo['min_purchase']); ?></strong>
              </p>
            <?php endif; ?>

            <?php if ($promo['max_discount']): ?>
              <p class="flex items-center gap-2">
                <span class="text-orange-500">•</span>
                Max discount: <strong>₱<?php echo number_format($promo['max_discount']); ?></strong>
              </p>
            <?php endif; ?>

            <?php if ($promo['usage_limit']): ?>
              <p class="flex items-center gap-2">
                <span class="text-orange-500">•</span>
                Limited to <strong><?php echo $promo['usage_limit'] - $promo['usage_count']; ?></strong> redemptions
              </p>
            <?php endif; ?>

            <?php if ($promo['end_date']): ?>
              <p class="flex items-center gap-2">
                <span class="text-orange-500">•</span>
                Valid until <strong><?php echo date('F d, Y', strtotime($promo['end_date'])); ?></strong>
              </p>
            <?php endif; ?>
          </div>

          <!-- Shop Now Button -->
          <a href="shop.php" class="block mt-4 bg-orange-500 hover:bg-orange-600 text-white text-center font-bold py-3 rounded-lg transition-colors">
            Shop Now
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center py-16">
      <div class="text-6xl mb-4">🎁</div>
      <h3 class="text-2xl font-bold text-gray-800 mb-2">No Active Promotions</h3>
      <p class="text-gray-600 mb-6">Check back soon for exciting deals!</p>
      <a href="shop.php" class="inline-block bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-lg">
        Continue Shopping
      </a>
    </div>
  <?php endif; ?>

  <!-- Toast Notification -->
  <div id="toast" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg transform translate-y-full opacity-0 transition-all duration-300 z-50 pointer-events-none">
    <p class="font-semibold">✅ Code copied to clipboard!</p>
  </div>
</main>

<?php include 'footer.php'; ?>

<script>
function copyCode(code, promoId) {
  // Copy to clipboard
  navigator.clipboard.writeText(code).then(() => {
    // Show toast notification
    const toast = document.getElementById('toast');
    toast.style.transform = 'translateY(0)';
    toast.style.opacity = '1';
    
    // Hide after 2 seconds with fade out
    setTimeout(() => {
      toast.style.transform = 'translateY(150%)';
      toast.style.opacity = '0';
    }, 2000);
  }).catch(err => {
    alert('Code: ' + code);
  });
}
</script>

</body>
</html>

