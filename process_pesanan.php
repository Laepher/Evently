<?php
session_start();
include 'config/config.php';
include 'auth/auth.php';

header('Content-Type: application/json');

// Aktifkan error reporting untuk debugging (hapus di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Pastikan user login
    require_login();

    // Validasi metode request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Metode harus POST');
    }

    // Validasi aksi
    if (!isset($_POST['action']) || $_POST['action'] !== 'create_pesanan') {
        throw new Exception('Aksi tidak valid');
    }

    // Ambil data dari session dan form
    $id_user = $_SESSION['id_user'] ?? null; // perhatikan: ini disesuaikan dengan login kamu
    $id_event = (int)($_POST['id_event'] ?? 0);
    $banyak_tiket = (int)($_POST['banyak_tiket'] ?? 0);
    $total_harga = (int)($_POST['total_harga'] ?? 0);
    $metode_bayar = trim($_POST['metode_bayar'] ?? '');

    // Validasi input
    if (!$id_user || !$id_event || !$banyak_tiket || !$total_harga || !$metode_bayar) {
        throw new Exception('Data pesanan tidak lengkap');
    }

    // Ambil salah satu tiket dari event
    $stmt = $conn->prepare("SELECT id_tiket FROM tiket WHERE id_event = ? LIMIT 1");
    $stmt->bind_param("i", $id_event);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        throw new Exception('Tiket tidak ditemukan untuk event ini');
    }

    $id_tiket = $row['id_tiket'];

    // Masukkan ke tabel pesanan
    $status_pesanan = 'menunggu'; // default status baru
    $stmt = $conn->prepare("INSERT INTO pesanan (id_user, id_tiket, banyak_tiket, total_harga, status_pesanan, metode_bayar) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $id_user, $id_tiket, $banyak_tiket, $total_harga, $status_pesanan, $metode_bayar);
    $stmt->execute();

    if ($stmt->affected_rows <= 0) {
        throw new Exception('Gagal menyimpan pesanan');
    }

    $id_pesanan = $stmt->insert_id;

    echo json_encode([
        'success' => true,
        'message' => 'Pesanan berhasil dibuat',
        'id_pesanan' => $id_pesanan
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
