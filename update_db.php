<?php
include 'connection.php';

try {
    $conn->query('ALTER TABLE customer ADD COLUMN sessions INT DEFAULT 1');
    $conn->query('ALTER TABLE customer ADD COLUMN offer_eligible TINYINT DEFAULT 0');
    echo 'Database updated successfully';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>