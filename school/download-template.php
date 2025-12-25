<?php
session_start();
require_once '../config.php';

// Check if school is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'school') {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="student_upload_template.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, ['Full Name', 'Phone', 'Email', 'Password', 'Student Code']);

// Write sample data (optional)
fputcsv($output, ['John Doe', '0781234567', 'john@example.com', 'password123', 'STD001']);
fputcsv($output, ['Jane Smith', '0782345678', 'jane@example.com', 'password123', 'STD002']);

// Close output stream
fclose($output);
exit;
?>