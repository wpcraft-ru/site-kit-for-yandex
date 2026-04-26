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

    public static function getCounterId()
    {
        return skfy()->config()->get('metrika_counter_id');
    }

    public static function renderTop10KeyPhraseForLast28Days()
    {
        YandexOverview::renderTop10KeyPhraseForLast28Days();
    }

    public static function getTop10KeyPhraseForLast28Days()
    {
        $cachedData = get_transient('skfy_metrika_top_10_keyphrase_28_days');
        if (! empty($cachedData)) {
            return $cachedData;
        }

        $metrikaCounterId = self::getCounterId();
        if (empty($metrikaCounterId)) {
            return new \WP_Error('no_counter_id', __('Metrika counter ID is not set.', 'site-kit-for-yandex'));
        }

        $query = [
            'id' => (string) $metrikaCounterId,
            'dimensions' => 'ym:s:searchPhrase',
            'metrics' => 'ym:s:visits',
            'sort' => '-ym:s:visits',
            'limit' => 10,
            'date1' => '28daysAgo',
            'date2' => 'today',
        ];

        $data = self::api('stat/v1/data?'.http_build_query($query));
        if (is_wp_error($data)) {
            return $data;
        }

        if (empty($data['data']) || ! is_array($data['data'])) {
            return [];
        }

        $preparedData = [];
        foreach ($data['data'] as $item) {
            $preparedData[] = [
                'phrase' => isset($item['dimensions'][0]['name']) ? $item['dimensions'][0]['name'] : '',
                'visits' => isset($item['metrics'][0]) ? $item['metrics'][0] : 0,
            ];
        }

        set_transient('skfy_metrika_top_10_keyphrase_28_days', $preparedData, HOUR_IN_SECONDS);

        return $preparedData;
    }

    public static function renderTop10PagesForLast28Days()
    {
        YandexOverview::renderTop10PagesForLast28Days();
    }

    public static function getTop10PagesForLast28Days()
    {
        $cachedData = get_transient('skfy_metrika_top_10_pages_28_days');
        if (! empty($cachedData)) {
            return $cachedData;
        }

        $metrikaCounterId = self::getCounterId();
        if (empty($metrikaCounterId)) {
            return new \WP_Error('no_counter_id', __('Metrika counter ID is not set.', 'site-kit-for-yandex'));
        }

        $query = [
            'id' => (string) $metrikaCounterId,
            'dimensions' => 'ym:s:startURL',
            'metrics' => 'ym:s:visits',
            'sort' => '-ym:s:visits',
            'limit' => 10,
            'date1' => '28daysAgo',
            'date2' => 'today',
        ];

        $data = self::api('stat/v1/data?'.http_build_query($query));
        if (is_wp_error($data)) {
            return $data;
        }

        if (empty($data['data']) || ! is_array($data['data'])) {
            return [];
        }

        $preparedData = [];
        foreach ($data['data'] as $item) {
            $url = $item['dimensions'][0]['name'];
            $visits = $item['metrics'][0];
            $preparedData[] = [
                'url' => $url,
                'visits' => $visits,
            ];
        }

        foreach ($preparedData as &$item) {
            $postId = url_to_postid($item['url']);
            if ($postId) {
                $item['title'] = get_the_title($postId);
            } else {
                $item['title'] = '';
            }
        }

        set_transient('skfy_metrika_top_10_pages_28_days', $preparedData, HOUR_IN_SECONDS);

        return $preparedData;
    }

    public static function getTrafficAndSources($url)
    {
        $counterId = self::getCounterId();
        if (empty($counterId)) {
            return new \WP_Error('no_counter_id', __('Metrika counter ID is not set.', 'site-kit-for-yandex'));
        }

        $query = [
            'id' => (string) $counterId,
            'dimensions' => 'ym:s:trafficSourceName',
            'metrics' => 'ym:s:visits,ym:s:users,ym:s:bounceRate,ym:s:avgVisitDurationSeconds,ym:s:pageDepth',
            'filters' => "ym:s:startURL=='".esc_url_raw($url)."'",
            'sort' => '-ym:s:visits',
            'date1' => '28daysAgo',
            'date2' => 'today',
        ];

        return self::api('stat/v1/data?'.http_build_query($query));
    }

    public static function getSearchPhrases($url)
    {
        $counterId = self::getCounterId();
        if (empty($counterId)) {
            return new \WP_Error('no_counter_id', __('Metrika counter ID is not set.', 'site-kit-for-yandex'));
        }

        $query = [
            'id' => (string) $counterId,
            'dimensions' => 'ym:s:searchPhrase,ym:s:searchEngineName',
            'metrics' => 'ym:s:visits,ym:s:users',
            'filters' => "ym:s:startURL=='".esc_url_raw($url)."'",
            'sort' => '-ym:s:visits',
            'limit' => 20,
            'date1' => '28daysAgo',
            'date2' => 'today',
        ];

        return self::api('stat/v1/data?'.http_build_query($query));
    }

    public static function api($path, $method = 'GET', $data = [])
    {
        $accessToken = skfy()->config()->getAccessToken();
        if (! $accessToken) {
            return new \WP_Error('no_access_token', __('Access token is not set.', 'site-kit-for-yandex'));
        }

        $url = 'https://api-metrika.yandex.net/'.ltrim($path, '/');

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => "OAuth $accessToken",
                'Content-Type' => 'application/json',
            ],
            'timeout' => 15,
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

        return new \WP_Error(
            'api_error',
            sprintf(
                /* translators: 1: HTTP status code, 2: API response body. */
                __('API request failed with status code %1$d: %2$s', 'site-kit-for-yandex'),
                $code,
                $body
            )
        );
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
            esc_html__('Counter ID from Yandex.Metrika, used to request statistics via API.', 'site-kit-for-yandex')
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
            '<p>%s</p>',
            esc_html__('Yandex Metrika is a free web analytics tool that collects traffic and user behavior data so site owners can evaluate ad performance, improve usability, and increase conversion.', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Metrika can be viewed at: ', 'site-kit-for-yandex'),
            esc_url('https://metrika.yandex.ru/'),
            'metrika.yandex.ru'
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('For connecting Yandex.Metrika, we recommend the official WordPress plugin.', 'site-kit-for-yandex'),
            esc_url('https://ru.wordpress.org/plugins/wp-yandex-metrika/'),
            esc_html__('WP Yandex Metrika', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('To retrieve Metrika data, use the official Yandex API.', 'site-kit-for-yandex'),
            esc_url('https://yandex.ru/dev/metrika/ru/'),
            esc_html__('Yandex.Metrika API', 'site-kit-for-yandex')
        );
    }
}
