<?php
// shipping_api_helper.php - Free Shipping API Integration
// Uses free APIs for shipping calculations

require_once 'settings_helper.php';

/**
 * Calculate shipping fee using free shipping APIs
 * Falls back to database shipping options if API fails
 */
function calculateShippingFeeWithAPI($fromAddress = null, $toAddress = null, $weight = 0, $distance = null, $shippingCode = 'standard') {
    global $pdo;
    
    // First, check if shipping code is provided and get its fee
    if ($shippingCode) {
        try {
            $stmt = $pdo->prepare("SELECT fee, estimated_days FROM shipping_options WHERE code = ? AND is_active = 1");
            $stmt->execute([$shippingCode]);
            $shipping = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($shipping) {
                $baseFee = (float)$shipping['fee'];
                
                // If addresses provided, try to enhance with distance calculation
                if ($fromAddress && $toAddress && $baseFee > 0) {
                    $calculatedFee = calculateWithDistance($fromAddress, $toAddress, $weight);
                    if ($calculatedFee !== null && $calculatedFee > $baseFee) {
                        return $calculatedFee; // Use higher fee if calculated is more
                    }
                }
                
                return $baseFee;
            }
        } catch (PDOException $e) {
            // Continue to fallback
        }
    }
    
    // Get default shipping option
    $defaultShipping = getDefaultShipping();
    $baseFee = $defaultShipping ? (float)$defaultShipping['fee'] : 50.00;
    
    // If addresses provided, try to get more accurate fee using free API
    if ($fromAddress && $toAddress) {
        // Option 1: Use distance calculation (free, no API key needed)
        $calculatedFee = calculateWithDistance($fromAddress, $toAddress, $weight);
        if ($calculatedFee !== null) {
            return max($calculatedFee, $baseFee); // Use higher of calculated or base
        }
        
        // Option 2: Use OpenRouteService API (free tier: 2000 requests/day)
        $calculatedFee = calculateWithOpenRouteService($fromAddress, $toAddress, $weight);
        if ($calculatedFee !== null) {
            return $calculatedFee;
        }
    }
    
    // Fallback to base fee
    return $baseFee;
}

/**
 * Get shipping fee from database (simplified version)
 * Renamed to avoid conflict with settings_helper.php
 */
if (!function_exists('calculateShippingFeeAdvanced')) {
    function calculateShippingFeeAdvanced($subtotal = 0, $shippingCode = 'standard') {
        global $pdo;
        
        // Check free shipping threshold
        $freeShippingThreshold = (float)getSetting('free_shipping_threshold', 0);
        if ($freeShippingThreshold > 0 && $subtotal >= $freeShippingThreshold) {
            return 0; // Free shipping
        }
        
        // Get shipping option fee
        try {
            if (isset($pdo) && $pdo) {
                $stmt = $pdo->prepare("SELECT fee FROM shipping_options WHERE code = ? AND is_active = 1");
                $stmt->execute([$shippingCode]);
                $shipping = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($shipping) {
                    return (float)$shipping['fee'];
                }
            }
        } catch (PDOException $e) {
            // Fallback
        }
        
        // Default shipping fee
        return 50.00;
    }
}

/**
 * Get default shipping option
 * Note: This function already exists in settings_helper.php, so we check before declaring
 */
if (!function_exists('getDefaultShipping')) {
    function getDefaultShipping() {
        global $pdo;
        
        if (!isset($pdo) || !$pdo) {
            return null;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM shipping_options WHERE is_active = 1 ORDER BY display_order ASC LIMIT 1");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
}

/**
 * Calculate shipping using OpenRouteService API (free tier)
 * Note: Requires API key but free tier is generous
 */
function calculateWithOpenRouteService($fromAddress, $toAddress, $weight = 0) {
    // OpenRouteService requires API key for routing
    // For now, return null to use fallback
    // You can integrate this if you have an API key
    return null;
}

/**
 * Calculate shipping using distance estimation (simple method)
 */
function calculateWithDistance($fromAddress, $toAddress, $weight = 0) {
    try {
        // Get coordinates using Nominatim (OpenStreetMap - free, no API key needed)
        $fromCoords = getCoordinates($fromAddress);
        $toCoords = getCoordinates($toAddress);
        
        if ($fromCoords && $toCoords) {
            // Calculate distance using Haversine formula
            $distance = calculateHaversineDistance(
                $fromCoords['lat'], $fromCoords['lon'],
                $toCoords['lat'], $toCoords['lon']
            );
            
            // Base fee + (distance * rate per km)
            $baseFee = 50.00;
            $ratePerKm = 1.00; // $1 per km (adjustable)
            $fee = $baseFee + ($distance * $ratePerKm);
            
            // Add weight factor
            if ($weight > 0) {
                $fee += ($weight * 5); // $5 per kg
            }
            
            return max($fee, 50.00); // Minimum $50
        }
    } catch (Exception $e) {
        // Log error silently
    }
    
    return null;
}

/**
 * Get coordinates from address using Nominatim (OpenStreetMap - free, no API key)
 */
function getCoordinates($address) {
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address);
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PetPantry+ Shipping Calculator');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                return [
                    'lat' => (float)$data[0]['lat'],
                    'lon' => (float)$data[0]['lon']
                ];
            }
        }
    } catch (Exception $e) {
        // Log error silently
    }
    
    return null;
}

/**
 * Calculate distance between two coordinates using Haversine formula
 */
function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Earth radius in kilometers
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $earthRadius * $c;
    
    return $distance; // Distance in kilometers
}

/**
 * Get estimated delivery days based on shipping method
 */
function getEstimatedDeliveryDays($shippingCode, $distance = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT estimated_days FROM shipping_options WHERE code = ? AND is_active = 1");
        $stmt->execute([$shippingCode]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['estimated_days']) {
            return (int)$result['estimated_days'];
        }
    } catch (PDOException $e) {
        // Fallback
    }
    
    // Default estimates
    $defaults = [
        'standard' => 3,
        'express' => 1,
        'free' => 5
    ];
    
    return $defaults[$shippingCode] ?? 3;
}

