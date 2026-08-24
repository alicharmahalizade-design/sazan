<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Daily sales workspace, lead reservation and next-best-action rules. */
class SZC_Workspace {
	public static function next_action( $contact ) {
		if ( ! $contact ) {
			return array( 'type' => 'none', 'label' => 'بدون اقدام', 'reason' => '' );
		}
		$now = current_time( 'timestamp' );
		if ( $contact->next_followup_at ) {
			$due = strtotime( $contact->next_followup_at );
			if ( $due <= $now ) {
				return array( 'type' => 'followup', 'label' => 'پیگیری همین حالا', 'reason' => 'پیگیری عقب‌افتاده یا سررسیده است.', 'score' => 100 );
			}
			if ( $due <= $now + DAY_IN_SECONDS ) {
				return array( 'type' => 'followup', 'label' => 'آماده‌سازی پیگیری نزدیک', 'reason' => 'پیگیری در ۲۴ ساعت آینده است.', 'score' => 90 );
			}
		}
		if ( ! $contact->last_contacted_at ) {
			return array( 'type' => 'call', 'label' => 'اولین تماس', 'reason' => 'هنوز تماسی ثبت نشده است.', 'score' => $contact->priority === 'hot' ? 85 : 70 );
		}
		$age_days = floor( ( $now - strtotime( $contact->last_contacted_at ) ) / DAY_IN_SECONDS );
		if ( $age_days >= 3 && ! in_array( $contact->stage, array( 'registered', 'not_interested', 'wrong' ), true ) ) {
			return array( 'type' => 'call', 'label' => 'تماس مجدد', 'reason' => szc_fa_digits( $age_days ) . ' روز از آخرین تماس گذشته است.', 'score' => 75 );
		}
		if ( in_array( $contact->stage, array( 'answered', 'info_want' ), true ) ) {
			return array( 'type' => 'sms', 'label' => 'ارسال اطلاعات و تعیین پیگیری', 'reason' => 'مخاطب در مرحله دریافت اطلاعات است.', 'score' => 60 );
		}
		return array( 'type' => 'review', 'label' => 'بررسی پرونده و تعیین اقدام', 'reason' => 'اقدام بعدی مشخص نشده است.', 'score' => 40 );
	}

	public static function unattended( $owner = 0, $limit = 30 ) {
		global $wpdb;
		$table = SZC_Contacts::table();
		$cutoff = wp_date( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS );
		$sql = "SELECT * FROM $table WHERE deleted_at IS NULL
			AND stage NOT IN ('registered','not_interested','wrong')
			AND (last_contacted_at IS NULL OR last_contacted_at<%s)";
		$args = array( $cutoff );
		if ( $owner > 0 ) {
			$sql .= ' AND owner_id=%d';
			$args[] = (int) $owner;
		}
		$sql .= " ORDER BY (priority='hot') DESC, COALESCE(last_contacted_at,created_at) ASC LIMIT %d";
		$args[] = max( 1, min( 100, (int) $limit ) );
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}

	public static function upcoming( $owner = 0, $hours = 24, $limit = 30 ) {
		global $wpdb;
		$end = wp_date( 'Y-m-d H:i:s', time() + max( 1, (int) $hours ) * HOUR_IN_SECONDS );
		$sql = 'SELECT * FROM ' . SZC_Contacts::table() . ' WHERE deleted_at IS NULL AND next_followup_at>%s AND next_followup_at<=%s';
		$args = array( current_time( 'mysql' ), $end );
		if ( $owner > 0 ) { $sql .= ' AND owner_id=%d'; $args[] = (int) $owner; }
		$sql .= ' ORDER BY next_followup_at ASC LIMIT %d'; $args[] = (int) $limit;
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}

	public static function unassigned( $limit = 20 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . SZC_Contacts::table() . ' WHERE deleted_at IS NULL AND owner_id=0 ORDER BY (priority=%s) DESC, created_at ASC LIMIT %d',
			'hot', max( 1, min( 100, (int) $limit ) )
		) );
	}

	public static function claim( $contact_id ) {
		global $wpdb;
		$actor = SZC_Auth::actor_id();
		if ( ! SZC_Auth::is_agent() || $actor <= 0 ) {
			return array( 'ok' => false, 'msg' => 'Claim فقط برای کارشناس فروش است.' );
		}
		$table = SZC_Contacts::table();
		$now = current_time( 'mysql' );
		$until = wp_date( 'Y-m-d H:i:s', time() + 15 * MINUTE_IN_SECONDS );
		$changed = $wpdb->query( $wpdb->prepare(
			"UPDATE $table SET owner_id=%d,reserved_by=%d,reserved_until=%s,updated_at=%s
			 WHERE id=%d AND deleted_at IS NULL AND owner_id=0
			 AND (reserved_until IS NULL OR reserved_until<%s OR reserved_by=%d)",
			$actor, $actor, $until, $now, (int) $contact_id, $now, $actor
		) );
		if ( $changed !== 1 ) {
			return array( 'ok' => false, 'msg' => 'این لید قبلاً توسط کارشناس دیگری برداشته شده است.' );
		}
		SZC_Audit::log( 'lead_claim', 'contact', $contact_id, 'لید بدون مسئول Claim شد' );
		return array( 'ok' => true, 'msg' => 'لید به شما اختصاص یافت و ۱۵ دقیقه رزرو شد.' );
	}

	public static function reserve_for_call( $contact_id ) {
		global $wpdb;
		$c = SZC_Contacts::get( $contact_id );
		if ( ! $c || ! SZC_Auth::can_access_contact( $c, 'calls' ) ) {
			return array( 'ok' => false, 'msg' => 'مخاطب در دسترس نیست.' );
		}
		$actor = SZC_Auth::actor_id();
		$now   = current_time( 'mysql' );
		$until = wp_date( 'Y-m-d H:i:s', time() + 10 * MINUTE_IN_SECONDS );
		$table = SZC_Contacts::table();
		$changed = $wpdb->query( $wpdb->prepare(
			"UPDATE $table SET reserved_by=%d,reserved_until=%s WHERE id=%d
			 AND (reserved_until IS NULL OR reserved_until<%s OR reserved_by=%d)",
			$actor, $until, (int) $contact_id, $now, $actor
		) );
		if ( $changed !== 1 ) {
			$current = SZC_Contacts::get( $contact_id );
			$holder = $current ? SZC_Auth::display_name( (int) $current->reserved_by ) : '';
			return array( 'ok' => false, 'msg' => 'این مخاطب اکنون توسط ' . ( $holder ?: 'کارشناس دیگری' ) . ' در حال پیگیری است.' );
		}
		return array( 'ok' => true, 'until' => $until );
	}

	public static function release( $contact_id ) {
		global $wpdb;
		$wpdb->update( SZC_Contacts::table(), array( 'reserved_by' => 0, 'reserved_until' => null ), array(
			'id' => (int) $contact_id, 'reserved_by' => SZC_Auth::actor_id(),
		) );
	}
}
