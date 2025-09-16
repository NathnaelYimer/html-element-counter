-- HTML Element Counter Database Schema
-- Optimized structure to avoid data repetition

CREATE DATABASE IF NOT EXISTS html_element_counter;
USE html_element_counter;

-- Domains table to store unique domain names
CREATE TABLE domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_domain_name (name)
);

-- URLs table to store unique URLs
CREATE TABLE urls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_id INT NOT NULL,
    path TEXT NOT NULL,
    full_url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE,
    INDEX idx_domain_id (domain_id),
    INDEX idx_full_url (full_url(255))
);

-- Elements table to store unique HTML element names
CREATE TABLE elements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_element_name (name)
);

-- Requests table to store all fetch requests and results
CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_id INT NOT NULL,
    url_id INT NOT NULL,
    element_id INT NOT NULL,
    element_count INT NOT NULL DEFAULT 0,
    fetch_time_ms INT NOT NULL,
    response_size INT DEFAULT NULL,
    status_code INT DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE,
    FOREIGN KEY (url_id) REFERENCES urls(id) ON DELETE CASCADE,
    FOREIGN KEY (element_id) REFERENCES elements(id) ON DELETE CASCADE,
    INDEX idx_domain_time (domain_id, created_at),
    INDEX idx_element_domain (element_id, domain_id),
    INDEX idx_created_at (created_at),
    INDEX idx_url_element_time (url_id, element_id, created_at)
);

-- Insert common HTML elements
INSERT INTO elements (name) VALUES 
('img'), ('div'), ('span'), ('a'), ('p'), ('h1'), ('h2'), ('h3'), ('h4'), ('h5'), ('h6'),
('ul'), ('li'), ('ol'), ('table'), ('tr'), ('td'), ('th'), ('form'), ('input'), ('button'),
('script'), ('style'), ('link'), ('meta'), ('title'), ('body'), ('head'), ('html');
