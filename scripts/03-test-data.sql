-- Insert test data for development and testing
USE html_element_counter;

-- Insert test domains
INSERT IGNORE INTO domains (name) VALUES 
('example.com'),
('google.com'),
('github.com'),
('stackoverflow.com');

-- Insert test URLs
INSERT IGNORE INTO urls (domain_id, path, full_url) VALUES 
(1, '/', 'http://example.com'),
(1, '/about', 'http://example.com/about'),
(2, '/', 'https://google.com'),
(3, '/', 'https://github.com'),
(4, '/questions', 'https://stackoverflow.com/questions');

-- Insert test requests (simulating past activity)
INSERT IGNORE INTO requests (domain_id, url_id, element_id, element_count, fetch_time_ms, response_size, created_at) VALUES 
(1, 1, 1, 5, 250, 1024, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 1, 2, 12, 280, 1024, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(2, 3, 1, 8, 150, 2048, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(3, 4, 3, 25, 320, 4096, DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
(4, 5, 1, 3, 180, 1536, DATE_SUB(NOW(), INTERVAL 10 MINUTE));
