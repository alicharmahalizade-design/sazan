<?php
/**
 * مدیریت بارگذاری استایل‌ها و اسکریپت‌ها.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {

	/** @var Assets|null */
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// استایل/اسکریپت سمت کاربر (فرانت)
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ) );

		// استایل سمت ویرایشگر المنتور (برای آیکن‌ها و ظاهر داخل ادیتور)
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'editor_assets' ) );
	}

	/**
	 * نسخه‌ی فایل بر اساس زمان آخرین تغییر (برای شکستن کش هنگام آپدیت).
	 *
	 * @param string $rel مسیر نسبی داخل افزونه.
	 * @return string|int
	 */
	private function ver( $rel ) {
		$path = SAZAN_CORE_PATH . $rel;
		return file_exists( $path ) ? filemtime( $path ) : SAZAN_CORE_VERSION;
	}

	/**
	 * ثبت و بارگذاری asyncها در فرانت.
	 * استایل‌ها با register ثبت می‌شوند تا فقط وقتی ویجت در صفحه باشد لود شوند.
	 */
	public function frontend_assets() {
		wp_register_style(
			'sazan-core',
			SAZAN_CORE_URL . 'assets/css/sazan-core.css',
			array(),
			$this->ver( 'assets/css/sazan-core.css' )
		);

		wp_register_script(
			'sazan-core',
			SAZAN_CORE_URL . 'assets/js/sazan-core.js',
			array( 'jquery' ),
			$this->ver( 'assets/js/sazan-core.js' ),
			true
		);

		// چون با register_style/script ثبت کردیم، هر ویجت در متد خودش
		// با ->add_style_depends() / ->add_script_depends() اعلام نیاز می‌کند
		// و المنتور به‌صورت خودکار فقط در صورت حضور ویجت آن‌ها را enqueue می‌کند.
		// در صورت تمایل می‌توان اینجا به‌صورت سراسری هم enqueue کرد:
		wp_enqueue_style( 'sazan-core' );
		wp_enqueue_script( 'sazan-core' );

		wp_localize_script( 'sazan-core', 'SazanCore', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'sazan_search' ),
		) );

		wp_register_style(
			'sazan-sections',
			SAZAN_CORE_URL . 'assets/css/sazan-sections.css',
			array(),
			$this->ver( 'assets/css/sazan-sections.css' )
		);
		wp_enqueue_style( 'sazan-sections' );

		wp_register_script(
			'sazan-sections',
			SAZAN_CORE_URL . 'assets/js/sazan-sections.js',
			array( 'jquery' ),
			$this->ver( 'assets/js/sazan-sections.js' ),
			true
		);
		wp_enqueue_script( 'sazan-sections' );
	}

	/**
	 * استایل مخصوص ویرایشگر.
	 */
	public function editor_assets() {
		wp_enqueue_style(
			'sazan-core-editor',
			SAZAN_CORE_URL . 'assets/css/sazan-editor.css',
			array(),
			$this->ver( 'assets/css/sazan-editor.css' )
		);
	}
}
