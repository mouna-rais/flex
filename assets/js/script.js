// Carousel functionality
document.addEventListener('DOMContentLoaded', function() {
    // Simple carousel auto-scroll
    const carousel = document.querySelector('.carousel');
    if (carousel) {
        let scrollAmount = 0;
        const scrollStep = 300;
        const maxScroll = carousel.scrollWidth - carousel.clientWidth;
        
        function autoScroll() {
            scrollAmount += scrollStep;
            if (scrollAmount > maxScroll) {
                scrollAmount = 0;
            }
            carousel.scrollTo({
                left: scrollAmount,
                behavior: 'smooth'
            });
        }
        
        setInterval(autoScroll, 3000);
    }
    
    // Toggle mobile menu (if added later)
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
        });
    }
    
    // Image lazy loading
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for browsers without IntersectionObserver
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
        });
    }
});