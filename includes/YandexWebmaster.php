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
        // add_action('site_kit_for_yandex_webmaster_tools_page_content', [self::class, 'renderSqi']);
        add_action('site_kit_for_yandex_webmaster_tools_page_content', [self::class, 'renderSummary']);
    }

    public static function renderSqi()
    {
        //get info about site https://yandex.ru/dev/webmaster/doc/ru/reference/hosts-id 

        ?>

        <h2>Индекс качества сайта (ИКС)</h2>
        <?php

        //get sqi history https://yandex.ru/dev/webmaster/doc/ru/reference/sqi-history
        $value = self::getSqi();
        printf('<p>ИКС: <strong>%s</strong></p>', esc_html($value));
        // dd($value);
    }

    public static function renderSummary()
    {
        ?>

        <h2>Сводка по сайту</h2>
        <?php

        $data = self::getSummary();
        if (is_wp_error($data)) {
            printf(
                '<p>%s</p>',
                esc_html($data->get_error_message())
            );

            return;
        }

        $sqi = isset($data['sqi']) ? (int) $data['sqi'] : null;
        $excludedPagesCount = isset($data['excluded_pages_count']) ? (int) $data['excluded_pages_count'] : null;
        $searchablePagesCount = isset($data['searchable_pages_count']) ? (int) $data['searchable_pages_count'] : null;
        $siteProblems = isset($data['site_problems']) && is_array($data['site_problems']) ? $data['site_problems'] : [];
        ?>
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
        <table class="widefat striped" style="max-width: 720px;">
            <tbody>
                <tr>
                    <td><strong><?php echo esc_html__('Индекс качества сайта (ИКС)', 'site-kit-for-yandex'); ?></strong></td>
                    <td><?php echo null !== $sqi ? esc_html(number_format_i18n($sqi)) : '—'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Страниц в поиске', 'site-kit-for-yandex'); ?></strong></td>
                    <td><?php echo null !== $searchablePagesCount ? esc_html(number_format_i18n($searchablePagesCount)) : '—'; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Исключенных страниц', 'site-kit-for-yandex'); ?></strong></td>
                    <td><?php echo null !== $excludedPagesCount ? esc_html(number_format_i18n($excludedPagesCount)) : '—'; ?>
                    </td>
                </tr>
                <?php if (! empty($siteProblems)) : ?>
                    <?php foreach ($siteProblems as $severity => $count) : ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php
                                    printf(
                                        esc_html__('Проблемы сайта (%s)', 'site-kit-for-yandex'),
                                        esc_html($severity)
                                    );
                                    ?>
                                </strong>
                            </td>
                            <td><?php echo esc_html(number_format_i18n((int) $count)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td><strong><?php echo esc_html__('Проблемы сайта', 'site-kit-for-yandex'); ?></strong></td>
                        <td><?php echo esc_html__('Проблем не найдено.', 'site-kit-for-yandex'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php

    }

    /**
     * Get popular search queries for host.
     *
     * @param string $orderBy             Sort field. TOTAL_SHOWS or TOTAL_CLICKS.
     * @param string $queryIndicator      Query indicator (e.g. TOTAL_SHOWS).
     * @param string $deviceTypeIndicator Device type indicator (e.g. ALL, DESKTOP, MOBILE).
     * @param string $dateFrom            Interval start date/datetime.
     * @param string $dateTo              Interval end date/datetime.
     * @param int    $offset              List offset.
     * @param int    $limit               Records limit (1-500).
     *
     * @return array|\WP_Error
     */
    public static function getPopularQueries(
        $orderBy = 'TOTAL_SHOWS',
        $queryIndicator = 'TOTAL_SHOWS',
        $deviceTypeIndicator = '',
        $dateFrom = '',
        $dateTo = '',
        $offset = 0,
        $limit = 100
    ) {
        $query = [
            'order_by' => $orderBy,
            'query_indicator' => $queryIndicator,
            'offset' => max(0, (int) $offset),
            'limit' => min(500, max(1, (int) $limit)),
        ];

        if (! empty($deviceTypeIndicator)) {
            $query['device_type_indicator'] = $deviceTypeIndicator;
        }

        if (! empty($dateFrom)) {
            $query['date_from'] = $dateFrom;
        }

        if (! empty($dateTo)) {
            $query['date_to'] = $dateTo;
        }

        return self::apiSite('search-queries/popular?'.http_build_query($query));
    }

    /**
     * Get host summary statistics.
     *
     * Docs: https://yandex.ru/dev/webmaster/doc/ru/reference/host-id-summary
     *
     * @return array|\WP_Error
     */
    public static function getSummary()
    {
        $data = get_transient('skfy_webmaster_summary_data');
        if ($data !== false) {
            return $data;
        }
        $data = self::apiSite('summary');
        if (! is_wp_error($data)) {
            set_transient('skfy_webmaster_summary_data', $data, HOUR_IN_SECONDS);
        }

        return $data;
    }


    /**
     * Get site quality index (SQI).
     *
     * @param string $dateFrom Start datetime in API-supported format.
     * @param string $dateTo   End datetime in API-supported format.
     *
     * @return array|\WP_Error
     */
    public static function getSqi($dateFrom = '', $dateTo = '')
    {
        $sqiValue = get_transient('skfy_webmaster_sqi_value');
        if ($sqiValue !== false) {
            return $sqiValue;
        }

        $query = [];

        if (! empty($dateFrom)) {
            $query['date_from'] = $dateFrom;
        } else {
            // Default to 3 days ago if date_from is not provided
            $query['date_from'] = date('Y-m-d', strtotime('-3 days'));
        }

        if (! empty($dateTo)) {
            $query['date_to'] = $dateTo;
        }

        $path = 'sqi-history';

        if (! empty($query)) {
            $path .= '?'.http_build_query($query);
        }

        $data = self::apiSite($path);

        $lastPoint = end($data['points']);
        $sqiValue = $lastPoint['value'] ?? null;

        set_transient('skfy_webmaster_sqi_value', $sqiValue, DAY_IN_SECONDS);

        return $sqiValue;
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
                    '<a href="%1$s">%2$s</a>.',
                    esc_url(admin_url('options-general.php?page=site-kit-for-yandex')),
                    esc_html__('Настройки плагина', 'site-kit-for-yandex')
                );
                ?>
            </p>

            <?php

            // проверить есть ли токен и если нет показать сообщение о том что нужно авторизоваться и получить токен доступа в настройках плагина, а если токен есть, то показать данные Яндекс.Вебмастера (например ИКС и количество страниц в поиске) и ссылку на страницу с этими данными на сайте Яндекс.Вебмастера.
            $accessToken = skfy()->config()->getAccessToken();
            if (! $accessToken) {
                printf(
                    '<p>%s <a href="%s">%s</a>.</p>',
                    esc_html__('Для просмотра данных Яндекс.Вебмастера необходимо авторизоваться и получить токен доступа в', 'site-kit-for-yandex'),
                    esc_url(admin_url('options-general.php?page=site-kit-for-yandex')),
                    esc_html__('настройках плагина', 'site-kit-for-yandex')
                );
            } else {
                do_action('site_kit_for_yandex_webmaster_tools_page_content');
            }

            ?>
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