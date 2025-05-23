document.addEventListener('DOMContentLoaded', function() {
    const stickyHeader = document.querySelector('.sticky-header');
    
    if (stickyHeader) {
        const originalOffset = stickyHeader.offsetTop;
        
        function handleScroll() {
            if (window.scrollY > originalOffset) {
                stickyHeader.classList.add('is-sticky');
            } else {
                stickyHeader.classList.remove('is-sticky');
            }
        }
        
        window.addEventListener('scroll', handleScroll);
    }
});
