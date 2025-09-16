-- Database optimization and indexing improvements
USE html_element_counter;

-- Add composite indexes for better query performance
ALTER TABLE requests ADD INDEX idx_domain_element_time (domain_id, element_id, created_at);
ALTER TABLE requests ADD INDEX idx_url_element_cache (url_id, element_id, created_at);
ALTER TABLE requests ADD INDEX idx_error_filter (error_message(1), created_at);

-- Add index for rate limiting table
ALTER TABLE rate_limits ADD INDEX idx_ip_cleanup (ip_address, window_start);

-- Optimize table storage engines if needed
ALTER TABLE domains ENGINE=InnoDB;
ALTER TABLE urls ENGINE=InnoDB;
ALTER TABLE elements ENGINE=InnoDB;
ALTER TABLE requests ENGINE=InnoDB;
ALTER TABLE rate_limits ENGINE=InnoDB;

-- Add constraints for data integrity
ALTER TABLE requests ADD CONSTRAINT chk_element_count CHECK (element_count >= 0);
ALTER TABLE requests ADD CONSTRAINT chk_fetch_time CHECK (fetch_time_ms >= 0);
ALTER TABLE requests ADD CONSTRAINT chk_response_size CHECK (response_size >= 0 OR response_size IS NULL);
