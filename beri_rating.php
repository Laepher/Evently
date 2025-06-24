<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['id_user'])) {
    die("Harus login.");
}

$id_user = $_SESSION['id_user'];
$id_tiket = $_POST['id_tiket'] ?? null;
$id_event = $_POST['id_event'] ?? null;
$rating_value = $_POST['rating_value'] ?? null;

if ($id_tiket && $rating_value) {
    // Ambil id_rating terbesar
    $sql_max = "SELECT MAX(id_rating) AS max_id FROM rating";
    $result = $conn->query($sql_max);
    $row = $result->fetch_assoc();
    $next_id_rating = ($row['max_id'] !== null) ? $row['max_id'] + 1 : 12000;

    // Insert atau Update rating
    $sql = "INSERT INTO rating (id_rating, id_tiket, id_user, rating_value)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating_value = VALUES(rating_value), tanggal_rating = CURRENT_TIMESTAMP";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $next_id_rating, $id_tiket, $id_user, $rating_value);

    if ($stmt->execute()) {
        header("Location: Deskripsi_tiket.php?id_event=" . urlencode($id_event));
        exit;
    } else {
        echo "Gagal simpan rating: " . $conn->error;
    }
    $stmt->close();
} else {
    echo "Data tidak lengkap.";
}

$conn->close();
?>
