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
        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Подключение сайта и просмотр данных Яндекс.Вебмастера осуществляется через сайт', 'site-kit-for-yandex'),
            esc_url('https://webmaster.yandex.ru/sites/'),
            esc_html__('Яндекс.Вебмастер', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Для подключения и настройки интеграции используем API Яндекс.Вебмастера.', 'site-kit-for-yandex'),
            esc_url('https://yandex.ru/dev/webmaster/'),
            esc_html__('Документация API', 'site-kit-for-yandex')
        );

    }
}