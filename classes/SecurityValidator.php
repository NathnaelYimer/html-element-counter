<?php
// This file keeps our app safe from bad requests and suspicious URLs
// Added some extra checks to make sure everything stays secure

class SecurityValidator {
    
    private static $blocked_domains = [
        'localhost',
        '127.0.0.1',
        '::1',
        '0.0.0.0'
    ];
    
    private static $blocked_extensions = [
        '.exe', '.bat', '.cmd', '.com', '.scr', '.pif', '.vbs', '.js', '.jar'
    ];
    
    // Let's make sure this URL is safe before we use it
    public static function validateUrlSecurity($url) {
        $parsed = parse_url($url);
        
        if (!$parsed) {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }
        
        // Make sure this isn't a local or private address
        $host = strtolower($parsed['host'] ?? '');
        if (in_array($host, self::$blocked_domains)) {
            return ['valid' => false, 'error' => 'Sorry, we can\'t access local addresses. Try a public website.'];
        }
        
        // Check for private IP ranges
        if (self::isPrivateIP($host)) {
            return ['valid' => false, 'error' => 'Private networks are off-limits for security.'];
        }
        
        // Check for suspicious file extensions
        $path = $parsed['path'] ?? '';
        foreach (self::$blocked_extensions as $ext) {
            if (stripos($path, $ext) !== false) {
                return ['valid' => false, 'error' => 'Whoops! That file type isn\'t allowed.'];
            }
        }
        
        // Watch out for sneaky data or javascript URLs
        if (stripos($url, 'data:') === 0 || stripos($url, 'javascript:') === 0) {
            return ['valid' => false, 'error' => 'That URL looks a bit fishy. Try a regular website address.'];
        }
        
        return ['valid' => true];
    }
    
    // Check if IP is in private range
    private static function isPrivateIP($ip) {
        // Convert hostname to IP if needed
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $resolved = gethostbyname($ip);
            if ($resolved === $ip) {
                return false; // Couldn't resolve
            }
            $ip = $resolved;
        }
        
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
    
    // Clean up whatever the user typed in - better safe than sorry
    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    // Double-check element names to prevent any funny business
    public static function validateElementSecurity($element) {
        // Block any elements that could be used for XSS
        if (stripos($element, 'script') !== false || 
            stripos($element, 'iframe') !== false ||
            stripos($element, 'object') !== false ||
            stripos($element, 'embed') !== false) {
            return ['valid' => false, 'error' => 'Element name contains potentially dangerous content'];
        }
        
        // Keep an eye out for SQL injection attempts
        $sql_patterns = ['union', 'select', 'insert', 'update', 'delete', 'drop', '--', ';'];
        $element_lower = strtolower($element);
        
        foreach ($sql_patterns as $pattern) {
            if (strpos($element_lower, $pattern) !== false) {
                return ['valid' => false, 'error' => 'Element name contains invalid characters'];
            }
        }
        
        return ['valid' => true];
    }
}
?>
