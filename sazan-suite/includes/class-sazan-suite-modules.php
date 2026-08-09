<?php
/**
 * مدیریت ماژول‌های سوئیت سازان.
 *
 * چهار افزونه‌ی قبلی اینجا به «ماژول» تبدیل شده‌اند. هر ماژول می‌تواند بدون
 * دست‌زدن به کد، از کنترل پنل روشن یا خاموش شود.
 *
 * @package Sazan\Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sazan_Suite_Modules {

	/** گزینه‌ی وضعیت روشن/خاموش ماژول‌ها. */
	const OPTION = 'sazan_suite_modules';

	/** گزینه‌ی نسخه‌ی نصب‌شده‌ی سوئیت (برای ارتقاهای آینده). */
	const OPTION_VERSION = 'sazan_suite_version';

	/** @var array<string,string> ماژول‌هایی که به‌خاطر تعارض بارگذاری نشدند. */
	private static $conflicts = array();

	/** @var array<string,bool> ماژول‌هایی که واقعاً در این درخواست بارگذاری شدند. */
	private static $loaded = array();

	/* =====================================================================
	 * تعریف ماژول‌ها
	 * =================================================================== */

	/**
	 * فهرست کامل ماژول‌ها به همراه شناسنامه‌ی هرکدام.
	 *
	 * @return array<string,array>
	 */
	public static function all() {
		return array(
			'core' => array(
				'label'       => 'هسته و ویجت‌ها',
				'description' => 'ویجت‌های اختصاصی المنتور، آزمون‌ساز، فرم‌ساز، رزرو مشاوره، پادکست، جست‌وجوی زنده و سبد خرید شناور.',
				'icon'        => 'dashicons-screenoptions',
				'sentinel'    => 'SAZAN_CORE_VERSION',
				'legacy'      => 'sazan-core/sazan-core.php',
				'legacy_name' => 'Sazan Core',
				'activate'    => 'sazan_core_activate',
				'deactivate'  => '',
				'requires'    => array( 'elementor' ),
			),
			'panel' => array(
				'label'       => 'پنل کاربری و ارزیابی',
				'description' => 'دوره‌ها، جلسات، تکالیف، اتاق گفتگو، بوم طراحی خدمت، کوچینگ و داشبورد ارزیابی هفتگی کاربر.',
				'icon'        => 'dashicons-welcome-learn-more',
				'sentinel'    => 'SZP_VERSION',
				'legacy'      => 'sazan-panel/sazan-panel.php',
				'legacy_name' => 'سازان پنل',
				'activate'    => array( 'SZP_Install', 'activate' ),
				'deactivate'  => array( 'SZP_Install', 'deactivate' ),
				'requires'    => array(),
			),
			'crm' => array(
				'label'       => 'CRM تیم فروش',
				'description' => 'مخاطبین، قیف فروش، پیگیری‌ها، ثبت تماس، دنباله‌های پیامکی، گزارش‌ها و پورتال کارشناسان فروش.',
				'icon'        => 'dashicons-phone',
				'sentinel'    => 'SZC_VERSION',
				'legacy'      => 'sazan-crm/sazan-crm.php',
				'legacy_name' => 'سازان CRM',
				'activate'    => array( 'SZC_Install', 'activate' ),
				'deactivate'  => array( 'SZC_Install', 'deactivate' ),
				'requires'    => array(),
			),
			'product-page' => array(
				'label'       => 'صفحه اختصاصی محصول',
				'description' => 'ساخت صفحه‌ی تکی محصول به‌صورت سکشن‌سکشن، با ویجت المنتور برای هر سکشن و نظرات اختصاصی دوره.',
				'icon'        => 'dashicons-cart',
				'sentinel'    => 'SPP_VERSION',
				'legacy'      => 'sazan-product-page/sazan-product-page.php',
				'legacy_name' => 'سازان — صفحه اختصاصی محصول',
				'activate'    => '',
				'deactivate'  => '',
				'requires'    => array( 'woocommerce' ),
			),
		);
	}

	/** برچسب یک ماژول. */
	public static function label( $slug ) {
		$all = self::all();
		return isset( $all[ $slug ] ) ? $all[ $slug ]['label'] : $slug;
	}

	/**
	 * وضعیت ذخیره‌شده‌ی ماژول‌ها ([slug => 1|0]). ماژول ناشناخته حذف می‌شود و
	 * ماژول تازه‌اضافه‌شده به‌طور پیش‌فرض روشن است.
	 *
	 * @return array<string,int>
	 */
	public static function states() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		$out   = array();

		foreach ( self::all() as $slug => $def ) {
			$out[ $slug ] = array_key_exists( $slug, $saved ) ? ( empty( $saved[ $slug ] ) ? 0 : 1 ) : 1;
		}

		return $out;
	}

	/** آیا این ماژول در تنظیمات روشن است؟ */
	public static function is_enabled( $slug ) {
		$states = self::states();
		return ! empty( $states[ $slug ] );
	}

	/** آیا این ماژول واقعاً در این درخواست بارگذاری شد؟ */
	public static function is_loaded( $slug ) {
		return ! empty( self::$loaded[ $slug ] );
	}

	/** ذخیره‌ی وضعیت روشن/خاموش ماژول‌ها. */
	public static function save_states( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = array();

		foreach ( self::all() as $slug => $def ) {
			$out[ $slug ] = empty( $input[ $slug ] ) ? 0 : 1;
		}

		update_option( self::OPTION, $out );

		// روشن‌شدن یک ماژول ممکن است CPT یا rewrite جدید بیاورد.
		update_option( 'sazan_suite_flush_rewrite', 1 );

		return $out;
	}

	/* =====================================================================
	 * پیش‌نیازها
	 * =================================================================== */

	/** آیا پیش‌نیاز مشخص‌شده در دسترس است؟ */
	public static function dependency_met( $dep ) {
		switch ( $dep ) {
			case 'elementor':
				return did_action( 'elementor/loaded' ) || defined( 'ELEMENTOR_VERSION' );
			case 'woocommerce':
				return class_exists( 'WooCommerce' );
		}
		return true;
	}

	/** نام خوانای یک پیش‌نیاز. */
	public static function dependency_label( $dep ) {
		$labels = array(
			'elementor'   => 'المنتور',
			'woocommerce' => 'ووکامرس',
		);
		return isset( $labels[ $dep ] ) ? $labels[ $dep ] : $dep;
	}

	/** پیش‌نیازهای نصب‌نشده‌ی یک ماژول. */
	public static function missing_dependencies( $slug ) {
		$all = self::all();
		if ( ! isset( $all[ $slug ] ) ) {
			return array();
		}

		$missing = array();
		foreach ( $all[ $slug ]['requires'] as $dep ) {
			if ( ! self::dependency_met( $dep ) ) {
				$missing[] = $dep;
			}
		}

		return $missing;
	}

	/* =====================================================================
	 * بارگذاری
	 * =================================================================== */

	/**
	 * بارگذاری ماژول‌های روشن.
	 *
	 * اگر نسخه‌ی مستقلِ قدیمیِ همان افزونه هنوز فعال باشد، ماژول بارگذاری
	 * نمی‌شود تا خطای «کلاس تکراری» رخ ندهد؛ در عوض هشدار نشان داده می‌شود.
	 */
	public static function load() {

		$states = self::states();

		foreach ( self::all() as $slug => $def ) {

			if ( empty( $states[ $slug ] ) ) {
				continue;
			}

			// نسخه‌ی مستقل قدیمی هنوز فعال است؟
			if ( defined( $def['sentinel'] ) ) {
				self::$conflicts[ $slug ] = $def['legacy_name'];
				continue;
			}

			$file = SAZAN_SUITE_DIR . 'modules/' . $slug . '/module.php';
			if ( ! file_exists( $file ) ) {
				continue;
			}

			require_once $file;
			self::$loaded[ $slug ] = true;
		}

		if ( self::$conflicts ) {
			add_action( 'admin_notices', array( __CLASS__, 'conflict_notice' ) );
		}

		add_action( 'admin_init', array( __CLASS__, 'maybe_flush_rewrite' ), 99 );
	}

	/** هشدار فعال‌بودن هم‌زمان افزونه‌ی مستقل قدیمی. */
	public static function conflict_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p><strong>سازان سوئیت:</strong> این افزونه‌های مستقل هنوز فعال هستند و با ماژول‌های هم‌نامشان تداخل دارند؛ ماژول‌ها تا غیرفعال‌شدن آن‌ها بارگذاری نمی‌شوند: '
			. esc_html( implode( '، ', array_values( self::$conflicts ) ) )
			. '. لطفاً از صفحه‌ی «افزونه‌ها» آن‌ها را غیرفعال کنید.</p></div>';
	}

	/** پس از تغییر وضعیت ماژول‌ها، بازنویسی قواعد پیوند یکتا. */
	public static function maybe_flush_rewrite() {
		if ( get_option( 'sazan_suite_flush_rewrite' ) ) {
			delete_option( 'sazan_suite_flush_rewrite' );
			flush_rewrite_rules();
		}
	}

	/* =====================================================================
	 * فعال‌سازی / غیرفعال‌سازی / ارتقا
	 * =================================================================== */

	/** فعال‌سازی افزونه: اجرای نصب‌کننده‌ی همه‌ی ماژول‌های روشن. */
	public static function activate() {

		self::deactivate_legacy_plugins();

		foreach ( self::all() as $slug => $def ) {

			if ( ! self::is_enabled( $slug ) || ! $def['activate'] ) {
				continue;
			}

			if ( is_callable( $def['activate'] ) ) {
				call_user_func( $def['activate'] );
			}
		}

		update_option( self::OPTION_VERSION, SAZAN_SUITE_VERSION );
		flush_rewrite_rules();
	}

	/** غیرفعال‌سازی افزونه: پاک‌کردن زمان‌بندی‌های هر ماژول. */
	public static function deactivate() {

		foreach ( self::all() as $slug => $def ) {

			if ( ! $def['deactivate'] || ! is_callable( $def['deactivate'] ) ) {
				continue;
			}

			call_user_func( $def['deactivate'] );
		}

		flush_rewrite_rules();
	}

	/**
	 * غیرفعال‌کردن خودکار نسخه‌های مستقلِ قدیمی هنگام فعال‌سازی سوئیت.
	 *
	 * داده‌ها دست‌نخورده می‌مانند؛ فقط افزونه‌ی تکراری خاموش می‌شود.
	 */
	public static function deactivate_legacy_plugins() {

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$legacy = array();
		foreach ( self::all() as $def ) {
			if ( $def['legacy'] && is_plugin_active( $def['legacy'] ) ) {
				$legacy[] = $def['legacy'];
			}
		}

		if ( $legacy ) {
			deactivate_plugins( $legacy, true );
		}
	}

	/** ارتقاهای نسخه‌ای سوئیت + نصب ماژول‌هایی که تازه روشن شده‌اند. */
	public static function maybe_upgrade() {

		$stored = (string) get_option( self::OPTION_VERSION, '' );

		if ( $stored === SAZAN_SUITE_VERSION ) {
			return;
		}

		// نخستین اجرا پس از ارتقا از افزونه‌های مستقل: نصب‌کننده‌ها را اجرا کن
		// تا جدول‌های تازه (در صورت وجود) ساخته شوند. نصب‌کننده‌ها idempotent هستند.
		foreach ( self::all() as $slug => $def ) {

			if ( ! self::is_loaded( $slug ) || ! $def['activate'] || ! is_callable( $def['activate'] ) ) {
				continue;
			}

			call_user_func( $def['activate'] );
		}

		update_option( self::OPTION_VERSION, SAZAN_SUITE_VERSION );
		update_option( 'sazan_suite_flush_rewrite', 1 );
	}
}
