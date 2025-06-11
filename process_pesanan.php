<?php
include 'config/config.php';
header('Content-Type: application/json');

// Error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    include 'config/config.php';
    session_start();

    // Debug: Log semua data yang diterima
    error_log('POST data received: ' . print_r($_POST, true));

    // Cek apakah request method POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method tidak diizinkan');
    }

    // Cek apakah action ada
    if (!isset($_POST['action']) || $_POST['action'] !== 'create_pesanan') {
        throw new Exception('Action tidak valid');
    }

    // Validasi input
    if (!isset($_POST['id_event'], $_POST['total_harga'], $_POST['banyak_tiket'], $_POST['metode_bayar'])) {
        throw new Exception('Data tidak lengkap');
    }

    $id_event = intval($_POST['id_event']);
    $total_harga = intval($_POST['total_harga']);
    $banyak_tiket = intval($_POST['banyak_tiket']);
    $metode_bayar = $_POST['metode_bayar'];
    
    // Validasi data
    if ($id_event <= 0 || $total_harga <= 0 || $banyak_tiket <= 0) {
        throw new Exception('Data tidak valid');
    }
    
    // Pastikan user sudah login (sesuaikan dengan sistem login Anda)
    $id_user = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
    
    // Jika tidak ada user login, cek apakah ada user di database
    if (!$id_user) {
        // Cek user pertama yang ada di database
        $check_user = $conn->query("SELECT id_user FROM user LIMIT 1");
        if ($check_user && $check_user->num_rows > 0) {
            $user_data = $check_user->fetch_assoc();
            $id_user = $user_data['id_user'];
        } else {
            throw new Exception('Tidak ada user yang tersedia di database. Silakan daftar terlebih dahulu.');
        }
    }
    
    // Validasi apakah user_id ada di database
    $validate_user = $conn->prepare("SELECT id_user FROM user WHERE id_user = ?");
    $validate_user->bind_param("i", $id_user);
    $validate_user->execute();
    
    if ($validate_user->get_result()->num_rows === 0) {
        throw new Exception('User dengan ID ' . $id_user . ' tidak ditemukan di database');
    }
    
    // Cek koneksi database
    if (!$conn) {
        throw new Exception('Koneksi database gagal');
    }
    
    // Ambil id_tiket berdasarkan id_event
    $query_tiket = "SELECT id_tiket FROM tiket WHERE id_event = ?";
    $stmt_tiket = $conn->prepare($query_tiket);
    
    if (!$stmt_tiket) {
        throw new Exception('Prepare statement gagal: ' . $conn->error);
    }
    
    $stmt_tiket->bind_param("i", $id_event);
    $stmt_tiket->execute();
    $result_tiket = $stmt_tiket->get_result();
    $tiket = $result_tiket->fetch_assoc();
    
    if (!$tiket) {
        throw new Exception('Tiket tidak ditemukan untuk event ID: ' . $id_event);
    }
    
    // Konversi metode bayar ke format enum database
    $metode_bayar_db = '';
    switch($metode_bayar) {
        case 'e-banking':
            $metode_bayar_db = 'E-Banking';
            break;
        case 'e-money':
            $metode_bayar_db = 'E-money';
            break;
        case 'transfer':
            $metode_bayar_db = 'Bank Transfer';
            break;
        default:
            throw new Exception('Metode pembayaran tidak valid: ' . $metode_bayar);
    }
    
    // Insert ke tabel pesanan
    $query_pesanan = "INSERT INTO pesanan (id_user, id_tiket, tanggal_pesanan, status_pesanan, total_harga, banyak_tiket, metode_bayar) 
                    VALUES (?, ?, CURDATE(), 'menunggu', ?, ?, ?)";
    
    $stmt_pesanan = $conn->prepare($query_pesanan);
    
    if (!$stmt_pesanan) {
        throw new Exception('Prepare statement pesanan gagal: ' . $conn->error);
    }
    
    $stmt_pesanan->bind_param("iiiis", $id_user, $tiket['id_tiket'], $total_harga, $banyak_tiket, $metode_bayar_db);
    
    if ($stmt_pesanan->execute()) {
        $id_pesanan = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'id_pesanan' => $id_pesanan,
            'message' => 'Pesanan berhasil dibuat'
        ]);
    } else {
        throw new Exception('Gagal menyimpan pesanan: ' . $stmt_pesanan->error);
    }

} catch (Exception $e) {
    // Log error untuk debugging
    error_log('Error in process_pesanan.php: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug' => [
            'file' => __FILE__,
            'line' => $e->getLine(),
            'post_data' => $_POST
        ]
    ]);
}
?>