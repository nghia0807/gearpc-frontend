import { fileToBase64, showToastAndReload } from '../utils/product-utils.js';
import { gatherVariantsData } from './product-variants.js';

// Initialize add product functionality
export function initAddProduct() {
    const btnAddProduct = document.getElementById('btnAddProduct');
    const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
    const addProductForm = document.getElementById('addProductForm');
    const addProductAlert = document.getElementById('addProductAlert');
    const variantsSection = document.getElementById('variantsSection');

    // If any of these elements don't exist, exit
    if (!btnAddProduct || !addProductModal || !addProductForm || !addProductAlert) return;

    // On modal show, reset form
    btnAddProduct.addEventListener('click', function() {
        addProductForm.reset();
        addProductAlert.innerHTML = '';
        // Show the modal - this was missing
        addProductModal.show();
    });

    // Handle form submission
    addProductForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        addProductAlert.innerHTML = '';
        
        // Get the submit button and set loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Adding...';
        
        // Get main form fields
        const form = e.target;
        const name = form.name.value.trim();
        const code = form.code.value.trim();
        const status = form.status.value;
        const brandCode = form.brandCode.value;
        const categoriesCode = Array.from(form.querySelectorAll('input[name="categoriesCode[]"]:checked')).map(cb => cb.value);
        const giftCodes = Array.from(form.querySelectorAll('input[name="giftCodes[]"]:checked')).map(cb => cb.value);

        // Main image
        const imageFile = form.image.files[0];
        let imageBase64 = '';
        if (imageFile) {
            imageBase64 = await fileToBase64(imageFile);
        }

        // Gather variants from the form
        const variants = gatherVariantsData(variantsSection);
        
        // Process images for each variant option
        for (const variant of variants) {
            for (const option of variant.options) {
                // Process images if available
                if (option.imagesInput && option.imagesInput.files.length > 0) {
                    const priorityInputs = option.imagesInput.closest('.option-block')
                        .querySelectorAll('.variantOptionImagesPriority input[type=number]');
                    
                    for (let i = 0; i < option.imagesInput.files.length; ++i) {
                        const file = option.imagesInput.files[i];
                        let priority = i;
                        if (priorityInputs[i]) {
                            priority = parseInt(priorityInputs[i].value, 10) || i;
                        }
                        if (file) {
                            const base64Content = await fileToBase64(file);
                            option.imagesBase64.push({
                                base64Content,
                                priority
                            });
                        }
                    }
                }
                // Remove the imagesInput property as it can't be serialized
                delete option.imagesInput;
            }
        }

        // Build request body
        const body = {
            name,
            code,
            imageBase64,
            categoriesCode,
            status,
            brandCode,
            variants,
            giftCodes
        };

        // Use PHP API handler to add product
        try {
            const formData = new FormData();
            formData.append('action', 'addProduct');
            formData.append('productData', JSON.stringify(body));
            
            const resp = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            if (!resp.ok) throw new Error('Network response was not ok');
            const data = await resp.json();
            
            if (!data.success) {
                // Restore button state on error
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                throw new Error(data.message || 'Failed to add product');
            }
            // Show toast and reload page
            showToastAndReload('success', 'Product added successfully');
        } catch (err) {
            // Restore button state on error if not already handled
            if (submitBtn.disabled) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
            showToastAndReload('danger', err.message || 'Unable to add product');
        }
    });
}