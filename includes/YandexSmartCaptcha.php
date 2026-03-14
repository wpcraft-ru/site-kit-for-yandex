<?php 

namespace SiteKitForYandex;

// YandexSmartCaptcha::init();

final class YandexSmartCaptcha
{
    public static function init()
    {
        // add_action('wp_enqueue_scripts', [self::class, 'enqueue_scripts']);
    }

    public static function enqueue_scripts()
    {
        wp_enqueue_script('yandex-smart-captcha', 'https://captcha-api.yandex.ru/captcha.js', [], null, true);
    }
}