<?php

namespace SiteKitForYandex;

YandexAds::init();

class YandexAds
{
    public static function init()
    {
        add_action('admin_init', [self::class, 'registerSettingsSection'], 110);
    }

    public static function registerSettingsSection()
    {
        add_settings_section(
            'site_kit_for_yandex_ads_section',
            __('Yandex Ads', 'site-kit-for-yandex'),
            [self::class, 'renderSectionText'],
            'site-kit-for-yandex'
        );
    }

    public static function renderSectionText()
    {
        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('A plugin by the WPCraft team for convenient ad placement on your site:', 'site-kit-for-yandex'),
            esc_url('https://wpcraft.ru/catalog/ads-media-planner'),
            esc_html__('Ads Media Planner', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('Place ads on your site via the Yandex Advertising Network and monetize traffic. Connect your platform, configure ad blocks, and track revenue in convenient statistics.', 'site-kit-for-yandex'),
            esc_url('https://yandex.ru/adv/publishers/monetization'),
            esc_html__('Learn more about placing ads on your site', 'site-kit-for-yandex')
        );
    }
}