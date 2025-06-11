<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

include 'config/config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Get the last check timestamp from request
    $input = json_decode(file_get_contents('php://input'), true);
    $last_check = isset($input['last_check']) ? $input['last_check'] : (time() - 300) * 1000; // Default 5 minutes ago
    
    // Convert JavaScript timestamp to MySQL datetime
    $last_check_datetime = date('Y-m-d H:i:s', $last_check / 1000);
    
    // Check for recent status updates or recently modified orders
    $query = "SELECT 
        p.id_pesanan,
        p.status_pesanan,
        p.tanggal_pesanan,
        UNIX_TIMESTAMP(p.tanggal_pesanan) * 1000 as timestamp_ms
    FROM pesanan p
    WHERE p.id_user = ? AND (
        p.status_pesanan IN ('terbayar', 'dibatalkan', 'confirmed', 'cancelled') OR
        p.tanggal_pesanan >= ? OR
        TIMESTAMPDIFF(MINUTE, p.tanggal_pesanan, NOW()) <= 5
    )
    ORDER BY p.tanggal_pesanan DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $user_id, $last_check_datetime);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $updated_orders = [];
    $has_updates = false;
    $recent_confirmed_or_cancelled = false;
    
    while ($row = $result->fetch_assoc()) {
        // Check if this order has a status that indicates recent admin action
        $status_lower = strtolower($row['status_pesanan']);
        if (in_array($status_lower, ['terbayar', 'dibatalkan', 'confirmed', 'cancelled'])) {
            $has_updates = true;
            $recent_confirmed_or_cancelled = true;
            $updated_orders[] = $row['id_pesanan'];
        }
        
        // Also check if order was created recently (within last 5 minutes)
        if ($row['timestamp_ms'] > $last_check) {
            $has_updates = true;
            $updated_orders[] = $row['id_pesanan'];
        }
    }
    
    $stmt->close();
    
    // Additional check: Look for any orders that have been updated in the last few minutes
    // This helps catch status changes that might have been made by admin
    $recent_update_query = "SELECT COUNT(*) as count FROM pesanan 
                          WHERE id_user = ? AND 
                          status_pesanan IN ('terbayar', 'dibatalkan', 'confirmed', 'cancelled') AND
                          TIMESTAMPDIFF(MINUTE, tanggal_pesanan, NOW()) <= 10";
    
    $recent_stmt = $conn->prepare($recent_update_query);
    $recent_stmt->bind_param("s", $user_id);
    $recent_stmt->execute();
    $recent_result = $recent_stmt->get_result();
    $recent_row = $recent_result->fetch_assoc();
    $recent_stmt->close();
    
    // If there are recent confirmed/cancelled orders, mark as having updates
    if ($recent_row['count'] > 0) {
        $has_updates = true;
    }
    
    // Response
    $response = [
        'success' => true,
        'hasUpdates' => $has_updates,
        'updatedOrders' => array_unique($updated_orders),
        'recentConfirmedOrCancelled' => $recent_confirmed_or_cancelled,
        'timestamp' => time() * 1000,
        'debug' => [
            'user_id' => $user_id,
            'last_check' => $last_check_datetime,
            'recent_count' => $recent_row['count']
        ]
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'hasUpdates' => false
    ]);
}

// Close connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>