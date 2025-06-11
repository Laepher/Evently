<?php
include 'config/config.php';

header('Content-Type: application/json');

// Function to generate unique filename
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    return $filename;
}

// Function to validate file upload
function validateFile($file) {
    $maxSize = 5 * 1024 * 1024; // 5MB
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    
    // Check if file exists
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'message' => 'File upload error'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'message' => 'File size exceeds 5MB limit'];
    }
    
    // Check file type
    if (!in_array($file['type'], $allowedTypes)) {
        return ['valid' => false, 'message' => 'Only JPG, JPEG, and PNG files are allowed'];
    }
    
    return ['valid' => true];
}

try {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    // Check if action is upload_bukti
    if (!isset($_POST['action']) || $_POST['action'] !== 'upload_bukti') {
        throw new Exception('Invalid action');
    }
    
    // Validate required fields
    if (!isset($_POST['id_pesanan']) || empty($_POST['id_pesanan'])) {
        throw new Exception('ID Pesanan is required');
    }
    
    if (!isset($_FILES['bukti_bayar'])) {
        throw new Exception('Bukti pembayaran file is required');
    }
    
    $id_pesanan = mysqli_real_escape_string($conn, $_POST['id_pesanan']);
    $bukti_bayar = $_FILES['bukti_bayar'];
    
    // Validate file
    $validation = validateFile($bukti_bayar);
    if (!$validation['valid']) {
        throw new Exception($validation['message']);
    }
    
    // Check if pesanan exists
    $checkPesananQuery = "SELECT * FROM pesanan WHERE id_pesanan = '$id_pesanan'";
    $pesananResult = mysqli_query($conn, $checkPesananQuery);
    
    if (!$pesananResult || mysqli_num_rows($pesananResult) === 0) {
        throw new Exception('Pesanan not found');
    }
    
    $pesananData = mysqli_fetch_assoc($pesananResult);
    
    // Create upload directory if it doesn't exist
    $uploadDir = 'uploads/bukti_pembayaran/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Generate unique filename
    $filename = generateUniqueFilename($bukti_bayar['name']);
    $uploadPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($bukti_bayar['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to upload file');
    }
    
    // Check if payment record already exists
    $checkPaymentQuery = "SELECT * FROM pembayaran WHERE id_pesanan = '$id_pesanan'";
    $paymentResult = mysqli_query($conn, $checkPaymentQuery);
    
    if ($paymentResult && mysqli_num_rows($paymentResult) > 0) {
        // Get existing payment data
        $existingPayment = mysqli_fetch_assoc($paymentResult);
        
        // Delete old bukti file if exists
        if (!empty($existingPayment['bukti_bayar'])) {
            $oldFilePath = $uploadDir . $existingPayment['bukti_bayar'];
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        
        // Update existing payment record
        $updateQuery = "UPDATE pembayaran 
                       SET bukti_bayar = '$filename' 
                       WHERE id_pesanan = '$id_pesanan'";
        
        if (!mysqli_query($conn, $updateQuery)) {
            // Delete uploaded file if database update fails
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
            throw new Exception('Failed to update payment record: ' . mysqli_error($conn));
        }
        
        $id_pembayaran = $existingPayment['id_pembayaran'];
    } else {
        // Insert new payment record
        $insertQuery = "INSERT INTO pembayaran (id_pesanan, bukti_bayar) 
                       VALUES ('$id_pesanan', '$filename')";
        
        if (!mysqli_query($conn, $insertQuery)) {
            // Delete uploaded file if database insert fails
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
            throw new Exception('Failed to insert payment record: ' . mysqli_error($conn));
        }
        
        $id_pembayaran = mysqli_insert_id($conn);
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Bukti pembayaran berhasil diupload',
        'filename' => $filename,
        'id_pesanan' => $id_pesanan,
        'id_pembayaran' => $id_pembayaran
    ]);
    
} catch (Exception $e) {
    // Error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    // Log error for debugging
    error_log('Payment upload error: ' . $e->getMessage());
}

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>