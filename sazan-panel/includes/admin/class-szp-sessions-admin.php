<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * بهبود لیست ادمینِ «جلسات»:
 *   - ستون «دوره» برای هر جلسه (با لینک فیلتر همان دوره)
 *   - فیلتر کشویی بالای لیست برای تفکیک جلسات هر دوره
 *   - مرتب‌سازی بر اساس دوره و زمان جلسه
 */
class SZP_Sessions_Admin {

	public static function init() {
		add_filter( 'manage_szp_session_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_szp_session_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'manage_edit-szp_session_sortable_columns', array( __CLASS__, 'sortable' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'course_filter' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_query' ) );
	}

	/** افزودن ستون‌های «دوره» و «زمان جلسه» بعد از ستون عنوان. */
	public static function columns( $cols ) {
		$new = array();
		foreach ( $cols as $key => $label ) {
			$new[ $key ] = $label;
			if ( $key === 'title' ) {
				$new['szp_course']   = 'دوره';
				$new['szp_datetime'] = 'زمان جلسه';
			}
		}
		return $new;
	}

	public static function column( $col, $post_id ) {
		if ( $col === 'szp_course' ) {
			$cid = (int) get_post_meta( $post_id, '_szp_course_id', true );
			if ( $cid && get_post_type( $cid ) === 'szp_course' ) {
				$url = add_query_arg(
					array( 'post_type' => 'szp_session', 'szp_course' => $cid ),
					admin_url( 'edit.php' )
				);
				printf( '<a href="%s"><strong>%s</strong></a>', esc_url( $url ), esc_html( get_the_title( $cid ) ) );
			} else {
				echo '<span style="color:#b32d2e">— بدون دوره —</span>';
			}
		} elseif ( $col === 'szp_datetime' ) {
			$ts = szp_ts_from_datetime( get_post_meta( $post_id, '_szp_datetime', true ) );
			echo $ts ? esc_html( szp_format_datetime( $ts ) ) : '—';
		}
	}

	public static function sortable( $cols ) {
		$cols['szp_course']   = 'szp_course';
		$cols['szp_datetime'] = 'szp_datetime';
		return $cols;
	}

	/** کشوی فیلتر «دوره» بالای لیست جلسات. */
	public static function course_filter() {
		global $typenow;
		if ( $typenow !== 'szp_session' ) {
			return;
		}
		$sel     = isset( $_GET['szp_course'] ) ? absint( $_GET['szp_course'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$courses = get_posts( array( 'post_type' => 'szp_course', 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
		echo '<select name="szp_course"><option value="0">همه دوره‌ها</option>';
		foreach ( $courses as $c ) {
			printf( '<option value="%1$d"%3$s>%2$s</option>', (int) $c->ID, esc_html( $c->post_title ), selected( $sel, $c->ID, false ) );
		}
		echo '</select>';
	}

	/** اعمال فیلتر دوره و مرتب‌سازی روی کوئری اصلی لیست جلسات. */
	public static function filter_query( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || $q->get( 'post_type' ) !== 'szp_session' ) {
			return;
		}

		if ( isset( $_GET['szp_course'] ) && absint( $_GET['szp_course'] ) > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification
			$mq   = (array) $q->get( 'meta_query' );
			$mq[] = array( 'key' => '_szp_course_id', 'value' => absint( $_GET['szp_course'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$q->set( 'meta_query', $mq );
		}

		$orderby = $q->get( 'orderby' );
		if ( $orderby === 'szp_course' ) {
			$q->set( 'meta_key', '_szp_course_id' );
			$q->set( 'orderby', 'meta_value_num' );
		} elseif ( $orderby === 'szp_datetime' ) {
			$q->set( 'meta_key', '_szp_datetime' );
			$q->set( 'orderby', 'meta_value' );
		}
	}
}
