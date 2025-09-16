<?php
// Handle errors with care and keep a record of what went wrong

class ErrorHandler {
    
    // Log error details and show a friendly message when something goes wrong
    public static function handleError($error, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[{$timestamp}] Error: {$error}";
        
        if (!empty($context)) {
            $log_message .= " Context: " . json_encode($context);
        }
        
        // Create a log file if it doesn't exist
        $log_file = __DIR__ . '/../logs/error.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        error_log($log_message . PHP_EOL, 3, $log_file);
        
        // Translate technical errors into user-friendly messages
        if (strpos($error, 'database') !== false || strpos($error, 'connection') !== false) {
            return 'We had trouble connecting to the database. Please try again later.';
        }
        
        if (strpos($error, 'timeout') !== false) {
            return 'The request took too long to respond. The website might be slow right now.';
        }
        
        if (strpos($error, 'DNS') !== false || strpos($error, 'resolve') !== false) {
            return 'We couldn\'t resolve the website address. Please double-check the URL.';
        }
        
        return 'Something unexpected went wrong. Please try again.';
    }
    
    // Ensure URLs are safe and properly formatted
    public static function validateUrl($url) {
        if (empty($url)) {
            return ['valid' => false, 'error' => 'A URL is required'];
        }
        
        // Remove any whitespace
        $url = trim($url);
        
        // Add protocol if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }
        
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }
        
        // Check for valid scheme
        $parsed = parse_url($url);
        if (!in_array($parsed['scheme'], ['http', 'https'])) {
            return ['valid' => false, 'error' => 'Only HTTP and HTTPS URLs are supported'];
        }
        
        // Check for valid host
        if (empty($parsed['host'])) {
            return ['valid' => false, 'error' => 'Invalid hostname in URL'];
        }
        
        // Block localhost and private IPs for security
        $host = $parsed['host'];
        if (in_array($host, ['localhost', '127.0.0.1', '::1']) || 
            preg_match('/^192\.168\./', $host) || 
            preg_match('/^10\./', $host) || 
            preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $host)) {
            return ['valid' => false, 'error' => 'Local and private URLs are not allowed'];
        }
        
        return ['valid' => true, 'url' => $url];
    }
    
    // Validate HTML element name
    public static function validateElement($element) {
        if (empty($element)) {
            return ['valid' => false, 'error' => 'An element name is required'];
        }
        
        $element = trim(strtolower($element));
        
        // Check valid HTML element name pattern
        if (!preg_match('/^[a-z][a-z0-9]*$/', $element)) {
            return ['valid' => false, 'error' => 'Invalid HTML element name. Use only letters and numbers, starting with a letter.'];
        }
        
        // Check element name length
        if (strlen($element) > 20) {
            return ['valid' => false, 'error' => 'Element name is too long (max 20 characters)'];
        }
        
        return ['valid' => true, 'element' => $element];
    }
}
?>
