<?php
/**
 * Helper functions for WC Variation Table
 */

if (!function_exists('convert_to_bytes')) {
    /**
     * Convert storage size string to bytes
     *
     * @param string $size Storage size string (e.g., "256GB", "1TB")
     * @return float Size in bytes
     */
    function convert_to_bytes($size) {
        $size = trim($size);
        $last = strtolower($size[strlen($size)-1]);
        $value = floatval($size);
        
        switch($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
}

if (!function_exists('is_storage_attribute')) {
    /**
     * Check if the attribute is a storage size attribute
     *
     * @param array $options Attribute options
     * @return bool Whether the attribute is a storage size
     */
    function is_storage_attribute($options) {
        if (empty($options)) {
            return false;
        }
        
        // Check if all values end with GB, TB, MB, or KB
        foreach ($options as $option) {
            if (!preg_match('/^[\d.]+(GB|TB|MB|KB)$/i', $option)) {
                return false;
            }
        }
        
        return true;
    }
} 