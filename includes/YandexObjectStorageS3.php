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
        add_action('admin_init', [self::class, 'registerSettingsSection'], 90);
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
            <?php echo esc_html__('Yandex Object Storage — S3-совместимое объектное хранилище для хранения и управления данными.', 'site-kit-for-yandex'); ?>
            <a href="https://yandex.cloud/ru/services/storage" target="_blank"
                rel="noopener noreferrer"><?php echo esc_html__('Подробнее', 'site-kit-for-yandex'); ?></a>
        </p>
        <p><?php echo esc_html__('Используется только под присмотром профессионалов, с пониманием всех особенностей, рисков и затрат на обслуживание.', 'site-kit-for-yandex'); ?>
        </p>
        <p>
            <?php echo esc_html__('Для настройки используйте проверенный плагин от известной команды Human Made:', 'site-kit-for-yandex'); ?>
            <a href="https://github.com/humanmade/S3-Uploads" target="_blank" rel="noopener noreferrer">
                <span>S3-Uploads</span>
            </a>
        </p>
        <?php

    }
}