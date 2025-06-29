<?php
session_start();
include 'config/config.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Loket</title>
    <link rel="stylesheet" href="style/searchbar.css">
    <link rel="stylesheet" href="style/hasil_pembayaran.css">
    <link rel="stylesheet" href="style/sidebar.css">
</head>

<body>
   <?php include 'komponen/navbar.php'; ?>

    <main>
        <div class="container">
            <div class="header-title">Konfirmasi Pembayaran Anda</div>
            
            <!-- QR Code dan Upload Section berdampingan -->
            <div class="payment-section">
                <div class="qr-container">
                    <img src="./assets/QR code.jpg" alt="QR Code Pembayaran" />
                </div>

                <!-- Upload Section -->
                <div class="upload-section">
                    <h3>Upload Bukti Pembayaran</h3>
                    <div class="file-upload">
                        <input type="file" id="paymentProof" class="file-input" accept="image/*">
                        <div id="dragDropArea" class="drag-drop-area">
                            <div class="drag-drop-icon">📤</div>
                            <div class="drag-drop-text">Seret gambar ke sini</div>
                            <div class="drag-drop-subtext">atau</div>
                            <div class="or-text"></div>
                            <label for="paymentProof" class="browse-button">Pilih File</label>
                        </div>
                    </div>
                    <div id="uploadStatus" class="upload-status"></div>
                    <div class="file-name" id="fileName"></div>
                </div>
            </div>

            <button id="previewButton" class="preview-button" style="display: none;">
                Lihat Preview
            </button>

            <!-- Modal untuk preview gambar -->
            <div id="imageModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <img id="modalImage" class="modal-image" alt="Preview">
                    <div class="modal-filename" id="modalFilename"></div>
                </div>
            </div>

            <!-- Instruksi pembayaran di bawah -->
            <div class="instructions">
                <ol>
                    <li>Buka aplikasi E-Wallet atau M-Banking di handphone kamu</li>
                    <li>Klik Scan QR</li>
                    <li>Arahkan kamera kamu ke Kode QR</li>
                    <li>Periksa kembali detail pembayaran</li>
                    <li>Tekan Pay untuk menyelesaikan transaksi</li>
                </ol>
            </div>

            <button class="konfirmasi-button" id="konfirmasi-button" onclick="confirmPayment()">KONFIRMASI</button>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 EVENTLY. All Rights Reserved.</p>
        <ul class="footer-links">
            <li><a href="#">Contact-Admin</a></li>
        </ul>
    </footer>

    <script>
        // Modal elements
        const modal = document.getElementById('imageModal');
        const previewButton = document.getElementById('previewButton');
        const modalImage = document.getElementById('modalImage');
        const modalFilename = document.getElementById('modalFilename');
        const closeModal = document.getElementsByClassName('close')[0];

        // Upload elements
        const dragDropArea = document.getElementById('dragDropArea');
        const fileInput = document.getElementById('paymentProof');
        const statusDiv = document.getElementById('uploadStatus');
        const fileNameDiv = document.getElementById('fileName');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dragDropArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            dragDropArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dragDropArea.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        dragDropArea.addEventListener('drop', handleDrop, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            dragDropArea.classList.add('drag-over');
        }

        function unhighlight(e) {
            dragDropArea.classList.remove('drag-over');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                handleFile(files[0]);
            }
        }

        // File input change handler
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                handleFile(file);
            }
        });

        function handleFile(file) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                showStatus('File harus berupa gambar!', 'error');
                resetUpload();
                return;
            }
            
            // Validate file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                showStatus('Ukuran file maksimal 5MB!', 'error');
                resetUpload();
                return;
            }
            
            // Show preview and update UI
            const reader = new FileReader();
            reader.onload = function(e) {
                modalImage.src = e.target.result; // Set modal image source
                modalFilename.textContent = file.name; // Set modal filename
                
                showStatus('Gambar berhasil dipilih!', 'success');
                fileNameDiv.textContent = `File: ${file.name}`;
                
                // Show Preview Button
                previewButton.style.display = 'block';
                
                // Update drag drop area to show success state
                dragDropArea.innerHTML = `
                    <div class="drag-drop-icon">✅</div>
                    <div class="drag-drop-text">File berhasil dipilih</div>
                    <div class="drag-drop-subtext">${file.name}</div>
                    <div class="or-text">Gambar Anda telah terupload</div>
                    <label for="paymentProof" class="browse-button">Ganti File</label>
                `;
                
                // Change the border to solid green to show success
                dragDropArea.style.border = '2px solid #28a745';
                dragDropArea.style.background = 'rgba(40, 167, 69, 0.1)';
            };
            reader.readAsDataURL(file);
        }

        function showStatus(message, type) {
            statusDiv.textContent = message;
            statusDiv.className = `upload-status upload-${type}`;
            statusDiv.style.display = 'block';
            
            // Auto hide status after 3 seconds
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 3000);
        }

        function resetUpload() {
            previewButton.style.display = 'none';
            fileNameDiv.textContent = '';
            fileInput.value = '';
            
            // Reset drag drop area
            dragDropArea.innerHTML = `
                <div class="drag-drop-icon">📤</div>
                <div class="drag-drop-text">Seret gambar ke sini</div>
                <div class="drag-drop-subtext">atau</div>
                <div class="or-text"></div>
                <label for="paymentProof" class="browse-button">Pilih File</label>
            `;
            
            // Reset styling
            dragDropArea.style.border = '2px dashed rgba(255, 255, 255, 0.5)';
            dragDropArea.style.background = 'rgba(255, 255, 255, 0.05)';
        }

        // Modal functionality
        previewButton.onclick = function() {
            modal.style.display = 'block';
        }

        // Close modal when X is clicked
        closeModal.onclick = function() {
            modal.style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Confirm payment function (DIPERBAIKI)
        function confirmPayment() {
            // Validasi file terlebih dahulu
            if (!fileInput.files[0]) {
                alert('Silakan upload bukti pembayaran terlebih dahulu!');
                return;
            }
            
            // Ambil ID pesanan dari sessionStorage atau URL parameter
            const id_pesanan = sessionStorage.getItem('id_pesanan') || 
                              new URLSearchParams(window.location.search).get('id_pesanan');
            
            if (!id_pesanan) {
                alert('Data pesanan tidak ditemukan! Silakan ulangi proses pemesanan.');
                // Redirect ke halaman sebelumnya atau homepage
                window.location.href = 'homepage.php';
                return;
            }
            
            if (confirm('Apakah Anda yakin ingin mengkonfirmasi pembayaran ini?')) {
                // Disable button untuk mencegah double click
                const confirmButton = document.getElementById('konfirmasi-button');
                confirmButton.disabled = true;
                confirmButton.textContent = 'Sedang memproses...';
                
                // Siapkan FormData untuk upload
                const formData = new FormData();
                formData.append('bukti_bayar', fileInput.files[0]);
                formData.append('id_pesanan', id_pesanan);
                formData.append('action', 'upload_bukti');
                
                // Kirim request ke server
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
                        // Hapus data session
                        sessionStorage.removeItem('id_pesanan');
                        // Redirect ke halaman sukses atau homepage
                        window.location.href = 'homepage.php?payment_success=1';
                    } else {
                        alert('Gagal mengupload bukti pembayaran: ' + (data.message || 'Terjadi kesalahan'));
                        // Re-enable button
                        confirmButton.disabled = false;
                        confirmButton.textContent = 'KONFIRMASI';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mengupload bukti pembayaran. Silakan coba lagi.');
                    // Re-enable button
                    confirmButton.disabled = false;
                    confirmButton.textContent = 'KONFIRMASI';
                });
            }
        }

        // Check if there's payment data on page load
        document.addEventListener('DOMContentLoaded', function() {
            const id_pesanan = sessionStorage.getItem('id_pesanan') || 
                              new URLSearchParams(window.location.search).get('id_pesanan');
            
            if (!id_pesanan) {
                // Jika tidak ada data pesanan, tampilkan peringatan
                console.warn('No order ID found');
                // Bisa ditambahkan redirect atau peringatan di sini
            }
        });
    </script>
</body>
</html>