<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['id_user'])) {
    die("Harus login.");
}

$id_user = $_SESSION['id_user'];
$id_event = $_POST['id_event'] ?? null;
$rating_value = $_POST['rating_value'] ?? null;

if ($id_event && $rating_value) {
    // Cek apakah user pernah membeli tiket untuk event ini dengan status confirmed
    $sql_check = "SELECT p.id_pesanan, t.id_tiket 
                  FROM pesanan p 
                  JOIN tiket t ON p.id_tiket = t.id_tiket 
                  WHERE p.id_user = ? AND t.id_event = ? 
                  AND (p.status_pesanan = 'confirmed' OR p.status_pesanan = 'terbayar')
                  LIMIT 1";
    
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $id_user, $id_event);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        echo "<script>
                alert('Anda hanya bisa memberikan rating setelah melakukan pembelian tiket yang sudah dikonfirmasi.');
                window.location.href = 'Deskripsi_tiket.php?id_event=" . urlencode($id_event) . "';
              </script>";
        exit;
    }
    
    $purchase_data = $result_check->fetch_assoc();
    $id_tiket = $purchase_data['id_tiket'];
    $stmt_check->close();
    
    // Cek apakah user sudah pernah memberikan rating untuk event ini
    $sql_existing = "SELECT id_rating FROM rating WHERE id_user = ? AND id_tiket = ?";
    $stmt_existing = $conn->prepare($sql_existing);
    $stmt_existing->bind_param("ss", $id_user, $id_tiket);
    $stmt_existing->execute();
    $result_existing = $stmt_existing->get_result();
    
    if ($result_existing->num_rows > 0) {
        // Update rating yang sudah ada
        $existing_rating = $result_existing->fetch_assoc();
        $sql_update = "UPDATE rating SET rating_value = ?, tanggal_rating = CURRENT_TIMESTAMP WHERE id_rating = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $rating_value, $existing_rating['id_rating']);
        
        if ($stmt_update->execute()) {
            echo "<script>
                    alert('Rating berhasil diperbarui!');
                    window.location.href = 'Deskripsi_tiket.php?id_event=" . urlencode($id_event) . "';
                  </script>";
        } else {
            echo "<script>
                    alert('Gagal memperbarui rating: " . $conn->error . "');
                    window.location.href = 'Deskripsi_tiket.php?id_event=" . urlencode($id_event) . "';
                  </script>";
        }
        $stmt_update->close();
    } else {
        // Insert rating baru
        $sql_max = "SELECT MAX(id_rating) AS max_id FROM rating";
        $result = $conn->query($sql_max);
        $row = $result->fetch_assoc();
        $next_id_rating = ($row['max_id'] !== null) ? $row['max_id'] + 1 : 12000;
        
        $sql_insert = "INSERT INTO rating (id_rating, id_tiket, id_user, rating_value, tanggal_rating) 
                       VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("issi", $next_id_rating, $id_tiket, $id_user, $rating_value);
        
        if ($stmt_insert->execute()) {
            echo "<script>
                    alert('Rating berhasil diberikan!');
                    window.location.href = 'Deskripsi_tiket.php?id_event=" . urlencode($id_event) . "';
                  </script>";
        } else {
            echo "<script>
                    alert('Gagal memberikan rating: " . $conn->error . "');
                    window.location.href = 'Deskripsi_tiket.php?id_event=" . urlencode($id_event) . "';
                  </script>";
        }
        $stmt_insert->close();
    }
    
    $stmt_existing->close();
} else {
    echo "<script>
            alert('Data tidak lengkap.');
            window.history.back();
          </script>";
}

$conn->close();
?>