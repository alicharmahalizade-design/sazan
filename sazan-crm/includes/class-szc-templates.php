<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** قالب‌های پیامک با متغیر (%name%، %link%، %mini%، %intro% و…). */
class SZC_Templates {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_templates';
	}

	public static function all() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id ASC' );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', (int) $id ) );
	}

	public static function create( $name, $body, $pattern_code = '' ) {
		global $wpdb;
		$name = sanitize_text_field( $name );
		$body = sanitize_textarea_field( $body );
		if ( $name === '' || $body === '' ) {
			return 0;
		}
		$now = current_time( 'mysql' );
		$wpdb->insert( self::table(), array(
			'name'         => $name,
			'body'         => $body,
			'pattern_code' => sanitize_text_field( $pattern_code ),
			'created_at'   => $now,
			'updated_at'   => $now,
		) );
		return (int) $wpdb->insert_id;
	}

	public static function update( $id, $name, $body, $pattern_code = '' ) {
		global $wpdb;
		$wpdb->update( self::table(), array(
			'name'         => sanitize_text_field( $name ),
			'body'         => sanitize_textarea_field( $body ),
			'pattern_code' => sanitize_text_field( $pattern_code ),
			'updated_at'   => current_time( 'mysql' ),
		), array( 'id' => (int) $id ) );
	}

	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ) );
	}

	/** جایگزینی متغیرها: %key% → مقدار. */
	public static function fill( $body, $vars ) {
		$rep = array();
		foreach ( (array) $vars as $k => $v ) {
			$rep[ '%' . $k . '%' ] = (string) $v;
		}
		return strtr( (string) $body, $rep );
	}

	/** قالب‌های نمونه‌ی اولیه (فقط اگر هیچ قالبی نباشد). */
	public static function seed_defaults() {
		global $wpdb;
		$count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() );
		if ( $count > 0 ) {
			return;
		}
		self::create(
			'تشکر پس از تماس + مینی‌دوره',
			'%first% عزیز، از وقتی که برای گفتگو گذاشتید سپاسگزاریم. این هم لینک مینی‌دوره‌ی رایگان ما: %mini%'
		);
		self::create(
			'دعوت به جلسه‌ی معارفه',
			'%first% عزیز، از شما برای شرکت در جلسه‌ی معارفه‌ی دوره دعوت می‌کنیم. جزئیات و ثبت‌نام: %intro%'
		);
	}
}
