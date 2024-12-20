<?php
/**
 * WooCommerce Variation Table Settings
 *
 * Handles the plugin's settings page, including column management and custom fields.
 * This class is responsible for rendering the settings interface and handling
 * the saving and retrieval of plugin settings.
 *
 * @package WC_Variation_Table
 * @subpackage Settings
 */

namespace WC_Variation_Table;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings Class
 *
 * Manages all plugin settings, including:
 * - Column management (ordering, visibility, custom columns)
 * - Custom fields configuration
 * - Display settings
 * - Table placement options
 *
 * @since 1.0.0
 */
class Settings {
    /**
     * Initialize the settings class
     *
     * Sets up all necessary hooks and filters for the settings page
     * functionality, including asset enqueueing and field rendering.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('woocommerce_admin_field_column_manager', array($this, 'output_column_manager'));
        add_action('woocommerce_admin_field_custom_fields_manager', array($this, 'output_custom_fields_manager'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Add filters to handle serialization
        add_filter('pre_update_option_wcvt_columns', array($this, 'maybe_serialize_columns'), 10, 2);
        add_filter('option_wcvt_columns', array($this, 'maybe_unserialize_columns'));

        // Add this to your settings class
        add_action( 'admin_init', array( $this, 'add_uninstall_notice' ) );
    }

    /**
     * Serialize column data before saving to database
     *
     * Ensures column data is properly structured and serialized
     * before being saved as a WordPress option.
     *
     * @since 1.0.0
     * @param mixed $value     The value to be saved
     * @param mixed $old_value The current value in the database
     * @return mixed The processed value to be saved
     */
    public function maybe_serialize_columns($value, $old_value) {
        if (is_array($value)) {
            // Ensure all columns have the correct structure
            foreach ($value as $id => &$column) {
                if (!is_array($column)) {
                    continue;
                }

                $column = array_merge([
                    'id' => $id,
                    'enabled' => false,
                    'type' => '',
                    'title' => '',
                    'system' => false
                ], $column);
            }
            return maybe_serialize($value);
        }
        return $value;
    }

    /**
     * Maybe unserialize columns when getting
     */
    public function maybe_unserialize_columns($value) {
        if (is_serialized($value)) {
            $value = maybe_unserialize($value);
        }
        return is_array($value) ? $value : array();
    }

    /**
     * Output the column manager field
     */
    public function output_column_manager($value) {
        $columns = $this->maybe_unserialize_columns(get_option('wcvt_columns', array()));
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label>
            </th>
            <td class="forminp">
                <div class="wcvt-column-manager-wrapper">
                    <p class="description"><?php echo esc_html($value['desc']); ?></p>
                    <div class="wcvt-column-list" id="wcvt-column-list">
                        <?php foreach ($columns as $id => $column) : ?>
                            <div class="wcvt-column-item" data-id="<?php echo esc_attr($id); ?>">
                                <div class="wcvt-column-handle">☰</div>
                                <div class="wcvt-column-content">
                                    <label class="wcvt-column-title">
                                        <?php echo esc_html($column['title']); ?>
                                    </label>
                                    <label class="wcvt-column-toggle">
                                        <input type="checkbox" 
                                            name="wcvt_columns[<?php echo esc_attr($id); ?>][enabled]" 
                                            value="1" 
                                            <?php checked(!empty($column['enabled'])); ?>
                                        >
                                        <span class="slider"></span>
                                    </label>
                                    <?php if (!empty($column['system'])) : ?>
                                        <button type="button" class="wcvt-remove-column" data-id="<?php echo esc_attr($id); ?>">
                                            &times;
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" 
                                    name="wcvt_columns[<?php echo esc_attr($id); ?>][id]" 
                                    value="<?php echo esc_attr($id); ?>"
                                >
                                <input type="hidden" 
                                    name="wcvt_columns[<?php echo esc_attr($id); ?>][type]" 
                                    value="<?php echo esc_attr($column['type']); ?>"
                                >
                                <input type="hidden" 
                                    name="wcvt_columns[<?php echo esc_attr($id); ?>][title]" 
                                    value="<?php echo esc_attr($column['title']); ?>"
                                >
                                <?php if (!empty($column['system'])) : ?>
                                    <input type="hidden" 
                                        name="wcvt_columns[<?php echo esc_attr($id); ?>][system]" 
                                        value="1"
                                    >
                                <?php endif; ?>
                                <?php if (isset($column['size'])) : ?>
                                    <input type="hidden" 
                                        name="wcvt_columns[<?php echo esc_attr($id); ?>][size]" 
                                        value="<?php echo esc_attr($column['size']); ?>"
                                    >
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ('woocommerce_page_wc-settings' !== $hook) {
            return;
        }

        if (!isset($_GET['tab']) || $_GET['tab'] !== 'products' || !isset($_GET['section']) || $_GET['section'] !== 'variation_table') {
            return;
        }

        wp_enqueue_style(
            'wcvt-admin-columns',
            plugins_url('assets/css/admin-columns.css', dirname(__FILE__)),
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'wcvt-admin-columns',
            plugins_url('assets/js/admin-columns.js', dirname(__FILE__)),
            array('jquery', 'jquery-ui-sortable'),
            '1.0.0',
            true
        );
    }

    /**
     * Sanitize the columns data
     */
    public function sanitize_columns($value, $option, $raw_value) {
        if (!is_array($value)) {
            return array();
        }

        // Clean up and ensure required fields
        $columns = array();
        foreach ($value as $id => $column) {
            if (!is_array($column)) {
                continue;
            }

            $columns[$id] = array(
                'id' => sanitize_key($id),
                'enabled' => !empty($column['enabled']),
                'type' => sanitize_key($column['type']),
                'title' => sanitize_text_field($column['title']),
                'system' => !empty($column['system']),
                'locked' => !empty($column['locked'])
            );

            if (isset($column['size'])) {
                $columns[$id]['size'] = sanitize_key($column['size']);
            }
        }

        return $columns;
    }

    /**
     * Output the custom fields manager
     */
    public function output_custom_fields_manager($value) {
        $custom_fields = get_option('wcvt_custom_fields', array());
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($value['id']); ?>"><?php echo esc_html($value['title']); ?></label>
            </th>
            <td class="forminp">
                <div class="wcvt-custom-fields-wrapper">
                    <p class="description"><?php echo esc_html($value['desc']); ?></p>
                    <div class="wcvt-custom-fields-list">
                        <?php foreach ($custom_fields as $id => $field) : ?>
                            <div class="wcvt-custom-field-item" data-id="<?php echo esc_attr($id); ?>">
                                <div class="wcvt-custom-field-content">
                                    <input type="text" 
                                        name="wcvt_custom_fields[<?php echo esc_attr($id); ?>][title]" 
                                        value="<?php echo esc_attr($field['title']); ?>"
                                        placeholder="<?php esc_attr_e('Field Title', 'wc-variation-table'); ?>"
                                    >
                                    <input type="text" 
                                        name="wcvt_custom_fields[<?php echo esc_attr($id); ?>][meta_key]" 
                                        value="<?php echo esc_attr($field['meta_key']); ?>"
                                        placeholder="<?php esc_attr_e('Meta Key', 'wc-variation-table'); ?>"
                                    >
                                    <button type="button" class="wcvt-remove-field">
                                        <?php esc_html_e('Remove', 'wc-variation-table'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button wcvt-add-custom-field">
                        <?php esc_html_e('Add Custom Field', 'wc-variation-table'); ?>
                    </button>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Sanitize the custom fields data
     */
    public function sanitize_custom_fields($value, $option, $raw_value) {
        if (!is_array($value)) {
            return array();
        }

        $fields = array();
        foreach ($value as $id => $field) {
            if (empty($field['title']) || empty($field['meta_key'])) {
                continue;
            }

            $fields[$id] = array(
                'title' => sanitize_text_field($field['title']),
                'meta_key' => sanitize_key($field['meta_key'])
            );
        }

        return $fields;
    }

    /**
     * Add uninstall notice to settings page
     *
     * @return void
     */
    public function add_uninstall_notice() {
        add_settings_field(
            'wc_variation_table_uninstall_notice',
            __( 'Plugin Deletion', 'wc-variation-table' ),
            array( $this, 'render_uninstall_notice' ),
            'wc_variation_table_settings',
            'wc_variation_table_general_section'
        );
    }

    /**
     * Render uninstall notice
     *
     * @return void
     */
    public function render_uninstall_notice() {
        echo '<p class="description">';
        esc_html_e( 'When this plugin is deleted through the WordPress Plugins page, all its data will be permanently removed from your database.', 'wc-variation-table' );
        echo '</p>';
    }
} 