<?php
/**
 * Plugin Name: WooCommerce Variation Table
 * Plugin URI: https://audunhus.com
 * Description: Adds a customizable table view of product variations for WooCommerce variable products, with sorting and filtering capabilities
 * Version: 1.0.0
 * Author: Thomas Audunhus
 * Author URI: https://audunhus.com
 * Text Domain: wc-variation-table
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/class-updater.php';
require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-settings.php';

class WC_Variation_Table {
    /**
     * Plugin version
     *
     * @var string
     */
    private $version = '1.0.0';

    /**
     * Constructor for the plugin
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));

        // Initialize the updater
        if (is_admin()) {
            new WC_Variation_Table\Updater();
            new WC_Variation_Table\Settings();
        }
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Only proceed if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Add settings
        add_filter('woocommerce_get_settings_products', array($this, 'add_settings'), 10, 2);
        add_filter('woocommerce_get_sections_products', array($this, 'add_section'));
        add_action('woocommerce_init', array($this, 'register_settings'));

        // Add frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Register shortcode
        add_shortcode('variation_table', array($this, 'shortcode_output'));

        // Register Gutenberg block
        add_action('init', array($this, 'register_block'));

        // Add variation table based on placement setting
        $placement = get_option('wcvt_table_placement', 'tab');
        
        switch ($placement) {
            case 'tab':
                add_filter('woocommerce_product_tabs', array($this, 'add_variation_table_tab'));
                break;
            case 'below':
                add_action('woocommerce_after_single_product_summary', array($this, 'display_variation_table'), 15);
                break;
            case 'above':
                add_action('woocommerce_before_single_product_summary', array($this, 'display_variation_table'), 30);
                break;
            case 'modal':
                add_action('woocommerce_before_add_to_cart_form', array($this, 'add_modal_button'));
                add_action('wp_footer', array($this, 'add_modal_markup'));
                break;
            case 'description_start':
                add_filter('the_content', array($this, 'add_to_description_start'), 10);
                break;
            case 'description_end':
                add_filter('the_content', array($this, 'add_to_description_end'), 10);
                break;
        }
    }

    /**
     * Display admin notice if WooCommerce is not active
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('WooCommerce Variation Table requires WooCommerce to be installed and active.', 'wc-variation-table'); ?></p>
        </div>
        <?php
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // String options
        add_option('wcvt_show_stock_status', 'yes');
        add_option('wcvt_enable_table', 'yes');
        add_option('wcvt_hide_unpurchasable', 'no');
        add_option('wcvt_table_placement', 'tab');
        add_option('wcvt_tab_text', __('Product options table', 'wc-variation-table'));
        add_option('wcvt_modal_title_postfix', __('- Available Options', 'wc-variation-table'));
        add_option('wcvt_modal_trigger_type', 'button');
        add_option('wcvt_enable_preview', 'yes');
        add_option('wcvt_preview_rows', '5');
        add_option('wcvt_table_title', '');
        
        // Default column settings
        if (!get_option('wcvt_columns')) {
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
                'stock_qty' => array(
                    'id' => 'stock_qty',
                    'enabled' => true,
                    'title' => __('Stock Quantity', 'wc-variation-table'),
                    'type' => 'stock_qty',
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
                ),
                'sku' => array(
                    'id' => 'sku',
                    'enabled' => false,
                    'title' => __('SKU', 'wc-variation-table'),
                    'type' => 'sku',
                    'system' => true
                ),
                'dimensions' => array(
                    'id' => 'dimensions',
                    'enabled' => false,
                    'title' => __('Dimensions', 'wc-variation-table'),
                    'type' => 'dimensions',
                    'system' => true
                ),
                'weight' => array(
                    'id' => 'weight',
                    'enabled' => false,
                    'title' => __('Weight', 'wc-variation-table'),
                    'type' => 'weight',
                    'system' => true
                ),
                'description' => array(
                    'id' => 'description',
                    'enabled' => false,
                    'title' => __('Description', 'wc-variation-table'),
                    'type' => 'description',
                    'system' => true
                ),
                'image' => array(
                    'id' => 'image',
                    'enabled' => false,
                    'title' => __('Image', 'wc-variation-table'),
                    'type' => 'image',
                    'size' => 'thumbnail',
                    'system' => true
                )
            );
            add_option('wcvt_columns', $default_columns);
        }

        // Initialize custom fields as empty array if not set
        if (!get_option('wcvt_custom_fields')) {
            add_option('wcvt_custom_fields', array());
        }
    }

    /**
     * Add our section to the Products tab
     */
    public function add_section($sections) {
        $sections['variation_table'] = __('Variation Table', 'wc-variation-table');
        return $sections;
    }

    /**
     * Add settings to WooCommerce products tab
     */
    public function add_settings($settings, $current_section) {
        // Return other settings if not our section
        if ($current_section !== 'variation_table') {
            return $settings;
        }

        $settings = [];
        
        $settings[] = array(
            'title' => __('Variation Table Settings', 'wc-variation-table'),
            'type'  => 'title',
            'id'    => 'wcvt_settings'
        );

        // General settings that always show
        $settings[] = array(
            'title'   => __('Enable Variation Table', 'wc-variation-table'),
            'desc'    => __('Show variation table on variable product pages', 'wc-variation-table'),
            'id'      => 'wcvt_enable_table',
            'default' => 'yes',
            'type'    => 'checkbox'
        );

        $settings[] = array(
            'title'   => __('Hide Unpurchasable Variations', 'wc-variation-table'),
            'desc'    => __('Hide variations that cannot be purchased (out of stock or unavailable)', 'wc-variation-table'),
            'id'      => 'wcvt_hide_unpurchasable',
            'default' => 'no',
            'type'    => 'checkbox'
        );

        $settings[] = array(
            'title'   => __('Separate Attribute Columns', 'wc-variation-table'),
            'desc'    => __('Display each variation attribute in its own column instead of combining them', 'wc-variation-table'),
            'id'      => 'wcvt_separate_attribute_columns',
            'default' => 'no',
            'type'    => 'checkbox'
        );

        // Column Manager
        $settings[] = array(
            'title'    => __('Table Columns', 'wc-variation-table'),
            'desc'     => __('Manage and customize table columns', 'wc-variation-table'),
            'id'       => 'wcvt_columns',
            'type'     => 'column_manager',
            'class'    => 'wcvt-column-manager'
        );

        // Custom Fields Manager
        $settings[] = array(
            'title'    => __('Custom Fields', 'wc-variation-table'),
            'desc'     => __('Add and manage custom fields for the variation table', 'wc-variation-table'),
            'id'       => 'wcvt_custom_fields',
            'type'     => 'custom_fields_manager',
            'class'    => 'wcvt-custom-fields-manager'
        );

        // Image size setting
        $settings[] = array(
            'title'    => __('Image Size', 'wc-variation-table'),
            'desc'     => __('Select the image size to use in the table', 'wc-variation-table'),
            'id'       => 'wcvt_image_size',
            'type'     => 'select',
            'class'    => 'wcvt-image-size-setting',
            'options'  => $this->get_available_image_sizes(),
            'default'  => 'thumbnail'
        );

        // Placement setting
        $settings[] = array(
            'title'    => __('Table Placement', 'wc-variation-table'),
            'desc'     => __('Choose where to display the variation table', 'wc-variation-table'),
            'id'       => 'wcvt_table_placement',
            'default'  => 'tab',
            'type'     => 'select',
            'options'  => array(
                'tab'               => __('In a product tab', 'wc-variation-table'),
                'below'            => __('Below product description', 'wc-variation-table'),
                'above'            => __('Above product description', 'wc-variation-table'),
                'modal'            => __('In a modal popup', 'wc-variation-table'),
                'description_start' => __('At the start of product description', 'wc-variation-table'),
                'description_end'  => __('At the end of product description', 'wc-variation-table'),
                'none'             => __('No automatic placement', 'wc-variation-table'),
            )
        );

        // Tab-specific settings
        $settings[] = array(
            'title'    => __('Tab Text', 'wc-variation-table'),
            'desc'     => __('The text to display for the variations tab', 'wc-variation-table'),
            'id'       => 'wcvt_tab_text',
            'default'  => __('Product options table', 'wc-variation-table'),
            'type'     => 'text',
            'class'    => 'wcvt-tab-text-setting'
        );

        // Modal-specific settings
        $settings[] = array(
            'title'    => __('Modal Title Postfix', 'wc-variation-table'),
            'desc'     => __('Text to append after product name in modal title', 'wc-variation-table'),
            'id'       => 'wcvt_modal_title_postfix',
            'default'  => __('- Available Options', 'wc-variation-table'),
            'type'     => 'text',
            'class'    => 'wcvt-modal-title-setting'
        );

        $settings[] = array(
            'title'    => __('Modal Trigger Type', 'wc-variation-table'),
            'desc'     => __('Choose how to display the modal trigger', 'wc-variation-table'),
            'id'       => 'wcvt_modal_trigger_type',
            'default'  => 'button',
            'type'     => 'select',
            'class'    => 'wcvt-modal-trigger-setting',
            'options'  => array(
                'button'    => __('Button', 'wc-variation-table'),
                'link'      => __('Text Link', 'wc-variation-table'),
            )
        );

        // Non-modal settings
        $settings[] = array(
            'title'    => __('Table Title', 'wc-variation-table'),
            'desc'     => __('Title to display above the table', 'wc-variation-table'),
            'id'       => 'wcvt_table_title',
            'default'  => '',
            'type'     => 'text',
            'class'    => 'wcvt-table-title-setting'
        );

        $settings[] = array(
            'title'    => __('Enable Table Preview', 'wc-variation-table'),
            'desc'     => __('Show a preview of the table with a "Show more" button', 'wc-variation-table'),
            'id'       => 'wcvt_enable_preview',
            'default'  => 'yes',
            'type'    => 'checkbox',
            'class'    => 'wcvt-preview-setting'
        );

        $settings[] = array(
            'title'    => __('Preview Rows', 'wc-variation-table'),
            'desc'     => __('Number of rows to show in preview', 'wc-variation-table'),
            'id'       => 'wcvt_preview_rows',
            'default'  => '5',
            'type'     => 'number',
            'custom_attributes' => array(
                'min'  => '1',
                'step' => '1'
            ),
            'class'    => 'wcvt-preview-rows-setting'
        );

        $settings[] = array(
            'type' => 'sectionend',
            'id'   => 'wcvt_settings'
        );

        return $settings;
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on WooCommerce settings pages
        if ('woocommerce_page_wc-settings' !== $hook) {
            return;
        }

        // Only load on the Products tab
        if (!isset($_GET['tab']) || $_GET['tab'] !== 'products') {
            return;
        }

        wp_enqueue_script(
            'wc-variation-table-admin',
            plugins_url('assets/js/admin.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (is_product() && get_option('wcvt_enable_table') === 'yes') {
            wp_enqueue_style(
                'wc-variation-table',
                plugins_url('assets/css/style.css', __FILE__),
                array(),
                '1.0.0'
            );

            wp_enqueue_script(
                'wc-variation-table',
                plugins_url('assets/js/script.js', __FILE__),
                array('jquery'),
                '1.0.0',
                true
            );
        }
    }

    /**
     * Display the variation table
     */
    public function display_variation_table() {
        global $product;

        if (!$product || $product->get_type() !== 'variable' || get_option('wcvt_enable_table') !== 'yes') {
            return;
        }

        // Get variations and attributes
        $variations = $product->get_available_variations();
        $attributes = $product->get_variation_attributes();

        // Debug
        if (empty($variations) || empty($attributes)) {
            error_log('WC Variation Table: No variations or attributes found for product ' . $product->get_id());
            return;
        }

        // Initialize default columns if not set
        if (!get_option('wcvt_columns')) {
            $this->register_settings();
        }

        include plugin_dir_path(__FILE__) . 'templates/variation-table.php';
    }

    /**
     * Add variation table tab to product tabs
     */
    public function add_variation_table_tab($tabs) {
        global $product;
        
        if ($product && $product->get_type() === 'variable' && get_option('wcvt_enable_table') === 'yes') {
            $tabs['variation_table'] = array(
                'title'    => get_option('wcvt_tab_text', __('Product options table', 'wc-variation-table')),
                'priority' => 15,
                'callback' => array($this, 'display_variation_table')
            );
        }
        
        return $tabs;
    }

    /**
     * Add button/link to open modal
     */
    public function add_modal_button() {
        global $product;
        
        if (!$product || $product->get_type() !== 'variable' || get_option('wcvt_enable_table') !== 'yes') {
            return;
        }

        $trigger_type = get_option('wcvt_modal_trigger_type', 'button');
        $text = __('View all variations', 'wc-variation-table');

        switch ($trigger_type) {
            case 'link':
                printf(
                    '<p><a href="#" class="wcvt-open-modal">%s</a></p>',
                    esc_html($text)
                );
                break;
            
            case 'button':
            default:
                printf(
                    '<button type="button" class="button wcvt-open-modal">%s</button>',
                    esc_html($text)
                );
                break;
        }
    }

    /**
     * Add modal markup to footer
     */
    public function add_modal_markup() {
        global $product;
        
        if ($product && $product->get_type() === 'variable' && get_option('wcvt_enable_table') === 'yes') {
            $postfix = get_option('wcvt_modal_title_postfix', '- Available Options');
            ?>
            <div class="wcvt-modal">
                <div class="wcvt-modal-overlay"></div>
                <div class="wcvt-modal-container">
                    <button type="button" class="wcvt-modal-close">&times;</button>
                    <div class="wcvt-modal-content">
                        <h2 class="wcvt-modal-title">
                            <?php 
                            echo esc_html($product->get_title()); 
                            if ($postfix) {
                                echo ' <span class="wcvt-modal-title-postfix">' . esc_html($postfix) . '</span>';
                            }
                            ?>
                        </h2>
                        <?php $this->display_variation_table(); ?>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    /**
     * Add table to the start of product description
     */
    public function add_to_description_start($content) {
        if (!is_product() || !get_option('wcvt_enable_table') === 'yes') {
            return $content;
        }

        global $product;
        if (!$product || $product->get_type() !== 'variable') {
            return $content;
        }

        ob_start();
        $this->display_variation_table();
        $table = ob_get_clean();

        return $table . $content;
    }

    /**
     * Add table to the end of product description
     */
    public function add_to_description_end($content) {
        if (!is_product() || !get_option('wcvt_enable_table') === 'yes') {
            return $content;
        }

        global $product;
        if (!$product || $product->get_type() !== 'variable') {
            return $content;
        }

        ob_start();
        $this->display_variation_table();
        $table = ob_get_clean();

        return $content . $table;
    }

    /**
     * Shortcode output
     */
    public function shortcode_output($atts) {
        if (!get_option('wcvt_enable_table') === 'yes') {
            return '';
        }

        global $product;
        if (!$product || $product->get_type() !== 'variable') {
            return '';
        }

        ob_start();
        $this->display_variation_table();
        return ob_get_clean();
    }

    /**
     * Register Gutenberg block
     */
    public function register_block() {
        if (!function_exists('register_block_type')) {
            return;
        }

        wp_register_script(
            'wcvt-block',
            plugins_url('assets/js/block.js', __FILE__),
            array('wp-blocks', 'wp-element', 'wp-editor'),
            $this->version
        );

        register_block_type('wc-variation-table/table', array(
            'editor_script' => 'wcvt-block',
            'render_callback' => array($this, 'render_block'),
            'supports' => array(
                'multiple' => false
            )
        ));
    }

    /**
     * Render Gutenberg block
     */
    public function render_block($attributes) {
        // Only render on single product pages
        if (!is_product()) {
            return '';
        }

        // Get the current product
        global $product;
        if (!$product) {
            $product = wc_get_product(get_the_ID());
        }

        // Check if we have a valid variable product
        if (!$product || $product->get_type() !== 'variable' || get_option('wcvt_enable_table') !== 'yes') {
            return '';
        }

        ob_start();
        $this->display_variation_table();
        return ob_get_clean();
    }

    /**
     * Get available image sizes
     *
     * @return array
     */
    private function get_available_image_sizes() {
        $sizes = array();
        foreach (get_intermediate_image_sizes() as $size) {
            $sizes[$size] = ucwords(str_replace(array('_', '-'), ' ', $size));
        }
        return $sizes;
    }
}

// Initialize the plugin
new WC_Variation_Table(); 