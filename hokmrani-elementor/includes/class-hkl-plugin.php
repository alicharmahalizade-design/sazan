<?php
/**
 * Core bootstrap: assets, widgets, page template and body classes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HKL_Plugin {

	const HANDLE   = 'hokmrani-landing';
	const CATEGORY = 'hokmrani-landing';

	/** @var HKL_Plugin|null */
	private static $instance = null;

	/** @var bool */
	private static $sprite_printed = false;

	/**
	 * Widget slug => class name (file: includes/widgets/class-hkl-widget-{slug}.php).
	 * The order is the order of the sections on the original page.
	 */
	const WIDGETS = [
		'header'      => 'HKL_Widget_Header',
		'hero'        => 'HKL_Widget_Hero',
		'logos'       => 'HKL_Widget_Logos',
		'results'     => 'HKL_Widget_Results',
		'diagnosis'   => 'HKL_Widget_Diagnosis',
		'roadmap'     => 'HKL_Widget_Roadmap',
		'program'     => 'HKL_Widget_Program',
		'chapters'    => 'HKL_Widget_Chapters',
		'stories'     => 'HKL_Widget_Stories',
		'teachers'    => 'HKL_Widget_Teachers',
		'guarantee'   => 'HKL_Widget_Guarantee',
		'investment'  => 'HKL_Widget_Investment',
		'fit'         => 'HKL_Widget_Fit',
		'final-cta'   => 'HKL_Widget_Final_Cta',
		'footer'      => 'HKL_Widget_Footer',
		'enrollment'  => 'HKL_Widget_Enrollment',
	];

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', [ $this, 'register_assets' ] );
		add_action( 'admin_notices', [ $this, 'elementor_notice' ] );

		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
		if ( defined( 'ELEMENTOR_VERSION' ) && version_compare( ELEMENTOR_VERSION, '3.5.0', '<' ) ) {
			add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets' ] );
		} else {
			add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		}

		add_filter( 'theme_page_templates', [ $this, 'add_page_template' ] );
		add_filter( 'template_include', [ $this, 'template_include' ], 999 );
		add_filter( 'body_class', [ $this, 'body_class' ], 99999 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_for_template' ], 9999 );
		add_action( 'wp_print_styles', [ $this, 'isolate_template_assets' ], 1 );
		add_action( 'wp_footer', [ $this, 'maybe_print_sprite' ], 1 );
	}

	/* ------------------------------------------------------------------ */
	/* Helpers                                                              */
	/* ------------------------------------------------------------------ */

	public static function asset_url( $path ) {
		return HKL_URL . 'assets/' . ltrim( $path, '/' );
	}

	public static function is_elementor_active() {
		return did_action( 'elementor/loaded' ) || defined( 'ELEMENTOR_VERSION' );
	}

	/** Is the current request rendering a post that uses the landing canvas template? */
	public static function is_landing_template() {
		if ( ! is_singular() ) {
			return false;
		}
		return HKL_TEMPLATE === get_page_template_slug( get_queried_object_id() );
	}

	/** Does the given post contain at least one landing widget? */
	public static function post_uses_widgets( $post_id ) {
		if ( ! $post_id ) {
			return false;
		}
		$data = get_post_meta( $post_id, '_elementor_data', true );
		if ( is_array( $data ) ) {
			$data = wp_json_encode( $data );
		}
		return is_string( $data ) && false !== strpos( $data, '"widgetType":"hk-' );
	}

	/* ------------------------------------------------------------------ */
	/* Assets                                                               */
	/* ------------------------------------------------------------------ */

	public function register_assets() {
		wp_register_style( self::HANDLE, self::asset_url( 'css/landing.css' ), [], HKL_VERSION );
		wp_register_script( self::HANDLE, self::asset_url( 'js/landing.js' ), [], HKL_VERSION, true );
		wp_localize_script(
			self::HANDLE,
			'HKL',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	public function enqueue_for_template() {
		if ( self::is_landing_template() ) {
			wp_enqueue_style( self::HANDLE );
			wp_enqueue_script( self::HANDLE );
		}
		$this->isolate_template_assets();
	}

	/**
	 * On the landing canvas the theme's (and block library's) styles would change the
	 * typography and spacing of the page, so they are removed there.
	 */
	public function isolate_template_assets() {
		if ( ! self::is_landing_template() ) {
			return;
		}
		$theme_urls = array_unique( [ get_template_directory_uri(), get_stylesheet_directory_uri() ] );
		$blocked    = [ 'global-styles', 'wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'core-block-supports' ];

		foreach ( [ wp_styles(), wp_scripts() ] as $deps ) {
			foreach ( (array) $deps->queue as $handle ) {
				if ( self::HANDLE === $handle ) {
					continue;
				}
				$src    = isset( $deps->registered[ $handle ] ) ? (string) $deps->registered[ $handle ]->src : '';
				$remove = in_array( $handle, $blocked, true );
				foreach ( $theme_urls as $url ) {
					if ( $src && 0 === strpos( $src, $url ) ) {
						$remove = true;
					}
				}
				/**
				 * Filter whether an asset is removed from the landing canvas.
				 *
				 * @param bool   $remove Remove it.
				 * @param string $handle Asset handle.
				 * @param string $src    Asset URL.
				 */
				if ( apply_filters( 'hkl_remove_asset_on_canvas', $remove, $handle, $src ) ) {
					if ( $deps instanceof WP_Styles ) {
						wp_dequeue_style( $handle );
					} else {
						wp_dequeue_script( $handle );
					}
				}
			}
		}
	}

	/* ------------------------------------------------------------------ */
	/* SVG sprite (icons referenced with <use href="#i-…">)                */
	/* ------------------------------------------------------------------ */

	public static function print_sprite() {
		if ( self::$sprite_printed ) {
			return;
		}
		self::$sprite_printed = true;
		include HKL_DIR . 'templates/sprite.php';
	}

	public function maybe_print_sprite() {
		if ( wp_style_is( self::HANDLE, 'done' ) || wp_style_is( self::HANDLE, 'enqueued' ) ) {
			self::print_sprite();
		}
	}

	/* ------------------------------------------------------------------ */
	/* Page template                                                        */
	/* ------------------------------------------------------------------ */

	public function add_page_template( $templates ) {
		$templates[ HKL_TEMPLATE ] = 'حکمرانی بر بازار — بوم کامل لندینگ';
		return $templates;
	}

	public function template_include( $template ) {
		if ( self::is_landing_template() ) {
			return HKL_DIR . 'templates/canvas.php';
		}
		return $template;
	}

	public function body_class( $classes ) {
		$landing = self::is_landing_template();
		if ( $landing || ( is_singular() && self::post_uses_widgets( get_queried_object_id() ) ) ) {
			$classes[] = 'hk-page';
			$classes[] = 'hk-dv2';
			$classes[] = 'is-version-4';
		}
		if ( $landing ) {
			// Elementor's global kit styles (fonts, colours, buttons) must not leak into the landing.
			$classes = array_values(
				array_filter(
					$classes,
					static function ( $class ) {
						return ! preg_match( '/^elementor-kit-\d+$/', $class );
					}
				)
			);
		}
		return array_unique( $classes );
	}

	/* ------------------------------------------------------------------ */
	/* Elementor                                                            */
	/* ------------------------------------------------------------------ */

	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			self::CATEGORY,
			[
				'title' => 'لندینگ حکمرانی بر بازار',
				'icon'  => 'eicon-site-identity',
			]
		);
	}

	public static function load_widget_classes() {
		require_once HKL_DIR . 'includes/class-hkl-widget-base.php';
		foreach ( self::WIDGETS as $slug => $class ) {
			require_once HKL_DIR . 'includes/widgets/class-hkl-widget-' . $slug . '.php';
		}
	}

	public function register_widgets( $widgets_manager ) {
		self::load_widget_classes();
		foreach ( self::WIDGETS as $class ) {
			if ( method_exists( $widgets_manager, 'register' ) ) {
				$widgets_manager->register( new $class() );
			} else {
				$widgets_manager->register_widget_type( new $class() );
			}
		}
	}

	public function elementor_notice() {
		if ( self::is_elementor_active() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html( 'افزونه «لندینگ حکمرانی بر بازار» برای کار به افزونه المنتور (Elementor) نیاز دارد. لطفاً المنتور را نصب و فعال کنید.' ) . '</p></div>';
	}
}
