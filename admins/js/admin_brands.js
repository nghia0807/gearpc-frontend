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

// Hide loading overlay when page is fully loaded
window.addEventListener('load', hideLoading);

// Add Brand: convert image to base64
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imgPreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('imageBase64').value = evt.target.result.split(',')[1];
            preview.src = evt.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('imageBase64').value = '';
        preview.src = 'https://via.placeholder.com/80x80?text=No+Image';
    }
});

// Edit Brand: fill modal with data
document.querySelectorAll('.editBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_code').value = this.dataset.code;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_imageBase64').value = '';
        var preview = document.getElementById('editImgPreview');
        var image = this.dataset.image;
        if (image && image.length > 0) {
            if (image.startsWith('http') || image.startsWith('data:image')) {
                preview.src = image;
            } else {
                preview.src = 'data:image/png;base64,' + image;
            }
        } else {
            preview.src = 'https://via.placeholder.com/80x80?text=No+Image';
        }
        document.getElementById('edit_image').value = '';
        var editModal = new bootstrap.Modal(document.getElementById('editModal'));
        editModal.show();
    });
});

// Edit Brand: convert image to base64
document.getElementById('edit_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('editImgPreview');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            document.getElementById('edit_imageBase64').value = evt.target.result.split(',')[1];
            preview.src = evt.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// --- Multiple delete logic for brands ---
const selectAllBrands = document.getElementById('selectAllBrands');
const brandCheckboxes = document.querySelectorAll('.brand-checkbox');
const btnDeleteSelectedBrands = document.getElementById('btnDeleteSelectedBrands');

function updateDeleteSelectedBrandBtn() {
    const anyChecked = Array.from(brandCheckboxes).some(cb => cb.checked);
    btnDeleteSelectedBrands.disabled = !anyChecked;
}

if (selectAllBrands) {
    selectAllBrands.addEventListener('change', function() {
        brandCheckboxes.forEach(cb => cb.checked = selectAllBrands.checked);
        updateDeleteSelectedBrandBtn();
    });
}
brandCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
        updateDeleteSelectedBrandBtn();
        if (!this.checked && selectAllBrands.checked) selectAllBrands.checked = false;
    });
});

btnDeleteSelectedBrands.addEventListener('click', function() {
    const codes = Array.from(brandCheckboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.getAttribute('data-code'));
    if (codes.length === 0) return;
    if (!confirm('Are you sure you want to delete the selected brands?')) return;
    
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
    input2.name = 'delete_brand';
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
document.addEventListener('DOMContentLoaded', function() {
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    toastElList.forEach(function(toastEl) {
        var toast = new bootstrap.Toast(toastEl);
        toast.show();
    });
});