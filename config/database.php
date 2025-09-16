<?php
// This file handles all the database connection stuff

class Database {
    private static $instance = null;
    private $connection;
    
    // Time to get connected! Update these settings to match your database setup
    private $host = 'localhost';
    private $database = 'html_element_counter';
    private $username = 'root';    // Default username for XAMPP
    private $password = '';  
    private $charset = 'utf8mb4';
    
    // Set up the database connection with some sensible defaults
    private function __construct() {
        // Build the connection string
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
        
        // Configure how we want to handle database errors and data
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Return data as associative arrays
            PDO::ATTR_EMULATE_PREPARES => false,  // Use real prepared statements
        ];
        
        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Keep the database connection secure by preventing cloning
    private function __clone() {}
    public function __wakeup() {}
}
?>
