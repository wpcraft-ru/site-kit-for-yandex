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

    public static function renderTop10KeyPhraseForLast28Days()
    {
        YandexOverview::renderTop10KeyPhraseForLast28Days();
    }

    public static function getTop10KeyPhraseForLast28Days()
    {
        return YandexOverview::getTop10KeyPhraseForLast28Days();
    }

    public static function renderTop10PagesForLast28Days()
    {
        YandexOverview::renderTop10PagesForLast28Days();
    }

    public static function getTop10PagesForLast28Days()
    {
        return YandexOverview::getTop10PagesForLast28Days();
    }

    public static function api($path, $method = 'GET', $data = [])
    {
        return YandexOverview::metrikaApi($path, $method, $data);
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

        add_settings_field(
            'metrika_counter_id',
            __('Metrika Counter ID', 'site-kit-for-yandex'),
            [self::class, 'renderCounterIdField'],
            self::$pageSlug,
            self::$sectionId
        );
    }

    /**
     * Render Metrika counter ID field.
     *
     * @return void
     */
    public static function renderCounterIdField()
    {
        $options = get_option('site_kit_for_yandex_options', []);
        $value = isset($options['metrika_counter_id']) ? $options['metrika_counter_id'] : '';

        printf(
            '<input type="text" class="regular-text" name="site_kit_for_yandex_options[metrika_counter_id]" value="%s" inputmode="numeric" />',
            esc_attr($value)
        );

        printf(
            '<p class="description">%s</p>',
            esc_html__('ID счётчика из Яндекс.Метрики, используется для запроса статистики через API.', 'site-kit-for-yandex')
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
            esc_url(admin_url('tools.php?page=skfy-overview')),
            esc_html__('Yandex Overview', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Просмотр метрики осуществляется по ссылке: ', 'site-kit-for-yandex'),
            esc_url('https://metrika.yandex.ru/'),
            'metrika.yandex.ru'
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Для подключения Яндекс.Метрики рекомендуем официальный плагин WordPress.', 'site-kit-for-yandex'),
            esc_url('https://ru.wordpress.org/plugins/wp-yandex-metrika/'),
            esc_html__('WP Yandex Metrika', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('Для получения данных метрики используйте официальный API Яндекса.', 'site-kit-for-yandex'),
            esc_url('https://yandex.ru/dev/metrika/ru/'),
            esc_html__('API Яндекс.Метрика', 'site-kit-for-yandex')
        );
    }
}
