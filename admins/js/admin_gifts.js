document.addEventListener('DOMContentLoaded', function () {
    // Loading overlay functionality
    const loadingOverlay = document.getElementById('loadingOverlay');
    
    function showLoading() {
        loadingOverlay.classList.add('active');
    }
    
    function hideLoading() {
        loadingOverlay.classList.remove('active');
    }

    // --- Edit modal logic ---
    document.querySelectorAll('.editGiftBtn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const code = this.getAttribute('data-code');
            const name = this.getAttribute('data-name');
            const image = this.getAttribute('data-image');
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_code_display').value = code;
            document.getElementById('edit_name').value = name;
            const imgPreview = document.getElementById('edit_image_preview');
            if (image) {
                imgPreview.src = image;
                imgPreview.style.display = '';
            } else {
                imgPreview.src = '';
                imgPreview.style.display = 'none';
            }
            // Clear file input
            document.getElementById('edit_image').value = '';
        });
    });

    // --- Gift selection logic ---
    const selectAllGifts = document.getElementById('selectAllGifts');
    const giftCheckboxes = document.querySelectorAll('.gift-checkbox');
    const btnDeleteSelectedGifts = document.getElementById('btnDeleteSelectedGifts');

    function updateDeleteSelectedBtn() {
        const anyChecked = Array.from(giftCheckboxes).some(cb => cb.checked);
        btnDeleteSelectedGifts.disabled = !anyChecked;
    }

    if (selectAllGifts) {
        selectAllGifts.addEventListener('change', function() {
            giftCheckboxes.forEach(cb => cb.checked = selectAllGifts.checked);
            updateDeleteSelectedBtn();
        });
    }
    
    giftCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateDeleteSelectedBtn();
            if (!this.checked && selectAllGifts && selectAllGifts.checked) {
                selectAllGifts.checked = false;
            }
        });
    });

    btnDeleteSelectedGifts.addEventListener('click', function() {
        const codes = Array.from(giftCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.getAttribute('data-code'));
        if (codes.length === 0) return;
        if (!confirm('Are you sure you want to delete the selected gifts?')) return;
        
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
        input2.name = 'delete_gift';
        input2.value = '1';
        form.appendChild(input2);
        document.body.appendChild(form);
        form.submit();
    });

    // --- Single delete logic ---
    document.querySelectorAll('.btn-delete-gift').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            if (!code) return;
            if (!confirm('Are you sure you want to delete this gift?')) return;
            
            // Show loading before submitting
            showLoading();
            
            // Submit via hidden form (POST)
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_code';
            input.value = code;
            form.appendChild(input);
            const input2 = document.createElement('input');
            input2.type = 'hidden';
            input2.name = 'delete_gift';
            input2.value = '1';
            form.appendChild(input2);
            document.body.appendChild(form);
            form.submit();
        });
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