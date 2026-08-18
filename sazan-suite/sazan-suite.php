<?php
/**
 * Plugin Name: سازان سوئیت (Sazan Suite)
 * Plugin URI:  https://sazan.ir/
 * Description: مجموعه‌ی یکپارچه‌ی سازان — هسته و ویجت‌های المنتور، پنل کاربری و ارزیابی، CRM تیم فروش و صفحه‌ی اختصاصی محصول؛ همگی در یک افزونه با یک «کنترل پنل تنظیمات» جامع و دسته‌بندی‌شده.
 * Version:     2.8.2
 * Author:      Sazan
 * Text Domain: sazan-suite
 * Domain Path: /languages
 * Requires PHP: 7.4
 *
 * WC requires at least: 7.0
 * WC tested up to: 9.x
 * Elementor tested up to: 3.x
 *
 * @package Sazan\Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // دسترسی مستقیم ممنوع.
}

/* ثابت‌های سوئیت ---------------------------------------------------------- */
define( 'SAZAN_SUITE_VERSION', '2.8.2' );
define( 'SAZAN_SUITE_FILE', __FILE__ );
define( 'SAZAN_SUITE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAZAN_SUITE_URL', plugin_dir_url( __FILE__ ) );
define( 'SAZAN_SUITE_BASENAME', plugin_basename( __FILE__ ) );

/* زیرساخت سوئیت ---------------------------------------------------------- */
require_once SAZAN_SUITE_DIR . 'includes/class-sazan-suite-modules.php';
require_once SAZAN_SUITE_DIR . 'includes/class-sazan-suite-store.php';
require_once SAZAN_SUITE_DIR . 'includes/class-sazan-suite-schema.php';
require_once SAZAN_SUITE_DIR . 'includes/class-sazan-suite-sanitizer.php';

if ( is_admin() ) {
	require_once SAZAN_SUITE_DIR . 'includes/admin/class-sazan-suite-fields.php';
	require_once SAZAN_SUITE_DIR . 'includes/admin/class-sazan-suite-control-panel.php';
	require_once SAZAN_SUITE_DIR . 'includes/admin/class-sazan-suite-tools.php';
	require_once SAZAN_SUITE_DIR . 'includes/admin/class-sazan-suite-admin.php';
}

/*
 * بارگذاری ماژول‌ها.
 *
 * ماژول‌ها دقیقاً در همان لحظه‌ای بارگذاری می‌شوند که قبلاً به‌عنوان افزونه‌ی
 * مستقل بارگذاری می‌شدند (حین بارگذاری افزونه‌ها و پیش از plugins_loaded)؛
 * بنابراین همه‌ی هوک‌ها و ترتیب راه‌اندازی داخلی ماژول‌ها بدون تغییر می‌ماند.
 */
Sazan_Suite_Modules::load();

/* راه‌اندازی بخش مدیریت --------------------------------------------------- */
add_action( 'plugins_loaded', 'sazan_suite_init', 5 );

/**
 * راه‌اندازی سوئیت (پیش از راه‌اندازی ماژول‌ها روی همان هوک).
 */
function sazan_suite_init() {

	load_plugin_textdomain( 'sazan-suite', false, dirname( SAZAN_SUITE_BASENAME ) . '/languages' );

	if ( is_admin() ) {
		// ارتقاها فقط در پیشخوان اجرا می‌شوند تا درخواست‌های فرانت سنگین نشوند.
		add_action( 'admin_init', array( 'Sazan_Suite_Modules', 'maybe_upgrade' ), 1 );
		Sazan_Suite_Admin::init();
	}
}

/* فعال‌سازی و غیرفعال‌سازی ------------------------------------------------- */
register_activation_hook( __FILE__, array( 'Sazan_Suite_Modules', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Sazan_Suite_Modules', 'deactivate' ) );
