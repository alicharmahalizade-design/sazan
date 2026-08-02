<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZP_Access {

	public static function table() { global $wpdb; return $wpdb->prefix . 'szp_access'; }

	public static function grant( $course_id, $target_type, $target_id, $source = 'manual' ) {
		global $wpdb;
		$target_type = ( $target_type === 'group' ) ? 'group' : 'user';
		$wpdb->query( $wpdb->prepare(
			'INSERT IGNORE INTO ' . self::table() . ' (course_id,target_type,target_id,source,created_at) VALUES (%d,%s,%d,%s,%s)',
			(int) $course_id, $target_type, (int) $target_id, sanitize_key( $source ), current_time( 'mysql' )
		) );
	}

	public static function revoke( $course_id, $target_type, $target_id, $source = null ) {
		global $wpdb;
		$target_type = ( $target_type === 'group' ) ? 'group' : 'user';
		if ( $source === null ) {
			$wpdb->query( $wpdb->prepare(
				'DELETE FROM ' . self::table() . ' WHERE course_id=%d AND target_type=%s AND target_id=%d',
				(int) $course_id, $target_type, (int) $target_id ) );
		} else {
			$wpdb->query( $wpdb->prepare(
				'DELETE FROM ' . self::table() . ' WHERE course_id=%d AND target_type=%s AND target_id=%d AND source=%s',
				(int) $course_id, $target_type, (int) $target_id, sanitize_key( $source ) ) );
		}
	}

	public static function manual_users( $course_id ) {
		global $wpdb;
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
			'SELECT target_id FROM ' . self::table() . " WHERE course_id=%d AND target_type='user' AND source='manual'", $course_id ) ) );
	}

	public static function manual_groups( $course_id ) {
		global $wpdb;
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
			'SELECT target_id FROM ' . self::table() . " WHERE course_id=%d AND target_type='group' AND source='manual'", $course_id ) ) );
	}

	/** Replace all manual assignments for a course (woo rows untouched). */
	public static function sync_manual( $course_id, $user_ids, $group_ids ) {
		global $wpdb;
		$course_id = (int) $course_id;
		$wpdb->query( $wpdb->prepare(
			'DELETE FROM ' . self::table() . " WHERE course_id=%d AND source='manual'", $course_id ) );
		foreach ( array_unique( array_map( 'intval', (array) $user_ids ) ) as $uid ) {
			if ( $uid > 0 ) {
				self::grant( $course_id, 'user', $uid, 'manual' );
			}
		}
		foreach ( array_unique( array_map( 'intval', (array) $group_ids ) ) as $gid ) {
			if ( $gid > 0 ) {
				self::grant( $course_id, 'group', $gid, 'manual' );
			}
		}
	}

	/** Course IDs a user can access (direct + via groups), published only. */
	public static function user_courses( $user_id ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return array();
		}
		$groups  = SZP_Groups::user_groups( $user_id );
		$where   = array();
		$where[] = $wpdb->prepare( "(target_type='user' AND target_id=%d)", $user_id );
		if ( $groups ) {
			$in = implode( ',', array_map( 'absint', $groups ) );
			$where[] = "(target_type='group' AND target_id IN ($in))";
		}
		$sql = 'SELECT DISTINCT course_id FROM ' . self::table() . ' WHERE ' . implode( ' OR ', $where );
		$ids = array_map( 'intval', $wpdb->get_col( $sql ) );
		if ( ! $ids ) {
			return array();
		}
		$q = new WP_Query( array(
			'post_type'      => 'szp_course',
			'post__in'       => $ids,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order date',
			'order'          => 'DESC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		) );
		return $q->posts;
	}

	public static function user_can_course( $user_id, $course_id ) {
		return in_array( (int) $course_id, array_map( 'intval', self::user_courses( $user_id ) ), true );
	}
}
