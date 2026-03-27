<?php

namespace SiteKitForYandex;

YandexID::init();

class YandexID
{
    public static function init()
    {
        add_action('admin_init', [self::class, 'registerSettingsSection'], 40);
    }

    public static function registerSettingsSection()
    {
        add_settings_section(
            'site_kit_for_yandex_id_section',
            __('Yandex ID', 'site-kit-for-yandex'),
            [self::class, 'renderSectionText'],
            'site-kit-for-yandex'
        );
    }

    public static function renderSectionText()
    {
        //Добавьте авторизацию через проверенный плагин https://wpcraft.ru/wordpress/plugins/socialify

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('Для авторизации через Яндекс ID можно использовать проверенный плагин Socialify авторизации, который поддерживает Яндекс ID.', 'site-kit-for-yandex'),
            esc_url('https://wpcraft.ru/wordpress/plugins/socialify'),
            esc_html__('Подробнее', 'site-kit-for-yandex')
        );
        

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('Яндекс ID — это единая учётная запись для доступа к сервисам Яндекса и партнёрским сайтам и приложениям с поддержкой этой авторизации; она позволяет безопасно входить в аккаунты, управлять личными данными, платёжными реквизитами и настройками, а также упрощает процесс авторизации — без необходимости запоминать множество логинов и паролей.', 'site-kit-for-yandex'),
            esc_url('https://oauth.yandex.ru/'),
            esc_html__('Подробнее', 'site-kit-for-yandex')
        );

    }
}