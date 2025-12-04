<?php
// settings_helper.php - Helper functions to retrieve platform settings

require_once 'database.php';

/**
 * Get a platform setting by key
 * Supports both old structure (setting_name) and new structure (setting_key)
 */
function getSetting($key, $default = null) {
    global $pdo;
    try {
        // Check which column exists first
        $checkStmt = $pdo->query("SHOW COLUMNS FROM platform_settings LIKE 'setting_key'");
        $hasNewColumn = $checkStmt->rowCount() > 0;
        
        if ($hasNewColumn) {
            // Use new column name
            $stmt = $pdo->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
        } else {
            // Use old column name
            $stmt = $pdo->prepare("SELECT setting_value FROM platform_settings WHERE setting_name = ? LIMIT 1");
            $stmt->execute([$key]);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        // If error, try with old column name as fallback
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM platform_settings WHERE setting_name = ? LIMIT 1");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['setting_value'] : $default;
        } catch (PDOException $e2) {
            return $default;
        }
    }
}

/**
 * Set a platform setting
 * Supports both old structure (setting_name) and new structure (setting_key)
 */
function setSetting($key, $value) {
    global $pdo;
    try {
        // Check which column exists
        $checkStmt = $pdo->query("SHOW COLUMNS FROM platform_settings LIKE 'setting_key'");
        $hasNewColumn = $checkStmt->rowCount() > 0;
        
        if ($hasNewColumn) {
            // Use new column name - check if setting exists first
            $check = $pdo->prepare("SELECT id FROM platform_settings WHERE setting_key = ? LIMIT 1");
            $check->execute([$key]);
            $exists = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($exists) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$value, $key]);
            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO platform_settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
        } else {
            // Use old column name - check if setting exists first
            $check = $pdo->prepare("SELECT id FROM platform_settings WHERE setting_name = ? LIMIT 1");
            $check->execute([$key]);
            $exists = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($exists) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_name = ?");
                $stmt->execute([$value, $key]);
            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO platform_settings (setting_name, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
        }
        return true;
    } catch (PDOException $e) {
        // Fallback to old column name with UPDATE/INSERT
        try {
            $check = $pdo->prepare("SELECT id FROM platform_settings WHERE setting_name = ? LIMIT 1");
            $check->execute([$key]);
            $exists = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($exists) {
                $stmt = $pdo->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_name = ?");
                $stmt->execute([$value, $key]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO platform_settings (setting_name, setting_value) VALUES (?, ?)");
                $stmt->execute([$key, $value]);
            }
            return true;
        } catch (PDOException $e2) {
            error_log("setSetting error: " . $e2->getMessage());
            return false;
        }
    }
}

/**
 * Get all active payment options
 */
function getActivePaymentOptions() {
    global $pdo;
    
    // Check if $pdo is available
    if (!isset($pdo) || !$pdo) {
        return [];
    }
    
    try {
        // Check if table exists first
        $checkTable = $pdo->query("SHOW TABLES LIKE 'payment_options'");
        if ($checkTable->rowCount() == 0) {
            return [];
        }
        $stmt = $pdo->query("SELECT * FROM payment_options WHERE is_active = 1 ORDER BY display_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getActivePaymentOptions PDO error: " . $e->getMessage());
        return [];
    } catch (Exception $e) {
        error_log("getActivePaymentOptions error: " . $e->getMessage());
        return [];
    } catch (Error $e) {
        error_log("getActivePaymentOptions fatal error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all payment options (including inactive)
 */
function getAllPaymentOptions() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM payment_options ORDER BY display_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get all active shipping options
 */
function getActiveShippingOptions() {
    global $pdo;
    
    // Check if $pdo is available
    if (!isset($pdo) || !$pdo) {
        return [];
    }
    
    try {
        // Check if table exists first
        $checkTable = $pdo->query("SHOW TABLES LIKE 'shipping_options'");
        if ($checkTable->rowCount() == 0) {
            return [];
        }
        $stmt = $pdo->query("SELECT * FROM shipping_options WHERE is_active = 1 ORDER BY display_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("getActiveShippingOptions PDO error: " . $e->getMessage());
        return [];
    } catch (Exception $e) {
        error_log("getActiveShippingOptions error: " . $e->getMessage());
        return [];
    } catch (Error $e) {
        error_log("getActiveShippingOptions fatal error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all shipping options (including inactive)
 */
function getAllShippingOptions() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM shipping_options ORDER BY display_order ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get default currency
 */
function getDefaultCurrency() {
    return getSetting('default_currency', 'PHP');
}

/**
 * Get currency symbol
 */
function getCurrencySymbol() {
    global $pdo;
    try {
        $symbol = getSetting('currency_symbol', null);
        
        // If symbol is empty or still default, get from currency code
        if (empty($symbol)) {
            $currency = getDefaultCurrency();
            // Map currency codes to symbols
            $currencyMap = [
                'USD' => '$',
                'PHP' => '₱',
                'EUR' => '€',
                'GBP' => '£',
                'JPY' => '¥',
                'CNY' => '¥',
                'AUD' => 'A$',
                'CAD' => 'C$'
            ];
            $symbol = $currencyMap[$currency] ?? '₱';
        }
        return $symbol ?: '₱'; // Final fallback
    } catch (Exception $e) {
        return '₱'; // Default fallback
    }
}

/**
 * Format currency amount (with automatic conversion)
 */
function formatCurrency($amount, $symbol = null, $convert = true) {
    try {
        if ($symbol === null) {
            if (function_exists('getCurrencySymbol')) {
                $symbol = getCurrencySymbol();
            } else {
                $symbol = '₱';
            }
        }
        
        // Convert amount if needed
        if ($convert && function_exists('convertCurrency') && function_exists('getSetting') && function_exists('getDefaultCurrency')) {
            try {
                $baseCurrency = getSetting('base_currency', 'PHP');
                $currentCurrency = getDefaultCurrency();
                
                // Only convert if currency is different from base
                if ($baseCurrency !== $currentCurrency && $amount > 0) {
                    $amount = convertCurrency($amount, $baseCurrency, $currentCurrency);
                }
            } catch (Exception $e) {
                // If conversion fails, use original amount
                error_log("Currency conversion failed: " . $e->getMessage());
            }
        }
        
        return $symbol . number_format((float)$amount, 2);
    } catch (Exception $e) {
        error_log("formatCurrency error: " . $e->getMessage());
        return '₱' . number_format((float)$amount, 2);
    } catch (Error $e) {
        error_log("formatCurrency fatal error: " . $e->getMessage());
        return '₱' . number_format((float)$amount, 2);
    }
}

/**
 * Get converted price for display (doesn't format, just converts)
 */
function getConvertedPrice($amount) {
    $baseCurrency = getSetting('base_currency', 'PHP');
    $currentCurrency = getDefaultCurrency();
    
    if ($baseCurrency !== $currentCurrency && $amount > 0) {
        return convertCurrency($amount, $baseCurrency, $currentCurrency);
    }
    
    return $amount;
}

/**
 * Get exchange rate from cache or API
 */
function getExchangeRate($from, $to) {
    global $pdo;
    
    // If same currency, return 1
    if ($from === $to) {
        return 1.0;
    }
    
    // Check cache first (rates cached for 1 hour)
    try {
        $stmt = $pdo->prepare("SELECT rate FROM currency_rates 
                              WHERE base_currency = ? AND target_currency = ? 
                              AND cached_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$from, $to]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return (float)$result['rate'];
        }
    } catch (PDOException $e) {
        // Continue to API fetch
    }
    
    // Fetch from API
    $rate = fetchExchangeRateFromAPI($from, $to);
    
    // Cache the rate
    if ($rate) {
        try {
            $stmt = $pdo->prepare("INSERT INTO currency_rates (base_currency, target_currency, rate) 
                                  VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE rate = ?, cached_at = NOW()");
            $stmt->execute([$from, $to, $rate, $rate]);
        } catch (PDOException $e) {
            // Ignore cache errors
        }
    }
    
    return $rate ?: 1.0;
}

/**
 * Fetch exchange rate from free API (exchangerate-api.io)
 */
function fetchExchangeRateFromAPI($from, $to) {
    // Using exchangerate-api.io (free tier: 1500 requests/month)
    $apiKey = 'a1b2c3d4e5f6g7h8i9j0'; // Free tier doesn't need API key for basic usage
    $url = "https://api.exchangerate-api.com/v4/latest/$from";
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (isset($data['rates'][$to])) {
                return (float)$data['rates'][$to];
            }
        }
    } catch (Exception $e) {
        // Fallback to 1:1 if API fails
    }
    
    return 1.0; // Default fallback
}

/**
 * Convert amount between currencies
 */
function convertCurrency($amount, $from, $to) {
    if ($from === $to) {
        return $amount;
    }
    
    $rate = getExchangeRate($from, $to);
    return $amount * $rate;
}

/**
 * Get default shipping option
 */
if (!function_exists('getDefaultShipping')) {
    function getDefaultShipping() {
        global $pdo;
        
        if (!isset($pdo) || !$pdo) {
            return null;
        }
        
        try {
            $stmt = $pdo->query("SELECT * FROM shipping_options WHERE is_active = 1 ORDER BY display_order ASC LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}

/**
 * Calculate shipping fee based on order total
 */
if (!function_exists('calculateShippingFee')) {
    function calculateShippingFee($orderTotal) {
        global $pdo;
        
        $freeShippingThreshold = (float)getSetting('free_shipping_threshold', 0);
        
        // Check if free shipping applies
        if ($freeShippingThreshold > 0 && $orderTotal >= $freeShippingThreshold) {
            try {
                if (isset($pdo) && $pdo) {
                    $freeShipping = $pdo->query("SELECT * FROM shipping_options WHERE code = 'free' AND is_active = 1 LIMIT 1")
                                       ->fetch(PDO::FETCH_ASSOC);
                    if ($freeShipping) {
                        return (float)$freeShipping['fee'];
                    }
                }
            } catch (Exception $e) {
                // Fallback
            }
        }
        
        // Get default standard shipping
        $default = getDefaultShipping();
        return $default ? (float)$default['fee'] : 50.00;
    }
}

