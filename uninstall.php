<?php
/**
 * WooCommerce Variation Table Uninstaller
 *
 * This file runs when the plugin is deleted through the WordPress admin interface.
 * Performs complete cleanup of plugin data, including:
 * - Plugin options and settings
 * - User meta data and preferences
 * - Transients and cached data
 * - Custom uploaded files
 * - Scheduled tasks
 *
 * @package WC_Variation_Table
 * @subpackage Uninstaller
 * @since 1.0.0
 *
 * @see https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Array of all plugin options that need to be removed.
 * This includes both core settings and feature-specific options.
 *
 * @var array
 */
$options = array(
    // Core plugin options
    'wc_variation_table_settings',
    'wc_variation_table_version',
    
    // Display settings
    'wcvt_show_stock_status',
    'wcvt_enable_table',
    'wcvt_hide_unpurchasable',
    'wcvt_table_placement',
    
    // UI text options
    'wcvt_tab_text',
    'wcvt_modal_title_postfix',
    'wcvt_modal_trigger_type',
    
    // Preview settings
    'wcvt_enable_preview',
    'wcvt_preview_rows',
    'wcvt_table_title',
    
    // Table configuration
    'wcvt_columns',
    'wcvt_custom_fields',
    'wcvt_image_size',
    'wcvt_separate_attribute_columns'
);

// Remove all plugin options
foreach ( $options as $option ) {
    delete_option( $option );
}

// Remove any transient data
delete_transient( 'wc_variation_table_updating' );

/**
 * Clean up user-specific settings and preferences.
 * This includes column visibility settings and other user preferences.
 */
$users = get_users( array( 'fields' => 'ID' ) );
foreach ( $users as $user_id ) {
    delete_user_meta( $user_id, 'wc_variation_table_hidden_columns' );
    delete_user_meta( $user_id, 'wc_variation_table_preferences' );
}

// Remove scheduled tasks
wp_clear_scheduled_hook( 'wc_variation_table_cleanup' );

/**
 * Clean up any uploaded files in the wp-content/uploads directory.
 * This ensures no orphaned files are left behind.
 */
$upload_dir = wp_upload_dir();
$plugin_upload_dir = $upload_dir['basedir'] . '/wc-variation-table';
if ( is_dir( $plugin_upload_dir ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
    require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
    $filesystem = new WP_Filesystem_Direct( null );
    $filesystem->rmdir( $plugin_upload_dir, true );
}

// Clear WordPress object cache
wp_cache_flush();
?> 