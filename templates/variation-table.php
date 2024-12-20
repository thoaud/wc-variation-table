<?php
/**
 * Template for displaying the variation table
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get columns from settings and unserialize if needed
$saved_columns = get_option('wcvt_columns');
if (is_serialized($saved_columns)) {
    $saved_columns = maybe_unserialize($saved_columns);
}

// Default columns structure
$default_columns = array(
    'attributes' => array(
        'id' => 'attributes',
        'enabled' => true,
        'title' => __('Variation Attributes', 'wc-variation-table'),
        'type' => 'attributes',
        'system' => true
    ),
    'price' => array(
        'id' => 'price',
        'enabled' => true,
        'title' => __('Price', 'wc-variation-table'),
        'type' => 'price',
        'system' => true
    ),
    'stock_status' => array(
        'id' => 'stock_status',
        'enabled' => true,
        'title' => __('Stock Status', 'wc-variation-table'),
        'type' => 'stock_status',
        'system' => true
    ),
    'add_to_cart' => array(
        'id' => 'add_to_cart',
        'enabled' => true,
        'title' => __('Add to Cart', 'wc-variation-table'),
        'type' => 'add_to_cart',
        'system' => true
    )
);

// Use saved columns if they exist and are valid, otherwise use defaults
$columns = (!empty($saved_columns) && is_array($saved_columns)) ? $saved_columns : $default_columns;

// Debug
error_log('WC Variation Table: Raw Saved Columns: ' . print_r($saved_columns, true));
error_log('WC Variation Table: Using Columns: ' . print_r($columns, true));

// Verify we have variations and attributes
if (empty($variations) || !is_array($variations) || empty($attributes) || !is_array($attributes)) {
    return;
}

// Check if we have any enabled columns
$has_enabled_columns = false;
foreach ($columns as $column) {
    if (!empty($column['enabled'])) {
        $has_enabled_columns = true;
        break;
    }
}

// If no enabled columns, show a message
if (!$has_enabled_columns) {
    echo '<p>' . __('No columns are enabled for the variation table. Please enable some columns in the settings.', 'wc-variation-table') . '</p>';
    return;
}

// Get settings
$separate_attributes = get_option('wcvt_separate_attribute_columns') === 'yes';
$show_preview = get_option('wcvt_enable_preview') === 'yes';
$preview_rows = absint(get_option('wcvt_preview_rows', 5));
$table_title = get_option('wcvt_table_title');
$placement = get_option('wcvt_table_placement', 'tab');
?>

<div class="wcvt-variation-table-wrapper<?php echo $show_preview ? ' wcvt-preview-enabled' : ''; ?>"<?php 
    if ($show_preview) {
        echo ' data-preview-rows="' . esc_attr($preview_rows) . '"';
    }
?>>
    <?php if ($table_title && $placement !== 'modal') : ?>
        <h2 class="wcvt-table-title"><?php echo esc_html($table_title); ?></h2>
    <?php endif; ?>
    
    <table class="wcvt-variation-table js-sort-table">
        <thead>
            <tr>
                <?php foreach ($columns as $column_id => $column) : ?>
                    <?php if (!empty($column['enabled'])) : ?>
                        <?php if ($column['type'] === 'attributes' && $separate_attributes) : ?>
                            <?php foreach ($attributes as $attribute_name => $options) : ?>
                                <th class="wcvt-attribute" data-sort="string">
                                    <?php echo esc_html(wc_attribute_label($attribute_name)); ?>
                                </th>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <th class="wcvt-<?php echo esc_attr($column['type']); ?>" data-sort="<?php echo esc_attr($column['type']); ?>">
                                <?php echo esc_html($column['title']); ?>
                            </th>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($variations as $variation) : ?>
                <?php 
                if (get_option('wcvt_hide_unpurchasable') === 'yes' && !$variation['is_purchasable']) {
                    continue;
                }
                ?>
                <tr>
                    <?php foreach ($columns as $column_id => $column) : ?>
                        <?php if (!empty($column['enabled'])) : ?>
                            <?php if ($column['type'] === 'attributes' && $separate_attributes) : ?>
                                <?php foreach ($attributes as $attribute_name => $options) : ?>
                                    <td class="wcvt-attribute">
                                        <?php
                                        $meta_key = 'attribute_' . sanitize_title($attribute_name);
                                        $attribute_value = isset($variation['attributes'][$meta_key]) ? $variation['attributes'][$meta_key] : '';
                                        
                                        if ($attribute_value) {
                                            if (taxonomy_exists($attribute_name)) {
                                                $term = get_term_by('slug', $attribute_value, $attribute_name);
                                                $value = $term ? $term->name : $attribute_value;
                                            } else {
                                                $value = $attribute_value;
                                            }
                                            echo esc_html($value);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <td class="wcvt-<?php echo esc_attr($column['type']); ?>">
                                    <?php
                                    switch ($column['type']) {
                                        case 'attributes':
                                            $attribute_parts = array();
                                            foreach ($attributes as $attribute_name => $options) {
                                                $meta_key = 'attribute_' . sanitize_title($attribute_name);
                                                $attribute_value = isset($variation['attributes'][$meta_key]) ? $variation['attributes'][$meta_key] : '';
                                                
                                                if ($attribute_value) {
                                                    if (taxonomy_exists($attribute_name)) {
                                                        $term = get_term_by('slug', $attribute_value, $attribute_name);
                                                        $value = $term ? $term->name : $attribute_value;
                                                    } else {
                                                        $value = $attribute_value;
                                                    }
                                                    $label = wc_attribute_label($attribute_name);
                                                    $attribute_parts[] = $label . ': ' . $value;
                                                }
                                            }
                                            echo esc_html(implode(', ', $attribute_parts));
                                            break;

                                        case 'price':
                                            if ($variation['display_price'] !== $variation['display_regular_price']) {
                                                echo '<del>' . wc_price($variation['display_regular_price']) . '</del> ';
                                                echo '<ins>' . wc_price($variation['display_price']) . '</ins>';
                                            } else {
                                                echo wc_price($variation['display_price']);
                                            }
                                            break;

                                        case 'stock_qty':
                                            if ($variation['is_in_stock']) {
                                                echo esc_html($variation['max_qty']);
                                            } else {
                                                echo '0';
                                            }
                                            break;

                                        case 'stock_status':
                                            if ($variation['is_in_stock']) {
                                                echo '<span class="wcvt-in-stock">' . esc_html__('In stock', 'woocommerce') . '</span>';
                                            } else {
                                                echo '<span class="wcvt-out-of-stock">' . esc_html__('Out of stock', 'woocommerce') . '</span>';
                                            }
                                            break;

                                        case 'add_to_cart':
                                            if ($variation['is_purchasable'] && $variation['is_in_stock']) {
                                                printf(
                                                    '<button type="button" class="button wcvt-add-to-cart" data-variation-id="%d">%s</button>',
                                                    esc_attr($variation['variation_id']),
                                                    esc_html__('Add to cart', 'woocommerce')
                                                );
                                            }
                                            break;

                                        case 'sku':
                                            echo esc_html($variation['sku']);
                                            break;

                                        case 'dimensions':
                                            if ($variation['dimensions_html']) {
                                                echo wp_kses_post($variation['dimensions_html']);
                                            }
                                            break;

                                        case 'weight':
                                            if ($variation['weight_html']) {
                                                echo wp_kses_post($variation['weight_html']);
                                            }
                                            break;

                                        case 'description':
                                            echo wp_kses_post($variation['variation_description']);
                                            break;

                                        case 'image':
                                            $size = !empty($column['size']) ? $column['size'] : 'thumbnail';
                                            if ($variation['image']['src']) {
                                                printf(
                                                    '<img src="%s" alt="%s" class="wcvt-variation-image" width="%d" height="%d">',
                                                    esc_url($variation['image']['src']),
                                                    esc_attr($variation['image']['alt']),
                                                    esc_attr($variation['image']['thumb_src_w']),
                                                    esc_attr($variation['image']['thumb_src_h'])
                                                );
                                            }
                                            break;

                                        case 'custom_meta':
                                            if (!empty($column['meta_key'])) {
                                                $meta_value = get_post_meta($variation['variation_id'], $column['meta_key'], true);
                                                echo esc_html($meta_value);
                                            }
                                            break;
                                    }
                                    ?>
                                </td>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($show_preview) : ?>
        <div class="wcvt-show-more">
            <button type="button" class="button wcvt-show-more-button">
                <?php esc_html_e('Show all variations', 'wc-variation-table'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>