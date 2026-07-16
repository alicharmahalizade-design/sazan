<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * دنباله‌ی پیامکی (Drip): چند گام با فاصله‌ی روز و ساعتِ مشخص.
 * هنگام «ثبت‌نام» یک مخاطب، همه‌ی گام‌ها با زمان مناسب در صف پیامک قرار می‌گیرند
 * (صف در بازه‌ی مجاز و با سقف نرخ ارسال می‌شود). لغو ثبت‌نام، گام‌های نفرستاده را لغو می‌کند.
 */
class SZC_Sequences {

	public static function t_seq()  { global $wpdb; return $wpdb->prefix . 'szc_sequences'; }
	public static function t_step() { global $wpdb; return $wpdb->prefix . 'szc_sequence_steps'; }
	public static function t_enr()  { global $wpdb; return $wpdb->prefix . 'szc_enrollments'; }

	/* ==================== دنباله‌ها ==================== */

	public static function all() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . self::t_seq() . ' ORDER BY id DESC' );
	}

	public static function active_sequences() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . self::t_seq() . ' WHERE active=1 ORDER BY name ASC' );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t_seq() . ' WHERE id=%d', (int) $id ) );
	}

	public static function create( $name ) {
		global $wpdb;
		$name = sanitize_text_field( $name );
		if ( $name === '' ) {
			return 0;
		}
		$now = current_time( 'mysql' );
		$wpdb->insert( self::t_seq(), array( 'name' => $name, 'active' => 1, 'created_at' => $now, 'updated_at' => $now ) );
		return (int) $wpdb->insert_id;
	}

	public static function update( $id, $name, $active ) {
		global $wpdb;
		$wpdb->update( self::t_seq(), array(
			'name'       => sanitize_text_field( $name ),
			'active'     => $active ? 1 : 0,
			'updated_at' => current_time( 'mysql' ),
		), array( 'id' => (int) $id ) );
	}

	public static function delete( $id ) {
		global $wpdb;
		$id = (int) $id;
		// لغو گام‌های در صف برای این دنباله.
		$enr = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . self::t_enr() . ' WHERE sequence_id=%d', $id ) );
		foreach ( $enr as $eid ) {
			self::cancel_enrollment( (int) $eid );
		}
		$wpdb->delete( self::t_seq(), array( 'id' => $id ) );
		$wpdb->delete( self::t_step(), array( 'sequence_id' => $id ) );
		$wpdb->delete( self::t_enr(), array( 'sequence_id' => $id ) );
	}

	/* ==================== گام‌ها ==================== */

	public static function steps( $seq_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::t_step() . ' WHERE sequence_id=%d ORDER BY step_no ASC', (int) $seq_id ) );
	}

	/** جایگزینی کامل گام‌ها. $rows = [{day_offset,hour,template_id}] */
	public static function set_steps( $seq_id, $rows ) {
		global $wpdb;
		$seq_id = (int) $seq_id;
		$wpdb->delete( self::t_step(), array( 'sequence_id' => $seq_id ) );
		$no = 0;
		foreach ( (array) $rows as $r ) {
			$tid = (int) ( $r['template_id'] ?? 0 );
			if ( ! $tid ) {
				continue;
			}
			$wpdb->insert( self::t_step(), array(
				'sequence_id' => $seq_id,
				'step_no'     => $no++,
				'day_offset'  => max( 0, (int) ( $r['day_offset'] ?? 0 ) ),
				'hour'        => min( 23, max( 0, (int) ( $r['hour'] ?? 10 ) ) ),
				'template_id' => $tid,
			) );
		}
	}

	/* ==================== ثبت‌نام ==================== */

	public static function enrollment( $seq_id, $contact_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::t_enr() . ' WHERE sequence_id=%d AND contact_id=%d', (int) $seq_id, (int) $contact_id ) );
	}

	public static function enrollments_for_contact( $contact_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT e.*, s.name FROM ' . self::t_enr() . ' e JOIN ' . self::t_seq() . ' s ON s.id=e.sequence_id WHERE e.contact_id=%d ORDER BY e.id DESC',
			(int) $contact_id ) );
	}

	/**
	 * ثبت‌نام یک مخاطب در دنباله؛ همه‌ی گام‌ها را در صف می‌گذارد.
	 * خروجی: array( ok, msg ).
	 */
	public static function enroll( $seq_id, $contact ) {
		global $wpdb;
		$seq = self::get( $seq_id );
		if ( ! $seq ) {
			return array( 'ok' => false, 'msg' => 'دنباله یافت نشد.' );
		}
		if ( ! SZC_SMS::is_sendable( $contact->mobile, $contact ) ) {
			return array( 'ok' => false, 'msg' => 'این مخاطب لغو دریافت/لیست سیاه است.' );
		}
		$steps = self::steps( $seq_id );
		if ( ! $steps ) {
			return array( 'ok' => false, 'msg' => 'این دنباله هنوز گامی ندارد.' );
		}
		$existing = self::enrollment( $seq_id, $contact->id );
		if ( $existing && $existing->status === 'active' ) {
			return array( 'ok' => false, 'msg' => 'این مخاطب قبلاً در این دنباله ثبت شده است.' );
		}

		$now = current_time( 'mysql' );
		if ( $existing ) {
			$wpdb->update( self::t_enr(), array( 'status' => 'active', 'started_at' => $now ), array( 'id' => (int) $existing->id ) );
			$eid = (int) $existing->id;
		} else {
			$wpdb->insert( self::t_enr(), array(
				'sequence_id' => (int) $seq_id,
				'contact_id'  => (int) $contact->id,
				'status'      => 'active',
				'started_at'  => $now,
				'created_by'  => SZC_Auth::actor_id(),
			) );
			$eid = (int) $wpdb->insert_id;
		}

		foreach ( $steps as $st ) {
			$tpl = SZC_Templates::get( $st->template_id );
			if ( ! $tpl ) {
				continue;
			}
			$dt = new DateTime( 'now', wp_timezone() );
			$dt->modify( '+' . (int) $st->day_offset . ' days' );
			$dt->setTime( (int) $st->hour, 0, 0 );
			if ( $dt->getTimestamp() < time() ) {
				$dt = new DateTime( 'now', wp_timezone() ); // گذشته → همین حالا
			}
			$send_at = $dt->format( 'Y-m-d H:i:s' );
			$r = SZC_SMS::resolve( $tpl, $contact );
			SZC_SMS::enqueue( (int) $contact->id, $contact->mobile, $r['message'], $send_at, array(
				'pattern_code'  => $r['pattern_code'],
				'values'        => $r['values'],
				'template_id'   => (int) $tpl->id,
				'enrollment_id' => $eid,
			) );
		}

		SZC_Activity::log( (int) $contact->id, 'sms', array(
			'outcome' => 'scheduled',
			'body'    => 'ثبت‌نام در دنباله‌ی «' . $seq->name . '» — ' . count( $steps ) . ' گام زمان‌بندی شد.',
			'meta'    => array( 'enrollment_id' => $eid, 'sequence_id' => (int) $seq_id ),
		) );
		return array( 'ok' => true, 'msg' => 'در دنباله ثبت شد؛ گام‌ها زمان‌بندی شدند.' );
	}

	public static function enroll_bulk( $seq_id, $contact_ids ) {
		$done = 0; $skip = 0;
		foreach ( (array) $contact_ids as $cid ) {
			$c = SZC_Contacts::get( $cid );
			if ( ! $c ) { $skip++; continue; }
			$res = self::enroll( $seq_id, $c );
			if ( ! empty( $res['ok'] ) ) { $done++; } else { $skip++; }
		}
		return array( 'enrolled' => $done, 'skipped' => $skip );
	}

	public static function cancel_enrollment( $enrollment_id ) {
		global $wpdb;
		$enrollment_id = (int) $enrollment_id;
		$wpdb->update( self::t_enr(), array( 'status' => 'canceled' ), array( 'id' => $enrollment_id ) );
		// گام‌های نفرستاده را لغو کن.
		$wpdb->update( $wpdb->prefix . 'szc_sms_queue',
			array( 'status' => 'canceled', 'response' => 'لغو دنباله' ),
			array( 'enrollment_id' => $enrollment_id, 'status' => 'pending' ) );
	}

	/** خلاصه‌ی وضعیت برای فهرست دنباله‌ها. */
	public static function stats( $seq_id ) {
		global $wpdb;
		$active = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::t_enr() . " WHERE sequence_id=%d AND status='active'", (int) $seq_id ) );
		$steps  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::t_step() . ' WHERE sequence_id=%d', (int) $seq_id ) );
		return array( 'active' => $active, 'steps' => $steps );
	}
}
