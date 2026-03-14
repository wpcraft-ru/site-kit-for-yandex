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

        //Просмотр метрики по адресу https://metrika.yandex.ru/


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