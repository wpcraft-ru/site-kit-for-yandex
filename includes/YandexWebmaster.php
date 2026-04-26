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

    public static function getQueryAnalytics($url, $limit = 20)
    {
        $body = [
            'text_indicator' => 'URL',
            'filters' => [
                'text_filters' => [
                    [
                        'text_indicator' => 'URL',
                        'operation' => 'TEXT_EQUAL',
                        'value' => $url,
                    ],
                ],
            ],
            'limit' => $limit,
        ];

        return self::apiSite('query-analytics/list', 'POST', $body);
    }

    public static function getTop10WebmasterQueriesForLast14Days()
    {
        $cachedData = get_transient('skfy_webmaster_top_10_queries_14_days');
        if (! empty($cachedData)) {
            return $cachedData;
        }

        $limit = 500;
        $offset = 0;
        $count = null;
        $rows = [];

        do {
            $response = self::apiSite(
                'query-analytics/list',
                'POST',
                [
                    'limit' => $limit,
                    'offset' => $offset,
                ]
            );

            if (is_wp_error($response)) {
                return $response;
            }

            if (is_numeric($response['count'] ?? null)) {
                $count = (int) $response['count'];
            }

            $batchRows = isset($response['text_indicator_to_statistics']) && is_array($response['text_indicator_to_statistics'])
                ? $response['text_indicator_to_statistics']
                : [];

            if (empty($batchRows)) {
                break;
            }

            $rows = array_merge($rows, $batchRows);
            $offset += $limit;

            if (null === $count && count($batchRows) < $limit) {
                break;
            }
        } while (null === $count || $offset < $count);

        if (empty($rows)) {
            return [];
        }

        $preparedData = [];
        foreach ($rows as $row) {
            $query = isset($row['text_indicator']['value']) ? (string) $row['text_indicator']['value'] : '';
            $url = isset($row['popular_complementary_indicator']['value']) ? (string) $row['popular_complementary_indicator']['value'] : '';

            $metrics = self::aggregateQueryAnalyticsStatistics(
                isset($row['statistics']) && is_array($row['statistics']) ? $row['statistics'] : []
            );

            $preparedData[] = [
                'query' => $query,
                'url' => $url,
                'impressions' => $metrics['impressions'],
                'clicks' => $metrics['clicks'],
                'ctr' => $metrics['ctr'],
                'position' => $metrics['position'],
                'demand' => $metrics['demand'],
            ];
        }

        usort(
            $preparedData,
            static function ($left, $right) {
                if ($left['clicks'] === $right['clicks']) {
                    return $right['impressions'] <=> $left['impressions'];
                }

                return $right['clicks'] <=> $left['clicks'];
            }
        );

        $top10 = array_slice($preparedData, 0, 10);

        set_transient('skfy_webmaster_top_10_queries_14_days', $top10, HOUR_IN_SECONDS);

        return $top10;
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

    private static function aggregateQueryAnalyticsStatistics($statistics)
    {
        $impressions = 0.0;
        $clicks = 0.0;
        $ctrValues = [];
        $weightedPosition = 0.0;
        $positionWeight = 0.0;
        $weightedDemand = 0.0;
        $demandWeight = 0.0;

        $consumeStat = static function ($field, $value) use (&$impressions, &$clicks, &$ctrValues, &$weightedPosition, &$positionWeight, &$weightedDemand, &$demandWeight) {
            if (! is_numeric($value)) {
                return;
            }

            $field = strtoupper((string) $field);
            $value = (float) $value;

            if ('IMPRESSIONS' === $field || 'SHOWS' === $field) {
                $impressions += $value;
                return;
            }

            if ('CLICKS' === $field) {
                $clicks += $value;
                return;
            }

            if ('CTR' === $field) {
                $ctrValues[] = $value;
                return;
            }

            if ('POSITION' === $field || 'AVERAGE_SHOW_POSITION' === $field) {
                $weight = $impressions > 0 ? $impressions : 1;
                $weightedPosition += $value * $weight;
                $positionWeight += $weight;
                return;
            }

            if ('DEMAND' === $field) {
                $weight = $impressions > 0 ? $impressions : 1;
                $weightedDemand += $value * $weight;
                $demandWeight += $weight;
            }
        };

        foreach ($statistics as $stat) {
            if (isset($stat['field'], $stat['value'])) {
                $consumeStat($stat['field'], $stat['value']);
            }

            if (isset($stat['statistics']) && is_array($stat['statistics'])) {
                foreach ($stat['statistics'] as $nestedStat) {
                    if (isset($nestedStat['field'], $nestedStat['value'])) {
                        $consumeStat($nestedStat['field'], $nestedStat['value']);
                    }
                }
            }

            if (isset($stat['indicators']) && is_array($stat['indicators'])) {
                foreach ($stat['indicators'] as $nestedIndicator) {
                    if (isset($nestedIndicator['field'], $nestedIndicator['value'])) {
                        $consumeStat($nestedIndicator['field'], $nestedIndicator['value']);
                    }
                }
            }
        }

        if ($impressions > 0) {
            $ctr = ($clicks / $impressions) * 100;
        } elseif (! empty($ctrValues)) {
            $ctrAvg = array_sum($ctrValues) / count($ctrValues);
            $ctr = $ctrAvg <= 1 ? $ctrAvg * 100 : $ctrAvg;
        } else {
            $ctr = 0;
        }

        $position = $positionWeight > 0 ? $weightedPosition / $positionWeight : 0;
        $demand = $demandWeight > 0 ? $weightedDemand / $demandWeight : 0;

        return [
            'impressions' => (int) round($impressions),
            'clicks' => (int) round($clicks),
            'ctr' => round($ctr, 2),
            'position' => round($position, 2),
            'demand' => round($demand, 2),
        ];
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
                    '<p>%s</p>',
                    esc_html__('Yandex Webmaster is a free service for webmasters and site owners that helps track site status in Yandex search results, analyze indexing, identify technical issues, and improve search positions.', 'site-kit-for-yandex')
                );

                printf(
                    '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
                    esc_html__('Connect your site and view Yandex.Webmaster data through', 'site-kit-for-yandex'),
                    esc_url('https://webmaster.yandex.ru/sites/'),
                    esc_html__('Yandex.Webmaster', 'site-kit-for-yandex')
                );

                printf(
                    '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
                    esc_html__('For integration, we use the Yandex.Webmaster API.', 'site-kit-for-yandex'),
                    esc_url('https://yandex.ru/dev/webmaster/'),
                    esc_html__('API Documentation', 'site-kit-for-yandex')
                );
            },
            self::$pageSlug
        );
    }
}
