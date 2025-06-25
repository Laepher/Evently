<?php
session_start();
include 'config/config.php';

if (!isset($_SESSION['id_user'])) {
    die("Harus login.");
}

$id_user = $_SESSION['id_user'];
$id_event = $_POST['id_event'] ?? null;
$nilai_ulasan = $_POST['nilai_ulasan'] ?? null;

if ($id_event && $nilai_ulasan) {
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
                alert('Anda hanya bisa memberikan ulasan setelah melakukan pembelian tiket yang sudah dikonfirmasi.');
                window.location.href = 'Deskripsi_tiket.php?id_event=" . urlencode($id_event) . "';
              </script>";
        exit;
    }
    
    $purchase_data = $result_check->fetch_assoc();
    $id_tiket = $purchase_data['id_tiket'];
    $stmt_check->close();
    
    // Cek apakah user sudah pernah memberikan ulasan untuk event ini
    $sql_existing = "SELECT id_ulasan FROM ulasan WHERE id_user = ? AND id_tiket = ?";
    $stmt_existing = $conn->prepare($sql_existing);
    $stmt_existing->bind_param("ss", $id_user, $id_tiket);
    $stmt_existing->execute();
    $result_existing = $stmt_existing->get_result();
    
    if ($result_existing->num_rows > 0) {
        // Update ulasan yang sudah ada
        $existing_ulasan = $result_existing->fetch_assoc();
        $sql_update = "UPDATE ulasan SET nilai_ulasan = ?, tanggal_ulasan = CURRENT_TIMESTAMP WHERE id_ulasan = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $nilai_ulasan, $existing_ulasan['id_ulasan']);
        
        if ($stmt_update->execute()) {
            echo "<script>
                    alert('Ulasan berhasil diperbarui!');
                    window.location.href = 'Deskripsi_tiket.php?id_event=" . urlencode($id_event) . "';
                  </script>";
        } else {
            echo "<script>
                    alert('Gagal memperbarui ulasan: " . $conn->error . "');
                    window.location.href = 'Deskripsi_tiket.php?id_event=" . urlencode($id_event) . "';
                  </script>";
        }
        $stmt_update->close();
    } else {
        // Insert ulasan baru
        $sql_max = "SELECT MAX(id_ulasan) AS max_id FROM ulasan";
        $result = $conn->query($sql_max);
        $row = $result->fetch_assoc();
        $next_id_ulasan = ($row['max_id'] !== null) ? $row['max_id'] + 1 : 12000;
        
        $sql_insert = "INSERT INTO ulasan (id_ulasan, id_tiket, id_user, nilai_ulasan, tanggal_ulasan) 
                       VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("issi", $next_id_ulasan, $id_tiket, $id_user, $nilai_ulasan);
        
        if ($stmt_insert->execute()) {
            echo "<script>
                    alert('Ulasan berhasil diberikan!');
                    window.location.href = 'Deskripsi_tiket.php?id_event=" . urlencode($id_event) . "';
                  </script>";
        } else {
            echo "<script>
                    alert('Gagal memberikan ulasan: " . $conn->error . "');
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