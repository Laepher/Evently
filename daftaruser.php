<?php
include 'config/config.php';

$searchTerm = '';
$filteredUsers = [];

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchTerm = trim($_GET['search']);
    $sql = "SELECT id_user, nama_user, email_user, status FROM user 
            WHERE nama_user LIKE ? OR email_user LIKE ? OR status LIKE ? 
            ORDER BY nama_user ASC";
    $stmt = $conn->prepare($sql);
    $likeTerm = "%{$searchTerm}%";
    $stmt->bind_param("sss", $likeTerm, $likeTerm, $likeTerm);
} else {
    $sql = "SELECT id_user, nama_user, email_user, status FROM user ORDER BY nama_user ASC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $filteredUsers[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evently - Daftar Users</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3B8BFF;
        }

        .navbar-custom {
            background-color: var(--primary-color) !important;
        }

        .text-primary-custom {
            color: var(--primary-color) !important;
        }

        .border-primary-custom {
            border-color: var(--primary-color) !important;
        }

        .btn-outline-primary-custom {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary-custom:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .sidebar {
            min-height: calc(100vh - 56px);
        }

        /* Standardized Bootstrap-style table */
        .table {
            width: 100%;
            border-collapse: collapse;
            color: #212529;
            font-size: 0.875rem;
            background-color: #fff;
        }

        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: middle;
            border-top: 1px solid #dee2e6;
        }

        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
            border-top: none;
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.75rem;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }

        .table tbody tr:nth-child(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .btn-edit {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 0.2rem;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
        }

        .btn-edit:hover {
            background-color: #0056b3;
            border-color: #004085;
            color: white;
            text-decoration: none;
        }

        .search-container {
            position: relative;
        }

        .search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: var(--primary-color);
            padding: 4px 8px;
        }

        .search-btn:hover {
            color: #2a6adf;
        }

        /* Admin username styling */
        .admin-username {
            color: #fff;
            font-weight: 500;
            margin-right: 1rem;
        }

        /* Logout link styling with hover effect */
        .logout-link {
            color: #ffcccb;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .logout-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* Remove underlines from sidebar navigation */
        .sidebar .nav-link {
            text-decoration: none !important;
        }

        .sidebar .nav-link:hover {
            text-decoration: none !important;
        }
    </style>
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid px-3">
            <span class="navbar-brand fw-bold fs-5 mb-0">EVENTLY</span>
            <div class="d-flex align-items-center">
                <a href="dashboardadmin.php" class="admin-username">DASHBOARD</a>
                <a href="#" class="logout-link">
                    LOG OUT
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 col-lg-2 sidebar bg-white p-4">
                <h5 class="fw-bold text-primary-custom mb-4">KELOLA</h5>
                <nav class="nav flex-column">
                    <a href="daftarevent.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3" href="#">
                        DAFTAR EVENT
                    </a>
                    <a href="daftaruser.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3" href="#">
                        DAFTAR USER
                    </a>
                    <a href="pemesanantiket.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3" href="#">
                        PEMESANAN TIKET
                    </a>
                    <a href="pembayaran.php" class="nav-link text-primary-custom fw-semibold p-0 mb-3" href="#">
                        PEMBAYARAN USER
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="text-primary-custom fw-bold">DAFTAR USER</h2>

                    <form class="search-container" method="GET" style="width: 250px;">
                        <div class="input-group">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search user..."
                                value="<?php echo htmlspecialchars($searchTerm); ?>"
                                style="padding-right: 40px;">
                            <button class="search-btn" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <?php if (!empty($searchTerm)): ?>
                    <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
                        <span>
                            <i class="fas fa-info-circle me-2"></i>
                            Showing <?php echo count($filteredUsers); ?> result(s) for "<?php echo htmlspecialchars($searchTerm); ?>"
                        </span>
                        <a href="?" class="btn btn-sm btn-outline-secondary">Clear Search</a>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <?php if (empty($filteredUsers)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No users found</h5>
                                <p class="text-muted">Try adjusting your search criteria</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-center" style="width: 60px;">NO.</th>
                                            <th scope="col">NAMA</th>
                                            <th scope="col">EMAIL</th>
                                            <th scope="col">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filteredUsers as $index => $user): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark fw-semibold"><?php echo $index + 1; ?></span>
                                                </td>
                                                <td class="fw-medium text-dark"><?php echo htmlspecialchars($user['nama_user']); ?></td>
                                                <td class="text-muted"><?php echo htmlspecialchars($user['email_user']); ?></td>
                                                <td>
                                                    <select class="form-select form-select-sm status-select" data-user-id="<?php echo $user['id_user']; ?>" style="width: 100px; display: inline-block; vertical-align: middle;">
                                                        <option value="aktif" <?php if ($user['status'] == 'aktif') echo 'selected'; ?>>Aktif</option>
                                                        <option value="nonaktif" <?php if ($user['status'] == 'nonaktif') echo 'selected'; ?>>Nonaktif</option>
                                                    </select>
                                                    <button class="btn btn-sm btn-primary ms-2 btn-update-status" data-user-id="<?php echo $user['id_user']; ?>" disabled>Ubah</button>
                                                </td>

                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($filteredUsers)): ?>
                    <div class="mt-3 text-muted">
                        <small>Showing <?php echo count($filteredUsers); ?> users</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Optional: Auto-submit search on input -->
    <script>
        let searchTimeout;
        const searchInput = document.querySelector('input[name="search"]');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 2 || this.value.length === 0) {
                        this.form.submit();
                    }
                }, 500);
            });
        }

        $(document).ready(function() {
            // Saat dropdown status berubah, aktifkan tombol Ubah yang sesuai
            $('.status-select').on('change', function() {
                const userId = $(this).data('user-id');
                const btnUpdate = $('.btn-update-status[data-user-id="' + userId + '"]');
                btnUpdate.prop('disabled', false);
            });

            // Saat tombol Ubah diklik, kirim AJAX update status
            $('.btn-update-status').on('click', function() {
                const userId = $(this).data('user-id');
                const select = $('.status-select[data-user-id="' + userId + '"]');
                const newStatus = select.val();
                const btn = $(this);

                btn.prop('disabled', true);
                btn.text('Menyimpan...');

                $.ajax({
                    url: 'update_status.php',
                    type: 'POST',
                    data: {
                        id_user: userId,
                        status: newStatus
                    },
                    success: function(response) {
                        alert("Status berhasil diupdate");
                        btn.text('Ubah');
                    },
                    error: function(xhr) {
                        alert("Gagal update status: " + xhr.responseText);
                        btn.prop('disabled', false);
                        btn.text('Ubah');
                    }
                });
            });
        });
    </script>
</body>

</html>