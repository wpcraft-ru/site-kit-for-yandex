<?php 

namespace SiteKitForYandex;

YandexMetrika::init();

class YandexMetrika
{
    /**
     * Settings section ID.
     */
    private static $sectionId = 'site_kit_for_yandex_metrika_section';

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
     * Add Yandex Metrika page under Tools menu.
     *
     * @return void
     */
    public static function addToolsPage()
    {
        add_management_page(
            __('Yandex Metrika', 'site-kit-for-yandex'),
            __('Yandex Metrika', 'site-kit-for-yandex'),
            'manage_options',
            'site-kit-for-yandex-metrika',
            [self::class, 'renderToolsPage']
        );
    }

    /**
     * Render Yandex Metrika tools page.
     *
     * @return void
     */
    public static function renderToolsPage()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Yandex Metrika', 'site-kit-for-yandex'); ?></h1>
            <p>
                <?php
                printf(
                    '%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.',
                    esc_html__('Просмотр информации о метрике:', 'site-kit-for-yandex'),
                    esc_url('https://metrika.yandex.ru/'),
                    esc_html__('metrika.yandex.ru', 'site-kit-for-yandex')
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
     * Register Yandex Metrika settings section.
     *
     * @return void
     */
    public static function registerSettingsSection()
    {
        add_settings_section(
            self::$sectionId,
            __('Yandex Metrika', 'site-kit-for-yandex'),
            [self::class, 'renderSectionText'],
            self::$pageSlug
        );
    }

    /**
     * Render Yandex Metrika section description text.
     *
     * @return void
     */
    public static function renderSectionText()
    {
        printf(
            '<p>%1$s <a href="%2$s">%3$s</a>.</p>',
            esc_html__('Сводка по метрике доступна на странице', 'site-kit-for-yandex'),
            esc_url(admin_url('tools.php?page=site-kit-for-yandex-metrika')),
            esc_html__('Yandex Metrika Tools', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Просмотр метрики осуществляется через сайт', 'site-kit-for-yandex'),
            esc_url('https://metrika.yandex.ru/'),
            esc_html__('Яндекс.Метрика', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Для подключения Яндекс.Метрики используйте официальный плагин WordPress.', 'site-kit-for-yandex'),
            esc_url('https://ru.wordpress.org/plugins/wp-yandex-metrika/'),
            esc_html__('WP Yandex Metrika', 'site-kit-for-yandex')
        );
    }

}