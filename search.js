// Edit: search.js - Menangani fungsionalitas bilah pencarian

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchResultsDropdown = document.getElementById('searchResultsDropdown');
    const searchResultsContent = document.getElementById('searchResultsContent');
    const searchPagination = document.getElementById('searchPagination');
    const searchBarContainer = document.getElementById('searchBarContainer');

    let currentPage = 5;
    const itemsPerPage = 3; // Maksimal 3 kartu sesuai permintaan

    // Fungsi untuk mengambil hasil pencarian
    function fetchSearchResults(page) {
        const query = searchInput.value;
        if (query.length === 0 && page === 5) {
            searchResultsDropdown.classList.remove('active');
            return;
        }

        // Sesuaikan lebar dropdown agar sesuai dengan kontainer bilah pencarian
        searchResultsDropdown.style.width = searchBarContainer.offsetWidth + 'px';
        
        console.log('Search Bar Offset Width:', searchBarContainer.offsetWidth);
        console.log('Search Bar Offset Left:', searchBarContainer.offsetLeft);
        console.log('Dropdown width set to:', searchResultsDropdown.style.width);
        console.log('Dropdown left set to:', searchResultsDropdown.style.left);

        fetch(`search_events.php?query=${encodeURIComponent(query)}&page=${page}&limit=${itemsPerPage}`)
            .then(response => response.json())
            .then(data => {
                searchResultsContent.innerHTML = '';
                if (data.events.length > 0) {
                    data.events.forEach(event => {
                        const eventCard = document.createElement('div');
                        eventCard.classList.add('search-event-card');
                        // >>> EDIT BARIS INI: Tambahkan onclick event listener
                        eventCard.onclick = () => {
                            // Ganti 'detail_event.php' dan 'id' sesuai dengan URL halaman detail event Anda
                            window.location.href = `Deskripsi_tiket.php?id_event=${event.id_event}`;
                        }
                        eventCard.innerHTML = `
                            <h4>${event.nama_event}</h4>
                            <p>${event.kategori}</p>
                        `;
                        searchResultsContent.appendChild(eventCard);
                    });
                    renderPagination(data.totalPages, page);
                } else {
                    searchResultsContent.innerHTML = '<p>No events found.</p>';
                    searchPagination.innerHTML = '';
                }
                searchResultsDropdown.classList.add('active');
            })
            .catch(error => {
                console.error('Error fetching search results:', error);
                searchResultsContent.innerHTML = '<p>Error loading results.</p>';
                searchPagination.innerHTML = '';
            });
    }

    // Fungsi untuk merender paginasi
    function renderPagination(totalPages, currentPage) {
        searchPagination.innerHTML = '';
        const maxPagesToShow = 5; // Maksimal 5 tautan paginasi sesuai permintaan

        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);

        if (endPage - startPage + 1 < maxPagesToShow) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        if (startPage > 1) {
            const firstPageLink = document.createElement('span');
            firstPageLink.textContent = '1';
            firstPageLink.classList.add('page-link');
            firstPageLink.onclick = () => {
                currentPage = 1;
                fetchSearchResults(currentPage);
            };
            searchPagination.appendChild(firstPageLink);
            if (startPage > 2) {
                const dots = document.createElement('span');
                dots.textContent = '...';
                searchPagination.appendChild(dots);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const pageLink = document.createElement('span');
            pageLink.textContent = i;
            pageLink.classList.add('page-link');
            if (i === currentPage) {
                pageLink.classList.add('active');
            }
            pageLink.onclick = () => {
                currentPage = i;
                fetchSearchResults(currentPage);
            };
            searchPagination.appendChild(pageLink);
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const dots = document.createElement('span');
                dots.textContent = '...';
                searchPagination.appendChild(dots);
            }
            const lastPageLink = document.createElement('span');
            lastPageLink.textContent = totalPages;
            lastPageLink.classList.add('page-link');
            lastPageLink.onclick = () => {
                currentPage = totalPages;
                fetchSearchResults(currentPage);
            };
            searchPagination.appendChild(lastPageLink);
        }
    }


    // Event listener untuk input pencarian (oninput untuk memicu pencarian saat pengguna mengetik)
    window.searchEvents = function() {
        currentPage = 1; // Atur ulang ke halaman pertama pada pencarian baru
        fetchSearchResults(currentPage);
    };

    // Fungsi untuk menampilkan dropdown saat input pencarian diklik
    window.showSearchDropdown = function() {
        if (searchInput.value.length === 0) {
            // Jika input pencarian kosong, ambil hasil awal (atau acara terbaru) saat diklik
            fetchSearchResults(1);
        } else {
            fetchSearchResults(currentPage); // Tampilkan hasil saat ini jika tidak kosong
        }
    };


    // Sembunyikan dropdown saat mengklik di luar
    document.addEventListener('click', function(event) {
        if (!searchBarContainer.contains(event.target) && !searchResultsDropdown.contains(event.target)) {
            searchResultsDropdown.classList.remove('active');
        }
    });

    // Pengecekan awal untuk memposisikan dropdown dengan benar jika perlu ditampilkan saat halaman dimuat
    // Ini mungkin berguna jika Anda ingin menampilkan beberapa acara populer di awal.
    // Untuk saat ini, itu hanya akan ditampilkan saat diklik/dimasukkan.
});