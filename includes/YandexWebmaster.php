<?php

namespace SiteKitForYandex;

YandexWebmaster::init();

class YandexWebmaster
{
    /**
     * Settings section ID.
     */
    private static $sectionId = 'site_kit_for_yandex_webmaster_section';

    /**
     * Settings page slug.
     */
    private static $pageSlug = 'site-kit-for-yandex';

    public static function init()
    {
        add_action('admin_init', [self::class, 'registerSettingsSection']);
        add_action('admin_menu', [self::class, 'addToolsPage']);
    }

    /**
     * Add Yandex Webmaster page under Tools menu.
     *
     * @return void
     */
    public static function addToolsPage()
    {
        add_management_page(
            __('Yandex Webmaster', 'site-kit-for-yandex'),
            __('Yandex Webmaster', 'site-kit-for-yandex'),
            'manage_options',
            'site-kit-for-yandex-webmaster',
            [self::class, 'renderToolsPage']
        );
    }

    /**
     * Render Yandex Webmaster tools page.
     *
     * @return void
     */
    public static function renderToolsPage()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Yandex Webmaster', 'site-kit-for-yandex'); ?></h1>
            <p>
                <?php
                printf(
                    '%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.',
                    esc_html__('Просмотр информации о сайте в целом:', 'site-kit-for-yandex'),
                    esc_url('https://webmaster.yandex.ru/'),
                    esc_html__('webmaster.yandex.ru', 'site-kit-for-yandex')
                );
                ?>
            </p>
            <p>
                <?php
                printf(
                    '<a href="%1$s">%2$s</a>.',
                    esc_url(admin_url('options-general.php?page=site-kit-for-yandex')),
                    esc_html__('Настройки плагина', 'site-kit-for-yandex')
                );
                ?>
            </p>

        </div>
        <?php
    }

    /**
     * Register Yandex Webmaster settings section.
     *
     * @return void
     */
    public static function registerSettingsSection()
    {
        add_settings_section(
            self::$sectionId,
            __('Yandex Webmaster', 'site-kit-for-yandex'),
            [self::class, 'renderSectionText'],
            self::$pageSlug
        );
    }

    /**
     * Render Yandex Webmaster section description text.
     *
     * @return void
     */
    public static function renderSectionText()
    {

        // Сводка /wp-admin/tools.php?page=site-kit-for-yandex-webmaster
        printf(
            '<p>%1$s <a href="%2$s">%3$s</a>.</p>',
            esc_html__('Сводка по сайту доступна на странице', 'site-kit-for-yandex'),
            esc_url(admin_url('tools.php?page=site-kit-for-yandex-webmaster')),
            esc_html__('Yandex Webmaster Tools', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Подключение сайта и просмотр данных Яндекс.Вебмастера осуществляется через сайт', 'site-kit-for-yandex'),
            esc_url('https://webmaster.yandex.ru/sites/'),
            esc_html__('Яндекс.Вебмастер', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Для интеграции используем API Яндекс.Вебмастера.', 'site-kit-for-yandex'),
            esc_url('https://yandex.ru/dev/webmaster/'),
            esc_html__('Документация API', 'site-kit-for-yandex')
        );

    }
}