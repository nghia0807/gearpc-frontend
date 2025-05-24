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

// Show loading spinner only when navigating between pages
window.addEventListener('beforeunload', function() {
    if (window.loadingSpinner) {
        window.loadingSpinner.showLoading();
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
