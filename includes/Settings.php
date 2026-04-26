<?php

namespace SiteKitForYandex;

Settings::init();

final class Settings
{
    /**
     * Settings section ID.
     */
    private static $sectionId = 'site_kit_for_yandex_authorization_section';

    /**
     * Settings page slug.
     */
    private static $pageSlug = 'site-kit-for-yandex';

    public static function init()
    {
        add_action('admin_menu', [self::class, 'addSettingsPage']);
        add_action('admin_init', [self::class, 'registerSettingsSection']);
    }

    public function get($key)
    {
        $options = get_option('site_kit_for_yandex_options', []);
        return isset($options[$key]) ? $options[$key] : null;
    }

    public function set($key, $value)
    {
        $options = get_option('site_kit_for_yandex_options', []);
        $options[$key] = $value;
        update_option('site_kit_for_yandex_options', $options);
    }

    public function getClientId()
    {
        return $this->get('client_id');
    }

    public function getClientSecret()
    {
        return $this->get('client_secret');
    }

    public function getAccessToken()
    {
        return $this->get('access_token');
    }

    public static function addSettingsPage()
    {
        add_options_page(
            'Site Kit for Yandex Settings',
            'Site Kit for Yandex',
            'manage_options',
            'site-kit-for-yandex',
            function () {
                ?>
            <div class="wrap">
                <h1>Site Kit for Yandex</h1>
                <p><?php echo esc_html__('Integrate your site with Yandex services (Metrika, Webmaster, etc.) and manage them from the WordPress admin panel, similar to Site Kit by Google.', 'site-kit-for-yandex'); ?></p>
                <?= self::renderActionsToSettingsPage(); ?>
                <?php do_action('site_kit_for_yandex_before_settings_form'); ?>
                <form method="post" action="options.php">
                    <?php
                        settings_fields('site_kit_for_yandex_options');
                        do_settings_sections('site-kit-for-yandex');
                        submit_button();
                        ?>
                </form>
            </div>
            <?php
            }
        );

        register_setting('site_kit_for_yandex_options', 'site_kit_for_yandex_options');
    }

    //renderActionsToSettingsPage
    public static function renderActionsToSettingsPage()
    {
        $links = [
            [
                'url' => "https://wpcraft.ru/wordpress/plugins/site-kit-for-yandex",
                'text' => __('About Plugin', 'site-kit-for-yandex'),
                'external' => true,
            ],
            [
                'url' => 'https://wpcraft.ru/contacts',
                'text' => __('Feedback and Support', 'site-kit-for-yandex'),
                'external' => true,
            ],
        ];
        ob_start();
        ?>
        <div style="margin-bottom: 20px;">
            <?php foreach ($links as $link) : ?>
                <a href="<?= esc_url($link['url']); ?>" class="button" style="margin-right: 10px;" <?= isset($link['external']) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                    <?= esc_html($link['text']); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Register settings section and authorization fields.
     *
     * @return void
     */
    public static function registerSettingsSection()
    {
        add_settings_section(
            self::$sectionId,
            __('Authorization - Yandex OAuth', 'site-kit-for-yandex'),
            [self::class, 'renderAuthorizationSectionText'],
            self::$pageSlug
        );

        add_settings_field(
            'client_id',
            __('ClientID', 'site-kit-for-yandex'),
            [self::class, 'renderClientIdField'],
            self::$pageSlug,
            self::$sectionId
        );

        add_settings_field(
            'client_secret',
            __('Client Secret', 'site-kit-for-yandex'),
            [self::class, 'renderClientSecretField'],
            self::$pageSlug,
            self::$sectionId
        );

        add_settings_field(
            'access_token',
            __('Access Token', 'site-kit-for-yandex'),
            [self::class, 'renderAccessTokenField'],
            self::$pageSlug,
            self::$sectionId
        );
    }

    /**
     * Render authorization section description.
     *
     * @return void
     */
    public static function renderAuthorizationSectionText()
    {
        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
            esc_html__('To use the integration, you need to get application keys in the Yandex OAuth dashboard:', 'site-kit-for-yandex'),
            esc_url('https://oauth.yandex.ru'),
            esc_html__('get ClientID and Client Secret', 'site-kit-for-yandex')
        );

        echo '<ol style="margin-left: 1.2em;">';
        printf(
            '<li>%s</li>',
            esc_html__('Add a new application and specify your site name to make it easier to navigate the app list.', 'site-kit-for-yandex')
        );
        printf(
            '<li>%s</li>',
            esc_html__('In app settings, grant permissions to access Yandex.Metrika and Yandex.Webmaster data.', 'site-kit-for-yandex')
        );
        printf(
            '<li>%s</li>',
            esc_html__('After that, copy the received ClientID and Client Secret into the fields above and save settings.', 'site-kit-for-yandex')
        );
        echo '</ol>';

    }

    /**
     * Render ClientID field.
     *
     * @return void
     */
    public static function renderClientIdField()
    {
        $options = get_option('site_kit_for_yandex_options', []);
        $value = isset($options['client_id']) ? $options['client_id'] : '';

        printf(
            '<input type="text" class="regular-text" name="site_kit_for_yandex_options[client_id]" value="%s" autocomplete="off" />',
            esc_attr($value)
        );
  
    }

    /**
     * Render Client Secret field.
     *
     * @return void
     */
    public static function renderClientSecretField()
    {
        $options = get_option('site_kit_for_yandex_options', []);
        $value = isset($options['client_secret']) ? $options['client_secret'] : '';

        printf(
            '<input type="password" class="regular-text" name="site_kit_for_yandex_options[client_secret]" value="%s" autocomplete="new-password" />',
            esc_attr($value)
        );
    }

    /**
     * Render Access Token field.
     *
     * @return void
     */
    public static function renderAccessTokenField()
    {
        $clientId = skfy()->config()->getClientId();

        $options = get_option('site_kit_for_yandex_options', []);
        $value = isset($options['access_token']) ? $options['access_token'] : '';

        printf(
            '<input type="password" class="regular-text" name="site_kit_for_yandex_options[access_token]" value="%s" autocomplete="off" />',
            esc_attr($value)
        );

        printf(
            '<p>%s</p>',
            esc_html__('To get an access token, first enter ClientID and save settings.', 'site-kit-for-yandex')
        );

        if (! empty($clientId)) {
            printf(
                '<p><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></p>',
                esc_url('https://oauth.yandex.ru/authorize?response_type=token&client_id='.$clientId),
                esc_html__('Get Access Token', 'site-kit-for-yandex')
            );
        }

    }
}