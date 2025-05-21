// Main entry point for admin products page
// Import all modules
import { initProductDeletion } from './modules/product-delete.js';
import { initVariantsSection } from './modules/product-variants.js';
import { initProductDetailView, initProductNameEdit } from './modules/product-detail.js';
import { initAddProduct } from './modules/product-add.js';

// Initialize all functionality when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize product deletion functionality
    initProductDeletion();
    
    // Initialize product variants
    initVariantsSection();
    
    // Initialize product detail viewing
    initProductDetailView();
    
    // Initialize product name editing
    initProductNameEdit();
    
    // Initialize add product functionality
    initAddProduct();
});