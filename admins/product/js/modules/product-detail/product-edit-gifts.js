import { showToastAndReload, escapeHtml } from '../../utils/product-utils.js';
import { cancelInlineEdit } from './product-edit-common.js';

/**
 * Hiển thị form chỉnh sửa quà tặng sản phẩm
 */
export function showGiftEditForm(field, currentGiftsArray, productCode) {
    const displayElement = document.getElementById(`${field}-display`);
    const editButton = document.querySelector(`.edit-btn[data-field="${field}"]`);
    if (!displayElement) return;
    
    // Hide edit button
    if (editButton) editButton.classList.add('d-none');
    
    // Get gifts from hidden container
    const giftsContainer = document.getElementById('giftSelectOptions');
    let giftsHtml = '';
    
    if (giftsContainer) {
        // Create checkboxes from options
        const options = Array.from(giftsContainer.querySelectorAll('option'));
        if (options.length > 0) {
            giftsHtml = '<div class="border rounded p-3 bg-light mb-2 gifts-selector"><div class="row">';
            
            options.forEach(option => {
                const code = option.value;
                const name = option.textContent.trim();
                const image = option.getAttribute('data-image') || '';
                
                giftsHtml += `
                <div class="col-md-6 mb-2">
                    <div class="form-check d-flex align-items-center">
                        <input class="form-check-input" type="checkbox" 
                               value="${escapeHtml(code)}" 
                               id="modal_gift_${escapeHtml(code)}">
                        <label class="form-check-label ms-2" for="modal_gift_${escapeHtml(code)}">
                            ${escapeHtml(name)}
                            ${image ? `<img src="${escapeHtml(image)}" alt="${escapeHtml(name)}" style="width:24px;height:24px;object-fit:cover;border-radius:3px;margin-left:5px;">` : ''}
                        </label>
                    </div>
                </div>`;
            });
            
            giftsHtml += '</div></div>';
        }
    }
    
    // Create form with checkboxes and buttons
    const formHtml = `
        <div class="inline-edit-form">
            ${giftsHtml || '<div class="alert alert-info">No gifts available</div>'}
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
    
    // Check the current gifts
    setTimeout(() => {
        if (Array.isArray(currentGiftsArray) && currentGiftsArray.length > 0) {
            currentGiftsArray.forEach(giftCode => {
                // Try finding checkbox by ID
                let checkbox = document.querySelector(`.gifts-selector #modal_gift_${giftCode}`);
                
                // If not found, try by value
                if (!checkbox) {
                    checkbox = document.querySelector(`.gifts-selector input[value="${giftCode}"]`);
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
        const checkedGifts = [];
        document.querySelectorAll('.gifts-selector .form-check-input:checked').forEach(checkbox => {
            checkedGifts.push(checkbox.value);
        });
        saveGiftEdit(field, checkedGifts, productCode);
    });
    
    document.querySelector(`.cancel-inline-edit[data-field="${field}"]`)?.addEventListener('click', function() {
        cancelInlineEdit(field);
    });
}

/**
 * Lưu cập nhật quà tặng sản phẩm
 */
function saveGiftEdit(field, giftCodes, productCode) {
    const displayElement = document.getElementById(`${field}-display`);
    const alertDiv = document.getElementById('viewProductAlert');
    alertDiv.innerHTML = '';
    
    displayElement.innerHTML = `<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Updating...</span>`;
    
    // Use PHP API handler to update product gifts
    const formData = new FormData();
    formData.append('action', 'updateProductGifts');
    formData.append('productCode', productCode);
    formData.append('giftCodes', JSON.stringify(giftCodes));
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        if (!response.ok) throw new Error('Network response was not ok');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to update product gifts');
        }
        
        showToastAndReload('success', 'Product gifts updated successfully!');
    })
    .catch(err => {
        alertDiv.innerHTML = `<div class="alert alert-danger">${err.message || 'Error updating gifts'}</div>`;
        cancelInlineEdit(field);
    });
}