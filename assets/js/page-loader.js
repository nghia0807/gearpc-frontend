/**
 * GearPC Page Loader
 * Handles page transitions and loading states for all pages
 */

// Only control page transition loading
document.addEventListener('DOMContentLoaded', function() {
    // Add class to body
    document.body.classList.add('page-loading');
    
    // Hide the loading spinner when page is ready
    if (window.loadingSpinner) {
        window.loadingSpinner.hideLoading();
    }
});

// Handle full page load completion
window.addEventListener('load', function() {
    document.body.classList.remove('page-loading');
});

// Modified: Don't show loading spinner on beforeunload (causes issues with back button)
// window.addEventListener('beforeunload', function() {
//     if (window.loadingSpinner) {
//         window.loadingSpinner.showLoading();
//     }
// });

// Hide loading spinner when using browser back/forward buttons
window.addEventListener('pageshow', function(event) {
    // The persisted property indicates if the page is loaded from cache (browser back/forward)
    if (event.persisted) {
        if (window.loadingSpinner) {
            window.loadingSpinner.hideLoading();
        }
        document.body.classList.remove('page-loading');
        console.log('Page loaded from cache (back/forward navigation)');
    }
});

// Global functions for showing and hiding loading spinner
function showPageLoading() {
    if (window.loadingSpinner) {
        window.loadingSpinner.showLoading();
    }
}

function hidePageLoading() {
    if (window.loadingSpinner) {
        window.loadingSpinner.hideLoading();
    }
}
