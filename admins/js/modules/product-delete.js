import { showToastAndReload } from '../utils/product-utils.js';

// Initialize product deletion functionality
export function initProductDeletion() {
    const selectAll = document.getElementById('selectAllProducts');
    const checkboxes = document.querySelectorAll('.product-checkbox');
    const btnDeleteSelected = document.getElementById('btnDeleteSelected');
    
    function updateDeleteSelectedBtn() {
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
        btnDeleteSelected.disabled = !anyChecked;
    }

    // Setup "Select All" checkbox
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateDeleteSelectedBtn();
        });
    }
    
    // Setup individual product checkboxes
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateDeleteSelectedBtn();
            if (!this.checked && selectAll && selectAll.checked) selectAll.checked = false;
        });
    });

    // Setup bulk delete button
    if (btnDeleteSelected) {
        btnDeleteSelected.addEventListener('click', function() {
            const codes = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.getAttribute('data-code'));
            if (codes.length === 0) return;
            if (!confirm('Are you sure you want to delete the selected products?')) return;
            deleteProductsByCodes(codes);
        });
    }

    // Setup individual delete buttons
    document.querySelectorAll('.btn-delete-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            if (!code) return;
            if (!confirm('Are you sure you want to delete this product?')) return;
            deleteProductsByCodes([code]);
        });
    });

    // Function to handle product deletion API call
    function deleteProductsByCodes(codes) {
        // Store the original button state
        const originalBtnText = btnDeleteSelected.innerHTML;
        btnDeleteSelected.disabled = true;
        btnDeleteSelected.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Deleting...';
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'deleteProducts');
        formData.append('codes', JSON.stringify(codes));
        
        // Post to PHP handler
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(async resp => {
            if (!resp.ok) throw new Error('Network response was not ok');
            const data = await resp.json();
            if (!data.success) {
                throw new Error(data.message || 'Product deletion failed');
            }
            showToastAndReload('success', data.message || 'Product(s) deleted successfully');
        })
        .catch(err => {
            // Restore button state on error
            btnDeleteSelected.disabled = false;
            btnDeleteSelected.innerHTML = originalBtnText;
            showToastAndReload('danger', err.message || 'Unable to delete product(s)');
        });
    }
}