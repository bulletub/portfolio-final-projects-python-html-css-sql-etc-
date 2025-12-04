<?php
// admin_cms.php - Content Management System
session_start();
include('database.php');
include('audit_helper.php');

// --- Role-based Access Control ---
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['name'] ?? 'Admin';
$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$userId || (!in_array('super_admin', $roles) && !in_array('admin_dashboard', $roles))) {
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

$message = "";

// --- Handle Carousel Image Upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_carousel') {
    $altText = trim($_POST['alt_text'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    
    if (isset($_FILES['carousel_image']) && $_FILES['carousel_image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . "/images/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        
        $fileName = 'carousel_' . time() . '_' . preg_replace("/[^A-Za-z0-9._-]/", "_", basename($_FILES["carousel_image"]["name"]));
        $targetFile = $targetDir . $fileName;
        $publicPath = "images/" . $fileName;
        
        if (move_uploaded_file($_FILES["carousel_image"]["tmp_name"], $targetFile)) {
            $stmt = $pdo->prepare("INSERT INTO carousel_images (image_path, alt_text, display_order) VALUES (?, ?, ?)");
            $stmt->execute([$publicPath, $altText, $displayOrder]);
            
            logAudit($pdo, $userId, $userName, 'CREATE', 'carousel_images', $pdo->lastInsertId(), null, [
                'image_path' => $publicPath,
                'alt_text' => $altText,
                'display_order' => $displayOrder
            ], 'Added new carousel image');
            
            $message = "<p class='text-green-600'>✅ Carousel image added successfully.</p>";
        }
    }
}

// --- Handle Carousel Image Delete ---
if (isset($_GET['delete_carousel'])) {
    $id = (int)$_GET['delete_carousel'];
    $stmt = $pdo->prepare("SELECT * FROM carousel_images WHERE id=?");
    $stmt->execute([$id]);
    $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($oldData) {
        $stmt = $pdo->prepare("DELETE FROM carousel_images WHERE id=?");
        $stmt->execute([$id]);
        
        logAudit($pdo, $userId, $userName, 'DELETE', 'carousel_images', $id, $oldData, null, 'Deleted carousel image');
        
        // Delete file
        if (file_exists($oldData['image_path'])) {
            unlink($oldData['image_path']);
        }
    }
    header("Location: admin_cms.php?tab=carousel");
    exit;
}

// --- Handle Carousel Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_carousel') {
    $id = (int)($_POST['carousel_id'] ?? 0);
    $altText = trim($_POST['alt_text'] ?? '');
    $displayOrder = (int)($_POST['display_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $pdo->prepare("SELECT * FROM carousel_images WHERE id=?");
    $stmt->execute([$id]);
    $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("UPDATE carousel_images SET alt_text=?, display_order=?, is_active=? WHERE id=?");
    $stmt->execute([$altText, $displayOrder, $isActive, $id]);
    
    logAudit($pdo, $userId, $userName, 'UPDATE', 'carousel_images', $id, $oldData, [
        'alt_text' => $altText,
        'display_order' => $displayOrder,
        'is_active' => $isActive
    ], 'Updated carousel image');
    
    $message = "<p class='text-green-600'>✅ Carousel updated successfully.</p>";
}

// --- Handle Homepage Section Update (Featured/Best Sellers) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_section') {
    $sectionName = $_POST['section_name'] ?? '';
    $productIds = $_POST['product_ids'] ?? [];
    
    // Delete existing products for this section
    $stmt = $pdo->prepare("DELETE FROM homepage_sections WHERE section_name=?");
    $stmt->execute([$sectionName]);
    
    // Insert new products
    if (!empty($productIds)) {
        $stmt = $pdo->prepare("INSERT INTO homepage_sections (section_name, product_id, display_order) VALUES (?, ?, ?)");
        foreach ($productIds as $index => $productId) {
            $stmt->execute([$sectionName, $productId, $index + 1]);
        }
    }
    
    logAudit($pdo, $userId, $userName, 'UPDATE', 'homepage_sections', null, null, [
        'section_name' => $sectionName,
        'product_count' => count($productIds)
    ], "Updated {$sectionName} section with " . count($productIds) . " products");
    
    $message = "<p class='text-green-600'>✅ Section updated successfully.</p>";
}

// --- Handle Promotion Add ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_promotion') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discountType = $_POST['discount_type'] ?? 'percent';
    $discountValue = (float)($_POST['discount_value'] ?? 0);
    $minPurchase = (float)($_POST['min_purchase'] ?? 0);
    $maxDiscount = $_POST['max_discount'] ? (float)$_POST['max_discount'] : null;
    $usageLimit = $_POST['usage_limit'] ? (int)$_POST['usage_limit'] : null;
    $showPopup = isset($_POST['show_popup']) ? 1 : 0;
    $startDate = $_POST['start_date'] ?: null;
    $endDate = $_POST['end_date'] ?: null;
    
    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['promo_image']) && $_FILES['promo_image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . "/images/promos/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        
        $fileName = 'promo_' . time() . '_' . preg_replace("/[^A-Za-z0-9._-]/", "_", basename($_FILES["promo_image"]["name"]));
        $targetFile = $targetDir . $fileName;
        $imagePath = "images/promos/" . $fileName;
        move_uploaded_file($_FILES["promo_image"]["tmp_name"], $targetFile);
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO promos (code, title, description, discount_type, discount_value, min_purchase, max_discount, 
                               usage_limit, image, show_popup, start_date, end_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$code, $title, $description, $discountType, $discountValue, $minPurchase, 
                       $maxDiscount, $usageLimit, $imagePath, $showPopup, $startDate, $endDate]);
        
        logAudit($pdo, $userId, $userName, 'CREATE', 'promos', $pdo->lastInsertId(), null, [
            'code' => $code,
            'title' => $title,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'show_popup' => $showPopup
        ], 'Created new promotion: ' . $title);
        
        $message = "<p class='text-green-600'>✅ Promotion created successfully!</p>";
    } catch (PDOException $e) {
        $message = "<p class='text-red-600'>❌ Error: " . ($e->getCode() == 23000 ? "Promo code already exists" : $e->getMessage()) . "</p>";
    }
}

// --- Handle Promotion Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_promotion') {
    $id = (int)($_POST['promo_id'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $showPopup = isset($_POST['show_popup']) ? 1 : 0;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discountValue = (float)($_POST['discount_value'] ?? 0);
    $minPurchase = (float)($_POST['min_purchase'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT * FROM promos WHERE id=?");
    $stmt->execute([$id]);
    $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("
        UPDATE promos 
        SET title=?, description=?, discount_value=?, min_purchase=?, is_active=?, show_popup=? 
        WHERE id=?
    ");
    $stmt->execute([$title, $description, $discountValue, $minPurchase, $isActive, $showPopup, $id]);
    
    logAudit($pdo, $userId, $userName, 'UPDATE', 'promos', $id, $oldData, [
        'title' => $title,
        'is_active' => $isActive,
        'show_popup' => $showPopup
    ], 'Updated promotion: ' . $title);
    
    $message = "<p class='text-green-600'>✅ Promotion updated successfully!</p>";
}

// --- Handle Promotion Delete ---
if (isset($_GET['delete_promo'])) {
    $id = (int)$_GET['delete_promo'];
    $stmt = $pdo->prepare("SELECT * FROM promos WHERE id=?");
    $stmt->execute([$id]);
    $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($oldData) {
        $stmt = $pdo->prepare("DELETE FROM promos WHERE id=?");
        $stmt->execute([$id]);
        
        logAudit($pdo, $userId, $userName, 'DELETE', 'promos', $id, $oldData, null, 'Deleted promotion: ' . $oldData['title']);
        
        // Delete image if exists
        if ($oldData['image'] && file_exists($oldData['image'])) {
            unlink($oldData['image']);
        }
    }
    header("Location: admin_cms.php?tab=promotions");
    exit;
}

// --- Handle Page Content Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_page_content') {
    $pageName = $_POST['page_name'] ?? '';
    $updates = $_POST['updates'] ?? [];
    
    // Handle map coordinates separately
    if (isset($updates['map_lat']) && isset($updates['map_lng'])) {
        $mapCoords = json_encode([
            'lat' => floatval($updates['map_lat']),
            'lng' => floatval($updates['map_lng'])
        ]);
        
        $stmt = $pdo->prepare("
            INSERT INTO cms_pages (page_name, section_key, content_type, content_value, updated_by)
            VALUES (?, 'map_coordinates', 'json', ?, ?)
            ON DUPLICATE KEY UPDATE content_value = ?, updated_by = ?
        ");
        $stmt->execute([$pageName, $mapCoords, $userId, $mapCoords, $userId]);
        
        unset($updates['map_lat'], $updates['map_lng']);
    }
    
    foreach ($updates as $sectionKey => $contentValue) {
        $stmt = $pdo->prepare("
            INSERT INTO cms_pages (page_name, section_key, content_type, content_value, updated_by)
            VALUES (?, ?, 'text', ?, ?)
            ON DUPLICATE KEY UPDATE content_value = ?, updated_by = ?
        ");
        $stmt->execute([$pageName, $sectionKey, $contentValue, $userId, $contentValue, $userId]);
    }
    
    logAudit($pdo, $userId, $userName, 'UPDATE', 'cms_pages', null, null, [
        'page_name' => $pageName,
        'sections_updated' => count($updates)
    ], "Updated {$pageName} page content");
    
    $message = "<p class='text-green-600'>✅ Page content updated successfully!</p>";
}

// --- Handle Team Member Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_team_members') {
    $teamMembersData = json_decode($_POST['team_members'] ?? '[]', true);
    
    // Handle image uploads for team members
    if (!empty($_FILES['team_images']['name'])) {
        $targetDir = __DIR__ . "/images/team/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        foreach ($_FILES['team_images']['name'] as $index => $fileName) {
            if (!empty($fileName) && $_FILES['team_images']['error'][$index] === UPLOAD_ERR_OK) {
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array($fileExt, $allowedExts)) {
                    $newFileName = 'team_' . time() . '_' . $index . '.' . $fileExt;
                    $targetFile = $targetDir . $newFileName;
                    $publicPath = "images/team/" . $newFileName;
                    
                    if (move_uploaded_file($_FILES['team_images']['tmp_name'][$index], $targetFile)) {
                        // Update the image path in the team members data
                        if (isset($teamMembersData[$index])) {
                            // Delete old image if exists
                            if (!empty($teamMembersData[$index]['image']) && file_exists($teamMembersData[$index]['image'])) {
                                unlink($teamMembersData[$index]['image']);
                            }
                            $teamMembersData[$index]['image'] = $publicPath;
                        }
                    }
                }
            }
        }
    }
    
    $teamMembers = json_encode($teamMembersData);
    
    $stmt = $pdo->prepare("
        UPDATE cms_pages 
        SET content_value = ?, updated_by = ? 
        WHERE page_name = 'about' AND section_key = 'team_members'
    ");
    $stmt->execute([$teamMembers, $userId]);
    
    logAudit($pdo, $userId, $userName, 'UPDATE', 'cms_pages', null, null, [
        'page_name' => 'about',
        'section' => 'team_members'
    ], "Updated team members on About page");
    
    $message = "<p class='text-green-600'>✅ Team members updated successfully!</p>";
}

// --- Fetch Data ---
$carouselImages = $pdo->query("SELECT * FROM carousel_images ORDER BY display_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$allProducts = $pdo->query("SELECT id, name, price, image FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch current homepage sections
$featuredProducts = $pdo->query("
    SELECT p.*, hs.display_order 
    FROM homepage_sections hs 
    JOIN products p ON hs.product_id = p.id 
    WHERE hs.section_name='featured' 
    ORDER BY hs.display_order ASC
")->fetchAll(PDO::FETCH_ASSOC);

$bestsellerProducts = $pdo->query("
    SELECT p.*, hs.display_order 
    FROM homepage_sections hs 
    JOIN products p ON hs.product_id = p.id 
    WHERE hs.section_name='bestseller' 
    ORDER BY hs.display_order ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch promotions
$promotions = $pdo->query("SELECT * FROM promos ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch page content
$contactContent = [];
$aboutContent = [];
$stmt = $pdo->query("SELECT section_key, content_value FROM cms_pages WHERE page_name='contact'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $contactContent[$row['section_key']] = $row['content_value'];
}
$stmt = $pdo->query("SELECT section_key, content_value FROM cms_pages WHERE page_name='about'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $aboutContent[$row['section_key']] = $row['content_value'];
}

$activeTab = $_GET['tab'] ?? 'preview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Content Management | PetPantry+</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      overflow-x: hidden;
      max-width: 100vw;
      width: 100%;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen font-sans text-gray-800">

<div class="flex min-h-screen">
  <?php include('admin_navbar.php'); ?>

  <div class="flex-1 p-8">
    <header class="flex justify-between items-center mb-6 py-4 px-2 md:px-0 border-b border-gray-200">
      <h1 class="text-2xl font-bold text-gray-800">📝 Content Management System</h1>
      <span class="text-gray-600">Welcome, <strong class="text-orange-500"><?php echo htmlspecialchars($userName); ?></strong></span>
    </header>

    <?php if (!empty($message)) echo "<div class='mb-4'>{$message}</div>"; ?>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-300">
      <nav class="flex space-x-4 overflow-x-auto">
        <a href="?tab=preview" class="px-4 py-2 whitespace-nowrap <?php echo $activeTab === 'preview' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-orange-500'; ?>">
          👁️ Live Preview & Edit
        </a>
        <a href="?tab=carousel" class="px-4 py-2 whitespace-nowrap <?php echo $activeTab === 'carousel' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-orange-500'; ?>">
          🎠 Hero Carousel
        </a>
        <a href="?tab=bestseller" class="px-4 py-2 whitespace-nowrap <?php echo $activeTab === 'bestseller' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-orange-500'; ?>">
          ⭐ Best Sellers
        </a>
        <a href="?tab=featured" class="px-4 py-2 whitespace-nowrap <?php echo $activeTab === 'featured' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-orange-500'; ?>">
          🔥 Featured Items
        </a>
        <a href="?tab=promotions" class="px-4 py-2 whitespace-nowrap <?php echo $activeTab === 'promotions' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-orange-500'; ?>">
          🎁 Promotions & Discounts
        </a>
        <a href="?tab=pages" class="px-4 py-2 whitespace-nowrap <?php echo $activeTab === 'pages' ? 'border-b-2 border-orange-500 text-orange-600 font-semibold' : 'text-gray-600 hover:text-orange-500'; ?>">
          📄 Pages (Contact & About)
        </a>
      </nav>
    </div>

    <!-- Live Preview & Edit -->
    <?php if ($activeTab === 'preview'): ?>
      <section>
        <div class="bg-white rounded-xl shadow-md border p-4 mb-4">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">🎨 Live Preview - Click Elements to Edit</h2>
            <div class="flex gap-2">
              <button onclick="refreshPreview()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                🔄 Refresh Preview
              </button>
              <a href="/" target="_blank" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">
                🌐 View Live Site
              </a>
            </div>
          </div>
          <p class="text-sm text-gray-600 mb-4">
            ℹ️ Click on carousel images, products, or sections below to edit them directly. Changes are saved immediately.
          </p>
        </div>

        <!-- Preview Container -->
        <div class="bg-white rounded-xl shadow-lg border overflow-hidden">
          <div id="previewContainer" class="relative">
            <!-- Loading State -->
            <div id="previewLoader" class="flex items-center justify-center h-96">
              <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mx-auto mb-4"></div>
                <p class="text-gray-600">Loading preview...</p>
              </div>
            </div>

            <!-- Preview Content will be loaded here -->
            <div id="previewContent" class="hidden"></div>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <!-- Carousel Management -->
    <?php if ($activeTab === 'carousel'): ?>
      <section class="mb-8">
        <div class="bg-white rounded-xl shadow-md border p-6">
          <h2 class="text-xl font-semibold mb-4">Add New Carousel Image</h2>
          <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="action" value="add_carousel">
            
            <div>
              <label class="block text-sm font-medium mb-1">Image</label>
              <input type="file" name="carousel_image" accept="image/*" required class="border rounded-lg p-2 w-full">
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-1">Alt Text</label>
              <input type="text" name="alt_text" placeholder="Description for accessibility" class="border rounded-lg p-2 w-full">
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-1">Display Order</label>
              <input type="number" name="display_order" value="0" min="0" class="border rounded-lg p-2 w-full">
            </div>
            
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg">
              Add Image
            </button>
          </form>
        </div>
      </section>

      <section>
        <div class="bg-white rounded-xl shadow-md border p-6">
          <h2 class="text-xl font-semibold mb-4">Current Carousel Images</h2>
          <div class="space-y-4">
            <?php foreach ($carouselImages as $img): ?>
              <div class="flex items-center gap-4 p-4 border rounded-lg hover:bg-gray-50">
                <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="<?php echo htmlspecialchars($img['alt_text']); ?>" class="w-32 h-20 object-cover rounded">
                <div class="flex-1">
                  <p class="font-semibold"><?php echo htmlspecialchars($img['alt_text'] ?: 'No description'); ?></p>
                  <p class="text-sm text-gray-600">Order: <?php echo $img['display_order']; ?> | Status: <?php echo $img['is_active'] ? '✅ Active' : '❌ Inactive'; ?></p>
                </div>
                <button onclick="editCarousel(<?php echo htmlspecialchars(json_encode($img)); ?>)" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">Edit</button>
                <a href="?delete_carousel=<?php echo $img['id']; ?>" onclick="return confirm('Delete this image?')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Delete</a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <!-- Best Sellers Management -->
    <?php if ($activeTab === 'bestseller'): ?>
      <section>
        <div class="bg-white rounded-xl shadow-md border p-6">
          <h2 class="text-xl font-semibold mb-4">Manage Best Sellers</h2>
          <p class="text-sm text-gray-600 mb-4">Select up to 8 products to display in the Best Sellers section. Drag to reorder.</p>
          
          <form method="POST" id="bestsellerForm">
            <input type="hidden" name="action" value="update_section">
            <input type="hidden" name="section_name" value="bestseller">
            
            <div class="mb-4">
              <label class="block text-sm font-medium mb-2">Add Product</label>
              <select id="addBestseller" class="border rounded-lg p-2 w-full">
                <option value="">-- Select Product --</option>
                <?php foreach ($allProducts as $p): ?>
                  <option value="<?php echo $p['id']; ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>" data-price="<?php echo $p['price']; ?>" data-image="<?php echo htmlspecialchars($p['image']); ?>">
                    <?php echo htmlspecialchars($p['name']); ?> (₱<?php echo number_format($p['price']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div id="bestsellerList" class="space-y-2 mb-4 min-h-[100px] border-2 border-dashed border-gray-300 rounded-lg p-4">
              <?php foreach ($bestsellerProducts as $p): ?>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-move" data-id="<?php echo $p['id']; ?>">
                  <span class="text-gray-400">☰</span>
                  <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="" class="w-12 h-12 object-cover rounded">
                  <span class="flex-1"><?php echo htmlspecialchars($p['name']); ?></span>
                  <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700">✕</button>
                </div>
              <?php endforeach; ?>
            </div>
            
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg">
              Save Best Sellers
            </button>
          </form>
        </div>
      </section>
    <?php endif; ?>

    <!-- Featured Items Management -->
    <?php if ($activeTab === 'featured'): ?>
      <section>
        <div class="bg-white rounded-xl shadow-md border p-6">
          <h2 class="text-xl font-semibold mb-4">Manage Featured Items</h2>
          <p class="text-sm text-gray-600 mb-4">Select up to 8 products to display in the Featured Items section. Drag to reorder.</p>
          
          <form method="POST" id="featuredForm">
            <input type="hidden" name="action" value="update_section">
            <input type="hidden" name="section_name" value="featured">
            
            <div class="mb-4">
              <label class="block text-sm font-medium mb-2">Add Product</label>
              <select id="addFeatured" class="border rounded-lg p-2 w-full">
                <option value="">-- Select Product --</option>
                <?php foreach ($allProducts as $p): ?>
                  <option value="<?php echo $p['id']; ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>" data-price="<?php echo $p['price']; ?>" data-image="<?php echo htmlspecialchars($p['image']); ?>">
                    <?php echo htmlspecialchars($p['name']); ?> (₱<?php echo number_format($p['price']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <div id="featuredList" class="space-y-2 mb-4 min-h-[100px] border-2 border-dashed border-gray-300 rounded-lg p-4">
              <?php foreach ($featuredProducts as $p): ?>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-move" data-id="<?php echo $p['id']; ?>">
                  <span class="text-gray-400">☰</span>
                  <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="" class="w-12 h-12 object-cover rounded">
                  <span class="flex-1"><?php echo htmlspecialchars($p['name']); ?></span>
                  <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700">✕</button>
                </div>
              <?php endforeach; ?>
            </div>
            
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg">
              Save Featured Items
            </button>
          </form>
        </div>
      </section>
    <?php endif; ?>

    <!-- Promotions & Discounts Management -->
    <?php if ($activeTab === 'promotions'): ?>
      <section class="mb-8">
        <div class="bg-white rounded-xl shadow-md border p-6">
          <h2 class="text-xl font-semibold mb-4">🎁 Create New Promotion</h2>
          <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="action" value="add_promotion">
            
            <div class="md:col-span-2">
              <label class="block text-sm font-medium mb-1">Promo Title *</label>
              <input type="text" name="title" required class="border rounded-lg p-2 w-full" placeholder="e.g., Summer Sale">
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-1">Promo Code * (Uppercase)</label>
              <input type="text" name="code" required class="border rounded-lg p-2 w-full" placeholder="e.g., SUMMER20" style="text-transform:uppercase">
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-1">Discount Type</label>
              <select name="discount_type" class="border rounded-lg p-2 w-full" required>
                <option value="percent">Percentage (%)</option>
                <option value="fixed">Fixed Amount (₱)</option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-1">Discount Value *</label>
              <input type="number" name="discount_value" step="0.01" min="0" required class="border rounded-lg p-2 w-full" placeholder="e.g., 10 or 100">
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-1">Minimum Purchase (₱)</label>
              <input type="number" name="min_purchase" step="0.01" min="0" value="0" class="border rounded-lg p-2 w-full">
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-1">Max Discount (₱) - For % type</label>
              <input type="number" name="max_discount" step="0.01" min="0" class="border rounded-lg p-2 w-full" placeholder="Optional">
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-1">Usage Limit</label>
              <input type="number" name="usage_limit" min="0" class="border rounded-lg p-2 w-full" placeholder="Leave empty for unlimited">
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-1">Start Date</label>
              <input type="datetime-local" name="start_date" class="border rounded-lg p-2 w-full">
            </div>
            
            <div>
              <label class="block text-sm font-medium mb-1">End Date</label>
              <input type="datetime-local" name="end_date" class="border rounded-lg p-2 w-full">
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-sm font-medium mb-1">Description</label>
              <textarea name="description" rows="3" class="border rounded-lg p-2 w-full" placeholder="Describe this promotion..."></textarea>
            </div>
            
            <div class="md:col-span-2">
              <label class="block text-sm font-medium mb-1">Promo Image (optional)</label>
              <input type="file" name="promo_image" accept="image/*" class="border rounded-lg p-2 w-full">
            </div>
            
            <div class="md:col-span-2">
              <label class="flex items-center gap-2">
                <input type="checkbox" name="show_popup" class="w-4 h-4">
                <span class="text-sm font-medium">📢 Show as Popup (Latest promotion will show to new visitors)</span>
              </label>
            </div>
            
            <div class="md:col-span-2 text-right">
              <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg">
                Create Promotion
              </button>
            </div>
          </form>
        </div>
      </section>

      <section>
        <div class="bg-white rounded-xl shadow-lg border overflow-hidden">
          <div class="px-6 py-4 bg-gradient-to-r from-orange-100 to-orange-50 flex items-center justify-between">
            <h3 class="text-lg font-semibold">📋 All Promotions</h3>
            <div class="text-sm text-gray-600"><?php echo count($promotions); ?> promotion(s)</div>
          </div>
          
          <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
              <thead class="bg-gray-50 sticky top-0">
                <tr>
                  <th class="px-4 py-3 text-left">Code</th>
                  <th class="px-4 py-3 text-left">Title</th>
                  <th class="px-4 py-3 text-left">Discount</th>
                  <th class="px-4 py-3 text-left">Min Purchase</th>
                  <th class="px-4 py-3 text-left">Usage</th>
                  <th class="px-4 py-3 text-left">Status</th>
                  <th class="px-4 py-3 text-left">Popup</th>
                  <th class="px-4 py-3 text-left">Valid Until</th>
                  <th class="px-4 py-3 text-center">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-100">
                <?php if (!empty($promotions)): ?>
                  <?php foreach ($promotions as $promo): ?>
                    <tr class="hover:bg-orange-50">
                      <td class="px-4 py-3 font-mono font-semibold text-orange-600"><?php echo htmlspecialchars($promo['code']); ?></td>
                      <td class="px-4 py-3">
                        <div class="font-medium"><?php echo htmlspecialchars($promo['title']); ?></div>
                        <?php if ($promo['description']): ?>
                          <div class="text-xs text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars($promo['description']); ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="px-4 py-3">
                        <?php 
                          echo $promo['discount_type'] === 'percent' 
                            ? $promo['discount_value'] . '%' 
                            : '₱' . number_format($promo['discount_value']);
                        ?>
                      </td>
                      <td class="px-4 py-3">₱<?php echo number_format($promo['min_purchase']); ?></td>
                      <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                          <span>
                            <?php 
                              echo $promo['usage_count'];
                              if ($promo['usage_limit']) {
                                echo ' / ' . $promo['usage_limit'];
                              }
                            ?>
                          </span>
                          <?php if ($promo['usage_count'] > 0): ?>
                            <button onclick="viewPromoOrders('<?php echo htmlspecialchars($promo['code']); ?>')" 
                                    class="text-orange-600 hover:text-orange-700 text-xs underline"
                                    title="View orders that used this promo">
                              📊 View
                            </button>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded text-xs font-semibold <?php echo $promo['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                          <?php echo $promo['is_active'] ? '✅ Active' : '❌ Inactive'; ?>
                        </span>
                      </td>
                      <td class="px-4 py-3">
                        <?php if ($promo['show_popup']): ?>
                          <span class="text-blue-600">📢 Yes</span>
                        <?php else: ?>
                          <span class="text-gray-400">No</span>
                        <?php endif; ?>
                      </td>
                      <td class="px-4 py-3">
                        <?php 
                          if ($promo['end_date']) {
                            $endDate = new DateTime($promo['end_date']);
                            $now = new DateTime();
                            $expired = $endDate < $now;
                            echo '<span class="' . ($expired ? 'text-red-600' : 'text-gray-600') . '">';
                            echo $endDate->format('M d, Y');
                            echo '</span>';
                          } else {
                            echo '<span class="text-gray-500">No expiry</span>';
                          }
                        ?>
                      </td>
                      <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                          <button onclick="editPromotion(<?php echo htmlspecialchars(json_encode($promo)); ?>)" 
                                  class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold py-1 px-3 rounded">
                            Edit
                          </button>
                          <a href="?delete_promo=<?php echo $promo['id']; ?>&tab=promotions" 
                             onclick="return confirm('Delete this promotion?')"
                             class="bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-1 px-3 rounded">
                            Delete
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="9" class="px-6 py-8 text-center text-gray-500">No promotions created yet.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <!-- Pages Management (Contact & About) -->
    <?php if ($activeTab === 'pages'): ?>
      <section>
        <div class="mb-6">
          <div class="bg-gradient-to-r from-orange-100 to-orange-50 rounded-xl p-6 border border-orange-200">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">📄 Manage Pages Content</h2>
            <p class="text-gray-600">Edit the content of Contact Us and About Us pages. Changes will be reflected immediately on all pages (guest, user, and admin).</p>
          </div>
        </div>

        <!-- Contact Us Page Editor -->
        <div class="bg-white rounded-xl shadow-md border p-6 mb-8">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="text-xl font-semibold text-gray-800">📞 Contact Us Page</h3>
              <p class="text-sm text-gray-600 mt-1">Edit banner, form, and map settings</p>
            </div>
            <a href="contact.php" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
              👁️ View Live Page
            </a>
          </div>

          <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="update_page_content">
            <input type="hidden" name="page_name" value="contact">

            <!-- Banner Section -->
            <div class="border-b pb-6">
              <h4 class="font-semibold text-lg mb-4 text-orange-600">Banner Section</h4>
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Banner Title</label>
                  <input type="text" name="updates[banner_title]" value="<?php echo htmlspecialchars($contactContent['banner_title'] ?? 'Contact'); ?>" class="border rounded-lg p-2 w-full">
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Banner Subtitle</label>
                  <input type="text" name="updates[banner_subtitle]" value="<?php echo htmlspecialchars($contactContent['banner_subtitle'] ?? 'Get in touch with us'); ?>" class="border rounded-lg p-2 w-full">
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Banner Image Path</label>
                  <input type="text" name="updates[banner_image]" value="<?php echo htmlspecialchars($contactContent['banner_image'] ?? 'images/Dog3.png'); ?>" class="border rounded-lg p-2 w-full">
                  <p class="text-xs text-gray-500 mt-1">Path to banner background image (e.g., images/Dog3.png)</p>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Background Image Path</label>
                  <input type="text" name="updates[background_image]" value="<?php echo htmlspecialchars($contactContent['background_image'] ?? 'images/Cat.png'); ?>" class="border rounded-lg p-2 w-full">
                  <p class="text-xs text-gray-500 mt-1">Path to form section background</p>
                </div>
              </div>
            </div>

            <!-- Form Section -->
            <div class="border-b pb-6">
              <h4 class="font-semibold text-lg mb-4 text-orange-600">Contact Form Section</h4>
              <div>
                <label class="block text-sm font-medium mb-1">Form Title</label>
                <input type="text" name="updates[form_title]" value="<?php echo htmlspecialchars($contactContent['form_title'] ?? 'SEND US A MESSAGE'); ?>" class="border rounded-lg p-2 w-full">
              </div>
            </div>

            <!-- Map Section -->
            <div class="border-b pb-6">
              <h4 class="font-semibold text-lg mb-4 text-orange-600">Map Section</h4>
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Map Section Title</label>
                  <input type="text" name="updates[map_title]" value="<?php echo htmlspecialchars($contactContent['map_title'] ?? 'Find Us Here'); ?>" class="border rounded-lg p-2 w-full">
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Location Name</label>
                  <input type="text" name="updates[map_location_name]" value="<?php echo htmlspecialchars($contactContent['map_location_name'] ?? 'Pet Pantry+'); ?>" class="border rounded-lg p-2 w-full">
                </div>
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium mb-1">Location Address</label>
                  <input type="text" name="updates[map_location_address]" value="<?php echo htmlspecialchars($contactContent['map_location_address'] ?? 'Gateway Mall, Cubao, Quezon City'); ?>" class="border rounded-lg p-2 w-full">
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Map Latitude</label>
                  <input type="text" name="updates[map_lat]" value="<?php 
                    $coords = json_decode($contactContent['map_coordinates'] ?? '{}', true);
                    echo htmlspecialchars($coords['lat'] ?? '14.6208'); 
                  ?>" class="border rounded-lg p-2 w-full">
                  <p class="text-xs text-gray-500 mt-1">Decimal degrees (e.g., 14.6208)</p>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Map Longitude</label>
                  <input type="text" name="updates[map_lng]" value="<?php 
                    $coords = json_decode($contactContent['map_coordinates'] ?? '{}', true);
                    echo htmlspecialchars($coords['lng'] ?? '121.0527'); 
                  ?>" class="border rounded-lg p-2 w-full">
                  <p class="text-xs text-gray-500 mt-1">Decimal degrees (e.g., 121.0527)</p>
                </div>
              </div>
            </div>

            <div class="text-right">
              <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-lg">
                💾 Save Contact Page Changes
              </button>
            </div>
          </form>
        </div>

        <!-- About Us Page Editor -->
        <div class="bg-white rounded-xl shadow-md border p-6">
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="text-xl font-semibold text-gray-800">👥 About Us Page</h3>
              <p class="text-sm text-gray-600 mt-1">Edit hero section, team members, and project info</p>
            </div>
            <a href="about.php" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
              👁️ View Live Page
            </a>
          </div>

          <form method="POST" class="space-y-6">
            <input type="hidden" name="action" value="update_page_content">
            <input type="hidden" name="page_name" value="about">

            <!-- Hero Section -->
            <div class="border-b pb-6">
              <h4 class="font-semibold text-lg mb-4 text-orange-600">Hero Section</h4>
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Hero Title (HTML allowed for styling)</label>
                  <input type="text" name="updates[hero_title]" value="<?php echo htmlspecialchars($aboutContent['hero_title'] ?? 'Meet the Creators Behind PetPantry+'); ?>" class="border rounded-lg p-2 w-full">
                  <p class="text-xs text-gray-500 mt-1">You can use HTML like: Meet the &lt;span class="text-yellow-300"&gt;Creators&lt;/span&gt; Behind PetPantry+</p>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Hero Subtitle</label>
                  <textarea name="updates[hero_subtitle]" rows="3" class="border rounded-lg p-2 w-full"><?php echo htmlspecialchars($aboutContent['hero_subtitle'] ?? ''); ?></textarea>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Hero Badge Text</label>
                  <input type="text" name="updates[hero_badge_text]" value="<?php echo htmlspecialchars($aboutContent['hero_badge_text'] ?? 'TIP-QC Academic Project 2025'); ?>" class="border rounded-lg p-2 w-full">
                </div>
              </div>
            </div>

            <!-- Team Section Headers -->
            <div class="border-b pb-6">
              <h4 class="font-semibold text-lg mb-4 text-orange-600">Team Section</h4>
              <div class="grid md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium mb-1">Team Section Title</label>
                  <input type="text" name="updates[team_section_title]" value="<?php echo htmlspecialchars($aboutContent['team_section_title'] ?? 'Our Development Team'); ?>" class="border rounded-lg p-2 w-full">
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Team Section Subtitle</label>
                  <input type="text" name="updates[team_section_subtitle]" value="<?php echo htmlspecialchars($aboutContent['team_section_subtitle'] ?? ''); ?>" class="border rounded-lg p-2 w-full">
                </div>
              </div>
            </div>

            <div class="text-right">
              <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-lg">
                💾 Save About Page Changes
              </button>
            </div>
          </form>

          <!-- Team Members Editor -->
          <div class="mt-8 border-t pt-6">
            <h4 class="font-semibold text-lg mb-4 text-orange-600">Team Members</h4>
            <div id="teamMembersContainer" class="space-y-4 mb-4">
              <?php
                $teamMembers = json_decode($aboutContent['team_members'] ?? '[]', true);
                foreach ($teamMembers as $index => $member):
              ?>
              <div class="border rounded-lg p-4 bg-gray-50 team-member-card" data-index="<?php echo $index; ?>">
                <div class="grid md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium mb-1">Name</label>
                    <input type="text" class="border rounded-lg p-2 w-full team-name" value="<?php echo htmlspecialchars($member['name'] ?? ''); ?>">
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Role</label>
                    <input type="text" class="border rounded-lg p-2 w-full team-role" value="<?php echo htmlspecialchars($member['role'] ?? ''); ?>">
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Bio</label>
                    <textarea class="border rounded-lg p-2 w-full team-bio" rows="2"><?php echo htmlspecialchars($member['bio'] ?? ''); ?></textarea>
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Profile Image</label>
                    <?php if (!empty($member['image'])): ?>
                      <div class="mb-2 flex items-center gap-3">
                        <img src="<?php echo htmlspecialchars($member['image']); ?>" alt="Current" class="w-20 h-20 object-cover rounded border">
                        <div class="flex-1">
                          <p class="text-sm text-gray-600">Current image: <?php echo htmlspecialchars($member['image']); ?></p>
                          <p class="text-xs text-gray-500">Upload a new image below to replace it</p>
                        </div>
                      </div>
                    <?php endif; ?>
                    <input type="file" class="border rounded-lg p-2 w-full team-image-upload" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                    <input type="hidden" class="team-image" value="<?php echo htmlspecialchars($member['image'] ?? ''); ?>">
                    <p class="text-xs text-gray-500 mt-1">Accepts: JPG, PNG, GIF, WEBP (max 5MB recommended)</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium mb-1">Color Theme</label>
                    <select class="border rounded-lg p-2 w-full team-color">
                      <option value="orange" <?php echo ($member['color'] ?? '') === 'orange' ? 'selected' : ''; ?>>Orange</option>
                      <option value="blue" <?php echo ($member['color'] ?? '') === 'blue' ? 'selected' : ''; ?>>Blue</option>
                      <option value="purple" <?php echo ($member['color'] ?? '') === 'purple' ? 'selected' : ''; ?>>Purple</option>
                      <option value="green" <?php echo ($member['color'] ?? '') === 'green' ? 'selected' : ''; ?>>Green</option>
                      <option value="pink" <?php echo ($member['color'] ?? '') === 'pink' ? 'selected' : ''; ?>>Pink</option>
                      <option value="red" <?php echo ($member['color'] ?? '') === 'red' ? 'selected' : ''; ?>>Red</option>
                      <option value="yellow" <?php echo ($member['color'] ?? '') === 'yellow' ? 'selected' : ''; ?>>Yellow</option>
                      <option value="indigo" <?php echo ($member['color'] ?? '') === 'indigo' ? 'selected' : ''; ?>>Indigo</option>
                    </select>
                  </div>
                </div>
                <div class="mt-3 text-right">
                  <button type="button" onclick="removeTeamMember(<?php echo $index; ?>)" class="text-red-600 hover:text-red-800 text-sm">
                    🗑️ Remove Member
                  </button>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
            
            <div class="flex justify-between items-center">
              <button type="button" onclick="addTeamMember()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">
                ➕ Add Team Member
              </button>
              <button type="button" onclick="saveTeamMembers()" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-lg">
                💾 Save Team Members
              </button>
            </div>
          </div>
        </div>
      </section>
    <?php endif; ?>

  </div>
</div>

<!-- Edit Carousel Modal -->
<div id="editCarouselModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
    <h3 class="text-xl font-semibold mb-4">Edit Carousel Image</h3>
    <form method="POST" id="editCarouselForm">
      <input type="hidden" name="action" value="update_carousel">
      <input type="hidden" name="carousel_id" id="edit_carousel_id">
      
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Alt Text</label>
        <input type="text" name="alt_text" id="edit_alt_text" class="border rounded-lg p-2 w-full">
      </div>
      
      <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Display Order</label>
        <input type="number" name="display_order" id="edit_display_order" min="0" class="border rounded-lg p-2 w-full">
      </div>
      
      <div class="mb-4">
        <label class="flex items-center gap-2">
          <input type="checkbox" name="is_active" id="edit_is_active" class="w-4 h-4">
          <span class="text-sm font-medium">Active</span>
        </label>
      </div>
      
      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeEditModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-100">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Promo Orders Modal -->
<div id="promoOrdersModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full p-6 max-h-[90vh] overflow-y-auto">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold">📊 Orders Using: <span id="promoOrdersCode" class="text-orange-600"></span></h3>
      <button onclick="closePromoOrdersModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
    </div>
    
    <div id="promoOrdersList" class="space-y-3">
      <div class="text-center py-8 text-gray-500">Loading...</div>
    </div>
  </div>
</div>

<!-- Edit Promotion Modal -->
<div id="editPromoModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold">✏️ Edit Promotion</h3>
      <button onclick="closePromoModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
    </div>
    
    <form method="POST">
      <input type="hidden" name="action" value="update_promotion">
      <input type="hidden" name="promo_id" id="edit_promo_id">
      
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Title</label>
          <input type="text" name="title" id="edit_promo_title" required class="border rounded-lg p-2 w-full">
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Description</label>
          <textarea name="description" id="edit_promo_description" rows="3" class="border rounded-lg p-2 w-full"></textarea>
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Discount Value</label>
          <input type="number" name="discount_value" id="edit_promo_discount_value" step="0.01" required class="border rounded-lg p-2 w-full">
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Minimum Purchase (₱)</label>
          <input type="number" name="min_purchase" id="edit_promo_min_purchase" step="0.01" class="border rounded-lg p-2 w-full">
        </div>
        
        <div class="flex items-center gap-2">
          <input type="checkbox" name="is_active" id="edit_promo_is_active" class="w-4 h-4">
          <label class="text-sm font-medium">Active</label>
        </div>
        
        <div class="flex items-center gap-2">
          <input type="checkbox" name="show_popup" id="edit_promo_show_popup" class="w-4 h-4">
          <label class="text-sm font-medium">Show as Popup</label>
        </div>
      </div>
      
      <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closePromoModal()" class="px-4 py-2 border rounded-lg hover:bg-gray-100">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
// Sortable for bestseller and featured lists
if (document.getElementById('bestsellerList')) {
  new Sortable(document.getElementById('bestsellerList'), {
    animation: 150,
    handle: '.cursor-move'
  });
}

if (document.getElementById('featuredList')) {
  new Sortable(document.getElementById('featuredList'), {
    animation: 150,
    handle: '.cursor-move'
  });
}

// Add product to bestseller list
document.getElementById('addBestseller')?.addEventListener('change', function() {
  if (!this.value) return;
  
  const option = this.options[this.selectedIndex];
  const id = this.value;
  const name = option.dataset.name;
  const image = option.dataset.image || 'https://via.placeholder.com/50';
  
  // Check if already added
  if (document.querySelector(`#bestsellerList [data-id="${id}"]`)) {
    alert('Product already added');
    this.value = '';
    return;
  }
  
  // Check limit
  if (document.querySelectorAll('#bestsellerList > div').length >= 8) {
    alert('Maximum 8 products allowed');
    this.value = '';
    return;
  }
  
  const item = document.createElement('div');
  item.className = 'flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-move';
  item.dataset.id = id;
  item.innerHTML = `
    <span class="text-gray-400">☰</span>
    <img src="${image}" alt="" class="w-12 h-12 object-cover rounded">
    <span class="flex-1">${name}</span>
    <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700">✕</button>
  `;
  
  document.getElementById('bestsellerList').appendChild(item);
  this.value = '';
});

// Add product to featured list
document.getElementById('addFeatured')?.addEventListener('change', function() {
  if (!this.value) return;
  
  const option = this.options[this.selectedIndex];
  const id = this.value;
  const name = option.dataset.name;
  const image = option.dataset.image || 'https://via.placeholder.com/50';
  
  // Check if already added
  if (document.querySelector(`#featuredList [data-id="${id}"]`)) {
    alert('Product already added');
    this.value = '';
    return;
  }
  
  // Check limit
  if (document.querySelectorAll('#featuredList > div').length >= 8) {
    alert('Maximum 8 products allowed');
    this.value = '';
    return;
  }
  
  const item = document.createElement('div');
  item.className = 'flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-move';
  item.dataset.id = id;
  item.innerHTML = `
    <span class="text-gray-400">☰</span>
    <img src="${image}" alt="" class="w-12 h-12 object-cover rounded">
    <span class="flex-1">${name}</span>
    <button type="button" onclick="removeItem(this)" class="text-red-500 hover:text-red-700">✕</button>
  `;
  
  document.getElementById('featuredList').appendChild(item);
  this.value = '';
});

// Remove item from list
function removeItem(btn) {
  btn.closest('div[data-id]').remove();
}

// Submit forms with product IDs
document.getElementById('bestsellerForm')?.addEventListener('submit', function(e) {
  const items = document.querySelectorAll('#bestsellerList > div[data-id]');
  items.forEach((item, index) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = `product_ids[${index}]`;
    input.value = item.dataset.id;
    this.appendChild(input);
  });
});

document.getElementById('featuredForm')?.addEventListener('submit', function(e) {
  const items = document.querySelectorAll('#featuredList > div[data-id]');
  items.forEach((item, index) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = `product_ids[${index}]`;
    input.value = item.dataset.id;
    this.appendChild(input);
  });
});

// Edit carousel modal
function editCarousel(data) {
  document.getElementById('edit_carousel_id').value = data.id;
  document.getElementById('edit_alt_text').value = data.alt_text || '';
  document.getElementById('edit_display_order').value = data.display_order;
  document.getElementById('edit_is_active').checked = data.is_active == 1;
  document.getElementById('editCarouselModal').classList.remove('hidden');
  document.getElementById('editCarouselModal').classList.add('flex');
}

function closeEditModal() {
  document.getElementById('editCarouselModal').classList.add('hidden');
  document.getElementById('editCarouselModal').classList.remove('flex');
}

// ===========================
// PROMOTION EDIT
// ===========================

function editPromotion(promo) {
  // Populate modal fields
  document.getElementById('edit_promo_id').value = promo.id;
  document.getElementById('edit_promo_title').value = promo.title || '';
  document.getElementById('edit_promo_description').value = promo.description || '';
  document.getElementById('edit_promo_discount_value').value = promo.discount_value;
  document.getElementById('edit_promo_min_purchase').value = promo.min_purchase;
  document.getElementById('edit_promo_is_active').checked = promo.is_active == 1;
  document.getElementById('edit_promo_show_popup').checked = promo.show_popup == 1;
  
  // Show modal
  document.getElementById('editPromoModal').classList.remove('hidden');
  document.getElementById('editPromoModal').classList.add('flex');
}

function closePromoModal() {
  document.getElementById('editPromoModal').classList.add('hidden');
  document.getElementById('editPromoModal').classList.remove('flex');
}

// View orders that used a promo code
function viewPromoOrders(promoCode) {
  const modal = document.getElementById('promoOrdersModal');
  const codeSpan = document.getElementById('promoOrdersCode');
  const listDiv = document.getElementById('promoOrdersList');
  
  codeSpan.textContent = promoCode;
  listDiv.innerHTML = '<div class="text-center py-8 text-gray-500">Loading...</div>';
  
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  
  // Fetch orders using this promo
  fetch(`get_promo_orders.php?code=${encodeURIComponent(promoCode)}`)
    .then(r => r.json())
    .then(data => {
      if (data.success && data.orders.length > 0) {
        listDiv.innerHTML = data.orders.map(order => `
          <div class="border border-gray-200 rounded-lg p-4 hover:bg-orange-50 transition-colors">
            <div class="flex justify-between items-start">
              <div>
                <div class="font-semibold text-lg">Order #${order.id}</div>
                <div class="text-sm text-gray-600 mt-1">
                  <div>👤 ${escapeHtml(order.username)} (${escapeHtml(order.email)})</div>
                  <div>📅 ${new Date(order.created_at).toLocaleString()}</div>
                  <div>📍 ${escapeHtml(order.address)}</div>
                  <div>💳 ${escapeHtml(order.payment_method)}</div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-sm text-gray-600">Subtotal: ₱${parseFloat(order.subtotal).toFixed(2)}</div>
                <div class="text-sm text-green-600 font-semibold">Discount: -₱${parseFloat(order.discount_amount).toFixed(2)}</div>
                <div class="text-lg font-bold text-orange-600 mt-1">Total: ₱${parseFloat(order.total).toFixed(2)}</div>
                <div class="mt-2">
                  <span class="px-2 py-1 rounded text-xs font-semibold ${getStatusClass(order.status)}">
                    ${order.status.toUpperCase()}
                  </span>
                </div>
              </div>
            </div>
          </div>
        `).join('');
      } else {
        listDiv.innerHTML = '<div class="text-center py-8 text-gray-500">No orders found using this promo code.</div>';
      }
    })
    .catch(err => {
      console.error(err);
      listDiv.innerHTML = '<div class="text-center py-8 text-red-500">Error loading orders.</div>';
    });
}

function closePromoOrdersModal() {
  document.getElementById('promoOrdersModal').classList.add('hidden');
  document.getElementById('promoOrdersModal').classList.remove('flex');
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function getStatusClass(status) {
  switch(status.toLowerCase()) {
    case 'pending': return 'bg-orange-100 text-orange-800';
    case 'shipping': return 'bg-blue-100 text-blue-800';
    case 'completed': return 'bg-green-100 text-green-800';
    case 'cancelled': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
}

// ===========================
// LIVE PREVIEW FUNCTIONALITY
// ===========================

let previewData = {
  carousel: [],
  bestsellers: [],
  featured: []
};

// Load preview on page load
if (window.location.search.includes('tab=preview') || !window.location.search) {
  window.addEventListener('DOMContentLoaded', loadPreview);
}

function loadPreview() {
  showLoader();
  
  // Fetch all data needed for preview
  Promise.all([
    fetch('cms_preview_api.php?action=get_carousel').then(r => r.json()),
    fetch('cms_preview_api.php?action=get_bestsellers').then(r => r.json()),
    fetch('cms_preview_api.php?action=get_featured').then(r => r.json()),
    fetch('cms_preview_api.php?action=get_all_products').then(r => r.json())
  ]).then(([carousel, bestsellers, featured, allProducts]) => {
    previewData = { carousel, bestsellers, featured, allProducts };
    renderPreview();
    hideLoader();
  }).catch(err => {
    console.error('Preview load error:', err);
    hideLoader();
    document.getElementById('previewContent').innerHTML = '<p class="text-red-500 p-8 text-center">Error loading preview. Please refresh.</p>';
  });
}

function renderPreview() {
  const content = document.getElementById('previewContent');
  
  content.innerHTML = `
    <!-- Hero Carousel Preview -->
    <div class="relative bg-orange-100 p-8">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold">Hero Carousel</h3>
        <button onclick="openCarouselManager()" class="bg-orange-500 text-white px-3 py-1 rounded text-sm hover:bg-orange-600">
          ✏️ Manage Carousel
        </button>
      </div>
      <div class="relative h-[400px] overflow-hidden rounded-lg bg-white">
        <div id="previewCarouselInner" class="relative w-full h-full">
          ${renderCarouselSlides()}
        </div>
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-20">
          ${previewData.carousel.map((_, i) => `
            <div class="w-3 h-3 rounded-full ${i === 0 ? 'bg-orange-500' : 'bg-white/50'}"></div>
          `).join('')}
        </div>
      </div>
      <div id="carouselEditButtons" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-2">
        ${previewData.carousel.map((img, i) => `
          <div class="relative group">
            <img src="${img.image_path}" alt="" class="w-full h-24 object-cover rounded border-2 ${i === 0 ? 'border-orange-500' : 'border-gray-300'}">
            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 rounded">
              <button onclick="editCarouselImage(${img.id})" class="bg-blue-500 text-white px-2 py-1 rounded text-xs">Edit</button>
              <button onclick="deleteCarouselImage(${img.id})" class="bg-red-500 text-white px-2 py-1 rounded text-xs">Delete</button>
            </div>
            <div class="absolute top-1 left-1 bg-black/70 text-white px-2 py-1 rounded text-xs">Slide ${i + 1}</div>
          </div>
        `).join('')}
      </div>
    </div>

    <!-- Best Sellers Preview -->
    <div class="relative bg-gray-50 p-8">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold">Best Sellers Section</h3>
        <button onclick="openBestsellerManager()" class="bg-orange-500 text-white px-3 py-1 rounded text-sm hover:bg-orange-600">
          ✏️ Manage Best Sellers
        </button>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        ${renderProductGrid(previewData.bestsellers, 'bestseller')}
      </div>
    </div>

    <!-- Featured Items Preview -->
    <div class="relative bg-white p-8">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold">Featured Items Section</h3>
        <button onclick="openFeaturedManager()" class="bg-orange-500 text-white px-3 py-1 rounded text-sm hover:bg-orange-600">
          ✏️ Manage Featured Items
        </button>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        ${renderProductGrid(previewData.featured, 'featured')}
      </div>
    </div>
  `;
  
  content.classList.remove('hidden');
}

function renderCarouselSlides() {
  if (!previewData.carousel || previewData.carousel.length === 0) {
    return '<div class="flex items-center justify-center h-full"><p class="text-gray-500">No carousel images. Add some in the Carousel tab.</p></div>';
  }
  
  return previewData.carousel.map((img, i) => `
    <div class="absolute inset-0 transition-opacity duration-500 ${i === 0 ? 'opacity-100' : 'opacity-0'}" data-slide="${i}">
      <img src="${img.image_path}" alt="${img.alt_text || ''}" class="w-full h-full object-cover">
      <div class="absolute top-4 right-4">
        <button onclick="editCarouselImage(${img.id})" class="bg-blue-500/90 hover:bg-blue-600 text-white px-3 py-2 rounded shadow">
          ✏️ Edit Slide ${i + 1}
        </button>
      </div>
    </div>
  `).join('');
}

function renderProductGrid(products, section) {
  if (!products || products.length === 0) {
    return '<div class="col-span-4 text-center py-8 text-gray-500">No products selected. Click "Manage" to add products.</div>';
  }
  
  return products.map(p => `
    <div class="relative group border rounded-lg p-4 bg-white shadow-sm hover:shadow-md transition-shadow">
      <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity z-10">
        <button onclick="removeProductFromSection('${section}', ${p.id})" class="bg-red-500 text-white p-1 rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">
          ✕
        </button>
      </div>
      <div class="h-32 flex items-center justify-center mb-3 bg-gray-50 rounded">
        <img src="${p.image || 'https://via.placeholder.com/150'}" alt="${p.name}" class="max-h-full max-w-full object-contain">
      </div>
      <div class="text-center">
        <p class="text-sm font-semibold text-gray-800 truncate">${p.name}</p>
        <p class="text-orange-500 font-bold">₱${parseFloat(p.price).toLocaleString()}</p>
      </div>
      <div class="mt-2 opacity-0 group-hover:opacity-100 transition-opacity">
        <button onclick="viewProductDetails(${p.id})" class="w-full bg-orange-500 text-white py-1 rounded text-xs hover:bg-orange-600">
          View Details
        </button>
      </div>
    </div>
  `).join('');
}

function showLoader() {
  document.getElementById('previewLoader').classList.remove('hidden');
  document.getElementById('previewContent').classList.add('hidden');
}

function hideLoader() {
  document.getElementById('previewLoader').classList.add('hidden');
  document.getElementById('previewContent').classList.remove('hidden');
}

function refreshPreview() {
  loadPreview();
}

// Navigation functions
function openCarouselManager() {
  window.location.href = '?tab=carousel';
}

function openBestsellerManager() {
  window.location.href = '?tab=bestseller';
}

function openFeaturedManager() {
  window.location.href = '?tab=featured';
}

// Edit carousel image
function editCarouselImage(id) {
  const img = previewData.carousel.find(c => c.id == id);
  if (img) {
    editCarousel(img);
  }
}

// Delete carousel image
function deleteCarouselImage(id) {
  if (confirm('Delete this carousel image?')) {
    window.location.href = `?delete_carousel=${id}&tab=preview`;
  }
}

// Remove product from section
function removeProductFromSection(section, productId) {
  if (confirm('Remove this product from ' + section + '?')) {
    fetch('cms_preview_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'remove_product',
        section: section,
        product_id: productId
      })
    }).then(r => r.json())
      .then(data => {
        if (data.success) {
          loadPreview();
        } else {
          alert('Error removing product');
        }
      });
  }
}

// View product details
function viewProductDetails(productId) {
  const product = [...previewData.bestsellers, ...previewData.featured]
    .find(p => p.id == productId);
  
  if (product) {
    alert(`Product: ${product.name}\nPrice: ₱${product.price}\nStock: ${product.stock || 'N/A'}\n\nClick OK to edit in inventory.`);
    // Could open edit modal or redirect to inventory page
  }
}

// ===========================
// TEAM MEMBERS MANAGEMENT
// ===========================

// Add image preview functionality
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('teamMembersContainer')?.addEventListener('change', function(e) {
    if (e.target.classList.contains('team-image-upload')) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
          // Find or create preview container
          let previewContainer = e.target.parentElement.querySelector('.image-preview-container');
          if (!previewContainer) {
            previewContainer = document.createElement('div');
            previewContainer.className = 'image-preview-container mt-2 p-2 border rounded bg-white';
            e.target.parentElement.insertBefore(previewContainer, e.target.nextSibling.nextSibling);
          }
          
          previewContainer.innerHTML = `
            <div class="flex items-center gap-3">
              <img src="${event.target.result}" alt="Preview" class="w-20 h-20 object-cover rounded border">
              <div class="flex-1">
                <p class="text-sm font-medium text-green-600">✓ New image selected</p>
                <p class="text-xs text-gray-500">${file.name} (${(file.size / 1024).toFixed(1)} KB)</p>
              </div>
            </div>
          `;
        };
        reader.readAsDataURL(file);
      }
    }
  });
});

function addTeamMember() {
  const container = document.getElementById('teamMembersContainer');
  const index = container.querySelectorAll('.team-member-card').length;
  
  const newMember = document.createElement('div');
  newMember.className = 'border rounded-lg p-4 bg-gray-50 team-member-card';
  newMember.dataset.index = index;
  newMember.innerHTML = `
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium mb-1">Name</label>
        <input type="text" class="border rounded-lg p-2 w-full team-name" value="">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Role</label>
        <input type="text" class="border rounded-lg p-2 w-full team-role" value="">
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Bio</label>
        <textarea class="border rounded-lg p-2 w-full team-bio" rows="2"></textarea>
      </div>
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Profile Image</label>
        <input type="file" class="border rounded-lg p-2 w-full team-image-upload" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
        <input type="hidden" class="team-image" value="">
        <p class="text-xs text-gray-500 mt-1">Accepts: JPG, PNG, GIF, WEBP (max 5MB recommended)</p>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Color Theme</label>
        <select class="border rounded-lg p-2 w-full team-color">
          <option value="orange">Orange</option>
          <option value="blue">Blue</option>
          <option value="purple">Purple</option>
          <option value="green">Green</option>
          <option value="pink">Pink</option>
          <option value="red">Red</option>
          <option value="yellow">Yellow</option>
          <option value="indigo">Indigo</option>
        </select>
      </div>
    </div>
    <div class="mt-3 text-right">
      <button type="button" onclick="removeTeamMember(${index})" class="text-red-600 hover:text-red-800 text-sm">
        🗑️ Remove Member
      </button>
    </div>
  `;
  
  container.appendChild(newMember);
}

function removeTeamMember(index) {
  if (confirm('Remove this team member?')) {
    const cards = document.querySelectorAll('.team-member-card');
    cards.forEach(card => {
      if (parseInt(card.dataset.index) === index) {
        card.remove();
      }
    });
  }
}

function saveTeamMembers() {
  const cards = document.querySelectorAll('.team-member-card');
  const teamMembers = [];
  const formData = new FormData();
  
  formData.append('action', 'update_team_members');
  
  cards.forEach((card, index) => {
    const member = {
      name: card.querySelector('.team-name').value.trim(),
      role: card.querySelector('.team-role').value.trim(),
      bio: card.querySelector('.team-bio').value.trim(),
      image: card.querySelector('.team-image').value.trim(),
      color: card.querySelector('.team-color').value
    };
    
    // Handle file upload
    const fileInput = card.querySelector('.team-image-upload');
    if (fileInput && fileInput.files && fileInput.files[0]) {
      formData.append('team_images[' + index + ']', fileInput.files[0]);
    }
    
    if (member.name || member.role) {
      teamMembers.push(member);
    }
  });
  
  formData.append('team_members', JSON.stringify(teamMembers));
  
  // Show loading state
  const saveButton = event.target;
  const originalText = saveButton.innerHTML;
  saveButton.disabled = true;
  saveButton.innerHTML = '⏳ Uploading...';
  
  // Submit via fetch
  fetch('?tab=pages', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (response.ok) {
      window.location.href = '?tab=pages';
    } else {
      alert('Error saving team members. Please try again.');
      saveButton.disabled = false;
      saveButton.innerHTML = originalText;
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error saving team members. Please try again.');
    saveButton.disabled = false;
    saveButton.innerHTML = originalText;
  });
}
</script>

</body>
</html>

