<?php

namespace SiteKitForYandex;

Settings::init();

final class Settings
{
    /**
     * Settings section ID.
     */
    private static $sectionId = 'site_kit_for_yandex_authorization_section';

    /**
     * Settings page slug.
     */
    private static $pageSlug = 'site-kit-for-yandex';

    public static function init()
    {
        add_action('admin_menu', [self::class, 'addSettingsPage']);
        add_action('admin_init', [self::class, 'registerSettingsSection']);
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

    public function getClientId()
    {
        return $this->get('client_id');
    }

    public function getClientSecret()
    {
        return $this->get('client_secret');
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
                <h1>Site Kit for Yandex</h1>
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

    /**
     * Register settings section and authorization fields.
     *
     * @return void
     */
    public static function registerSettingsSection()
    {
        add_settings_section(
            self::$sectionId,
            __('Авторизация - Яндекс OAuth', 'site-kit-for-yandex'),
            [self::class, 'renderAuthorizationSectionText'],
            self::$pageSlug
        );

        add_settings_field(
            'client_id',
            __('ClientID', 'site-kit-for-yandex'),
            [self::class, 'renderClientIdField'],
            self::$pageSlug,
            self::$sectionId
        );

        add_settings_field(
            'client_secret',
            __('Client Secret', 'site-kit-for-yandex'),
            [self::class, 'renderClientSecretField'],
            self::$pageSlug,
            self::$sectionId
        );
    }

    /**
     * Render authorization section description.
     *
     * @return void
     */
    public static function renderAuthorizationSectionText()
    {
        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('Для работы интеграции нужно получить ключи приложения в кабинете Яндекс OAuth:', 'site-kit-for-yandex'),
            esc_url('https://oauth.yandex.ru'),
            esc_html__('получить ClientID и Client Secret', 'site-kit-for-yandex')
        );

        echo '<ol style="margin-left: 1.2em;">';
        printf(
            '<li>%s</li>',
            esc_html__('Добавьте новое приложение и укажите название сайта, чтобы удобнее ориентироваться в списке приложений.', 'site-kit-for-yandex')
        );
        printf(
            '<li>%s</li>',
            esc_html__('В настройках приложения выдайте разрешения на доступ к данным Яндекс.Метрики и Яндекс.Вебмастера.', 'site-kit-for-yandex')
        );
        printf(
            '<li>%s</li>',
            esc_html__('После этого скопируйте полученные ClientID и Client Secret в поля выше и сохраните настройки.', 'site-kit-for-yandex')
        );
        echo '</ol>';

    }

    /**
     * Render ClientID field.
     *
     * @return void
     */
    public static function renderClientIdField()
    {
        $options = get_option('site_kit_for_yandex_options', []);
        $value = isset($options['client_id']) ? $options['client_id'] : '';

        printf(
            '<input type="text" class="regular-text" name="site_kit_for_yandex_options[client_id]" value="%s" autocomplete="off" />',
            esc_attr($value)
        );
    }

    /**
     * Render Client Secret field.
     *
     * @return void
     */
    public static function renderClientSecretField()
    {
        $options = get_option('site_kit_for_yandex_options', []);
        $value = isset($options['client_secret']) ? $options['client_secret'] : '';

        printf(
            '<input type="password" class="regular-text" name="site_kit_for_yandex_options[client_secret]" value="%s" autocomplete="new-password" />',
            esc_attr($value)
        );
    }
}