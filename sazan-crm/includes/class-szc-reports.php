<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** گزارش‌ها: قیف فروش، تماس‌های امروز، پیامک‌های ارسالی و نرخ تبدیل. */
class SZC_Reports {

	/** WHERE اختیاری برای محدودسازی به مالک (کارشناس). */
	protected static function owner_where( $owner, $alias = '' ) {
		global $wpdb;
		$owner = (int) $owner;
		if ( $owner <= 0 ) {
			return '';
		}
		$col = ( $alias ? $alias . '.' : '' ) . 'owner_id';
		return $wpdb->prepare( " AND $col=%d", $owner );
	}

	/** تعداد تماس‌های ثبت‌شده از ابتدای امروز (اختیاری بر اساس کاربر). */
	public static function calls_today( $user_id = 0 ) {
		global $wpdb;
		$start = wp_date( 'Y-m-d 00:00:00' );
		$sql   = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . "szc_activities WHERE type='call' AND created_at>=%s";
		$args  = array( $start );
		if ( $user_id > 0 ) {
			$sql   .= ' AND user_id=%d';
			$args[] = (int) $user_id;
		}
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
	}

	/** پیامک‌های ارسال‌شده در N روز اخیر (بر اساس صف). */
	public static function sms_sent( $days = 1 ) {
		global $wpdb;
		$since = wp_date( 'Y-m-d 00:00:00', time() - ( max( 1, (int) $days ) - 1 ) * DAY_IN_SECONDS );
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $wpdb->prefix . "szc_sms_queue WHERE status='sent' AND sent_at>=%s", $since ) );
	}

	/** قیف: شمار هر مرحله + نرخ تبدیل. */
	public static function funnel( $owner = 0 ) {
		global $wpdb;
		$where = '1=1' . self::owner_where( $owner );
		$rows  = $wpdb->get_results( 'SELECT stage, COUNT(*) c FROM ' . $wpdb->prefix . "szc_contacts WHERE $where GROUP BY stage", OBJECT_K );
		$out   = array();
		$total = 0;
		foreach ( SZC_Settings::stages() as $k => $lbl ) {
			$c = isset( $rows[ $k ] ) ? (int) $rows[ $k ]->c : 0;
			$out[ $k ] = array( 'label' => $lbl, 'count' => $c );
			$total    += $c;
		}
		$registered = $out['registered']['count'] ?? 0;
		$conv       = $total > 0 ? round( $registered / $total * 100, 1 ) : 0;
		return array( 'stages' => $out, 'total' => $total, 'registered' => $registered, 'conversion' => $conv );
	}

	/** شمار تماس‌ها بر اساس نتیجه در N روز اخیر. */
	public static function calls_by_outcome( $days = 7 ) {
		global $wpdb;
		$since = wp_date( 'Y-m-d 00:00:00', time() - ( max( 1, (int) $days ) - 1 ) * DAY_IN_SECONDS );
		$rows  = $wpdb->get_results( $wpdb->prepare(
			'SELECT outcome, COUNT(*) c FROM ' . $wpdb->prefix . "szc_activities WHERE type='call' AND created_at>=%s GROUP BY outcome", $since ), OBJECT_K );
		$out = array();
		foreach ( SZC_Settings::call_outcomes() as $k => $lbl ) {
			$out[ $k ] = array( 'label' => $lbl, 'count' => isset( $rows[ $k ] ) ? (int) $rows[ $k ]->c : 0 );
		}
		return $out;
	}

	/** عملکرد کارشناسان: تماس و پیامکِ امروزِ هر کاربرِ دارای دسترسی. */
	public static function agent_leaderboard() {
		$out = array();
		foreach ( SZC_Settings::assignable_users() as $uid => $name ) {
			$out[] = array(
				'name'  => $name,
				'calls' => self::calls_today( $uid ),
			);
		}
		usort( $out, function ( $a, $b ) { return $b['calls'] <=> $a['calls']; } );
		return $out;
	}
}
