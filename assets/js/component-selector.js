document.addEventListener('DOMContentLoaded', () => {
    // Initialize modal
    const componentModal = new bootstrap.Modal(document.getElementById('componentModal'));
    let currentCategory = null;
    let allComponents = {};
    
    // Set up buttons to open modal
    document.querySelectorAll('.select-component-btn').forEach(btn => {
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
        const categoryConfig = {
            cpu: { name: 'Processor', code: 'cpu' },
            motherboard: { name: 'Motherboard', code: 'motherboard' },
            ram: { name: 'Memory', code: 'ram' },
            ssd: { name: 'SSD Storage', code: 'storage-ssd' },
            hdd: { name: 'HDD Storage', code: 'storage-hdd' },
            gpu: { name: 'Graphics Card', code: 'gpu' },
            case: { name: 'Case', code: 'case' },
            psu: { name: 'Power Supply', code: 'psu' },
            cooler: { name: 'CPU Cooler', code: 'cooler' }
        };
        
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
                const response = await fetch(`http://localhost:5000/api/products?categoryCode=${encodeURIComponent(categoryConfig[category].code)}&pageIndex=0&pageSize=100`);
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
          // Enable/disable Add to Cart button - allow any components to be added
        const hasAnyComponents = Object.values(window.pcBuilderState.components).some(component => component !== null);
        
        const addToCartBtn = document.getElementById('add-to-cart-btn');
        addToCartBtn.disabled = !hasAnyComponents;
    }
});
