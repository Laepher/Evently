<?php
header('Content-Type: application/json');

include 'config/config.php';

try {
    // Check database connection
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Get total orders
    $total_query = "SELECT COUNT(*) as total FROM pesanan";
    $total_result = mysqli_query($conn, $total_query);
    $total_row = mysqli_fetch_assoc($total_result);
    $total = $total_row['total'];
    
    // Get confirmed orders
    $confirmed_query = "SELECT COUNT(*) as confirmed FROM pesanan WHERE status_pesanan IN ('confirmed', 'terbayar')";
    $confirmed_result = mysqli_query($conn, $confirmed_query);
    $confirmed_row = mysqli_fetch_assoc($confirmed_result);
    $confirmed = $confirmed_row['confirmed'];
    
    // Get pending orders
    $pending_query = "SELECT COUNT(*) as pending FROM pesanan WHERE status_pesanan IN ('pending', 'menunggu')";
    $pending_result = mysqli_query($conn, $pending_query);
    $pending_row = mysqli_fetch_assoc($pending_result);
    $pending = $pending_row['pending'];
    
    // Get cancelled orders
    $cancelled_query = "SELECT COUNT(*) as cancelled FROM pesanan WHERE status_pesanan IN ('cancelled', 'dibatalkan')";
    $cancelled_result = mysqli_query($conn, $cancelled_query);
    $cancelled_row = mysqli_fetch_assoc($cancelled_result);
    $cancelled = $cancelled_row['cancelled'];
    
    // Prepare response
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
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'total' => 0,
        'confirmed' => 0,
        'pending' => 0,
        'cancelled' => 0
    ]);
}

// Close connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>