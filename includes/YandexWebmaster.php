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

    public static function renderSqi()
    {
        YandexOverview::renderSqi();
    }

    public static function renderSummary()
    {
        YandexOverview::renderSummary();
    }

    public static function getPopularQueries(
        $orderBy = 'TOTAL_SHOWS',
        $queryIndicator = 'TOTAL_SHOWS',
        $deviceTypeIndicator = '',
        $dateFrom = '',
        $dateTo = '',
        $offset = 0,
        $limit = 100
    ) {
        return YandexOverview::getPopularQueries(
            $orderBy,
            $queryIndicator,
            $deviceTypeIndicator,
            $dateFrom,
            $dateTo,
            $offset,
            $limit
        );
    }

    public static function getSummary()
    {
        return YandexOverview::getSummary();
    }

    public static function getSqi($dateFrom = '', $dateTo = '')
    {
        return YandexOverview::getSqi($dateFrom, $dateTo);
    }

    public static function apiSite($path, $method = 'GET', $data = [])
    {
        return YandexOverview::webmasterApiSite($path, $method, $data);
    }

    public static function getSiteInfo()
    {
        return YandexOverview::getSiteInfo();
    }

    public static function api($path, $method = 'GET', $data = [])
    {
        return YandexOverview::webmasterApi($path, $method, $data);
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
            function () {
                printf(
                    '<p>%1$s <a href="%2$s">%3$s</a>.</p>',
                    esc_html__('Сводка по сайту доступна на странице', 'site-kit-for-yandex'),
                    esc_url(admin_url('tools.php?page=skfy-overview')),
                    esc_html__('Yandex Overview', 'site-kit-for-yandex')
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
            },
            self::$pageSlug
        );
    }
}
