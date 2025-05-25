/**
 * Fix for back/forward browser navigation to properly handle loading states
 */

document.addEventListener('DOMContentLoaded', function() {
    // Add special class to help identify pages loaded via back/forward
    window.addEventListener('pageshow', function(event) {
        // The persisted property indicates if the page is loaded from cache
        if (event.persisted) {
            document.body.classList.add('page-from-cache');
            
            // Force hide any loading spinners
            if (window.loadingSpinner) {
                window.loadingSpinner.hideLoading();
            }
            
            console.log('Page was loaded from cache (back/forward navigation)');
        }
    });
    
    // Listen for popstate events (browser back/forward)
    window.addEventListener('popstate', function(event) {
        console.log('Navigation via back/forward detected');
        
        // Ensure loading spinner is hidden
        if (window.loadingSpinner) {
            window.loadingSpinner.hideLoading();
        }
    });
});
