<?php
/**
 * Test script for order status email
 * Access this file to send a test email: test_order_email.php
 */

require_once 'email_helper.php';

// ⚠️ IMPORTANT: Change this to YOUR email address before running!
$testEmail = 'villotajericho98@gmail.com'; // ← Change this to your email
$testName = 'Test Customer';
$testOrderId = 999;

// Test order details
$testOrderDetails = [
    'items' => [
        ['name' => 'Premium Dog Food - 5kg', 'quantity' => 2, 'price' => 850.00],
        ['name' => 'Cat Litter Box', 'quantity' => 1, 'price' => 450.00],
        ['name' => 'Pet Shampoo', 'quantity' => 3, 'price' => 120.00]
    ],
    'total' => 2610.00,
    'address' => '123 Pet Street, Animal City, PH 1234'
];

echo "<!DOCTYPE html><html><head><title>Email Test</title></head><body>";
echo "<h1>PetPantry+ Order Email Test</h1>";
echo "<p>Sending test emails to: <strong>{$testEmail}</strong></p>";
echo "<hr>";

// Test different statuses
$statuses = ['shipping', 'completed', 'cancelled'];

foreach ($statuses as $status) {
    echo "<h2>Testing: " . ucfirst($status) . " Status</h2>";
    
    $result = sendOrderStatusEmail(
        $testEmail,
        $testName,
        $testOrderId,
        $status,
        $testOrderDetails
    );
    
    if ($result) {
        echo "<p style='color: green;'>✅ {$status} email sent successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to send {$status} email</p>";
    }
    
    echo "<hr>";
}

echo "<h3>✅ Email test complete!</h3>";
echo "<p>Check your inbox at <strong>{$testEmail}</strong></p>";
echo "<p><em>Note: Emails may take a few minutes to arrive. Check spam folder if not received.</em></p>";
echo "</body></html>";
?>

