jQuery(function($) {
    // Initialize sortable for columns
    $('#wcvt-column-list').sortable({
        handle: '.wcvt-column-handle',
        placeholder: 'wcvt-column-item ui-sortable-placeholder',
        axis: 'y',
        update: function(event, ui) {
            // Update order values
            updateColumnOrder();
        }
    });

    // Show/hide image size setting based on image column status
    function toggleImageSizeField() {
        var $imageColumn = $('.wcvt-column-item[data-id="image"]');
        var $imageSizeField = $('.wcvt-image-size-setting').closest('tr');
        
        if ($imageColumn.find('input[type="checkbox"]').is(':checked')) {
            $imageSizeField.show();
        } else {
            $imageSizeField.hide();
        }
    }

    // Initial check for image size field
    toggleImageSizeField();

    // Watch for changes to image column
    $('.wcvt-column-item[data-id="image"] input[type="checkbox"]').on('change', toggleImageSizeField);

    // Handle removing columns
    $('.wcvt-remove-column').on('click', function() {
        var $item = $(this).closest('.wcvt-column-item');
        $item.slideUp(200, function() {
            $item.remove();
            updateAddColumnDropdown();
        });
    });

    // Add dropdown to add system columns
    var $addColumnWrapper = $('<div class="wcvt-add-column-wrapper" style="margin-top: 10px;"></div>');
    var $addColumnSelect = $('<select class="wcvt-add-column-select"></select>');
    var $addColumnButton = $('<button type="button" class="button wcvt-add-column">Add Column</button>');
    
    $addColumnWrapper.append($addColumnSelect).append($addColumnButton);
    $('.wcvt-column-list').after($addColumnWrapper);

    // Function to update the dropdown options
    function updateAddColumnDropdown() {
        var systemColumns = {
            'attributes': { title: 'Variation Attributes', type: 'attributes' },
            'price': { title: 'Price', type: 'price' },
            'stock_qty': { title: 'Stock Quantity', type: 'stock_qty' },
            'stock_status': { title: 'Stock Status', type: 'stock_status' },
            'add_to_cart': { title: 'Add to Cart', type: 'add_to_cart' },
            'sku': { title: 'SKU', type: 'sku' },
            'dimensions': { title: 'Dimensions', type: 'dimensions' },
            'weight': { title: 'Weight', type: 'weight' },
            'description': { title: 'Description', type: 'description' },
            'image': { title: 'Image', type: 'image', size: 'thumbnail' }
        };

        $addColumnSelect.empty();
        $addColumnSelect.append('<option value="">Select column to add...</option>');

        // Add options for system columns that aren't currently in the list
        Object.keys(systemColumns).forEach(function(id) {
            if (!$('.wcvt-column-item[data-id="' + id + '"]').length) {
                $addColumnSelect.append(
                    '<option value="' + id + '">' + systemColumns[id].title + '</option>'
                );
            }
        });

        // Show/hide the wrapper based on whether there are options
        if ($addColumnSelect.find('option').length > 1) {
            $addColumnWrapper.show();
        } else {
            $addColumnWrapper.hide();
        }
    }

    // Handle adding system columns
    $addColumnButton.on('click', function() {
        var id = $addColumnSelect.val();
        if (!id) return;

        var systemColumns = {
            'attributes': {
                title: 'Variation Attributes',
                type: 'attributes'
            },
            'price': {
                title: 'Price',
                type: 'price'
            },
            'stock_qty': {
                title: 'Stock Quantity',
                type: 'stock_qty'
            },
            'stock_status': {
                title: 'Stock Status',
                type: 'stock_status'
            },
            'add_to_cart': {
                title: 'Add to Cart',
                type: 'add_to_cart'
            },
            'sku': {
                title: 'SKU',
                type: 'sku'
            },
            'dimensions': {
                title: 'Dimensions',
                type: 'dimensions'
            },
            'weight': {
                title: 'Weight',
                type: 'weight'
            },
            'description': {
                title: 'Description',
                type: 'description'
            },
            'image': {
                title: 'Image',
                type: 'image',
                size: 'thumbnail'
            }
        };

        var column = systemColumns[id];
        if (!column) return;

        var template = `
            <div class="wcvt-column-item" data-id="${id}">
                <div class="wcvt-column-handle">☰</div>
                <div class="wcvt-column-content">
                    <label class="wcvt-column-title">
                        ${column.title}
                    </label>
                    <label class="wcvt-column-toggle">
                        <input type="checkbox" 
                            name="wcvt_columns[${id}][enabled]" 
                            value="1"
                        >
                        <span class="slider"></span>
                    </label>
                    <button type="button" class="wcvt-remove-column" data-id="${id}">
                        &times;
                    </button>
                </div>
                <input type="hidden" 
                    name="wcvt_columns[${id}][id]" 
                    value="${id}"
                >
                <input type="hidden" 
                    name="wcvt_columns[${id}][type]" 
                    value="${column.type}"
                >
                <input type="hidden" 
                    name="wcvt_columns[${id}][title]" 
                    value="${column.title}"
                >
                <input type="hidden" 
                    name="wcvt_columns[${id}][system]" 
                    value="1"
                >
                ${column.size ? `
                    <input type="hidden" 
                        name="wcvt_columns[${id}][size]" 
                        value="${column.size}"
                    >
                ` : ''}
            </div>
        `;

        var $newColumn = $(template);
        $newColumn.hide();
        $('.wcvt-column-list').append($newColumn);
        $newColumn.slideDown(200);

        // Update dropdown
        updateAddColumnDropdown();

        // Reset select
        $addColumnSelect.val('');

        // Bind remove event to new column
        $newColumn.find('.wcvt-remove-column').on('click', function() {
            var $item = $(this).closest('.wcvt-column-item');
            $item.slideUp(200, function() {
                $item.remove();
                updateAddColumnDropdown();
            });
        });

        // If it's the image column, check for visibility
        if (id === 'image') {
            toggleImageSizeField();
        }
    });

    // Initial dropdown update
    updateAddColumnDropdown();

    // Update column order
    function updateColumnOrder() {
        $('.wcvt-column-item').each(function(index) {
            $(this).find('.wcvt-column-order').val(index);
        });
    }

    // Initialize column order
    updateColumnOrder();

    // Handle adding custom fields
    $('.wcvt-add-custom-field').on('click', function() {
        var id = 'custom_' + Date.now();
        var template = `
            <div class="wcvt-custom-field-item" data-id="${id}">
                <div class="wcvt-custom-field-content">
                    <input type="text" 
                        name="wcvt_custom_fields[${id}][title]" 
                        placeholder="Field Title"
                    >
                    <input type="text" 
                        name="wcvt_custom_fields[${id}][meta_key]" 
                        placeholder="Meta Key"
                    >
                    <button type="button" class="wcvt-remove-field">
                        Remove
                    </button>
                </div>
            </div>
        `;

        var $newField = $(template);
        $newField.hide();
        $('.wcvt-custom-fields-list').append($newField);
        $newField.slideDown(200);
    });

    // Handle removing custom fields
    $(document).on('click', '.wcvt-remove-field', function() {
        var $item = $(this).closest('.wcvt-custom-field-item');
        $item.slideUp(200, function() {
            $item.remove();
        });
    });
}); 