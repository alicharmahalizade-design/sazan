<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Immutable audit trail for security-sensitive CRM changes. */
class SZC_Audit {
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_audit_log';
	}

	public static function log( $action, $object_type, $object_id = 0, $summary = '', $before = null, $after = null, $actor_id = null ) {
		global $wpdb;
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$wpdb->insert( self::table(), array(
			'actor_id'   => $actor_id === null ? ( class_exists( 'SZC_Auth' ) ? SZC_Auth::actor_id() : 0 ) : (int) $actor_id,
			'action'     => sanitize_key( $action ),
			'object_type'=> sanitize_key( $object_type ),
			'object_id'  => (int) $object_id,
			'summary'    => sanitize_textarea_field( $summary ),
			'before_data'=> $before === null ? null : wp_json_encode( $before, JSON_UNESCAPED_UNICODE ),
			'after_data' => $after === null ? null : wp_json_encode( $after, JSON_UNESCAPED_UNICODE ),
			'ip'         => substr( $ip, 0, 45 ),
			'created_at' => current_time( 'mysql' ),
		) );
		return (int) $wpdb->insert_id;
	}

	public static function recent( $limit = 200 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' ORDER BY created_at DESC, id DESC LIMIT %d',
			max( 1, min( 1000, (int) $limit ) )
		) );
	}
}
