<?php
// ===== get_statistics.php - For updating statistics without page refresh =====

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Include database connection
include 'config/config.php';

try {
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed: ' . mysqli_connect_error());
    }
    
    // Get total orders
    $total_query = "SELECT COUNT(*) as total FROM pesanan";
    $total_result = mysqli_query($conn, $total_query);
    if (!$total_result) {
        throw new Exception('Failed to get total orders: ' . mysqli_error($conn));
    }
    $total_row = mysqli_fetch_assoc($total_result);
    $total = $total_row['total'];
    
    // Get confirmed orders
    $confirmed_query = "SELECT COUNT(*) as confirmed FROM pesanan WHERE status_pesanan IN ('confirmed', 'dikonfirmasi')";
    $confirmed_result = mysqli_query($conn, $confirmed_query);
    if (!$confirmed_result) {
        throw new Exception('Failed to get confirmed orders: ' . mysqli_error($conn));
    }
    $confirmed_row = mysqli_fetch_assoc($confirmed_result);
    $confirmed = $confirmed_row['confirmed'];
    
    // Get pending orders
    $pending_query = "SELECT COUNT(*) as pending FROM pesanan WHERE status_pesanan IN ('pending', 'menunggu')";
    $pending_result = mysqli_query($conn, $pending_query);
    if (!$pending_result) {
        throw new Exception('Failed to get pending orders: ' . mysqli_error($conn));
    }
    $pending_row = mysqli_fetch_assoc($pending_result);
    $pending = $pending_row['pending'];
    
    // Get cancelled orders
    $cancelled_query = "SELECT COUNT(*) as cancelled FROM pesanan WHERE status_pesanan IN ('cancelled', 'dibatalkan')";
    $cancelled_result = mysqli_query($conn, $cancelled_query);
    if (!$cancelled_result) {
        throw new Exception('Failed to get cancelled orders: ' . mysqli_error($conn));
    }
    $cancelled_row = mysqli_fetch_assoc($cancelled_result);
    $cancelled = $cancelled_row['cancelled'];
    
    // Return statistics
    $response = [
        'success' => true,
        'total' => $total,
        'confirmed' => $confirmed,
        'pending' => $pending,
        'cancelled' => $cancelled,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'message' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => $e->getLine()
    ];
    
    echo json_encode($error_response);
}

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>