<?php
include 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bannerId = $_POST['banner_id'] ?? null;
    $description = $_POST['description'] ?? '';
    $description = trim($description);

    if (!$bannerId || !in_array($bannerId, ['1', '2', '3'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid banner ID']);
        exit;
    }

    if (!isset($_FILES['banner_image']) || $_FILES['banner_image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid image uploaded']);
        exit;
    }

    $fileTmpPath = $_FILES['banner_image']['tmp_name'];
    $fileSize = $_FILES['banner_image']['size'];
    $fileType = $_FILES['banner_image']['type'];

    // Validate file type (image only)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($fileType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Unsupported image type']);
        exit;
    }

    // Validate file size max 5MB
    if ($fileSize > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['error' => 'File size exceeds 5MB']);
        exit;
    }

    $imageData = file_get_contents($fileTmpPath);

    // Check if banner row exists
    $sqlCheck = "SELECT COUNT(*) as cnt FROM banners WHERE banner_id = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $bannerId);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $row = $resultCheck->fetch_assoc();
    $exists = $row['cnt'] > 0;
    $stmtCheck->close();

    if ($exists) {
        // Update
        $sql = "UPDATE banners SET banner_image = ?, description = ? WHERE banner_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("bsi", $null, $desc, $bannerId);
        $desc = $description;
        $stmt->send_long_data(0, $imageData);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Banner updated']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update banner']);
        }
        $stmt->close();
    } else {
        // Insert new row
        $sql = "INSERT INTO banners (banner_id, banner_image, description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ibs", $bannerId, $null, $description);
        $stmt->send_long_data(1, $imageData);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Banner saved']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save banner']);
        }
        $stmt->close();
    }

    $conn->close();
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

