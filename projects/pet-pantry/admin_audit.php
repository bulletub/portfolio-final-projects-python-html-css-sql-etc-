<?php
// admin_audit.php - Audit Trail Viewer
session_start();
include('database.php');

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

// --- Filters ---
$filterAction = $_GET['action'] ?? '';
$filterTable = $_GET['table'] ?? '';
$filterUser = $_GET['user'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($filterAction) {
    $where[] = "action = ?";
    $params[] = $filterAction;
}
if ($filterTable) {
    $where[] = "table_name = ?";
    $params[] = $filterTable;
}
if ($filterUser) {
    $where[] = "user_id = ?";
    $params[] = $filterUser;
}
if ($filterDateFrom) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $filterDateFrom;
}
if ($filterDateTo) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $filterDateTo;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total records
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_trail {$whereClause}");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Fetch audit logs
$stmt = $pdo->prepare("
    SELECT * FROM audit_trail 
    {$whereClause}
    ORDER BY created_at DESC 
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$auditLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique values for filters
$actions = $pdo->query("SELECT DISTINCT action FROM audit_trail ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$tables = $pdo->query("SELECT DISTINCT table_name FROM audit_trail ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
$users = $pdo->query("SELECT DISTINCT user_id, user_name FROM audit_trail ORDER BY user_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Audit Trail | PetPantry+</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen font-sans text-gray-800">

<div class="flex min-h-screen">
  <?php include('admin_navbar.php'); ?>

  <div class="flex-1 p-8">
    <header class="flex justify-between items-center mb-6 py-4 px-2 md:px-0 border-b border-gray-200">
      <h1 class="text-2xl font-bold text-gray-800">📋 Audit Trail</h1>
      <span class="text-gray-600">Welcome, <strong class="text-orange-500"><?php echo htmlspecialchars($userName); ?></strong></span>
    </header>

    <!-- Filters -->
    <section class="bg-white rounded-xl shadow-md border p-6 mb-6">
      <h2 class="text-lg font-semibold mb-4">🔍 Filters</h2>
      <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Action</label>
          <select name="action" class="border rounded-lg p-2 w-full text-sm">
            <option value="">All Actions</option>
            <?php foreach ($actions as $act): ?>
              <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $filterAction === $act ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($act); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Table</label>
          <select name="table" class="border rounded-lg p-2 w-full text-sm">
            <option value="">All Tables</option>
            <?php foreach ($tables as $tbl): ?>
              <option value="<?php echo htmlspecialchars($tbl); ?>" <?php echo $filterTable === $tbl ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($tbl); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">User</label>
          <select name="user" class="border rounded-lg p-2 w-full text-sm">
            <option value="">All Users</option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo $u['user_id']; ?>" <?php echo $filterUser == $u['user_id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($u['user_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Date From</label>
          <input type="date" name="date_from" value="<?php echo htmlspecialchars($filterDateFrom); ?>" class="border rounded-lg p-2 w-full text-sm">
        </div>
        
        <div>
          <label class="block text-sm font-medium mb-1">Date To</label>
          <input type="date" name="date_to" value="<?php echo htmlspecialchars($filterDateTo); ?>" class="border rounded-lg p-2 w-full text-sm">
        </div>
        
        <div class="md:col-span-3 lg:col-span-5 flex gap-2">
          <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg">
            Apply Filters
          </button>
          <a href="admin_audit.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded-lg">
            Clear Filters
          </a>
        </div>
      </form>
    </section>

    <!-- Audit Logs Table -->
    <section class="bg-white rounded-xl shadow-md border overflow-hidden">
      <div class="px-6 py-4 bg-gradient-to-r from-orange-100 to-orange-50 flex items-center justify-between">
        <h3 class="text-lg font-semibold">Audit Logs</h3>
        <div class="text-sm text-gray-600"><?php echo number_format($totalRecords); ?> total records</div>
      </div>
      
      <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50 sticky top-0">
            <tr>
              <th class="px-4 py-3 text-left font-semibold">ID</th>
              <th class="px-4 py-3 text-left font-semibold">Date/Time</th>
              <th class="px-4 py-3 text-left font-semibold">User</th>
              <th class="px-4 py-3 text-left font-semibold">Action</th>
              <th class="px-4 py-3 text-left font-semibold">Table</th>
              <th class="px-4 py-3 text-left font-semibold">Record ID</th>
              <th class="px-4 py-3 text-left font-semibold">Description</th>
              <th class="px-4 py-3 text-left font-semibold">IP Address</th>
              <th class="px-4 py-3 text-center font-semibold">Details</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <?php if (!empty($auditLogs)): ?>
              <?php foreach ($auditLogs as $log): ?>
                <tr class="hover:bg-orange-50">
                  <td class="px-4 py-3"><?php echo $log['id']; ?></td>
                  <td class="px-4 py-3 whitespace-nowrap">
                    <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                  </td>
                  <td class="px-4 py-3"><?php echo htmlspecialchars($log['user_name']); ?></td>
                  <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded text-xs font-semibold
                      <?php 
                        echo match($log['action']) {
                          'CREATE' => 'bg-green-100 text-green-800',
                          'UPDATE' => 'bg-blue-100 text-blue-800',
                          'DELETE' => 'bg-red-100 text-red-800',
                          default => 'bg-gray-100 text-gray-800'
                        };
                      ?>">
                      <?php echo htmlspecialchars($log['action']); ?>
                    </span>
                  </td>
                  <td class="px-4 py-3 font-mono text-xs"><?php echo htmlspecialchars($log['table_name']); ?></td>
                  <td class="px-4 py-3"><?php echo $log['record_id'] ?? 'N/A'; ?></td>
                  <td class="px-4 py-3 max-w-xs truncate"><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                  <td class="px-4 py-3 font-mono text-xs"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                  <td class="px-4 py-3 text-center">
                    <button onclick="viewDetails(<?php echo htmlspecialchars(json_encode($log)); ?>)" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs">
                      View
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="px-6 py-8 text-center text-gray-500">No audit logs found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 bg-gray-50 flex items-center justify-between border-t">
          <div class="text-sm text-gray-600">
            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
          </div>
          <div class="flex gap-2">
            <?php if ($page > 1): ?>
              <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>" 
                 class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded">Previous</a>
            <?php endif; ?>
            
            <?php if ($page < $totalPages): ?>
              <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY)); ?>" 
                 class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded">Next</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 p-4">
  <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
    <div class="p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold">Audit Log Details</h3>
        <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
      </div>
      
      <div id="detailsContent" class="space-y-4">
        <!-- Content populated by JS -->
      </div>
      
      <div class="mt-6 flex justify-end">
        <button onclick="closeDetailsModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function viewDetails(log) {
  const modal = document.getElementById('detailsModal');
  const content = document.getElementById('detailsContent');
  
  let oldValues = '';
  let newValues = '';
  
  try {
    if (log.old_values) {
      const parsed = JSON.parse(log.old_values);
      oldValues = '<pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">' + JSON.stringify(parsed, null, 2) + '</pre>';
    } else {
      oldValues = '<p class="text-gray-500 text-sm">No data</p>';
    }
  } catch(e) {
    oldValues = '<p class="text-red-500 text-sm">Error parsing data</p>';
  }
  
  try {
    if (log.new_values) {
      const parsed = JSON.parse(log.new_values);
      newValues = '<pre class="bg-gray-100 p-3 rounded text-xs overflow-x-auto">' + JSON.stringify(parsed, null, 2) + '</pre>';
    } else {
      newValues = '<p class="text-gray-500 text-sm">No data</p>';
    }
  } catch(e) {
    newValues = '<p class="text-red-500 text-sm">Error parsing data</p>';
  }
  
  content.innerHTML = `
    <div class="grid grid-cols-2 gap-4 text-sm">
      <div><strong>ID:</strong> ${log.id}</div>
      <div><strong>Date/Time:</strong> ${log.created_at}</div>
      <div><strong>User:</strong> ${log.user_name} (ID: ${log.user_id})</div>
      <div><strong>Action:</strong> <span class="px-2 py-1 rounded font-semibold ${log.action === 'CREATE' ? 'bg-green-100 text-green-800' : log.action === 'UPDATE' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800'}">${log.action}</span></div>
      <div><strong>Table:</strong> <code class="bg-gray-100 px-2 py-1 rounded">${log.table_name}</code></div>
      <div><strong>Record ID:</strong> ${log.record_id || 'N/A'}</div>
      <div class="col-span-2"><strong>Description:</strong> ${log.description || 'N/A'}</div>
      <div><strong>IP Address:</strong> <code class="bg-gray-100 px-2 py-1 rounded">${log.ip_address || 'N/A'}</code></div>
    </div>
    
    <div class="mt-6">
      <h4 class="font-semibold mb-2">Old Values:</h4>
      ${oldValues}
    </div>
    
    <div class="mt-6">
      <h4 class="font-semibold mb-2">New Values:</h4>
      ${newValues}
    </div>
  `;
  
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeDetailsModal() {
  const modal = document.getElementById('detailsModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}
</script>

</body>
</html>

