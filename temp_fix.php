<?php
// Temporary script to add is_read column to appointments table if it doesn't exist
require_once 'db.php';

// Check if the column exists
$result = mysqli_query($conn, "SHOW COLUMNS FROM appointments LIKE 'is_read'");
if (!$result || mysqli_num_rows($result) == 0) {
    // Column does not exist, add it
    if (mysqli_query($conn, "ALTER TABLE appointments ADD COLUMN is_read TINYINT(1) DEFAULT 0")) {
        echo "Column 'is_read' added successfully.\n";
    } else {
        echo "Error adding column: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Column 'is_read' already exists.\n";
}
?>