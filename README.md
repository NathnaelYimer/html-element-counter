# HTML Element Counter - Colnect Developer Test Project

This is a test project created as part of the Colnect developer application process. It demonstrates web development skills including PHP, MySQL, JavaScript, and security best practices.

## Project Overview
This application counts HTML elements on any given web page. It was built to demonstrate:
- Modern PHP development practices
- Secure database interactions
- RESTful API design
- Frontend-backend communication
- Input validation and security measures
- Performance optimization

## Key Features

- Element Detection: Accurately identifies and counts any HTML element on a webpage
- Performance Optimization: Implements smart caching mechanisms for faster response times
- Comprehensive Statistics: Provides detailed analytics about page elements and structure
- Rate Limiting: Implements request throttling to prevent abuse
- Robust Error Handling: Comprehensive error management with user-friendly messages
- Security Measures: Strict input validation and output sanitization
- Efficient Processing: Optimized DOM parsing and database operations
- Cross-Platform Compatibility: Fully responsive design for all device sizes
- Security Guard: Blocks shady requests and keeps your data safe
- Looks Good Everywhere: Works on your phone, tablet, or that old laptop in your closet

## Technology Stack

- Frontend: HTML5, CSS3, Vanilla JavaScript (ES6+)
- Backend: PHP 7.4+
- Database: MySQL 5.7+
- Architecture: RESTful API with AJAX

## Quick Start Guide

### Prerequisites

- XAMPP (includes Apache, PHP, and MySQL) - [Download here](https://www.apachefriends.org/)
- Or separately install:
  - PHP 7.4+
  - MySQL 5.7+
  - Apache/Nginx web server

### Step 1: Install XAMPP
1. Download and install XAMPP from [apachefriends.org](https://www.apachefriends.org/)
2. Start XAMPP Control Panel
3. Click "Start" for both Apache and MySQL services

### Step 2: Set Up the Project
1. Place the project folder in your web server directory:
   - Windows: `C:\xampp\htdocs\html-element-counter`
   - Mac: `/Applications/XAMPP/htdocs/html-element-counter`
   - Linux: `/var/www/html/html-element-counter`

### Step 3: Create Database and User
1. Open phpMyAdmin at http://localhost/phpmyadmin
2. Click "New" and create a database named `html_element_counter`
3. Go to "User accounts" > "Add user account"
4. Set username to `html_counter` and choose a strong password
5. Under "Database for user", select "Grant all privileges on database `html_element_counter`"
6. Click "Go" to create the user

### Step 4: Import Database Schema
1. In phpMyAdmin, select the `html_element_counter` database
2. Go to "Import" tab
3. Click "Choose File" and select `database/schema.sql` from the project
4. Click "Go" to import the database structure

### Step 5: Configure Database Connection
Edit `config/database.php` with your database credentials:
```php
private $host = 'localhost';
private $database = 'html_element_counter';
private $username = 'html_counter';
private $password = 'your_secure_password';  // Use the password you set
private $charset = 'utf8mb4';
```

### Step 6: Set Up Logs Directory
```bash
# Create logs directory
mkdir logs

# Set proper permissions
chmod 755 logs  # Linux/Mac
# OR on Windows: Right-click folder > Properties > Security > Edit > Add "Everyone" with full control
```

### Step 7: Test the Application
1. Open your browser and go to:
   ```
   http://localhost/html-element-counter/
   ```
2. Try counting elements on a test URL like `https://www.w3schools.com/`
3. Enter an element name (e.g., `div`, `img`, `a`)
4. Click "Count Elements"

### Troubleshooting
- Database Connection Issues:
  - Verify MySQL is running in XAMPP
  - Double-check database credentials in `config/database.php`
  - Ensure the database and user exist in phpMyAdmin

- Permission Issues:
  - Make sure the `logs` directory is writable
  - Check web server user has proper permissions

- Page Not Found:
  - Verify mod_rewrite is enabled in Apache
  - Check `.htaccess` file exists and is properly configured

## Project Requirements (Colnect Test Task)

This project was developed as part of the Colnect developer application process. The requirements included:

- Create a web application that counts HTML elements on any given URL
- Implement a 5-minute caching mechanism
- Include comprehensive error handling
- Ensure security best practices
- Provide statistics about element counts
- Support both web interface and API access

### Technical Implementation
- No frameworks - Built with vanilla PHP, MySQL, and JavaScript
- Security - Input validation, XSS protection, rate limiting
- Performance - Database optimization, caching
- Responsive Design - Works on all device sizes

This project demonstrates my ability to develop a complete web application following modern development practices. The code is well-documented and includes comprehensive error handling and security measures.

## How to Use

### Using the Web Interface

1. Enter a Website URL: 
   - Type or paste the full URL of the webpage you want to analyze (e.g., `https://www.example.com`)
   - The URL should include the protocol (http:// or https://)

2. Enter an HTML Element to Count:
   - Type the HTML element you want to count (e.g., `img`, `div`, `p`, `a`)
   - Only alphanumeric characters are allowed (a-z, A-Z, 0-9)
   - Common elements to try: `img`, `div`, `p`, `a`, `h1`, `h2`, `span`, `ul`, `li`, `table`

3. Click "Count Elements":
   - The system will fetch the webpage and count the specified elements
   - Results will be displayed below the form

### Understanding the Results

- Request Results: Shows the URL, fetch time, and element count
- General Statistics: Displays information about previous requests
  - Number of unique URLs from the same domain
  - Average fetch time for the domain
  - Total count of the element from this domain
  - Global count of the element across all requests

### Example Usage

1. Count all images on a page:
   - URL: `https://www.example.com`
   - Element: `img`

2. Count all links on a page:
   - URL: `https://www.example.com`
   - Element: `a`

3. Count all paragraphs on a page:
   - URL: `https://www.example.com`
   - Element: `p`

### Tips
- The system caches results for 5 minutes to improve performance
- If a website is not responding, check if the URL is correct and the site is accessible
- Some websites may block automated requests

### Real-World Example

Let's say you want to know how many images are on example.com:

1. Enter: `https://example.com`
2. Type: `img`
3. Click "Count Elements"

You'll see something like:

Found 12 `<img>` tags on example.com

## API Documentation

If you want to get all technical and use this in your own projects, here's how the API works:

### The Main Event: `POST /api/process.php`

**Request Body**:
```json
{
  "url": "https://example.com",
  "element": "img"
}
```

**Success Response**:
```json
{
  "success": true,
  "cached": false,
  "result": {
    "url": "https://example.com",
    "element": "img",
    "count": 5,
    "fetch_time": 250,
    "timestamp": "15/09/2024 14:30"
  },
  "statistics": {
    "domain_urls": 10,
    "domain_avg_time": 275,
    "domain_element_total": 45,
    "global_element_total": 1250
  }
}
```

**Error Response**:
```json
{
  "success": false,
  "error": "Invalid URL format"
}
```

## Database Schema

### Tables

- domains: Stores unique domain names
- urls: Stores unique URLs with domain relationships
- elements: Stores HTML element names
- requests: Stores all fetch requests and results
- rate_limits: Tracks request rates per IP

### Relationships

- requests → domains (many-to-one)
- requests → urls (many-to-one)  
- requests → elements (many-to-one)
- urls → domains (many-to-one)

## Security Features

- Input Validation: Server and client-side validation
- Rate Limiting: 100 requests/hour, 10 requests/minute per IP
- XSS Protection: All inputs sanitized
- Private Network Blocking: Prevents access to local/private IPs
- SQL Injection Prevention: Prepared statements throughout
- Error Logging: Comprehensive error tracking

## Performance Optimizations

- Database Indexing: Optimized queries with proper indexes
- Caching: 5-minute cache for identical requests
- Connection Pooling: Singleton database connections
- Gzip Compression: Reduced bandwidth usage
- Lazy Loading: Efficient resource loading

## Browser Compatibility

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Development

### Testing

Run the test suite:
```bash
php test/test-api.php
```

### Debugging

Enable debug mode in `config/config.php`:
```php
define('APP_DEBUG', true);
```

### Logging

Error logs are stored in `logs/error.log` and `logs/app.log`.

## Deployment

### Production Checklist

1. Set `APP_ENV` to `'production'` in `config/config.php`
2. Disable debug mode
3. Use strong database passwords
4. Enable HTTPS
5. Configure proper file permissions
6. Set up log rotation
7. Configure firewall rules
8. Enable PHP OPcache

### Hosting Requirements

- PHP 7.4+ with extensions: PDO, MySQL, libxml, JSON
- MySQL 5.7+ or MariaDB 10.2+
- 50MB+ disk space
- SSL certificate recommended

## Assumptions Made

1. **Public URLs Only**: Only publicly accessible websites are supported
2. **HTML Content**: URLs must serve HTML content (not JSON, images, etc.)
3. **5-minute Cache**: Reasonable balance between performance and freshness
4. **Rate Limiting**: Conservative limits to prevent abuse
5. **Element Names**: Standard HTML element names only
6. **Error Handling**: Graceful degradation for network issues
7. **Database**: MySQL/MariaDB as primary database
8. **No Authentication**: Public service without user accounts

## Time Tracking

- **Planning & Analysis**: 2 hours
- **Database Design**: 1 hour  
- **Backend Development**: 4 hours
- **Frontend Development**: 2 hours
- **Error Handling & Security**: 2 hours
- **Testing & Optimization**: 1 hour
- **Documentation**: 1 hour
- **Total**: ~13 hours


