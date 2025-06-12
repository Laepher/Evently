<?php
// update_pesanan.php - Handler untuk update status pesanan dari pemesanantiket.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
include 'config/config.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
    exit;
}

// Check database connection
if (!isset($conn) || !$conn) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

// Get and validate input data
$order_id = isset($_POST['order_id']) ? trim($_POST['order_id']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validate required fields
if (empty($order_id) || empty($status)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Order ID and status are required'
    ]);
    exit;
}

// Validate status values
$valid_statuses = ['Confirmed', 'Pending', 'Cancelled'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value'
    ]);
    exit;
}

// Map status to database values
$status_mapping = [
    'Confirmed' => 'confirmed',
    'Pending' => 'pending',
    'Cancelled' => 'cancelled'
];

$db_status = $status_mapping[$status];

try {
    // Start transaction
    mysqli_autocommit($conn, false);
    
    // Check if order exists first
    $check_query = "SELECT id_pesanan, status_pesanan FROM pesanan WHERE id_pesanan = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    
    if (!$check_stmt) {
        throw new Exception('Failed to prepare check statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_stmt, 'i', $order_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        mysqli_stmt_close($check_stmt);
        throw new Exception('Order not found');
    }
    
    $existing_order = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);
    
    // Update the order status
    $update_query = "UPDATE pesanan SET status_pesanan = ?, updated_at = NOW() WHERE id_pesanan = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    
    if (!$update_stmt) {
        throw new Exception('Failed to prepare update statement: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($update_stmt, 'si', $db_status, $order_id);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception('Failed to execute update: ' . mysqli_stmt_error($update_stmt));
    }
    
    $affected_rows = mysqli_stmt_affected_rows($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    if ($affected_rows === 0) {
        throw new Exception('No rows were updated. Order may not exist or status is already set.');
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order status updated successfully',
        'data' => [
            'order_id' => $order_id,
            'old_status' => $existing_order['status_pesanan'],
            'new_status' => $db_status,
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'order_id' => $order_id,
            'status' => $status,
            'db_status' => $db_status ?? null
        ]
    ]);
    
} finally {
    // Restore autocommit
    mysqli_autocommit($conn, true);
    
    // Close connection
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
?>