<?php
// This is where the magic happens - our main API endpoint
// It handles all the counting requests and keeps things secure

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// We only accept POST requests - no sneaky GET requests allowed!
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Load up all the tools we'll need
    require_once __DIR__ . '/../classes/ElementCounter.php';
    require_once __DIR__ . '/../classes/ErrorHandler.php';
    require_once __DIR__ . '/../classes/RateLimiter.php';
    require_once __DIR__ . '/../classes/SecurityValidator.php'; // Added security validator
    
    // Make sure this user isn't making too many requests
    $rateLimiter = new RateLimiter();
    $clientIP = RateLimiter::getClientIP();
    $rateCheck = $rateLimiter->isAllowed($clientIP);
    
    if (!$rateCheck['allowed']) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => $rateCheck['error']]);
        exit;
    }
    
    // Get the data that was sent to us
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }
    
    $url = SecurityValidator::sanitizeInput($input['url'] ?? '');
    $element = SecurityValidator::sanitizeInput($input['element'] ?? '');
    $noCache = !empty($input['nocache']);
    
    // Validate URL
    $urlValidation = ErrorHandler::validateUrl($url);
    if (!$urlValidation['valid']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $urlValidation['error']]);
        exit;
    }
    
    $securityCheck = SecurityValidator::validateUrlSecurity($urlValidation['url']);
    if (!$securityCheck['valid']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $securityCheck['error']]);
        exit;
    }
    
    // Validate element
    $elementValidation = ErrorHandler::validateElement($element);
    if (!$elementValidation['valid']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $elementValidation['error']]);
        exit;
    }
    
    $elementSecurityCheck = SecurityValidator::validateElementSecurity($elementValidation['element']);
    if (!$elementSecurityCheck['valid']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $elementSecurityCheck['error']]);
        exit;
    }
    
    // If we made it this far, let's count some elements!
    $counter = new ElementCounter();
    
    // Bypass the cache if the user wants fresh results
    if ($noCache) {
        $counter->clearCache($urlValidation['url'], $elementValidation['element']);
    }
    
    $result = $counter->processRequest($urlValidation['url'], $elementValidation['element']);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
    
} catch (PDOException $e) {
    $error_msg = ErrorHandler::handleError('Database error: ' . $e->getMessage(), [
        'ip' => $clientIP ?? 'unknown',
        'input' => $input ?? null
    ]);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $error_msg]);
    
} catch (Exception $e) {
    $error_msg = ErrorHandler::handleError('General error: ' . $e->getMessage(), [
        'ip' => $clientIP ?? 'unknown',
        'input' => $input ?? null
    ]);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $error_msg]);
}
?>
