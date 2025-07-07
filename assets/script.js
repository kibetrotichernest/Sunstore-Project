// Initialize gallery modal
document.addEventListener('DOMContentLoaded', function() {
    // Handle thumbnail clicks
    document.querySelectorAll('.gallery-thumb').forEach(thumb => {
        thumb.addEventListener('click', function(e) {
            e.preventDefault();
            const index = parseInt(this.getAttribute('data-index'));
            const carousel = document.querySelector('#projectGalleryCarousel');
            const bsCarousel = new bootstrap.Carousel(carousel);
            bsCarousel.to(index);
        });
    });
});
// Initialize Fancybox for gallery
document.addEventListener('DOMContentLoaded', function() {
    // Gallery lightbox
    Fancybox.bind("[data-fancybox]", {
        Thumbs: {
            autoStart: false,
        },
        Toolbar: {
            display: {
                left: [],
                middle: [],
                right: ["close"],
            },
        },
    });
    
    // Smooth scrolling for page anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});
document.addEventListener('DOMContentLoaded', function() {
    // Press Ctrl+Shift+D to toggle debug info
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'D') {
            document.querySelector('.debug-info').style.display = 
                document.querySelector('.debug-info').style.display === 'none' ? 'block' : 'none';
        }
    });
});