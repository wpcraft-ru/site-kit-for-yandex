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
        add_action('admin_menu', [self::class, 'addToolsPage']);

        //site_kit_for_yandex_webmaster_tools_page_content
        add_action('site_kit_for_yandex_webmaster_tools_page_content', [self::class, 'host_search_queries_popular']);
    }


    public static function host_search_queries_popular()
    {
        // echo 111;
        //get info about site https://yandex.ru/dev/webmaster/doc/ru/reference/hosts-id 

        ?>

        <h2>Популярные запросы</h2>
        <?php 

        //get pop query https://yandex.ru/dev/webmaster/doc/ru/reference/host-search-queries-popular
        $dataTest2 = self::apiSite('search-queries/popular?order_by=TOTAL_SHOWS&query_indicator=TOTAL_SHOWS&limit=100');
        dd($dataTest2);


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
                'Authorization' => 'OAuth '.$accessToken,
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
        } else {
            return new \WP_Error('api_error', sprintf(__('API request failed with status code %d: %s', 'site-kit-for-yandex'), $code, $body));
        }
    }

    /**
     * Add Yandex Webmaster page under Tools menu.
     *
     * @return void
     */
    public static function addToolsPage()
    {
        add_management_page(
            __('Yandex Webmaster', 'site-kit-for-yandex'),
            __('Yandex Webmaster', 'site-kit-for-yandex'),
            'manage_options',
            'site-kit-for-yandex-webmaster',
            [self::class, 'renderToolsPage']
        );
    }

    /**
     * Render Yandex Webmaster tools page.
     *
     * @return void
     */
    public static function renderToolsPage()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Yandex Webmaster', 'site-kit-for-yandex'); ?></h1>
            <p>
                <?php
                printf(
                    '%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.',
                    esc_html__('Просмотр информации о сайте в целом:', 'site-kit-for-yandex'),
                    esc_url('https://webmaster.yandex.ru/'),
                    esc_html__('webmaster.yandex.ru', 'site-kit-for-yandex')
                );
                ?>
            </p>
            <p>
                <?php
                printf(
                    '<a href="%1$s">%2$s</a>.',
                    esc_url(admin_url('options-general.php?page=site-kit-for-yandex')),
                    esc_html__('Настройки плагина', 'site-kit-for-yandex')
                );
                ?>
            </p>

            <?php do_action('site_kit_for_yandex_webmaster_tools_page_content'); ?>
        </div>
        <?php
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
                    esc_url(admin_url('tools.php?page=site-kit-for-yandex-webmaster')),
                    esc_html__('Yandex Webmaster Tools', 'site-kit-for-yandex')
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