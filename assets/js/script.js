jQuery(function($) {
    console.log('Initializing table sort...');
    
    function sortTable(table, column, asc = true) {
        const dirModifier = asc ? 1 : -1;
        const tBody = table.tBodies[0];
        const rows = Array.from(tBody.querySelectorAll('tr'));

        // Sort each row
        const sortedRows = rows.sort((a, b) => {
            const aColText = a.querySelector(`td:nth-child(${column + 1})`).textContent.trim();
            const bColText = b.querySelector(`td:nth-child(${column + 1})`).textContent.trim();

            // Handle numeric values (like prices)
            const aValue = aColText.replace(/[^0-9.-]+/g, '');
            const bValue = bColText.replace(/[^0-9.-]+/g, '');

            if (!isNaN(aValue) && !isNaN(bValue)) {
                return (parseFloat(aValue) - parseFloat(bValue)) * dirModifier;
            }

            return (aColText || '').localeCompare(bColText || '') * dirModifier;
        });

        // Remove all existing rows
        while (tBody.firstChild) {
            tBody.removeChild(tBody.firstChild);
        }

        // Add sorted rows
        tBody.append(...sortedRows);

        // Remember how the column is currently sorted
        table.querySelectorAll('th').forEach(th => th.classList.remove('th-sort-asc', 'th-sort-desc'));
        table.querySelector(`th:nth-child(${column + 1})`).classList.toggle('th-sort-asc', asc);
        table.querySelector(`th:nth-child(${column + 1})`).classList.toggle('th-sort-desc', !asc);
    }

    // Add click event to all table headers
    document.querySelectorAll('.wcvt-variation-table th').forEach((headerCell, columnIndex) => {
        headerCell.addEventListener('click', () => {
            const tableElement = headerCell.closest('table');
            const currentIsAscending = headerCell.classList.contains('th-sort-asc');
            sortTable(tableElement, columnIndex, !currentIsAscending);
        });
    });

    // Show more functionality
    $('.wcvt-show-more-button').on('click', function() {
        var $wrapper = $(this).closest('.wcvt-variation-table-wrapper');
        $wrapper.removeClass('wcvt-preview-enabled');
        $(this).closest('.wcvt-show-more').remove();
    });

    // Add to cart functionality
    $('.wcvt-add-to-cart').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var variationId = $button.data('variation-id');

        $button.addClass('loading');

        var data = {
            action: 'woocommerce_add_to_cart',
            product_id: $button.data('product-id'),
            variation_id: variationId,
            quantity: 1
        };

        $.post(wc_add_to_cart_params.ajax_url, data, function(response) {
            if (!response) {
                return;
            }

            if (response.error && response.product_url) {
                window.location = response.product_url;
                return;
            }

            // Trigger event so themes can refresh other areas
            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);

            $button.removeClass('loading');
        });
    });

    // Function to close modal
    function closeModal() {
        $('.wcvt-modal').removeClass('is-open');
        $('body').css('overflow', '');
    }

    // Modal functionality
    $('.wcvt-open-modal').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const $modal = $('.wcvt-modal');
        $modal.addClass('is-open');
        $('body').css('overflow', 'hidden');
    });

    $('.wcvt-modal-close, .wcvt-modal-overlay').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closeModal();
    });

    // Close modal on escape key
    $(document).on('keyup', function(e) {
        if (e.key === 'Escape' && $('.wcvt-modal').hasClass('is-open')) {
            closeModal();
        }
    });

    // Prevent modal content clicks from closing modal
    $('.wcvt-modal-container').on('click', function(e) {
        e.stopPropagation();
    });

    // Initialize preview on load
    $('.wcvt-preview-enabled').each(function() {
        const $wrapper = $(this);
        const previewRows = parseInt($wrapper.data('preview-rows'), 10);
        const $rows = $wrapper.find('tbody tr');
        
        if ($rows.length > previewRows) {
            // Hide rows beyond preview limit
            $rows.slice(previewRows).addClass('preview-hidden');
            
            // Find the background color
            function findBackgroundColor(element) {
                console.log('Starting background color search for:', element);
                let currentElement = element;
                let depth = 0;
                
                while (currentElement && depth < 10) {
                    const computedStyle = window.getComputedStyle(currentElement);
                    console.log('Checking element:', currentElement.tagName, {
                        backgroundColor: computedStyle.backgroundColor,
                        background: computedStyle.background
                    });

                    // Check for background-color first
                    if (computedStyle.backgroundColor && 
                        computedStyle.backgroundColor !== 'rgba(0, 0, 0, 0)' && 
                        computedStyle.backgroundColor !== 'transparent') {
                        console.log('Found backgroundColor:', computedStyle.backgroundColor);
                        return computedStyle.backgroundColor;
                    }

                    // Then check for background
                    if (computedStyle.background && 
                        computedStyle.background !== 'none' && 
                        !computedStyle.background.includes('transparent') &&
                        !computedStyle.background.includes('rgba(0, 0, 0, 0)')) {
                        console.log('Found background:', computedStyle.background);
                        return computedStyle.background;
                    }

                    currentElement = currentElement.parentElement;
                    depth++;
                }

                // If we get here, try body and html as fallback
                const bodyColor = window.getComputedStyle(document.body).backgroundColor;
                if (bodyColor && bodyColor !== 'rgba(0, 0, 0, 0)' && bodyColor !== 'transparent') {
                    console.log('Using body color:', bodyColor);
                    return bodyColor;
                }

                const htmlColor = window.getComputedStyle(document.documentElement).backgroundColor;
                if (htmlColor && htmlColor !== 'rgba(0, 0, 0, 0)' && htmlColor !== 'transparent') {
                    console.log('Using html color:', htmlColor);
                    return htmlColor;
                }

                console.log('No background color found, using fallback');
                return '#ffffff';
            }

            // Create and append the fade overlay
            const backgroundColor = findBackgroundColor($wrapper[0]);
            console.log('Final background color:', backgroundColor);

            const $fadeOverlay = $('<div class="wcvt-fade-overlay"></div>');
            $wrapper.append($fadeOverlay);
            
            // Set the gradient using the found background color
            const rgbaColor = backgroundColor.includes('rgb') ? 
                backgroundColor.replace(')', ', 0)').replace('rgb', 'rgba') :
                backgroundColor;
            
            const gradientStyle = `linear-gradient(to bottom, ${rgbaColor} 0%, ${backgroundColor} 80%)`;
            console.log('Setting gradient:', gradientStyle);
            
            $fadeOverlay.css('background', gradientStyle);
        } else {
            $wrapper.removeClass('wcvt-preview-enabled');
            $wrapper.find('.wcvt-show-more').remove();
        }
    });
}); 