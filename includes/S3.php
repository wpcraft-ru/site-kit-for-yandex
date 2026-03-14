<?php 

namespace SiteKitForYandex;


// @todo rename to YandexObjectStorage
class S3
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
        add_action('admin_init', [self::class, 'registerSettingsSection']);
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
        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Yandex Object Storage — S3-совместимое объектное хранилище для хранения и управления данными.', 'site-kit-for-yandex'),
            esc_url('https://yandex.cloud/ru/services/storage'),
            esc_html__('Подробнее', 'site-kit-for-yandex')
        );

        //для настройки используйте рекомендованный плагин от известной команды https://github.com/humanmade/S3-Uploads
        printf(
            '<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a></p>',
            esc_html__('Для настройки используйте рекомендованный плагин от известной команды Human Made', 'site-kit-for-yandex'),
            esc_url('https://github.com/humanmade/S3-Uploads'),
            esc_html__('S3-Uploads', 'site-kit-for-yandex')
        );

    }
}