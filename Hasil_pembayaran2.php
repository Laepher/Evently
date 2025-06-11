<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Loket</title>
    <link rel="stylesheet" href="style/searchbar.css">
    <link rel="stylesheet" href="style/hasil_pembayaran2.css">
    <link rel="stylesheet" href="style/sidebar.css">
</head>

<body>
    <?php include 'komponen/navbar.php'; ?>

    <div class="container">
        <div class="header-title">Konfirmasi Pembayaran Anda</div>

        <!-- Top section: Rekening dan Upload bersebelahan -->
        <div class="top-section">
            <!-- Rekening container - sebelah kiri -->
            <div class="rekening-container">
                <p class="rekening-title">Silakan transfer ke salah satu rekening berikut:</p>
                <ul class="rekening-list">
                    <li><span class="bank">BCA</span>: <span class="nomor">1234567890</span> <span class="nama">a.n. Amanda Namira</span></li>
                    <li><span class="bank">Mandiri</span>: <span class="nomor">9876543210</span> <span class="nama">a.n. Amanda Namira</span></li>
                    <li><span class="bank">BRI</span>: <span class="nomor">1122334455</span> <span class="nama">a.n. Amanda Namira</span></li>
                </ul>
            </div>

            <!-- Upload container - sebelah kanan -->
            <div class="upload-container">
                <p class="upload-title">Upload Bukti Pembayaran</p>
                <label class="file-upload-container" for="paymentProof">
                    <div class="file-upload-input">
                        <div class="upload-text">📎 Klik untuk pilih file atau drag & drop</div>
                    </div>
                    <div class="file-info" id="fileInfo">
                        <span id="fileName"></span>
                    </div>
                    <p class="upload-note">Format yang didukung: JPG, PNG, PDF (Max 5MB)</p>
                    <input type="file" id="paymentProof" name="paymentProof" accept="image/*,.pdf" onchange="handleFileSelect(this)">
                </label>
            </div>
        </div>

        <!-- Instructions - di tengah bawah -->
        <div class="instructions">
            <p class="instructions-title">Cara Transfer:</p>
            <ol>
                <li>Buka aplikasi M-Banking di handphone kamu</li>
                <li>Pilih menu Transfer</li>
                <li>Pilih jenis transfer: Antar Bank atau Sesama Bank (sesuai dengan rekening tujuan)</li>
                <li>Masukkan nomor rekening tujuan</li>
                <li>Masukkan jumlah nominal pembayaran</li>
                <li>Periksa kembali nama penerima dan detail transfer</li>
                <li>Jika sudah benar, tekan Kirim/Transfer</li>
                <li>Masukkan PIN/OTP untuk menyelesaikan transaksi</li>
            </ol>
        </div>

        <div class="button">
            <button class="confirm-button" id="confirmButton">Konfirmasi Pembayaran</button>
        </div>

        <div class="extra">
            <p>Temukan event menarik lainnya di halaman <a href="homepage.php">Beranda</a>.</p>
        </div>
    </div>

    <script>
        function handleFileSelect(input) {
            const file = input.files[0];
            const fileInfo = document.getElementById('fileInfo');
            const fileName = document.getElementById('fileName');
            const uploadText = document.querySelector('.upload-text');

            if (file) {
                // Check file size (5MB limit)
                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                if (file.size > maxSize) {
                    alert('File terlalu besar! Maksimal 5MB.');
                    input.value = '';
                    resetUploadDisplay();
                    return;
                }

                // Check file type (Allow images and PDF)
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Format file tidak didukung! Gunakan JPG, PNG, atau PDF.');
                    input.value = '';
                    resetUploadDisplay();
                    return;
                }

                // Show file info
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                fileName.textContent = `📄 ${file.name} (${fileSize} MB)`;
                fileInfo.classList.add('show');
                uploadText.textContent = '✅ File berhasil dipilih';
                uploadText.style.color = '#90ff90';

                // Change border color to success
                const uploadInput = document.querySelector('.file-upload-input');
                uploadInput.style.borderColor = 'rgba(144, 255, 144, 0.8)';
                uploadInput.style.background = 'rgba(144, 255, 144, 0.1)';

            } else {
                resetUploadDisplay();
            }
        }

        function resetUploadDisplay() {
            const fileInfo = document.getElementById('fileInfo');
            const uploadText = document.querySelector('.upload-text');
            const uploadInput = document.querySelector('.file-upload-input');
            
            // Reset if no file selected
            fileInfo.classList.remove('show');
            uploadText.textContent = '📎 Klik untuk pilih file atau drag & drop';
            uploadText.style.color = 'rgba(255, 255, 255, 0.9)';
            uploadInput.style.borderColor = 'rgba(255, 255, 255, 0.5)';
            uploadInput.style.background = 'rgba(255, 255, 255, 0.1)';
        }

        // Handle drag and drop
        document.addEventListener('DOMContentLoaded', function() {
            const uploadContainer = document.querySelector('.file-upload-input');

            if (uploadContainer) {
                // Prevent default drag behaviors
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    uploadContainer.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });

                // Highlight drop area when item is dragged over it
                ['dragenter', 'dragover'].forEach(eventName => {
                    uploadContainer.addEventListener(eventName, highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    uploadContainer.addEventListener(eventName, unhighlight, false);
                });

                // Handle dropped files
                uploadContainer.addEventListener('drop', handleDrop, false);
            }

            // Event listener untuk tombol konfirmasi
            const confirmButton = document.getElementById('confirmButton');
            if (confirmButton) {
                confirmButton.addEventListener('click', confirmPayment);
            }

            // Check order data on page load
            checkOrderData();
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            const uploadContainer = document.querySelector('.file-upload-input');
            uploadContainer.style.borderColor = 'rgba(255, 255, 255, 1)';
            uploadContainer.style.background = 'rgba(255, 255, 255, 0.2)';
        }

        function unhighlight(e) {
            const uploadContainer = document.querySelector('.file-upload-input');
            uploadContainer.style.borderColor = 'rgba(255, 255, 255, 0.5)';
            uploadContainer.style.background = 'rgba(255, 255, 255, 0.1)';
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            const fileInput = document.getElementById('paymentProof');

            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect(fileInput);
            }
        }

        // Check if order data exists
        function checkOrderData() {
            const id_pesanan = sessionStorage.getItem('id_pesanan') || 
                              new URLSearchParams(window.location.search).get('id_pesanan');
            
            if (!id_pesanan) {
                console.warn('No order ID found');
                // Optional: Show warning or redirect
                // alert('Data pesanan tidak ditemukan. Silakan ulangi proses pemesanan.');
                // window.location.href = 'homepage.php';
            } else {
                console.log('Order ID found:', id_pesanan);
            }
        }

        // Fungsi konfirmasi pembayaran yang diperbaiki
        function confirmPayment() {
            const fileInput = document.getElementById('paymentProof');
            
            if (!fileInput.files[0]) {
                alert('Silakan upload bukti pembayaran terlebih dahulu!');
                return;
            }
            
            // Get order ID from sessionStorage or URL parameter
            const id_pesanan = sessionStorage.getItem('id_pesanan') || 
                              new URLSearchParams(window.location.search).get('id_pesanan');
            
            if (!id_pesanan) {
                alert('Data pesanan tidak ditemukan! Silakan ulangi proses pemesanan.');
                window.location.href = 'homepage.php';
                return;
            }
            
            if (confirm('Apakah Anda yakin ingin mengkonfirmasi pembayaran ini?')) {
                // Disable button to prevent double submission
                const confirmButton = document.getElementById('confirmButton');
                const originalText = confirmButton.textContent;
                confirmButton.disabled = true;
                confirmButton.textContent = 'Sedang memproses...';
                
                // Prepare FormData
                const formData = new FormData();
                formData.append('bukti_bayar', fileInput.files[0]);
                formData.append('id_pesanan', id_pesanan);
                formData.append('action', 'upload_bukti');
                
                // Send to server
                fetch('process_pembayaran.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Pembayaran berhasil dikonfirmasi! Terima kasih.');
                        // Clear session data
                        sessionStorage.removeItem('id_pesanan');
                        // Redirect to success page
                        window.location.href = 'homepage.php?payment_success=1';
                    } else {
                        alert('Gagal mengupload bukti pembayaran: ' + (data.message || 'Terjadi kesalahan'));
                        // Re-enable button on error
                        confirmButton.disabled = false;
                        confirmButton.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengupload bukti pembayaran. Silakan coba lagi.');
                    // Re-enable button on error
                    confirmButton.disabled = false;
                    confirmButton.textContent = originalText;
                });
            }
        }

        // Menu toggle functionality (if exists)
        const toggleButton = document.querySelector('.menu-toggle');
        const navLinks = document.querySelector('.nav-links');

        if (toggleButton && navLinks) {
            toggleButton.addEventListener('click', () => {
                navLinks.classList.toggle('active');
            });
        }
    </script>
    <script src="Homepage_script.js"></script>
    <?php include 'komponen/footer.php'; ?>
</body>

</html>