<?php
/**
 * Plugin Name: سازان لندینگ — حکمرانی بر بازار (Sazan Landing)
 * Description: مجموعه ویجت‌های المنتور برای ساخت لندینگ «دوره جامع حکمرانی بر بازار» با کنترل کامل متن‌ها و استایل‌ها، به‌همراه فرم ثبت درخواست حضور در دوره.
 * Version: 1.0.0
 * Author: Sazan
 * Text Domain: sazan-landing
 * Domain Path: /languages
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SZL_VERSION', '1.0.0' );
define( 'SZL_FILE', __FILE__ );
define( 'SZL_DIR', plugin_dir_path( __FILE__ ) );
define( 'SZL_URL', plugin_dir_url( __FILE__ ) );
define( 'SZL_PREFIX', 'szl' );

require_once SZL_DIR . 'includes/class-szl-requests.php';

if ( is_admin() ) {
	require_once SZL_DIR . 'includes/class-szl-admin.php';
}

add_action( 'plugins_loaded', 'szl_init' );
function szl_init() {
	load_plugin_textdomain( 'sazan-landing', false, dirname( plugin_basename( SZL_FILE ) ) . '/languages' );

	SZL_Requests::init();

	if ( is_admin() ) {
		SZL_Admin::init();
	}

	add_action( 'wp_enqueue_scripts', 'szl_register_assets' );

	// Elementor integration (loaded lazily, only when Elementor is active).
	add_action( 'elementor/elements/categories_registered', 'szl_elementor_category' );
	add_action( 'elementor/widgets/register', 'szl_elementor_widgets' );

	// Assets must also be available inside the editor preview.
	add_action( 'elementor/preview/enqueue_styles', 'szl_register_assets' );
}

/** Register (not enqueue) front assets; widgets pull them in via get_*_depends(). */
function szl_register_assets() {
	wp_register_style( 'szl-front', SZL_URL . 'assets/css/sazan-landing.css', array(), SZL_VERSION );
	wp_register_script( 'szl-front', SZL_URL . 'assets/js/sazan-landing.js', array(), SZL_VERSION, true );

	wp_localize_script( 'szl-front', 'SZL_CFG', array(
		'ajax'  => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'szl_request' ),
		'i18n'  => array(
			'sending' => 'در حال ارسال…',
			'failed'  => 'ارسال انجام نشد. لطفاً دوباره تلاش کنید.',
		),
	) );
}

/** Elementor: register the widget category. */
function szl_elementor_category( $manager ) {
	$manager->add_category( 'sazan-landing', array(
		'title' => 'سازان — لندینگ دوره',
		'icon'  => 'eicon-single-page',
	) );
}

/** Elementor: register all landing widgets (supports Elementor 3.5+ and older). */
function szl_elementor_widgets( $widgets_manager ) {
	require_once SZL_DIR . 'includes/class-szl-widget-base.php';

	foreach ( szl_elementor_widget_list() as $slug => $cls ) {
		$file = SZL_DIR . 'includes/elementor/class-szl-w-' . $slug . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
		if ( ! class_exists( $cls ) ) {
			continue;
		}
		$widget = new $cls();
		if ( method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( $widget );
		} elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
			$widgets_manager->register_widget_type( $widget );
		}
	}
}

/** Map of widget file slug => class name. */
function szl_elementor_widget_list() {
	return array(
		'hero'      => 'SZL_W_Hero',
		'facts'     => 'SZL_W_Facts',
		'flow'      => 'SZL_W_Flow',
		'questions' => 'SZL_W_Questions',
		'compare'   => 'SZL_W_Compare',
		'modules'   => 'SZL_W_Modules',
		'people'    => 'SZL_W_People',
		'features'  => 'SZL_W_Features',
		'layers'    => 'SZL_W_Layers',
		'checklist' => 'SZL_W_Checklist',
		'results'   => 'SZL_W_Results',
		'form'      => 'SZL_W_Form',
		'faq'       => 'SZL_W_Faq',
	);
}

register_activation_hook( __FILE__, array( 'SZL_Requests', 'activate' ) );
