<?php
/**
 * ماژول «صفحه اختصاصی محصول» از سوئیت سازان.
 *
 * این فایل قبلاً فایل اصلی افزونه‌ی مستقل «سازان — صفحه اختصاصی محصول» بود و
 * اکنون به‌عنوان یک ماژول از داخل sazan-suite.php بارگذاری می‌شود.
 *
 * @package Sazan\Suite\Modules\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // دسترسی مستقیم ممنوع
}

/* ثابت‌ها ------------------------------------------------------------------ */
define( 'SPP_VERSION', '1.6.0' );
define( 'SPP_FILE', __FILE__ );
define( 'SPP_PATH', plugin_dir_path( __FILE__ ) );
define( 'SPP_URL', plugin_dir_url( __FILE__ ) );

/**
 * بارگذاری افزونه.
 */
function spp_boot() {

	require_once SPP_PATH . 'includes/functions.php';
	require_once SPP_PATH . 'includes/class-spp-sections.php';
	require_once SPP_PATH . 'includes/class-spp-fields.php';
	require_once SPP_PATH . 'includes/class-spp-settings.php';
	require_once SPP_PATH . 'includes/class-spp-metabox.php';
	require_once SPP_PATH . 'includes/class-spp-assets.php';
	require_once SPP_PATH . 'includes/class-spp-reviews.php';

	// سکشن‌ها — با اضافه شدن هر سکشن جدید، فقط یک خط به این فهرست اضافه می‌شود.
	require_once SPP_PATH . 'includes/sections/class-spp-section-hero.php';
	require_once SPP_PATH . 'includes/sections/class-spp-section-about.php';
	require_once SPP_PATH . 'includes/sections/class-spp-section-curriculum.php';
	require_once SPP_PATH . 'includes/sections/class-spp-section-instructor.php';
	require_once SPP_PATH . 'includes/sections/class-spp-section-skills.php';
	require_once SPP_PATH . 'includes/sections/class-spp-section-testimonials.php';
	require_once SPP_PATH . 'includes/sections/class-spp-section-faq.php';

	SPP_Sections::register( new SPP_Section_Hero() );
	SPP_Sections::register( new SPP_Section_About() );
	SPP_Sections::register( new SPP_Section_Curriculum() );
	SPP_Sections::register( new SPP_Section_Instructor() );
	SPP_Sections::register( new SPP_Section_Skills() );
	SPP_Sections::register( new SPP_Section_Testimonials() );
	SPP_Sections::register( new SPP_Section_FAQ() );

	SPP_Reviews::init();
	SPP_Settings::init();
	SPP_Metabox::init();
	SPP_Assets::init();

	// ویجت‌های المنتور.
	add_action( 'elementor/elements/categories_registered', 'spp_elementor_category' );
	add_action( 'elementor/widgets/register', 'spp_elementor_widgets' );

	// شورت‌کد: [spp_section id="hero" product="12"]
	add_shortcode( 'spp_section', 'spp_shortcode_section' );

	load_plugin_textdomain( 'sazan-product-page', false, dirname( plugin_basename( SPP_FILE ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'spp_boot' );

/**
 * اعلام سازگاری با HPOS ووکامرس.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		// سازگاری باید با فایل اصلیِ افزونه (سوئیت) اعلام شود، نه با فایل ماژول.
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SAZAN_SUITE_FILE, true );
	}
} );

/**
 * دسته‌بندی ویجت‌ها در المنتور.
 *
 * @param \Elementor\Elements_Manager $manager
 */
function spp_elementor_category( $manager ) {
	$manager->add_category( 'sazan-product', array(
		'title' => 'سازان — صفحه محصول',
		'icon'  => 'eicon-product-info',
	) );
}

/**
 * ثبت یک ویجت به ازای هر سکشنِ ثبت‌شده.
 *
 * @param \Elementor\Widgets_Manager $widgets_manager
 */
function spp_elementor_widgets( $widgets_manager ) {
	require_once SPP_PATH . 'includes/elementor/class-spp-widget-base.php';
	require_once SPP_PATH . 'includes/elementor/widgets.php';

	foreach ( spp_elementor_widget_map() as $class ) {
		if ( class_exists( $class ) ) {
			$widgets_manager->register( new $class() );
		}
	}
}

/**
 * شورت‌کدِ رندر سکشن.
 *
 * @param array $atts
 * @return string
 */
function spp_shortcode_section( $atts ) {
	$atts = shortcode_atts( array(
		'id'      => 'hero',
		'product' => 0,
	), $atts, 'spp_section' );

	$post_id = (int) $atts['product'];
	if ( ! $post_id ) {
		$post_id = get_the_ID();
	}

	return SPP_Sections::render( $atts['id'], $post_id );
}
