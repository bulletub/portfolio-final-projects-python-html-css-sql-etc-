<?php
// admin_inventory.php
session_start();
include('database.php');

// initialize message
$message = "";

// helper: check if a table has a column (for optional description support)
function hasColumn($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }
}
$hasDescription = hasColumn($pdo, 'products', 'description');

// --- Role-based Access Control ---
$userId = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$userId || (!in_array('super_admin', $roles) && !in_array('inventory', $roles))) {
    header("Location: Login_and_creating_account_fixed.php");
    exit;
}

// --- Helper: Handle Image Upload ---
function handleImageUpload($file) {
    if (!isset($file) || !isset($file['error'])) return null;
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . "/uploads/products/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        $fileName = time() . '_' . preg_replace("/[^A-Za-z0-9._-]/", "_", basename($file["name"]));
        $targetFile = $targetDir . $fileName;
        $publicPath = "uploads/products/" . $fileName;

        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            return $publicPath;
        }
    }
    return null;
}

// --- Categories & Subcategories ---
$categories = [
    "Natural Pet Foods" => [
        "Dry Kibble & Freeze-Dried Meals",
        "Wet & Canned Foods",
        "Dietary Supplements & Mix-ins"
    ],
    "Eco-Friendly Toys & Accessories" => [
        "Chew & Tug Toys",
        "Pet Wear & Apparel",
        "Eco Beds & Carriers"
    ],
    "Grooming & Wellness" => [
        "Shampoos & Conditioners",
        "Dental & Ear Care",
        "First Aid & Calming Aids"
    ]
];

// --- Add Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? '';
    $subcategory = $_POST['subcategory'] ?? '';
    $imagePath = handleImageUpload($_FILES['image'] ?? null);
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $message = "<p class='text-red-600 text-sm'>❌ Product name is required.</p>";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name=?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $message = "<p class='text-red-600 text-sm'>❌ Product with this name already exists!</p>";
        } else {
            if ($hasDescription) {
                $stmt = $pdo->prepare("INSERT INTO products (name, category, subcategory, image, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $category, $subcategory, $imagePath, $description]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO products (name, category, subcategory, image) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $category, $subcategory, $imagePath]);
            }
            header("Location: admin_inventory.php");
            exit;
        }
    }
}

// --- Edit Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id = (int)($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? '';
    $subcategory = $_POST['subcategory'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $currentImage = $_POST['current_image'] ?? null;
    $uploaded = handleImageUpload($_FILES['image'] ?? null);
    $imagePath = $uploaded ?? $currentImage;

    if ($id > 0 && $name !== '') {
        if ($hasDescription) {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, subcategory=?, image=?, description=? WHERE id=?");
            $stmt->execute([$name, $category, $subcategory, $imagePath, $description, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name=?, category=?, subcategory=?, image=? WHERE id=?");
            $stmt->execute([$name, $category, $subcategory, $imagePath, $id]);
        }
        $message = "<p class='text-green-600 text-sm'>✅ Product updated successfully.</p>";
    } else {
        $message = "<p class='text-red-600 text-sm'>❌ Invalid product data.</p>";
    }
}

// --- Delete Product ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
        $stmt->execute([$id]);
    }
    header("Location: admin_inventory.php");
    exit;
}

// --- Filters ---
$q = trim($_GET['q'] ?? '');
$fcat = $_GET['fcat'] ?? '';
$fsub = $_GET['fsub'] ?? '';
$fstock = $_GET['fstock'] ?? '';

// --- Fetch Products with filters ---
$where = [];
$params = [];
if ($q !== '') {
    if (ctype_digit($q)) { $where[] = "id = ?"; $params[] = (int)$q; }
    $where[] = "name LIKE ?"; $params[] = "%$q%";
}
if ($fcat !== '') { $where[] = "category = ?"; $params[] = $fcat; }
if ($fsub !== '') { $where[] = "subcategory = ?"; $params[] = $fsub; }
if ($fstock === 'in') { $where[] = "(stock IS NULL OR stock > 0)"; }
if ($fstock === 'out') { $where[] = "(stock IS NOT NULL AND stock <= 0)"; }

$sql = "SELECT * FROM products";
if (!empty($where)) { $sql .= " WHERE " . implode(" AND ", $where); }
$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Inventory Management | PetPantry+</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen font-sans text-gray-800">

<div class="flex min-h-screen">
    <?php $section="inventory"; include('admin_navbar.php'); ?>

    <main class="flex-1 p-8">
        <header class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-extrabold"> Inventory Dashboard</h1>
               
            </div>
            <div class="text-right">
                <span class="text-gray-600">Welcome, <strong class="text-orange-600"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?></strong></span>
            </div>
        </header>

        <?php if (!empty($message)) echo "<div class='mb-4'>{$message}</div>"; ?>

        <!-- Add Product Card -->
        <section class="mb-8">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">➕ Add New Product</h2>
                </div>

                <form id="addForm" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="add">

                    <input type="text" name="name" placeholder="Product Name" required
                           class="col-span-3 md:col-span-2 border border-gray-300 rounded-lg p-3" />

                    <div class="flex gap-4 col-span-3 md:col-span-2">
                        <select name="category" id="add-category" required
                                class="flex-1 border border-gray-300 rounded-lg p-3">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat => $subs): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select name="subcategory" id="add-subcategory" required
                                class="flex-1 border border-gray-300 rounded-lg p-3">
                            <option value="">Select Subcategory</option>
                        </select>
                    </div>

                    <input type="file" name="image" accept="image/*" class="col-span-3 border border-gray-300 rounded-lg p-3" />

                    <?php if ($hasDescription): ?>
                    <textarea name="description" rows="3" placeholder="Product description" class="col-span-3 border border-gray-300 rounded-lg p-3"></textarea>
                    <?php endif; ?>

                    <div class="col-span-3 text-right">
                        <button type="submit"
                                class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg shadow">
                            Add Product
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Filters -->
        <section class="mb-4">
            <form method="GET" class="bg-white rounded-xl shadow-md border border-gray-200 p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
                <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search by name or ID" class="border border-gray-300 rounded-lg p-3" />
                <select name="fcat" id="filter-category" class="border border-gray-300 rounded-lg p-3">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat => $subs): ?>
                      <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $fcat===$cat?'selected':''; ?>><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="fsub" id="filter-subcategory" class="border border-gray-300 rounded-lg p-3">
                    <option value="">All Subcategories</option>
                </select>
                <select name="fstock" class="border border-gray-300 rounded-lg p-3">
                    <option value="">Any Stock</option>
                    <option value="in" <?php echo $fstock==='in'?'selected':''; ?>>In Stock</option>
                    <option value="out" <?php echo $fstock==='out'?'selected':''; ?>>Out of Stock</option>
                </select>
                <div class="md:col-span-4 flex gap-2 justify-end">
                    <a href="admin_inventory.php" class="px-4 py-2 rounded border">Reset</a>
                    <button class="px-4 py-2 rounded bg-orange-500 text-white">Filter</button>
                </div>
            </form>
        </section>

        <!-- Products Table -->
        <section>
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-orange-100 to-orange-50 flex items-center justify-between">
                    <h3 class="text-lg font-semibold"> Product Inventory</h3>
                    <div class="text-sm text-gray-600"><?php echo count($products); ?> item(s)</div>
                </div>

                <!-- Scrollable Inventory -->
                         <div class="overflow-x-auto max-h-[700px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">ID</th>
                                <th class="px-4 py-3 text-left">Image</th>
                                <th class="px-4 py-3 text-left">Name</th>
                                <th class="px-4 py-3 text-left">Category</th>
                                <th class="px-4 py-3 text-left">Subcategory</th>
                                <?php if ($hasDescription): ?>
                                <th class="px-4 py-3 text-left">Description</th>
                                <?php endif; ?>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $product): 
                                    $img = $product['image'] ? htmlspecialchars($product['image']) : 'https://via.placeholder.com/120?text=No+Image';
                                ?>
                                <tr class="hover:bg-orange-50">
                                    <td class="px-4 py-3"><?php echo (int)$product['id']; ?></td>
                                    <td class="px-4 py-3">
                                        <img src="<?php echo $img; ?>" alt="" class="w-16 h-16 object-cover rounded-md shadow-sm">
                                    </td>
                                    <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td class="px-4 py-3"><?php echo htmlspecialchars($product['subcategory']); ?></td>
                                    <?php if ($hasDescription): ?>
                                    <td class="px-4 py-3 text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($product['description'] ?? ''); ?>"><?php echo htmlspecialchars($product['description'] ?? ''); ?></td>
                                    <?php endif; ?>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                class="edit-btn bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold py-1 px-3 rounded"
                                                data-id="<?php echo (int)$product['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>"
                                                data-category="<?php echo htmlspecialchars($product['category'], ENT_QUOTES); ?>"
                                                data-subcategory="<?php echo htmlspecialchars($product['subcategory'], ENT_QUOTES); ?>"
                                                data-image="<?php echo htmlspecialchars($product['image'], ENT_QUOTES); ?>"
                                                data-description="<?php echo htmlspecialchars($product['description'] ?? '', ENT_QUOTES); ?>"
                                            >
                                                Edit
                                            </button>

                                            <a href="admin_inventory.php?delete=<?php echo (int)$product['id']; ?>"
                                               class="delete-btn bg-red-500 hover:bg-red-600 text-white text-xs font-bold py-1 px-3 rounded">
                                                Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">No products available.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center px-4 py-6">
  <!-- Overlay -->
  <div class="absolute inset-0 bg-black opacity-50"></div>

  <!-- Modal Container -->
  <div class="relative w-full max-w-lg bg-white rounded-xl shadow-xl overflow-y-auto max-h-[90vh] z-10">
    <div class="px-6 pt-6 pb-4">
      <!-- Header -->
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold text-gray-800">✏️ Edit Product</h3>
        <button id="editCloseX" class="text-gray-400 hover:text-gray-600 text-2xl font-bold">&times;</button>
      </div>

      <!-- Form -->
      <form id="editForm" method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="product_id" id="edit-id">
        <input type="hidden" name="current_image" id="edit-current-image">

        <!-- Product Name -->
        <div>
          <label for="edit-name" class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
          <input type="text" name="name" id="edit-name" required
                 class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-orange-400 focus:outline-none" 
                 placeholder="Enter product name" />
        </div>

        <!-- Category & Subcategory -->
        <div class="flex flex-col md:flex-row gap-4">
          <div class="flex-1">
            <label for="edit-category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select name="category" id="edit-category" required
                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-orange-400 focus:outline-none"></select>
          </div>
          <div class="flex-1">
            <label for="edit-subcategory" class="block text-sm font-medium text-gray-700 mb-1">Subcategory</label>
            <select name="subcategory" id="edit-subcategory" required
                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-orange-400 focus:outline-none"></select>
          </div>
        </div>

        <!-- Image Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Image (optional)</label>
          <input type="file" name="image" accept="image/*"
                 class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-orange-400 focus:outline-none">
          <div class="mt-2 flex items-center gap-4">
            <img id="edit-preview" src="https://via.placeholder.com/120?text=No+Image" 
                 alt="Preview" class="w-24 h-24 object-cover rounded-md shadow-sm border">
            <span class="text-gray-500 text-sm">Current image preview</span>
          </div>
        </div>

        <?php if ($hasDescription): ?>
        <div>
          <label for="edit-description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea name="description" id="edit-description" rows="4" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-orange-400 focus:outline-none" placeholder="Enter a detailed, product-related description"></textarea>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="flex justify-end gap-2 mt-4">
          <button type="button" id="editCancelBtn" 
                  class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-100 transition">Cancel</button>
          <button type="submit" 
                  class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Confirmation Modal -->
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
const categories = <?php echo json_encode($categories); ?>;

// --- Utility Functions ---
const populateOptions = (selectEl, optionsArr = [], selected = '') => {
  selectEl.innerHTML = '<option value="">Select Subcategory</option>';
  optionsArr.forEach(opt => {
    const option = document.createElement('option');
    option.value = opt;
    option.textContent = opt;
    if(opt === selected) option.selected = true;
    selectEl.appendChild(option);
  });
};

const showConfirm = (message, callback) => {
  confirmMessage.textContent = message;
  confirmModal.classList.remove('hidden');
  confirmModal.classList.add('flex');
  confirmCallback = callback;
};

// --- Add Product Form ---
const addCategory = document.getElementById('add-category');
const addSubcategory = document.getElementById('add-subcategory');

addCategory?.addEventListener('change', () => {
  populateOptions(addSubcategory, categories[addCategory.value] || []);
});

// --- Confirmation Modal ---
const confirmModal = document.getElementById('confirmModal');
const confirmMessage = document.getElementById('confirmMessage');
const confirmCancel = document.getElementById('confirmCancel');
const confirmOk = document.getElementById('confirmOk');
let confirmCallback = null;

confirmCancel.addEventListener('click', () => {
  confirmModal.classList.add('hidden');
  confirmModal.classList.remove('flex');
});
confirmOk.addEventListener('click', () => {
  confirmModal.classList.add('hidden');
  confirmModal.classList.remove('flex');
  if(confirmCallback) confirmCallback();
});

// Delete buttons
document.querySelectorAll('.delete-btn').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();
    showConfirm("Delete this product permanently?", () => {
      window.location.href = btn.getAttribute('href');
    });
  });
});

// --- Edit Modal ---
const editModal = document.getElementById('editModal');
const editForm = document.getElementById('editForm');
const editCancelBtn = document.getElementById('editCancelBtn');
const editCloseX = document.getElementById('editCloseX');
const editName = document.getElementById('edit-name');
const editId = document.getElementById('edit-id');
const editCategory = document.getElementById('edit-category');
const editSubcategory = document.getElementById('edit-subcategory');
const editPreview = document.getElementById('edit-preview');
const editCurrentImage = document.getElementById('edit-current-image');
const editImageInput = editForm.querySelector('input[name="image"]');
const editDescription = document.getElementById('edit-description');

// Populate categories
editCategory.innerHTML = '<option value="">Select Category</option>';
Object.keys(categories).forEach(cat => {
  const o = document.createElement('option');
  o.value = cat;
  o.textContent = cat;
  editCategory.appendChild(o);
});

// Handlers
editCategory.addEventListener('change', () => {
  populateOptions(editSubcategory, categories[editCategory.value] || []);
});

editImageInput.addEventListener('change', e => {
  const file = e.target.files[0];
  editPreview.src = file ? URL.createObjectURL(file) : (editCurrentImage.value || 'https://via.placeholder.com/120?text=No+Image');
});

// Open modal
document.querySelectorAll('.edit-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    editId.value = btn.dataset.id || '';
    editName.value = btn.dataset.name || '';
    editCurrentImage.value = btn.dataset.image || '';
    editPreview.src = btn.dataset.image || 'https://via.placeholder.com/120?text=No+Image';
    editCategory.value = btn.dataset.category || '';
    populateOptions(editSubcategory, categories[btn.dataset.category] || [], btn.dataset.subcategory || '');
    if (editDescription) editDescription.value = btn.dataset.description || '';
    editModal.classList.remove('hidden');
    editModal.classList.add('flex');
  });
});

// Cancel modal (X and Cancel button)
[editCancelBtn, editCloseX].forEach(btn => {
  btn.addEventListener('click', () => {
    editModal.classList.add('hidden');
    editModal.classList.remove('flex');
    editForm.reset();
    editPreview.src = editCurrentImage.value || 'https://via.placeholder.com/120?text=No+Image';
  });
});

// Submit edit form
editForm.addEventListener('submit', e => {
  e.preventDefault();
  showConfirm("Save changes to this product?", () => editForm.submit());
});

// --- Populate filter subcategories ---
const filterCategory = document.getElementById('filter-category');
const filterSubcategory = document.getElementById('filter-subcategory');
if (filterCategory && filterSubcategory) {
  const selectedSub = '<?php echo htmlspecialchars($fsub, ENT_QUOTES); ?>';
  const populateFilterSubs = () => {
    filterSubcategory.innerHTML = '<option value="">All Subcategories</option>';
    const subs = categories[filterCategory.value] || [];
    subs.forEach(s => {
      const o = document.createElement('option');
      o.value = s; o.textContent = s; if (s === selectedSub) o.selected = true; filterSubcategory.appendChild(o);
    });
  };
  populateFilterSubs();
  filterCategory.addEventListener('change', () => { filterSubcategory.value=''; populateFilterSubs(); });
}
</script>


</body>
</html>
