<?php

namespace SiteKitForYandex;

YandexURLInspector::init();

class YandexURLInspector
{

    private static $siteScheme = '';
    private static $siteHost = '';
    private static $sitePort = 80;

    public static function init()
    {
        $homeUrl = home_url('/');
        self::$siteScheme = (string) wp_parse_url($homeUrl, PHP_URL_SCHEME);
        self::$siteHost   = (string) wp_parse_url($homeUrl, PHP_URL_HOST);
        self::$sitePort   = (int) wp_parse_url($homeUrl, PHP_URL_PORT);
        if (0 === self::$sitePort) {
            self::$sitePort = 'https' === strtolower(self::$siteScheme) ? 443 : 80;
        }
        add_action('admin_menu', [self::class, 'addMenu'], 20);

        add_action('site_kit_for_yandex_before_settings_form', [self::class, 'renderActionsForSettingsForm']);

        add_action('site_kit_for_yandex_after_url_input_form_start', [self::class, 'importantPages']);
    }

    //importantPages
    public static function importantPages()
    {
        // https://yandex.ru/dev/webmaster/doc/ru/reference/host-id-important-urls
        $data = YandexWebmaster::apiSite('important-urls');

        echo '<h2>'.esc_html__('Yandex Webmaster: Important Pages Monitoring', 'site-kit-for-yandex').'</h2>';
        $webmasterSiteId = sprintf('%s:%s:%d', self::$siteScheme, self::$siteHost, self::$sitePort);
        $webmasterTrackerUrl = 'https://webmaster.yandex.ru/site/'.$webmasterSiteId.'/indexing/url-tracker/';

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('Important pages monitoring in Yandex.Webmaster', 'site-kit-for-yandex'),
            esc_url($webmasterTrackerUrl),
            esc_html__('URL Tracker', 'site-kit-for-yandex')
        );

        printf(
            '<details><summary>%1$s</summary><p><code>%2$s</code></p></details>',
            esc_html__('API Method', 'site-kit-for-yandex'),
            esc_html('GET /v4/user/{user-id}/hosts/{host-id}/important-urls')
        );

        if (is_wp_error($data)) {
            printf('<div class="notice notice-warning inline"><p>%s</p></div>', esc_html($data->get_error_message()));
            return;
        }

        $rows = isset($data['urls']) && is_array($data['urls']) ? $data['urls'] : [];

        if (empty($rows)) {
            echo '<p>'.esc_html__('No data in important pages monitoring yet.', 'site-kit-for-yandex').'</p>';
            return;
        }
        ?>
        <table class="widefat striped" style="max-width: 900px; margin-bottom: 24px;">
            <thead>
                <tr>
                    <th><?php echo esc_html__('URL', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('In Search', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Indexing', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('HTTP', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Update Date', 'site-kit-for-yandex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) : ?>
                    <?php
                    $url = isset($row['url']) ? (string) $row['url'] : '';
                    $indexingStatus = isset($row['indexing_status']['status']) ? (string) $row['indexing_status']['status'] : '—';
                    $httpCode = isset($row['indexing_status']['http_code']) ? (string) $row['indexing_status']['http_code'] : '—';
                    $searchable = isset($row['search_status']['searchable'])
                        ? ($row['search_status']['searchable'] ? esc_html__('Yes', 'site-kit-for-yandex') : esc_html__('No', 'site-kit-for-yandex'))
                        : '—';
                    $updateDate = isset($row['update_date']) ? substr((string) $row['update_date'], 0, 10) : '—';
                    ?>
                    <tr>
                        <td>
                            <?php if ($url !== '') : ?>
                                <a href="<?php echo esc_url(admin_url('tools.php?page=skfy-url-inspector&url=' . rawurlencode($url))); ?>"><?php echo esc_html($url); ?></a>
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
            esc_html__('You can check whether a URL is in the Yandex index on the page', 'site-kit-for-yandex'),
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

        $url = self::getUrl();

        if ($url !== '') {
            self::renderAnalysis($url);
        }

        echo '</div>';
    }

    /**
     * Gets and sanitizes the URL from the $_GET parameter.
     *
     * @since 1.0.0
     * @return string Processed URL or an empty string.
     */
    private static function getUrl()
    {
        $rawUrl = isset($_GET['url']) ? $_GET['url'] : '';
        return is_string($rawUrl) ? esc_url_raw(trim($rawUrl)) : '';
    }

    private static function renderUrlInputForm()
    {
        $defaultUrl = home_url('/');
        $url = self::getUrl();
        $currentUrl = empty($url) ? $defaultUrl : $url;
        
        if(!empty($url)){
            //return to main page
            printf(
                '<p><a href="%s">%s</a></p>',
                esc_url(admin_url('tools.php?page=skfy-url-inspector')),
                esc_html__('<- Back to URL input', 'site-kit-for-yandex')
            );
        }

        //go to settings link
        printf(
            '<p>%1$s <a href="%2$s">%3$s</a>.</p>',
            esc_html__('To get URL data, you need to connect and configure Yandex.Webmaster in the', 'site-kit-for-yandex'),
            esc_url(admin_url('options-general.php?page=site-kit-for-yandex')),
            esc_html__('plugin settings', 'site-kit-for-yandex')
        );

        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('Go to Yandex.Webmaster', 'site-kit-for-yandex'),
            esc_url('https://webmaster.yandex.ru/sites/'),
            esc_html__('Yandex.Webmaster', 'site-kit-for-yandex')
        );


        ?>

        <form method="get" action="<?php echo esc_url(admin_url('tools.php')); ?>" style="margin: 16px 0 24px;">
            <input type="hidden" name="page" value="skfy-url-inspector" />
            <label for="skfy-url-input" style="display:block; margin-bottom: 8px;">
                <?php echo esc_html__('Enter URL for analysis', 'site-kit-for-yandex'); ?>
            </label>
            <input id="skfy-url-input" type="url" name="url" class="regular-text" style="min-width: 420px;"
                placeholder="<?php echo esc_attr($defaultUrl); ?>" value="<?php echo esc_attr($currentUrl); ?>" required />
            <?php submit_button(esc_html__('Analyze URL', 'site-kit-for-yandex'), 'primary', '', false); ?>
        </form>
        <?php

        if ('' === $url) {
            do_action('site_kit_for_yandex_after_url_input_form_start');
        }
    }

    private static function renderAnalysis($url)
    {
        $url = esc_url_raw($url);

        if (! wp_http_validate_url($url)) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('Invalid URL. Please specify the full address starting with http:// or https://', 'site-kit-for-yandex')
            );
            return;
        }

        $parts = wp_parse_url($url);
        if (! is_array($parts)) {
            printf(
                '<div class="notice notice-error"><p>%s</p></div>',
                esc_html__('Failed to parse URL.', 'site-kit-for-yandex')
            );
            return;
        }

        $isInternal = self::isInternalUrl($url);

        echo '<h2>'.esc_html__('URL Analysis Result', 'site-kit-for-yandex').'</h2>';
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
            esc_html__('Scheme', 'site-kit-for-yandex'),
            esc_html(isset($parts['scheme']) ? $parts['scheme'] : '—')
        );

        printf(
            '<tr><td><strong>%1$s</strong></td><td>%2$s</td></tr>',
            esc_html__('Host', 'site-kit-for-yandex'),
            esc_html(isset($parts['host']) ? $parts['host'] : '—')
        );

        printf(
            '<tr><td><strong>%1$s</strong></td><td>%2$s</td></tr>',
            esc_html__('Path', 'site-kit-for-yandex'),
            esc_html(isset($parts['path']) ? $parts['path'] : '/')
        );

        printf(
            '<tr><td><strong>%1$s</strong></td><td>%2$s</td></tr>',
            esc_html__('Query', 'site-kit-for-yandex'),
            esc_html(isset($parts['query']) ? $parts['query'] : '—')
        );

        printf(
            '<tr><td><strong>%1$s</strong></td><td>%2$s</td></tr>',
            esc_html__('URL belongs to the current site', 'site-kit-for-yandex'),
            $isInternal ? esc_html__('Yes', 'site-kit-for-yandex') : esc_html__('No', 'site-kit-for-yandex')
        );

        echo '</tbody>';
        echo '</table>';

        if ($isInternal) {
            // self::renderQueryAnalytics($url);
            self::renderTrafficAndSources($url);
            // self::renderSearchPhrases($url);
        }
    }

    private static function renderTrafficAndSources($url)
    {
        $data = YandexMetrika::getTrafficAndSources($url);

        echo '<h2>'.esc_html__('Traffic and Sources (last 28 days)', 'site-kit-for-yandex').'</h2>';

        if (is_wp_error($data)) {
            printf('<div class="notice notice-warning inline"><p>%s</p></div>', esc_html($data->get_error_message()));
            return;
        }

        $rows = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];
        $totals = isset($data['totals']) && is_array($data['totals']) ? $data['totals'] : [];

        if (empty($rows)) {
            echo '<p>'.esc_html__('No data.', 'site-kit-for-yandex').'</p>';
            return;
        }
        ?>
        <table class="widefat striped" style="max-width: 900px; margin-bottom: 24px;">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Source', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Visits', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Visitors', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Bounce Rate, %', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Time, s', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Depth', 'site-kit-for-yandex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (! empty($totals)) : ?>
                    <tr>
                        <td><strong><?php echo esc_html__('Total', 'site-kit-for-yandex'); ?></strong></td>
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

    /**
     * Renders a table of search phrases users arrived from to the given URL.
     * 
     * @todo not ready yet — need to figure out how to retrieve this data via REST API
     */
    private static function renderSearchPhrases($url)
    {
        $data = YandexMetrika::getSearchPhrases($url);

        echo '<h2>'.esc_html__('Search Queries (last 28 days)', 'site-kit-for-yandex').'</h2>';

        if (is_wp_error($data)) {
            printf('<div class="notice notice-warning inline"><p>%s</p></div>', esc_html($data->get_error_message()));
            return;
        }

        $rows = isset($data['data']) && is_array($data['data']) ? $data['data'] : [];

        if (empty($rows)) {
            echo '<p>'.esc_html__('No data.', 'site-kit-for-yandex').'</p>';
            return;
        }
        ?>
        <table class="widefat striped" style="max-width: 900px; margin-bottom: 24px;">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Query', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Search Engine', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Visits', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Visitors', 'site-kit-for-yandex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html(isset($row['dimensions'][0]['name']) ? $row['dimensions'][0]['name'] : esc_html__('(undefined)', 'site-kit-for-yandex')); ?>
                        </td>
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
        $data = YandexWebmaster::getQueryAnalytics($url);

        echo '<h2>'.esc_html__('Search Queries from Webmaster', 'site-kit-for-yandex').'</h2>';

        if (is_wp_error($data)) {
            printf('<div><p>%s</p></div>', esc_html($data->get_error_message()));
            return;
        }

        $queries = isset($data['text_indicator_to_statistics']) && is_array($data['text_indicator_to_statistics'])
            ? $data['text_indicator_to_statistics']
            : [];

        if (empty($queries)) {
            echo '<p>'.esc_html__('No data.', 'site-kit-for-yandex').'</p>';
            return;
        }
        ?>
        <table class="widefat striped" style="max-width: 900px; margin-bottom: 24px;">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Query', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Impressions', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Clicks', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('CTR, %', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Avg. Position', 'site-kit-for-yandex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($queries as $item) : ?>
                    <?php
                    $query = isset($item['text_indicator']['value']) ? $item['text_indicator']['value'] : '—';
                    $stats = isset($item['statistics']) && is_array($item['statistics']) ? $item['statistics'] : [];
                    $statMap = [];
                    foreach ($stats as $stat) {
                        $statMap[$stat['field']] = $stat['value'];
                    }
                    $shows = isset($statMap['SHOWS']) ? (int) $statMap['SHOWS'] : 0;
                    $clicks = isset($statMap['CLICKS']) ? (int) $statMap['CLICKS'] : 0;
                    $ctr = isset($statMap['CTR']) ? round((float) $statMap['CTR'] * 100, 2) : 0;
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