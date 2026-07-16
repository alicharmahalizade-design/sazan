<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * فولدربندی سلسله‌مراتبیِ مخاطبین (گروه/زیرگروه). هر مخاطب در یک فولدر (group_id).
 * پوشه‌ها با parent_id درخت می‌سازند؛ عمقِ نامحدود.
 */
class SZC_Groups {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_groups';
	}

	/** همه‌ی پوشه‌ها (تخت)، مرتب بر اساس والد و ترتیب و نام. */
	public static function all() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY parent_id ASC, sort ASC, name ASC' );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', (int) $id ) );
	}

	public static function name( $id ) {
		$g = self::get( $id );
		return $g ? $g->name : '';
	}

	/** شمارِ مخاطبِ مستقیمِ هر پوشه: [group_id => count]. */
	public static function contact_counts() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT group_id, COUNT(*) c FROM ' . $wpdb->prefix . 'szc_contacts GROUP BY group_id', OBJECT_K );
		$out  = array();
		foreach ( (array) $rows as $k => $r ) {
			$out[ (int) $k ] = (int) $r->c;
		}
		return $out;
	}

	/** درختِ تودرتو: هر گره { id, name, children[], count }. */
	public static function tree( $parent = 0, $flat = null, $counts = null ) {
		if ( $flat === null ) {
			$flat = self::all();
		}
		if ( $counts === null ) {
			$counts = self::contact_counts();
		}
		$out = array();
		foreach ( $flat as $g ) {
			if ( (int) $g->parent_id !== (int) $parent ) {
				continue;
			}
			$out[] = array(
				'id'       => (int) $g->id,
				'name'     => $g->name,
				'count'    => isset( $counts[ (int) $g->id ] ) ? $counts[ (int) $g->id ] : 0,
				'children' => self::tree( (int) $g->id, $flat, $counts ),
			);
		}
		return $out;
	}

	/** شناسه‌ی یک پوشه + همه‌ی زیرپوشه‌ها (برای فیلترِ شاملِ زیرمجموعه). */
	public static function descendants( $id ) {
		$id  = (int) $id;
		$ids = array( $id );
		$all = self::all();
		$add = function ( $pid ) use ( &$add, $all, &$ids ) {
			foreach ( $all as $g ) {
				if ( (int) $g->parent_id === (int) $pid ) {
					$ids[] = (int) $g->id;
					$add( (int) $g->id );
				}
			}
		};
		$add( $id );
		return array_values( array_unique( $ids ) );
	}

	/** مسیرِ نان‌بردکرامب: آرایه‌ای از { id, name } از ریشه تا این پوشه. */
	public static function breadcrumb( $id ) {
		$path = array();
		$g    = self::get( $id );
		$seen = array();
		while ( $g && ! isset( $seen[ (int) $g->id ] ) ) {
			$seen[ (int) $g->id ] = true;
			array_unshift( $path, array( 'id' => (int) $g->id, 'name' => $g->name ) );
			$g = ( (int) $g->parent_id > 0 ) ? self::get( (int) $g->parent_id ) : null;
		}
		return $path;
	}

	public static function create( $name, $parent_id = 0 ) {
		global $wpdb;
		$name = trim( wp_strip_all_tags( (string) $name ) );
		if ( $name === '' ) {
			return 0;
		}
		$wpdb->insert( self::table(), array(
			'parent_id'  => (int) $parent_id,
			'name'       => $name,
			'created_at' => current_time( 'mysql' ),
		), array( '%d', '%s', '%s' ) );
		return (int) $wpdb->insert_id;
	}

	public static function rename( $id, $name ) {
		global $wpdb;
		$name = trim( wp_strip_all_tags( (string) $name ) );
		if ( $name === '' ) {
			return false;
		}
		return (bool) $wpdb->update( self::table(), array( 'name' => $name ), array( 'id' => (int) $id ), array( '%s' ), array( '%d' ) );
	}

	/** حذف پوشه: زیرپوشه‌ها و مخاطبینش به والدِ همان پوشه منتقل می‌شوند. */
	public static function delete( $id ) {
		global $wpdb;
		$g = self::get( $id );
		if ( ! $g ) {
			return false;
		}
		$parent = (int) $g->parent_id;
		$wpdb->update( self::table(), array( 'parent_id' => $parent ), array( 'parent_id' => (int) $id ), array( '%d' ), array( '%d' ) );
		$wpdb->update( $wpdb->prefix . 'szc_contacts', array( 'group_id' => $parent ), array( 'group_id' => (int) $id ), array( '%d' ), array( '%d' ) );
		$wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) );
		return true;
	}

	public static function move_contact( $contact_id, $group_id ) {
		global $wpdb;
		return (bool) $wpdb->update( $wpdb->prefix . 'szc_contacts', array( 'group_id' => (int) $group_id ), array( 'id' => (int) $contact_id ), array( '%d' ), array( '%d' ) );
	}

	/** جابه‌جایی یک پوشه زیر والدِ دیگر (با محافظت در برابر حلقه). */
	public static function move_group( $id, $new_parent ) {
		global $wpdb;
		$id = (int) $id; $new_parent = (int) $new_parent;
		if ( $id === $new_parent || in_array( $new_parent, self::descendants( $id ), true ) ) {
			return false; // نمی‌توان زیرِ خودش یا زیرمجموعه‌ی خودش برد
		}
		return (bool) $wpdb->update( self::table(), array( 'parent_id' => $new_parent ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
	}
}
