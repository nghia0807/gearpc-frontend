import { showInlineEditForm } from './product-edit-common.js';
import { showBrandEditForm } from './product-edit-brand.js';
import { showCategoryEditForm } from './product-edit-categories.js';
import { showGiftEditForm } from './product-edit-gifts.js';

/**
 * Thiết lập các sự kiện xử lý nút chỉnh sửa inline
 */
export function setupInlineEditHandlers(productData) {
    const info = productData.productInfo || {};
    
    // Add event listeners for edit buttons
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const field = this.getAttribute('data-field');
            const value = this.getAttribute('data-value');
            const code = this.getAttribute('data-code') || info.code;
            
            if (field === 'productName') {
                showInlineEditForm(field, 'text', value, code);
            } else if (field === 'productBrand') {
                showBrandEditForm(field, value, code);
            } else if (field === 'productCategories') {
                // Parse the categories data from the button attribute
                let categoriesArray = [];
                try {
                    const categoriesData = this.getAttribute('data-categories');
                    if (categoriesData) {
                        categoriesArray = JSON.parse(categoriesData);
                    }
                } catch (e) {
                    console.error('Error parsing categories data', e);
                }
                showCategoryEditForm(field, categoriesArray, code);
            } else if (field === 'productGifts') {
                // Parse the gifts data from the button attribute
                let giftsArray = [];
                try {
                    const giftsData = this.getAttribute('data-gifts');
                    if (giftsData) {
                        giftsArray = JSON.parse(giftsData);
                    }
                } catch (e) {
                    console.error('Error parsing gifts data', e);
                }
                showGiftEditForm(field, giftsArray, code);
            }
        });
    });
}