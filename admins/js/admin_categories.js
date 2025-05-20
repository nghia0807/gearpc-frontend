document.addEventListener('DOMContentLoaded', function() {
    // Loading overlay functionality
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    function showLoading() {
        loadingOverlay.classList.add('active');
    }
    
    function hideLoading() {
        loadingOverlay.classList.remove('active');
    }
    
    // Edit button functionality
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            var editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        });
    });

    // --- Multiple delete logic for categories ---
    const selectAllCategories = document.getElementById('selectAllCategories');
    const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
    const btnDeleteSelectedCategories = document.getElementById('btnDeleteSelectedCategories');

    function updateDeleteSelectedBtn() {
        const anyChecked = Array.from(categoryCheckboxes).some(cb => cb.checked);
        btnDeleteSelectedCategories.disabled = !anyChecked;
    }

    if (selectAllCategories) {
        selectAllCategories.addEventListener('change', function() {
            categoryCheckboxes.forEach(cb => cb.checked = selectAllCategories.checked);
            updateDeleteSelectedBtn();
        });
    }
    categoryCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateDeleteSelectedBtn();
            if (!this.checked && selectAllCategories.checked) selectAllCategories.checked = false;
        });
    });

    btnDeleteSelectedCategories.addEventListener('click', function() {
        const codes = Array.from(categoryCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.getAttribute('data-code'));
        if (codes.length === 0) return;
        if (!confirm('Are you sure you want to delete the selected categories?')) return;
        
        // Show loading before submitting
        showLoading();
        
        // Submit via hidden form (POST)
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'delete_codes';
        input.value = JSON.stringify(codes);
        form.appendChild(input);
        const input2 = document.createElement('input');
        input2.type = 'hidden';
        input2.name = 'delete_category';
        input2.value = '1';
        form.appendChild(input2);
        document.body.appendChild(form);
        form.submit();
    });
    
    // Add form submission handlers for loading indicator
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            // Don't show loading for forms that aren't submitting to API
            if (this.getAttribute('data-no-loading') === 'true') return;
            showLoading();
        });
    });
    
    // Show all toasts on page load
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.forEach(function(toastEl) {
        var toast = new bootstrap.Toast(toastEl);
        toast.show();
    });
    
    // Hide loading overlay when page is fully loaded
    window.addEventListener('load', hideLoading);
});