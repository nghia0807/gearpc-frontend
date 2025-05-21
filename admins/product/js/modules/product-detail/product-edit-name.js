import { showToastAndReload } from '../../utils/product-utils.js';

/**
 * Khởi tạo chức năng chỉnh sửa tên sản phẩm
 */
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