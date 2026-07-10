<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** یادداشت‌ها، تماس‌ها، پیگیری‌ها و تایم‌لاین هر مخاطب. */
class SZC_Activity {

	public static function t_notes() { global $wpdb; return $wpdb->prefix . 'szc_notes'; }
	public static function t_act()   { global $wpdb; return $wpdb->prefix . 'szc_activities'; }

	/* ==================== یادداشت ==================== */

	public static function add_note( $contact_id, $body, $user_id = 0 ) {
		global $wpdb;
		$body = sanitize_textarea_field( $body );
		if ( trim( $body ) === '' ) {
			return 0;
		}
		$wpdb->insert( self::t_notes(), array(
			'contact_id' => (int) $contact_id,
			'user_id'    => $user_id ? (int) $user_id : get_current_user_id(),
			'body'       => $body,
			'created_at' => current_time( 'mysql' ),
		) );
		return (int) $wpdb->insert_id;
	}

	public static function delete_note( $id ) {
		global $wpdb;
		$wpdb->delete( self::t_notes(), array( 'id' => (int) $id ) );
	}

	/* ==================== فعالیت (تماس/پیامک/مرحله/سیستمی) ==================== */

	public static function log( $contact_id, $type, $args = array() ) {
		global $wpdb;
		$a = wp_parse_args( $args, array(
			'user_id' => get_current_user_id(),
			'outcome' => '',
			'body'    => '',
			'meta'    => null,
			'due_at'  => null,
			'done'    => 0,
		) );
		$wpdb->insert( self::t_act(), array(
			'contact_id' => (int) $contact_id,
			'user_id'    => (int) $a['user_id'],
			'type'       => sanitize_key( $type ),
			'outcome'    => sanitize_text_field( $a['outcome'] ),
			'body'       => sanitize_textarea_field( $a['body'] ),
			'meta'       => $a['meta'] !== null ? wp_json_encode( $a['meta'] ) : null,
			'due_at'     => $a['due_at'] ?: null,
			'done'       => $a['done'] ? 1 : 0,
			'created_at' => current_time( 'mysql' ),
		) );
		return (int) $wpdb->insert_id;
	}

	/**
	 * ثبت یک تماس. در صورت درخواست، پیامک تشکر/دعوت را برای بعد زمان‌بندی می‌کند.
	 * خروجی: array( id, sms_scheduled ).
	 */
	public static function log_call( $contact, $outcome, $body = '', $want_sms = null, $template_id = 0 ) {
		$cid = (int) $contact->id;
		$id  = self::log( $cid, 'call', array( 'outcome' => $outcome, 'body' => $body ) );
		SZC_Contacts::touch_contacted( $cid );

		// پیشرفت خودکار مرحله: جدید → تماس گرفته شد.
		if ( $contact->stage === 'new' ) {
			SZC_Contacts::set_stage( $cid, 'contacted' );
			self::log( $cid, 'stage', array( 'body' => 'تماس گرفته شد', 'outcome' => 'contacted' ) );
		}

		$scheduled = false;
		$auto = ( $want_sms === null ) ? (int) SZC_Settings::get( 'auto_after_call' ) === 1 : (bool) $want_sms;
		// فقط برای تماس‌های معنادار (پاسخ داده/درخواست تماس مجدد) و مخاطب بدون لغو دریافت.
		$sendable_outcome = in_array( $outcome, array( 'answered', 'callback' ), true );
		if ( $auto && $sendable_outcome && ! $contact->opt_out ) {
			$scheduled = SZC_SMS::schedule_thanks( $contact, (int) $template_id );
		}
		return array( 'id' => $id, 'sms_scheduled' => $scheduled );
	}

	/* ==================== پیگیری (Callback) ==================== */

	public static function add_followup( $contact_id, $due_mysql, $body = '' ) {
		$id = self::log( $contact_id, 'followup', array( 'body' => $body, 'due_at' => $due_mysql, 'done' => 0 ) );
		SZC_Contacts::set_followup( $contact_id, $due_mysql );
		return $id;
	}

	public static function complete_followup( $activity_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t_act() . ' WHERE id=%d', (int) $activity_id ) );
		if ( ! $row ) {
			return;
		}
		$wpdb->update( self::t_act(), array( 'done' => 1 ), array( 'id' => (int) $activity_id ) );
		// اگر پیگیری دیگری باز نبود، next_followup_at را پاک کن.
		$open = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . self::t_act() . " WHERE contact_id=%d AND type='followup' AND done=0", (int) $row->contact_id ) );
		if ( ! $open ) {
			SZC_Contacts::set_followup( (int) $row->contact_id, null );
		}
	}

	public static function delete_activity( $id ) {
		global $wpdb;
		$wpdb->delete( self::t_act(), array( 'id' => (int) $id ) );
	}

	/* ==================== خواندن / تایم‌لاین ==================== */

	public static function notes( $contact_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::t_notes() . ' WHERE contact_id=%d ORDER BY created_at DESC', (int) $contact_id ) );
	}

	public static function activities( $contact_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::t_act() . ' WHERE contact_id=%d ORDER BY created_at DESC', (int) $contact_id ) );
	}

	/** تایم‌لاین یکپارچه: یادداشت‌ها + فعالیت‌ها، جدیدترین بالا. */
	public static function timeline( $contact_id ) {
		$items = array();
		foreach ( self::notes( $contact_id ) as $n ) {
			$items[] = array(
				'kind'       => 'note',
				'type'       => 'note',
				'body'       => $n->body,
				'outcome'    => '',
				'user_id'    => (int) $n->user_id,
				'created_at' => $n->created_at,
				'id'         => (int) $n->id,
				'done'       => 0,
				'due_at'     => null,
			);
		}
		foreach ( self::activities( $contact_id ) as $x ) {
			$items[] = array(
				'kind'       => 'activity',
				'type'       => $x->type,
				'body'       => $x->body,
				'outcome'    => $x->outcome,
				'user_id'    => (int) $x->user_id,
				'created_at' => $x->created_at,
				'id'         => (int) $x->id,
				'done'       => (int) $x->done,
				'due_at'     => $x->due_at,
			);
		}
		usort( $items, function ( $a, $b ) {
			return strcmp( (string) $b['created_at'], (string) $a['created_at'] );
		} );
		return $items;
	}

	/**
	 * پیگیری‌های باز. $when: 'due' (سررسیدشده/امروز و قبل)، 'upcoming' (آینده)، 'all'.
	 * $owner>0 فقط سرنخ‌های آن کارشناس.
	 */
	public static function followups( $when = 'due', $owner = 0, $limit = 200 ) {
		global $wpdb;
		$now  = current_time( 'mysql' );
		$sql  = 'SELECT a.*, c.first_name, c.last_name, c.mobile, c.owner_id FROM ' . self::t_act() . ' a '
			. 'JOIN ' . SZC_Contacts::table() . ' c ON c.id=a.contact_id '
			. "WHERE a.type='followup' AND a.done=0 AND a.due_at IS NOT NULL";
		$args = array();
		if ( $when === 'due' ) {
			$sql .= ' AND a.due_at<=%s'; $args[] = $now;
			$order = 'ASC';
		} elseif ( $when === 'upcoming' ) {
			$sql .= ' AND a.due_at>%s'; $args[] = $now;
			$order = 'ASC';
		} else {
			$order = 'ASC';
		}
		if ( $owner > 0 ) {
			$sql .= ' AND c.owner_id=%d'; $args[] = (int) $owner;
		}
		$sql   .= ' ORDER BY a.due_at ' . $order . ' LIMIT %d';
		$args[] = (int) $limit;
		return $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
	}

	/** میان‌بر: پیگیری‌های سررسیدشده (برای داشبورد). */
	public static function due_followups( $limit = 50, $owner = 0 ) {
		return self::followups( 'due', $owner, $limit );
	}
}
