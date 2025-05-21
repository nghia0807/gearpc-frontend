/**
 * Module chính cho chức năng chi tiết sản phẩm
 * Tách thành các module con cho dễ quản lý
 */

// Import các chức năng từ module con
import { initProductDetailView } from './product-detail/product-view.js';
import { initProductNameEdit } from './product-detail/product-edit-name.js';
import { initProductMainImageEdit } from './product-detail/product-edit-main-image.js';

// Export các chức năng để module khác có thể sử dụng
export { initProductDetailView, initProductNameEdit, initProductMainImageEdit };