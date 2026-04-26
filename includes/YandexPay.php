<?php

namespace SiteKitForYandex;

YandexPay::init();

class YandexPay
{
	public static function init()
	{
		add_action('admin_init', [self::class, 'registerSettingsSection'], 95);
	}

	public static function registerSettingsSection()
	{
		add_settings_section(
			'site_kit_for_yandex_pay_section',
			__('Yandex Pay and Split', 'site-kit-for-yandex'),
			[self::class, 'renderSectionText'],
			'site-kit-for-yandex'
		);
	}

	public static function renderSectionText()
	{
		printf(
			'<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
			esc_html__('Yandex Pay and Split is an official payment plugin for WooCommerce that allows customers to pay for purchases with cashback in Plus points or in installments via Yandex Split.', 'site-kit-for-yandex'),
			esc_url('https://wordpress.org/plugins/yandex-pay-and-split/'),
			esc_html__('View official plugin', 'site-kit-for-yandex')
		);

		printf(
			'<p>%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>.</p>',
			esc_html__('To configure the plugin, follow the official setup guide for WordPress and WooCommerce.', 'site-kit-for-yandex'),
			esc_url('https://pay.yandex.ru/docs/ru/cms/wordpress/#settings'),
			esc_html__('Open setup instructions', 'site-kit-for-yandex')
		);
	}
}

