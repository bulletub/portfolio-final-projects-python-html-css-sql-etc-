<?php
// admin_payment_verification.php - Admin tool for verifying payments
session_start();
require_once 'database.php';
require_once 'settings_helper.php';
require_once 'payment_api_helper.php';

// Role-based Access Control
$userId = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("SELECT role_name FROM admin_roles WHERE user_id=?");
$stmt->execute([$userId]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$userId || (!in_array('super_admin', $roles) && !in_array('orders', $roles))) {
    header("Location: Login_and_creating_account_fixed.php#login");
    exit;
}

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'verify_payment') {
        $transactionId = $_POST['transaction_id'] ?? '';
        $orderGroupId = $_POST['order_group_id'] ?? null;
        
        try {
            // Update transaction status
            $stmt = $pdo->prepare("
                UPDATE payment_transactions 
                SET status = 'verified', 
                    verified_by = ?, 
                    verified_at = NOW() 
                WHERE transaction_id = ?
            ");
            $stmt->execute([$userId, $transactionId]);
            
            // Update order status if order_group_id provided
            if ($orderGroupId) {
                $stmt = $pdo->prepare("UPDATE order_groups SET status = 'Completed' WHERE id = ?");
                $stmt->execute([$orderGroupId]);
            }
            
            $message = "Payment verified successfully!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    } elseif ($action === 'reject_payment') {
        $transactionId = $_POST['transaction_id'] ?? '';
        
        try {
            $stmt = $pdo->prepare("
                UPDATE payment_transactions 
                SET status = 'failed', 
                    verified_by = ?, 
                    verified_at = NOW() 
                WHERE transaction_id = ?
            ");
            $stmt->execute([$userId, $transactionId]);
            
            $message = "Payment rejected.";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Fetch pending payments
try {
    $stmt = $pdo->query("
        SELECT pt.*, og.id as order_group_id, og.status as order_status, u.name as user_name, u.email as user_email
        FROM payment_transactions pt
        LEFT JOIN order_groups og ON pt.order_group_id = og.id
        LEFT JOIN users u ON pt.user_id = u.id
        WHERE pt.status IN ('pending', 'pending_verification')
        ORDER BY pt.created_at DESC
    ");
    $pendingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pendingPayments = [];
}

// Fetch all transactions
try {
    $stmt = $pdo->query("
        SELECT pt.*, og.id as order_group_id, og.status as order_status, u.name as user_name
        FROM payment_transactions pt
        LEFT JOIN order_groups og ON pt.order_group_id = og.id
        LEFT JOIN users u ON pt.user_id = u.id
        ORDER BY pt.created_at DESC
        LIMIT 100
    ");
    $allTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allTransactions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Verification | Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-white min-h-screen">
<div class="flex min-h-screen">
    <?php include('admin_navbar.php'); ?>
    
    <div class="flex-1 p-8">
        <header class="mb-6">
            <h1 class="text-2xl font-bold">💰 Payment Verification</h1>
            <p class="text-gray-600">Verify GCash, Bank Transfer, and other manual payments</p>
        </header>

        <?php if (isset($message)): ?>
        <div class="mb-4 p-4 rounded <?= $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- Pending Payments -->
        <section class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">⏳ Pending Verification (<?= count($pendingPayments) ?>)</h2>
            
            <?php if (empty($pendingPayments)): ?>
                <p class="text-gray-500">No pending payments to verify.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border px-4 py-2">Transaction ID</th>
                                <th class="border px-4 py-2">Payment Method</th>
                                <th class="border px-4 py-2">Amount</th>
                                <th class="border px-4 py-2">Customer</th>
                                <th class="border px-4 py-2">Order ID</th>
                                <th class="border px-4 py-2">Date</th>
                                <th class="border px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingPayments as $payment): ?>
                            <tr>
                                <td class="border px-4 py-2"><code class="text-xs"><?= htmlspecialchars($payment['transaction_id']) ?></code></td>
                                <td class="border px-4 py-2"><?= htmlspecialchars(ucfirst($payment['payment_code'])) ?></td>
                                <td class="border px-4 py-2 font-semibold"><?= formatCurrency($payment['amount']) ?></td>
                                <td class="border px-4 py-2">
                                    <?= htmlspecialchars($payment['user_name'] ?? 'N/A') ?><br>
                                    <span class="text-xs text-gray-500"><?= htmlspecialchars($payment['user_email'] ?? '') ?></span>
                                </td>
                                <td class="border px-4 py-2">
                                    <?php if ($payment['order_group_id']): ?>
                                        <a href="orders_tracker.php#order-<?= $payment['order_group_id'] ?>" class="text-blue-600 hover:underline">
                                            #<?= $payment['order_group_id'] ?>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td class="border px-4 py-2 text-sm"><?= date('M d, Y H:i', strtotime($payment['created_at'])) ?></td>
                                <td class="border px-4 py-2">
                                    <form method="POST" class="inline" onsubmit="return confirm('Verify this payment?')">
                                        <input type="hidden" name="action" value="verify_payment">
                                        <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($payment['transaction_id']) ?>">
                                        <input type="hidden" name="order_group_id" value="<?= $payment['order_group_id'] ?>">
                                        <button type="submit" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-sm">
                                            ✅ Verify
                                        </button>
                                    </form>
                                    <form method="POST" class="inline ml-2" onsubmit="return confirm('Reject this payment?')">
                                        <input type="hidden" name="action" value="reject_payment">
                                        <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($payment['transaction_id']) ?>">
                                        <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm">
                                            ❌ Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- All Transactions -->
        <section class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">📋 All Transactions</h2>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border px-4 py-2">Transaction ID</th>
                            <th class="border px-4 py-2">Method</th>
                            <th class="border px-4 py-2">Amount</th>
                            <th class="border px-4 py-2">Status</th>
                            <th class="border px-4 py-2">Date</th>
                            <th class="border px-4 py-2">Verified By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allTransactions as $txn): ?>
                        <tr>
                            <td class="border px-4 py-2"><code class="text-xs"><?= htmlspecialchars($txn['transaction_id']) ?></code></td>
                            <td class="border px-4 py-2"><?= htmlspecialchars(ucfirst($txn['payment_code'])) ?></td>
                            <td class="border px-4 py-2"><?= formatCurrency($txn['amount']) ?></td>
                            <td class="border px-4 py-2">
                                <?php
                                $statusColors = [
                                    'verified' => 'bg-green-100 text-green-700',
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'pending_verification' => 'bg-orange-100 text-orange-700',
                                    'failed' => 'bg-red-100 text-red-700',
                                    'cancelled' => 'bg-gray-100 text-gray-700'
                                ];
                                $color = $statusColors[$txn['status']] ?? 'bg-gray-100';
                                ?>
                                <span class="px-2 py-1 rounded text-xs <?= $color ?>">
                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $txn['status']))) ?>
                                </span>
                            </td>
                            <td class="border px-4 py-2"><?= date('M d, Y H:i', strtotime($txn['created_at'])) ?></td>
                            <td class="border px-4 py-2 text-xs"><?= $txn['verified_by'] ? 'Admin #' . $txn['verified_by'] : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
</body>
</html>

