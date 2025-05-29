<?php
/**
 * Toast Notification Component
 * 
 * This component provides a standardized way to display toast notifications
 * across different pages in the application.
 * 
 * Usage:
 * 1. Include this file: include 'components/toasts.php';
 * 2. Add alerts to the $alerts array: $alerts[] = ['type' => 'success', 'msg' => 'Operation successful'];
 *    Supported types: success, danger, warning, info
 * 3. Call renderToasts() where you want to display the toasts
 */

// Initialize alerts array if not already defined
if (!isset($alerts) || !is_array($alerts)) {
    $alerts = [];
}

/**
 * Renders toast notifications based on the $alerts array
 * 
 * @param string $position Position of the toast container (e.g., 'top-0 end-0', 'bottom-0 end-0')
 * @param int $zIndex Z-index for the toast container
 * @param int $delay Delay before toast disappears (in milliseconds)
 */
function renderToasts(string $position = 'bottom-0 end-0', int $zIndex = 1080, int $delay = 3500)
{
    global $alerts;

    if (empty($alerts))
        return;    // Bottom right
    echo '<!-- Toast Container -->';
    echo '<div aria-live="polite" aria-atomic="true" class="position-fixed bottom-0 end-0 p-3" style="z-index: ' . $zIndex . '; width: auto; min-width: 0; margin-bottom: 1.5rem; margin-right: 1.5rem;">';
    echo '<div id="toastContainer" class="toast-container" style="width: max-content; min-width: 300px; max-width: 90vw;">';

    foreach ($alerts as $alert) {
        $type = isset($alert['type']) ? htmlspecialchars($alert['type']) : 'info';
        $msg = isset($alert['msg']) ? htmlspecialchars($alert['msg']) : '';

        echo '<div class="toast align-items-center text-bg-' . $type . ' border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="' . $delay . '">';
        echo '<div class="d-flex">';
        echo '<div class="toast-body">' . $msg . '</div>';
        echo '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>';
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
    echo '<!-- End Toast Container -->';
}

/**
 * Adds the JavaScript to initialize all toasts
 * Call this function before the closing </body> tag
 */
function initializeToasts()
{
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo '    var toastElList = [].slice.call(document.querySelectorAll(".toast"));';
    echo '    toastElList.forEach(function(toastEl) {';
    echo '        var toast = new bootstrap.Toast(toastEl);';
    echo '        toast.show();';
    echo '    });';
    echo '});';
    echo '</script>';
}

/**
 * Helper function to add a new alert
 * 
 * @param string $type Alert type (success, danger, warning, info)
 * @param string $msg Alert message
 */
function addAlert(string $type, string $msg)
{
    global $alerts;
    $alerts[] = ['type' => $type, 'msg' => $msg];
}