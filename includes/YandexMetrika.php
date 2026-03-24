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

    private static $counterId = null;

    public static function init()
    {
        add_action('admin_init', [self::class, 'registerSettingsSection']);
        add_action('admin_menu', [self::class, 'addToolsPage']);

        self::$counterId = skfy()->config()->get('metrika_counter_id');
        if (empty(self::$counterId)) {
            add_action('site_kit_for_yandex_after_tools_page', function () {
                printf('<p>%s</p>', esc_html__('ID счётчика Яндекс.Метрики не установлен. Пожалуйста, укажите его в настройках плагина.', 'site-kit-for-yandex'));
            });
        } else {
            add_action('site_kit_for_yandex_after_tools_page', [self::class, 'renderTop10PagesForLast28Days']);
            add_action('site_kit_for_yandex_after_tools_page', [self::class, 'renderTop10KeyPhraseForLast28Days']);
        }

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

        $counterId = skfy()->config()->get('metrika_counter_id');

        if (empty($counterId)) {
            return new \WP_Error('no_counter_id', __('Metrika counter ID is not set.', 'site-kit-for-yandex'));
        }

        $query = [
            'id' => (string) $counterId,
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


    /**
     * Get the top 10 pages for the last 28 days.
     * 
     * @return array|\WP_Error Array of data with url, visits, and title, or WP_Error on failure.
     */
    public static function getTop10PagesForLast28Days()
    {
        $cachedData = get_transient('skfy_metrika_top_10_pages_28_days');
        if (! empty($cachedData)) {
            return $cachedData;
        }

        $counterId = skfy()->config()->get('metrika_counter_id');

        if (empty($counterId)) {
            return new \WP_Error('no_counter_id', __('Metrika counter ID is not set.', 'site-kit-for-yandex'));
        }


        $query = [
            'id' => (string) $counterId,
            'dimensions' => 'ym:s:startURL',
            'metrics' => 'ym:s:visits',
            'sort' => '-ym:s:visits',
            'limit' => 10,
            'date1' => '28daysAgo',
            'date2' => 'today',
        ];

        /**
         * example data
         * array(13) { ["query"]=> array(24) { ["ids"]=> array(1) { [0]=> int(27160562) } ["dimensions"]=> array(1) { [0]=> string(13) "ym:s:startURL" } ["metrics"]=> array(1) { [0]=> string(11) "ym:s:visits" } ["sort"]=> array(1) { [0]=> string(12) "-ym:s:visits" } ["date1"]=> string(10) "2026-02-24" ["date2"]=> string(10) "2026-03-24" ["limit"]=> int(10) ["offset"]=> int(1) ["group"]=> string(4) "Week" ["benchmarks_version"]=> string(13) "1773619200000" ["auto_group_size"]=> string(1) "1" ["exp_bucket_size"]=> string(3) "100" ["attr_name"]=> string(0) "" ["funnel_window"]=> string(0) "" ["benchmarks_quantile"]=> string(2) "50" ["time_interval"]=> string(7) "1SECOND" ["intervals"]=> string(1) "1" ["quantile"]=> string(2) "50" ["offline_window"]=> string(2) "21" ["attribution"]=> string(8) "LastSign" ["currency"]=> string(3) "RUB" ["benchmarks_attribution"]=> string(9) "Automatic" ["adfox_event_id"]=> string(1) "0" ["funnel_pattern"]=> string(0) "" } ["data"]=> array(10) { [0]=> array(2) { ["dimensions"]=> array(1) { [0]=> array(2) { ["name"]=> string(57) "https://wpcraft.ru/club-wordpress-woocommerce?ref=wp-kama" ["favicon"]=> string(10) "wpcraft.ru" } } ["metrics"]=> array(1) { [0]=> float(380) } } [1]=> array(2) { ["dimensions"]=> array(1) { [0]=> array(2) { ["name"]=> string(19) "https://wpcraft.ru/" ["favicon"]=> string(10) "wpcraft.ru" } } ["metrics"]=> array(1) { [0]=> float(311) } } [2]=> array(2) { ["dimensions"]=> array(1) { [0]=> array(2) { ["name"]=> string(59) "https://wpcraft.ru/blog/website-page-builders-for-wordpress" ["favicon"]=> string(10) "wpcraft.ru" } } ["metrics"]=> array(1) { [0]=> float(119) } } [3]=> array(2) { ["dimensions"]=> array(1) { [0]=> array(2) { ["name"]=> string(35) "https://wpcraft.ru/wordpress/themes" ["favicon"]=> string(10) "wpcraft.ru" } } ["metrics"]=> array(1) { [0]=> float(118) } } [4]=> array(2) { ["dimensions"]=> array(1) { [0]=> array(2) { ["name"]=> string(24) "https://wpcraft.ru/wooms" ["favicon"]=> string(10) "wpcraft.ru" } } ["metrics"]=> array(1) { [0]=> float(99) } } [5]=> array(2) { ["dimensions"]=> array(1) { [0]=> array(2) { ["name"]=> string(43) "https://wpcraft.ru/blog/local-dev-wordpress" ["favicon"]=> string(10) "wpcraft.ru" } } ["metrics"]=> array(1) { [0]=> float(98) } } [6]=> array(2) { ["dimensions"]=> array(1) { [0]=> array(2) { ["name"]=> string(37) "https://wpcraft.ru/blog/pwa-wordpress" ["favicon"]=> string(10) "wpcraft.ru" } } ["metrics"]=> array(1) { [0]=> float(81) } } [7]=> array(2) { ["dimensions"]=> array(1) { [0]=> array(2) { ["name"]=> string(54) "https://wpcraft.ru/blog/examples-wordpress-woocommerce" ["favicon"]=> string(10) "wpcraft.ru" } } ["metrics"]=> array(1) { [0]=> float(67) } } [8]=> array(2) { ["dimensions"]=> array(1) { [0]=> array(2) { ["name"]=> string(49) "https://wpcraft.ru/blog/agent-skills-ai-wordpress" ["favicon"]=> string(10) "wpcraft.ru" } } ["metrics"]=> array(1) { [0]=> float(54) } } [9]=> array(2) { ["dimensions"]=> array(1) { [0]=> array(2) { ["name"]=> string(73) "https://wpcraft.ru/blog/nastrojka-dostavki-v-magazine-na-baze-woocommerce" ["favicon"]=> string(10) "wpcraft.ru" } } ["metrics"]=> array(1) { [0]=> float(50) } } } ["total_rows"]=> int(788) ["total_rows_rounded"]=> bool(false) ["sampled"]=> bool(false) ["contains_sensitive_data"]=> bool(false) ["sample_share"]=> float(1) ["sample_size"]=> int(4176) ["sample_space"]=> int(4176) ["data_lag"]=> int(0) ["totals"]=> array(1) { [0]=> float(4135) } ["min"]=> array(1) { [0]=> float(50) } ["max"]=> array(1) { [0]=> float(380) } }
         */
        $data = self::api('stat/v1/data?'.http_build_query($query));
        if (is_wp_error($data)) {
            return $data;
        }

        //prepare data - extract page URL and visits count
        $preparedData = [];
        foreach ($data['data'] as $item) {
            $url = $item['dimensions'][0]['name'];
            $visits = $item['metrics'][0];
            $preparedData[] = [
                'url' => $url,
                'visits' => $visits,
            ];
        }

        //add post or page title from wordpress api
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

    //api
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
            "timeout" => 15,
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
     * Add Yandex Metrika page under Tools menu.
     *
     * @return void
     */
    public static function addToolsPage()
    {
        add_management_page(
            __('Yandex Metrika', 'site-kit-for-yandex'),
            __('Yandex Metrika', 'site-kit-for-yandex'),
            'manage_options',
            'site-kit-for-yandex-metrika',
            [self::class, 'renderToolsPage']
        );
    }

    /**
     * Render Yandex Metrika tools page.
     *
     * @return void
     */
    public static function renderToolsPage()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Yandex Metrika', 'site-kit-for-yandex'); ?></h1>
            <p>
                <?php
                $url = "https://metrika.yandex.ru";

                //if counter ID is set, add direct link to counter dashboard
                $counterId = skfy()->config()->get('metrika_counter_id');
                if (! empty($counterId)) {
                    $url = "https://metrika.yandex.ru/overview?id={$counterId}";
                }
                printf(
                    '%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.',
                    esc_html__('Просмотр информации о метрике:', 'site-kit-for-yandex'),
                    $url,
                    esc_html__('metrika.yandex.ru', 'site-kit-for-yandex')
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

            <?php
            $counterId = skfy()->config()->get('metrika_counter_id');
            if (empty($counterId)) {
                printf(
                    '<p>%1$s</p>',
                    esc_html__('ID счётчика Яндекс.Метрики не установлен. Пожалуйста, укажите его в настройках плагина.', 'site-kit-for-yandex')
                );
            } else {
                ?>
                <hr>
                <div>
                    <?php do_action('site_kit_for_yandex_after_tools_page'); ?>
                </div>
                <?php
            }

            ?>
        </div>
        <?php
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
            esc_url(admin_url('tools.php?page=site-kit-for-yandex-metrika')),
            esc_html__('Yandex Metrika Tools', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Просмотр метрики осуществляется по ссылке: ', 'site-kit-for-yandex'),
            esc_url('https://metrika.yandex.ru/'),
            "metrika.yandex.ru"
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