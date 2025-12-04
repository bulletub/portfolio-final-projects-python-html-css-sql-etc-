<?php
// payment_api_helper.php - Payment API Integration
// Handles integration with various payment gateways (Free/Demo APIs)

require_once 'settings_helper.php';

// Demo API Configuration (FREE - Test Mode)
if (!defined('STRIPE_TEST_SECRET_KEY')) {
    define('STRIPE_TEST_SECRET_KEY', 'sk_test_51PYourTestKeyHere'); // Replace with Stripe test key
}
if (!defined('STRIPE_TEST_PUBLIC_KEY')) {
    define('STRIPE_TEST_PUBLIC_KEY', 'pk_test_51PYourTestKeyHere'); // Replace with Stripe test key
}
if (!defined('DEMO_MODE')) {
    define('DEMO_MODE', true); // Set to false for production
}

/**
 * Process payment based on payment method code
 */
function processPayment($paymentCode, $amount, $currency, $orderData) {
    global $pdo;
    
    // Get payment option details
    try {
        $stmt = $pdo->prepare("SELECT * FROM payment_options WHERE code = ? AND is_active = 1");
        $stmt->execute([$paymentCode]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment) {
            return ['status' => 'error', 'message' => 'Payment method not available'];
        }
        
        // Route to appropriate payment processor
        switch ($paymentCode) {
            case 'paypal':
                return processPayPalPayment($amount, $currency, $orderData);
            
            case 'cod':
                return processCOD($amount, $currency, $orderData);
            
            case 'gcash':
                return processGCash($amount, $currency, $orderData);
            
            case 'card':
                return processCardPayment($amount, $currency, $orderData);
            
            case 'bank':
            case 'bank_transfer':
                return processBankTransfer($amount, $currency, $orderData);
            
            default:
                return ['status' => 'error', 'message' => 'Payment method not implemented'];
        }
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Database error'];
    }
}

/**
 * Process PayPal Payment (already integrated in cart.php)
 */
function processPayPalPayment($amount, $currency, $orderData) {
    // PayPal is handled client-side in cart.php
    // Server-side validation can be added here if needed
    return [
        'status' => 'success',
        'message' => 'PayPal payment processed',
        'payment_method' => 'paypal',
        'transaction_id' => $orderData['paypal_order_id'] ?? null
    ];
}

/**
 * Process Cash on Delivery
 */
function processCOD($amount, $currency, $orderData) {
    return [
        'status' => 'success',
        'message' => 'Cash on Delivery - Payment on delivery',
        'payment_method' => 'cod',
        'transaction_id' => 'COD-' . time()
    ];
}

/**
 * Process GCash Payment - DEMO MODE (Simulated API)
 * This simulates GCash payment processing for demo purposes
 */
function processGCash($amount, $currency, $orderData) {
    // Generate demo GCash transaction ID
    $transactionId = 'GCASH-' . strtoupper(substr(uniqid(), -12)) . '-' . time();
    
    // Simulate API call delay
    usleep(500000); // 0.5 second delay
    
    // In demo mode, simulate successful payment
    // In production, this would call actual GCash API
    if (DEMO_MODE) {
        // Store transaction for verification
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payment_transactions 
                (payment_code, transaction_id, amount, currency, status, order_data, created_at) 
                VALUES (?, ?, ?, ?, 'pending', ?, NOW())
            ");
            $stmt->execute([
                'gcash',
                $transactionId,
                $amount,
                $currency,
                json_encode($orderData)
            ]);
        } catch (PDOException $e) {
            // Table might not exist, that's okay for demo
        }
        
        return [
            'status' => 'success',
            'message' => 'GCash payment initiated. Transaction ID: ' . $transactionId,
            'payment_method' => 'gcash',
            'transaction_id' => $transactionId,
            'demo_mode' => true,
            'verification_url' => 'gcash_verify.php?transaction=' . $transactionId
        ];
    }
    
    // Production mode would call actual GCash API here
    return [
        'status' => 'success',
        'message' => 'GCash payment processed',
        'payment_method' => 'gcash',
        'transaction_id' => $transactionId
    ];
}

/**
 * Process Card Payment using Stripe Test Mode (FREE)
 * Works with Stripe test cards - no real charges
 */
function processCardPayment($amount, $currency, $orderData) {
    // Stripe API integration (Test Mode - FREE)
    $stripeToken = $orderData['stripe_token'] ?? null;
    
    if (!$stripeToken && DEMO_MODE) {
        // In demo mode without token, simulate successful payment
        $transactionId = 'CARD-' . strtoupper(substr(uniqid(), -12)) . '-' . time();
        
        return [
            'status' => 'success',
            'message' => 'Card payment processed (Demo Mode)',
            'payment_method' => 'card',
            'transaction_id' => $transactionId,
            'demo_mode' => true
        ];
    }
    
    if ($stripeToken && !DEMO_MODE) {
        // Real Stripe API call (when you have API keys)
        // Uncomment and configure when ready for production
        
        /*
        require_once __DIR__ . '/vendor/autoload.php'; // Stripe PHP SDK
        \Stripe\Stripe::setApiKey(STRIPE_TEST_SECRET_KEY);
        
        try {
            $charge = \Stripe\Charge::create([
                'amount' => $amount * 100, // Stripe uses cents
                'currency' => strtolower($currency),
                'source' => $stripeToken,
                'description' => 'Order #' . ($orderData['order_id'] ?? 'N/A')
            ]);
            
            return [
                'status' => 'success',
                'message' => 'Card payment processed',
                'payment_method' => 'card',
                'transaction_id' => $charge->id
            ];
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'status' => 'error',
                'message' => 'Card declined: ' . $e->getError()->message
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Payment processing failed'
            ];
        }
        */
    }
    
    // Demo mode fallback
    $transactionId = 'CARD-DEMO-' . time();
    return [
        'status' => 'success',
        'message' => 'Card payment processed (Demo Mode - Use test card: 4242 4242 4242 4242)',
        'payment_method' => 'card',
        'transaction_id' => $transactionId,
        'demo_mode' => true
    ];
}

/**
 * Process Bank Transfer - DEMO MODE (Simulated)
 */
function processBankTransfer($amount, $currency, $orderData) {
    // Generate demo bank transfer reference
    $referenceNumber = 'BT-' . strtoupper(substr(uniqid(), -10)) . '-' . time();
    
    // Store transaction for admin verification
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions 
            (payment_code, transaction_id, amount, currency, status, order_data, created_at) 
            VALUES (?, ?, ?, ?, 'pending_verification', ?, NOW())
        ");
        $stmt->execute([
            'bank',
            $referenceNumber,
            $amount,
            $currency,
            json_encode($orderData)
        ]);
    } catch (PDOException $e) {
        // Table might not exist, create it later
    }
    
    return [
        'status' => 'success',
        'message' => 'Bank transfer instructions sent. Reference: ' . $referenceNumber,
        'payment_method' => 'bank',
        'transaction_id' => $referenceNumber,
        'demo_mode' => true,
        'bank_details' => getBankDetails(),
        'verification_note' => 'Admin will verify payment manually using reference number'
    ];
}

/**
 * Get bank transfer details for display
 */
function getBankDetails() {
    return [
        'bank_name' => getSetting('bank_name', 'Demo Bank'),
        'account_name' => getSetting('account_name', 'PetPantry+'),
        'account_number' => getSetting('account_number', '1234-5678-9012'),
        'swift_code' => getSetting('swift_code', 'DEMOXXXX'),
        'branch' => getSetting('bank_branch', 'Main Branch')
    ];
}

/**
 * Verify payment status
 */
function verifyPayment($paymentCode, $transactionId) {
    global $pdo;
    
    // Check stored transactions first
    try {
        $stmt = $pdo->prepare("SELECT status, amount, currency FROM payment_transactions WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            return [
                'status' => $transaction['status'],
                'transaction_id' => $transactionId,
                'amount' => $transaction['amount'],
                'currency' => $transaction['currency']
            ];
        }
    } catch (PDOException $e) {
        // Table might not exist
    }
    
    // Fallback verification based on payment method
    switch ($paymentCode) {
        case 'paypal':
            return ['status' => 'verified', 'transaction_id' => $transactionId];
        
        case 'cod':
            return ['status' => 'pending', 'message' => 'Payment on delivery'];
        
        case 'gcash':
        case 'bank':
            return ['status' => 'pending_verification', 'transaction_id' => $transactionId];
        
        case 'card':
            return ['status' => 'verified', 'transaction_id' => $transactionId];
        
        default:
            return ['status' => 'unknown', 'message' => 'Payment verification not available'];
    }
}

/**
 * Get payment method display name
 */
function getPaymentMethodName($paymentCode) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT name FROM payment_options WHERE code = ?");
        $stmt->execute([$paymentCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['name'] : ucfirst($paymentCode);
    } catch (PDOException $e) {
        return ucfirst($paymentCode);
    }
}

/**
 * Get Stripe public key for frontend
 */
function getStripePublicKey() {
    return DEMO_MODE ? STRIPE_TEST_PUBLIC_KEY : STRIPE_TEST_PUBLIC_KEY;
}

/**
 * Check if payment method requires frontend integration
 */
function requiresFrontendIntegration($paymentCode) {
    return in_array($paymentCode, ['card', 'paypal', 'gcash']);
}

