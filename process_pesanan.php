<?php
session_start();
include 'config/config.php';
include 'auth/auth.php';

header('Content-Type: application/json');

try {
    require_login();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metode harus POST');
    }

    if (!isset($_POST['action']) || $_POST['action'] !== 'create_pesanan') {
        throw new Exception('Aksi tidak valid');
    }

    $id_user = $_SESSION['id_user'];
    $id_event = (int)($_POST['id_event'] ?? 0);
    $banyak_tiket = (int)($_POST['banyak_tiket'] ?? 0);
    $total_harga = (int)($_POST['total_harga'] ?? 0);
    $metode_bayar = trim($_POST['metode_bayar'] ?? '');

    if (!$id_user || !$id_event || !$banyak_tiket || !$total_harga || !$metode_bayar) {
        throw new Exception('Data pesanan tidak lengkap');
    }

    // Validasi metode bayar
    $metode_valid = ['e-banking', 'e-money', 'transfer'];
    if (!in_array($metode_bayar, $metode_valid)) {
        throw new Exception('Metode bayar tidak valid');
    }

    // Ambil satu id_tiket dari event
    $stmt = $conn->prepare("SELECT id_tiket FROM tiket WHERE id_event = ? LIMIT 1");
    $stmt->bind_param("i", $id_event);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        throw new Exception('Tiket tidak ditemukan');
    }

    $id_tiket = $row['id_tiket'];

    // Status default
    $status_pesanan = 'menunggu';

    $stmt = $conn->prepare("INSERT INTO pesanan (id_user, id_tiket, banyak_tiket, total_harga, status_pesanan, metode_bayar) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $id_user, $id_tiket, $banyak_tiket, $total_harga, $status_pesanan, $metode_bayar);
    $stmt->execute();

    $id_pesanan = $stmt->insert_id;

    echo json_encode([
        'success' => true,
        'message' => 'Pesanan berhasil dibuat',
        'id_pesanan' => $id_pesanan
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
