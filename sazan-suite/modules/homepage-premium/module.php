<?php
/**
 * Premium homepage Elementor module.
 *
 * @package Sazan\HomepagePremium
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SAZAN_HOMEPAGE_PREMIUM_VERSION', '2.0.2' );
define( 'SAZAN_HOMEPAGE_PREMIUM_PATH', plugin_dir_path( __FILE__ ) );
define( 'SAZAN_HOMEPAGE_PREMIUM_URL', plugin_dir_url( __FILE__ ) );

require_once SAZAN_HOMEPAGE_PREMIUM_PATH . 'includes/class-sazan-homepage-renderer.php';
require_once SAZAN_HOMEPAGE_PREMIUM_PATH . 'includes/class-sazan-homepage-builder.php';

add_action( 'wp_enqueue_scripts', 'sazan_homepage_premium_register_assets', 5 );
add_action( 'elementor/editor/before_enqueue_scripts', 'sazan_homepage_premium_register_assets', 5 );

function sazan_homepage_premium_register_assets() {
	wp_register_style(
		'sazan-homepage-foundation',
		SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/css/styles.css',
		array(),
		SAZAN_HOMEPAGE_PREMIUM_VERSION
	);
	wp_register_style(
		'sazan-homepage-premium',
		SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/css/premium.css',
		array( 'sazan-homepage-foundation' ),
		SAZAN_HOMEPAGE_PREMIUM_VERSION
	);
	wp_register_script(
		'sazan-homepage-premium',
		SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/js/homepage.js',
		array(),
		SAZAN_HOMEPAGE_PREMIUM_VERSION,
		true
	);
}

add_action( 'elementor/elements/categories_registered', function( $elements_manager ) {
	$elements_manager->add_category(
		'sazan-homepage',
		array(
			'title' => 'سازان — صفحه اصلی پریمیوم',
			'icon'  => 'fa fa-home',
		)
	);
} );

add_action( 'elementor/widgets/register', function( $widgets_manager ) {
	// Keep the previous all-in-one widget renderable for pages created by 2.7.x,
	// while hiding it from the Elementor panel so all new pages use independent widgets.
	$legacy = SAZAN_HOMEPAGE_PREMIUM_PATH . 'includes/class-sazan-homepage-widget.php';
	if ( file_exists( $legacy ) ) {
		require_once $legacy;
		$widgets_manager->register( new \Sazan_Homepage_Premium_Widget() );
	}
	$sections = SAZAN_HOMEPAGE_PREMIUM_PATH . 'includes/class-sazan-homepage-section-widgets.php';
	if ( file_exists( $sections ) ) {
		require_once $sections;
		foreach ( sazan_homepage_section_widget_classes() as $class ) {
			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	}
} );

add_shortcode( 'sazan_premium_homepage', function( $atts = array() ) {
	wp_enqueue_style( 'sazan-homepage-premium' );
	wp_enqueue_script( 'sazan-homepage-premium' );
	return Sazan_Homepage_Premium_Renderer::html( (array) $atts );
} );

Sazan_Homepage_Premium_Builder::init();
