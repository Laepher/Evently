document.addEventListener('DOMContentLoaded', function() {
    // === Carousel utama ===
    const carousel = document.querySelector('.carousel');
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.nav-dot');
    
    let currentIndex = 0;
    let intervalId;
    
    function startCarousel() {
        intervalId = setInterval(() => {
            currentIndex = (currentIndex + 1) % slides.length;
            updateCarousel();
        }, 5000);
    }
    
    function updateCarousel() {
        carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
    }
    
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            currentIndex = parseInt(dot.getAttribute('data-index'));
            clearInterval(intervalId);
            updateCarousel();
            startCarousel();
        });
    });
    
    startCarousel();
    
    // === Event Cards Pagination System ===
    class EventCardsPagination {
        constructor() {
            this.currentPage = 0;
            this.cardsPerPage = this.getCardsPerPage();
            this.allCards = Array.from(document.querySelectorAll('.event-card'));
            this.totalPages = Math.ceil(this.allCards.length / this.cardsPerPage);
            
            this.paginationContainer = document.getElementById('paginationContainer');
            this.paginationNumbers = document.getElementById('paginationNumbers');
            this.prevBtn = document.getElementById('prevBtn');
            this.nextBtn = document.getElementById('nextBtn');
            
            this.init();
        }
        
        getCardsPerPage() {
            const width = window.innerWidth;
            if (width >= 1200) return 10; // 5x2
            if (width >= 900) return 8;   // 4x2
            if (width >= 700) return 6;   // 3x2
            if (width >= 480) return 4;   // 2x2
            return 3; // 1x3 for mobile
        }
        
        init() {
            if (this.totalPages <= 1) {
                this.paginationContainer.style.display = 'none';
                return;
            }
            
            this.paginationContainer.style.display = 'flex';
            this.createPaginationNumbers();
            this.attachEventListeners();
            this.showPage(0);
        }
        
        createPaginationNumbers() {
            this.paginationNumbers.innerHTML = '';
            for (let i = 0; i < this.totalPages; i++) {
                const numberBtn = document.createElement('button');
                numberBtn.className = 'pagination-number';
                numberBtn.textContent = i + 1;
                numberBtn.onclick = () => this.goToPage(i);
                this.paginationNumbers.appendChild(numberBtn);
            }
        }
        
        attachEventListeners() {
            this.prevBtn.onclick = () => this.changePage(-1);
            this.nextBtn.onclick = () => this.changePage(1);
        }
        
        showPage(pageIndex) {
            const startIndex = pageIndex * this.cardsPerPage;
            const endIndex = startIndex + this.cardsPerPage;
            
            // Hide all cards with animation
            this.allCards.forEach((card, index) => {
                if (index >= startIndex && index < endIndex) {
                    card.classList.remove('hidden');
                    card.style.display = 'flex';
                } else {
                    card.classList.add('hidden');
                    setTimeout(() => {
                        if (card.classList.contains('hidden')) {
                            card.style.display = 'none';
                        }
                    }, 300);
                }
            });
            
            this.updatePaginationUI(pageIndex);
        }
        
        updatePaginationUI(pageIndex) {
            // Update pagination numbers
            const numberButtons = this.paginationNumbers.querySelectorAll('.pagination-number');
            numberButtons.forEach((btn, index) => {
                btn.classList.toggle('active', index === pageIndex);
            });
            
            // Update navigation buttons
            this.prevBtn.disabled = pageIndex === 0;
            this.nextBtn.disabled = pageIndex === this.totalPages - 1;
        }
        
        changePage(direction) {
            const newPage = this.currentPage + direction;
            if (newPage >= 0 && newPage < this.totalPages) {
                this.goToPage(newPage);
            }
        }
        
        goToPage(pageIndex) {
            if (pageIndex >= 0 && pageIndex < this.totalPages) {
                this.currentPage = pageIndex;
                this.showPage(pageIndex);
            }
        }
        
        // Method to handle window resize
        handleResize() {
            const newCardsPerPage = this.getCardsPerPage();
            if (newCardsPerPage !== this.cardsPerPage) {
                this.cardsPerPage = newCardsPerPage;
                this.totalPages = Math.ceil(this.allCards.length / this.cardsPerPage);
                
                // Reset to page 0 if current page doesn't exist anymore
                if (this.currentPage >= this.totalPages) {
                    this.currentPage = 0;
                }
                
                this.createPaginationNumbers();
                this.showPage(this.currentPage);
                
                // Show/hide pagination based on total pages
                if (this.totalPages <= 1) {
                    this.paginationContainer.style.display = 'none';
                    // Show all cards if no pagination needed
                    this.allCards.forEach(card => {
                        card.classList.remove('hidden');
                        card.style.display = 'flex';
                    });
                } else {
                    this.paginationContainer.style.display = 'flex';
                }
            }
        }
    }
    
    // Initialize pagination system
    let eventPagination;
    
    // Wait for cards to be loaded
    if (document.querySelectorAll('.event-card').length > 0) {
        eventPagination = new EventCardsPagination();
    }
    
    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (eventPagination) {
                eventPagination.handleResize();
            }
        }, 250);
    });
});