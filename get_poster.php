<?php
include 'config/config.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo 'error';
    exit;
}

$eventId = $_GET['id'];

// Fetch poster data from database
$query = "SELECT poster_event FROM event WHERE id_event = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    echo 'error';
    exit;
}

$stmt->bind_param("s", $eventId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo 'error';
    exit;
}

$row = $result->fetch_assoc();
$posterData = $row['poster_event'];

// Check if poster data exists
if (empty($posterData)) {
    echo 'error';
    exit;
}

// Convert binary data to base64
$base64Poster = base64_encode($posterData);

// Return base64 encoded image
echo $base64Poster;

$stmt->close();
$conn->close();
?>