<?php
/**
 * Plugin Name: Site Kit for Yandex
 * Plugin URI: https://example.com
 * Description: WordPress plugin for integrating Yandex services with your site.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: site-kit-for-yandex
 * Domain Path: /languages
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SITE_KIT_YANDEX_VERSION', '1.0.0');
define('SITE_KIT_YANDEX_DIR', plugin_dir_path(__FILE__));
define('SITE_KIT_YANDEX_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin singleton.
 */
final class SiteKitForYandex
{
    /**
     * Singleton instance.
     *
     * @var SiteKitForYandex|null
     */
    private static $instance = null;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        // Bootstrap plugin components here.
    }

    /**
     * Get singleton instance.
     *
     * @return SiteKitForYandex
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Run plugin startup logic.
     *
     * @return void
     */
    public static function init()
    {
        self::instance();
        // Register hooks/services here.
    }

    /**
     * Activate the plugin.
     *
     * @return void
     */
    public static function activate()
    {
        // Activation code here.
    }

    /**
     * Deactivate the plugin.
     *
     * @return void
     */
    public static function deactivate()
    {
        // Deactivation code here.
    }
}

/**
 * Global plugin accessor.
 *
 * Usage: skfy()->init();
 *
 * @return SiteKitForYandex
 */
function skfy()
{
    return SiteKitForYandex::instance();
}

add_action('plugins_loaded', ['SiteKitForYandex', 'init']);

register_deactivation_hook(__FILE__, ['SiteKitForYandex', 'deactivate']);
register_activation_hook(__FILE__, ['SiteKitForYandex', 'activate']);
