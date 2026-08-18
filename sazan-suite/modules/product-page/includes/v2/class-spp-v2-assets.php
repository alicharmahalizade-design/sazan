<?php
/** Scoped, conditional assets for Product Page v2. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SPP_V2_Assets {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'isolate_visual_styles' ), PHP_INT_MAX );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'isolate_theme_scripts' ), PHP_INT_MAX );
		add_action( 'wp_print_styles', array( __CLASS__, 'isolate_visual_styles' ), PHP_INT_MAX );
		add_action( 'wp_footer', array( __CLASS__, 'isolate_visual_styles' ), 0 );
		add_action( 'wp_footer', array( __CLASS__, 'isolate_theme_scripts' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'isolate_theme_head' ), 99 );
		add_filter( 'style_loader_tag', array( __CLASS__, 'filter_visual_style_tag' ), PHP_INT_MAX, 4 );
		add_action( 'elementor/frontend/after_register_styles', array( __CLASS__, 'register' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( __CLASS__, 'register' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		if ( ! wp_style_is( 'spp-v2-front', 'registered' ) ) {
			wp_register_style( 'spp-v2-front', SPP_URL . 'assets/css/spp-v2-front.css', array(), SPP_VERSION );
		}
		if ( ! wp_script_is( 'spp-v2-front', 'registered' ) ) {
			wp_register_script( 'spp-v2-front', SPP_URL . 'assets/js/spp-v2-front.js', array(), SPP_VERSION, true );
			wp_script_add_data( 'spp-v2-front', 'strategy', 'defer' );
			wp_localize_script( 'spp-v2-front', 'SPPV2', array(
				'ajax' => admin_url( 'admin-ajax.php' ),
				'reviewsNonce' => wp_create_nonce( SPP_V2_Reviews_Ajax::NONCE ),
				'i18n' => array( 'loading' => 'در حال دریافت…', 'more' => 'نمایش نظرهای بیشتر', 'error' => 'دریافت نظرها ناموفق بود.' ),
			) );
		}
		if ( ! wp_style_is( 'spp-v3-front', 'registered' ) ) {
			wp_register_style( 'spp-v3-front', SPP_URL . 'assets/css/spp-v3-front.css', array(), SPP_VERSION );
		}
		if ( ! wp_script_is( 'spp-v3-front', 'registered' ) ) {
			wp_register_script( 'spp-v3-front', SPP_URL . 'assets/js/spp-v3-front.js', array(), SPP_VERSION, true );
			wp_script_add_data( 'spp-v3-front', 'strategy', 'defer' );
			wp_localize_script( 'spp-v3-front', 'SPPV3', array(
				'ajax'        => admin_url( 'admin-ajax.php' ),
				'reviewNonce' => wp_create_nonce( SPP_Reviews::NONCE ),
				'i18n'        => array(
					'sending' => 'در حال ارسال نظر…',
					'success' => 'نظر شما ثبت شد و پس از تأیید نمایش داده می‌شود.',
					'error'   => 'ارسال نظر انجام نشد. دوباره تلاش کنید.',
				),
			) );
		}
	}

	public static function should_enqueue() {
		if ( SPP_V2_Template::is_active_request() ) {
			return true;
		}
		$preview_id = isset( $_GET['elementor-preview'] ) ? absint( $_GET['elementor-preview'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $preview_id && $preview_id === SPP_V2_Template::template_id();
	}

	public static function enqueue() {
		self::register();
		if ( self::should_enqueue() ) {
			wp_enqueue_style( 'spp-v3-front' );
			wp_enqueue_script( 'spp-v3-front' );
		}
	}

	/**
	 * The V3 canvas is a complete visual document. Keeping theme, block and
	 * WooCommerce styles in the queue makes generic selectors such as
	 * `.container`, `h1` and `button` alter the approved layout. On the active
	 * canvas retain only the V3 stylesheet and the WordPress toolbar styles.
	 */
	public static function isolate_visual_styles() {
		if ( ! SPP_V2_Template::is_active_request() || isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		self::register();
		wp_enqueue_style( 'spp-v3-front' );

		$allowed = array( 'spp-v3-front', 'admin-bar', 'dashicons' );
		$styles  = wp_styles();
		if ( ! $styles || empty( $styles->queue ) ) {
			return;
		}

		foreach ( array_values( $styles->queue ) as $handle ) {
			if ( ! in_array( $handle, $allowed, true ) ) {
				wp_dequeue_style( $handle );
			}
		}
	}

	/** Remove theme Customizer CSS, which is printed outside the style queue. */
	public static function isolate_theme_head() {
		if ( ! SPP_V2_Template::is_active_request() || isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		remove_action( 'wp_head', 'wp_custom_css_cb', 101 );
	}

	/** Prevent theme JavaScript from targeting generic V3 class names. */
	public static function isolate_theme_scripts() {
		if ( ! SPP_V2_Template::is_active_request() || isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$scripts = wp_scripts();
		if ( ! $scripts || empty( $scripts->queue ) ) {
			return;
		}

		$theme_paths = array_filter( array_unique( array(
			wp_parse_url( get_template_directory_uri(), PHP_URL_PATH ),
			wp_parse_url( get_stylesheet_directory_uri(), PHP_URL_PATH ),
		) ) );

		foreach ( array_values( $scripts->queue ) as $handle ) {
			if ( empty( $scripts->registered[ $handle ] ) ) {
				continue;
			}
			$src_path = wp_parse_url( $scripts->registered[ $handle ]->src, PHP_URL_PATH );
			foreach ( $theme_paths as $theme_path ) {
				if ( $src_path && 0 === strpos( $src_path, $theme_path ) ) {
					wp_dequeue_script( $handle );
					break;
				}
			}
		}
	}

	/**
	 * Final fail-closed guard for styles enqueued after the normal print pass
	 * (optimizers and some themes do this late in wp_head/wp_footer).
	 */
	public static function filter_visual_style_tag( $html, $handle, $href, $media ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! SPP_V2_Template::is_active_request() || isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $html;
		}

		$allowed = array( 'spp-v3-front', 'admin-bar', 'dashicons' );
		return in_array( $handle, $allowed, true ) ? $html : '';
	}
}
