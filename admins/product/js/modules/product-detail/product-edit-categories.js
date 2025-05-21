import { showToastAndReload } from '../../utils/product-utils.js';
import { cancelInlineEdit } from './product-edit-common.js';

/**
 * Hiển thị form chỉnh sửa danh mục sản phẩm
 */
export function showCategoryEditForm(field, currentCategoriesArray, productCode) {
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

/**
 * Lưu cập nhật danh mục sản phẩm
 */
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