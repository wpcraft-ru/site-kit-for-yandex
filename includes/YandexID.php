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
        // Add authorization via the proven plugin: https://wpcraft.ru/wordpress/plugins/socialify

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('For Yandex ID authorization, you can use the proven Socialify authentication plugin that supports Yandex ID.', 'site-kit-for-yandex'),
            esc_url('https://wpcraft.ru/wordpress/plugins/socialify'),
            esc_html__('Learn more', 'site-kit-for-yandex')
        );

        $yandex_oauth_url = 'https://oauth.yandex.com/';

        if (0 === strpos(determine_locale(), 'ru')) {
            $yandex_oauth_url = 'https://oauth.yandex.ru/';
        }

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('Yandex ID is a unified account for accessing Yandex services and partner websites/apps that support this login method. It enables secure sign-in, management of personal data, payment details and settings, and simplifies authorization without remembering many usernames and passwords.', 'site-kit-for-yandex'),
            esc_url($yandex_oauth_url),
            esc_html__('Learn more', 'site-kit-for-yandex')
        );

    }
}