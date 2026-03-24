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


    public static function getSiteInfo()
    {
        $data = get_transient('skfy_webmaster_site_info');
        if ($data !== false) {
            return $data;
        }

        $userId = self::api('user')['user_id'] ?? null;
        if (! $userId) {
            return null;
        }

        $hosts = self::api('user/'.$userId.'/hosts')['hosts'] ?? [];
        $host = null;
        foreach ($hosts as $host) {
            $baseUrlFromAPI = $host['unicode_host_url'] ?? null;
            $baseUrlFromWP = site_url();
            if ($baseUrlFromAPI) {
                if (rtrim($baseUrlFromAPI, '/') === rtrim($baseUrlFromWP, '/')) {
                    break;
                }
            }
        }
        if (! $host) {
            return null;
        }

        $data = [
            'user_id' => $userId,
            'host' => $host,
        ];

        set_transient('skfy_webmaster_site_info', $data, HOUR_IN_SECONDS);

        return $data;
    }

    public static function apiSite($path, $method = 'GET', $data = [])
    {
        $siteInfo = self::getSiteInfo();
        if (! $siteInfo || ! isset($siteInfo['user_id'], $siteInfo['host']['host_id'])) {
            return new \WP_Error('no_site_info', __('Site information is not available.', 'site-kit-for-yandex'));
        }

        $userId = $siteInfo['user_id'];
        $hostId = $siteInfo['host']['host_id'];
        $apiPath = 'user/'.$userId.'/hosts/'.$hostId;

        if (! empty($path)) {
            $apiPath .= '/'.ltrim($path, '/');
        }

        return self::api($apiPath, $method, $data);
    }

    public static function api($path, $method = 'GET', $data = [])
    {
        $accessToken = skfy()->config()->getAccessToken();
        if (! $accessToken) {
            return new \WP_Error('no_access_token', __('Access token is not set.', 'site-kit-for-yandex'));
        }

        $url = 'https://api.webmaster.yandex.net/v4/'.ltrim($path, '/');

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => "OAuth $accessToken",
                'Content-Type' => 'application/json',
            ],
        ];

        if (! empty($data)) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code >= 200 && $code < 300) {
            return json_decode($body, true);
        }

        return new \WP_Error('api_error', sprintf(__('API request failed with status code %d: %s', 'site-kit-for-yandex'), $code, $body));
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
