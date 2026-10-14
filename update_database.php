<?php
include 'db_config.php';

try {
    // Add user_id column to orders table if it doesn't exist
    $conn->exec("ALTER TABLE orders ADD COLUMN user_id INT DEFAULT NULL AFTER id");
    
    // Add foreign key constraint
    $conn->exec("ALTER TABLE orders ADD CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE");
    
    echo '<h2 style="color: green;">✓ Database updated successfully!</h2>';
    echo '<p>The orders table now has user_id column.</p>';
    echo '<p><a href="admin_dashboard.php">Go to Admin Dashboard</a></p>';
} catch (PDOException $e) {
    // Column might already exist, that's okay
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo '<h2 style="color: blue;">✓ Column already exists!</h2>';
        echo '<p>The orders table already has user_id column.</p>';
        echo '<p><a href="admin_dashboard.php">Go to Admin Dashboard</a></p>';
    } else {
        echo '<h2 style="color: red;">Error:</h2>';
        echo '<p>' . $e->getMessage() . '</p>';
    }
}
?>