<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** لیست سیاه/لغو دریافت سراسری بر اساس شماره‌ی موبایل. */
class SZC_Blacklist {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_blacklist';
	}

	public static function is_blocked( $mobile ) {
		global $wpdb;
		$mobile = szc_normalize_mobile( $mobile );
		if ( $mobile === '' ) {
			return false;
		}
		return (bool) $wpdb->get_var( $wpdb->prepare(
			'SELECT id FROM ' . self::table() . ' WHERE mobile=%s', $mobile ) );
	}

	public static function add( $mobile, $reason = '' ) {
		global $wpdb;
		$mobile = szc_normalize_mobile( $mobile );
		if ( $mobile === '' ) {
			return false;
		}
		$wpdb->query( $wpdb->prepare(
			'INSERT INTO ' . self::table() . ' (mobile,reason,created_by,created_at) VALUES (%s,%s,%d,%s)
			 ON DUPLICATE KEY UPDATE reason=VALUES(reason)',
			$mobile, sanitize_text_field( $reason ), SZC_Auth::actor_id(), current_time( 'mysql' ) ) );
		// اگر مخاطبی با این شماره هست، opt_out هم بشود.
		$c = SZC_Contacts::get_by_mobile( $mobile );
		if ( $c ) {
			SZC_Contacts::set_opt_out( (int) $c->id, 1 );
		}
		return true;
	}

	public static function remove_mobile( $mobile ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'mobile' => szc_normalize_mobile( $mobile ) ), array( '%s' ) );
	}

	public static function remove( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) );
	}

	public static function count() {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() );
	}

	public static function all( $per_page = 50, $page = 1 ) {
		global $wpdb;
		$per_page = max( 1, (int) $per_page );
		$offset   = ( max( 1, (int) $page ) - 1 ) * $per_page;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT %d OFFSET %d', $per_page, $offset ) );
	}

	/** افزودن انبوه از متن (هر خط یک شماره). خروجی: تعداد افزوده‌شده. */
	public static function add_bulk_text( $text, $reason = '' ) {
		$lines = preg_split( '/[\r\n,]+/', (string) $text );
		$n = 0;
		foreach ( $lines as $line ) {
			$m = szc_normalize_mobile( $line );
			if ( szc_is_valid_mobile( $m ) && self::add( $m, $reason ) ) {
				$n++;
			}
		}
		return $n;
	}
}
