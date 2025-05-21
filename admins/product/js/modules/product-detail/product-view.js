import { escapeHtml, formatDateTime, formatPrice } from '../../utils/product-utils.js';
import { setupInlineEditHandlers } from './product-edit-handlers.js';

/**
 * Khởi tạo chức năng xem chi tiết sản phẩm
 */
export function initProductDetailView() {
    document.querySelectorAll('.btn-view-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showProductDetail(productId);
        });
    });
}

/**
 * Hiển thị chi tiết sản phẩm trong modal
 */
function showProductDetail(productId) {
    const modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
    const contentDiv = document.getElementById('viewProductModalContent');
    const alertDiv = document.getElementById('viewProductAlert');
    alertDiv.innerHTML = '';
    contentDiv.innerHTML = '<div class="text-muted">Loading product information...</div>';
    modal.show();

    // Use PHP API handler to get product detail
    const formData = new FormData();
    formData.append('action', 'getProductDetail');
    formData.append('productId', productId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        if (!response.ok) {
            throw new Error(`HTTP error: ${response.status} ${response.statusText}`);
        }
        return await response.json();
    })
    .then(data => {
        if (!data.success || !data.data) {
            contentDiv.innerHTML = `<div class="alert alert-warning">
                ${data.message || 'Could not load product details. Please try again.'}
            </div>`;
            return;
        }
        
        // Debug: Log the full API response
        console.log("Full API response:", data.data);
        
        contentDiv.innerHTML = renderProductDetail(data.data);
        // Setup inline editing handlers after rendering the content
        setupInlineEditHandlers(data.data);
    })
    .catch(err => {
        console.error('Error fetching product details:', err);
        contentDiv.innerHTML = `<div class="alert alert-danger">
            Server connection error, please try again.<br>
            <small class="text-muted">${err.message || ''}</small>
        </div>`;
    });
}

/**
 * Render chi tiết sản phẩm thành HTML
 */
function renderProductDetail(data) {
    const info = data.productInfo || {};
    const price = data.price || {};
    const detail = data.productDetail || {};
    const options = Array.isArray(data.productOptions) ? data.productOptions : [];
    const gifts = Array.isArray(data.gifts) ? data.gifts : [];
    const createdDate = data.createdDate ? formatDateTime(data.createdDate) : '';
    const createdBy = escapeHtml(data.createdBy || '');

    let imgThumb = info.imageUrl ? `<img src="${escapeHtml(info.imageUrl)}" class="modal-product-thumb me-2 mb-2" alt="Product Image">` :
        `<img src="https://via.placeholder.com/96x96?text=No+Image" class="modal-product-thumb me-2 mb-2" alt="No Image">`;

    // Add image display with edit button
    imgThumb = `
        <div class="position-relative">
            <span id="productMainImage-display">${imgThumb}</span>
            <button class="btn btn-sm edit-btn p-1" data-field="productMainImage" 
                data-code="${escapeHtml(info.code)}" 
                title="Edit Product Image">
                <i class="fa-solid fa-pen text-warning"></i>
            </button>
        </div>
    `;

    let imgList = '';
    if (detail.image && detail.image.length > 0) {
        imgList = detail.image.map(img =>
            `<img src="${escapeHtml(img.url)}" alt="Ảnh phụ" title="priority: ${img.priority}" />`
        ).join('');
        imgList = `<div class="modal-product-img-list mb-2">${imgList}</div>`;
    }

    // Create categories display with edit button - simplified
    const categoryCodes = Array.isArray(info.categoryCode) ? info.categoryCode : 
                         (Array.isArray(info.category) ? info.category : []);
    
    const categoryNames = Array.isArray(info.category) ? info.category : 
                          (info.category ? [info.category] : []);
    
    const categoriesDisplay = `
        <span class="modal-product-label fw-bold text-primary">Categories:</span> 
        <span id="productCategories-display">${categoryNames.map(escapeHtml).join(', ') || 'None'}</span>
        <button class="btn btn-sm edit-btn p-1" data-field="productCategories" 
            data-code="${escapeHtml(info.code)}" 
            data-categories="${escapeHtml(JSON.stringify(categoryCodes))}" 
            title="Edit Product Categories">
            <i class="fa-solid fa-pen text-warning"></i>
        </button><br>
    `;

    let descList = '';
    if (detail.description && detail.description.length > 0) {
        descList = '<div class="mb-2"><span class="modal-product-label fw-bold">Description:</span><ul class="ps-4" style="list-style-type: disc; margin-bottom: 0;">';
        detail.description.forEach(d => {
            descList += `<li>
                <span class="modal-product-label fw-bold">${escapeHtml(d.name)}:</span> ${escapeHtml(d.descriptionText)}
            </li>`;
        });
        descList += '</ul></div>';
    }

    let optionList = '';
    if (options.length > 0) {
        optionList = '<div class="mb-2">';
        options.forEach(opt => {
            optionList += `<div class="modal-product-option mb-1"><span class="modal-product-label fw-bold">${escapeHtml(opt.title)}:</span> `;
            if (Array.isArray(opt.options)) {
                optionList += opt.options.map(o =>
                    `<span class="${o.selected ? 'selected' : ''}">${escapeHtml(o.label)}</span>`
                ).join(', ');
            }
            optionList += '</div>';
        });
        optionList += '</div>';
    }

    // Extract gift codes for edit button
    const giftCodes = gifts.map(gift => gift.code || '').filter(code => code !== '');

    // Gift list display with edit button
    let giftList = '';
    if (!gifts || gifts.length === 0) {
        giftList = '<span id="productGifts-display"><div class="text-muted">No gifts</div></span>';
    } else {
        giftList = '<span id="productGifts-display"><div class="mb-2">';
        gifts.forEach((g, idx) => {
            if (g && g.image) {
                giftList += `<img src="${escapeHtml(g.image)}" alt="${escapeHtml(g.name || '')}" title="${escapeHtml(g.name || '')}" style="width:48px;height:48px;object-fit:cover;border-radius:6px;margin-right:6px;margin-bottom:4px;background:#eee;">`;
            }
        });
        giftList += '</div></span>';
    }

    // Add edit button for gifts
    const giftsDisplay = `
        <span class="modal-product-label fw-bold text-primary">Gifts:</span> 
        ${giftList}
        <button class="btn btn-sm edit-btn p-1" data-field="productGifts" 
            data-code="${escapeHtml(info.code)}" 
            data-gifts="${escapeHtml(JSON.stringify(giftCodes))}" 
            title="Edit Product Gifts">
            <i class="fa-solid fa-pen text-warning"></i>
        </button>
    `;

    let priceHtml = `<div class="row g-2">
        <div class="col-md-6">
            <span class="modal-product-label fw-bold">Original Price:</span>
            <span class="text-secondary">${formatPrice(price.originalPrice)}</span><span class="text-secondary">₫</span>
        </div>
        <div class="col-md-6">
            <span class="modal-product-label fw-bold">Current Price:</span>
            <span class="text-success">${formatPrice(price.currentPrice)}</span><span class="text-success">₫</span>
        </div>
    </div>`;

    return `
    <div class="card border-0 shadow-sm mb-0" style="background:#f8f9fb;">
        <div class="card-body pb-2">
            <div class="row">
                <div class="col-md-4 text-center">
                    <div class="mb-2">${imgThumb}</div>
                    ${imgList}
                </div>
                <div class="col-md-8">
                    <div class="mb-2">
                        <span class="modal-product-label fw-bold text-primary">ID:</span> ${escapeHtml(info.id)}<br>
                        <span class="modal-product-label fw-bold text-primary">Product Code:</span> ${escapeHtml(info.code)}<br>
                        <span class="modal-product-label fw-bold text-primary">Name:</span> 
                        <span id="productName-display">${escapeHtml(info.name)}</span>
                        <button class="btn btn-sm edit-btn p-1" data-field="productName" data-value="${escapeHtml(info.name)}" data-code="${escapeHtml(info.code)}" title="Edit Product Name">
                            <i class="fa-solid fa-pen text-warning"></i>
                        </button><br>
                        <span class="modal-product-label fw-bold text-primary">Status:</span> ${escapeHtml(info.status)}<br>
                        ${categoriesDisplay}
                        <span class="modal-product-label fw-bold text-primary">Brand:</span> 
                        <span id="productBrand-display">${escapeHtml(info.brand)}</span>
                        <button class="btn btn-sm edit-btn p-1" data-field="productBrand" data-value="${escapeHtml(info.brandCode || '')}" data-code="${escapeHtml(info.code)}" title="Edit Product Brand">
                            <i class="fa-solid fa-pen text-warning"></i>
                        </button>
                    </div>
                    <div class="mb-2">${priceHtml}</div>
                    <div class="mb-2">
                        <span class="modal-product-label fw-bold text-primary">Short Description:</span> 
                        ${escapeHtml(detail.shortDescription || '')}
                    </div>
                    ${descList}
                    ${optionList}
                    <div class="mb-2">
                        ${giftsDisplay}
                    </div>
                    <div class="modal-product-meta small text-muted">
                        <span class="modal-product-label fw-bold">Created:</span> ${createdDate}<br>
                        <span class="modal-product-label fw-bold">Created by:</span> ${createdBy}
                    </div>
                </div>
            </div>
        </div>
    </div>
    `;
}