<?php
/**
 * Plugin Name: Sazan Core
 * Description: مجموعه ویجت‌های اختصاصی المنتور (سازان) با کنترل‌های استایلی کامل.
 * Version:     3.30.0
 * Author:      Sazan
 * Text Domain: sazan-core
 * Requires Plugins: elementor
 *
 * Elementor tested up to: 3.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // دسترسی مستقیم ممنوع
}

/* ثابت‌های افزونه ------------------------------------------------------- */
define( 'SAZAN_CORE_VERSION', '3.30.0' );
define( 'SAZAN_CORE_FILE', __FILE__ );
define( 'SAZAN_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SAZAN_CORE_URL', plugin_dir_url( __FILE__ ) );

/* حداقل نسخه‌های موردنیاز */
define( 'SAZAN_CORE_MIN_ELEMENTOR', '3.5.0' );
define( 'SAZAN_CORE_MIN_PHP', '7.4' );

/**
 * بارگذاری افزونه پس از اطمینان از وجود المنتور.
 */
function sazan_core_load() {

	// بررسی فعال بودن المنتور
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'sazan_core_notice_missing_elementor' );
		return;
	}

	// بررسی نسخه‌ی المنتور
	if ( ! version_compare( ELEMENTOR_VERSION, SAZAN_CORE_MIN_ELEMENTOR, '>=' ) ) {
		add_action( 'admin_notices', 'sazan_core_notice_min_elementor' );
		return;
	}

	// بررسی نسخه‌ی PHP
	if ( version_compare( PHP_VERSION, SAZAN_CORE_MIN_PHP, '<' ) ) {
		add_action( 'admin_notices', 'sazan_core_notice_min_php' );
		return;
	}

	// راه‌اندازی هسته
	require_once SAZAN_CORE_PATH . 'includes/class-sazan-core.php';
	\Sazan\Core::instance();
}
add_action( 'plugins_loaded', 'sazan_core_load' );

/* فعال‌سازی: ساخت جدول لیدهای آزمون + فلش rewrite */
function sazan_core_activate() {
	require_once SAZAN_CORE_PATH . 'includes/class-quiz-engine.php';
	\Sazan\Quiz_Engine::install();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sazan_core_activate' );

/* پیام‌های خطا در پیشخوان ---------------------------------------------- */
function sazan_core_notice_missing_elementor() {
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	$msg = sprintf(
		/* translators: %s: نام افزونه */
		esc_html__( '«%1$s» برای کار نیاز به افزونه‌ی «%2$s» دارد. لطفاً ابتدا المنتور را نصب و فعال کنید.', 'sazan-core' ),
		'<strong>Sazan Core</strong>',
		'<strong>Elementor</strong>'
	);
	printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', wp_kses_post( $msg ) );
}

function sazan_core_notice_min_elementor() {
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	$msg = sprintf(
		esc_html__( '«%1$s» به المنتور نسخه‌ی %2$s یا بالاتر نیاز دارد.', 'sazan-core' ),
		'<strong>Sazan Core</strong>',
		SAZAN_CORE_MIN_ELEMENTOR
	);
	printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', wp_kses_post( $msg ) );
}

function sazan_core_notice_min_php() {
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	$msg = sprintf(
		esc_html__( '«%1$s» به PHP نسخه‌ی %2$s یا بالاتر نیاز دارد.', 'sazan-core' ),
		'<strong>Sazan Core</strong>',
		SAZAN_CORE_MIN_PHP
	);
	printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', wp_kses_post( $msg ) );
}
