<?php

// Simple script to fix the database issue
// Run this with: php fix_database.php

require_once 'vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'amtradingplc';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? 'root';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Check current structure
    $stmt = $pdo->query("DESCRIBE credits");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $branchIdColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'branch_id') {
            $branchIdColumn = $column;
            break;
        }
    }
    
    if ($branchIdColumn) {
        echo "Current branch_id column: " . json_encode($branchIdColumn) . "\n";
        
        if ($branchIdColumn['Null'] === 'NO') {
            echo "Making branch_id nullable...\n";
            $pdo->exec("ALTER TABLE credits MODIFY COLUMN branch_id BIGINT UNSIGNED NULL");
            echo "✅ branch_id is now nullable.\n";
        } else {
            echo "✅ branch_id is already nullable.\n";
        }
    } else {
        echo "❌ branch_id column not found!\n";
    }
    
    // Update any NULL branch_id values
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM credits WHERE branch_id IS NULL");
    $nullCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($nullCount > 0) {
        echo "Found $nullCount credits with NULL branch_id. Updating...\n";
        
        // Get first active branch
        $stmt = $pdo->query("SELECT id FROM branches WHERE is_active = 1 LIMIT 1");
        $branch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($branch) {
            $branchId = $branch['id'];
            $stmt = $pdo->prepare("UPDATE credits SET branch_id = ? WHERE branch_id IS NULL");
            $stmt->execute([$branchId]);
            echo "✅ Updated $nullCount credits with branch_id = $branchId\n";
        } else {
            echo "❌ No active branches found!\n";
        }
    } else {
        echo "✅ No credits with NULL branch_id found.\n";
    }
    
    echo "Database fix completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}