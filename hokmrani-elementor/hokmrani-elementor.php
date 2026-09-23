<?php
/**
 * Plugin Name: لندینگ حکمرانی بر بازار برای المنتور (Hokmrani Landing for Elementor)
 * Description: تمام بخش‌های صفحه لندینگ «حکمرانی بر بازار» به‌صورت ویجت‌های مستقل المنتور، به‌همراه دکمه «ساخت صفحه» که کل صفحه را با همین ویجت‌ها و دقیقاً با همان ظاهر نسخه استاتیک می‌سازد.
 * Version: 1.1.0
 * Author: Sazan
 * Text Domain: hokmrani-elementor
 * Requires PHP: 7.4
 * Requires at least: 5.9
 * Elementor tested up to: 3.30.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HKL_VERSION', '1.1.0' );
define( 'HKL_FILE', __FILE__ );
define( 'HKL_DIR', plugin_dir_path( __FILE__ ) );
define( 'HKL_URL', plugin_dir_url( __FILE__ ) );
define( 'HKL_TEMPLATE', 'hokmrani-landing-canvas.php' );

require_once HKL_DIR . 'includes/class-hkl-plugin.php';
require_once HKL_DIR . 'includes/class-hkl-leads.php';
require_once HKL_DIR . 'includes/class-hkl-page-builder.php';

HKL_Plugin::instance();
HKL_Leads::init();
HKL_Page_Builder::init();
