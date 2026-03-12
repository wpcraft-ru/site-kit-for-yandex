<?php
/**
 * Plugin Name: Site Kit for Yandex
 * Plugin URI: https://example.com
 * Description: WordPress plugin for integrating Yandex services with your site.
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: site-kit-for-yandex
 * Domain Path: /languages
 * Version: 0.1.260115
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin singleton.
 */
final class SiteKitForYandex
{
    /**
     * Plugin version.
     *
     * @var string
     */
    private $version;

    /**
     * Absolute plugin directory path.
     *
     * @var string
     */
    private $dir;

    /**
     * Plugin URL.
     *
     * @var string
     */
    private $url;

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
        $this->dir = plugin_dir_path(__FILE__);
        $this->url = plugin_dir_url(__FILE__);
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
