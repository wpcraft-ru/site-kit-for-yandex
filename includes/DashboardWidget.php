<?php

namespace SiteKitForYandex;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
DashboardWidget::init();

class DashboardWidget
{

    /**
     * Initialize the widget
     */
    public static function init()
    {
        add_action('wp_dashboard_setup', [self::class, 'register_widget']);
    }

    /**
     * Register the dashboard widget
     */
    public static function register_widget()
    {
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
        <div class="sitekit-for-yandex-widget-content">

            <?php self::render_summary_metrics(); ?>
            <?php self::render_important_pages_status(); ?>

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
                <?php _e('Мониторинг важных страниц', 'sitekit-for-yandex'); ?></div>
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
}

