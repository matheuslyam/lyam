<?php
require_once 'config.php';

try {
    $pdo->exec("ALTER TABLE leads ADD COLUMN funnel_stage VARCHAR(50) DEFAULT 'New Lead'");
    echo "Column 'funnel_stage' added successfully.<br>";
} catch (PDOException $e) {
    echo "Error adding 'funnel_stage' (might already exist): " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE leads ADD COLUMN last_contact_date DATETIME DEFAULT NULL");
    echo "Column 'last_contact_date' added successfully.<br>";
} catch (PDOException $e) {
    echo "Error adding 'last_contact_date' (might already exist): " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE leads ADD COLUMN sales_notes TEXT DEFAULT NULL");
    echo "Column 'sales_notes' added successfully.<br>";
} catch (PDOException $e) {
    echo "Error adding 'sales_notes' (might already exist): " . $e->getMessage() . "<br>";
}

try {
    $pdo->exec("ALTER TABLE leads ADD COLUMN lead_source VARCHAR(50) DEFAULT 'Quiz'");
    echo "Column 'lead_source' added successfully.<br>";
} catch (PDOException $e) {
    echo "Error adding 'lead_source' (might already exist): " . $e->getMessage() . "<br>";
}

echo "Database update completed.";
?>