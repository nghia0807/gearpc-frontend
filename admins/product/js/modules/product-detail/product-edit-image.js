import { fileToBase64, showToastAndReload } from '../../utils/product-utils.js';

/**
 * Initialize product main image edit functionality
 */
export function initProductMainImageEdit() {
    // This will be called by the setup function in product-edit-handlers.js
    
    // Listen for image edit button clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-btn[data-field="productMainImage"]')) {
            const btn = e.target.closest('.edit-btn[data-field="productMainImage"]');
            const productCode = btn.getAttribute('data-code');
            showImageEditForm(productCode);
        }
    });

    // Listen for save and cancel image edit clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.save-image-edit')) {
            const btn = e.target.closest('.save-image-edit');
            const productCode = btn.getAttribute('data-code');
            saveImageEdit(productCode);
        } else if (e.target.closest('.cancel-image-edit')) {
            cancelImageEdit();
        }
    });
}

/**
 * Show image edit form
 */
function showImageEditForm(productCode) {
    const displayElement = document.getElementById('productMainImage-display');
    const editButton = document.querySelector('.edit-btn[data-field="productMainImage"]');
    
    if (!displayElement) return;
    
    // Hide edit button
    if (editButton) editButton.classList.add('d-none');
    
    // Store original content
    displayElement.dataset.originalContent = displayElement.innerHTML;
    
    // Create image upload form
    const formHtml = `
        <div class="image-edit-form mb-2">
            <div class="mb-2">
                <input type="file" class="form-control form-control-sm" id="productMainImage-input" accept="image/*">
                <div class="form-text">Select a new image to replace the current one</div>
            </div>
            <div class="mt-2">
                <button class="btn btn-sm btn-success save-image-edit" data-code="${productCode}">
                    <i class="fa-solid fa-check"></i> Save
                </button>
                <button class="btn btn-sm btn-secondary cancel-image-edit">
                    <i class="fa-solid fa-times"></i> Cancel
                </button>
            </div>
        </div>
    `;
    
    // Replace with form
    displayElement.innerHTML = formHtml;
}

/**
 * Save image edit
 */
async function saveImageEdit(productCode) {
    const displayElement = document.getElementById('productMainImage-display');
    const alertDiv = document.getElementById('viewProductAlert');
    const fileInput = document.getElementById('productMainImage-input');
    
    alertDiv.innerHTML = '';
    
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alertDiv.innerHTML = `<div class="alert alert-warning">Please select an image file</div>`;
        return;
    }
    
    const file = fileInput.files[0];
    if (!file.type.startsWith('image/')) {
        alertDiv.innerHTML = `<div class="alert alert-warning">Please select a valid image file</div>`;
        return;
    }
    
    // Show loading state
    displayElement.innerHTML = `<div class="text-center py-3">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2">Uploading image...</p>
    </div>`;
    
    try {
        // Convert file to base64
        const imageBase64 = await fileToBase64(file);
        
        // Use PHP API handler to update product image
        const formData = new FormData();
        formData.append('action', 'updateProductMainImage');
        formData.append('productCode', productCode);
        formData.append('imageBase64', imageBase64);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to update product image');
        }
        
        // Show success message and reload page
        showToastAndReload('success', 'Product image updated successfully!');
    } catch (err) {
        alertDiv.innerHTML = `<div class="alert alert-danger">
            ${err.message || 'Error updating product image'}
        </div>`;
        cancelImageEdit();
    }
}

/**
 * Cancel image edit
 */
function cancelImageEdit() {
    const displayElement = document.getElementById('productMainImage-display');
    const editButton = document.querySelector('.edit-btn[data-field="productMainImage"]');
    
    if (displayElement && displayElement.dataset.originalContent) {
        displayElement.innerHTML = displayElement.dataset.originalContent;
        // Show edit button again
        if (editButton) editButton.classList.remove('d-none');
    }
}