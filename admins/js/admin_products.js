// Helper function to convert file to base64
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        if (!file) {
            reject(new Error("No file provided"));
            return;
        }
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result.split(',')[1]);
        reader.onerror = error => reject(error);
        reader.readAsDataURL(file);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-view-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            showProductDetail(productId);
        });
    });

    // --- Product selection logic ---
    const selectAll = document.getElementById('selectAllProducts');
    const checkboxes = document.querySelectorAll('.product-checkbox');
    const btnDeleteSelected = document.getElementById('btnDeleteSelected');
    const jsAlertContainer = document.getElementById('jsAlertContainer');

    function updateDeleteSelectedBtn() {
        const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
        btnDeleteSelected.disabled = !anyChecked;
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateDeleteSelectedBtn();
        });
    }
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateDeleteSelectedBtn();
            if (!this.checked && selectAll && selectAll.checked) selectAll.checked = false;
        });
    });

    if (btnDeleteSelected) {
        btnDeleteSelected.addEventListener('click', function() {
            const codes = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.getAttribute('data-code'));
            if (codes.length === 0) return;
            if (!confirm('Are you sure you want to delete the selected products?')) return;
            deleteProductsByCodes(codes);
        });
    }

    // --- Single delete logic ---
    document.querySelectorAll('.btn-delete-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            if (!code) return;
            if (!confirm('Are you sure you want to delete this product?')) return;
            deleteProductsByCodes([code]);
        });
    });

    // Function for showing toast and reloading page
    function showToastAndReload(type, msg) {
        // Encode the toast parameters in the URL
        const redirectUrl = new URL(window.location.href);
        redirectUrl.searchParams.set('toast_type', type);
        redirectUrl.searchParams.set('toast_msg', msg);
        
        // Preserve existing page parameter if present
        if (window.location.search.includes('page=')) {
            const pageMatch = window.location.search.match(/page=(\d+)/);
            if (pageMatch && pageMatch[1]) {
                redirectUrl.searchParams.set('page', pageMatch[1]);
            }
        }
        
        // Redirect to show the toast
        window.location.href = redirectUrl.toString();
    }

    // Use PHP API handler to delete products
    function deleteProductsByCodes(codes) {
        // Store the original button state
        const originalBtnText = btnDeleteSelected.innerHTML;
        btnDeleteSelected.disabled = true;
        btnDeleteSelected.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Deleting...';
        
        // Create form data
        const formData = new FormData();
        formData.append('action', 'deleteProducts');
        formData.append('codes', JSON.stringify(codes));
        
        // Post to PHP handler
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(async resp => {
            if (!resp.ok) throw new Error('Network response was not ok');
            const data = await resp.json();
            if (!data.success) {
                throw new Error(data.message || 'Product deletion failed');
            }
            showToastAndReload('success', data.message || 'Product(s) deleted successfully');
        })
        .catch(err => {
            // Restore button state on error
            btnDeleteSelected.disabled = false;
            btnDeleteSelected.innerHTML = originalBtnText;
            showToastAndReload('danger', err.message || 'Unable to delete product(s)');
        });
    }

    // --- Add Product Modal logic ---
    const btnAddProduct = document.getElementById('btnAddProduct');
    const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
    const addProductForm = document.getElementById('addProductForm');
    const addProductAlert = document.getElementById('addProductAlert');

    // --- VARIANTS DYNAMIC ---
    const variantsSection = document.getElementById('variantsSection');
    const btnAddVariant = document.getElementById('btnAddVariant');
    let variantCount = 0;
    const MAX_VARIANTS = 2;

    function createVariantBlock(idx) {
        return `
        <div class="variant-block border rounded p-3 mb-4 bg-white shadow-sm" data-variant-idx="${idx}">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-info"><i class="fa-solid fa-cubes me-2"></i>Variant #${idx + 1}</h6>
            <button type="button" class="btn btn-sm btn-outline-danger btnRemoveVariant" ${idx === 0 ? 'style="display:none;"' : ''}>
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold text-info">Variant Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="variant_optionTitle_${idx}" placeholder="e.g. Color, Size, Memory" required>
            <div class="form-text">The name for this variant group (e.g. "Color", "Size")</div>
          </div>
          <div class="variant-options-container">
            <label class="form-label fw-semibold text-info">Options for this variant</label>
            <div class="variant-options-list" data-variant-idx="${idx}"></div>
            <div class="text-end mt-2">
              <button type="button" class="btn btn-sm btn-outline-info btnAddOption" data-variant-idx="${idx}">
                <i class="fa-solid fa-plus"></i> Add Option
              </button>
            </div>
          </div>
        </div>
        `;
    }

    function createOptionBlock(variantIdx, optionIdx) {
        return `
        <div class="option-block border rounded p-3 mb-3 bg-light" data-option-idx="${optionIdx}">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-secondary"><i class="fa-solid fa-tag me-2"></i>Option #${optionIdx + 1}</h6>
            <button type="button" class="btn btn-sm btn-outline-danger btnRemoveOption" ${optionIdx === 0 ? 'style="display:none;"' : ''}>
              <i class="fa-solid fa-times"></i>
            </button>
          </div>
          
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary">Option Label <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="variant_${variantIdx}_optionLabel_${optionIdx}" placeholder="e.g. Red, XL, 512GB" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary">Short Description</label>
              <input type="text" class="form-control" name="variant_${variantIdx}_shortDescription_${optionIdx}" placeholder="Brief description (optional)">
            </div>
          </div>
          
          <div class="row g-3 mt-1">
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary">Original Price <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" class="form-control" name="variant_${variantIdx}_originalPrice_${optionIdx}" required>
                <span class="input-group-text">₫</span>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold text-secondary">Current Price <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" class="form-control" name="variant_${variantIdx}_currentPrice_${optionIdx}" required>
                <span class="input-group-text">₫</span>
              </div>
            </div>
          </div>
          
          <!-- Descriptions Section -->
          <div class="mt-3 p-2 border rounded bg-white">
            <label class="form-label fw-semibold text-secondary">
              <i class="fa-solid fa-list me-1"></i>Detailed Descriptions
            </label>
            <div class="variantDescriptionsList" data-variant-idx="${variantIdx}" data-option-idx="${optionIdx}"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2 btnAddDescription" data-variant-idx="${variantIdx}" data-option-idx="${optionIdx}">
              <i class="fa-solid fa-plus"></i> Add Description
            </button>
          </div>
          
          <!-- Images Section -->
          <div class="mt-3 p-2 border rounded bg-white">
            <label class="form-label fw-semibold text-secondary">
              <i class="fa-solid fa-images me-1"></i>Option Images
            </label>
            <input type="file" class="form-control mb-2 variant-option-images-input" 
              name="variant_${variantIdx}_option_${optionIdx}_images[]" accept="image/*" multiple>
            <div class="form-text mb-2">Select multiple images for this option</div>
            <div class="variantOptionImagesPreview d-flex flex-wrap gap-2 mt-2"></div>
            <div class="variantOptionImagesPriority mt-2"></div>
            <!-- Add a container for legacy image rows if needed -->
            <div class="variantImagesList"></div>
          </div>
        </div>
        `;
    }

    function createDescriptionRow(variantIdx, optionIdx, descIdx = 0, name = '', text = '', priority = 0) {
        return `
        <div class="variant-desc-row border-bottom pb-2 mb-2" data-desc-idx="${descIdx}">
          <div class="row g-2">
            <div class="col-md-3">
              <label class="form-label small">Name</label>
              <input type="text" class="form-control form-control-sm" 
                name="variant_${variantIdx}_option_${optionIdx}_desc_name[]" 
                placeholder="Description Name" value="${name}">
            </div>
            <div class="col-md-6">
              <label class="form-label small">Content</label>
              <input type="text" class="form-control form-control-sm" 
                name="variant_${variantIdx}_option_${optionIdx}_desc_text[]" 
                placeholder="Description Content" value="${text}">
            </div>
            <div class="col-md-2">
              <label class="form-label small">Priority</label>
              <input type="number" class="form-control form-control-sm" 
                name="variant_${variantIdx}_option_${optionIdx}_desc_priority[]" 
                placeholder="Priority" value="${priority}">
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button type="button" class="btn btn-sm btn-outline-danger btn-remove-desc">
                <i class="fa-solid fa-trash-alt"></i>
              </button>
            </div>
          </div>
        </div>`;
    }
    function createImageRow(variantIdx, optionIdx, imgIdx = 0, priority = 0) {
        return `
        <div class="input-group mb-1 variant-img-row" data-img-idx="${imgIdx}">
            <input type="file" class="form-control" name="variant_${variantIdx}_option_${optionIdx}_image[]" accept="image/*">
            <input type="number" class="form-control" name="variant_${variantIdx}_option_${optionIdx}_image_priority[]" placeholder="Priority" value="${priority}">
            <button type="button" class="btn btn-outline-danger btn-remove-img" tabindex="-1">&times;</button>
        </div>`;
    }

    function refreshVariantRemoveBtns() {
        variantsSection.querySelectorAll('.btnRemoveVariant').forEach(btn => {
            btn.onclick = function() {
                btn.closest('.variant-block').remove();
                variantCount--;
                updateAddVariantBtn();
            };
        });
    }
    function refreshOptionRemoveBtns() {
        variantsSection.querySelectorAll('.btnRemoveOption').forEach(btn => {
            btn.onclick = function() {
                btn.closest('.option-block').remove();
            };
        });
    }
    function refreshDescriptionRemoveBtns() {
        variantsSection.querySelectorAll('.btn-remove-desc').forEach(btn => {
            btn.onclick = function() {
                btn.closest('.variant-desc-row').remove();
            };
        });
    }
    function refreshImageRemoveBtns() {
        variantsSection.querySelectorAll('.btn-remove-img').forEach(btn => {
            btn.onclick = function() {
                btn.closest('.variant-img-row').remove();
            };
        });
    }
    function updateAddVariantBtn() {
        btnAddVariant.disabled = variantCount >= MAX_VARIANTS;
    }

    function addOptionToVariant(variantIdx) {
        const variantBlock = variantsSection.querySelector(`.variant-block[data-variant-idx="${variantIdx}"]`);
        const optionsList = variantBlock.querySelector('.variant-options-list');
        const optionIdx = optionsList.children.length;
        optionsList.insertAdjacentHTML('beforeend', createOptionBlock(variantIdx, optionIdx));
        refreshOptionRemoveBtns();

        // Add one description row by default
        const descList = optionsList.lastElementChild.querySelector('.variantDescriptionsList');
        descList.insertAdjacentHTML('beforeend', createDescriptionRow(variantIdx, optionIdx, 0, '', '', 0));
        refreshDescriptionRemoveBtns();

        // Add handler for add description button
        optionsList.lastElementChild.querySelector('.btnAddDescription').onclick = function() {
            const descList = this.parentElement.querySelector('.variantDescriptionsList');
            const descIdx = descList.children.length;
            descList.insertAdjacentHTML('beforeend', createDescriptionRow(variantIdx, optionIdx, descIdx, '', '', descIdx));
            refreshDescriptionRemoveBtns();
        };
    }

    btnAddVariant.onclick = function() {
        if (variantCount >= MAX_VARIANTS) return;
        const idx = variantCount;
        variantsSection.insertAdjacentHTML('beforeend', createVariantBlock(idx));
        variantCount++;
        updateAddVariantBtn();
        refreshVariantRemoveBtns();

        // Add one option by default
        addOptionToVariant(idx);

        // Add handler for add option button
        const variantBlock = variantsSection.querySelector(`.variant-block[data-variant-idx="${idx}"]`);
        variantBlock.querySelector('.btnAddOption').onclick = function() {
            addOptionToVariant(idx);
        };
    };

    // On modal show, reset variants
    btnAddProduct.addEventListener('click', function() {
        addProductForm.reset();
        addProductAlert.innerHTML = '';
        variantsSection.innerHTML = '';
        variantCount = 0;
        btnAddVariant.disabled = false;
        // Thêm biến thể đầu tiên (không dùng await)
        btnAddVariant.click();
        addProductModal.show();
    });

    // --- Gather form data for variants/options ---
    addProductForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        addProductAlert.innerHTML = '';
        
        // Get the submit button and set loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Adding...';
        
        // ...existing code for main fields...
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

        // --- Gather variants ---
        const variants = [];
        const variantBlocks = Array.from(variantsSection.querySelectorAll('.variant-block'));
        for (let vIdx = 0; vIdx < variantBlocks.length; ++vIdx) {
            const variantBlock = variantBlocks[vIdx];
            const optionTitle = variantBlock.querySelector(`input[name="variant_optionTitle_${vIdx}"]`).value.trim();
            const options = [];
            const optionBlocks = Array.from(variantBlock.querySelectorAll('.option-block'));
            for (let oIdx = 0; oIdx < optionBlocks.length; ++oIdx) {
                const optionBlock = optionBlocks[oIdx];
                const optionLabel = optionBlock.querySelector(`input[name="variant_${vIdx}_optionLabel_${oIdx}"]`).value.trim();
                const originalPrice = parseInt(optionBlock.querySelector(`input[name="variant_${vIdx}_originalPrice_${oIdx}"]`).value, 10) || 0;
                const currentPrice = parseInt(optionBlock.querySelector(`input[name="variant_${vIdx}_currentPrice_${oIdx}"]`).value, 10) || 0;
                const shortDescription = optionBlock.querySelector(`input[name="variant_${vIdx}_shortDescription_${oIdx}"]`).value.trim();

                // Descriptions
                const descNames = Array.from(optionBlock.querySelectorAll(`input[name="variant_${vIdx}_option_${oIdx}_desc_name[]"]`)).map(i => i.value.trim());
                const descTexts = Array.from(optionBlock.querySelectorAll(`input[name="variant_${vIdx}_option_${oIdx}_desc_text[]"]`)).map(i => i.value.trim());
                const descPriorities = Array.from(optionBlock.querySelectorAll(`input[name="variant_${vIdx}_option_${oIdx}_desc_priority[]"]`)).map(i => parseInt(i.value, 10) || 0);
                const descriptions = [];
                for (let i = 0; i < descNames.length; ++i) {
                    if (descNames[i] && descTexts[i]) {
                        descriptions.push({
                            name: descNames[i],
                            descriptionText: descTexts[i],
                            priority: descPriorities[i]
                        });
                    }
                }

                // Option Images (multiple, with priority)
                const imagesInput = optionBlock.querySelector(`input[name="variant_${vIdx}_option_${oIdx}_images[]"]`);
                let imagesBase64 = [];
                if (imagesInput && imagesInput.files.length > 0) {
                    // Get priorities from the UI (input[type=number] generated below)
                    const priorityInputs = optionBlock.querySelectorAll('.variantOptionImagesPriority input[type=number]');
                    for (let i = 0; i < imagesInput.files.length; ++i) {
                        const file = imagesInput.files[i];
                        let priority = i;
                        if (priorityInputs[i]) {
                            priority = parseInt(priorityInputs[i].value, 10) || i;
                        }
                        if (file) {
                            const base64Content = await fileToBase64(file);
                            imagesBase64.push({
                                base64Content,
                                priority
                            });
                        }
                    }
                }

                options.push({
                    optionLabel,
                    originalPrice,
                    currentPrice,
                    descriptions,
                    imagesBase64,
                    shortDescription
                });
            }
            variants.push({
                optionTitle,
                options
            });
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
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return await response.json();
        })
        .then(data => {
            if (!data.success || !data.data) {
                contentDiv.innerHTML = '<div class="alert alert-danger">Unable to load product information.</div>';
                return;
            }
            contentDiv.innerHTML = renderProductDetail(data.data);
            // Setup inline editing handlers after rendering the content
            setupInlineEditHandlers(data.data);
        })
        .catch(err => {
            contentDiv.innerHTML = '<div class="alert alert-danger">Server connection error, please try again.</div>';
        });
    }

    function renderProductDetail(data) {
        function esc(str) {
            return typeof str === 'string' ? str.replace(/[&<>"']/g, function(m) {
                return ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
                })[m];
            }) : '';
        }
        const info = data.productInfo || {};
        const price = data.price || {};
        const detail = data.productDetail || {};
        const options = Array.isArray(data.productOptions) ? data.productOptions : [];
        const gifts = Array.isArray(data.gifts) ? data.gifts : [];
        const createdDate = data.createdDate ? formatDateTime(data.createdDate) : '';
        const createdBy = esc(data.createdBy || '');

        let imgThumb = info.imageUrl ? `<img src="${esc(info.imageUrl)}" class="modal-product-thumb me-2 mb-2" alt="Product Image">` :
            `<img src="https://via.placeholder.com/96x96?text=No+Image" class="modal-product-thumb me-2 mb-2" alt="No Image">`;

        let imgList = '';
        if (detail.image && detail.image.length > 0) {
            imgList = detail.image.map(img =>
                `<img src="${esc(img.url)}" alt="Ảnh phụ" title="priority: ${img.priority}" />`
            ).join('');
            imgList = `<div class="modal-product-img-list mb-2">${imgList}</div>`;
        }

        let categories = Array.isArray(info.category) ? info.category.map(esc).join(', ') : '';

        let descList = '';
        if (detail.description && detail.description.length > 0) {
            descList = '<div class="mb-2"><span class="modal-product-label fw-bold">Description:</span><ul class="ps-4" style="list-style-type: disc; margin-bottom: 0;">';
            detail.description.forEach(d => {
                descList += `<li>
                    <span class="modal-product-label fw-bold">${esc(d.name)}:</span> ${esc(d.descriptionText)}
                </li>`;
            });
            descList += '</ul></div>';
        }

        let optionList = '';
        if (options.length > 0) {
            optionList = '<div class="mb-2">';
            options.forEach(opt => {
                optionList += `<div class="modal-product-option mb-1"><span class="modal-product-label fw-bold">${esc(opt.title)}:</span> `;
                if (Array.isArray(opt.options)) {
                    optionList += opt.options.map(o =>
                        `<span class="${o.selected ? 'selected' : ''}">${esc(o.label)}</span>`
                    ).join(', ');
                }
                optionList += '</div>';
            });
            optionList += '</div>';
        }

        // Extract gift codes for edit functionality
        const giftCodes = gifts.map(g => g.code || '').filter(Boolean);
        let giftCodesJson = JSON.stringify(giftCodes);

        let giftList = '';
        if (!gifts || gifts.length === 0) {
            giftList = '<div class="text-muted">No gifts</div>';
        } else {
            giftList = '<div class="mb-2">';
            gifts.forEach((g, idx) => {
                if (g && g.image) {
                    giftList += `<img src="${esc(g.image)}" alt="${esc(g.name || '')}" title="${esc(g.name || '')}" style="width:48px;height:48px;object-fit:cover;border-radius:6px;margin-right:6px;margin-bottom:4px;background:#eee;">`;
                }
            });
            giftList += '</div>';
        }

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
                            <span class="modal-product-label fw-bold text-primary">ID:</span> ${esc(info.id)}<br>
                            <span class="modal-product-label fw-bold text-primary">Product Code:</span> ${esc(info.code)}<br>
                            <span class="modal-product-label fw-bold text-primary">Name:</span> 
                            <span id="productName-display">${esc(info.name)}</span>
                            <button class="btn btn-sm edit-btn p-1" data-field="productName" data-value="${esc(info.name)}" data-code="${esc(info.code)}" title="Edit Product Name">
                                <i class="fa-solid fa-pen text-warning"></i>
                            </button><br>
                            <span class="modal-product-label fw-bold text-primary">Status:</span> ${esc(info.status)}<br>
                            <span class="modal-product-label fw-bold text-primary">Categories:</span> ${categories}<br>
                            <span class="modal-product-label fw-bold text-primary">Brand:</span> 
                            <span id="productBrand-display">${esc(info.brand)}</span>
                            <button class="btn btn-sm edit-btn p-1" data-field="productBrand" data-value="${esc(info.brandCode || '')}" data-code="${esc(info.code)}" title="Edit Product Brand">
                                <i class="fa-solid fa-pen text-warning"></i>
                            </button>
                        </div>
                        <div class="mb-2">${priceHtml}</div>
                        <div class="mb-2">
                            <span class="modal-product-label fw-bold text-primary">Short Description:</span> 
                            ${esc(detail.shortDescription || '')}
                        </div>
                        ${descList}
                        ${optionList}
                        <div class="mb-2">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="modal-product-label fw-bold text-primary">Gifts:</span>
                                <button class="btn btn-sm btn-edit-gifts p-1" data-code="${esc(info.code)}" data-gift-codes='${giftCodesJson}' title="Edit Gifts">
                                    <i class="fa-solid fa-pen text-warning"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success d-none btn-save-gifts" data-code="${esc(info.code)}">
                                    <i class="fa-solid fa-check"></i> Save
                                </button>
                                <button class="btn btn-sm btn-secondary d-none btn-cancel-gifts">
                                    <i class="fa-solid fa-times"></i> Cancel
                                </button>
                            </div>
                            <div id="giftsViewMode">
                                ${giftList}
                            </div>
                            <div id="giftsEditMode" class="d-none">
                                <div class="border rounded p-3 bg-light">
                                    <div class="row" id="editGiftsCheckboxes">
                                        <!-- Gift checkboxes will be rendered from PHP via backend -->
                                    </div>
                                </div>
                            </div>
                            <div id="giftsAlert" class="mt-2"></div>
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

    function setupInlineEditHandlers(productData) {
        const info = productData.productInfo || {};
        
        // Add event listeners for edit buttons
        document.querySelector('.btn-edit-gifts')?.addEventListener('click', function() {
            const code = this.getAttribute('data-code');
            const giftCodes = JSON.parse(this.getAttribute('data-gift-codes') || '[]');
            showGiftsEditForm(code, giftCodes);
        });
        
        // Add event listeners for other edit buttons (if any)
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const field = this.getAttribute('data-field');
                const value = this.getAttribute('data-value');
                const code = this.getAttribute('data-code') || info.code;
                
                if (field === 'productName') {
                    showInlineEditForm(field, 'text', value, code);
                } else if (field === 'productBrand') {
                    showBrandEditForm(field, value, code);
                }
            });
        });
    }

    function showInlineEditForm(field, type, value, code) {
        const displayElement = document.getElementById(`${field}-display`);
        const editButton = document.querySelector(`.edit-btn[data-field="${field}"]`);
        if (!displayElement) return;
        
        // Hide edit button
        if (editButton) editButton.classList.add('d-none');
        
        // Create edit form
        const inputHtml = `<input type="text" class="form-control form-control-sm" id="${field}-input" value="${value}">`;

        // Create form with save and cancel buttons
        const formHtml = `
            <div class="inline-edit-form">
                ${inputHtml}
                <div class="mt-2">
                    <button class="btn btn-sm btn-success save-inline-edit" data-field="${field}" data-code="${code}">
                        <i class="fa-solid fa-check"></i> Save
                    </button>
                    <button class="btn btn-sm btn-secondary cancel-inline-edit" data-field="${field}">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        `;
        
        // Store original content and replace with form
        displayElement.dataset.originalContent = displayElement.innerHTML;
        displayElement.innerHTML = formHtml;
        
        // Focus the input
        const input = document.getElementById(`${field}-input`);
        if (input) input.focus();
        
        // Add event listeners for save and cancel buttons
        document.querySelector(`.save-inline-edit[data-field="${field}"]`)?.addEventListener('click', function() {
            const newValue = input.value;
            saveInlineEdit(field, newValue, code);
        });
        
        document.querySelector(`.cancel-inline-edit[data-field="${field}"]`)?.addEventListener('click', function() {
            cancelInlineEdit(field);
        });
    }

    function saveInlineEdit(field, value, code) {
        const displayElement = document.getElementById(`${field}-display`);
        const alertDiv = document.getElementById('viewProductAlert');
        alertDiv.innerHTML = '';
        
        // Only support productName field editing
        if (field !== 'productName') {
            showToastAndReload('danger', 'Only product name can be edited');
            cancelInlineEdit(field);
            return;
        }
        
        // Show loading state
        displayElement.innerHTML = `<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Updating...</span>`;
        
        // Use PHP API handler to update product name
        const formData = new FormData();
        formData.append('action', 'updateProductName');
        formData.append('productCode', code);
        formData.append('name', value);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || `Failed to update product name`);
            }
            
            // Show success message via toast
            showToastAndReload('success', 'Product name updated successfully!');
        })
        .catch(err => {
            showToastAndReload('danger', err.message || 'Error updating product name');
            cancelInlineEdit(field);
        });
    }

    function cancelInlineEdit(field) {
        const displayElement = document.getElementById(`${field}-display`);
        const editButton = document.querySelector(`.edit-btn[data-field="${field}"]`);
        if (displayElement && displayElement.dataset.originalContent) {
            displayElement.innerHTML = displayElement.dataset.originalContent;
            // Show edit button again
            if (editButton) editButton.classList.remove('d-none');
        }
    }

    function showBrandEditForm(field, currentBrandCode, productCode) {
        const displayElement = document.getElementById(`${field}-display`);
        const editButton = document.querySelector(`.edit-btn[data-field="${field}"]`);
        if (!displayElement) return;
        
        // Hide edit button
        if (editButton) editButton.classList.add('d-none');
        
        // Create brand selection dropdown - we'll create this dynamically from what's available in the DOM
        const brandSelectContainer = document.getElementById('brandSelectOptions');
        let brandOptions = '';
        
        if (brandSelectContainer) {
            // Extract options from hidden container with brands
            brandOptions = brandSelectContainer.innerHTML;
        }
        
        const formHtml = `
            <div class="inline-edit-form">
                <select class="form-select form-select-sm" id="${field}-input">
                    <option value="">-- Select Brand --</option>
                    ${brandOptions}
                </select>
                <div class="mt-2">
                    <button class="btn btn-sm btn-success save-inline-edit" data-field="${field}" data-code="${productCode}">
                        <i class="fa-solid fa-check"></i> Save
                    </button>
                    <button class="btn btn-sm btn-secondary cancel-inline-edit" data-field="${field}">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                </div>
            </div>
        `;
        
        displayElement.dataset.originalContent = displayElement.innerHTML;
        displayElement.innerHTML = formHtml;
        
        const input = document.getElementById(`${field}-input`);
        if (input) {
            // Set current value if possible
            if (currentBrandCode && input.querySelector(`option[value="${currentBrandCode}"]`)) {
                input.value = currentBrandCode;
            }
            input.focus();
        }
        
        document.querySelector(`.save-inline-edit[data-field="${field}"]`)?.addEventListener('click', function() {
            const newValue = input.value;
            saveBrandEdit(field, newValue, productCode);
        });
        
        document.querySelector(`.cancel-inline-edit[data-field="${field}"]`)?.addEventListener('click', function() {
            cancelInlineEdit(field);
        });
    }

    function saveBrandEdit(field, brandCode, productCode) {
        const displayElement = document.getElementById(`${field}-display`);
        const alertDiv = document.getElementById('viewProductAlert');
        alertDiv.innerHTML = '';
        
        if (!brandCode) {
            alertDiv.innerHTML = '<div class="alert alert-danger">Please select a brand</div>';
            return;
        }
        
        displayElement.innerHTML = `<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Updating...</span>`;
        
        // Use PHP API handler to update product brand
        const formData = new FormData();
        formData.append('action', 'updateProductBrand');
        formData.append('productCode', productCode);
        formData.append('brandCode', brandCode);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to update product brand');
            }
            
            showToastAndReload('success', 'Product brand updated successfully!');
        })
        .catch(err => {
            showToastAndReload('danger', err.message || 'Error updating product brand');
            cancelInlineEdit(field);
        });
    }

    function showGiftsEditForm(productCode, currentGiftCodes = []) {
        // Hide edit button
        const editButton = document.querySelector('.btn-edit-gifts');
        if (editButton) editButton.classList.add('d-none');
        
        // Toggle visibility
        document.querySelector('#giftsViewMode').classList.add('d-none');
        document.querySelector('#giftsEditMode').classList.remove('d-none');
        document.querySelector('.btn-save-gifts').classList.remove('d-none');
        document.querySelector('.btn-cancel-gifts').classList.remove('d-none');
        
        // Clear previous alerts
        document.getElementById('giftsAlert').innerHTML = '';
        
        // Check current gift codes
        document.querySelectorAll('.gift-checkbox').forEach(cb => {
            cb.checked = currentGiftCodes.includes(cb.value);
        });

        // Handle cancel
        document.querySelector('.btn-cancel-gifts').onclick = function() {
            document.querySelector('#giftsViewMode').classList.remove('d-none');
            document.querySelector('#giftsEditMode').classList.add('d-none');
            // Show edit button again
            const editButton = document.querySelector('.btn-edit-gifts');
            if (editButton) editButton.classList.remove('d-none');
            document.querySelector('.btn-save-gifts').classList.add('d-none');
            document.querySelector('.btn-cancel-gifts').classList.add('d-none');
            document.getElementById('giftsAlert').innerHTML = '';
        };

        // Handle save
        document.querySelector('.btn-save-gifts').onclick = function() {
            saveProductGifts(productCode);
        };
    }

    function saveProductGifts(productCode) {
        const alertContainer = document.getElementById('giftsAlert');
        const checkedGifts = Array.from(document.querySelectorAll('.gift-checkbox:checked')).map(cb => cb.value);
        const saveBtn = document.querySelector('.btn-save-gifts');
        const cancelBtn = document.querySelector('.btn-cancel-gifts');
        const viewMode = document.querySelector('#giftsViewMode');
        const editMode = document.querySelector('#giftsEditMode');
        
        // Disable buttons during submission
        saveBtn.disabled = true;
        cancelBtn.disabled = true;
        
        // Hide buttons and edit form, show view mode with updating message
        saveBtn.classList.add('d-none');
        cancelBtn.classList.add('d-none');
        editMode.classList.add('d-none');
        viewMode.classList.remove('d-none');
        viewMode.innerHTML = `<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Updating...</span>`;
        
        // Use PHP API handler to update gifts
        const formData = new FormData();
        formData.append('action', 'updateProductGifts');
        formData.append('productCode', productCode);
        formData.append('giftCodes', JSON.stringify(checkedGifts));
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to update product gifts');
            }
            
            // Show success and reload page
            showToastAndReload('success', 'Product gifts updated successfully!');
        })
        .catch(err => {
            // Re-enable buttons and show error
            saveBtn.disabled = false;
            cancelBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fa-solid fa-check"></i> Save Changes';
            alertContainer.innerHTML = `<div class="alert alert-danger">${err.message || 'Error updating product gifts'}</div>`;
        });
    }

    function formatPrice(val) {
        if (typeof val !== 'number') return '0';
        return val.toLocaleString('vi-VN', {maximumFractionDigits: 0});
    }

    function formatDateTime(dt) {
        const d = new Date(dt);
        if (isNaN(d.getTime())) return '';
        const pad = n => n < 10 ? '0' + n : n;
        return `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${pad(d.getFullYear())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    // Show all toasts on page load
    document.addEventListener('DOMContentLoaded', function() {
        var toastElList = [].slice.call(document.querySelectorAll('.toast'));
        toastElList.forEach(function(toastEl) {
            var toast = new bootstrap.Toast(toastEl);
            toast.show();
        });
    });

    // --- Handle dynamic image preview and priority UI for option images ---
    variantsSection.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('variant-option-images-input')) {
            updateOptionImagesPreviewAndPriority(e.target);
        }
    });

    // Remove image from input.files (by recreating FileList)
    function removeImageFromInput(input, removeIdx) {
        const dt = new DataTransfer();
        Array.from(input.files).forEach((file, idx) => {
            if (idx !== removeIdx) dt.items.add(file);
        });
        input.files = dt.files;
        // Update preview and priorities after removal
        updateOptionImagesPreviewAndPriority(input);
    }

    // Helper to update preview and priority UI for option images
    function updateOptionImagesPreviewAndPriority(input) {
        const previewContainer = input.closest('.option-block').querySelector('.variantOptionImagesPreview');
        const priorityContainer = input.closest('.option-block').querySelector('.variantOptionImagesPriority');
        previewContainer.innerHTML = '';
        priorityContainer.innerHTML = '';
        if (input.files && input.files.length > 0) {
            Array.from(input.files).forEach((file, i) => {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    // Show image preview with remove button and priority input
                    const wrapper = document.createElement('div');
                    wrapper.className = 'position-relative d-inline-block';
                    wrapper.style.width = '70px';
                    wrapper.style.height = '90px';
                    wrapper.innerHTML = `
                        <img src="${evt.target.result}" class="border rounded" style="width:64px;height:64px;object-fit:cover;">
                        <input type="number" class="form-control form-control-sm mt-1 text-center" value="${i}" min="0" name="priority_${i}" style="width:64px;" placeholder="Priority">
                        <button type="button" class="btn btn-sm btn-danger btn-remove-image position-absolute top-0 end-0 p-1" title="Remove" style="font-size:0.8em;line-height:1;">&times;</button>
                    `;

                    wrapper.querySelector('.btn-remove-image').onclick = function() {
                        removeImageFromInput(input, i);
                    };
                    previewContainer.appendChild(wrapper);
                };
                reader.readAsDataURL(file);
            });
        }
    }

    // --- Edit product name logic ---
    document.querySelectorAll('.btn-edit-product').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const productCode = this.getAttribute('data-code');
            const productName = this.getAttribute('data-name');
            
            // Populate the edit form
            const codeField = document.getElementById('editProductCode');
            const nameField = document.getElementById('editProductName');
            const alertContainer = document.getElementById('editProductNameAlert');
            
            if (codeField && nameField) {
                codeField.value = productCode;
                nameField.value = productName;
            }
            
            // Clear any previous alerts
            if (alertContainer) {
                alertContainer.innerHTML = '';
            }
            
            // Show the modal
            const editModal = document.getElementById('editProductNameModal');
            if (editModal) {
                const bsModal = new bootstrap.Modal(editModal);
                bsModal.show();
            }
        });
    });

    // Handle form submission for product name edit
    const editProductNameForm = document.getElementById('editProductNameForm');
    if (editProductNameForm) {
        editProductNameForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productCode = document.getElementById('editProductCode')?.value;
            const productName = document.getElementById('editProductName')?.value;
            const alertContainer = document.getElementById('editProductNameAlert');
            
            // Validate
            if (!productCode || !productName?.trim()) {
                if (alertContainer) {
                    alertContainer.innerHTML = '<div class="alert alert-danger">Product code and name are required</div>';
                }
                return;
            }
            
            // Use formdata and PHP API handler instead of direct API call
            const formData = new FormData();
            formData.append('action', 'updateProductName');
            formData.append('productCode', productCode);
            formData.append('name', productName);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(async response => {
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to update product name');
                }
                // Show success message via toast and reload
                showToastAndReload('success', 'Product name updated successfully!');
            })
            .catch(err => {
                alertContainer.innerHTML = `<div class="alert alert-danger">${err.message || 'Error updating product name'}</div>`;
            });
        });
    }
});