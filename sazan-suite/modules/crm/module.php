<?php
/**
 * ماژول «CRM» از سوئیت سازان.
 *
 * این فایل قبلاً فایل اصلی افزونه‌ی مستقل «سازان CRM» بود و اکنون به‌عنوان یک
 * ماژول از داخل sazan-suite.php بارگذاری می‌شود. زمان‌بندی بارگذاری دقیقاً
 * مانند قبل است (لود در زمان بارگذاری افزونه، راه‌اندازی روی plugins_loaded)
 * تا رفتار ماژول تغییری نکند.
 *
 * @package Sazan\Suite\Modules\CRM
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SZC_VERSION', '1.27.1' );
define( 'SZC_FILE', __FILE__ );
define( 'SZC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SZC_URL', plugin_dir_url( __FILE__ ) );
define( 'SZC_PREFIX', 'szc' );

require_once SZC_DIR . 'includes/functions.php';
require_once SZC_DIR . 'includes/class-szc-install.php';
require_once SZC_DIR . 'includes/class-szc-settings.php';
require_once SZC_DIR . 'includes/class-szc-agents.php';
require_once SZC_DIR . 'includes/class-szc-auth.php';
require_once SZC_DIR . 'includes/class-szc-audit.php';
require_once SZC_DIR . 'includes/class-szc-contacts.php';
require_once SZC_DIR . 'includes/class-szc-groups.php';
require_once SZC_DIR . 'includes/class-szc-activity.php';
require_once SZC_DIR . 'includes/class-szc-templates.php';
require_once SZC_DIR . 'includes/class-szc-blacklist.php';
require_once SZC_DIR . 'includes/class-szc-sms.php';
require_once SZC_DIR . 'includes/class-szc-sequences.php';
require_once SZC_DIR . 'includes/class-szc-segments.php';
require_once SZC_DIR . 'includes/class-szc-reports.php';
require_once SZC_DIR . 'includes/class-szc-goals.php';
require_once SZC_DIR . 'includes/class-szc-workspace.php';
require_once SZC_DIR . 'includes/class-szc-conversation.php';
require_once SZC_DIR . 'includes/class-szc-import.php';
require_once SZC_DIR . 'includes/frontend/class-szc-portal.php';

// در درخواست‌های admin-ajax نیز is_admin()=true است؛ پورتال فرانت‌اند از همان
// هندلرهای AJAXِ SZC_Admin استفاده می‌کند، پس این کلاس‌ها آن‌جا لود می‌شوند.
if ( is_admin() ) {
	require_once SZC_DIR . 'includes/admin/class-szc-admin.php';
	require_once SZC_DIR . 'includes/admin/class-szc-admin-pages.php';
}

// فعال‌سازی/غیرفعال‌سازی از طریق Sazan_Suite_Modules مدیریت می‌شود.

add_action( 'plugins_loaded', 'szc_init' );
function szc_init() {
	load_plugin_textdomain( 'sazan-crm', false, dirname( plugin_basename( SZC_FILE ) ) . '/languages' );

	SZC_Settings::init();
	SZC_Install::maybe_upgrade();
	SZC_Auth::init();
	SZC_SMS::init();
	SZC_Portal::init();

	// صف پیامک: جاروب هر ۵ دقیقه برای ارسال پیامک‌های زمان‌بندی‌شده (اتوماسیون/زمان‌بندی‌شده).
	add_action( SZC_SMS::HOOK_SWEEP, array( 'SZC_SMS', 'run_queue' ) );
	add_action( SZC_SMS::HOOK_SWEEP, array( 'SZC_Activity', 'run_followup_reminders' ) );
	add_action( SZC_SMS::HOOK_SWEEP, array( 'SZC_Goals', 'run_alerts' ) );
	if ( ! wp_next_scheduled( SZC_SMS::HOOK_SWEEP ) ) {
		wp_schedule_event( time() + 60, 'szc_5min', SZC_SMS::HOOK_SWEEP );
	}

	if ( is_admin() ) {
		add_action( 'admin_init', array( 'SZC_Install', 'maybe_upgrade' ) );
		SZC_Admin::init();
		SZC_Admin_Pages::init();
	}
}

/** بازه‌ی کرون ۵ دقیقه‌ای برای صف پیامک. */
add_filter( 'cron_schedules', 'szc_cron_schedules' );
function szc_cron_schedules( $schedules ) {
	if ( ! isset( $schedules['szc_5min'] ) ) {
		$schedules['szc_5min'] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => 'هر ۵ دقیقه (سازان CRM)',
		);
	}
	return $schedules;
}
