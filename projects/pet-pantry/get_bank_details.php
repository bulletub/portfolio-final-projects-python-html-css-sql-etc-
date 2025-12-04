<?php
// get_bank_details.php - Returns bank transfer details
session_start();
require_once 'settings_helper.php';
require_once 'payment_api_helper.php';

header('Content-Type: application/json');

// Check if user is logged in (optional - can be public)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$bankDetails = getBankDetails();

echo json_encode([
    'success' => true,
    'bank_name' => $bankDetails['bank_name'],
    'account_name' => $bankDetails['account_name'],
    'account_number' => $bankDetails['account_number'],
    'swift_code' => $bankDetails['swift_code'],
    'branch' => $bankDetails['branch']
]);

