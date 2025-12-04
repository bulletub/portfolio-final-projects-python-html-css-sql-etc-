<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include('database.php');
require_once 'settings_helper.php';

// Get currency settings for admin display
$currencySymbol = getCurrencySymbol();
$currencyCode = getDefaultCurrency();

// --- Role-based Access Control ---
$userId = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$userId || (!in_array('super_admin', $roles) && !in_array('admin_dashboard', $roles))) {
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

// --- Fetch summary stats ---
// Users
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type='admin'")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type='customer'")->fetchColumn();

// Orders
$totalOrders = $pdo->query("SELECT COUNT(*) FROM order_groups")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE status='pending'")->fetchColumn();
$shippingOrders = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE status='shipping'")->fetchColumn();
$completedOrders = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE status='completed'")->fetchColumn();
$cancelledOrders = $pdo->query("SELECT COUNT(*) FROM order_groups WHERE status='cancelled'")->fetchColumn();

// Fetch detailed sales data (default: last 30 days)
$salesRange = $_GET['sales_range'] ?? 'monthly';
$salesDateFilter = '';
switch ($salesRange) {
    case 'today':
        $salesDateFilter = "AND DATE(og.created_at) = CURDATE()";
        break;
    case 'yesterday':
        $salesDateFilter = "AND DATE(og.created_at) = CURDATE() - INTERVAL 1 DAY";
        break;
    case 'weekly':
        $salesDateFilter = "AND og.created_at >= CURDATE() - INTERVAL 7 DAY";
        break;
    case 'monthly':
        $salesDateFilter = "AND og.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
    case 'annually':
        $salesDateFilter = "AND og.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        break;
    default:
        $salesDateFilter = "AND og.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
}

$salesQuery = "
    SELECT 
        og.id AS order_id,
        og.created_at AS order_date,
        u.name AS customer_name,
        u.email AS customer_email,
        og.status,
        COALESCE(NULLIF(og.payment_method, ''), og.payment_code) AS payment_method,
        og.payment_code,
        og.promo_code,
        og.discount_amount,
        GROUP_CONCAT(CONCAT(p.name, ' (x', o.quantity, ')') SEPARATOR ', ') AS products,
        SUM(o.price * o.quantity) AS subtotal,
        COALESCE(og.discount_amount, 0) AS discount,
        (SUM(o.price * o.quantity) - COALESCE(og.discount_amount, 0) + 50) AS total
    FROM order_groups og
    LEFT JOIN users u ON og.user_id = u.id
    LEFT JOIN orders o ON og.id = o.order_group_id
    LEFT JOIN products p ON o.product_id = p.id
    WHERE 1=1 $salesDateFilter
    GROUP BY og.id
    ORDER BY og.created_at DESC
    LIMIT 500
";

$salesStmt = $pdo->query($salesQuery);
$salesData = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Admin Dashboard | PetPantry+</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      overflow-x: hidden;
      max-width: 100vw;
      width: 100%;
    }
    
    /* Print Styles */
    @media print {
      body * {
        visibility: hidden;
      }
      .sales-report-section, .sales-report-section * {
        visibility: visible;
      }
      .sales-report-section {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 20px;
      }
      .print-header {
        display: block !important;
      }
      .no-print {
        display: none !important;
      }
      .sales-table {
        border-collapse: collapse;
        width: 100%;
        font-size: 10px;
      }
      .sales-table th,
      .sales-table td {
        border: 1px solid #000;
        padding: 6px;
        text-align: left;
      }
      .sales-table th {
        background-color: #f3f4f6 !important;
        font-weight: bold;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .status-badge {
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 9px;
      }
    }
    
    .status-badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 600;
      display: inline-block;
    }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-shipping { background: #dbeafe; color: #1e40af; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    
    /* Chart container animations */
    .chart-container {
      transition: all 0.3s ease-in-out;
      opacity: 1;
    }
    .chart-container.loading {
      opacity: 0.6;
      pointer-events: none;
    }
    
    /* Smooth transitions for chart updates */
    .chart-wrapper {
      position: relative;
      transition: transform 0.3s ease-in-out;
    }
    
    /* Loading overlay */
    .chart-loading {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 10;
      background: rgba(255, 255, 255, 0.9);
      padding: 10px 20px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      display: none;
      font-size: 14px;
      color: #6366f1;
      font-weight: 500;
    }
    .chart-loading.active {
      display: block;
      animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translate(-50%, -60%); }
      to { opacity: 1; transform: translate(-50%, -50%); }
    }
    
    /* Smooth hover effects */
    button, select {
      transition: all 0.2s ease-in-out;
    }
    
    button:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    select:hover {
      border-color: #f97316;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen font-sans text-gray-800">

<div class="flex min-h-screen">
  <?php include('admin_navbar.php'); ?>

  <div class="flex-1 p-8">
   <header class="flex justify-between items-center mb-6 py-4 px-2 md:px-0 border-b border-gray-200">
  <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
  <span class="text-gray-600">Welcome, <strong class="text-orange-500"><?php echo htmlspecialchars($_SESSION['name']); ?></strong></span>
</header>


    <!-- Summary Cards -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white p-4 rounded shadow text-center">
        <h3 class="text-lg font-semibold">Total Users</h3>
        <p class="text-2xl text-blue-600 font-bold"><?php echo $totalUsers; ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow text-center">
        <h3 class="text-lg font-semibold">Customers</h3>
        <p class="text-2xl text-green-600 font-bold"><?php echo $totalCustomers; ?></p>
      </div>
      <div class="bg-white p-4 rounded shadow text-center">
        <h3 class="text-lg font-semibold">Admins</h3>
        <p class="text-2xl text-purple-600 font-bold"><?php echo $totalAdmins; ?></p>
      </div>
    </section>

    <!-- Charts Section -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <!-- Orders Summary -->
      <div class="bg-white p-6 rounded shadow text-center chart-container">
        <h3 class="text-lg font-semibold mb-4">Orders Summary</h3>
        <div class="chart-wrapper relative">
          <div class="chart-loading">Loading...</div>
          <canvas id="ordersChart"></canvas>
        </div>
      </div>

      <!-- Total Profit (Line Chart with Range Filter) -->
      <div class="bg-white p-6 rounded shadow text-center chart-container">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold">Total Profit</h3>
          <select id="profitRange" class="border rounded px-2 py-1 text-sm transition-colors">
            <option value="today">Today</option>
            <option value="yesterday">Yesterday</option>
            <option value="weekly" selected>Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="annually">Annually</option>
          </select>
        </div>
        <div class="w-full h-64 mt-6 chart-wrapper relative">
          <div class="chart-loading">Loading...</div>
          <canvas id="profitChart"></canvas>
        </div>
      </div>

      <!-- Top Seller -->
      <div class="bg-white p-6 rounded shadow text-center chart-container">
        <h3 class="text-lg font-semibold mb-4">Top Seller</h3>
        <div class="chart-wrapper relative">
          <div class="chart-loading">Loading...</div>
          <canvas id="topSellerChart"></canvas>
        </div>
      </div>
    </section>

    <div class="text-center mb-8">
      <button onclick="refreshCharts()" 
              class="px-4 py-2 bg-blue-600 text-white rounded shadow hover:bg-blue-700">
        Refresh
      </button>
    </div>

    <!-- Detailed Sales Report Section -->
    <section class="sales-report-section bg-white p-6 rounded shadow mb-8">
      <div class="print-header mb-4" style="display: none;">
        <h1 class="text-3xl font-bold text-center mb-2">PetPantry+ Sales Report</h1>
        <p class="text-center text-gray-600">Period: <?php 
          $rangeLabels = [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'weekly' => 'Last 7 Days',
            'monthly' => 'Last 30 Days',
            'annually' => 'Last Year'
          ];
          echo $rangeLabels[$salesRange] ?? 'Last 30 Days';
        ?></p>
        <p class="text-center text-gray-600">Generated: <?php echo date('F d, Y h:i A'); ?></p>
        <hr class="my-4 border-gray-300">
      </div>
      <div class="flex justify-between items-center mb-4 flex-wrap gap-4">
        <h2 class="text-2xl font-bold text-gray-800">Detailed Sales Report</h2>
        <div class="flex gap-2 flex-wrap">
          <select id="salesRange" class="border rounded px-3 py-2 text-sm no-print" onchange="filterSales()">
            <option value="today" <?php echo $salesRange === 'today' ? 'selected' : ''; ?>>Today</option>
            <option value="yesterday" <?php echo $salesRange === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
            <option value="weekly" <?php echo $salesRange === 'weekly' ? 'selected' : ''; ?>>Last 7 Days</option>
            <option value="monthly" <?php echo $salesRange === 'monthly' ? 'selected' : ''; ?>>Last 30 Days</option>
            <option value="annually" <?php echo $salesRange === 'annually' ? 'selected' : ''; ?>>Last Year</option>
          </select>
          <button onclick="window.print()" 
                  class="px-4 py-2 bg-green-600 text-white rounded shadow hover:bg-green-700 no-print">
            🖨️ Print Report
          </button>
        </div>
      </div>

      <?php 
      $totalSales = 0;
      $totalOrders = count($salesData);
      foreach ($salesData as $sale) {
        $totalSales += floatval($sale['total']);
      }
      ?>

      <div class="mb-4 no-print">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-blue-50 p-3 rounded">
            <p class="text-sm text-gray-600">Total Orders</p>
            <p class="text-2xl font-bold text-blue-600"><?php echo $totalOrders; ?></p>
          </div>
          <div class="bg-green-50 p-3 rounded">
            <p class="text-sm text-gray-600">Total Sales</p>
            <p class="text-2xl font-bold text-green-600"><?php echo $currencySymbol . number_format($totalSales, 2); ?></p>
          </div>
          <div class="bg-purple-50 p-3 rounded">
            <p class="text-sm text-gray-600">Average Order</p>
            <p class="text-2xl font-bold text-purple-600"><?php echo $currencySymbol . ($totalOrders > 0 ? number_format($totalSales / $totalOrders, 2) : '0.00'); ?></p>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="sales-table w-full border-collapse">
          <thead>
            <tr class="bg-gray-100">
              <th class="border px-4 py-3 text-left">Order ID</th>
              <th class="border px-4 py-3 text-left">Date</th>
              <th class="border px-4 py-3 text-left">Customer</th>
              <th class="border px-4 py-3 text-left">Products</th>
              <th class="border px-4 py-3 text-right">Subtotal</th>
              <th class="border px-4 py-3 text-right">Discount</th>
              <th class="border px-4 py-3 text-right">Total</th>
              <th class="border px-4 py-3 text-left">Status</th>
              <th class="border px-4 py-3 text-left">Payment</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($salesData)): ?>
              <tr>
                <td colspan="9" class="border px-4 py-8 text-center text-gray-500">
                  No sales data found for the selected period.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($salesData as $sale): 
                $statusClass = 'status-' . strtolower($sale['status']);
              ?>
                <tr class="hover:bg-gray-50">
                  <td class="border px-4 py-2">#<?php echo htmlspecialchars($sale['order_id']); ?></td>
                  <td class="border px-4 py-2"><?php echo date('M d, Y H:i', strtotime($sale['order_date'])); ?></td>
                  <td class="border px-4 py-2">
                    <div>
                      <div class="font-medium"><?php echo htmlspecialchars($sale['customer_name'] ?? 'N/A'); ?></div>
                      <div class="text-xs text-gray-500"><?php echo htmlspecialchars($sale['customer_email'] ?? ''); ?></div>
                    </div>
                  </td>
                  <td class="border px-4 py-2 text-sm"><?php echo htmlspecialchars($sale['products'] ?? 'N/A'); ?></td>
                  <td class="border px-4 py-2 text-right"><?php echo $currencySymbol . number_format(floatval($sale['subtotal']), 2); ?></td>
                  <td class="border px-4 py-2 text-right">
                    <?php 
                    $discount = floatval($sale['discount'] ?? 0);
                    if ($discount > 0) {
                      echo '<span class="text-green-600">-' . $currencySymbol . number_format($discount, 2) . '</span>';
                      if ($sale['promo_code']) {
                        echo '<br><span class="text-xs text-gray-500">(' . htmlspecialchars($sale['promo_code']) . ')</span>';
                      }
                    } else {
                      echo $currencySymbol . '0.00';
                    }
                    ?>
                  </td>
                  <td class="border px-4 py-2 text-right font-semibold"><?php echo $currencySymbol . number_format(floatval($sale['total']), 2); ?></td>
                  <td class="border px-4 py-2">
                    <span class="status-badge <?php echo $statusClass; ?>">
                      <?php echo htmlspecialchars(ucfirst($sale['status'])); ?>
                    </span>
                  </td>
                  <td class="border px-4 py-2">
                    <?php 
                    // Format payment method display with icons and proper names
                    // Check both payment_method and payment_code fields
                    $paymentMethod = trim($sale['payment_method'] ?? '');
                    $paymentCode = trim($sale['payment_code'] ?? '');
                    
                    // Use payment_code as fallback if payment_method is empty or invalid
                    if (empty($paymentMethod) || strtolower($paymentMethod) === 'null') {
                        $paymentMethod = $paymentCode;
                    }
                    
                    // If still empty, try both fields one more time (handle COALESCE from SQL)
                    if (empty($paymentMethod) && !empty($paymentCode)) {
                        $paymentMethod = $paymentCode;
                    }
                    
                    $paymentMethodLower = strtolower(trim($paymentMethod));
                    
                    // Normalize common variations - but don't clear if it's a valid code
                    if ($paymentMethodLower === 'null' || $paymentMethodLower === '') {
                        $paymentMethod = '';
                        $paymentMethodLower = '';
                    }
                    
                    // Map payment codes/names to display names and icons (fallback)
                    $paymentDisplay = [
                        'cod' => ['name' => 'Cash on Delivery', 'icon' => '💵'],
                        'cash' => ['name' => 'Cash on Delivery', 'icon' => '💵'],
                        'cash on delivery' => ['name' => 'Cash on Delivery', 'icon' => '💵'],
                        'paypal' => ['name' => 'PayPal', 'icon' => '💳'],
                        'gcash' => ['name' => 'GCash', 'icon' => '📱'],
                        'bank' => ['name' => 'Bank Transfer', 'icon' => '🏦'],
                        'bank_transfer' => ['name' => 'Bank Transfer', 'icon' => '🏦'],
                        'bank transfer' => ['name' => 'Bank Transfer', 'icon' => '🏦'],
                        'card' => ['name' => 'Card Payment', 'icon' => '💳'],
                        'credit card' => ['name' => 'Card Payment', 'icon' => '💳'],
                        'debit card' => ['name' => 'Card Payment', 'icon' => '💳'],
                    ];
                    
                    $displayName = 'Not Specified';
                    $displayIcon = '❓';
                    
                    // Try to get from payment_options table for active methods (preferred method)
                    // Check both paymentMethod and paymentCode to ensure we don't miss valid data
                    $searchValue = !empty($paymentMethod) ? $paymentMethod : $paymentCode;
                    $searchValueLower = strtolower(trim($searchValue));
                    
                    if (!empty($searchValue) && $searchValueLower !== 'null') {
                        try {
                            // Check if payment_options table exists and query it
                            $tableCheck = $pdo->query("SHOW TABLES LIKE 'payment_options'");
                            if ($tableCheck->rowCount() > 0) {
                                // Try matching by code first, then by name (case-insensitive)
                                $stmt = $pdo->prepare("SELECT name, icon FROM payment_options WHERE (LOWER(code) = LOWER(?) OR LOWER(name) = LOWER(?)) LIMIT 1");
                                $stmt->execute([$searchValue, $searchValue]);
                                $paymentOption = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($paymentOption) {
                                    $displayName = $paymentOption['name'];
                                    $displayIcon = !empty($paymentOption['icon']) ? $paymentOption['icon'] : '💳';
                                } else {
                                    // No match in database, use fallback map
                                    $displayInfo = $paymentDisplay[$searchValueLower] ?? ['name' => ucfirst($searchValue), 'icon' => '💳'];
                                    $displayName = $displayInfo['name'];
                                    $displayIcon = $displayInfo['icon'];
                                }
                            } else {
                                // Table doesn't exist, use fallback map
                                $displayInfo = $paymentDisplay[$searchValueLower] ?? ['name' => ucfirst($searchValue), 'icon' => '💳'];
                                $displayName = $displayInfo['name'];
                                $displayIcon = $displayInfo['icon'];
                            }
                        } catch (Exception $e) {
                            // Error querying, use fallback map
                            $displayInfo = $paymentDisplay[$searchValueLower] ?? ['name' => ucfirst($searchValue), 'icon' => '💳'];
                            $displayName = $displayInfo['name'];
                            $displayIcon = $displayInfo['icon'];
                        }
                    }
                    
                    echo '<span class="flex items-center gap-2 whitespace-nowrap">';
                    echo '<span class="text-lg">' . htmlspecialchars($displayIcon) . '</span>';
                    echo '<span class="font-medium">' . htmlspecialchars($displayName) . '</span>';
                    echo '</span>';
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <tr class="bg-gray-100 font-bold">
              <td colspan="4" class="border px-4 py-3 text-right">Totals:</td>
              <td class="border px-4 py-3 text-right">
                <?php 
                $grandSubtotal = 0;
                foreach ($salesData as $sale) {
                  $grandSubtotal += floatval($sale['subtotal']);
                }
                echo $currencySymbol . number_format($grandSubtotal, 2);
                ?>
              </td>
              <td class="border px-4 py-3 text-right text-red-600">
                <?php 
                $grandDiscount = 0;
                foreach ($salesData as $sale) {
                  $grandDiscount += floatval($sale['discount'] ?? 0);
                }
                echo '-' . $currencySymbol . number_format($grandDiscount, 2);
                ?>
              </td>
              <td class="border px-4 py-3 text-right">
                <?php echo $currencySymbol . number_format($totalSales, 2); ?>
              </td>
              <td colspan="2" class="border px-4 py-3"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="mt-4 text-sm text-gray-500 no-print">
        <p>Report generated on <?php echo date('F d, Y h:i A'); ?></p>
        <p>Showing <?php echo count($salesData); ?> orders</p>
      </div>
    </section>

  </div>
</div>

<script>
let ordersChart, profitChart, topSellerChart;

// Common animation configuration
const chartAnimations = {
  animation: {
    duration: 1200,
    easing: 'easeInOutCubic',
    animateRotate: true,
    animateScale: true
  },
  transitions: {
    active: {
      animation: {
        duration: 400
      }
    },
    resize: {
      animation: {
        duration: 300
      }
    }
  }
};

function showChartLoading(chartId) {
  const container = document.querySelector(`#${chartId}`).closest('.chart-wrapper');
  if (container) {
    const loading = container.querySelector('.chart-loading');
    if (loading) loading.classList.add('active');
  }
}

function hideChartLoading(chartId) {
  const container = document.querySelector(`#${chartId}`).closest('.chart-wrapper');
  if (container) {
    const loading = container.querySelector('.chart-loading');
    if (loading) loading.classList.remove('active');
  }
}

function loadOrdersSummary() {
  const ctx = document.getElementById('ordersChart');
  const ctx2d = ctx.getContext('2d');
  
  showChartLoading('ordersChart');
  
  // Use update if chart exists, otherwise create new
  if (ordersChart) {
    ordersChart.data.datasets[0].data = [
      <?php echo $pendingOrders; ?>,
      <?php echo $shippingOrders; ?>,
      <?php echo $completedOrders; ?>,
      <?php echo $cancelledOrders; ?>
    ];
    ordersChart.update('active');
    setTimeout(() => hideChartLoading('ordersChart'), 400);
  } else {
    ordersChart = new Chart(ctx2d, {
      type: 'doughnut',
      data: {
        labels: ['Pending', 'Shipping', 'Completed', 'Cancelled'],
        datasets: [{
          data: [
            <?php echo $pendingOrders; ?>,
            <?php echo $shippingOrders; ?>,
            <?php echo $completedOrders; ?>,
            <?php echo $cancelledOrders; ?>
          ],
          backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
          borderWidth: 2,
          borderColor: '#ffffff',
          hoverOffset: 8
        }]
      },
      options: { 
        responsive: true, 
        maintainAspectRatio: true,
        aspectRatio: 1,
        animation: {
          ...chartAnimations.animation,
          duration: 1000
        },
        plugins: { 
          legend: { 
            position: 'bottom',
            labels: {
              padding: 15,
              usePointStyle: true,
              font: {
                size: 12
              }
            }
          },
          tooltip: {
            enabled: true,
            animation: {
              animateScale: true,
              animateRotate: true
            }
          }
        }
      }
    });
    setTimeout(() => hideChartLoading('ordersChart'), 1000);
  }
}

function loadTotalProfit() {
  const range = document.getElementById('profitRange').value;
  const chartCanvas = document.getElementById('profitChart');
  
  showChartLoading('profitChart');
  
  fetch('profit_data.php?range=' + range)
    .then(res => res.json())
    .then(data => {
      const labels = data.map(item => item.date);
      const profits = data.map(item => item.profit);

      // Use update if chart exists for smooth transition
      if (profitChart) {
        profitChart.data.labels = labels;
        profitChart.data.datasets[0].data = profits;
        profitChart.options.scales.x.title.text = range === 'annually' ? 'Month' : 'Date';
        profitChart.update('active');
        setTimeout(() => hideChartLoading('profitChart'), 500);
      } else {
        profitChart = new Chart(chartCanvas, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Total Profit (<?php echo $currencySymbol; ?>)',
              data: profits,
              borderColor: '#4CAF50',
              backgroundColor: 'rgba(76, 175, 80, 0.2)',
              tension: 0.4,
              fill: true,
              pointRadius: 5,
              pointHoverRadius: 7,
              pointBackgroundColor: '#4CAF50',
              pointBorderColor: '#ffffff',
              pointBorderWidth: 2,
              pointHoverBackgroundColor: '#45a049',
              pointHoverBorderColor: '#ffffff',
              borderWidth: 3,
              cubicInterpolationMode: 'monotone'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
              ...chartAnimations.animation,
              duration: 1500
            },
            interaction: {
              intersect: false,
              mode: 'index'
            },
            plugins: {
              legend: { 
                display: true, 
                position: 'top',
                labels: {
                  font: {
                    size: 12,
                    weight: '500'
                  },
                  padding: 15
                }
              },
              tooltip: {
                enabled: true,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: {
                  size: 14,
                  weight: 'bold'
                },
                bodyFont: {
                  size: 13
                },
                cornerRadius: 8,
                displayColors: true,
                animation: {
                  animateScale: true
                }
              }
            },
            scales: {
              y: { 
                beginAtZero: true, 
                title: { 
                  display: true, 
                  text: 'Profit (<?php echo $currencySymbol; ?>)',
                  font: {
                    size: 12,
                    weight: '500'
                  }
                },
                grid: {
                  color: 'rgba(0, 0, 0, 0.05)'
                },
                ticks: {
                  callback: function(value) {
                    return '<?php echo $currencySymbol; ?>' + value.toLocaleString();
                  }
                }
              },
              x: { 
                title: { 
                  display: true, 
                  text: range === 'annually' ? 'Month' : 'Date',
                  font: {
                    size: 12,
                    weight: '500'
                  }
                },
                grid: {
                  display: false
                }
              }
            }
          }
        });
        setTimeout(() => hideChartLoading('profitChart'), 1500);
      }
    })
    .catch(error => {
      console.error('Error loading profit data:', error);
      hideChartLoading('profitChart');
    });
}

function loadTopSeller() {
  showChartLoading('topSellerChart');
  
  fetch('top_seller.php')
    .then(res => res.json())
    .then(data => {
      const labels = data.map(item => item.product_name);
      const values = data.map(item => item.total_sold);
      
      // Use update if chart exists for smooth transition
      if (topSellerChart) {
        topSellerChart.data.labels = labels;
        topSellerChart.data.datasets[0].data = values;
        topSellerChart.update('active');
        setTimeout(() => hideChartLoading('topSellerChart'), 400);
      } else {
        topSellerChart = new Chart(document.getElementById('topSellerChart'), {
          type: 'doughnut',
          data: { 
            labels: labels, 
            datasets: [{ 
              data: values, 
              backgroundColor: ['#FF5733', '#33FF57', '#3357FF', '#F1C40F', '#8E44AD', '#FF6B9D', '#C44569'],
              borderWidth: 2,
              borderColor: '#ffffff',
              hoverOffset: 10
            }] 
          },
          options: { 
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1,
            animation: {
              ...chartAnimations.animation,
              duration: 1000,
              animateRotate: true,
              animateScale: true
            },
            plugins: { 
              legend: { 
                position: 'bottom', 
                labels: { 
                  usePointStyle: true, 
                  padding: 20, 
                  font: { 
                    size: 11 
                  },
                  boxWidth: 12,
                  boxHeight: 12
                } 
              },
              tooltip: {
                enabled: true,
                animation: {
                  animateScale: true,
                  animateRotate: true
                },
                callbacks: {
                  label: function(context) {
                    const label = context.label || '';
                    const value = context.parsed || 0;
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = ((value / total) * 100).toFixed(1);
                    return `${label}: ${value} units (${percentage}%)`;
                  }
                }
              }
            } 
          }
        });
        setTimeout(() => hideChartLoading('topSellerChart'), 1000);
      }
    })
    .catch(error => {
      console.error('Error loading top seller data:', error);
      hideChartLoading('topSellerChart');
    });
}

function refreshCharts() {
  // Add smooth fade effect
  const containers = document.querySelectorAll('.chart-container');
  containers.forEach(container => {
    container.classList.add('loading');
  });
  
  setTimeout(() => {
    loadOrdersSummary();
    loadTotalProfit();
    loadTopSeller();
    
    // Remove loading state after animations
    setTimeout(() => {
      containers.forEach(container => {
        container.classList.remove('loading');
      });
    }, 1500);
  }, 100);
}

// Re-fetch Total Profit when dropdown changes with smooth transition
document.addEventListener('DOMContentLoaded', () => {
  const profitRangeSelect = document.getElementById('profitRange');
  if (profitRangeSelect) {
    profitRangeSelect.addEventListener('change', function() {
      // Add smooth transition effect
      const container = this.closest('.chart-container');
      if (container) {
        container.style.opacity = '0.7';
        container.style.transform = 'scale(0.98)';
      }
      
      loadTotalProfit();
      
      // Restore after update
      setTimeout(() => {
        if (container) {
          container.style.opacity = '1';
          container.style.transform = 'scale(1)';
        }
      }, 600);
    });
  }
});

// Filter sales table by date range
function filterSales() {
  const range = document.getElementById('salesRange').value;
  const url = new URL(window.location);
  url.searchParams.set('sales_range', range);
  window.location.href = url.toString();
}

// Load everything on page load with smooth entrance
document.addEventListener('DOMContentLoaded', () => {
  // Initial load with fade-in effect
  const chartsSection = document.querySelector('.grid.grid-cols-1');
  if (chartsSection) {
    chartsSection.style.opacity = '0';
    chartsSection.style.transform = 'translateY(20px)';
  }
  
  refreshCharts();
  
  // Fade in charts section
  setTimeout(() => {
    if (chartsSection) {
      chartsSection.style.transition = 'opacity 0.6s ease-in-out, transform 0.6s ease-in-out';
      chartsSection.style.opacity = '1';
      chartsSection.style.transform = 'translateY(0)';
    }
  }, 100);
});
</script>

</body>
</html>
    