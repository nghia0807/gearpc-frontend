document.addEventListener('DOMContentLoaded', () => {
    const state = {
        selectedComponents: {},
        totalPrice: 0
    };

    const CATEGORIES = {
        cpu: { name: 'Processor', icon: 'cpu', code: 'cpu', containerId: 'cpu-components' },
        motherboard: { name: 'Motherboard', icon: 'motherboard', code: 'motherboard', containerId: 'motherboard-components' },
        ram: { name: 'Memory', icon: 'memory', code: 'ram', containerId: 'ram-components' },
        ssd: { name: 'SSD Storage', icon: 'device-ssd', code: 'storage-ssd', containerId: 'ssd-components' },
        hdd: { name: 'HDD Storage', icon: 'device-hdd', code: 'storage-hdd', containerId: 'hdd-components' },
        gpu: { name: 'Graphics Card', icon: 'gpu-card', code: 'gpu', containerId: 'gpu-components' },
        case: { name: 'Case', icon: 'box', code: 'case', containerId: 'case-components' },
        psu: { name: 'Power Supply', icon: 'lightning-charge', code: 'psu', containerId: 'psu-components' },
        cooler: { name: 'CPU Cooler', icon: 'fan', code: 'cooler', containerId: 'cooler-components' }
    };    // Expose the showComponentModal function to the window
    window.showComponentModal = showComponentModal;
    window.removeComponent = removeComponent;

    // Initialize the application
    initializePage();

    async function initializePage() {
        // Initialize modal
        componentModal = new bootstrap.Modal(document.getElementById('componentModal'));

        // Set up search functionality
        const searchInput = document.getElementById('searchComponent');
        searchInput.addEventListener('input', () => {
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            searchTimeout = setTimeout(() => {
                filterComponents(searchInput.value);
            }, 300);
        });

        // Add event listener for Add to Cart button
        document.getElementById('add-to-cart-btn').addEventListener('click', addBuildToCart);
    } async function showComponentModal(category) {
        const modalTitle = document.querySelector('#componentModal .modal-title');
        const modalBody = document.getElementById('modal-components');
        const loadingOverlay = document.querySelector('#componentModal .loading-overlay');
        const searchInput = document.getElementById('searchComponent');

        modalTitle.textContent = `Select ${CATEGORIES[category].name}`;
        modalBody.innerHTML = '';
        modalBody.dataset.currentCategory = category;
        searchInput.value = '';
        loadingOverlay.style.display = 'flex';

        try {
            // Fetch components if not already cached
            if (!allComponents[category]) {
                const response = await fetch(`http://localhost:5000/api/products?categoryCode=${encodeURIComponent(CATEGORIES[category].code)}&pageIndex=0&pageSize=100`);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const result = await response.json();
                allComponents[category] = result.data?.data || [];
            }

            // Display components
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
            const componentCard = createComponentCard(component, category);
            modalBody.appendChild(componentCard);
        });
    }

    function filterComponents(searchTerm) {
        const modalBody = document.getElementById('modal-components');
        const category = modalBody.dataset.currentCategory;
        if (!category || !allComponents[category]) return;

        const filtered = allComponents[category].filter(component =>
            component.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
            component.brandName.toLowerCase().includes(searchTerm.toLowerCase()) ||
            component.shortDescription.toLowerCase().includes(searchTerm.toLowerCase())
        );

        displayComponents(category, filtered);
    } let componentModal;
    let allComponents = {};
    let searchTimeout;

    // Expose the showComponentModal function to the global scope
    window.showComponentModal = async function (category) {
        if (!componentModal) {
            componentModal = new bootstrap.Modal(document.getElementById('componentModal'));
        }
        await showComponentModal(category);
    };

    async function loadComponents(category) {
        // Set up initial component display
        // We're now handling click events in the inline script
        // If you need to implement this function, move the code inside here and make sure to call it as needed.
    }

    function createComponentCard(component, category) {
        const col = document.createElement('div');
        col.className = 'col-md-4 mb-3';

        col.innerHTML = `
                <div class="component-card card h-100" data-component-id="${component.id}">
                    <img src="${component.imageUrl}" class="card-img-top" alt="${component.name}">
                    <div class="card-body">
                        <h6 class="card-title mb-1">${component.name}</h6>
                        <p class="text-muted small mb-2">${component.brandName}</p>
                        <p class="card-text">
                            <small class="text-muted">${component.shortDescription || ''}</small>
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                <span class="h5 mb-0">$${component.currentPrice}</span>
                                ${component.originalPrice > component.currentPrice ? `
                                    <small class="text-muted text-decoration-line-through ms-2">$${component.originalPrice}</small>
                                ` : ''}
                            </div>
                            <button class="btn btn-primary btn-sm select-component">
                                Select
                            </button>
                        </div>
                    </div>
                </div>
            `;

        // Add click event listener
        const selectBtn = col.querySelector('.select-component');
        selectBtn.addEventListener('click', () => selectComponent(component, category));

        return col;
    }

    function selectComponent(component, category) {
        // Close the modal
        componentModal.hide();

        // Update selected component in state
        state.selectedComponents[category] = component;

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

        // Check compatibility if needed
        checkCompatibility(category, component);

        // Update build summary
        updateBuildSummary();

        // Enable/disable Add to Cart button
        updateAddToCartButton();
    }

    function checkCompatibility(category, component) {
        const motherboard = state.selectedComponents.motherboard;
        const cpu = state.selectedComponents.cpu;
        const compatibilityAlert = document.getElementById('motherboard-compatibility');

        // Clear any existing alerts
        compatibilityAlert.style.display = 'none';
        compatibilityAlert.textContent = '';

        // Check CPU and Motherboard compatibility
        if (category === 'cpu' && motherboard || category === 'motherboard' && cpu) {
            const socket = category === 'cpu' ? component.socket : cpu.socket;
            const mbSocket = category === 'motherboard' ? component.socket : motherboard.socket;

            if (socket !== mbSocket) {
                compatibilityAlert.textContent = 'Warning: CPU socket does not match motherboard socket!';
                compatibilityAlert.style.display = 'block';
            }
        }
    }

    function removeComponent(category) {
        delete state.selectedComponents[category];

        const selectionDiv = document.querySelector(`#${category}-selection`);
        const noSelection = selectionDiv.querySelector('.no-component-selected');
        const selectedComponent = selectionDiv.querySelector('.selected-component');

        noSelection.classList.remove('d-none');
        selectedComponent.classList.add('d-none');
        selectedComponent.innerHTML = '';

        checkCompatibility(category, null);
        updateBuildSummary();
        updateAddToCartButton();
    }

    function updateBuildSummary() {
        const summaryContainer = document.getElementById('build-summary');
        let total = 0;

        // Clear existing summary
        summaryContainer.innerHTML = '';

        // Add each selected component to summary
        for (const [category, component] of Object.entries(state.selectedComponents)) {
            if (component) {
                total += parseFloat(component.currentPrice);
                summaryContainer.innerHTML += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <i class="bi bi-${CATEGORIES[category].icon} me-2"></i>
                            ${component.name}
                        </div>
                        <div>$${component.currentPrice}</div>
                    </div>
                `;
            }
        }

        // Update total price
        state.totalPrice = total;
        document.getElementById('total-price').textContent = `$${total.toFixed(2)}`;
    }

    function updateAddToCartButton() {
        const requiredCategories = ['cpu', 'motherboard', 'ram'];
        // At least one storage device (SSD or HDD) is required
        const hasStorage = state.selectedComponents.ssd !== undefined || state.selectedComponents.hdd !== undefined;
        const hasRequiredComponents = requiredCategories.every(category =>
            state.selectedComponents[category] !== undefined
        ) && hasStorage;

        const addToCartBtn = document.getElementById('add-to-cart-btn');
        addToCartBtn.disabled = !hasRequiredComponents;
    }

    async function addBuildToCart() {
        try {
            const build = {
                components: state.selectedComponents,
                totalPrice: state.totalPrice
            };

            const response = await fetch('/api/cart/add-build', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(build)
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

            // Show success message
            alert('Build successfully added to cart!');

            // Optional: Redirect to cart page
            // window.location.href = '/cart';
        } catch (error) {
            console.error('Error adding build to cart:', error);
            alert('Failed to add build to cart. Please try again.');
        }
    }
});
