import { showToastAndReload } from '../../utils/product-utils.js';
import { cancelInlineEdit } from './product-edit-common.js';

/**
 * Hiển thị form chỉnh sửa thương hiệu
 */
export function showBrandEditForm(field, currentBrandCode, productCode) {
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

/**
 * Lưu cập nhật thương hiệu sản phẩm
 */
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