<?php
/**
 * مدیریت و ثبت ویجت‌ها.
 *
 * برای افزودن یک ویجت جدید کافی است:
 *   1) یک پوشه در widgets/ بسازی (مثلاً widgets/my-widget/)
 *   2) داخلش فایل class-sazan-my-widget.php با یک کلاس که از
 *      \Sazan\Base\Widget_Base ارث‌بری می‌کند قرار بدی.
 *   3) نام کلاس را در آرایه‌ی $widgets پایین اضافه کنی.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Widgets_Manager {

	/** @var Widgets_Manager|null */
	private static $instance = null;

	/**
	 * لیست ویجت‌ها: نام فایل => نام کلاس کامل.
	 * هر ویجت جدید را اینجا اضافه کن.
	 *
	 * @var array<string,string>
	 */
	private $widgets = array(
		'header/class-sazan-header.php'     => '\Sazan\Widgets\Header',
		'hero/class-sazan-hero.php'         => '\Sazan\Widgets\Hero',
		'sticky-bar/class-sazan-sticky-bar.php' => '\Sazan\Widgets\Sticky_Bar',
		'mobile-bar/class-sazan-mobile-bar.php' => '\Sazan\Widgets\Mobile_Bar',
		'mobile-header/class-sazan-mobile-header.php' => '\Sazan\Widgets\Mobile_Header',
		'heading/class-sazan-heading.php'   => '\Sazan\Widgets\Heading',
		'marquee/class-sazan-marquee.php'   => '\Sazan\Widgets\Marquee',
		'courses/class-sazan-courses.php'   => '\Sazan\Widgets\Courses',
		'features/class-sazan-features.php' => '\Sazan\Widgets\Features',
		'about/class-sazan-about.php'       => '\Sazan\Widgets\About',
		'cta/class-sazan-cta.php'           => '\Sazan\Widgets\CTA',
		'calendar/class-sazan-calendar.php' => '\Sazan\Widgets\Calendar',
		'podcast/class-sazan-podcast.php'   => '\Sazan\Widgets\Podcast',
		'blog/class-sazan-blog.php'         => '\Sazan\Widgets\Blog',
		'quiz/class-sazan-quiz.php'         => '\Sazan\Widgets\Quiz',
		'consult/class-sazan-consult.php'   => '\Sazan\Widgets\Consult',
		'shop/class-sazan-shop.php'         => '\Sazan\Widgets\Shop',
	);

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * بارگذاری و ثبت همه‌ی ویجت‌ها.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager
	 */
	public function register_widgets( $widgets_manager ) {
		// کلاس پایه اینجا لود می‌شود؛ در این مرحله المنتور کاملاً بارگذاری شده
		// و کلاس \Elementor\Widget_Base در دسترس است.
		require_once SAZAN_CORE_PATH . 'includes/base/class-sazan-widget-base.php';

		foreach ( $this->widgets as $file => $class ) {
			$path = SAZAN_CORE_PATH . 'widgets/' . $file;

			if ( ! file_exists( $path ) ) {
				continue;
			}

			require_once $path;

			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	}
}
