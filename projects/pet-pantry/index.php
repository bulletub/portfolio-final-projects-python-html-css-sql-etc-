<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'database.php'; // expects $pdo
require_once 'settings_helper.php';

// Get currency settings with error handling
try {
    $currencySymbol = getCurrencySymbol();
    $currencyCode = getDefaultCurrency();
    
    // Fallback if functions return null/empty
    if (empty($currencySymbol)) {
        $currencySymbol = '₱'; // Default fallback
    }
    if (empty($currencyCode)) {
        $currencyCode = 'PHP'; // Default fallback
    }
} catch (Exception $e) {
    // If any error, use defaults
    $currencySymbol = '₱';
    $currencyCode = 'PHP';
}

// --- Helper function to fetch products ---
function fetchProducts($pdo, $query) {
    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ==============================
   QUERIES
   ============================== */

// Check if CMS-managed sections exist
$cmsExists = $pdo->query("SHOW TABLES LIKE 'homepage_sections'")->rowCount() > 0;

// Best Sellers - Use CMS if available, otherwise fall back to automatic
if ($cmsExists) {
    $cmsBestSellers = fetchProducts($pdo, "
        SELECT p.id, p.name, p.description, p.price, p.subcategory, p.image, p.stock, p.images
        FROM homepage_sections hs 
        JOIN products p ON hs.product_id = p.id 
        WHERE hs.section_name='bestseller' 
        ORDER BY hs.display_order ASC
        LIMIT 8
    ");
    
    $bestSellers = !empty($cmsBestSellers) ? $cmsBestSellers : fetchProducts($pdo, "
        SELECT p.id, p.name, p.description, p.price, p.subcategory, p.image, p.stock, p.images,
               COUNT(o.id) AS total_orders
        FROM products p
        JOIN orders o ON p.id = o.product_id
        GROUP BY p.id
        ORDER BY total_orders DESC
        LIMIT 8
    ");
} else {
    $bestSellers = fetchProducts($pdo, "
        SELECT p.id, p.name, p.description, p.price, p.subcategory, p.image, p.stock, p.images,
               COUNT(o.id) AS total_orders
        FROM products p
        JOIN orders o ON p.id = o.product_id
        GROUP BY p.id
        ORDER BY total_orders DESC
        LIMIT 8
    ");
}

// Convert prices for best sellers
$baseCurrency = getSetting('base_currency', 'PHP');
foreach ($bestSellers as &$product) {
    if ($baseCurrency !== $currencyCode && $product['price'] > 0) {
        $product['price'] = getConvertedPrice($product['price']);
    }
}
unset($product);

// Featured Items - Use CMS if available, otherwise fall back to automatic
if ($cmsExists) {
    $cmsFeatured = fetchProducts($pdo, "
        SELECT p.id, p.name, p.description, p.price, p.subcategory, p.image, p.stock, p.images
        FROM homepage_sections hs 
        JOIN products p ON hs.product_id = p.id 
        WHERE hs.section_name='featured' 
        ORDER BY hs.display_order ASC
        LIMIT 8
    ");
    
    $featuredItems = !empty($cmsFeatured) ? $cmsFeatured : fetchProducts($pdo, "
        SELECT p.id, p.name, p.description, p.price, p.subcategory, p.image, p.stock, p.images,
               AVG(r.rating) AS avg_rating
        FROM products p
        JOIN orders o ON p.id = o.product_id
        JOIN order_reviews r ON o.id = r.order_id
        GROUP BY p.id
        HAVING avg_rating >= 4
        ORDER BY avg_rating DESC
        LIMIT 8
    ");
} else {
    $featuredItems = fetchProducts($pdo, "
        SELECT p.id, p.name, p.description, p.price, p.subcategory, p.image, p.stock, p.images,
               AVG(r.rating) AS avg_rating
        FROM products p
        JOIN orders o ON p.id = o.product_id
        JOIN order_reviews r ON o.id = r.order_id
        GROUP BY p.id
        HAVING avg_rating >= 4
        ORDER BY avg_rating DESC
        LIMIT 8
    ");
}

// Convert prices for featured items
foreach ($featuredItems as &$product) {
    if ($baseCurrency !== $currencyCode && $product['price'] > 0) {
        $product['price'] = getConvertedPrice($product['price']);
    }
}
unset($product);

// Promotional Items
$promoItems = fetchProducts($pdo, "
    SELECT id, name, description, price, subcategory, image, stock, images
    FROM products
    WHERE promo = 1
    ORDER BY id DESC
    LIMIT 8
");

// Convert prices for promo items
foreach ($promoItems as &$product) {
    if ($baseCurrency !== $currencyCode && $product['price'] > 0) {
        $product['price'] = getConvertedPrice($product['price']);
    }
}
unset($product);

// New Arrivals
$newArrivals = fetchProducts($pdo, "
    SELECT id, name, description, price, subcategory, image, stock, images
    FROM products
    ORDER BY id DESC
    LIMIT 8
");

// Convert prices for new arrivals
foreach ($newArrivals as &$product) {
    if ($baseCurrency !== $currencyCode && $product['price'] > 0) {
        $product['price'] = getConvertedPrice($product['price']);
    }
}
unset($product);

// Fetch carousel images from CMS
$carouselImagesExist = $pdo->query("SHOW TABLES LIKE 'carousel_images'")->rowCount() > 0;
if ($carouselImagesExist) {
    $carouselImages = $pdo->query("
        SELECT * FROM carousel_images 
        WHERE is_active = 1 
        ORDER BY display_order ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $carouselImages = [];
}

// Fallback to default images if no CMS images
if (empty($carouselImages)) {
    $carouselImages = [
        ['image_path' => 'images/bg1.png', 'alt_text' => 'Happy pets enjoying premium food'],
        ['image_path' => 'images/bg2.png', 'alt_text' => 'Nutritious pet food bowl'],
        ['image_path' => 'images/bg3.png', 'alt_text' => 'Healthy pets running outdoors']
    ];
}

/* ==============================
   CAROUSEL RENDERER
   ============================== */
function renderProductCarousel($id, $products, $currencySymbol = '₱') {
    ?>
    <div class="relative overflow-hidden">
      <button class="absolute left-0 top-1/2 -translate-y-1/2 bg-orange-500 text-white p-3 rounded-full shadow z-10"
              onclick="slideCarousel('<?= $id ?>', -1)">&#10094;</button>
      <div id="<?= $id ?>" class="flex transition-transform duration-500 ease-in-out">
        <?php foreach ($products as $p): ?>
          <?php $imgSrc = $p['image'] ?: 'https://via.placeholder.com/300'; ?>
          <article class="w-1/2 md:w-1/4 flex-shrink-0 px-2">
            <div class="border rounded-lg p-4 shadow-sm relative hover:shadow-lg cursor-pointer"
                 onclick="viewProduct(<?= $p['id'] ?>)">
              <div class="h-48 flex items-center justify-center mb-3">
                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                     class="max-h-full max-w-full object-contain">
              </div>
              <div class="text-center">
                <p class="text-xs font-semibold text-gray-700"><?= htmlspecialchars($p['name']) ?></p>
                <p class="text-orange-500 font-semibold text-lg"><?= formatCurrency($p['price']) ?></p>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <button class="absolute right-0 top-1/2 -translate-y-1/2 bg-orange-500 text-white p-3 rounded-full shadow z-10"
              onclick="slideCarousel('<?= $id ?>', 1)">&#10095;</button>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PetPantry+</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="index.css">
  <style>
    .carousel-item { opacity: 0; position: absolute; inset: 0; transition: opacity 1s ease-in-out; }
    .carousel-item.opacity-100 { opacity: 1; position: relative; }
  </style>
</head>
<body class="bg-white">

<?php include 'header.php'; ?>

<main>
  <!-- Hero Carousel -->
<section class="relative h-[720px] overflow-hidden">
  <!-- Slides -->
  <div class="carousel relative w-full h-full">
    <div class="carousel-inner relative w-full h-full">
      <?php foreach ($carouselImages as $index => $img): ?>
      <div class="carousel-item <?php echo $index === 0 ? 'opacity-100' : ''; ?>">
        <img 
          src="<?php echo htmlspecialchars($img['image_path']); ?>" 
          alt="<?php echo htmlspecialchars($img['alt_text'] ?? ''); ?>" 
          class="w-full h-full object-cover"
        >
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Dots -->
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2 z-20">
      <?php foreach ($carouselImages as $index => $img): ?>
      <button 
        class="carousel-dot w-3 h-3 rounded-full bg-white/50" 
        aria-label="Go to slide <?php echo $index + 1; ?>" 
        data-index="<?php echo $index; ?>"
      ></button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Hero Content -->
  <div class="absolute inset-0 flex flex-col md:flex-row items-center max-w-7xl mx-auto px-8 py-16 gap-6 z-10">
    <div class="flex-1 text-center md:text-left max-w-lg md:ml-32">
      <h1 class="text-4xl md:text-5xl font-extrabold uppercase text-white leading-tight">
        High Quality <br>
        <span class="text-5xl md:text-6xl">Pet Food</span>
      </h1>
      <p class="mt-3 text-sm text-white/80">
        Your Pet Deserves the Best
      </p>
      <a 
        href="shop.php" 
        class="btn-black mt-6 inline-block"
        aria-label="Shop now for high quality pet food"
      >
        Shop Now
      </a>
    </div>
  </div>
</section>


  <!-- Sections -->
  <section class="py-16 max-w-7xl mx-auto px-4">
    <h2 class="text-center text-2xl font-extrabold mb-10"><span class="text-orange-500">Best </span>Sellers</h2>
    <?php renderProductCarousel("bestSeller", $bestSellers, $currencySymbol); ?>
  </section>

  <section class="py-16 max-w-7xl mx-auto px-4">
    <h2 class="text-center text-2xl font-extrabold mb-10"><span class="text-orange-500">Featured </span>Items</h2>
    <?php renderProductCarousel("featuredItems", $featuredItems, $currencySymbol); ?>
  </section>



  <section class="py-16 max-w-7xl mx-auto px-4">
    <h2 class="text-center text-2xl font-extrabold mb-10"><span class="text-orange-500">New </span>Arrivals</h2>
    <?php renderProductCarousel("newArrivals", $newArrivals, $currencySymbol); ?>
  </section>

  <!-- Testimonial -->
<section class="relative min-h-[60vh] mt-24 flex items-center">
  <!-- Background image -->
  <img 
    src="images/bg4.png" 
    alt="Taste Guarantee background" 
    class="absolute inset-0 w-full h-full object-cover"
  >

  <!-- Content -->
  <div class="relative z-10 max-w-xl px-6 py-12 mx-auto text-center md:text-left md:ml-24">
    <h3 class="text-base md:text-lg mb-4 text-gray-700">Taste Guarantee</h3>
    <h2 class="text-2xl md:text-3xl font-bold mb-3 text-gray-700">
      Taste it, love it or we’ll replace it... Guaranteed!
    </h2>
    <p class="text-sm md:text-base leading-relaxed mb-6 text-gray-700">
      At PetPantry+, we believe your dog and cat will love their food so much that if they don’t, we’ll help you find a replacement. That's our taste guarantee.
    </p>
    <button class="btn-black">Find out more</button>
  </div>
</section>

  
    <!-- Popular Brands Section -->
  <section class="py-14 max-w-7xl mx-auto px-4">
    <h2 class="text-center text-2xl font-extrabold mb-10">
      <span class="text-orange-500">Popular </span><span>Brands</span>
    </h2>
    <div class="bg-white border rounded-lg p-6 grid grid-cols-5 gap-6 shadow-sm max-w-xl mx-auto">
      <img src="images/brand1.png" alt="Brand 1 logo" class="rounded-full w-20 h-20 object-contain cursor-pointer hover:opacity-80 transition">
      <img src="images/brand2.png" alt="Brand 2 logo" class="rounded-full w-20 h-20 object-contain cursor-pointer hover:opacity-80 transition">
      <img src="images/brand3.png" alt="Brand 3 logo" class="rounded-full w-20 h-20 object-contain cursor-pointer hover:opacity-80 transition">
      <img src="images/brand4.png" alt="Brand 4 logo" class="rounded-full w-20 h-20 object-contain cursor-pointer hover:opacity-80 transition">
      <img src="images/brand5.png" alt="Brand 5 logo" class="rounded-full w-20 h-20 object-contain cursor-pointer hover:opacity-80 transition">
    </div>
  </section>
  
</main>



<?php include 'footer.php'; ?>

<!-- Product Modal -->
<div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 overflow-y-auto overflow-x-hidden p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full relative flex flex-col my-8 mx-auto max-h-[90vh] overflow-y-auto">
    <div class="flex flex-col md:flex-row">
      <div class="md:w-1/2 p-4 flex flex-col items-center">
        <img id="modalImage" src="" alt="Product Image" class="w-full h-96 object-contain rounded-lg mb-4">
        <div id="modalThumbnails" class="flex gap-2 overflow-x-auto w-full"></div>
      </div>
      <div class="md:w-1/2 p-6 flex flex-col justify-between relative">
        <div>
          <h2 id="modalName" class="text-2xl font-bold mb-2 text-gray-900"></h2>
          <p id="modalDescription" class="text-gray-600 mb-4"></p>
          <p class="text-sm text-gray-500 mb-2"><b>Stock:</b> <span id="modalStock"></span></p>
          <p class="text-xl text-orange-500 font-bold mb-4"><span id="modalPrice"></span></p>
          <div class="flex items-center gap-4 mb-4">
            <span class="font-semibold">Quantity:</span>
            <div class="flex items-center border rounded-md overflow-hidden">
              <button id="decreaseQty" class="px-3 py-1 text-gray-700 hover:bg-gray-200">-</button>
              <input id="modalQuantity" type="number" min="1" value="1" class="w-16 text-center outline-none border-l border-r border-gray-300">
              <button id="increaseQty" class="px-3 py-1 text-gray-700 hover:bg-gray-200">+</button>
            </div>
          </div>
        </div>
        <button id="modalAddToCart" class="mt-4 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg w-full">
          Add to Cart
        </button>
        <button id="closeModal" class="absolute top-4 right-4 text-gray-600 hover:text-gray-900 text-2xl font-bold">✕</button>
      </div>
    </div>
    
    <!-- Reviews Section -->
    <div class="w-full border-t p-4">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold">Customer Reviews</h3>
        <div class="text-sm text-gray-600">
          <span id="reviewsAverage" class="font-semibold text-orange-500">0.0</span>
          <span class="ml-1">/ 5 •</span>
          <span id="reviewsCount">0</span>
          <span class="ml-1">review(s)</span>
        </div>
      </div>
      <div id="reviewsList" class="space-y-3 max-h-60 overflow-y-auto pr-1"></div>
    </div>
  </div>
</div>



<script>
const productsData = {
  bestSellers: <?= json_encode($bestSellers) ?>,
  featuredItems: <?= json_encode($featuredItems) ?>,
  promoItems: <?= json_encode($promoItems) ?>,
  newArrivals: <?= json_encode($newArrivals) ?>
};

// Currency settings for JavaScript
window.CURRENCY_SYMBOL = '<?= $currencySymbol ?>';
window.CURRENCY_CODE = '<?= $currencyCode ?>';
</script>

<script src="index.js"></script>
<script>
/* Hero carousel */
document.addEventListener('DOMContentLoaded', () => {
  const items = document.querySelectorAll('.carousel-item');
  const dots = document.querySelectorAll('.carousel-dot');
  let current = 0;

  function showSlide(i){
    items[current].classList.remove('opacity-100');
    dots[current].classList.remove('bg-orange-500');
    current = i;
    items[current].classList.add('opacity-100');
    dots[current].classList.add('bg-orange-500');
  }
  dots.forEach((dot,i)=>dot.addEventListener('click',()=>showSlide(i)));
  setInterval(()=>showSlide((current+1)%items.length),7000);
});



/* Product carousels */
let carouselPositions = {};
function slideCarousel(id, dir){
  const carousel = document.getElementById(id);
  const item = carousel.querySelector("article");
  if (!item) return;
  const itemWidth = item.offsetWidth;
  const visible = window.innerWidth < 768 ? 2 : 4;
  const total = carousel.children.length;
  if (!carouselPositions[id]) carouselPositions[id] = 0;
  carouselPositions[id] += dir * itemWidth;
  const max = (total - visible) * itemWidth;
  if (carouselPositions[id] < 0) carouselPositions[id] = 0;
  if (carouselPositions[id] > max) carouselPositions[id] = max;
  carousel.style.transform = `translateX(-${carouselPositions[id]}px)`;
}
</script>

<?php include 'disclaimer_popup.php'; ?>

</body>
</html>
