<?php
require_once __DIR__ . '/../includes/session_init.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
    .component-card {
        border: 2px solid #ddd;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .component-card:hover {
        border-color: #ff9620;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .component-card.selected {
        border-color: #ff9620;
        background-color: #fff9f2;
    }

    .component-selection {
        background-color: #f8f9fa;
        border-radius: 8px;
        min-height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .selected-component {
        width: 100%;
    }

    .selected-component .component-summary {
        display: flex;
        align-items: center;
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .selected-component img {
        width: 80px;
        height: 80px;
        object-fit: contain;
        margin-right: 15px;
    }

    .component-card img {
        height: 140px;
        object-fit: contain;
        padding: 15px;
        background-color: #fff;
    }

    .component-card .card-title {
        font-size: 0.95rem;
        line-height: 1.4;
        height: 2.8em;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .component-card .card-text {
        font-size: 0.85rem;
        height: 3.6em;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }    .summary-card {
        position: sticky;
        top: 20px;
    }

    .summary-item-img {
        width: 40px;
        height: 40px;
        object-fit: contain;
        border-radius: 4px;
        background-color: white;
    }

    .summary-item-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #ff9620;
        background-color: #fff;
        border-radius: 4px;
    }

    .summary-item-details {
        max-width: 150px;
        overflow: hidden;
    }

    .summary-item-name {
        font-size: 0.85rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .summary-item-category {
        font-size: 0.75rem;
    }

    .category-icon {
        font-size: 1.5rem;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
        border-radius: 8px;
        margin-right: 15px;
    }

    .category-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }

    .compatibility-alert {
        margin-top: 10px;
        padding: 10px;
        border-radius: 4px;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        display: none;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        display: none;
    }

    .category-section {
        position: relative;
        margin-bottom: 30px;
    }

    .btn-primary {
        background-color: #ff9620 !important;
        color: white !important;
        border-color: #ff9620 !important;
    }

    .btn-primary:hover {
        background-color: #e0851c !important;
        border-color: #e0851c !important;
    }

    .btn-primary:focus,
    .btn-primary:focus-visible,
    .btn-primary:active:focus {
        outline: none !important;
        box-shadow: none !important;
    }

    .btn-outline-primary {
        color: #ff9620 !important;
        background-color: transparent !important;
        border: 1px solid #ff9620 !important;
    }

    .btn-outline-primary:hover {
        background-color: #ff9620 !important;
        color: white !important;
        border-color: #ff9620 !important;
    }

    .btn-outline-primary:focus,
    .btn-outline-primary:focus-visible,
    .btn-outline-primary:active:focus {
        outline: none !important;
        box-shadow: 0 0 0 0.25rem rgba(255, 150, 32, 0.5) !important;
    }

    #searchComponent.form-control {
        border: 1px solid #ff9620 !important;
        background-color: #fff !important;
        color: #212529 !important;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }

    #searchComponent.form-control:focus {
        border-color: #ff9620;
        box-shadow: 0 0 0 0.25rem rgba(255, 150, 32, 0.25);
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Component Selection Area -->
        <div class="col-lg-8 mb-4">
            <h2 class="mb-4">Build Your Custom PC</h2>

            <!-- CPU Section -->
            <div class="category-section" id="cpu-section">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="bi bi-cpu"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">Processor (CPU)</h4>
                        <small class="text-muted">Select a processor for your build</small>
                    </div>
                </div>
                <div id="cpu-selection" class="component-selection p-3">
                    <div class="text-center no-component-selected">
                        <p class="mb-2">No CPU selected</p>
                        <button class="btn btn-outline-primary select-component-btn" data-category="cpu">
                            <i class="bi bi-plus-lg me-2"></i>Select CPU
                        </button>
                    </div>
                    <div class="selected-component d-none">
                        <!-- Selected component will be shown here -->
                    </div>
                </div>
            </div> <!-- Motherboard Section -->
            <div class="category-section" id="motherboard-section">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="bi bi-motherboard"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">Motherboard</h4>
                        <small class="text-muted">Select a compatible motherboard</small>
                    </div>
                </div>
                <div id="motherboard-selection" class="component-selection p-3">
                    <div class="text-center no-component-selected">
                        <p class="mb-2">No Motherboard selected</p>
                        <button class="btn btn-outline-primary select-component-btn" data-category="motherboard">
                            <i class="bi bi-plus-lg me-2"></i>Select Motherboard
                        </button>
                    </div>
                    <div class="selected-component d-none">
                        <!-- Selected component will be shown here -->
                    </div>
                </div>
                <div class="compatibility-alert" id="motherboard-compatibility"></div>
            </div> <!-- Memory Section -->
            <div class="category-section" id="ram-section">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="bi bi-memory"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">Memory (RAM)</h4>
                        <small class="text-muted">Select memory modules</small>
                    </div>
                </div>
                <div id="ram-selection" class="component-selection p-3">
                    <div class="text-center no-component-selected">
                        <p class="mb-2">No RAM selected</p>
                        <button class="btn btn-outline-primary select-component-btn" data-category="ram">
                            <i class="bi bi-plus-lg me-2"></i>Select RAM
                        </button>
                    </div>
                    <div class="selected-component d-none">
                        <!-- Selected component will be shown here -->
                    </div>
                </div>
            </div> <!-- Storage Sections -->
            <div class="category-section" id="storage-section">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="bi bi-device-hdd"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">Storage</h4>
                        <small class="text-muted">Select storage devices</small>
                    </div>
                </div>
                <div id="ssd-selection" class="component-selection p-3 mb-2">
                    <div class="text-center no-component-selected">
                        <p class="mb-2">No SSD selected</p>
                        <button class="btn btn-outline-primary select-component-btn" data-category="ssd">
                            <i class="bi bi-plus-lg me-2"></i>Select SSD
                        </button>
                    </div>
                    <div class="selected-component d-none">
                        <!-- Selected component will be shown here -->
                    </div>
                </div>
                <div id="hdd-selection" class="component-selection p-3">
                    <div class="text-center no-component-selected">
                        <p class="mb-2">No HDD selected</p>
                        <button class="btn btn-outline-primary select-component-btn" data-category="hdd">
                            <i class="bi bi-plus-lg me-2"></i>Select HDD
                        </button>
                    </div>
                    <div class="selected-component d-none">
                        <!-- Selected component will be shown here -->
                    </div>
                </div>
            </div> <!-- GPU Section -->
            <div class="category-section" id="gpu-section">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="bi bi-gpu-card"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">Graphics Card (GPU)</h4>
                        <small class="text-muted">Select a graphics card</small>
                    </div>
                </div>
                <div id="gpu-selection" class="component-selection p-3">
                    <div class="text-center no-component-selected">
                        <p class="mb-2">No Graphics Card selected</p>
                        <button class="btn btn-outline-primary select-component-btn" data-category="gpu">
                            <i class="bi bi-plus-lg me-2"></i>Select Graphics Card
                        </button>
                    </div>
                    <div class="selected-component d-none">
                        <!-- Selected component will be shown here -->
                    </div>
                </div>
            </div> <!-- Case Section -->
            <div class="category-section" id="case-section">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="bi bi-box"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">PC Case</h4>
                        <small class="text-muted">Select a case for your build</small>
                    </div>
                </div>
                <div id="case-selection" class="component-selection p-3">
                    <div class="text-center no-component-selected">
                        <p class="mb-2">No PC Case selected</p>
                        <button class="btn btn-outline-primary select-component-btn" data-category="case">
                            <i class="bi bi-plus-lg me-2"></i>Select PC Case
                        </button>
                    </div>
                    <div class="selected-component d-none">
                        <!-- Selected component will be shown here -->
                    </div>
                </div>
            </div> <!-- Power Supply Section -->
            <div class="category-section" id="psu-section">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="bi bi-lightning-charge"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">Power Supply (PSU)</h4>
                        <small class="text-muted">Select a power supply</small>
                    </div>
                </div>
                <div id="psu-selection" class="component-selection p-3">
                    <div class="text-center no-component-selected">
                        <p class="mb-2">No Power Supply selected</p>
                        <button class="btn btn-outline-primary select-component-btn" data-category="psu">
                            <i class="bi bi-plus-lg me-2"></i>Select Power Supply
                        </button>
                    </div>
                    <div class="selected-component d-none">
                        <!-- Selected component will be shown here -->
                    </div>
                </div>
            </div>

            <!-- CPU Cooler Section -->
            <div class="category-section" id="cooler-section">
                <div class="category-header">
                    <div class="category-icon">
                        <i class="bi bi-fan"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">CPU Cooler</h4>
                        <small class="text-muted">Select a CPU cooler</small>
                    </div>
                </div>
                <div id="cooler-selection" class="component-selection p-3">
                    <div class="text-center no-component-selected">
                        <p class="mb-2">No CPU Cooler selected</p>
                        <button class="btn btn-outline-primary select-component-btn" data-category="cooler">
                            <i class="bi bi-plus-lg me-2"></i>Select CPU Cooler
                        </button>
                    </div>
                    <div class="selected-component d-none">
                        <!-- Selected component will be shown here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Build Summary -->
        <div class="col-lg-4">
            <div class="card summary-card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Build Summary</h5>
                </div>
                <div class="card-body">
                    <div id="build-summary">
                        <!-- Summary items will be added here dynamically -->
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Total:</h5>
                        <h5 class="mb-0" id="total-price">$0.00</h5>
                    </div>
                    <button class="btn btn-primary w-100" id="add-to-cart-btn" disabled>
                        <i class="bi bi-cart-plus me-2"></i>Thêm vào giỏ hàng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Component Selection Modal -->
<div class="modal fade" id="componentModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Component</h5>
                <div class="ms-auto me-2 flex-grow-1" style="max-width: 300px;">
                    <input type="text" class="form-control" id="searchComponent" placeholder="Search components...">
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="loading-overlay" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="row" id="modal-components">
                    <!-- Components will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/gearpc-frontend/assets/js/component-selector.js"></script>
<script>
    // Add to Cart functionality
    document.addEventListener('DOMContentLoaded', () => {
        const addToCartBtn = document.getElementById('add-to-cart-btn');

        addToCartBtn.addEventListener('click', async () => {            try {
                if (!window.pcBuilderState) {
                    showToast('Please select at least one product', 'warning');
                    return;
                }
                // Make a clean copy of the components and ensure each has an ID
                const cleanedComponents = {};
                let hasValidComponents = false;

                for (const [category, component] of Object.entries(window.pcBuilderState.components)) {
                    // Skip null/undefined components (not selected)
                    if (!component) continue;

                    // Make sure we have a valid ID
                    let componentId = component.id || component.productId;

                    if (componentId) {
                        cleanedComponents[category] = {
                            id: componentId,
                            name: component.name,
                            currentPrice: component.currentPrice
                        };
                        hasValidComponents = true;
                    }
                }                if (!hasValidComponents) {
                    showToast('Failed to add to cart', 'warning');
                    return;
                }

                console.log('Adding build to cart:', cleanedComponents);

                const response = await fetch('/gearpc-frontend/actions/add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        components: cleanedComponents,
                        totalPrice: window.pcBuilderState.totalPrice
                    })
                });

                let result;
                try {
                    const text = await response.text();
                    console.log('Raw response:', text);
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse response:', e);
                    throw new Error('Invalid response from server');
                }                if (!response.ok) {
                    throw new Error(result?.message || `Error: ${response.status}`);
                }

                // Check for success
                if (result && result.success) {
                    // Success will be handled by the showToast function below
                } else {
                    throw new Error(result?.message || 'Failed to add to cart');
                }// Optional: Redirect to cart page
                // window.location.href = '/gearpc-frontend/pages/cart.php';

                // Show success toast instead of alert
                showToast(result.message || 'add to cart successfully!', 'success');
            } catch (error) {
                console.error('Error adding build to cart:', error);
                showToast(`Unable to add product to cart: ${error.message || 'Please try again.'}`, 'danger');
            }
        });

        // Function to show toast notifications
        function showToast(message, type = 'success') {
            // Create toast container if it doesn't exist
            let toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toastContainer';
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                toastContainer.style.zIndex = '1080';
                document.body.appendChild(toastContainer);
            }

            // Create toast element
            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center text-bg-${type} border-0`;
            toastEl.role = 'alert';
            toastEl.ariaLive = 'assertive';
            toastEl.ariaAtomic = 'true';
            
            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;
            
            toastContainer.appendChild(toastEl);
            
            // Initialize and show toast
            const toast = new bootstrap.Toast(toastEl, {
                delay: 5000
            });
            toast.show();
            
            // Remove toast from DOM after it's hidden
            toastEl.addEventListener('hidden.bs.toast', () => {
                toastEl.remove();
            });
        }
    });
</script>