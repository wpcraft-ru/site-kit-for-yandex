<?php 

namespace SiteKitForYandex;

YandexAds::init();

class YandexAds
{
    public static function init()
    {
        add_action('admin_init', [self::class, 'registerSettingsSection'], 60);
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
            esc_html__('Размещайте рекламу на вашем сайте через Рекламную сеть Яндекса и зарабатывайте на трафике. Подключите площадку, настройте рекламные блоки и отслеживайте доход в удобной статистике.', 'site-kit-for-yandex'),
            esc_url('https://yandex.ru/adv/publishers/monetization'),
            esc_html__('Узнать больше о размещении рекламы на сайте', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('Плагин от команды WPCraft для удобного размещения рекламы на сайте:', 'site-kit-for-yandex'),
            esc_url('https://wpcraft.ru/catalog/ads-media-planner'),
            esc_html__('Ads Media Planner', 'site-kit-for-yandex')
        );
    }
}