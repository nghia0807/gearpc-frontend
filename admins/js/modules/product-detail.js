import { escapeHtml, formatDateTime, formatPrice, showToastAndReload } from '../utils/product-utils.js';

// Initialize product detail viewing functionality
export function initProductDetailView() {
    document.querySelectorAll('.btn-view-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showProductDetail(productId);
        });
    });
}

// Show product detail in modal
function showProductDetail(productId) {
    const modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
    const contentDiv = document.getElementById('viewProductModalContent');
    const alertDiv = document.getElementById('viewProductAlert');
    alertDiv.innerHTML = '';
    contentDiv.innerHTML = '<div class="text-muted">Loading product information...</div>';
    modal.show();

    // Use PHP API handler to get product detail
    const formData = new FormData();
    formData.append('action', 'getProductDetail');
    formData.append('productId', productId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        if (!response.ok) {
            throw new Error(`HTTP error: ${response.status} ${response.statusText}`);
        }
        return await response.json();
    })
    .then(data => {
        if (!data.success || !data.data) {
            contentDiv.innerHTML = `<div class="alert alert-warning">
                ${data.message || 'Could not load product details. Please try again.'}
            </div>`;
            return;
        }
        
        // Debug: Log the full API response
        console.log("Full API response:", data.data);
        
        contentDiv.innerHTML = renderProductDetail(data.data);
        // Setup inline editing handlers after rendering the content
        setupInlineEditHandlers(data.data);
    })
    .catch(err => {
        console.error('Error fetching product details:', err);
        contentDiv.innerHTML = `<div class="alert alert-danger">
            Server connection error, please try again.<br>
            <small class="text-muted">${err.message || ''}</small>
        </div>`;
    });
}

// Render product detail HTML
function renderProductDetail(data) {
    const info = data.productInfo || {};
    const price = data.price || {};
    const detail = data.productDetail || {};
    const options = Array.isArray(data.productOptions) ? data.productOptions : [];
    const gifts = Array.isArray(data.gifts) ? data.gifts : [];
    const createdDate = data.createdDate ? formatDateTime(data.createdDate) : '';
    const createdBy = escapeHtml(data.createdBy || '');

    let imgThumb = info.imageUrl ? `<img src="${escapeHtml(info.imageUrl)}" class="modal-product-thumb me-2 mb-2" alt="Product Image">` :
        `<img src="https://via.placeholder.com/96x96?text=No+Image" class="modal-product-thumb me-2 mb-2" alt="No Image">`;

    let imgList = '';
    if (detail.image && detail.image.length > 0) {
        imgList = detail.image.map(img =>
            `<img src="${escapeHtml(img.url)}" alt="Ảnh phụ" title="priority: ${img.priority}" />`
        ).join('');
        imgList = `<div class="modal-product-img-list mb-2">${imgList}</div>`;
    }

    // Create categories display with edit button - simplified
    const categoryCodes = Array.isArray(info.categoryCode) ? info.categoryCode : 
                         (Array.isArray(info.category) ? info.category : []);
    
    const categoryNames = Array.isArray(info.category) ? info.category : 
                          (info.category ? [info.category] : []);
    
    const categoriesDisplay = `
        <span class="modal-product-label fw-bold text-primary">Categories:</span> 
        <span id="productCategories-display">${categoryNames.map(escapeHtml).join(', ') || 'None'}</span>
        <button class="btn btn-sm edit-btn p-1" data-field="productCategories" 
            data-code="${escapeHtml(info.code)}" 
            data-categories="${escapeHtml(JSON.stringify(categoryCodes))}" 
            title="Edit Product Categories">
            <i class="fa-solid fa-pen text-warning"></i>
        </button><br>
    `;

    let descList = '';
    if (detail.description && detail.description.length > 0) {
        descList = '<div class="mb-2"><span class="modal-product-label fw-bold">Description:</span><ul class="ps-4" style="list-style-type: disc; margin-bottom: 0;">';
        detail.description.forEach(d => {
            descList += `<li>
                <span class="modal-product-label fw-bold">${escapeHtml(d.name)}:</span> ${escapeHtml(d.descriptionText)}
            </li>`;
        });
        descList += '</ul></div>';
    }

    let optionList = '';
    if (options.length > 0) {
        optionList = '<div class="mb-2">';
        options.forEach(opt => {
            optionList += `<div class="modal-product-option mb-1"><span class="modal-product-label fw-bold">${escapeHtml(opt.title)}:</span> `;
            if (Array.isArray(opt.options)) {
                optionList += opt.options.map(o =>
                    `<span class="${o.selected ? 'selected' : ''}">${escapeHtml(o.label)}</span>`
                ).join(', ');
            }
            optionList += '</div>';
        });
        optionList += '</div>';
    }

    // Gift list display
    let giftList = '';
    if (!gifts || gifts.length === 0) {
        giftList = '<div class="text-muted">No gifts</div>';
    } else {
        giftList = '<div class="mb-2">';
        gifts.forEach((g, idx) => {
            if (g && g.image) {
                giftList += `<img src="${escapeHtml(g.image)}" alt="${escapeHtml(g.name || '')}" title="${escapeHtml(g.name || '')}" style="width:48px;height:48px;object-fit:cover;border-radius:6px;margin-right:6px;margin-bottom:4px;background:#eee;">`;
            }
        });
        giftList += '</div>';
    }

    let priceHtml = `<div class="row g-2">
        <div class="col-md-6">
            <span class="modal-product-label fw-bold">Original Price:</span>
            <span class="text-secondary">${formatPrice(price.originalPrice)}</span><span class="text-secondary">₫</span>
        </div>
        <div class="col-md-6">
            <span class="modal-product-label fw-bold">Current Price:</span>
            <span class="text-success">${formatPrice(price.currentPrice)}</span><span class="text-success">₫</span>
        </div>
    </div>`;

    return `
    <div class="card border-0 shadow-sm mb-0" style="background:#f8f9fb;">
        <div class="card-body pb-2">
            <div class="row">
                <div class="col-md-4 text-center">
                    <div class="mb-2">${imgThumb}</div>
                    ${imgList}
                </div>
                <div class="col-md-8">
                    <div class="mb-2">
                        <span class="modal-product-label fw-bold text-primary">ID:</span> ${escapeHtml(info.id)}<br>
                        <span class="modal-product-label fw-bold text-primary">Product Code:</span> ${escapeHtml(info.code)}<br>
                        <span class="modal-product-label fw-bold text-primary">Name:</span> 
                        <span id="productName-display">${escapeHtml(info.name)}</span>
                        <button class="btn btn-sm edit-btn p-1" data-field="productName" data-value="${escapeHtml(info.name)}" data-code="${escapeHtml(info.code)}" title="Edit Product Name">
                            <i class="fa-solid fa-pen text-warning"></i>
                        </button><br>
                        <span class="modal-product-label fw-bold text-primary">Status:</span> ${escapeHtml(info.status)}<br>
                        ${categoriesDisplay}
                        <span class="modal-product-label fw-bold text-primary">Brand:</span> 
                        <span id="productBrand-display">${escapeHtml(info.brand)}</span>
                        <button class="btn btn-sm edit-btn p-1" data-field="productBrand" data-value="${escapeHtml(info.brandCode || '')}" data-code="${escapeHtml(info.code)}" title="Edit Product Brand">
                            <i class="fa-solid fa-pen text-warning"></i>
                        </button>
                    </div>
                    <div class="mb-2">${priceHtml}</div>
                    <div class="mb-2">
                        <span class="modal-product-label fw-bold text-primary">Short Description:</span> 
                        ${escapeHtml(detail.shortDescription || '')}
                    </div>
                    ${descList}
                    ${optionList}
                    <div class="mb-2">
                        <span class="modal-product-label fw-bold text-primary">Gifts:</span>
                        ${giftList}
                    </div>
                    <div class="modal-product-meta small text-muted">
                        <span class="modal-product-label fw-bold">Created:</span> ${createdDate}<br>
                        <span class="modal-product-label fw-bold">Created by:</span> ${createdBy}
                    </div>
                </div>
            </div>
        </div>
    </div>
    `;
}

// Setup handlers for inline editing buttons
function setupInlineEditHandlers(productData) {
    const info = productData.productInfo || {};
    
    // Add event listeners for edit buttons
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const field = this.getAttribute('data-field');
            const value = this.getAttribute('data-value');
            const code = this.getAttribute('data-code') || info.code;
            
            if (field === 'productName') {
                showInlineEditForm(field, 'text', value, code);
            } else if (field === 'productBrand') {
                showBrandEditForm(field, value, code);
            } else if (field === 'productCategories') {
                // Parse the categories data from the button attribute
                let categoriesArray = [];
                try {
                    const categoriesData = this.getAttribute('data-categories');
                    if (categoriesData) {
                        categoriesArray = JSON.parse(categoriesData);
                    }
                } catch (e) {
                    console.error('Error parsing categories data', e);
                }
                showCategoryEditForm(field, categoriesArray, code);
            }
        });
    });
}

// Initialize the product edit name functionality
export function initProductNameEdit() {
    document.querySelectorAll('.btn-edit-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productCode = this.getAttribute('data-code');
            const productName = this.getAttribute('data-name');
            
            // Populate the edit form
            const codeField = document.getElementById('editProductCode');
            const nameField = document.getElementById('editProductName');
            const alertContainer = document.getElementById('editProductNameAlert');
            
            if (codeField && nameField) {
                codeField.value = productCode;
                nameField.value = productName;
            }
            
            // Clear any previous alerts
            if (alertContainer) {
                alertContainer.innerHTML = '';
            }
            
            // Show the modal
            const editModal = document.getElementById('editProductNameModal');
            if (editModal) {
                const bsModal = new bootstrap.Modal(editModal);
                bsModal.show();
            }
        });
    });

    // Handle form submission for product name edit
    const editProductNameForm = document.getElementById('editProductNameForm');
    if (editProductNameForm) {
        editProductNameForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productCode = document.getElementById('editProductCode')?.value;
            const productName = document.getElementById('editProductName')?.value;
            const alertContainer = document.getElementById('editProductNameAlert');
            
            // Validate
            if (!productCode || !productName?.trim()) {
                if (alertContainer) {
                    alertContainer.innerHTML = '<div class="alert alert-danger">Product code and name are required</div>';
                }
                return;
            }
            
            // Use formdata and PHP API handler instead of direct API call
            const formData = new FormData();
            formData.append('action', 'updateProductName');
            formData.append('productCode', productCode);
            formData.append('name', productName);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to update product name');
                }
                // Show success message via toast and reload
                showToastAndReload('success', 'Product name updated successfully!');
            })
            .catch(err => {
                alertContainer.innerHTML = `<div class="alert alert-danger">${err.message || 'Error updating product name'}</div>`;
            });
        });
    }
}

// Show inline edit form for text fields
function showInlineEditForm(field, type, value, code) {
    const displayElement = document.getElementById(`${field}-display`);
    const editButton = document.querySelector(`.edit-btn[data-field="${field}"]`);
    if (!displayElement) return;
    
    // Hide edit button
    if (editButton) editButton.classList.add('d-none');
    
    // Create edit form
    const inputHtml = `<input type="text" class="form-control form-control-sm" id="${field}-input" value="${value}">`;

    // Create form with save and cancel buttons
    const formHtml = `
        <div class="inline-edit-form">
            ${inputHtml}
            <div class="mt-2">
                <button class="btn btn-sm btn-success save-inline-edit" data-field="${field}" data-code="${code}">
                    <i class="fa-solid fa-check"></i> Save
                </button>
                <button class="btn btn-sm btn-secondary cancel-inline-edit" data-field="${field}">
                    <i class="fa-solid fa-times"></i> Cancel
                </button>
            </div>
        </div>
    `;
    
    // Store original content and replace with form
    displayElement.dataset.originalContent = displayElement.innerHTML;
    displayElement.innerHTML = formHtml;
    
    // Focus the input
    const input = document.getElementById(`${field}-input`);
    if (input) input.focus();
    
    // Add event listeners for save and cancel buttons
    document.querySelector(`.save-inline-edit[data-field="${field}"]`)?.addEventListener('click', function() {
        const newValue = input.value;
        saveInlineEdit(field, newValue, code);
    });
    
    document.querySelector(`.cancel-inline-edit[data-field="${field}"]`)?.addEventListener('click', function() {
        cancelInlineEdit(field);
    });
}

// Save inline edit for text field
function saveInlineEdit(field, value, code) {
    const displayElement = document.getElementById(`${field}-display`);
    const alertDiv = document.getElementById('viewProductAlert');
    alertDiv.innerHTML = '';
    
    // Only support productName field editing
    if (field !== 'productName') {
        showToastAndReload('danger', 'Only product name can be edited');
        cancelInlineEdit(field);
        return;
    }
    
    // Show loading state
    displayElement.innerHTML = `<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Updating...</span>`;
    
    // Use PHP API handler to update product name
    const formData = new FormData();
    formData.append('action', 'updateProductName');
    formData.append('productCode', code);
    formData.append('name', value);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || `Failed to update product name`);
        }
        
        // Show success message via toast
        showToastAndReload('success', 'Product name updated successfully!');
    })
    .catch(err => {
        showToastAndReload('danger', err.message || 'Error updating product name');
        cancelInlineEdit(field);
    });
}

// Cancel inline edit form
function cancelInlineEdit(field) {
    const displayElement = document.getElementById(`${field}-display`);
    const editButton = document.querySelector(`.edit-btn[data-field="${field}"]`);
    if (displayElement && displayElement.dataset.originalContent) {
        displayElement.innerHTML = displayElement.dataset.originalContent;
        // Show edit button again
        if (editButton) editButton.classList.remove('d-none');
    }
}

// Show brand edit form
function showBrandEditForm(field, currentBrandCode, productCode) {
    const displayElement = document.getElementById(`${field}-display`);
    const editButton = document.querySelector(`.edit-btn[data-field="${field}"]`);
    if (!displayElement) return;
    
    // Hide edit button
    if (editButton) editButton.classList.add('d-none');
    
    // Create brand selection dropdown - we'll create this dynamically from what's available in the DOM
    const brandSelectContainer = document.getElementById('brandSelectOptions');
    let brandOptions = '';
    
    if (brandSelectContainer) {
        // Extract options from hidden container with brands
        brandOptions = brandSelectContainer.innerHTML;
    }
    
    const formHtml = `
        <div class="inline-edit-form">
            <select class="form-select form-select-sm" id="${field}-input">
                <option value="">-- Select Brand --</option>
                ${brandOptions}
            </select>
            <div class="mt-2">
                <button class="btn btn-sm btn-success save-inline-edit" data-field="${field}" data-code="${productCode}">
                    <i class="fa-solid fa-check"></i> Save
                </button>
                <button class="btn btn-sm btn-secondary cancel-inline-edit" data-field="${field}">
                    <i class="fa-solid fa-times"></i> Cancel
                </button>
            </div>
        </div>
    `;
    
    displayElement.dataset.originalContent = displayElement.innerHTML;
    displayElement.innerHTML = formHtml;
    
    const input = document.getElementById(`${field}-input`);
    if (input) {
        // Set current value if possible
        if (currentBrandCode && input.querySelector(`option[value="${currentBrandCode}"]`)) {
            input.value = currentBrandCode;
        }
        input.focus();
    }
    
    document.querySelector(`.save-inline-edit[data-field="${field}"]`)?.addEventListener('click', function() {
        const newValue = input.value;
        saveBrandEdit(field, newValue, productCode);
    });
    
    document.querySelector(`.cancel-inline-edit[data-field="${field}"]`)?.addEventListener('click', function() {
        cancelInlineEdit(field);
    });
}

// Save brand edit
function saveBrandEdit(field, brandCode, productCode) {
    const displayElement = document.getElementById(`${field}-display`);
    const alertDiv = document.getElementById('viewProductAlert');
    alertDiv.innerHTML = '';
    
    if (!brandCode) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please select a brand</div>';
        return;
    }
    
    displayElement.innerHTML = `<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Updating...</span>`;
    
    // Use PHP API handler to update product brand
    const formData = new FormData();
    formData.append('action', 'updateProductBrand');
    formData.append('productCode', productCode);
    formData.append('brandCode', brandCode);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to update product brand');
        }
        
        showToastAndReload('success', 'Product brand updated successfully!');
    })
    .catch(err => {
        showToastAndReload('danger', err.message || 'Error updating product brand');
        cancelInlineEdit(field);
    });
}

// Show category edit form
function showCategoryEditForm(field, currentCategoriesArray, productCode) {
    const displayElement = document.getElementById(`${field}-display`);
    const editButton = document.querySelector(`.edit-btn[data-field="${field}"]`);
    if (!displayElement) return;
    
    // Hide edit button
    if (editButton) editButton.classList.add('d-none');
    
    // Get categories from hidden container
    const categoriesContainer = document.getElementById('categoriesCheckboxes');
    let categoriesHtml = '';
    
    if (categoriesContainer) {
        // Clone the content to avoid modifying the original
        const content = categoriesContainer.innerHTML;
        categoriesHtml = `
            <div class="border rounded p-3 bg-light mb-2 categories-selector">
                <div class="row">
                    ${content}
                </div>
            </div>
        `;
    }
    
    // Create form with checkboxes and buttons
    const formHtml = `
        <div class="inline-edit-form">
            ${categoriesHtml}
            <div class="mt-2">
                <button class="btn btn-sm btn-success save-inline-edit" data-field="${field}" data-code="${productCode}">
                    <i class="fa-solid fa-check"></i> Save
                </button>
                <button class="btn btn-sm btn-secondary cancel-inline-edit" data-field="${field}">
                    <i class="fa-solid fa-times"></i> Cancel
                </button>
            </div>
        </div>
    `;
    
    // Store original content and replace with form
    displayElement.dataset.originalContent = displayElement.innerHTML;
    displayElement.innerHTML = formHtml;
    
    // Check the current categories
    setTimeout(() => {
        if (Array.isArray(currentCategoriesArray) && currentCategoriesArray.length > 0) {
            currentCategoriesArray.forEach(catCode => {
                // Try finding checkbox by ID first
                let checkbox = document.querySelector(`.categories-selector #modal_cat_${catCode}`);
                
                // If not found, try by value
                if (!checkbox) {
                    checkbox = document.querySelector(`.categories-selector input[value="${catCode}"]`);
                }
                
                // Check it if found
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }
    }, 50);
    
    // Add event listeners for save and cancel buttons
    document.querySelector(`.save-inline-edit[data-field="${field}"]`)?.addEventListener('click', function() {
        const checkedCategories = [];
        document.querySelectorAll('.categories-selector .form-check-input:checked').forEach(checkbox => {
            checkedCategories.push(checkbox.value);
        });
        saveCategoryEdit(field, checkedCategories, productCode);
    });
    
    document.querySelector(`.cancel-inline-edit[data-field="${field}"]`)?.addEventListener('click', function() {
        cancelInlineEdit(field);
    });
}

// Save category edit
function saveCategoryEdit(field, categoriesCodes, productCode) {
    const displayElement = document.getElementById(`${field}-display`);
    const alertDiv = document.getElementById('viewProductAlert');
    alertDiv.innerHTML = '';
    
    if (!Array.isArray(categoriesCodes) || categoriesCodes.length === 0) {
        alertDiv.innerHTML = '<div class="alert alert-danger">Please select at least one category</div>';
        return;
    }
    
    displayElement.innerHTML = `<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Updating...</span>`;
    
    // Use PHP API handler to update product categories
    const formData = new FormData();
    formData.append('action', 'updateProductCategories');
    formData.append('productCode', productCode);
    formData.append('categoriesCode', JSON.stringify(categoriesCodes));
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to update product categories');
        }
        
        showToastAndReload('success', 'Product categories updated successfully!');
    })
    .catch(err => {
        alertDiv.innerHTML = `<div class="alert alert-danger">${err.message || 'Error updating categories'}</div>`;
        cancelInlineEdit(field);
    });
}