const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { useBlockProps } = wp.blockEditor;

registerBlockType('wc-variation-table/table', {
    title: __('Variation Table', 'wc-variation-table'),
    icon: 'grid-view',
    category: ['woocommerce', 'widgets'],
    description: __('Display the WooCommerce variation table for variable products.', 'wc-variation-table'),
    supports: {
        html: false,
        multiple: false
    },
    edit: function() {
        const blockProps = useBlockProps();
        
        return (
            <div { ...blockProps }>
                <div className="components-placeholder">
                    <div className="components-placeholder__label">
                        { __('Variation Table', 'wc-variation-table') }
                    </div>
                    <div className="components-placeholder__instructions">
                        { __('This block will display the variation table for variable products.', 'wc-variation-table') }
                    </div>
                </div>
            </div>
        );
    },
    save: function() {
        return null; // Dynamic block, render_callback will handle the frontend
    }
}); 