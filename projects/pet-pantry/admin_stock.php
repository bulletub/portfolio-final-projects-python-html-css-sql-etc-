<?php
session_start();
require_once 'database.php';
require_once 'settings_helper.php';

// Get currency settings for price display
$currencySymbol = getCurrencySymbol();
$currencyCode = getDefaultCurrency();

// ----------------------------------------------------
// ✅ ROLE-BASED ACCESS VALIDATION
// ----------------------------------------------------
$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    header("Location: Login_and_creating_account_fixed.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id = ?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('super_admin', $roles) && !in_array('pricing_stock', $roles)) {
    header("Location: Login_and_creating_account_fixed.php");
    exit;
}

// ----------------------------------------------------
// ✅ HANDLE PRODUCT UPDATES
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_product':
            require_once 'settings_helper.php';
            $price = (float)$_POST['price'];
            
            // If price was entered in different currency, convert back to base currency
            if (isset($_POST['save_in_base_currency']) && $_POST['save_in_base_currency'] == '1') {
                $baseCurrency = getSetting('base_currency', 'PHP');
                $currentCurrency = getDefaultCurrency();
                
                // Convert back to base currency for storage
                if ($baseCurrency !== $currentCurrency && $price > 0) {
                    // Reverse conversion: current -> base
                    $rate = getExchangeRate($baseCurrency, $currentCurrency);
                    if ($rate > 0) {
                        $price = $price / $rate; // Convert back to base currency
                    }
                }
            }
            
            $stmt = $pdo->prepare("UPDATE products SET price = ?, stock = ? WHERE id = ?");
            $stmt->execute([$price, (int)$_POST['stock'], (int)$_POST['product_id']]);
            header("Location: admin_stock.php");
            exit;

        case 'add_promo':
            $stmt = $pdo->prepare("
                INSERT INTO promos (code, discount_type, discount_value, applies_to, product_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($_POST['code']),
                $_POST['discount_type'],
                $_POST['discount_value'],
                $_POST['applies_to'],
                !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null
            ]);
            header("Location: admin_stock.php");
            exit;
    }
}

// ----------------------------------------------------
// ✅ HANDLE PROMO DELETE
// ----------------------------------------------------
if (isset($_GET['delete_promo'])) {
    $stmt = $pdo->prepare("DELETE FROM promos WHERE id = ?");
    $stmt->execute([(int)$_GET['delete_promo']]);
    header("Location: admin_stock.php");
    exit;
}

// ----------------------------------------------------
// ✅ FETCH DATA WITH FILTERS
// ----------------------------------------------------
$searchQuery = $_GET['search'] ?? '';
$stockFilter = $_GET['stock_filter'] ?? 'all';
$priceFilter = $_GET['price_filter'] ?? 'all';
$sortBy = $_GET['sort_by'] ?? 'id_desc';

// Build WHERE clause
$whereConditions = [];
$params = [];

if (!empty($searchQuery)) {
    $whereConditions[] = "name LIKE ?";
    $params[] = "%$searchQuery%";
}

if ($stockFilter === 'low') {
    $whereConditions[] = "stock <= 5";
} elseif ($stockFilter === 'in_stock') {
    $whereConditions[] = "stock > 5";
} elseif ($stockFilter === 'out_of_stock') {
    $whereConditions[] = "stock = 0";
}

if ($priceFilter === 'low_price') {
    $whereConditions[] = "price <= 500";
} elseif ($priceFilter === 'medium_price') {
    $whereConditions[] = "price > 500 AND price <= 1500";
} elseif ($priceFilter === 'high_price') {
    $whereConditions[] = "price > 1500";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Build ORDER BY clause
switch($sortBy) {
    case 'id_asc':
        $orderBy = "ORDER BY id ASC";
        break;
    case 'id_desc':
        $orderBy = "ORDER BY id DESC";
        break;
    case 'name_asc':
        $orderBy = "ORDER BY name ASC";
        break;
    case 'name_desc':
        $orderBy = "ORDER BY name DESC";
        break;
    case 'price_asc':
        $orderBy = "ORDER BY price ASC";
        break;
    case 'price_desc':
        $orderBy = "ORDER BY price DESC";
        break;
    case 'stock_asc':
        $orderBy = "ORDER BY stock ASC";
        break;
    case 'stock_desc':
        $orderBy = "ORDER BY stock DESC";
        break;
    default:
        $orderBy = "ORDER BY id DESC";
        break;
}

$sql = "SELECT id, name, price, stock FROM products $whereClause $orderBy";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for stats
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5")->fetchColumn();
$outOfStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();

$promos = $pdo->query("SELECT * FROM promos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stock Management | PetPantry+</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    /* Smooth transitions for filters */
    .filter-section {
        transition: all 0.3s ease-in-out;
    }
    
    .filter-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .filter-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    input, select, button {
        transition: all 0.2s ease-in-out;
    }
    
    input:focus, select:focus {
        transform: scale(1.02);
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
    }
    
    button:hover {
        transform: translateY(-1px);
    }
    
    /* Table row animations */
    tbody tr {
        transition: background-color 0.2s ease-in-out, transform 0.1s ease-in-out;
    }
    
    tbody tr:hover {
        transform: scale(1.01);
    }
    
    /* Smooth filter application */
    .product-row {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen font-sans text-gray-800">

<div class="flex min-h-screen">
    <?php $section = "stock"; include 'admin_navbar.php'; ?>

    <main class="flex-1 p-8">
        <!-- HEADER -->
        <header class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-extrabold">Stock & Pricing Dashboard</h1>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-gray-600">
                    Welcome, <strong class="text-orange-600"><?= htmlspecialchars($_SESSION['name']) ?></strong>
                </span>

                <!-- 🔔 Notification Dropdown -->
                <div class="relative">
                    <button id="notifBtn" class="relative bg-orange-100 p-2 rounded-full hover:bg-orange-200">
                        🔔
                        <span id="notifCount"
                              class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold px-1 rounded-full hidden">
                              0
                        </span>
                    </button>
                    <div id="notifDropdown"
                         class="hidden absolute right-0 mt-2 w-72 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        <div class="px-4 py-2 font-semibold bg-orange-50 border-b">Low Stock Alerts</div>
                        <div id="notifList" class="max-h-60 overflow-y-auto"></div>
                    </div>
                </div>
            </div>
        </header>

        <!-- FILTER SECTION -->
        <section class="mb-6 filter-section">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 filter-card">
                <h2 class="text-lg font-semibold mb-4 text-gray-800">Filter & Search Products</h2>
                <form method="GET" action="admin_stock.php" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Product</label>
                        <input type="text" 
                               name="search" 
                               value="<?= htmlspecialchars($searchQuery) ?>" 
                               placeholder="Product name..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    
                    <!-- Stock Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Stock Status</label>
                        <select name="stock_filter" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="all" <?= $stockFilter === 'all' ? 'selected' : '' ?>>All Products</option>
                            <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low Stock (≤5)</option>
                            <option value="in_stock" <?= $stockFilter === 'in_stock' ? 'selected' : '' ?>>In Stock (>5)</option>
                            <option value="out_of_stock" <?= $stockFilter === 'out_of_stock' ? 'selected' : '' ?>>Out of Stock</option>
                        </select>
                    </div>
                    
                    <!-- Price Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                        <select name="price_filter" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="all" <?= $priceFilter === 'all' ? 'selected' : '' ?>>All Prices</option>
                            <option value="low_price" <?= $priceFilter === 'low_price' ? 'selected' : '' ?>>Low (≤ <?= $currencySymbol ?>500)</option>
                            <option value="medium_price" <?= $priceFilter === 'medium_price' ? 'selected' : '' ?>>Medium (<?= $currencySymbol ?>500 - <?= $currencySymbol ?>1,500)</option>
                            <option value="high_price" <?= $priceFilter === 'high_price' ? 'selected' : '' ?>>High (> <?= $currencySymbol ?>1,500)</option>
                        </select>
                    </div>
                    
                    <!-- Sort By -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select name="sort_by" 
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="id_desc" <?= $sortBy === 'id_desc' ? 'selected' : '' ?>>ID (Newest First)</option>
                            <option value="id_asc" <?= $sortBy === 'id_asc' ? 'selected' : '' ?>>ID (Oldest First)</option>
                            <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                            <option value="name_desc" <?= $sortBy === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                            <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
                            <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
                            <option value="stock_asc" <?= $sortBy === 'stock_asc' ? 'selected' : '' ?>>Stock (Low to High)</option>
                            <option value="stock_desc" <?= $sortBy === 'stock_desc' ? 'selected' : '' ?>>Stock (High to Low)</option>
                        </select>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="md:col-span-2 lg:col-span-4 flex gap-2 justify-end items-end">
                        <button type="submit" 
                                class="px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-semibold shadow-md">
                            🔍 Apply Filters
                        </button>
                        <a href="admin_stock.php" 
                           class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold shadow-md">
                            🔄 Reset
                        </a>
                    </div>
                </form>
                
                <!-- Stats Summary -->
                <div class="mt-4 pt-4 border-t border-gray-200 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600"><?= $totalProducts ?></div>
                        <div class="text-xs text-gray-600">Total Products</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600"><?= count($products) ?></div>
                        <div class="text-xs text-gray-600">Showing</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600"><?= $lowStockCount ?></div>
                        <div class="text-xs text-gray-600">Low Stock</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600"><?= $outOfStockCount ?></div>
                        <div class="text-xs text-gray-600">Out of Stock</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- PRODUCTS TABLE -->
        <section class="mb-10">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-orange-100 to-orange-50 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Products</h2>
                    <span class="text-sm text-gray-600">
                        Showing <strong><?= count($products) ?></strong> of <strong><?= $totalProducts ?></strong> products
                        <?php if (!empty($searchQuery) || $stockFilter !== 'all' || $priceFilter !== 'all'): ?>
                            <span class="text-orange-600">(Filtered)</span>
                        <?php endif; ?>
                    </span>
                </div>

                <div class="relative overflow-y-auto max-h-[700px] overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">ID</th>
                                <th class="px-4 py-2 text-left">Product Name</th>
                                <th class="px-4 py-2 text-left">Price</th>
                                <th class="px-4 py-2 text-left">Stock</th>
                                <th class="px-4 py-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        <div class="text-lg mb-2">🔍</div>
                                        <div>No products found matching your filters.</div>
                                        <a href="admin_stock.php" class="text-orange-600 hover:underline mt-2 inline-block">Clear filters</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                <tr id="product-<?= $p['id'] ?>" class="hover:bg-orange-50 product-row">
                                <td class="px-4 py-2"><?= $p['id'] ?></td>
                                <td class="px-4 py-2 font-medium"><?= htmlspecialchars($p['name']) ?></td>
                                <td class="px-4 py-2"><?= formatCurrency($p['price']) ?></td>
                                <td class="px-4 py-2"><?= $p['stock'] ?></td>
                                <td class="px-4 py-2 text-center">
                                    <form method="POST" class="product-update flex flex-col md:flex-row gap-2 items-center justify-center">
                                        <input type="hidden" name="action" value="update_product">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <?php 
                                        // Show converted price but save in base currency
                                        $baseCurrency = getSetting('base_currency', 'PHP');
                                        $currentCurrency = getDefaultCurrency();
                                        $displayPrice = ($baseCurrency !== $currentCurrency) ? convertCurrency($p['price'], $baseCurrency, $currentCurrency) : $p['price'];
                                        ?>
                                        <input type="number" step="0.01" name="price" value="<?= number_format($displayPrice, 2, '.', '') ?>"
                                               class="border border-gray-300 rounded-lg p-1 text-xs w-24" required
                                               data-base-price="<?= $p['price'] ?>"
                                               data-base-currency="<?= $baseCurrency ?>"
                                               data-current-currency="<?= $currentCurrency ?>">
                                        <input type="hidden" name="save_in_base_currency" value="1">
                                        <input type="number" name="stock" value="<?= $p['stock'] ?>"
                                               class="border border-gray-300 rounded-lg p-1 text-xs w-20" required>
                                        <button type="submit"
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold shadow">
                                            Save
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- ✅ Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-black opacity-40"></div>
    <div class="bg-white rounded-lg shadow-xl z-10 w-full max-w-sm mx-4">
        <div class="px-6 py-4">
            <h4 id="confirmMessage" class="text-lg font-semibold text-gray-800">Are you sure?</h4>
            <div class="flex justify-end gap-2 mt-4">
                <button id="confirmCancel" class="px-4 py-2 rounded border">Cancel</button>
                <button id="confirmOk" class="px-4 py-2 rounded bg-red-600 text-white">Yes</button>
            </div>
        </div>
    </div>
</div>

<script>
/* ---------------- Confirmation Modal Logic ---------------- */
const confirmModal = document.getElementById('confirmModal');
const confirmMessage = document.getElementById('confirmMessage');
const confirmCancel = document.getElementById('confirmCancel');
const confirmOk = document.getElementById('confirmOk');
let confirmCallback = null;

function showConfirm(msg, callback) {
    confirmMessage.textContent = msg;
    confirmModal.classList.remove('hidden');
    confirmModal.classList.add('flex');
    confirmCallback = callback;
}

confirmCancel.onclick = () => confirmModal.classList.add('hidden');
confirmOk.onclick = () => {
    confirmModal.classList.add('hidden');
    if (confirmCallback) confirmCallback();
};

/* ---------------- Promo & Product Form Confirmation ---------------- */
document.querySelectorAll('form.product-update').forEach(f => {
    f.addEventListener('submit', e => {
        e.preventDefault();
        showConfirm("Update this product's price/stock?", () => f.submit());
    });
});

/* ---------------- Low Stock Notification ---------------- */
const notifBtn = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');
notifBtn.addEventListener('click', () => notifDropdown.classList.toggle('hidden'));

function loadLowStockNotifs() {
    fetch('fetch_lowstock_notifs.php?_=' + Date.now())
        .then(res => res.json())
        .then(res => {
            const notifList = document.getElementById('notifList');
            const notifCount = document.getElementById('notifCount');

            notifList.innerHTML = '';
            notifCount.classList.add('hidden');

            if (res.status === 'success' && res.count > 0) {
                notifCount.textContent = res.count;
                notifCount.classList.remove('hidden');

                res.data.forEach(item => {
                    const div = document.createElement('div');
                    div.className = "px-4 py-2 hover:bg-orange-50 cursor-pointer text-sm border-b";
                    div.innerHTML = `
                        ⚠️ <strong>${item.product_name}</strong> is low in stock 
                        (<span class='text-red-600 font-bold'>${item.stock}</span> left)
                    `;
                    div.onclick = () => {
                        location.hash = 'product-' + item.product_id;
                        notifDropdown.classList.add('hidden');
                    };
                    notifList.appendChild(div);
                });
            } else {
                notifList.innerHTML = `
                    <div class='px-4 py-2 text-gray-500 text-sm'>No low stock alerts.</div>
                `;
            }
        })
        .catch(err => {
            console.error('Error loading low stock alerts:', err);
        });
}

// Highlight low stock items
document.querySelectorAll('tbody tr').forEach(row => {
    const stockCell = row.querySelector('td:nth-child(4)');
    if (stockCell) {
        const stockValue = parseInt(stockCell.textContent);
        if (stockValue <= 5 && stockValue > 0) {
            stockCell.classList.add('text-red-600', 'font-semibold');
        } else if (stockValue === 0) {
            stockCell.classList.add('text-red-800', 'font-bold');
        }
    }
});

// Auto-submit form on filter change (optional - for instant filtering)
document.querySelectorAll('select[name="stock_filter"], select[name="price_filter"], select[name="sort_by"]').forEach(select => {
    select.addEventListener('change', function() {
        // Optional: Auto-submit when dropdown changes (commented out - uncomment if desired)
        // this.form.submit();
    });
});

// Smooth scroll to product when clicked from notification
if (location.hash) {
    const target = document.querySelector(location.hash);
    if (target) {
        setTimeout(() => {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.add('bg-yellow-100');
            setTimeout(() => target.classList.remove('bg-yellow-100'), 2000);
        }, 100);
    }
}

loadLowStockNotifs();
setInterval(loadLowStockNotifs, 10000);
</script>
</body>
</html>
