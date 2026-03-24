<?php

namespace SiteKitForYandex;

YandexURLInspector::init();

class YandexURLInspector
{

    public static function init()
    {
        add_action('admin_menu', [self::class, 'addMenu'], 20);
    }

    // addMenu
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

    // renderPage
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
        $currentUrl = isset($_GET['url']) ? sanitize_text_field(wp_unslash($_GET['url'])) : '';
        ?>
        <form method="get" action="<?php echo esc_url(admin_url('tools.php')); ?>" style="margin: 16px 0 24px;">
            <input type="hidden" name="page" value="skfy-url-inspector" />
            <label for="skfy-url-input" style="display:block; margin-bottom: 8px;">
                <?php echo esc_html__('Введите URL для анализа', 'site-kit-for-yandex'); ?>
            </label>
            <input id="skfy-url-input" type="url" name="url" class="regular-text" style="min-width: 420px;"
                placeholder="https://example.com/page/" value="<?php echo esc_attr($currentUrl); ?>" required />
            <?php submit_button(esc_html__('Анализировать URL', 'site-kit-for-yandex'), 'primary', '', false); ?>
        </form>
        <?php
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