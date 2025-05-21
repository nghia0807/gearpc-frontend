// Module for managing product main image updates
import { fileToBase64, showToastAndReload } from '../../utils/product-utils.js';

export function initProductMainImageEdit() {
    document.addEventListener('click', async function(e) {
        // Check if clicked element is a product main image edit button
        if (e.target.closest('.btn-edit-product-main-image')) {
            const productMainImageButton = e.target.closest('.btn-edit-product-main-image');
            const productCode = productMainImageButton.dataset.productCode;
            const productImageContainer = productMainImageButton.closest('.position-relative');
            const currentImage = productImageContainer.querySelector('img');
            
            // Create file input element
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/jpeg,image/png,image/gif,image/webp';
            
            // When file is selected, process it
            fileInput.onchange = async function() {
                if (!fileInput.files || !fileInput.files[0]) return;
                
                const file = fileInput.files[0];
                
                // Check file type
                const validImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!validImageTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, PNG, GIF, WEBP)');
                    return;
                }
                
                // Check file size (max 5MB)
                const maxSizeInBytes = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSizeInBytes) {
                    alert('Image file is too large. Maximum size is 5MB.');
                    return;
                }
                
                // Create a loading indicator
                const alertContainer = document.getElementById('viewProductAlert');
                alertContainer.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fa fa-spinner fa-spin me-2"></i> Uploading and updating product image...
                    </div>
                `;
                
                try {
                    // Get the full data URL including the MIME type prefix
                    const dataUrl = await fileToBase64(file);
                    
                    // Show preview immediately
                    if (currentImage) {
                        currentImage.src = dataUrl;
                    }
                    
                    // Prepare form data for the request
                    const formData = new FormData();
                    formData.append('action', 'updateProductMainImage');
                    formData.append('productCode', productCode);
                    formData.append('imageBase64', dataUrl);  // Send the full data URL
                    formData.append('mimeType', file.type);
                    
                    console.log('Sending image update with mime type:', file.type);
                    
                    // Send request to server
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        throw new Error('Server error: ' + response.statusText);
                    }
                    
                    const result = await response.json();
                    console.log('API response:', result);
                    
                    if (result.success) {
                        alertContainer.innerHTML = `
                            <div class="alert alert-success">
                                <i class="fa fa-check-circle me-2"></i> Product image updated successfully!
                            </div>
                        `;
                    } else {
                        throw new Error(result.message || 'Failed to update product image');
                    }
                } catch (error) {
                    console.error('Image update error:', error);
                    alertContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-circle me-2"></i> ${error.message || 'Error updating product image'}
                        </div>
                    `;
                    
                    // Restore original image if update failed
                    if (currentImage && currentImage.dataset.originalSrc) {
                        currentImage.src = currentImage.dataset.originalSrc;
                    }
                }
            };
            
            // Store original image source for potential rollback
            if (currentImage) {
                currentImage.dataset.originalSrc = currentImage.src;
            }
            
            // Trigger file selection
            fileInput.click();
        }
    });
}