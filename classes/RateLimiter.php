<?php
// This helps prevent the server from being overwhelmed by too many requests

class RateLimiter {
    private $db;
    private $max_requests_per_hour = 100;
    private $max_requests_per_minute = 10;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->createRateLimitTable();
    }
    
    // Set up the database table we'll use to track request rates
    private function createRateLimitTable() {
        $sql = "CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            request_count INT DEFAULT 1,
            window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_window (ip_address, window_start)
        )";
        
        $this->db->exec($sql);
    }
    
    // Check if this IP has made too many requests recently
    public function isAllowed($ip_address) {
        // Clean up old records to keep our database tidy
        $this->cleanOldRecords();
        
        // First, check if they've hit the hourly limit
        if (!$this->checkHourlyLimit($ip_address)) {
            return ['allowed' => false, 'error' => 'Hourly rate limit exceeded. Please try again later.'];
        }
        
        // Then, check the per-minute limit
        if (!$this->checkMinuteLimit($ip_address)) {
            return ['allowed' => false, 'error' => 'Too many requests per minute. Please slow down.'];
        }
        
        // Log this request for future rate limiting
        $this->recordRequest($ip_address);
        
        return ['allowed' => true];
    }
    
    // Make sure the user hasn't made too many requests this hour
    private function checkHourlyLimit($ip_address) {
        $sql = "SELECT COUNT(*) as request_count 
                FROM rate_limits 
                WHERE ip_address = ? 
                AND window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ip_address]);
        $result = $stmt->fetch();
        
        return $result['request_count'] < $this->max_requests_per_hour;
    }
    
    // Check if the user is making requests too quickly
    private function checkMinuteLimit($ip_address) {
        $sql = "SELECT COUNT(*) as request_count 
                FROM rate_limits 
                WHERE ip_address = ? 
                AND window_start > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ip_address]);
        $result = $stmt->fetch();
        
        return $result['request_count'] < $this->max_requests_per_minute;
    }
    
    /**
     * Keep track of this request for rate limiting
     */
    private function recordRequest($ip_address) {
        $sql = "INSERT INTO rate_limits (ip_address) VALUES (?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ip_address]);
    }
    
    /**
     * Remove old records to keep the database from growing too large
     */
    private function cleanOldRecords() {
        $sql = "DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 2 HOUR)";
        $this->db->exec($sql);
    }
    
    // Figure out the visitor's IP address
    public static function getClientIP() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs (from proxies)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
?>
