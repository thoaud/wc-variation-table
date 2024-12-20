jQuery(function($) {
    // Initialize sortable
    $('#wcvt-column-list').sortable({
        handle: '.wcvt-column-handle',
        update: function(event, ui) {
            // Update order after sorting
            updateColumnOrder();
        }
    });

    // Handle column removal
    $('.wcvt-column-list').on('click', '.wcvt-remove-column', function() {
        if (confirm('Are you sure you want to remove this column?')) {
            $(this).closest('.wcvt-column-item').remove();
            updateColumnOrder();
        }
    });

    // Update column order
    function updateColumnOrder() {
        $('.wcvt-column-item').each(function(index) {
            $(this).find('.wcvt-column-order').val(index);
        });
    }

    // Handle settings visibility
    function toggleSettings() {
        var placement = $('#wcvt_table_placement').val();
        
        // Find all settings rows
        var rows = {
            tabText: $('input#wcvt_tab_text').closest('tr'),
            modalTitle: $('input#wcvt_modal_title_postfix').closest('tr'),
            modalTrigger: $('select#wcvt_modal_trigger_type').closest('tr'),
            preview: $('input#wcvt_enable_preview').closest('tr'),
            previewRows: $('input#wcvt_preview_rows').closest('tr'),
            tableTitle: $('input#wcvt_table_title').closest('tr')
        };

        // Hide all settings first
        Object.values(rows).forEach(function($row) {
            $row.hide();
        });

        // Show relevant settings based on placement
        switch (placement) {
            case 'tab':
                rows.tabText.show();
                rows.preview.show();
                rows.previewRows.show();
                rows.tableTitle.show();
                break;
            case 'modal':
                rows.modalTitle.show();
                rows.modalTrigger.show();
                break;
            case 'none':
                // No additional settings needed for manual placement
                break;
            default:
                // For all other placements (above, below, description_start, description_end)
                rows.preview.show();
                rows.previewRows.show();
                rows.tableTitle.show();
                break;
        }
    }

    // Run on page load
    toggleSettings();

    // Run when placement setting changes
    $('#wcvt_table_placement').on('change', toggleSettings);
}); 