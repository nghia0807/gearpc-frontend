document.addEventListener('DOMContentLoaded', function() {
    // Loading overlay functionality
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    function showLoading() {
        loadingOverlay.classList.add('active');
    }
    
    function hideLoading() {
        loadingOverlay.classList.remove('active');
    }
    
    // Helper function to preserve pagination when reloading the page
    function getPageUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const page = urlParams.get('page') || 0;
        return `?page=${page}`;
    }
    
    // Edit button functionality
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            const editModal = new bootstrap.Modal(document.getElementById("editModal"));
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
        form.action = getPageUrl(); // Preserve pagination
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
        form.addEventListener('submit', function(e) {
            // Don't show loading for forms that aren't submitting to API
            if (this.getAttribute('data-no-loading') === 'true') return;
            
            // Add the current page to the form action for regular forms
            if (!this.action || this.action === window.location.href) {
                const currentPageUrl = getPageUrl();
                const formAction = this.getAttribute('action') || '';
                if (!formAction.includes('page=')) {
                    if (formAction.includes('?')) {
                        this.action = formAction + '&' + currentPageUrl.substring(1);
                    } else {
                        this.action = formAction + currentPageUrl;
                    }
                }
            }
            showLoading();
        });
    });
    
    // Show all toasts on page load
    const toastElList = [].slice.call(document.querySelectorAll(".toast"));
    toastElList.forEach(function(toastEl) {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    });
    
    // Hide loading overlay when page is fully loaded
    window.addEventListener('load', hideLoading);
});