<?php
/**
 * رجیستری سکشن‌ها.
 *
 * هر سکشن یک کلاس است که SPP_Section را پیاده می‌کند: شناسه، عنوان، آیکن،
 * تعریف فیلدها و متد رندر. متاباکس و ویجت‌های المنتور هر دو از همین رجیستری
 * تغذیه می‌شوند، پس اضافه‌کردن سکشن بعدی فقط یک فایل جدید است.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface SPP_Section {

	/** شناسه‌ی یکتا، مثل hero. */
	public function id();

	/** عنوان فارسی برای تب متاباکس و ویجت. */
	public function title();

	/** آیکن المنتور (eicon-*). */
	public function icon();

	/** تعریف فیلدها. */
	public function fields();

	/** چاپ HTML سکشن. */
	public function render( $post_id, $data );
}

class SPP_Sections {

	/** @var SPP_Section[] */
	private static $items = array();

	/**
	 * ثبت یک سکشن.
	 *
	 * @param SPP_Section $section
	 */
	public static function register( SPP_Section $section ) {
		self::$items[ $section->id() ] = $section;
	}

	/**
	 * @return SPP_Section[]
	 */
	public static function all() {
		return self::$items;
	}

	/**
	 * @param string $id
	 * @return SPP_Section|null
	 */
	public static function get( $id ) {
		return self::$items[ $id ] ?? null;
	}

	/**
	 * تعریف فیلدهای یک سکشن.
	 *
	 * @param string $id
	 * @return array
	 */
	public static function fields( $id ) {
		$section = self::get( $id );
		return $section ? $section->fields() : array();
	}

	/**
	 * رندر سکشن به رشته.
	 *
	 * @param string $id
	 * @param int    $post_id
	 * @return string
	 */
	public static function render( $id, $post_id ) {
		$section = self::get( $id );
		if ( ! $section || ! $post_id ) {
			return '';
		}

		$data = spp_get_section_data( $post_id, $id );

		ob_start();
		$section->render( (int) $post_id, $data );
		return (string) ob_get_clean();
	}
}
