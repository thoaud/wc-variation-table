<?php
/**
 * Plugin updater class
 *
 * Handles automatic updates for the plugin using GitHub releases.
 * This class implements a custom update checker that bypasses the WordPress.org
 * plugin repository and instead checks for updates directly from a GitHub repository.
 *
 * Features:
 * - Checks for updates via GitHub's plugin-info.json
 * - Caches update checks for 24 hours
 * - Handles SSL verification differently in development
 * - Integrates with WordPress's native update system
 *
 * @package WC_Variation_Table
 * @subpackage Updates
 * @since 1.0.0
 */

namespace WC_Variation_Table;

class Updater {
    /**
     * Plugin slug
     *
     * @var string
     */
    private $plugin_slug;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version;

    /**
     * Cache key
     *
     * @var string
     */
    private $cache_key;

    /**
     * Whether to allow caching
     *
     * @var bool
     */
    private $cache_allowed;

    /**
     * Initialize the updater
     */
    public function __construct() {
        $this->plugin_slug = 'wc-variation-table';
        $this->version = '1.0.0'; // Should match plugin version
        $this->cache_key = 'wc_variation_table_updater';
        $this->cache_allowed = true;

        // Disable SSL verification in dev mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_filter('https_ssl_verify', '__return_false');
            add_filter('https_local_ssl_verify', '__return_false');
            add_filter('http_request_host_is_external', '__return_true');
        }

        add_filter('plugins_api', array($this, 'info'), 20, 3);
        add_filter('site_transient_update_plugins', array($this, 'update'));
        add_action('upgrader_process_complete', array($this, 'purge'), 10, 2);
    }

    /**
     * Get update information from GitHub
     *
     * @return object|bool Update data or false on failure
     */
    private function request() {
        $remote = get_transient($this->cache_key);

        if (false === $remote || !$this->cache_allowed) {
            $remote = wp_remote_get(
                'https://raw.githubusercontent.com/audunhus/wc-variation-table/main/plugin-info.json',
                array(
                    'timeout' => 10,
                    'headers' => array(
                        'Accept' => 'application/json'
                    )
                )
            );

            if (
                is_wp_error($remote)
                || 200 !== wp_remote_retrieve_response_code($remote)
                || empty(wp_remote_retrieve_body($remote))
            ) {
                return false;
            }

            set_transient($this->cache_key, $remote, DAY_IN_SECONDS);
        }

        $remote = json_decode(wp_remote_retrieve_body($remote));

        return $remote;
    }

    /**
     * Provide plugin information for WordPress updates
     *
     * @param false|object|array $response The result object or array
     * @param string $action The type of information being requested from the Plugin Installation API
     * @param object $args Plugin API arguments
     * @return false|object Plugin information or false
     */
    public function info($response, $action, $args) {
        // Do nothing if this is not about getting plugin information
        if ('plugin_information' !== $action) {
            return $response;
        }

        // Do nothing if it is not our plugin
        if (empty($args->slug) || $this->plugin_slug !== $args->slug) {
            return $response;
        }

        // Get updates
        $remote = $this->request();

        if (!$remote) {
            return $response;
        }

        $response = new \stdClass();

        $response->name = $remote->name;
        $response->slug = $remote->slug;
        $response->version = $remote->version;
        $response->tested = $remote->tested;
        $response->requires = $remote->requires;
        $response->author = $remote->author;
        $response->author_profile = $remote->author_profile;
        $response->donate_link = $remote->donate_link;
        $response->homepage = $remote->homepage;
        $response->download_link = $remote->download_url;
        $response->trunk = $remote->download_url;
        $response->requires_php = $remote->requires_php;
        $response->last_updated = $remote->last_updated;

        $response->sections = array(
            'description' => $remote->sections->description,
            'installation' => $remote->sections->installation,
            'changelog' => $remote->sections->changelog
        );

        if (!empty($remote->banners)) {
            $response->banners = array(
                'low' => $remote->banners->low,
                'high' => $remote->banners->high
            );
        }

        return $response;
    }

    /**
     * Check for plugin updates
     *
     * @param object $transient The pre-saved value of the `update_plugins` site transient
     * @return object Modified transient value
     */
    public function update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = $this->request();

        if (
            $remote
            && version_compare($this->version, $remote->version, '<')
            && version_compare($remote->requires, get_bloginfo('version'), '<=')
            && version_compare($remote->requires_php, PHP_VERSION, '<')
        ) {
            $response = new \stdClass();
            $response->slug = $this->plugin_slug;
            $response->plugin = "{$this->plugin_slug}/{$this->plugin_slug}.php";
            $response->new_version = $remote->version;
            $response->tested = $remote->tested;
            $response->package = $remote->download_url;

            $transient->response[$response->plugin] = $response;
        }

        return $transient;
    }

    /**
     * Purge plugin update cache after update
     *
     * @param \WP_Upgrader $upgrader WP_Upgrader instance
     * @param array $options Update data
     */
    public function purge($upgrader, $options) {
        if (
            $this->cache_allowed
            && 'update' === $options['action']
            && 'plugin' === $options['type']
        ) {
            delete_transient($this->cache_key);
        }
    }
} 