document.addEventListener('DOMContentLoaded', function() {
    console.log('Homepage script loaded');

    // === Carousel utama ===
    const carousel = document.querySelector('.carousel');
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.nav-dot');
  
    let currentIndex = 0;
    let intervalId;

    // Debug info
    console.log('Carousel elements found:', {
        carousel: !!carousel,
        slides: slides.length,
        dots: dots.length
    });

    function startCarousel() {
        if (slides.length > 1) {
            intervalId = setInterval(() => {
                currentIndex = (currentIndex + 1) % slides.length;
                updateCarousel();
            }, 5000);
        }
    }
  
    function updateCarousel() {
        if (carousel && slides.length > 0) {
            carousel.style.transform = `translateX(-${currentIndex * 100}%)`;
            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentIndex);
            });
        }
    }
  
    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            currentIndex = parseInt(dot.getAttribute('data-index'));
            clearInterval(intervalId);
            updateCarousel();
            startCarousel();
        });
    });
  
    if (slides.length > 0) {
        startCarousel();
    }

    // === Event Cards Management ===
    const eventCards = document.querySelectorAll('.event-card');
    console.log('Total event cards found:', eventCards.length);

    if (eventCards.length === 0) {
        console.log('No event cards found - this might be normal if no events are available');
    } else {
        console.log('Event cards loaded successfully');
        
        // Add hover effects and interactions
        eventCards.forEach((card, index) => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }

    // === Event Cards Slider (if needed for future carousel implementation) ===
    const eventContainer = document.querySelector('.event-cards-container');
    const eventSlides = document.querySelectorAll('.event-cards-slide');
    const eventDots = document.querySelectorAll('.event-dot');
  
    let currentEventIndex = 0;
    let eventIntervalId;

    if (eventContainer && eventSlides.length > 0) {
        function startEventSlider() {
            eventIntervalId = setInterval(() => {
                currentEventIndex = (currentEventIndex + 1) % eventSlides.length;
                updateEventSlider();
            }, 6000);
        }
  
        function updateEventSlider() {
            eventContainer.style.transform = `translateX(-${currentEventIndex * 100}%)`;
            eventDots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentEventIndex);
            });
        }
  
        eventDots.forEach(dot => {
            dot.addEventListener('click', () => {
                currentEventIndex = parseInt(dot.getAttribute('data-index'));
                clearInterval(eventIntervalId);
                updateEventSlider();
                startEventSlider();
            });
        });
  
        startEventSlider();
    }

    // === Sidebar Toggle & Klik di Luar Sidebar ===
    const burgerBtn = document.querySelector('.burger');
    const sidebar = document.getElementById('userSidebar');
    const logoutBtn = document.querySelector('.logout-btn');

    function handleClickOutside(event) {
        if (sidebar && !sidebar.contains(event.target) && burgerBtn && !burgerBtn.contains(event.target)) {
            sidebar.classList.add('hidden');
            document.removeEventListener('click', handleClickOutside);
        }
    }

    if (burgerBtn && sidebar) {
        burgerBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Hindari klik dianggap klik di luar
            sidebar.classList.toggle('hidden');

            if (!sidebar.classList.contains('hidden')) {
                setTimeout(() => {
                    document.addEventListener('click', handleClickOutside);
                }, 0);
            } else {
                document.removeEventListener('click', handleClickOutside);
            }
        });
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (confirm("Apakah Anda yakin ingin logout?")) {
                alert("Anda telah logout!");
                // window.location.href = "login.html"; // kalau ada halaman login
            }
        });
    }

    // === Image Loading Error Handling ===
    const eventImages = document.querySelectorAll('.event-image');
    eventImages.forEach(img => {
        // Check if background image fails to load
        const bgImage = new Image();
        const bgUrl = img.style.backgroundImage.slice(4, -1).replace(/"/g, "");
        
        if (bgUrl && bgUrl !== '') {
            bgImage.onerror = function() {
                console.log('Failed to load image:', bgUrl);
                img.style.backgroundImage = "url('data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='200' viewBox='0 0 300 200'%3E%3Crect width='300' height='200' fill='%23f0f0f0'/%3E%3Ctext x='150' y='100' text-anchor='middle' fill='%23666' font-family='Arial' font-size='16'%3EImage Not Available%3C/text%3E%3C/svg%3E')";
            };
            bgImage.src = bgUrl;
        }
    });

    // === Pagination (if needed in future updates) ===
    const paginationContainer = document.getElementById('paginationContainer');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const paginationNumbers = document.getElementById('paginationNumbers');

    if (paginationContainer && eventCards.length > 6) {
        // Show pagination if more than 6 events
        paginationContainer.style.display = 'flex';
        
        // Implement pagination logic here if needed
        console.log('Pagination would be useful with', eventCards.length, 'events');
    }

    // === Debugging Information ===
    console.log('Homepage initialization complete:', {
        carouselSlides: slides.length,
        eventCards: eventCards.length,
        hasSidebar: !!sidebar,
        hasBurgerBtn: !!burgerBtn
    });

    // === Error Handling for Missing Elements ===
    if (!carousel) {
        console.warn('Carousel container not found');
    }
    
    if (slides.length === 0) {
        console.warn('No carousel slides found');
    }

    // === Smooth Scrolling for Internal Links ===
    const internalLinks = document.querySelectorAll('a[href^="#"]');
    internalLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // === Lazy Loading for Event Images (Performance Enhancement) ===
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    // Image is already loaded via CSS background-image
                    // This is just for tracking visibility
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });

        eventImages.forEach(img => {
            imageObserver.observe(img);
        });
    }
});