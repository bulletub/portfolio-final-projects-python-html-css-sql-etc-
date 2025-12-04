<?php
// currency_global.php - Global currency settings
// Include this file at the top of pages to get currency settings

if (!function_exists('getCurrencySettings')) {
    require_once __DIR__ . '/settings_helper.php';
    
    // Get currency settings once
    $GLOBALS['CURRENCY_SYMBOL'] = getCurrencySymbol();
    $GLOBALS['CURRENCY_CODE'] = getDefaultCurrency();
    
    /**
     * Format currency for display
     */
    function formatCurrencyGlobal($amount) {
        $symbol = $GLOBALS['CURRENCY_SYMBOL'] ?? '₱';
        return $symbol . number_format($amount, 2);
    }
    
    /**
     * Get currency symbol
     */
    function getCurrencySymbolGlobal() {
        return $GLOBALS['CURRENCY_SYMBOL'] ?? '₱';
    }
    
    /**
     * Get currency code
     */
    function getCurrencyCodeGlobal() {
        return $GLOBALS['CURRENCY_CODE'] ?? 'PHP';
    }
}

