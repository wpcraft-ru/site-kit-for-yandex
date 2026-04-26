<?php

namespace SiteKitForYandex;

YandexIndexNow::init();

class YandexIndexNow
{
    public static function init()
    {
        add_action('admin_init', [self::class, 'registerSettingsSection'], 50);
    }

    public static function registerSettingsSection()
    {
        add_settings_section(
            'site_kit_for_yandex_indexnow_section',
            __('Yandex IndexNow', 'site-kit-for-yandex'),
            [self::class, 'renderSectionText'],
            'site-kit-for-yandex'
        );
    }

    public static function renderSectionText()
    {
        // Plugin officially recommended by Yandex: https://wordpress.org/plugins/recrawler/
        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('Plugin officially recommended by Yandex:', 'site-kit-for-yandex'),
            esc_url('https://wordpress.org/plugins/recrawler/'),
            esc_html__('Recrawler', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('Notify search engines about new or updated site pages using the IndexNow protocol.', 'site-kit-for-yandex'),
            esc_url('https://yandex.ru/dev/indexnow/'),
            esc_html__('API Documentation', 'site-kit-for-yandex')
        );
    }
}