<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Load currency settings for products API (requires database.php first)
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/settings_helper.php';

$currencySymbol = getCurrencySymbol();
$currencyCode = getDefaultCurrency();

// Fetch products using PDO
$stmt = $pdo->query("SELECT * FROM products");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert prices to current currency
$baseCurrency = getSetting('base_currency', 'PHP');
foreach ($products as &$product) {
    if ($baseCurrency !== $currencyCode && $product['price'] > 0) {
        $product['price'] = convertCurrency($product['price'], $baseCurrency, $currencyCode);
    }
}
unset($product); // Break reference

// Add currency info to response
$response = [
    'products' => $products,
    'currency' => [
        'symbol' => $currencySymbol,
        'code' => $currencyCode
    ]
];

echo json_encode($response);
?>
