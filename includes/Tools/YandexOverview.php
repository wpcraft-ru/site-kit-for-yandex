<?php

namespace SiteKitForYandex;

YandexOverview::init();

class YandexOverview
{
    public static function init()
    {
        add_action('admin_menu', [self::class, 'addMenu'], 20);
        add_action('site_kit_for_yandex_before_settings_form', [self::class, 'renderActionsForSettingsForm']);
    }

    public static function renderActionsForSettingsForm()
    {
        printf(
            '<p>%1$s <a href="%2$s">%3$s</a>.</p>',
            esc_html__('Site overview is available on the page', 'site-kit-for-yandex'),
            esc_url(admin_url('tools.php?page=skfy-overview')),
            esc_html__('Yandex Overview', 'site-kit-for-yandex')
        );
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
                    esc_html__('Plugin Settings', 'site-kit-for-yandex')
                );
                ?>
            </p>

            <p>
                <?php
                $metrikaCounterId = YandexMetrika::getCounterId();
                $metrikaUrl = 'https://metrika.yandex.ru';
                if (! empty($metrikaCounterId)) {
                    $metrikaUrl = 'https://metrika.yandex.ru/overview?id='.(int) $metrikaCounterId;
                }

                printf(
                    '%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.',
                    esc_html__('Metrika Details:', 'site-kit-for-yandex'),
                    esc_url($metrikaUrl),
                    esc_html__('metrika.yandex.ru', 'site-kit-for-yandex')
                );
                ?>
            </p>

            <p>
                <?php
                printf(
                    '%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.',
                    esc_html__('Webmaster Details:', 'site-kit-for-yandex'),
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
                    esc_html__('To view Yandex data, you need to authorize and get an access token in', 'site-kit-for-yandex'),
                    esc_url(admin_url('options-general.php?page=site-kit-for-yandex')),
                    esc_html__('plugin settings', 'site-kit-for-yandex')
                );

                echo '</div>';
                return;
            }

            self::renderSummary();

            if (empty($metrikaCounterId)) {
                printf(
                    '<p>%s</p>',
                    esc_html__('Yandex.Metrika counter ID is not set. Please specify it in plugin settings.', 'site-kit-for-yandex')
                );

                echo '</div>';
                return;
            }

            echo '<hr>';
            self::renderTop10PagesForLast28Days();
            self::renderTop10KeyPhraseForLast28Days();
            self::renderTop10WebmasterQueriesForLast14Days();
            ?>
        </div>
        <?php
    }

    public static function renderTop10WebmasterQueriesForLast14Days()
    {
        printf('<h2>%s</h2>', esc_html__('Top 10 Queries and Pages from Webmaster', 'site-kit-for-yandex'));
        printf('<p>%s</p>', esc_html__('Sorted by clicks. Last 14 days. 14 days is the maximum for Webmaster.', 'site-kit-for-yandex'));

        $items = YandexWebmaster::getTop10WebmasterQueriesForLast14Days();

        if (is_wp_error($items)) {
            echo '<p>'.esc_html__('Error getting Webmaster data: ', 'site-kit-for-yandex').esc_html($items->get_error_message()).'</p>';
            return;
        }

        if (empty($items)) {
            echo '<p>'.esc_html__('No data.', 'site-kit-for-yandex').'</p>';
            return;
        }

        ?>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Query', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Page URL', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Impressions', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Clicks', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('CTR, %', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Avg position', 'site-kit-for-yandex'); ?></th>
                    <th><?php echo esc_html__('Demand', 'site-kit-for-yandex'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item) : ?>
                    <tr>
                        <td><?php echo esc_html($item['query']); ?></td>
                        <td>
                            <?php if (! empty($item['url'])) : ?>
                                <a href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($item['url']); ?></a>
                            <?php else : ?>
                                <?php echo esc_html('—'); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(number_format_i18n((int) $item['impressions'])); ?></td>
                        <td><?php echo esc_html(number_format_i18n((int) $item['clicks'])); ?></td>
                        <td><?php echo esc_html(number_format_i18n((float) $item['ctr'], 2)); ?></td>
                        <td><?php echo esc_html(number_format_i18n((float) $item['position'], 2)); ?></td>
                        <td><?php echo esc_html(number_format_i18n((float) $item['demand'], 2)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    public static function renderTop10KeyPhraseForLast28Days()
    {
        printf('<h2>%s</h2>', esc_html__('Top 10 Key Phrases for the Last 28 Days', 'site-kit-for-yandex'));

        $items = YandexMetrika::getTop10KeyPhraseForLast28Days();

        if (is_wp_error($items)) {
            echo '<p>'.esc_html__('Error getting Metrika data: ', 'site-kit-for-yandex').esc_html($items->get_error_message()).'</p>';
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

    public static function renderTop10PagesForLast28Days()
    {
        echo '<h2>'.esc_html__('Top 10 Pages for the Last 28 Days', 'site-kit-for-yandex').'</h2>';

        $items = YandexMetrika::getTop10PagesForLast28Days();

        if (is_wp_error($items)) {
            echo '<p>'.esc_html__('Error getting Metrika data: ', 'site-kit-for-yandex').esc_html($items->get_error_message()).'</p>';
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
                        <td><a href="<?php echo esc_url($item['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($item['url']); ?></a></td>
                        <td><?php echo esc_html($item['visits']); ?></td>
                        <td><?php echo esc_html($item['title']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    public static function renderSqi()
    {
        ?>
        <h2>Site Quality Index (SQI)</h2>
        <?php

        $value = self::getSqi();
        printf('<p>%1$s <strong>%2$s</strong></p>', esc_html__('SQI:', 'site-kit-for-yandex'), esc_html($value));
    }

    public static function renderSummary()
    {
        ?>
        <h2><?php echo esc_html__('Site Summary', 'site-kit-for-yandex'); ?></h2>
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
                    <td><strong><?php echo esc_html__('Site Quality Index (SQI)', 'site-kit-for-yandex'); ?></strong></td>
                    <td><?php echo null !== $sqi ? esc_html(number_format_i18n($sqi)) : '—'; ?></td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Pages in Search', 'site-kit-for-yandex'); ?></strong></td>
                    <td><?php echo null !== $searchablePagesCount ? esc_html(number_format_i18n($searchablePagesCount)) : '—'; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php echo esc_html__('Excluded Pages', 'site-kit-for-yandex'); ?></strong></td>
                    <td><?php echo null !== $excludedPagesCount ? esc_html(number_format_i18n($excludedPagesCount)) : '—'; ?>
                    </td>
                </tr>
                <?php if (! empty($siteProblems)) : ?>
                    <?php foreach ($siteProblems as $severity => $count) : ?>
                        <tr>
                            <td>
                                <strong>
                                    <?php
                                    /* translators: %s: site problem severity level. */
                                    printf(
                                        /* translators: %s: site problem severity level. */
                                        esc_html__('Site Issues (%s)', 'site-kit-for-yandex'),
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
                        <td><strong><?php echo esc_html__('Site Issues', 'site-kit-for-yandex'); ?></strong></td>
                        <td><?php echo esc_html__('No issues found.', 'site-kit-for-yandex'); ?></td>
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

        return YandexWebmaster::apiSite('search-queries/popular?'.http_build_query($query));
    }

    public static function getSummary()
    {
        $data = get_transient('skfy_webmaster_summary_data');
        if ($data !== false) {
            return $data;
        }
        $data = YandexWebmaster::apiSite('summary');
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

        $data = YandexWebmaster::apiSite($path);
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
        return YandexWebmaster::getSiteInfo();
    }

    public static function webmasterApiSite($path, $method = 'GET', $data = [])
    {
        return YandexWebmaster::apiSite($path, $method, $data);
    }

    public static function webmasterApi($path, $method = 'GET', $data = [])
    {
        return YandexWebmaster::api($path, $method, $data);
    }
}
