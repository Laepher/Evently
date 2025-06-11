<?php
// ===== FIXED update_pesanan.php =====

// Basic headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include 'config/config.php';

// Simple debug logging
$debug_log = "debug_" . date('Y-m-d') . ".log";
file_put_contents($debug_log, "\n" . date('Y-m-d H:i:s') . " - Request received", FILE_APPEND);

try {
    // Check if POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }

    // Get raw POST data (for JSON requests)
    $raw_input = file_get_contents('php://input');
    file_put_contents($debug_log, "\nRaw input: " . $raw_input, FILE_APPEND);

    // Try to decode JSON first
    $json_data = json_decode($raw_input, true);
    
    // Determine data source
    if ($json_data) {
        // JSON request
        $order_id = isset($json_data['order_id']) ? intval($json_data['order_id']) : 0;
        $new_status = isset($json_data['status']) ? trim($json_data['status']) : '';
        file_put_contents($debug_log, "\nUsing JSON data", FILE_APPEND);
    } else {
        // Form POST request
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $new_status = isset($_POST['status']) ? trim($_POST['status']) : '';
        file_put_contents($debug_log, "\nUsing POST data", FILE_APPEND);
    }

    // Debug: log processed data
    file_put_contents($debug_log, "\nProcessing - Order ID: $order_id, Status: $new_status", FILE_APPEND);

    // Validate order_id
    if ($order_id <= 0) {
        throw new Exception('Invalid or missing order ID: ' . $order_id);
    }
    
    // Validate status
    $allowed_statuses = ['Confirmed', 'Pending', 'Cancelled'];
    if (empty($new_status) || !in_array($new_status, $allowed_statuses)) {
        throw new Exception('Invalid status: "' . $new_status . '". Allowed: ' . implode(', ', $allowed_statuses));
    }
    
    // Convert status to database format
    switch($new_status) {
        case 'Confirmed':
            $status_db = 'terbayar';
            break;
        case 'Pending':
            $status_db = 'menunggu';
            break;
        case 'Cancelled':
            $status_db = 'dibatalkan';
            break;
        default:
            $status_db = 'menunggu';
    }
    
    file_put_contents($debug_log, "\nConverted status: $status_db", FILE_APPEND);
    
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed: ' . mysqli_connect_error());
    }
    
    // Use prepared statement for security
    $check_stmt = mysqli_prepare($conn, "SELECT id_pesanan, status_pesanan FROM pesanan WHERE id_pesanan = ?");
    if (!$check_stmt) {
        throw new Exception('Prepare statement failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_stmt, "i", $order_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) === 0) {
        throw new Exception('Order not found with ID: ' . $order_id);
    }
    
    $current_order = mysqli_fetch_assoc($check_result);
    file_put_contents($debug_log, "\nCurrent order: " . print_r($current_order, true), FILE_APPEND);
    
    // Update order status using prepared statement
    $update_stmt = mysqli_prepare($conn, "UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?");
    if (!$update_stmt) {
        throw new Exception('Prepare update statement failed: ' . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($update_stmt, "si", $status_db, $order_id);
    $update_result = mysqli_stmt_execute($update_stmt);
    
    if (!$update_result) {
        throw new Exception('Update failed: ' . mysqli_stmt_error($update_stmt));
    }
    
    // Check affected rows
    $affected_rows = mysqli_stmt_affected_rows($update_stmt);
    file_put_contents($debug_log, "\nAffected rows: $affected_rows", FILE_APPEND);
    
    // Always return success if query executed without error
    $response = [
        'success' => true,
        'message' => 'Status updated successfully',
        'order_id' => $order_id,
        'old_status' => $current_order['status_pesanan'],
        'new_status' => $status_db,
        'display_status' => $new_status,
        'affected_rows' => $affected_rows
    ];
    
    file_put_contents($debug_log, "\nSuccess response: " . print_r($response, true), FILE_APPEND);
    echo json_encode($response);

} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => $e->getLine(),
        'debug_info' => [
            'post_data' => $_POST,
            'raw_input' => isset($raw_input) ? $raw_input : 'not available'
        ]
    ];
    
    file_put_contents($debug_log, "\nError: " . print_r($error_response, true), FILE_APPEND);
    echo json_encode($error_response);
}

// Clean up
if (isset($check_stmt)) {
    mysqli_stmt_close($check_stmt);
}
if (isset($update_stmt)) {
    mysqli_stmt_close($update_stmt);
}
if (isset($conn)) {
    mysqli_close($conn);
}
?>