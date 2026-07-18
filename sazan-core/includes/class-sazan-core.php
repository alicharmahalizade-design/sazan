<?php
/**
 * هسته‌ی اصلی افزونه‌ی Sazan Core
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Core {

	/** @var Core|null نمونه‌ی واحد (Singleton) */
	private static $instance = null;

	/**
	 * دریافت نمونه‌ی واحد.
	 *
	 * @return Core
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * سازنده — هوک‌ها را ثبت می‌کند.
	 */
	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * بارگذاری فایل‌های موردنیاز.
	 */
	private function includes() {
		// دقت: کلاس پایه (که از \Elementor\Widget_Base ارث می‌برد) اینجا لود نمی‌شود،
		// چون در زمان plugins_loaded هنوز کلاس‌های المنتور در دسترس نیستند.
		// بارگذاری کلاس پایه و ویجت‌ها به هوک elementor/widgets/register موکول شده است.
		require_once SAZAN_CORE_PATH . 'includes/class-assets.php';
		require_once SAZAN_CORE_PATH . 'includes/class-widgets-manager.php';
		require_once SAZAN_CORE_PATH . 'includes/class-ajax-search.php';
		require_once SAZAN_CORE_PATH . 'includes/class-podcast-cpt.php';
		require_once SAZAN_CORE_PATH . 'includes/class-views-counter.php';
		require_once SAZAN_CORE_PATH . 'includes/class-mini-cart.php';
		require_once SAZAN_CORE_PATH . 'includes/class-quiz-cpt.php';
		require_once SAZAN_CORE_PATH . 'includes/class-quiz-engine.php';
		require_once SAZAN_CORE_PATH . 'includes/class-quiz-sms.php';
		require_once SAZAN_CORE_PATH . 'includes/class-quiz-admin.php';
		require_once SAZAN_CORE_PATH . 'includes/class-consult-cpt.php';
		require_once SAZAN_CORE_PATH . 'includes/class-consult-engine.php';
		require_once SAZAN_CORE_PATH . 'includes/class-form-cpt.php';
		require_once SAZAN_CORE_PATH . 'includes/class-form-engine.php';
		require_once SAZAN_CORE_PATH . 'includes/class-settings.php';
		require_once SAZAN_CORE_PATH . 'includes/class-font-resizer.php';
	}

	/**
	 * ثبت هوک‌ها.
	 */
	private function hooks() {
		// بارگذاری ترجمه‌ها در زمان init (نه زودتر)
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// دسته‌بندی اختصاصی در پنل المنتور
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );

		// راه‌اندازی مدیریت اسکریپت/استایل و ویجت‌ها
		Assets::instance();
		Widgets_Manager::instance();
		Ajax_Search::instance();
		Podcast_CPT::instance();
		Views_Counter::instance();
		if ( class_exists( 'WooCommerce' ) ) { Mini_Cart::instance(); }
		Quiz_CPT::instance();
		Quiz_Engine::instance();
		Quiz_SMS::instance();
		if ( is_admin() ) { Quiz_Admin::instance(); }
		Consult_CPT::instance();
		Consult_Engine::instance();
		Form_CPT::instance();
		Form_Engine::instance();
		if ( is_admin() ) { Settings::instance(); }
		Font_Resizer::instance();
	}

	/**
	 * بارگذاری فایل ترجمه.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'sazan-core', false, dirname( plugin_basename( SAZAN_CORE_FILE ) ) . '/languages' );
	}

	/**
	 * افزودن دسته‌بندی «سازان» به پنل ویجت‌های المنتور.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'sazan',
			array(
				'title' => esc_html__( 'سازان', 'sazan-core' ),
				'icon'  => 'eicon-apps',
			)
		);
	}
}
