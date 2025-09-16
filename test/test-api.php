<?php
/**
 * Simple API testing script
 * Run this to test the API endpoints
 */

// Test configuration
$base_url = 'http://localhost/html-element-counter';
$api_url = $base_url . '/api/process.php';

// Test cases
$test_cases = [
    [
        'name' => 'Valid request - Example.com img elements',
        'data' => ['url' => 'http://example.com', 'element' => 'img'],
        'expected_success' => true
    ],
    [
        'name' => 'Valid request - Google.com div elements',
        'data' => ['url' => 'https://google.com', 'element' => 'div'],
        'expected_success' => true
    ],
    [
        'name' => 'Invalid URL',
        'data' => ['url' => 'not-a-url', 'element' => 'img'],
        'expected_success' => false
    ],
    [
        'name' => 'Invalid element name',
        'data' => ['url' => 'http://example.com', 'element' => '123invalid'],
        'expected_success' => false
    ],
    [
        'name' => 'Empty URL',
        'data' => ['url' => '', 'element' => 'img'],
        'expected_success' => false
    ],
    [
        'name' => 'Empty element',
        'data' => ['url' => 'http://example.com', 'element' => ''],
        'expected_success' => false
    ],
    [
        'name' => 'Blocked local URL',
        'data' => ['url' => 'http://localhost', 'element' => 'img'],
        'expected_success' => false
    ]
];

echo "HTML Element Counter API Test Suite\n";
echo "===================================\n\n";

$passed = 0;
$total = count($test_cases);

foreach ($test_cases as $i => $test) {
    echo ($i + 1) . ". Testing: " . $test['name'] . "\n";
    
    $result = makeApiRequest($api_url, $test['data']);
    
    if ($result['success'] === $test['expected_success']) {
        echo "   ✓ PASSED\n";
        $passed++;
    } else {
        echo "   ✗ FAILED\n";
        echo "   Expected success: " . ($test['expected_success'] ? 'true' : 'false') . "\n";
        echo "   Actual success: " . ($result['success'] ? 'true' : 'false') . "\n";
        if (isset($result['error'])) {
            echo "   Error: " . $result['error'] . "\n";
        }
    }
    echo "\n";
}

echo "Test Results: {$passed}/{$total} tests passed\n";

if ($passed === $total) {
    echo "🎉 All tests passed!\n";
} else {
    echo "❌ Some tests failed. Please check the implementation.\n";
}

/**
 * Make API request for testing
 */
function makeApiRequest($url, $data) {
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data),
            'timeout' => 30
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['success' => false, 'error' => 'Failed to connect to API'];
    }
    
    $decoded = json_decode($response, true);
    
    if ($decoded === null) {
        return ['success' => false, 'error' => 'Invalid JSON response'];
    }
    
    return $decoded;
}
?>
