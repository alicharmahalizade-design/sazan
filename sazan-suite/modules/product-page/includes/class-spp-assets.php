<?php
/**
 * ثبت و بارگذاری فایل‌های CSS/JS.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Assets {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'front' ) );
		add_action( 'elementor/frontend/after_enqueue_styles', array( __CLASS__, 'register_front' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( __CLASS__, 'register_front' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin' ) );
	}

	/**
	 * ثبت (بدون صف) تا ویجت‌های المنتور بتوانند به‌عنوان depend استفاده کنند.
	 */
	public static function register_front() {
		if ( ! wp_style_is( 'spp-front', 'registered' ) ) {
			wp_register_style( 'spp-front', SPP_URL . 'assets/css/spp-front.css', array(), SPP_VERSION );
		}
		if ( ! wp_script_is( 'spp-front', 'registered' ) ) {
			wp_register_script( 'spp-front', SPP_URL . 'assets/js/spp-front.js', array(), SPP_VERSION, true );

			wp_localize_script( 'spp-front', 'SPP', array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( class_exists( 'SPP_Reviews' ) ? SPP_Reviews::NONCE : 'spp_review' ),
				'i18n'  => array(
					'sending' => 'در حال ارسال…',
					'error'   => 'ارسال ناموفق بود. دوباره تلاش کنید.',
				),
			) );
		}
	}

	public static function front() {
		self::register_front();

		// روی صفحه‌ی محصول همیشه لود شود (چون شورت‌کد/قالب هم ممکن است استفاده شود).
		if ( is_singular( self::post_types() ) ) {
			wp_enqueue_style( 'spp-front' );
			wp_enqueue_script( 'spp-front' );
		}
	}

	public static function admin( $hook ) {

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// صفحه‌ی ویرایش محصول، یا صفحه‌ی تنظیمات سراسری.
		$is_edit     = $screen && in_array( $screen->post_type, self::post_types(), true )
			&& in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$is_settings = isset( $_GET['page'] ) && SPP_Settings::SLUG === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $is_edit && ! $is_settings ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'spp-admin', SPP_URL . 'assets/css/spp-admin.css', array(), SPP_VERSION );
		wp_enqueue_script( 'spp-admin', SPP_URL . 'assets/js/spp-admin.js', array( 'jquery', 'jquery-ui-sortable' ), SPP_VERSION, true );
	}

	private static function post_types() {
		return class_exists( 'SPP_Metabox' ) ? SPP_Metabox::post_types() : array( 'product' );
	}
}
