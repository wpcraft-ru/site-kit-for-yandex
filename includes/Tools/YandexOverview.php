<?php

namespace SiteKitForYandex;

YandexOverview::init();

class YandexOverview
{

    private static $metrikaCounterId = null;

    public static function init()
    {
        add_action('admin_menu', [self::class, 'addMenu'], 20);

        self::$metrikaCounterId = skfy()->config()->get('metrika_counter_id');
    }

    // addMenu
    public static function addMenu()
    {
        add_submenu_page(
            'tools.php',
            esc_html__('Yandex: Overview', 'site-kit-for-yandex'),
            esc_html__('Yandex: Overview', 'site-kit-for-yandex'),
            'manage_options',
            'skfy-overview',
            [self::class, 'renderPage']
        );
    }

    // renderPage
    public static function renderPage()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Yandex: Overview', 'site-kit-for-yandex'); ?></h1>

            <p>
                <?php
                printf(
                    '<a href="%1$s">%2$s</a>.',
                    esc_url(admin_url('options-general.php?page=site-kit-for-yandex')),
                    esc_html__('Настройки плагина', 'site-kit-for-yandex')
                );
                ?>
            </p>

            <p>
                <?php
                $metrikaUrl = 'https://metrika.yandex.ru';
                if (! empty(self::$metrikaCounterId)) {
                    $metrikaUrl = 'https://metrika.yandex.ru/overview?id='.(int) self::$metrikaCounterId;
                }

                printf(
                    '%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.',
                    esc_html__('Детали по Метрике:', 'site-kit-for-yandex'),
                    esc_url($metrikaUrl),
                    esc_html__('metrika.yandex.ru', 'site-kit-for-yandex')
                );
                ?>
            </p>

            <p>
                <?php
                printf(
                    '%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.',
                    esc_html__('Детали по ВебМастеру:', 'site-kit-for-yandex'),
                    esc_url('https://webmaster.yandex.ru/'),
                    esc_html__('webmaster.yandex.ru', 'site-kit-for-yandex')
                );
                ?>
            </p>

            <?php
            $accessToken = skfy()->config()->getAccessToken();
            if (! $accessToken) {
                printf(
                    '<p>%s <a href="%s">%s</a>.</p>',
                    esc_html__('Для просмотра данных Яндекса необходимо авторизоваться и получить токен доступа в', 'site-kit-for-yandex'),
                    esc_url(admin_url('options-general.php?page=site-kit-for-yandex')),
                    esc_html__('настройках плагина', 'site-kit-for-yandex')
                );

                echo '</div>';
                return;
            }

            self::renderSummary();

            if (empty(self::$metrikaCounterId)) {
                printf(
                    '<p>%s</p>',
                    esc_html__('ID счётчика Яндекс.Метрики не установлен. Пожалуйста, укажите его в настройках плагина.', 'site-kit-for-yandex')
                );

                echo '</div>';
                return;
            }

            echo '<hr>';
            self::renderTop10PagesForLast28Days();
            self::renderTop10KeyPhraseForLast28Days();
            ?>
        </div>
        <?php
    }

    public static function renderTop10KeyPhraseForLast28Days()
    {
        printf('<h2>%s</h2>', esc_html__('Топ 10 ключевых фраз за последние 28 дней', 'site-kit-for-yandex'));

        $items = self::getTop10KeyPhraseForLast28Days();

        if (is_wp_error($items)) {
            echo '<p>'.esc_html__('Ошибка получения данных метрики: ', 'site-kit-for-yandex').esc_html($items->get_error_message()).'</p>';
            return;
        }

        ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Search phrase', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Visits', 'site-kit-for-yandex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item['phrase']); ?></td>
                        <td><?php echo esc_html($item['visits']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    public static function getTop10KeyPhraseForLast28Days()
    {
        $cachedData = get_transient('skfy_metrika_top_10_keyphrase_28_days');
        if (! empty($cachedData)) {
            return $cachedData;
        }

        if (empty(self::$metrikaCounterId)) {
            return new \WP_Error('no_counter_id', __('Metrika counter ID is not set.', 'site-kit-for-yandex'));
        }

        $query = [
            'id' => (string) self::$metrikaCounterId,
            'dimensions' => 'ym:s:searchPhrase',
            'metrics' => 'ym:s:visits',
            'sort' => '-ym:s:visits',
            'limit' => 10,
            'date1' => '28daysAgo',
            'date2' => 'today',
        ];

        $data = self::metrikaApi('stat/v1/data?'.http_build_query($query));
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
        echo '<h2>'.esc_html__('Топ 10 страниц за последние 28 дней', 'site-kit-for-yandex').'</h2>';

        $items = self::getTop10PagesForLast28Days();

        if (is_wp_error($items)) {
            echo '<p>'.esc_html__('Ошибка получения данных метрики: ', 'site-kit-for-yandex').esc_html($items->get_error_message()).'</p>';
            return;
        }

        ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Page URL', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Visits', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Title', 'site-kit-for-yandex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item) : ?>
                    <tr>
                        <td><a href="<?php echo esc_url($item['url']); ?>" target="_blank"
                                rel="noopener noreferrer"><?php echo esc_html($item['url']); ?></a></td>
                        <td><?php echo esc_html($item['visits']); ?></td>
                        <td><?php echo esc_html($item['title']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    public static function getTop10PagesForLast28Days()
    {
        $cachedData = get_transient('skfy_metrika_top_10_pages_28_days');
        if (! empty($cachedData)) {
            return $cachedData;
        }

        if (empty(self::$metrikaCounterId)) {
            return new \WP_Error('no_counter_id', __('Metrika counter ID is not set.', 'site-kit-for-yandex'));
        }

        $query = [
            'id' => (string) self::$metrikaCounterId,
            'dimensions' => 'ym:s:startURL',
            'metrics' => 'ym:s:visits',
            'sort' => '-ym:s:visits',
            'limit' => 10,
            'date1' => '28daysAgo',
            'date2' => 'today',
        ];

        $data = self::metrikaApi('stat/v1/data?'.http_build_query($query));
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

    public static function metrikaApi($path, $method = 'GET', $data = [])
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

        return new \WP_Error('api_error', sprintf(__('API request failed with status code %d: %s', 'site-kit-for-yandex'), $code, $body));
    }

    public static function renderSqi()
    {
        ?>
        <h2>Индекс качества сайта (ИКС)</h2>
        <?php

        $value = self::getSqi();
        printf('<p>ИКС: <strong>%s</strong></p>', esc_html($value));
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

        return self::webmasterApiSite('search-queries/popular?'.http_build_query($query));
    }

    public static function getSummary()
    {
        $data = get_transient('skfy_webmaster_summary_data');
        if ($data !== false) {
            return $data;
        }
        $data = self::webmasterApiSite('summary');
        if (! is_wp_error($data)) {
            set_transient('skfy_webmaster_summary_data', $data, HOUR_IN_SECONDS);
        }

        return $data;
    }

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
            $query['date_from'] = date('Y-m-d', strtotime('-3 days'));
        }

        if (! empty($dateTo)) {
            $query['date_to'] = $dateTo;
        }

        $path = 'sqi-history';

        if (! empty($query)) {
            $path .= '?'.http_build_query($query);
        }

        $data = self::webmasterApiSite($path);
        if (is_wp_error($data)) {
            return $data;
        }

        $lastPoint = end($data['points']);
        $sqiValue = $lastPoint['value'] ?? null;

        set_transient('skfy_webmaster_sqi_value', $sqiValue, DAY_IN_SECONDS);

        return $sqiValue;
    }

    public static function getSiteInfo()
    {
        $data = get_transient('skfy_webmaster_site_info');
        if ($data !== false) {
            return $data;
        }

        $userId = self::webmasterApi('user')['user_id'] ?? null;
        if (! $userId) {
            return null;
        }

        $hosts = self::webmasterApi('user/'.$userId.'/hosts')['hosts'] ?? [];
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

    public static function webmasterApiSite($path, $method = 'GET', $data = [])
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

        return self::webmasterApi($apiPath, $method, $data);
    }


    public static function webmasterApi($path, $method = 'GET', $data = [])
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
}