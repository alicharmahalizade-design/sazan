<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Phase 2 data access: assignment submissions, checklist state, survey responses. */
class SZP_Data {

	public static function t_sub() { global $wpdb; return $wpdb->prefix . 'szp_submissions'; }
	public static function t_sv()  { global $wpdb; return $wpdb->prefix . 'szp_survey'; }
	public static function t_ck()  { global $wpdb; return $wpdb->prefix . 'szp_checklist'; }

	/* ---------------- submissions ---------------- */

	public static function get_submission( $session_id, $user_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM " . self::t_sub() . " WHERE session_id=%d AND user_id=%d ORDER BY id DESC LIMIT 1",
			$session_id, $user_id ) );
	}

	public static function save_submission( $session_id, $user_id, $content, $file_id = 0 ) {
		global $wpdb;
		$now      = current_time( 'mysql' );
		$existing = self::get_submission( $session_id, $user_id );

		if ( $existing ) {
			$data = array( 'content' => $content, 'updated_at' => $now );
			$fmt  = array( '%s', '%s' );
			if ( $file_id ) {
				$data['file_id'] = (int) $file_id;
				$fmt[]           = '%d';
			}
			$wpdb->update( self::t_sub(), $data, array( 'id' => (int) $existing->id ), $fmt, array( '%d' ) );
			return (int) $existing->id;
		}

		$wpdb->insert( self::t_sub(), array(
			'session_id' => (int) $session_id,
			'user_id'    => (int) $user_id,
			'content'    => $content,
			'file_id'    => (int) $file_id,
			'created_at' => $now,
			'updated_at' => $now,
		), array( '%d', '%d', '%s', '%d', '%s', '%s' ) );
		return (int) $wpdb->insert_id;
	}

	public static function recent_submissions( $session_id = 0, $limit = 200 ) {
		global $wpdb;
		$limit = (int) $limit;
		if ( $session_id ) {
			return $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM " . self::t_sub() . " WHERE session_id=%d ORDER BY updated_at DESC LIMIT %d",
				$session_id, $limit ) );
		}
		return $wpdb->get_results( "SELECT * FROM " . self::t_sub() . " ORDER BY updated_at DESC LIMIT $limit" );
	}

	/* ---------------- checklist ---------------- */

	public static function get_checklist( $session_id, $user_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT item_index, done FROM " . self::t_ck() . " WHERE session_id=%d AND user_id=%d",
			$session_id, $user_id ) );
		$out = array();
		foreach ( $rows as $r ) {
			$out[ (int) $r->item_index ] = (int) $r->done;
		}
		return $out;
	}

	public static function set_checklist_item( $session_id, $user_id, $index, $done ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare(
			"INSERT INTO " . self::t_ck() . " (session_id,user_id,item_index,done,updated_at)
			 VALUES (%d,%d,%d,%d,%s)
			 ON DUPLICATE KEY UPDATE done=VALUES(done), updated_at=VALUES(updated_at)",
			$session_id, $user_id, $index, $done ? 1 : 0, current_time( 'mysql' ) ) );
	}

	/* ---------------- survey ---------------- */

	public static function get_survey( $context, $object_id, $user_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT answers FROM " . self::t_sv() . " WHERE context=%s AND object_id=%d AND user_id=%d",
			$context, $object_id, $user_id ) );
		if ( ! $row ) {
			return array();
		}
		$a = json_decode( $row->answers, true );
		return is_array( $a ) ? $a : array();
	}

	public static function save_survey( $context, $object_id, $user_id, $answers ) {
		global $wpdb;
		$json = wp_json_encode( array_map( 'intval', (array) $answers ) );
		$wpdb->query( $wpdb->prepare(
			"INSERT INTO " . self::t_sv() . " (context,object_id,user_id,answers,created_at)
			 VALUES (%s,%d,%d,%s,%s)
			 ON DUPLICATE KEY UPDATE answers=VALUES(answers)",
			$context, $object_id, $user_id, $json, current_time( 'mysql' ) ) );
	}

	/** Average rating per question index + total responses (for admin reports). */
	public static function survey_results( $context, $object_id, $q_count ) {
		global $wpdb;
		$q_count = max( 0, (int) $q_count );
		$rows    = $wpdb->get_col( $wpdb->prepare(
			"SELECT answers FROM " . self::t_sv() . " WHERE context=%s AND object_id=%d",
			$context, $object_id ) );
		$sum = array_fill( 0, $q_count, 0 );
		$cnt = array_fill( 0, $q_count, 0 );
		foreach ( $rows as $j ) {
			$a = json_decode( $j, true );
			if ( ! is_array( $a ) ) {
				continue;
			}
			for ( $i = 0; $i < $q_count; $i++ ) {
				if ( isset( $a[ $i ] ) && $a[ $i ] > 0 ) {
					$sum[ $i ] += (int) $a[ $i ];
					$cnt[ $i ]++;
				}
			}
		}
		$avg = array();
		for ( $i = 0; $i < $q_count; $i++ ) {
			$avg[ $i ] = $cnt[ $i ] ? round( $sum[ $i ] / $cnt[ $i ], 2 ) : 0;
		}
		return array( 'avg' => $avg, 'responses' => count( $rows ) );
	}
}
