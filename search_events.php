<?php
// Edit: search_events.php - Skrip backend untuk menangani pencarian acara

require 'config/config.php'; // Diasumsikan Anda memiliki config.php dengan $conn

header('Content-Type: application/json');

$query = isset($_GET['query']) ? $_GET['query'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3; // Default 3 item per halaman

$offset = ($page - 1) * $limit;

// Siapkan kueri SQL dasar
$sql = "SELECT id_event, nama_event, kategori FROM event";
$params = [];
$types = "";

if (!empty($query)) {
    $sql .= " WHERE nama_event LIKE ? OR kategori LIKE ?";
    $params[] = '%' . $query . '%';
    $params[] = '%' . $query . '%';
    $types .= "ss";
}

$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt === false) {
    echo json_encode(['error' => 'Failed to prepare statement: ' . mysqli_error($conn)]);
    exit;
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$events = [];
while ($row = mysqli_fetch_assoc($result)) {
    $events[] = $row;
}
mysqli_stmt_close($stmt);

// Dapatkan jumlah total acara untuk paginasi
$countSql = "SELECT COUNT(*) AS total FROM event";
$countParams = [];
$countTypes = "";

if (!empty($query)) {
    $countSql .= " WHERE nama_event LIKE ? OR kategori LIKE ?";
    $countParams[] = '%' . $query . '%';
    $countParams[] = '%' . $query . '%';
    $countTypes .= "ss";
}

$countStmt = mysqli_prepare($conn, $countSql);
if ($countStmt === false) {
    echo json_encode(['error' => 'Failed to prepare count statement: ' . mysqli_error($conn)]);
    exit;
}

if (!empty($query)) {
    mysqli_stmt_bind_param($countStmt, $countTypes, ...$countParams);
}
mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$totalRows = mysqli_fetch_assoc($countResult)['total'];
mysqli_stmt_close($countStmt);

$totalPages = ceil($totalRows / $limit);

echo json_encode([
    'events' => $events,
    'totalPages' => $totalPages,
    'currentPage' => $page,
    'totalResults' => $totalRows
]);

mysqli_close($conn);
?>