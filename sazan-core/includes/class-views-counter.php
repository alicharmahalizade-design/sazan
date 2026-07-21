<?php
/**
 * شمارنده‌ی ساده‌ی بازدید نوشته‌ها.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Views_Counter {

	const META = '_sazan_views';

	/** پست‌تایپ‌هایی که بازدیدشان شمرده می‌شود. */
	private $types = array( 'post', 'sazan_podcast' );

	/** @var Views_Counter|null */
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_count' ) );

		// ستون بازدید در لیست نوشته‌ها
		add_filter( 'manage_post_posts_columns', array( $this, 'add_column' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-post_sortable_columns', array( $this, 'sortable_column' ) );
		add_action( 'pre_get_posts', array( $this, 'orderby_views' ) );
	}

	/** شمارش بازدید در صفحه‌ی تکیِ نوشته (با گارد کوکی ۱۲ ساعته). */
	public function maybe_count() {
		if ( ! is_singular( $this->types ) ) {
			return;
		}
		$id = get_queried_object_id();
		if ( ! $id ) {
			return;
		}
		$cookie = 'sazan_v_' . $id;
		if ( isset( $_COOKIE[ $cookie ] ) ) {
			return;
		}
		$count = (int) get_post_meta( $id, self::META, true );
		update_post_meta( $id, self::META, $count + 1 );

		if ( ! headers_sent() ) {
			$path = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
			$dom  = defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '';
			setcookie( $cookie, '1', time() + 12 * HOUR_IN_SECONDS, $path, $dom );
		}
	}

	/** تعداد بازدید (عدد). */
	public static function get( $id ) {
		return (int) get_post_meta( $id, self::META, true );
	}

	/** تبدیل ارقام انگلیسی به فارسی. */
	public static function to_fa( $str ) {
		$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		return str_replace( $en, $fa, (string) $str );
	}

	/** خروجی آماده مثل «۲۷۵ بازدید». */
	public static function format( $id ) {
		$n = self::get( $id );
		return self::to_fa( number_format( $n ) ) . ' ' . esc_html__( 'بازدید', 'sazan-core' );
	}

	/* ---- ستون پیشخوان ---- */
	public function add_column( $cols ) {
		$cols['sazan_views'] = esc_html__( 'بازدید', 'sazan-core' );
		return $cols;
	}

	public function render_column( $col, $post_id ) {
		if ( 'sazan_views' === $col ) {
			echo esc_html( self::to_fa( number_format( self::get( $post_id ) ) ) );
		}
	}

	public function sortable_column( $cols ) {
		$cols['sazan_views'] = 'sazan_views';
		return $cols;
	}

	public function orderby_views( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( 'sazan_views' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', self::META );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}
}
