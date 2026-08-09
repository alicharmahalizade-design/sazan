<?php
/**
 * ماژول «پنل کاربری» از سوئیت سازان.
 *
 * این فایل قبلاً فایل اصلی افزونه‌ی مستقل «سازان پنل» بود و اکنون به‌عنوان یک
 * ماژول از داخل sazan-suite.php بارگذاری می‌شود.
 *
 * @package Sazan\Suite\Modules\Panel
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SZP_VERSION', '1.32.1' );
define( 'SZP_FILE', __FILE__ );
define( 'SZP_DIR', plugin_dir_path( __FILE__ ) );
define( 'SZP_URL', plugin_dir_url( __FILE__ ) );
define( 'SZP_PREFIX', 'szp' );

require_once SZP_DIR . 'includes/functions.php';
require_once SZP_DIR . 'includes/class-szp-settings.php';
require_once SZP_DIR . 'includes/class-szp-install.php';
require_once SZP_DIR . 'includes/class-szp-cpt.php';
require_once SZP_DIR . 'includes/class-szp-groups.php';
require_once SZP_DIR . 'includes/class-szp-access.php';
require_once SZP_DIR . 'includes/class-szp-woo.php';
require_once SZP_DIR . 'includes/class-szp-data.php';
require_once SZP_DIR . 'includes/class-szp-render.php';
require_once SZP_DIR . 'includes/class-szp-coach.php';
require_once SZP_DIR . 'includes/class-szp-coach-render.php';
require_once SZP_DIR . 'includes/class-szp-chat.php';
require_once SZP_DIR . 'includes/class-szp-ai.php';
require_once SZP_DIR . 'includes/class-szp-canvas.php';
require_once SZP_DIR . 'includes/class-szp-eval.php';
require_once SZP_DIR . 'includes/class-szp-growth.php';
require_once SZP_DIR . 'includes/class-szp-eval-roles.php';
require_once SZP_DIR . 'includes/class-szp-courses-slider.php';
require_once SZP_DIR . 'includes/class-szp-sms.php';
require_once SZP_DIR . 'includes/class-szp-sessions.php';
require_once SZP_DIR . 'includes/class-szp-sessions-render.php';
require_once SZP_DIR . 'includes/frontend/class-szp-frontend.php';
require_once SZP_DIR . 'includes/frontend/class-szp-front-ajax.php';
require_once SZP_DIR . 'includes/frontend/class-szp-chat-ajax.php';
require_once SZP_DIR . 'includes/frontend/class-szp-coach-ajax.php';
require_once SZP_DIR . 'includes/frontend/class-szp-canvas-ajax.php';
require_once SZP_DIR . 'includes/frontend/class-szp-eval-ajax.php';
require_once SZP_DIR . 'includes/frontend/class-szp-sessions-ajax.php';

if ( is_admin() ) {
	require_once SZP_DIR . 'includes/admin/class-szp-admin.php';
	require_once SZP_DIR . 'includes/admin/class-szp-metaboxes.php';
	require_once SZP_DIR . 'includes/admin/class-szp-groups-page.php';
	require_once SZP_DIR . 'includes/admin/class-szp-roles-page.php';
	require_once SZP_DIR . 'includes/admin/class-szp-chat-admin.php';
	require_once SZP_DIR . 'includes/admin/class-szp-ajax.php';
	require_once SZP_DIR . 'includes/admin/class-szp-reports.php';
	require_once SZP_DIR . 'includes/admin/class-szp-coach-admin.php';
	require_once SZP_DIR . 'includes/admin/class-szp-canvas-admin.php';
	require_once SZP_DIR . 'includes/admin/class-szp-eval-admin.php';
	require_once SZP_DIR . 'includes/admin/class-szp-eval-settings.php';
}

// فعال‌سازی/غیرفعال‌سازی از طریق Sazan_Suite_Modules مدیریت می‌شود.

add_action( 'plugins_loaded', 'szp_init' );
function szp_init() {
	load_plugin_textdomain( 'sazan-panel', false, dirname( plugin_basename( SZP_FILE ) ) . '/languages' );
	// Run additive schema upgrades before any front-end form can write new fields.
	SZP_Install::maybe_upgrade();

	SZP_CPT::init();
	SZP_Woo::init();
	SZP_Eval_Roles::init();
	SZP_Frontend::init();
	SZP_Front_Ajax::init();
	SZP_Chat_Ajax::init();
	SZP_Coach_Ajax::init();
	SZP_Canvas_Ajax::init();
	SZP_Eval_Ajax::init();
	SZP_Sessions_Ajax::init();

	// یادآور پیامکی روزانه (ارزیابی).
	add_action( 'szp_eval_daily', array( 'SZP_SMS', 'run_daily_reminders' ) );
	if ( ! wp_next_scheduled( 'szp_eval_daily' ) ) {
		// اجرای روزانه؛ ساعت ۹ صبح به‌وقت سایت.
		$first = strtotime( 'tomorrow 09:00' );
		wp_schedule_event( $first ? $first : ( time() + HOUR_IN_SECONDS ), 'daily', 'szp_eval_daily' );
	}

	// ارسال دقیقِ لینک نظرسنجی جلسه (رویداد تک‌بار) + جاروبِ ساعتیِ پشتیبان.
	add_action( SZP_Sessions::HOOK_SURVEY, array( 'SZP_Sessions', 'send_survey_for' ) );
	add_action( SZP_Sessions::HOOK_SWEEP, array( 'SZP_Sessions', 'run_survey_sweep' ) );
	if ( ! wp_next_scheduled( SZP_Sessions::HOOK_SWEEP ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', SZP_Sessions::HOOK_SWEEP );
	}

	// Elementor integration (loaded lazily, only when Elementor is active).
	add_action( 'elementor/elements/categories_registered', 'szp_elementor_category' );
	add_action( 'elementor/widgets/register', 'szp_elementor_widgets' );

	if ( is_admin() ) {
		add_action( 'admin_init', array( 'SZP_Install', 'maybe_upgrade' ) );
		SZP_Admin::init();
		SZP_Metaboxes::init();
		SZP_Groups_Page::init();
		SZP_Roles_Page::init();
		SZP_Chat_Admin::init();
		SZP_Ajax::init();
		SZP_Reports::init();
		SZP_Coach_Admin::init();
		SZP_Canvas_Admin::init();
		SZP_Eval_Admin::init();
		SZP_Eval_Settings::init();
	}
}

/** Elementor: register the widget category. */
function szp_elementor_category( $manager ) {
	$manager->add_category( 'sazan-panel', array(
		'title' => 'سازان پنل',
		'icon'  => 'eicon-document-file',
	) );
}

/** Elementor: register all Sazan widgets (supports Elementor 3.5+ and older). */
function szp_elementor_widgets( $widgets_manager ) {
	$files = array(
		'includes/elementor/widgets.php'         => 'szp_elementor_widget_list',
		'includes/elementor/widgets-landing.php' => 'szl_elementor_widget_list',
	);

	$classes = array();
	foreach ( $files as $file => $list_fn ) {
		if ( file_exists( SZP_DIR . $file ) ) {
			require_once SZP_DIR . $file;
		}
		if ( function_exists( $list_fn ) ) {
			$classes = array_merge( $classes, call_user_func( $list_fn ) );
		}
	}

	foreach ( $classes as $cls ) {
		if ( ! class_exists( $cls ) ) {
			continue;
		}
		try {
			$widget = new $cls();
			if ( method_exists( $widgets_manager, 'register' ) ) {
				$widgets_manager->register( $widget );
			} elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
				$widgets_manager->register_widget_type( $widget );
			}
		} catch ( \Throwable $e ) {
			// یک ویجت معیوب نباید ثبت بقیه یا بارگذاری سایت را متوقف کند.
			error_log( 'Sazan Panel: registering widget ' . $cls . ' failed – ' . $e->getMessage() ); // phpcs:ignore
		}
	}
}
