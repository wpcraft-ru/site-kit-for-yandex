<?php

namespace SiteKitForYandex;

YandexURLInspector::init();

class YandexURLInspector
{

    private static $metrikaCounterId = null;

    public static function init()
    {
        self::$metrikaCounterId = skfy()->config()->get('metrika_counter_id');
        add_action('admin_menu', [self::class, 'addMenu'], 20);

        add_action('site_kit_for_yandex_before_settings_form', [self::class, 'renderActionsForSettingsForm']);

        add_action('site_kit_for_yandex_after_url_input_form_start', [self::class, 'importantPages']);
    }

    //importantPages
    public static function importantPages()
    {
        // https://yandex.ru/dev/webmaster/doc/ru/reference/host-id-important-urls
        $data = YandexWebmaster::apiSite('important-urls');

        echo '<h2>' . esc_html__('Yandex Webmaster: Мониторинг важных страниц', 'site-kit-for-yandex') . '</h2>';

        printf(
            '<p>%1$s <code>%2$s</code></p>',
            esc_html__('Метод API', 'site-kit-for-yandex'),
            esc_html('GET /v4/user/{user-id}/hosts/{host-id}/important-urls')
        );

        if (is_wp_error($data)) {
            printf('<div class="notice notice-warning inline"><p>%s</p></div>', esc_html($data->get_error_message()));
            return;
        }

        $rows = isset($data['urls']) && is_array($data['urls']) ? $data['urls'] : [];

        if (empty($rows)) {
            echo '<p>' . esc_html__('В мониторинге важных страниц пока нет данных.', 'site-kit-for-yandex') . '</p>';
            return;
        }
        ?>
        <table class="widefat striped" style="max-width: 900px; margin-bottom: 24px;">
            <thead>
                <tr>
                    <th><?php echo esc_html__('URL', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('В поиске', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Индексирование', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('HTTP', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Дата обновления', 'site-kit-for-yandex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) : ?>
                <?php
                    $url = isset($row['url']) ? (string) $row['url'] : '';
                    $indexingStatus = isset($row['indexing_status']['status']) ? (string) $row['indexing_status']['status'] : '—';
                    $httpCode = isset($row['indexing_status']['http_code']) ? (string) $row['indexing_status']['http_code'] : '—';
                    $searchable = isset($row['search_status']['searchable'])
                        ? ($row['search_status']['searchable'] ? esc_html__('Да', 'site-kit-for-yandex') : esc_html__('Нет', 'site-kit-for-yandex'))
                        : '—';
                    $updateDate = isset($row['update_date']) ? (string) $row['update_date'] : '—';
                ?>
                <tr>
                    <td>
                        <?php if ($url !== '') : ?>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($url); ?></a>
                        <?php else : ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($searchable); ?></td>
                    <td><code><?php echo esc_html($indexingStatus); ?></code></td>
                    <td><?php echo esc_html($httpCode); ?></td>
                    <td><?php echo esc_html($updateDate); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }


    public static function renderActionsForSettingsForm()
    {
        printf(
            '<p>%1$s <a href="%2$s">%3$s</a>.</p>',
            esc_html__('Проверить URL на наличие в индексе Яндекса можно на странице', 'site-kit-for-yandex'),
            esc_url(admin_url('tools.php?page=skfy-url-inspector')),
            esc_html__('Yandex: URL Inspector', 'site-kit-for-yandex')
        );
    }

    public static function addMenu()
    {
        add_submenu_page(
            'tools.php',
            esc_html__('Yandex: URL Inspector', 'site-kit-for-yandex'),
            esc_html__('Yandex: URL Inspector', 'site-kit-for-yandex'),
            'manage_options',
            'skfy-url-inspector',
            [self::class, 'renderPage']
        );
    }

    public static function renderPage()
    {
        echo '<div class="wrap">';
        echo '<h1>'.esc_html__('Yandex: URL Inspector', 'site-kit-for-yandex').'</h1>';

        self::renderUrlInputForm();

        $rawUrl = isset($_GET['url']) ? wp_unslash($_GET['url']) : '';
        $url = is_string($rawUrl) ? trim($rawUrl) : '';

        if ($url !== '') {
            self::renderAnalysis($url);
        }

        echo '</div>';
    }

    private static function renderUrlInputForm()
    {
        $defaultUrl = home_url('/');
        $currentUrl = isset($_GET['url']) ? esc_url_raw(wp_unslash($_GET['url'])) : $defaultUrl;

        //go to settings link
        printf(
            '<p>%1$s <a href="%2$s">%3$s</a>.</p>',
            esc_html__('Для получения данных по URL необходимо подключить и настроить Яндекс.Вебмастер в разделе', 'site-kit-for-yandex'),
            esc_url(admin_url('options-general.php?page=site-kit-for-yandex')),
            esc_html__('настройки плагина', 'site-kit-for-yandex')
        );

         printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
                esc_html__('Перейти в Яндекс.Вебмастер', 'site-kit-for-yandex'),
                esc_url('https://webmaster.yandex.ru/sites/'),
                esc_html__('Яндекс.Вебмастер', 'site-kit-for-yandex')
        );
        ?>

        <form method="get" action="<?php echo esc_url(admin_url('tools.php')); ?>" style="margin: 16px 0 24px;">
            <input type="hidden" name="page" value="skfy-url-inspector" />
            <label for="skfy-url-input" style="display:block; margin-bottom: 8px;">
                <?php echo esc_html__('Введите URL для анализа', 'site-kit-for-yandex'); ?>
            </label>
            <input id="skfy-url-input" type="url" name="url" class="regular-text" style="min-width: 420px;"
                placeholder="<?php echo esc_attr($defaultUrl); ?>" value="<?php echo esc_attr($currentUrl); ?>" required />
            <?php submit_button(esc_html__('Анализировать URL', 'site-kit-for-yandex'), 'primary', '', false); ?>
        </form>
        <?php

        do_action('site_kit_for_yandex_after_url_input_form_start');
    }

    private static function renderAnalysis($url)
    {
        $url = esc_url_raw($url);

        if (! wp_http_validate_url($url)) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('Некорректный URL. Укажите полный адрес, начиная с http:// или https://', 'site-kit-for-yandex')
            );
            return;
        }

        $parts = wp_parse_url($url);
        if (! is_array($parts)) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('Не удалось разобрать URL.', 'site-kit-for-yandex')
            );
            return;
        }

        $isInternal = self::isInternalUrl($url);

        echo '<h2>'.esc_html__('Результат анализа URL', 'site-kit-for-yandex').'</h2>';
        echo '<table class="widefat striped" style="max-width: 900px;">';
        echo '<tbody>';

        printf(
            '<tr><td><strong>%1$s</strong></td><td><a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></td></tr>',
            esc_html__('URL', 'site-kit-for-yandex'),
            esc_url($url),
            esc_html($url)
        );

        printf(
            '<tr><td><strong>%1$s</strong></td><td>%2$s</td></tr>',
            esc_html__('Схема', 'site-kit-for-yandex'),
            esc_html(isset($parts['scheme']) ? $parts['scheme'] : '—')
        );

        printf(
            '<tr><td><strong>%1$s</strong></td><td>%2$s</td></tr>',
            esc_html__('Хост', 'site-kit-for-yandex'),
            esc_html(isset($parts['host']) ? $parts['host'] : '—')
        );

        printf(
            '<tr><td><strong>%1$s</strong></td><td>%2$s</td></tr>',
            esc_html__('Путь', 'site-kit-for-yandex'),
            esc_html(isset($parts['path']) ? $parts['path'] : '/')
        );

        printf(
            '<tr><td><strong>%1$s</strong></td><td>%2$s</td></tr>',
            esc_html__('Query', 'site-kit-for-yandex'),
            esc_html(isset($parts['query']) ? $parts['query'] : '—')
        );

        printf(
            '<tr><td><strong>%1$s</strong></td><td>%2$s</td></tr>',
            esc_html__('URL относится к текущему сайту', 'site-kit-for-yandex'),
            $isInternal ? esc_html__('Да', 'site-kit-for-yandex') : esc_html__('Нет', 'site-kit-for-yandex')
        );

        echo '</tbody>';
        echo '</table>';

        if ($isInternal) {
            // self::renderQueryAnalytics($url);
            self::renderTrafficAndSources($url);
            self::renderSearchPhrases($url);
        }
    }

    public static function getTrafficAndSources($url)
    {
        if (empty(self::$metrikaCounterId)) {
            return new \WP_Error('no_counter_id', __('Metrika counter ID is not set.', 'site-kit-for-yandex'));
        }

        $query = [
            'id'         => (string) self::$metrikaCounterId,
            'dimensions' => 'ym:s:trafficSourceName',
            'metrics'    => 'ym:s:visits,ym:s:users,ym:s:bounceRate,ym:s:avgVisitDurationSeconds,ym:s:pageDepth',
            'filters'    => "ym:s:startURL=='" . $url . "'",
            'sort'       => '-ym:s:visits',
            'date1'      => '28daysAgo',
            'date2'      => 'today',
        ];

        return YandexMetrika::api('stat/v1/data?' . http_build_query($query));
    }

    public static function getSearchPhrases($url)
    {
        if (empty(self::$metrikaCounterId)) {
            return new \WP_Error('no_counter_id', __('Metrika counter ID is not set.', 'site-kit-for-yandex'));
        }

        $query = [
            'id'         => (string) self::$metrikaCounterId,
            'dimensions' => 'ym:s:searchPhrase,ym:s:searchEngineName',
            'metrics'    => 'ym:s:visits,ym:s:users',
            'filters'    => "ym:s:startURL=='" . $url . "'",
            'sort'       => '-ym:s:visits',
            'limit'      => 20,
            'date1'      => '28daysAgo',
            'date2'      => 'today',
        ];

        return YandexMetrika::api('stat/v1/data?' . http_build_query($query));
    }

    public static function getQueryAnalytics($url, $limit = 20)
    {
        $body = [
            'text_indicator' => 'URL',
            'filters'        => [
                'text_filters' => [
                    [
                        'text_indicator' => 'URL',
                        'operation'      => 'TEXT_EQUAL',
                        'value'          => $url,
                    ],
                ],
            ],
            'limit'          => $limit,
        ];

        return YandexWebmaster::apiSite('query-analytics/list', 'POST', $body);
    }

    private static function renderTrafficAndSources($url)
    {
        $data = self::getTrafficAndSources($url);

        echo '<h2>' . esc_html__('Трафик и источники (за 28 дней)', 'site-kit-for-yandex') . '</h2>';

        if (is_wp_error($data)) {
            printf('<div class="notice notice-warning inline"><p>%s</p></div>', esc_html($data->get_error_message()));
            return;
        }

        $rows = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];
        $totals = isset($data['totals']) && is_array($data['totals']) ? $data['totals'] : [];

        if (empty($rows)) {
            echo '<p>' . esc_html__('Нет данных.', 'site-kit-for-yandex') . '</p>';
            return;
        }
        ?>
        <table class="widefat striped" style="max-width: 900px; margin-bottom: 24px;">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Источник', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Визиты', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Посетители', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Отказы, %', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Время, с', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Глубина', 'site-kit-for-yandex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($totals)) : ?>
                <tr>
                    <td><strong><?php echo esc_html__('Итого', 'site-kit-for-yandex'); ?></strong></td>
                    <?php foreach ($totals as $val) : ?>
                        <td><strong><?php echo esc_html(is_numeric($val) ? round($val, 1) : $val); ?></strong></td>
                    <?php endforeach; ?>
                </tr>
                <?php endif; ?>
                <?php foreach ($rows as $row) : ?>
                <tr>
                    <td><?php echo esc_html(isset($row['dimensions'][0]['name']) ? $row['dimensions'][0]['name'] : '—'); ?></td>
                    <?php foreach ($row['metrics'] as $val) : ?>
                        <td><?php echo esc_html(is_numeric($val) ? round($val, 1) : $val); ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private static function renderSearchPhrases($url)
    {
        $data = self::getSearchPhrases($url);

        echo '<h2>' . esc_html__('Поисковые запросы (за 28 дней)', 'site-kit-for-yandex') . '</h2>';

        if (is_wp_error($data)) {
            printf('<div class="notice notice-warning inline"><p>%s</p></div>', esc_html($data->get_error_message()));
            return;
        }

        $rows = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];

        if (empty($rows)) {
            echo '<p>' . esc_html__('Нет данных.', 'site-kit-for-yandex') . '</p>';
            return;
        }
        ?>
        <table class="widefat striped" style="max-width: 900px; margin-bottom: 24px;">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Запрос', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Поисковик', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Визиты', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Посетители', 'site-kit-for-yandex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) : ?>
                <tr>
                    <td><?php echo esc_html(isset($row['dimensions'][0]['name']) ? $row['dimensions'][0]['name'] : esc_html__('(не определено)', 'site-kit-for-yandex')); ?></td>
                    <td><?php echo esc_html(isset($row['dimensions'][1]['name']) ? $row['dimensions'][1]['name'] : '—'); ?></td>
                    <td><?php echo esc_html(isset($row['metrics'][0]) ? $row['metrics'][0] : 0); ?></td>
                    <td><?php echo esc_html(isset($row['metrics'][1]) ? $row['metrics'][1] : 0); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private static function renderQueryAnalytics($url)
    {
        $data = self::getQueryAnalytics($url);

        echo '<h2>' . esc_html__('Поисковые запросы из Вебмастера', 'site-kit-for-yandex') . '</h2>';

        if (is_wp_error($data)) {
            printf('<div><p>%s</p></div>', esc_html($data->get_error_message()));
            return;
        }

        $queries = isset($data['text_indicator_to_statistics']) && is_array($data['text_indicator_to_statistics'])
            ? $data['text_indicator_to_statistics']
            : [];

        if (empty($queries)) {
            echo '<p>' . esc_html__('Нет данных.', 'site-kit-for-yandex') . '</p>';
            return;
        }
        ?>
        <table class="widefat striped" style="max-width: 900px; margin-bottom: 24px;">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Запрос', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Показы', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Клики', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('CTR, %', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Ср. позиция', 'site-kit-for-yandex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($queries as $item) : ?>
                <?php
                    $query   = isset($item['text_indicator']['value']) ? $item['text_indicator']['value'] : '—';
                    $stats   = isset($item['statistics']) && is_array($item['statistics']) ? $item['statistics'] : [];
                    $statMap = [];
                    foreach ($stats as $stat) {
                        $statMap[$stat['field']] = $stat['value'];
                    }
                    $shows    = isset($statMap['SHOWS']) ? (int) $statMap['SHOWS'] : 0;
                    $clicks   = isset($statMap['CLICKS']) ? (int) $statMap['CLICKS'] : 0;
                    $ctr      = isset($statMap['CTR']) ? round((float) $statMap['CTR'] * 100, 2) : 0;
                    $position = isset($statMap['AVERAGE_SHOW_POSITION']) ? round((float) $statMap['AVERAGE_SHOW_POSITION'], 1) : '—';
                ?>
                <tr>
                    <td><?php echo esc_html($query); ?></td>
                    <td><?php echo esc_html($shows); ?></td>
                    <td><?php echo esc_html($clicks); ?></td>
                    <td><?php echo esc_html($ctr); ?></td>
                    <td><?php echo esc_html($position); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

   

    private static function isInternalUrl($url)
    {
        $siteHost = wp_parse_url(home_url('/'), PHP_URL_HOST);
        $urlHost = wp_parse_url($url, PHP_URL_HOST);

        if (! is_string($siteHost) || ! is_string($urlHost)) {
            return false;
        }

        return strtolower($siteHost) === strtolower($urlHost);
    }
}