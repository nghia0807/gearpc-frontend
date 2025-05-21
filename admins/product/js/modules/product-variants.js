// Module for managing product variants in the add product form
export function initVariantsSection() {
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

    // Check if variant section exists in the DOM
    if (!variantsSection || !btnAddVariant) return;

    // Setup add variant button
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

    // Initialize the variants section with one variant
    btnAddVariant.click();
}

// Method to gather variants data from form for submission
export function gatherVariantsData(variantsSection) {
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
            const imagesBase64 = []; // This will be filled by the caller with fileToBase64 async calls
            
            options.push({
                optionLabel,
                originalPrice,
                currentPrice,
                descriptions,
                imagesBase64,
                shortDescription,
                imagesInput // Pass the file input element for processing by caller
            });
        }
        
        variants.push({
            optionTitle,
            options
        });
    }
    
    return variants;
}