import { showToastAndReload } from '../../utils/product-utils.js';

/**
 * Hiển thị form chỉnh sửa inline dựa trên loại field
 */
export function showInlineEditForm(field, type, value, code) {
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

/**
 * Lưu dữ liệu từ form chỉnh sửa inline
 */
export function saveInlineEdit(field, value, code) {
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

/**
 * Hủy chỉnh sửa và khôi phục nội dung ban đầu
 */
export function cancelInlineEdit(field) {
    const displayElement = document.getElementById(`${field}-display`);
    const editButton = document.querySelector(`.edit-btn[data-field="${field}"]`);
    if (displayElement && displayElement.dataset.originalContent) {
        displayElement.innerHTML = displayElement.dataset.originalContent;
        // Show edit button again
        if (editButton) editButton.classList.remove('d-none');
    }
}