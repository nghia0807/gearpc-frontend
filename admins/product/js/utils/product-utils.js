// Helper function to convert file to base64
export function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        if (!file) {
            reject(new Error("No file provided"));
            return;
        }
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result); // Return the full data URL
        reader.onerror = error => reject(error);
        reader.readAsDataURL(file);
    });
}

// Format date & time
export function formatDateTime(dateString) {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString; // Return the original string if parsing fails
    }
}

// Format price with thousand separators
export function formatPrice(price) {
    if (price == null) return '0';
    return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Function for showing toast and reloading page
export function showToastAndReload(type, msg) {
    // Encode the toast parameters in the URL
    const redirectUrl = new URL(window.location.href);
    redirectUrl.searchParams.set('toast_type', type);
    redirectUrl.searchParams.set('toast_msg', msg);
    
    // Preserve existing page parameter if present
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('page')) {
        redirectUrl.searchParams.set('page', urlParams.get('page'));
    }
    
    // Redirect to show the toast
    window.location.href = redirectUrl.toString();
}

// Escape HTML chars to prevent XSS
export function escapeHtml(str) {
    return typeof str === 'string' ? str.replace(/[&<>"']/g, function(m) {
        return ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[m];
    }) : '';
}