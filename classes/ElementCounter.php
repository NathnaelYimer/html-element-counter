<?php
/**
 * ElementCounter class
 * 
 * Handles the core functionality of counting HTML elements on web pages.
 * Provides methods for URL validation, content fetching, element counting,
 * and result caching. Uses PDO for database operations and includes
 * comprehensive error handling and input validation.
 */

require_once __DIR__ . '/../config/database.php';

class ElementCounter {
    private $db;
    private $cache_duration = 300; // 5 minutes in seconds
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Process a URL and element count request
     */
    public function processRequest($url, $element) {
        // Validate inputs
        $validation = $this->validateInputs($url, $element);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }
        
        $url = $validation['url'];
        $element = $validation['element'];
        
        // Parse URL components
        $parsed_url = parse_url($url);
        $domain = $parsed_url['host'];
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
        if (isset($parsed_url['query'])) {
            $path .= '?' . $parsed_url['query'];
        }
        
        // Check cache first
        $cached_result = $this->getCachedResult($url, $element);
        if ($cached_result) {
            $stats = $this->getStatistics($domain, $element);
            return [
                'success' => true,
                'cached' => true,
                'result' => $cached_result,
                'statistics' => $stats
            ];
        }
        
        // Fetch and process the URL
        $fetch_result = $this->fetchUrl($url);
        if (!$fetch_result['success']) {
            return ['success' => false, 'error' => $fetch_result['error']];
        }
        
        // Count elements in HTML
        $element_count = $this->countElements($fetch_result['content'], $element);
        
        // Store result in database
        $store_result = $this->storeResult(
            $domain, 
            $path, 
            $url, 
            $element, 
            $element_count, 
            $fetch_result['fetch_time_ms'], 
            $fetch_result['size_bytes'], 
            $fetch_result['error'] ?? null
        );
        
        if (isset($store_result['error'])) {
            return ['success' => false, 'error' => $store_result['error']];
        }
        
        // Get statistics
        $stats = $this->getStatistics($domain, $element);
        
        return [
            'success' => true,
            'cached' => false,
            'result' => [
                'url' => $url,
                'element' => $element,
                'count' => $element_count,
                'fetch_time' => $fetch_result['fetch_time_ms'],
                'timestamp' => date('d/m/Y H:i')
            ],
            'statistics' => $stats
        ];
    }
    
    /**
     * Validate URL and element inputs
     */
    private function validateInputs($url, $element) {
        // Validate URL
        if (empty($url)) {
            return ['valid' => false, 'error' => 'URL is required'];
        }
        
        // Add protocol if missing
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }
        
        // Validate element
        if (empty($element)) {
            return ['valid' => false, 'error' => 'Element name is required'];
        }
        
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $element)) {
            return ['valid' => false, 'error' => 'Invalid HTML element name'];
        }
        
        return ['valid' => true, 'url' => $url, 'element' => strtolower($element)];
    }
    
    /**
     * Check if we have a cached result within 5 minutes
     */
    private function getCachedResult($url, $element) {
        $sql = "SELECT r.element_count, r.fetch_time_ms, r.created_at 
                FROM requests r
                JOIN urls u ON r.url_id = u.id
                JOIN elements e ON r.element_id = e.id
                WHERE u.full_url = ? AND e.name = ? 
                AND r.created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
                AND r.error_message IS NULL
                ORDER BY r.created_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$url, $element, $this->cache_duration]);
        $result = $stmt->fetch();
        
        if ($result) {
            return [
                'url' => $url,
                'element' => $element,
                'count' => $result['element_count'],
                'fetch_time' => $result['fetch_time_ms'],
                'timestamp' => date('d/m/Y H:i', strtotime($result['created_at']))
            ];
        }
        
        return null;
    }
    
    /**
     * Fetch URL content with cURL for better reliability and error handling
     */
    private function fetchUrl($url) {
        $start_time = microtime(true);
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options with enhanced browser-like headers
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => 'gzip, deflate, br',
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'Accept-Language: en-US,en;q=0.9',
                'Accept-Encoding: gzip, deflate, br',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
                'Cache-Control: max-age=0',
                'Sec-Fetch-Dest: document',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-Site: none',
                'Sec-Fetch-User: ?1',
                'DNT: 1',
                'Pragma: no-cache',
                'Referer: https://www.google.com/'
            ],
            CURLOPT_COOKIEJAR => tempnam(sys_get_temp_dir(), 'cookies_'),
            CURLOPT_REFERER => 'https://www.google.com/'
        ];
        
        // For debugging: Uncomment to see request headers
        // $options[CURLOPT_HEADER] = true;
        // $options[CURLINFO_HEADER_OUT] = true;
        
        curl_setopt_array($ch, $options);
        
        // Execute the request
        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $fetch_time = round((microtime(true) - $start_time) * 1000);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        
        // Debug: Log the first 500 characters of the response
        error_log('Raw response (first 500 chars): ' . substr($html, 0, 500));
        
        // Close cURL resource
        curl_close($ch);
        
        // Check for cURL errors
        if ($errno) {
            $error_message = strtolower($error);
            
            if (strpos($error_message, 'could not resolve host') !== false || 
                strpos($error_message, 'name or service not known') !== false) {
                return ['success' => false, 'error' => 'Unable to resolve domain name. Please check the URL.'];
            }
            
            if (strpos($error_message, 'connection refused') !== false) {
                return ['success' => false, 'error' => 'Connection refused by the server. The website may be down.'];
            }
            
            if (strpos($error_message, 'connection timed out') !== false || 
                strpos($error_message, 'timed out') !== false) {
                return ['success' => false, 'error' => 'Request timed out. The website is taking too long to respond.'];
            }
            
            if (strpos($error_message, 'ssl') !== false || strpos($error_message, 'certificate') !== false) {
                return ['success' => false, 'error' => 'SSL certificate error. The website may have security issues.'];
            }
            
            if (strpos($error_message, '404') !== false) {
                return ['success' => false, 'error' => 'Page not found (404). Please check the URL.'];
            }
            
            if (strpos($error_message, '403') !== false) {
                return ['success' => false, 'error' => 'Access forbidden (403). The website blocked our request.'];
            }
            
            if (strpos($error_message, '500') !== false) {
                return ['success' => false, 'error' => 'Server error (500). The website is experiencing technical difficulties.'];
            }
            
            return ['success' => false, 'error' => 'Failed to fetch URL: ' . $this->sanitizeErrorMessage($error_message)];
        }
        
        // Check HTTP status code
        if ($http_code >= 400) {
            switch ($http_code) {
                case 400:
                    return ['success' => false, 'error' => 'Bad request (400). The server cannot process the request.'];
                case 401:
                    return ['success' => false, 'error' => 'Unauthorized (401). Authentication is required.'];
                case 403:
                    return ['success' => false, 'error' => 'Access forbidden (403). The website blocked our request.'];
                case 404:
                    return ['success' => false, 'error' => 'Page not found (404). Please check the URL.'];
                case 500:
                    return ['success' => false, 'error' => 'Server error (500). The website is experiencing technical difficulties.'];
                case 502:
                    return ['success' => false, 'error' => 'Bad gateway (502). The website server is having issues.'];
                case 503:
                    return ['success' => false, 'error' => 'Service unavailable (503). The website is temporarily down.'];
                case 504:
                    return ['success' => false, 'error' => 'Gateway timeout (504). The website server is taking too long to respond.'];
                default:
                    return ['success' => false, 'error' => "HTTP error ({$http_code}). Unable to fetch the page."];
            }
        }
        
        if (empty($html)) {
            return ['success' => false, 'error' => 'The website returned empty content.'];
        }
        
        // Check if content is actually HTML
        $html_lower = strtolower($html);
        
        // If we got a very small response, it might be an error page or CAPTCHA
        if (strlen($html) < 200) {
            return ['success' => false, 'error' => 'Received suspiciously small response. The website might be blocking automated requests.'];
        }
        
        // Debug: Log first 500 characters of response
        error_log('First 500 chars of response: ' . substr($html, 0, 500));
        
        // More lenient check for HTML content
        if (stripos($html_lower, '<html') === false && 
            stripos($html_lower, '<!doctype') === false &&
            stripos($html_lower, '<p') === false) {
            return ['success' => false, 'error' => 'The URL does not contain valid HTML content.'];
        }
        
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (stripos($header, 'content-type:') === 0) {
                    $content_type = strtolower(trim(substr($header, 13)));
                    if (strpos($content_type, 'application/json') !== false ||
                        strpos($content_type, 'application/xml') !== false ||
                        strpos($content_type, 'text/plain') !== false ||
                        strpos($content_type, 'image/') !== false ||
                        strpos($content_type, 'application/pdf') !== false) {
                        return ['success' => false, 'error' => 'The URL does not serve HTML content.'];
                    }
                    return ['success' => false, 'error' => 'The URL does not contain valid HTML content.'];
                }
            }
        }
        
        return [
            'success' => true,
            'content' => $html,
            'fetch_time_ms' => $fetch_time,
            'size_bytes' => strlen($html),
            'http_code' => $http_code
        ];
    }
    
    /**
     * New method to sanitize error messages for user display
     */
    private function sanitizeErrorMessage($message) {
        // Remove file paths and sensitive information
        $message = preg_replace('/in \/[^\s]+/', '', $message);
        $message = preg_replace('/on line \d+/', '', $message);
        
        // Limit message length
        if (strlen($message) > 200) {
            $message = substr($message, 0, 200) . '...';
        }
        
        return trim($message);
    }
    
    /**
     * Count HTML elements with enhanced error handling
     */
    private function countElements($html, $element) {
        try {
            $dom = new DOMDocument();
            
            // Suppress warnings for malformed HTML
            $old_setting = libxml_use_internal_errors(true);
            libxml_clear_errors();
            
            // Try to load HTML with different approaches
            $loaded = false;
            
            // First attempt: Load as HTML5
            if (@$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
                $loaded = true;
            }
            // Second attempt: Load as standard HTML
            elseif (@$dom->loadHTML($html)) {
                $loaded = true;
            }
            // Third attempt: Try with UTF-8 BOM
            elseif (@$dom->loadHTML("\xEF\xBB\xBF" . $html)) {
                $loaded = true;
            }
            
            if (!$loaded) {
                libxml_use_internal_errors($old_setting);
                return $this->countElementsWithRegex($html, $element);
            }
            
            // Clear any libxml errors
            libxml_clear_errors();
            libxml_use_internal_errors($old_setting);
            
            // Count elements using DOM
            $elements = $dom->getElementsByTagName($element);
            return $elements->length;
            
        } catch (Exception $e) {
            return $this->countElementsWithRegex($html, $element);
        }
    }
    
    /**
     * New fallback method using regex for element counting
     */
    private function countElementsWithRegex($html, $element) {
        // Use regex as fallback (less accurate but more robust)
        $pattern = '/<' . preg_quote($element, '/') . '(?:\s[^>]*)?>/i';
        preg_match_all($pattern, $html, $matches);
        return count($matches[0]);
    }
    
    /**
     * Store result with enhanced error logging
     */
    private function storeResult($domain, $path, $full_url, $element, $count, $fetch_time = 0, $response_size = 0, $error = null) {
        try {
            $this->db->beginTransaction();
            
            // Ensure required values are set
            $fetch_time = $fetch_time ?: 0;
            $response_size = $response_size ?: 0;
            $count = $count ?: 0;
            
            // Get or create domain
            $domain_id = $this->getOrCreateDomain($domain);
            
            // Get or create URL
            $url_id = $this->getOrCreateUrl($domain_id, $path, $full_url);
            
            // Get or create element
            $element_id = $this->getOrCreateElement($element);
            
            $sql = "INSERT INTO requests (domain_id, url_id, element_id, element_count, fetch_time_ms, response_size, error_message) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $domain_id, 
                $url_id, 
                $element_id, 
                (int)$count, 
                (int)$fetch_time, 
                (int)$response_size, 
                $error
            ]);
            
            if (!$result) {
                throw new Exception('Failed to execute database query');
            }
            
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            ErrorHandler::handleError('Database error in storeResult: ' . $e->getMessage(), [
                'domain' => $domain,
                'url' => $full_url,
                'element' => $element
            ]);
            throw new Exception('Failed to store result in database');
        }
    }
    
    /**
     * Get or create domain record
     */
    private function getOrCreateDomain($domain) {
        $sql = "SELECT id FROM domains WHERE name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$domain]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['id'];
        }
        
        $sql = "INSERT INTO domains (name) VALUES (?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$domain]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get or create URL record
     */
    private function getOrCreateUrl($domain_id, $path, $full_url) {
        $sql = "SELECT id FROM urls WHERE domain_id = ? AND full_url = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$domain_id, $full_url]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['id'];
        }
        
        $sql = "INSERT INTO urls (domain_id, path, full_url) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$domain_id, $path, $full_url]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get or create element record
     */
    private function getOrCreateElement($element) {
        $sql = "SELECT id FROM elements WHERE name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$element]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['id'];
        }
        
        $sql = "INSERT INTO elements (name) VALUES (?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$element]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get statistics for display
     */
    private function getStatistics($domain, $element) {
        $stats = [];
        
        // 1. Count unique URLs from this domain
        $sql = "SELECT COUNT(DISTINCT u.id) as url_count 
                FROM urls u 
                JOIN domains d ON u.domain_id = d.id 
                WHERE d.name = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$domain]);
        $stats['domain_urls'] = $stmt->fetch()['url_count'];
        
        // 2. Average fetch time from domain in last 24 hours
        $sql = "SELECT AVG(r.fetch_time_ms) as avg_time 
                FROM requests r 
                JOIN domains d ON r.domain_id = d.id 
                WHERE d.name = ? AND r.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND r.error_message IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$domain]);
        $result = $stmt->fetch();
        $stats['domain_avg_time'] = $result['avg_time'] ? round($result['avg_time']) : 0;
        
        // 3. Total count of this element from this domain
        $sql = "SELECT SUM(r.element_count) as total_count 
                FROM requests r 
                JOIN domains d ON r.domain_id = d.id 
                JOIN elements e ON r.element_id = e.id 
                WHERE d.name = ? AND e.name = ?
                AND r.error_message IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$domain, $element]);
        $result = $stmt->fetch();
        $stats['domain_element_total'] = $result['total_count'] ?? 0;
        
        // 4. Total count of this element from ALL requests
        $sql = "SELECT SUM(r.element_count) as total_count 
                FROM requests r 
                JOIN elements e ON r.element_id = e.id 
                WHERE e.name = ?
                AND r.error_message IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$element]);
        $result = $stmt->fetch();
        $stats['global_element_total'] = $result['total_count'] ?? 0;
        
        return $stats;
    }
}
?>
