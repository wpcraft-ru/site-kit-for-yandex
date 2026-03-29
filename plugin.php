<?php
/**
 * Plugin Name: @ Site Kit for Yandex
 * Plugin URI: https://wpcraft.ru
 * Description: WordPress plugin for integrating Yandex services with your site.
 * Author: WPCraft
 * Author URI: https://wpcraft.ru
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: site-kit-for-yandex
 * Domain Path: /languages
 * Version: 0.2.260330
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

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
     * Run plugin startup logic.
     *
     * @return void
     */
    public static function init()
    {
        self::instance();
        foreach (glob(self::$instance->dir.'includes/*.php') as $file) {
            require_once $file;
        }

        foreach (glob(self::$instance->dir.'includes/Tools/*.php') as $file) {
            require_once $file;
        }


        add_filter('plugin_action_links_'.plugin_basename(__FILE__), [self::instance(), 'plugin_action_links']);
        
    }

    /**
     * Get configuration instance.
     *
     * @return \SiteKitForYandex\Settings
     */
    public function config()
    {
        return new \SiteKitForYandex\Settings();
    }

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
     * Add plugin action links in plugins list.
     *
     * @param array $links Existing action links.
     * @return array
     */
    public function plugin_action_links($links)
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('options-general.php?page=site-kit-for-yandex')),
            esc_html__('Settings', 'site-kit-for-yandex')
        );

        array_unshift($links, $settings_link);

        return $links;
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
