/**
 * Loading spinner manager for GearPC
 * Controls showing/hiding loading overlay throughout the application
 */

class LoadingSpinner {    constructor() {
        // Get loading overlay element once the DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            this.loadingOverlay = document.getElementById('loading-overlay');
            
            // Hide loading overlay when page is fully loaded
            window.addEventListener('load', () => {
                this.hideLoading();
            });

            // Only show loading on page navigation
            this.setupPageNavigationLoading();
            
            // Setup AJAX request interception
            this.setupAjaxInterception();
        });
    }    /**
     * Show loading overlay
     */
    showLoading() {
        if (!this.loadingOverlay) return;
        this.loadingOverlay.classList.add('active');
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        if (!this.loadingOverlay) return;
        this.loadingOverlay.classList.remove('active');
    }

    /**
     * Setup event listeners for page navigation to show loading
     */
    setupPageNavigationLoading() {
        // Show loading when clicking on links that navigate to other pages
        document.addEventListener('click', (event) => {
            const target = event.target.closest('a');
            
            // Check if the clicked element is a link and not meant to open in a new tab/window
            if (target && target.tagName === 'A' && 
                !target.classList.contains('no-loading') &&
                !target.getAttribute('target') && 
                target.getAttribute('href') && 
                !target.getAttribute('href').startsWith('#') &&
                !target.getAttribute('href').startsWith('javascript:')) {
                
                // Don't show for download links
                if (target.getAttribute('download')) return;
                  // Show loading before navigating away
                this.showLoading();
            }
        });

        // Handle form submissions
        document.addEventListener('submit', (event) => {
            const form = event.target;
            
            // Don't show loading for forms with class 'no-loading'
            if (form.classList.contains('no-loading')) {
                return;
            }
            
            // Show loading when form is submitted
            this.showLoading();
        });

        // Handle history navigation (back/forward)
        window.addEventListener('beforeunload', () => {
            this.showLoading();
        });
    }    /**
     * Use this when starting an AJAX request
     */
    startRequest() {
        this.showLoading();
    }

    /**
     * Use this when AJAX request completes
     */
    endRequest() {
        this.hideLoading();
    }
    
    /**
     * Setup interception for AJAX requests to automatically show/hide loading spinner
     */
    setupAjaxInterception() {
        const self = this;
        const originalXHROpen = XMLHttpRequest.prototype.open;
        const originalXHRSend = XMLHttpRequest.prototype.send;
        
        // Count active AJAX requests
        let activeRequests = 0;
        
        // Override open method
        XMLHttpRequest.prototype.open = function() {
            this._loadingSpinnerUrl = arguments[1];
            return originalXHROpen.apply(this, arguments);
        };
        
        // Override send method
        XMLHttpRequest.prototype.send = function() {
            // Skip showing loading for certain requests (like analytics)
            const skipLoadingFor = [
                '/analytics', 
                '/log',
                '/ping',
                '/heartbeat'
            ];
            
            const shouldSkipLoading = skipLoadingFor.some(url => 
                this._loadingSpinnerUrl && this._loadingSpinnerUrl.includes(url)
            );
            
            if (!shouldSkipLoading) {
                activeRequests++;                if (activeRequests === 1) {
                    // Only show loading on first request
                    self.showLoading();
                }
                
                // Setup listeners for request completion
                this.addEventListener('load', completeRequest);
                this.addEventListener('error', completeRequest);
                this.addEventListener('abort', completeRequest);
            }
            
            function completeRequest() {
                if (!shouldSkipLoading) {
                    activeRequests--;
                    if (activeRequests === 0) {
                        // Only hide loading when all requests are done
                        self.hideLoading();
                    }
                }
            }
            
            return originalXHRSend.apply(this, arguments);
        };
        
        // Also intercept fetch API
        const originalFetch = window.fetch;
        window.fetch = function() {            // Show loading
            activeRequests++;
            if (activeRequests === 1) {
                self.showLoading();
            }
            
            // Call original fetch
            return originalFetch.apply(this, arguments)
                .then(response => {
                    // Handle successful response
                    activeRequests--;
                    if (activeRequests === 0) {
                        self.hideLoading();
                    }
                    return response;
                })
                .catch(error => {
                    // Handle error
                    activeRequests--;
                    if (activeRequests === 0) {
                        self.hideLoading();
                    }
                    throw error;
                });
        };
    }
}

// Create a global instance
const loadingSpinner = new LoadingSpinner();

// Export as global for use in inline scripts
window.loadingSpinner = loadingSpinner;
