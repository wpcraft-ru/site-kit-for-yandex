<?php

namespace SiteKitForYandex;

YandexObjectStorageS3::init();

class YandexObjectStorageS3
{
    /**
     * Settings section ID.
     */
    private static $sectionId = 'site_kit_for_yandex_s3_section';

    /**
     * Settings page slug.
     */
    private static $pageSlug = 'site-kit-for-yandex';

    public static function init()
    {
        add_action('admin_init', [self::class, 'registerSettingsSection'], 150);
    }

    /**
     * Register S3 settings section.
     *
     * @return void
     */
    public static function registerSettingsSection()
    {
        add_settings_section(
            self::$sectionId,
            __('Yandex Object Storage - S3', 'site-kit-for-yandex'),
            [self::class, 'renderSectionText'],
            self::$pageSlug
        );
    }

    /**
     * Render S3 section description text.
     *
     * @return void
     */
    public static function renderSectionText()
    {
        ?>
        <p>
            <?php echo esc_html__('Yandex Object Storage is an S3-compatible object storage for storing and managing data.', 'site-kit-for-yandex'); ?>
            <a href="https://yandex.cloud/ru/services/storage" target="_blank"
                rel="noopener noreferrer"><?php echo esc_html__('Learn more', 'site-kit-for-yandex'); ?></a>
        </p>
        <p><?php echo esc_html__('Use only under professional supervision, with full understanding of features, risks, and maintenance costs.', 'site-kit-for-yandex'); ?>
        </p>
        <p>
            <?php echo esc_html__('For setup, use a proven plugin from the well-known Human Made team:', 'site-kit-for-yandex'); ?>
            <a href="https://github.com/humanmade/S3-Uploads" target="_blank" rel="noopener noreferrer">
                <span>S3-Uploads</span>
            </a>
        </p>
        <?php

    }
}