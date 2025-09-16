<?php
// Here's where all the important settings live
// Feel free to tweak these to match your needs

// Set to 'development' when working locally, 'production' when live
define('APP_DEBUG', APP_ENV === 'development');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'html_element_counter');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// How long to keep cached results (in seconds)
// 300 seconds = 5 minutes
define('MAX_REQUESTS_PER_HOUR', 100);
define('MAX_REQUESTS_PER_MINUTE', 10);
define('REQUEST_TIMEOUT', 30); // seconds
define('MAX_REDIRECTS', 5);

// Safety first! These settings help protect your site
define('ENABLE_RATE_LIMITING', true);
define('LOG_ERRORS', true);
define('LOG_FILE', __DIR__ . '/../logs/app.log');

// Speed things up with these performance tweaks
define('ENABLE_GZIP', true);
define('CACHE_HEADERS', true);

// These addresses are off-limits for security reasons
define('BLOCKED_DOMAINS', [
    'localhost',
    '127.0.0.1',
    '::1',
    '0.0.0.0'
]);

// These are the HTML elements we'll look for and count
define('COMMON_HTML_ELEMENTS', [
    'a', 'abbr', 'address', 'area', 'article', 'aside', 'audio',
    'b', 'base', 'bdi', 'bdo', 'blockquote', 'body', 'br', 'button',
    'canvas', 'caption', 'cite', 'code', 'col', 'colgroup',
    'data', 'datalist', 'dd', 'del', 'details', 'dfn', 'dialog', 'div', 'dl', 'dt',
    'em', 'embed',
    'fieldset', 'figcaption', 'figure', 'footer', 'form',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hr', 'html',
    'i', 'iframe', 'img', 'input', 'ins',
    'kbd',
    'label', 'legend', 'li', 'link',
    'main', 'map', 'mark', 'meta', 'meter',
    'nav', 'noscript',
    'object', 'ol', 'optgroup', 'option', 'output',
    'p', 'param', 'picture', 'pre', 'progress',
    'q',
    'rp', 'rt', 'ruby',
    's', 'samp', 'script', 'section', 'select', 'small', 'source', 'span', 'strong', 'style', 'sub', 'summary', 'sup',
    'table', 'tbody', 'td', 'template', 'textarea', 'tfoot', 'th', 'thead', 'time', 'title', 'tr', 'track',
    'u', 'ul',
    'var', 'video',
    'wbr'
]);
?>
