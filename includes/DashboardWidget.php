<?php

namespace SiteKitForYandex;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

DashboardWidget::init();

class DashboardWidget
{

    /**
     * Initialize the widget and REST endpoint
     */
    public static function init()
    {
        add_action('wp_dashboard_setup', [self::class, 'register_widget']);
        add_action('rest_api_init', [self::class, 'register_rest_endpoint']);
    }

    /**
     * Register the dashboard widget
     */
    public static function register_widget()
    {
        if (! current_user_can('manage_options') && ! current_user_can('edit_posts')) {
            return;
        }

        wp_add_dashboard_widget(
            'sitekit_for_yandex',           // Widget ID
            __('Overview from Yandex', 'sitekit-for-yandex'),        // Widget Title
            [self::class, 'display_content']     // Callback
        );
    }

    /**
     * Display the content of the dashboard widget
     */
    public static function display_content()
    {
        $overview_url = admin_url('tools.php?page=skfy-overview');
        $inspector_url = admin_url('tools.php?page=skfy-url-inspector');

        ?>
        <div class="sitekit-for-yandex-widget-content" id="sitekit-widget-loading">
            <div style="padding: 20px; text-align: center; color: #666;">
                <p><?php _e('Loading data...', 'sitekit-for-yandex'); ?></p>
            </div>

            <p style="margin-top: 10px;"><strong><?php _e('Yandex Tools:', 'sitekit-for-yandex'); ?></strong></p>
            <p>
                <a href="<?php echo esc_url($overview_url); ?>" class="button button-secondary">
                    <?php _e('Overview', 'sitekit-for-yandex'); ?>
                </a>
                <a href="<?php echo esc_url($inspector_url); ?>" class="button button-secondary" style="margin-left: 8px;">
                    <?php _e('URL Inspector', 'sitekit-for-yandex'); ?>
                </a>
            </p>
        </div>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.getElementById('sitekit-widget-loading');
                if (!container) return;

                const restUrl = <?php echo wp_json_encode(rest_url('sitekit-for-yandex/v1/dashboard-widget-data')); ?>;

                fetch(restUrl, {
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>,
                        'Content-Type': 'application/json',
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.summary_html || data.urls_html) {
                            const summaryHtml = data.summary_html || '';
                            const urlsHtml = data.urls_html || '';

                            // Replace loading div content with actual data
                            const loadingDiv = container.querySelector('div[style*="padding: 20px"]');
                            if (loadingDiv) {
                                loadingDiv.innerHTML = summaryHtml + urlsHtml;
                            }
                        }
                    });
            });
        </script>

        <?php
    }

    /**
     * Render summary metrics from Yandex
     */
    private static function render_summary_metrics()
    {
        $data = YandexOverview::getSummary();
        if (is_wp_error($data)) {
            return;
        }

        $sqi = isset($data['sqi']) ? (int) $data['sqi'] : null;
        $searchable_pages = isset($data['searchable_pages_count']) ? (int) $data['searchable_pages_count'] : null;
        $excluded_pages = isset($data['excluded_pages_count']) ? (int) $data['excluded_pages_count'] : null;
        ?>
        <div style="background: #f5f5f5; padding: 12px; border-radius: 4px; margin-bottom: 12px; font-size: 13px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <?php if (null !== $sqi) : ?>
                    <div>
                        <div style="color: #666; font-size: 12px;">ИКС</div>
                        <div style="font-weight: bold; font-size: 18px; color: #0073aa;">
                            <?php echo esc_html(number_format_i18n($sqi)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (null !== $searchable_pages) : ?>
                    <div>
                        <div style="color: #666; font-size: 12px;"><?php _e('В поиске', 'sitekit-for-yandex'); ?></div>
                        <div style="font-weight: bold; font-size: 18px; color: #0073aa;">
                            <?php echo esc_html(number_format_i18n($searchable_pages)); ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (null !== $excluded_pages) : ?>
                    <div>
                        <div style="color: #666; font-size: 12px;"><?php _e('Исключено', 'sitekit-for-yandex'); ?></div>
                        <div style="font-weight: bold; color: #d63638;">
                            <?php echo esc_html(number_format_i18n($excluded_pages)); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render important pages status from Yandex URL Inspector
     */
    private static function render_important_pages_status()
    {
        $data = YandexWebmaster::apiSite('important-urls');
        if (is_wp_error($data)) {
            return;
        }

        $urls = isset($data['urls']) && is_array($data['urls']) ? $data['urls'] : [];
        if (empty($urls)) {
            return;
        }

        $indexed = 0;
        $indexing_errors = 0;
        $not_searchable = 0;

        foreach ($urls as $url_data) {
            $searchable = isset($url_data['search_status']['searchable']) ? $url_data['search_status']['searchable'] : false;
            $status = isset($url_data['indexing_status']['status']) ? $url_data['indexing_status']['status'] : '';

            if (! $searchable) {
                $not_searchable++;
            } elseif ('Indexed' === $status) {
                $indexed++;
            } else {
                $indexing_errors++;
            }
        }
        ?>
        <div
            style="background: #fafafa; padding: 12px; border-radius: 4px; margin-bottom: 12px; font-size: 13px; border-left: 4px solid #0073aa;">
            <div style="color: #666; font-size: 12px; margin-bottom: 8px;">
                <?php _e('Мониторинг важных страниц', 'sitekit-for-yandex'); ?>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                <div>
                    <div style="color: #666; font-size: 11px;"><?php _e('Индексед', 'sitekit-for-yandex'); ?></div>
                    <div style="font-weight: bold; font-size: 16px; color: #27ae60;">
                        <?php echo esc_html($indexed); ?>
                    </div>
                </div>
                <div>
                    <div style="color: #666; font-size: 11px;"><?php _e('Ошибки', 'sitekit-for-yandex'); ?></div>
                    <div style="font-weight: bold; font-size: 16px; color: #d63638;">
                        <?php echo esc_html($indexing_errors); ?>
                    </div>
                </div>
                <div>
                    <div style="color: #666; font-size: 11px;"><?php _e('Не в поиске', 'sitekit-for-yandex'); ?></div>
                    <div style="font-weight: bold; font-size: 16px; color: #f39c12;">
                        <?php echo esc_html($not_searchable); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Register REST API endpoint for dashboard widget data
     *
     * @since 1.0.0
     * @return void
     */
    public static function register_rest_endpoint()
    {
        register_rest_route(
            'sitekit-for-yandex/v1',
            '/dashboard-widget-data',
            [
                'methods' => 'GET',
                'callback' => [self::class, 'rest_get_widget_data'],
                'permission_callback' => [self::class, 'check_widget_access'],
            ]
        );
    }

    /**
     * Check if user has permission to access widget data
     *
     * @since 1.0.0
     * @param \WP_REST_Request $request REST request object
     * @return bool|\WP_Error
     */
    public static function check_widget_access($request)
    {
        return true;
    }

    /**
     * REST API callback to get widget data
     *
     * @since 1.0.0
     * @param WP_REST_Request $request REST request object
     * @return WP_REST_Response|WP_Error
     */
    public static function rest_get_widget_data($request)
    {
        $data = self::get_widget_html_data();

        return rest_ensure_response($data);
    }

    /**
     * Get widget HTML data
     *
     * Collects HTML output from both render methods and returns as array
     *
     * @since 1.0.0
     * @return array
     */
    private static function get_widget_html_data()
    {
        ob_start();
        self::render_summary_metrics();
        $summary_html = ob_get_clean();

        ob_start();
        self::render_important_pages_status();
        $urls_html = ob_get_clean();

        return [
            'summary_html' => $summary_html ?: '',
            'urls_html' => $urls_html ?: '',
        ];
    }
}

