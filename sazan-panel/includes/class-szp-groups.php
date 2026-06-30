<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZP_Groups {

	public static function table()  { global $wpdb; return $wpdb->prefix . 'szp_groups'; }
	public static function table_u() { global $wpdb; return $wpdb->prefix . 'szp_group_users'; }

	public static function all() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY name ASC' );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', $id ) );
	}

	public static function create( $name ) {
		global $wpdb;
		$name = sanitize_text_field( $name );
		if ( $name === '' ) {
			return 0;
		}
		$slug = sanitize_title( $name );
		if ( $slug === '' ) {
			$slug = 'group';
		}
		$base = $slug;
		$i    = 2;
		while ( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE slug=%s', $slug ) ) ) {
			$slug = $base . '-' . $i;
			$i++;
		}
		$wpdb->insert( self::table(), array(
			'name'       => $name,
			'slug'       => $slug,
			'created_at' => current_time( 'mysql' ),
		), array( '%s', '%s', '%s' ) );
		return (int) $wpdb->insert_id;
	}

	public static function rename( $id, $name ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'name' => sanitize_text_field( $name ) ), array( 'id' => (int) $id ), array( '%s' ), array( '%d' ) );
	}

	public static function delete( $id ) {
		global $wpdb;
		$id = (int) $id;
		$wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
		$wpdb->delete( self::table_u(), array( 'group_id' => $id ), array( '%d' ) );
		$wpdb->delete( SZP_Access::table(), array( 'target_type' => 'group', 'target_id' => $id ), array( '%s', '%d' ) );
	}

	public static function members( $group_id ) {
		global $wpdb;
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
			'SELECT user_id FROM ' . self::table_u() . ' WHERE group_id=%d', $group_id ) ) );
	}

	public static function set_members( $group_id, $user_ids ) {
		global $wpdb;
		$group_id = (int) $group_id;
		$user_ids = array_unique( array_map( 'intval', (array) $user_ids ) );
		$wpdb->delete( self::table_u(), array( 'group_id' => $group_id ), array( '%d' ) );
		foreach ( $user_ids as $uid ) {
			if ( $uid > 0 ) {
				$wpdb->insert( self::table_u(), array( 'group_id' => $group_id, 'user_id' => $uid ), array( '%d', '%d' ) );
			}
		}
	}

	/** Add users to a group without removing existing members (skips duplicates). Returns count added. */
	public static function add_members( $group_id, $user_ids ) {
		global $wpdb;
		$group_id = (int) $group_id;
		$user_ids = array_unique( array_map( 'intval', (array) $user_ids ) );
		$existing = array_flip( self::members( $group_id ) );
		$added    = 0;
		foreach ( $user_ids as $uid ) {
			if ( $uid > 0 && ! isset( $existing[ $uid ] ) ) {
				$wpdb->insert( self::table_u(), array( 'group_id' => $group_id, 'user_id' => $uid ), array( '%d', '%d' ) );
				$added++;
			}
		}
		return $added;
	}

	/** All user IDs on the site (for "add everyone" bulk action). */
	public static function all_user_ids() {
		return array_map( 'intval', get_users( array( 'fields' => 'ID' ) ) );
	}

	public static function user_groups( $user_id ) {
		global $wpdb;
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
			'SELECT group_id FROM ' . self::table_u() . ' WHERE user_id=%d', $user_id ) ) );
	}

	/** Best-effort mobile/phone number for a user from common meta keys (WooCommerce, Digits, …). */
	public static function user_mobile( $user_id ) {
		$keys = array( 'billing_phone', 'mobile', 'phone', 'digits_phone', 'user_mobile', 'mobile_number', 'digt_countrycode' );
		foreach ( $keys as $key ) {
			$val = get_user_meta( (int) $user_id, $key, true );
			if ( is_string( $val ) && trim( $val ) !== '' ) {
				return trim( $val );
			}
		}
		return '';
	}

	/** Display row for a member: id, first name, last name, mobile, email, full name. */
	public static function user_info( $user_id ) {
		$u = get_userdata( $user_id );
		if ( ! $u ) {
			return null;
		}
		$first = get_user_meta( $user_id, 'first_name', true );
		$last  = get_user_meta( $user_id, 'last_name', true );
		$name  = trim( $first . ' ' . $last );
		return array(
			'id'     => (int) $user_id,
			'first'  => (string) $first,
			'last'   => (string) $last,
			'mobile' => self::user_mobile( $user_id ),
			'email'  => (string) $u->user_email,
			'name'   => $name !== '' ? $name : $u->display_name,
		);
	}
}
