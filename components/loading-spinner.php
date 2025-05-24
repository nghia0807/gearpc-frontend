<?php
/**
 * Loading Spinner Component
 * 
 * This component provides a loading overlay with a gear icon for:
 * - Page transitions/navigation
 * - AJAX requests
 * - Form submissions
 * 
 * Usage in JavaScript:
 * - Show loading: window.loadingSpinner.showLoading();
 * - Hide loading: window.loadingSpinner.hideLoading();
 * - For AJAX requests: Automatically handled
 * - For page transitions: Automatically handled
 * 
 * Available globally as window.loadingSpinner
 */
?>
<div id="loading-overlay" class="loading-overlay">
    <div class="loading-spinner">
        <i class="bi bi-gear-fill gear-icon spinning"></i>
    </div>
</div>

<style>
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s, visibility 0.3s;
    }

    .loading-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        background-color: transparent;
        padding: 30px;
    }

    .gear-icon {
        font-size: 3.5rem;
        color: #ffa33a;
        filter: drop-shadow(0 0 10px rgba(255, 163, 58, 0.7));
    }

    /* Spinning animation integrated into pulse keyframes above */    /* Loading text removed */

    /* Animation for fading in and out */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }

        to {
            opacity: 0;
        }
    }

    /* Animation for gear icon */
    @keyframes pulse {
        0% {
            transform: scale(1) rotate(0deg);
            filter: drop-shadow(0 0 5px rgba(255, 163, 58, 0.4));
        }

        50% {
            transform: scale(1.1) rotate(180deg);
            filter: drop-shadow(0 0 15px rgba(255, 163, 58, 0.8));
        }

        100% {
            transform: scale(1) rotate(360deg);
            filter: drop-shadow(0 0 5px rgba(255, 163, 58, 0.4));
        }
    }

    .spinning {
        animation: pulse 3s ease-in-out infinite;
    }
</style>