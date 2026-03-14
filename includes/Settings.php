<?php

namespace SiteKitForYandex;

final class Settings
{
    public static function init()
    {
        add_action('admin_menu', [self::class, 'addSettingsPage']);
    }

    public function get($key)
    {
        $options = get_option('site_kit_for_yandex_options', []);
        return isset($options[$key]) ? $options[$key] : null;
    }

    public function set($key, $value)
    {
        $options = get_option('site_kit_for_yandex_options', []);
        $options[$key] = $value;
        update_option('site_kit_for_yandex_options', $options);
    }

    public static function addSettingsPage()
    {
        add_options_page(
            'Site Kit for Yandex Settings',
            'Site Kit for Yandex',
            'manage_options',
            'site-kit-for-yandex',
            function () {
                ?>
                        <div class="wrap">
                            <h1>Site Kit for Yandex Settings</h1>
                            <p>Configure your Yandex services integration here.</p>
                            <form method="post" action="options.php">
                                <?php
                                    settings_fields('site_kit_for_yandex_options');
                                    do_settings_sections('site-kit-for-yandex');
                                    submit_button();
                                    ?>
                            </form>
                        </div>
                        <?php
            }
        );

        register_setting('site_kit_for_yandex_options', 'site_kit_for_yandex_options');
    }
}