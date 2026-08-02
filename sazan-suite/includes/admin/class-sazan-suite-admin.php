<?php
/**
 * راه‌اندازی بخش مدیریت سوئیت: منو، دارایی‌ها و بازنشانی صفحه‌های تنظیمات قدیمی.
 *
 * @package Sazan\Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sazan_Suite_Admin {

	/** دسترسی لازم برای دیدن کنترل پنل. */
	const CAP = 'manage_options';

	public static function init() {

		Sazan_Suite_Control_Panel::init();
		Sazan_Suite_Tools::init();

		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 5 );
		add_action( 'admin_menu', array( __CLASS__, 'retire_legacy_pages' ), 999 );
		add_action( 'admin_init', array( __CLASS__, 'redirect_legacy_pages' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'plugin_action_links_' . SAZAN_SUITE_BASENAME, array( __CLASS__, 'action_links' ) );
	}

	/* =====================================================================
	 * منو
	 * =================================================================== */

	public static function menu() {

		add_menu_page(
			'سازان',
			'سازان',
			self::CAP,
			Sazan_Suite_Control_Panel::SLUG,
			array( 'Sazan_Suite_Control_Panel', 'render' ),
			'dashicons-admin-generic',
			24
		);

		add_submenu_page(
			Sazan_Suite_Control_Panel::SLUG,
			'کنترل پنل تنظیمات',
			'کنترل پنل',
			self::CAP,
			Sazan_Suite_Control_Panel::SLUG,
			array( 'Sazan_Suite_Control_Panel', 'render' )
		);

		add_submenu_page(
			Sazan_Suite_Control_Panel::SLUG,
			'ماژول‌های سازان',
			'ماژول‌ها',
			self::CAP,
			Sazan_Suite_Tools::SLUG_MODULES,
			array( 'Sazan_Suite_Tools', 'render_modules' )
		);

		add_submenu_page(
			Sazan_Suite_Control_Panel::SLUG,
			'ابزارها و پشتیبان',
			'ابزارها',
			self::CAP,
			Sazan_Suite_Tools::SLUG_TOOLS,
			array( 'Sazan_Suite_Tools', 'render_tools' )
		);
	}

	/**
	 * نگاشت صفحه‌های تنظیماتِ قدیمیِ هر ماژول به دسته‌ی متناظر در کنترل پنل.
	 *
	 * @return array<string,array{tab:string,parent:string}>
	 */
	public static function legacy_pages() {

		return array(
			'szp-eval-settings'      => array( 'tab' => 'evaluation', 'parent' => 'sazan-panel' ),
			'szc-settings'           => array( 'tab' => 'crm',        'parent' => 'szc' ),
			'sazan-core-settings'    => array( 'tab' => 'general',    'parent' => '' ),
			'spp-global'             => array( 'tab' => 'product',    'parent' => 'edit.php?post_type=product' ),
			'sazan-quiz-settings'    => array( 'tab' => 'engines',    'parent' => 'edit.php?post_type=sazan_quiz' ),
			'sazan-consult-settings' => array( 'tab' => 'engines',    'parent' => 'edit.php?post_type=sazan_consult' ),
			'sazan-form-settings'    => array( 'tab' => 'sms',        'parent' => 'edit.php?post_type=sazan_form' ),
		);
	}

	/**
	 * حذف صفحه‌های تنظیماتِ پراکنده‌ی ماژول‌ها.
	 *
	 * همه‌ی آن‌ها اکنون داخل کنترل پنل یکپارچه هستند. با فیلتر زیر می‌توان
	 * صفحه‌های قدیمی را دوباره برگرداند.
	 */
	public static function retire_legacy_pages() {

		/**
		 * آیا صفحه‌های تنظیمات قدیمیِ ماژول‌ها حذف شوند؟
		 *
		 * @param bool $retire
		 */
		if ( ! apply_filters( 'sazan_suite_retire_legacy_settings', true ) ) {
			return;
		}

		foreach ( self::legacy_pages() as $slug => $meta ) {
			if ( $meta['parent'] ) {
				remove_submenu_page( $meta['parent'], $slug );
			} else {
				remove_menu_page( $slug );
			}
		}

		// میان‌بر به کنترل پنل، از داخل منوی هر ماژول (اگر آن ماژول بارگذاری شده باشد).
		$shortcuts = array(
			'sazan-panel' => 'panel',
			'szc'         => 'crm',
		);

		foreach ( $shortcuts as $parent => $module ) {

			if ( ! Sazan_Suite_Modules::is_loaded( $module ) ) {
				continue;
			}

			add_submenu_page(
				$parent,
				'کنترل پنل سازان',
				'⚙ کنترل پنل سازان',
				self::CAP,
				Sazan_Suite_Control_Panel::SLUG
			);
		}
	}

	/** هدایت نشانی‌های قدیمی به دسته‌ی متناظر در کنترل پنل. */
	public static function redirect_legacy_pages() {

		if ( ! isset( $_GET['page'] ) || wp_doing_ajax() ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$page   = sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$legacy = self::legacy_pages();

		if ( ! isset( $legacy[ $page ] ) || ! current_user_can( self::CAP ) ) {
			return;
		}

		if ( ! apply_filters( 'sazan_suite_retire_legacy_settings', true ) ) {
			return;
		}

		wp_safe_redirect( Sazan_Suite_Control_Panel::url( $legacy[ $page ]['tab'] ) );
		exit;
	}

	/* =====================================================================
	 * دارایی‌ها
	 * =================================================================== */

	/** آیا این صفحه یکی از صفحه‌های سوئیت است؟ */
	private static function is_suite_screen( $hook ) {

		$slugs = array(
			Sazan_Suite_Control_Panel::SLUG,
			Sazan_Suite_Tools::SLUG_MODULES,
			Sazan_Suite_Tools::SLUG_TOOLS,
		);

		foreach ( $slugs as $slug ) {
			if ( false !== strpos( (string) $hook, $slug ) ) {
				return true;
			}
		}

		return false;
	}

	public static function assets( $hook ) {

		if ( ! self::is_suite_screen( $hook ) ) {
			return;
		}

		$css = SAZAN_SUITE_DIR . 'assets/css/control-panel.css';
		$js  = SAZAN_SUITE_DIR . 'assets/js/control-panel.js';

		wp_enqueue_style(
			'sazan-suite-control-panel',
			SAZAN_SUITE_URL . 'assets/css/control-panel.css',
			array( 'dashicons' ),
			file_exists( $css ) ? filemtime( $css ) : SAZAN_SUITE_VERSION
		);

		wp_enqueue_media();

		wp_enqueue_script(
			'sazan-suite-control-panel',
			SAZAN_SUITE_URL . 'assets/js/control-panel.js',
			array(),
			file_exists( $js ) ? filemtime( $js ) : SAZAN_SUITE_VERSION,
			true
		);

		wp_localize_script( 'sazan-suite-control-panel', 'SazanSuite', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'unsaved' => 'تغییرات ذخیره‌نشده دارید. از این صفحه خارج می‌شوید؟',
			'pick'    => 'انتخاب تصویر',
			'use'     => 'استفاده از این تصویر',
		) );
	}

	/** پیوند «کنترل پنل» در فهرست افزونه‌ها. */
	public static function action_links( $links ) {

		array_unshift(
			$links,
			'<a href="' . esc_url( Sazan_Suite_Control_Panel::url() ) . '"><strong>کنترل پنل</strong></a>'
		);

		return $links;
	}
}
