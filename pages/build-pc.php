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
    }

    .summary-card {
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

    /* Floating button styles */
    .scroll-to-top {
        position: fixed;
        bottom: 80px;
        right: 18px;
        background-color: #ff9620;
        color: white;
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        z-index: 1000;
        display: none; /* Hidden by default */
    }

    .scroll-to-top:hover {
        background-color: #e0851c;
    }

    @media (max-width: 768px) {
        .summary-card {
            position: static;
            width: 90%;
            max-height: 60vh;
        }

        #cpu-selection {
            width: 100%;
            margin: 0 auto;
        }
    }

    @media (max-width: 576px) {
        .summary-card {
            position: static;
            width: 100%;
            max-height: none;
        }

        #cpu-selection {
            width: 100%;
            margin: 0 auto;
        }
    }
</style>

<div class="container py-4">
    <div class="row w-100">
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
                    <div class="d-flex gap-2 mb-2">
                        <button class="btn btn-primary flex-grow-1" id="add-to-cart-btn" disabled>
                            <i class="bi bi-cart-plus me-2"></i>Add to Cart
                        </button>
                        <button class="btn btn-dark flex-grow-1" id="buy-now-btn" disabled>
                            <i class="bi bi-bag-check me-2"></i>Buy Now
                        </button>
                    </div>
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

<!-- Toast container for notifications -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"
    style="z-index: 1080; margin-bottom: 1.5rem; margin-right: 1.5rem; width: max-content; min-width: 300px; max-width: 90vw;">
</div>

<!-- Floating Scroll-to-Top Button -->
<button class="scroll-to-top" id="scrollToTopBtn">
    <i class="bi bi-arrow-up"></i>
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // PC Builder Component Functionality
    document.addEventListener('DOMContentLoaded', async () => {
        // Initialize modal
        const componentModal = new bootstrap.Modal(document.getElementById('componentModal'));
        let currentCategory = null;
        let allComponents = {};

        const categoryConfig = {
            cpu: { name: 'Processor', code: 'cpu' },
            motherboard: { name: 'Motherboard', code: 'motherboard' },
            ram: { name: 'Memory', code: 'ram' },
            ssd: { name: 'SSD Storage', code: 'ssd' },
            hdd: { name: 'HDD Storage', code: 'hdd' },
            gpu: { name: 'Graphics Card', code: 'gpu' },
            case: { name: 'Case', code: 'case' },
            psu: { name: 'Power Supply', code: 'psu' },
            cooler: { name: 'CPU Cooler', code: 'cooler' }
        };

        // Set up buttons to open modal
        document.querySelectorAll('.select-component-btn').forEach(async (btn) => {
            btn.addEventListener('click', () => {
                const category = btn.dataset.category;
                showComponentModal(category);
            });
        });

        // Set up search functionality
        const searchInput = document.getElementById('searchComponent');
        let searchTimeout;
        searchInput.addEventListener('input', () => {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            searchTimeout = setTimeout(() => {
                filterComponents(searchInput.value);
            }, 300);
        });

        async function showComponentModal(category) {
            currentCategory = category;

            const modalTitle = document.querySelector('#componentModal .modal-title');
            const modalBody = document.getElementById('modal-components');
            const loadingOverlay = document.querySelector('#componentModal .loading-overlay');

            modalTitle.textContent = `Select ${categoryConfig[category].name}`;
            modalBody.innerHTML = '';
            searchInput.value = '';
            loadingOverlay.style.display = 'flex';

            try {
                // Fetch components if not cached
                if (!allComponents[category]) {
                    const response = await fetch(`https://tamcutephomaique.ddns.net:5001/api/products?categoryCode=${encodeURIComponent(categoryConfig[category].code)}&pageIndex=0&pageSize=100`);
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    const result = await response.json();
                    allComponents[category] = result.data?.data || [];
                }

                displayComponents(category, allComponents[category]);
            } catch (error) {
                console.error('Error loading components:', error);
                modalBody.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger">
                            Error loading components. Please try again later.
                        </div>
                    </div>`;
            } finally {
                loadingOverlay.style.display = 'none';
            }

            componentModal.show();
        }

        function displayComponents(category, components) {
            const modalBody = document.getElementById('modal-components');
            modalBody.innerHTML = '';

            components.forEach(component => {
                const col = document.createElement('div');
                col.className = 'col-md-4 mb-3';
                col.innerHTML = `
                    <div class="component-card card h-100" data-id="${component.id}">
                        <img src="${component.imageUrl}" class="card-img-top" alt="${component.name}">
                        <div class="card-body">
                            <h6 class="card-title mb-1">${component.name}</h6>
                            <p class="text-muted small mb-2">${component.brandName}</p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <span class="h5 mb-0">$${component.currentPrice}</span>
                                    ${component.originalPrice > component.currentPrice ? `
                                        <small class="text-muted text-decoration-line-through ms-2">$${component.originalPrice}</small>
                                    ` : ''}
                                </div>
                                <button class="btn btn-primary btn-sm select-this-component">
                                    Select
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                // Add click event listener
                const selectBtn = col.querySelector('.select-this-component');
                selectBtn.addEventListener('click', () => {
                    selectComponent(component, category);
                });

                modalBody.appendChild(col);
            });
        }

        function filterComponents(searchTerm) {
            if (!currentCategory || !allComponents[currentCategory]) return;

            searchTerm = searchTerm.toLowerCase();
            const filtered = allComponents[currentCategory].filter(component =>
                component.name.toLowerCase().includes(searchTerm) ||
                component.brandName.toLowerCase().includes(searchTerm) ||
                (component.shortDescription && component.shortDescription.toLowerCase().includes(searchTerm))
            );

            displayComponents(currentCategory, filtered);
        }

        function selectComponent(component, category) {
            // Close modal
            componentModal.hide();

            // update params
            const params = new URLSearchParams(window.location.search);
            params.set(category, component.id);
            const newUrl = `${window.location.pathname}?${params.toString()}`;

            window.history.replaceState({}, '', newUrl);

            // Update UI to show selected component
            const selectionDiv = document.querySelector(`#${category}-selection`);
            const noSelection = selectionDiv.querySelector('.no-component-selected');
            const selectedComponent = selectionDiv.querySelector('.selected-component');

            noSelection.classList.add('d-none');
            selectedComponent.classList.remove('d-none');
            selectedComponent.innerHTML = `
                <div class="component-summary">
                    <img src="${component.imageUrl}" alt="${component.name}">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${component.name}</h6>
                        <p class="text-muted small mb-1">${component.brandName}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h6 mb-0">$${component.currentPrice}</span>
                            <button class="btn btn-outline-danger btn-sm remove-component">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            // Add remove button event listener
            const removeBtn = selectedComponent.querySelector('.remove-component');
            removeBtn.addEventListener('click', () => removeComponent(category));

            // Save in app state - will use window.pcBuilderState to share between files
            if (!window.pcBuilderState) {
                window.pcBuilderState = {
                    components: {},
                    totalPrice: 0
                };
            }

            // Make sure the component has an id property
            if (!component.id && component.productId) {
                component.id = component.productId;
            }

            // Log component data for debugging
            console.log(`Selected ${category} component:`, component);

            window.pcBuilderState.components[category] = component;
            updateBuildSummary();
        }

        function removeComponent(category) {
            // Remove from state
            if (window.pcBuilderState && window.pcBuilderState.components) {
                delete window.pcBuilderState.components[category];
            }

            // Update UI
            const selectionDiv = document.querySelector(`#${category}-selection`);
            const noSelection = selectionDiv.querySelector('.no-component-selected');
            const selectedComponent = selectionDiv.querySelector('.selected-component');

            noSelection.classList.remove('d-none');
            selectedComponent.classList.add('d-none');
            selectedComponent.innerHTML = '';

            updateBuildSummary();
        }

        function updateBuildSummary() {
            if (!window.pcBuilderState) {
                window.pcBuilderState = {
                    components: {},
                    totalPrice: 0
                };
            }

            const summaryContainer = document.getElementById('build-summary');
            let total = 0;

            // Clear existing summary
            summaryContainer.innerHTML = '';

            // Add each selected component to summary
            for (const [category, component] of Object.entries(window.pcBuilderState.components)) {
                if (component) {
                    total += parseFloat(component.currentPrice);
                    const icon = {
                        cpu: 'cpu',
                        motherboard: 'motherboard',
                        ram: 'memory',
                        ssd: 'device-ssd',
                        hdd: 'device-hdd',
                        gpu: 'gpu-card',
                        case: 'box',
                        psu: 'lightning-charge',
                        cooler: 'fan'
                    }[category] || 'gear';

                    summaryContainer.innerHTML += `
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center">
                                ${component.imageUrl ?
                            `<img src="${component.imageUrl}" alt="${component.name}" class="summary-item-img me-2">` :
                            `<i class="bi bi-${icon} me-2 summary-item-icon"></i>`
                        }
                                <div class="summary-item-details">
                                    <div class="summary-item-name">${component.name}</div>
                                    <div class="summary-item-category text-muted small">${category.toUpperCase()}</div>
                                </div>
                            </div>
                            <div class="summary-item-price">$${component.currentPrice}</div>
                        </div>
                    `;
                }
            }

            // Update total price
            window.pcBuilderState.totalPrice = total;
            document.getElementById('total-price').textContent = `$${total.toFixed(2)}`;
            // Enable/disable Add to Cart and Buy Now buttons - allow any components to be added
            const hasAnyComponents = Object.values(window.pcBuilderState.components).some(component => component !== null);

            const addToCartBtn = document.getElementById('add-to-cart-btn');
            const buyNowBtn = document.getElementById('buy-now-btn');

            addToCartBtn.disabled = !hasAnyComponents;
            buyNowBtn.disabled = !hasAnyComponents;
        }

        // Add to Cart functionality
        const addToCartBtn = document.getElementById('add-to-cart-btn');
        const buyNowBtn = document.getElementById('buy-now-btn');

        // Function to check if components are selected and valid
        function getValidComponents() {
            if (!window.pcBuilderState) {
                showToast('Please select at least one product', 'warning');
                return null;
            }

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
            }

            if (!hasValidComponents) {
                showToast('Please select at least one component', 'warning');
                return null;
            }

            return {
                components: cleanedComponents,
                totalPrice: window.pcBuilderState.totalPrice
            };
        } addToCartBtn.addEventListener('click', async () => {
            try {
                // Check if user is logged in
                if (!isUserLoggedIn()) {
                    showLoginConfirmModal();
                    return;
                }

                const validComponentsData = getValidComponents();
                if (!validComponentsData) return;

                console.log('Adding build to cart:', validComponentsData.components);

                const response = await fetch('/gearpc-frontend/actions/add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        components: validComponentsData.components,
                        totalPrice: validComponentsData.totalPrice
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
                }

                if (!response.ok) {
                    throw new Error(result?.message || `Error: ${response.status}`);
                }

                // Check for success
                if (result && result.success) {
                    // Success will be handled by the showToast function below
                } else {
                    throw new Error(result?.message || 'Failed to add to cart');
                }

                // Show success toast instead of alert
                showToast(result.message || 'Added to cart successfully!', 'success');
            } catch (error) {
                console.error('Error adding build to cart:', error);
                showToast(`Unable to add products to cart: ${error.message || 'Please try again.'}`, 'danger');
            }
        });

        // Buy Now button functionality
        buyNowBtn.addEventListener('click', async () => {
            try {
                // Check if user is logged in
                if (!isUserLoggedIn()) {
                    showLoginConfirmModal();
                    return;
                }

                const validComponentsData = getValidComponents();
                if (!validComponentsData) return;

                // First, add components to cart
                const response = await fetch('/gearpc-frontend/actions/add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        components: validComponentsData.components,
                        totalPrice: validComponentsData.totalPrice
                    })
                });

                let result;
                try {
                    const text = await response.text();
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse response:', e);
                    throw new Error('Invalid response from server');
                }

                if (!response.ok || !result.success) {
                    throw new Error(result?.message || `Error: ${response.status}`);
                }

                // Extract item ids from the components to pass to order page
                const itemIds = Object.values(validComponentsData.components)
                    .map(component => component.id)
                    .filter(id => id);

                if (itemIds.length > 0) {
                    // Redirect to order page with the item ids
                    window.location.href = 'index.php?page=order&items=' + itemIds.join(',');
                } else {
                    throw new Error('No valid component IDs found');
                }
            } catch (error) {
                console.error('Error processing buy now:', error);
                showToast(`Unable to proceed to checkout: ${error.message || 'Please try again.'}`, 'danger');
            }
        });

        // Function to check if user is logged in
        function isUserLoggedIn() {
            return <?= isset($_SESSION['token']) ? 'true' : 'false' ?>;
        }

        // Show login confirmation modal
        function showLoginConfirmModal() {
            const modal = document.getElementById('loginConfirmModal');
            if (!modal) {
                // Create login modal if it doesn't exist
                const loginModal = document.createElement('div');
                loginModal.className = 'modal fade';
                loginModal.id = 'loginConfirmModal';
                loginModal.tabIndex = '-1';
                loginModal.setAttribute('aria-labelledby', 'loginConfirmModalLabel');
                loginModal.setAttribute('aria-hidden', 'true');

                loginModal.innerHTML = `
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content bg-dark text-white">
                            <div class="modal-header border-secondary">
                                <h5 class="modal-title" id="loginConfirmModalLabel">Login Required</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Please log in to use this feature.</p>
                            </div>
                            <div class="modal-footer border-top border-secondary">
                                <a href="/pages/login.php" class="btn" style="background-color: #ffa33a; color: #000000; font-weight: 600;">Login</a>
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(loginModal);
                const bsModal = new bootstrap.Modal(loginModal);
                bsModal.show();
            } else {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        }

        // Function to show toast notifications
        function showToast(message, type = 'success') {
            // Create toast container if it doesn't exist
            const toastContainer = document.getElementById('toastContainer');

            // Create toast element
            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center text-bg-${type} border-0 mb-2`;
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            toastEl.setAttribute('data-bs-delay', '5000');

            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            `;

            toastContainer.appendChild(toastEl);

            const toast = new bootstrap.Toast(toastEl);
            toast.show();

            // Auto-remove toast after it's hidden
            toastEl.addEventListener('hidden.bs.toast', function () {
                toastEl.remove();
            });
        }

        // Scroll to top button functionality
        const scrollToTopBtn = document.getElementById('scrollToTopBtn');

        // Show/hide button based on scroll position
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                scrollToTopBtn.style.display = 'flex';
            } else {
                scrollToTopBtn.style.display = 'none';
            }
        });

        // Scroll to top on button click
        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        let params = new URLSearchParams(document.location.search);
        for (item in categoryConfig) {
            console.log(item, params.get(item));
            let componentId = params.get(item);
            if (componentId) {
                // Fetch component details
                await fetch(`http://tamcutephomaique.ddns.net:5001/api/products?categoryCode=${encodeURIComponent(categoryConfig[item].code)}&pageIndex=0&pageSize=100`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.data) {
                            console.log(data.data.data.find(comp => comp.id === componentId));
                            selectComponent(data.data.data.find(comp => comp.id === componentId), item);
                        } else {
                            console.warn(`No component found for ID: ${componentId}`);
                        }
                    })
                    .catch(error => console.error(`Error fetching component ${item} with ID ${componentId}:`, error));
            }
        }
    });
</script>