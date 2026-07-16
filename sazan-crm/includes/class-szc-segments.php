<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** بخش‌بندی: ذخیره‌ی یک ترکیب فیلتر با نام، برای استفاده‌ی دوباره و اقدام گروهی. */
class SZC_Segments {

	/** کلیدهای فیلترِ مجاز برای ذخیره. */
	const KEYS = array( 'search', 'stage', 'priority', 'tag', 'opt_out', 'owner' );

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_segments';
	}

	public static function all() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY name ASC' );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', (int) $id ) );
	}

	public static function filters( $seg ) {
		if ( ! $seg ) {
			return array();
		}
		$f = json_decode( (string) $seg->filters, true );
		return is_array( $f ) ? $f : array();
	}

	public static function create( $name, $filters ) {
		global $wpdb;
		$name = sanitize_text_field( $name );
		if ( $name === '' ) {
			return 0;
		}
		$clean = array();
		foreach ( self::KEYS as $k ) {
			if ( isset( $filters[ $k ] ) && $filters[ $k ] !== '' ) {
				$clean[ $k ] = sanitize_text_field( $filters[ $k ] );
			}
		}
		$wpdb->insert( self::table(), array(
			'name'       => $name,
			'filters'    => wp_json_encode( $clean ),
			'created_by' => SZC_Auth::actor_id(),
			'created_at' => current_time( 'mysql' ),
		) );
		return (int) $wpdb->insert_id;
	}

	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ) );
	}
}
