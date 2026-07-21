<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * کوچینگ کسب‌وکار — لایه داده و منطق سفر کوچینگ.
 * هر «سفر» (journey) متعلق به یک (دوره، کاربر) است و این مراحل را طی می‌کند:
 *   scan    : اسکن و تحلیل کسب‌وکار + تعریف شاخص‌های پایه (KPI)
 *   form    : پاسخ دانشجو به فرم تحلیلی کوچ
 *   review  : در انتظار راه‌اندازی هفته اول توسط کوچ
 *   tracking: چرخه هفتگی — اقدامات/تکالیف کوچ + ثبت نتایج دانشجو + نمودار رشد
 */
class SZP_Coach {

	const STAGE_SCAN     = 'scan';
	const STAGE_FORM     = 'form';
	const STAGE_REVIEW   = 'review';
	const STAGE_TRACKING = 'tracking';

	public static function t_j() { global $wpdb; return $wpdb->prefix . 'szp_coach_journeys'; }
	public static function t_k() { global $wpdb; return $wpdb->prefix . 'szp_coach_kpis'; }
	public static function t_a() { global $wpdb; return $wpdb->prefix . 'szp_coach_answers'; }
	public static function t_w() { global $wpdb; return $wpdb->prefix . 'szp_coach_weeks'; }

	/* ==================== course-level config ==================== */

	public static function is_enabled( $course_id ) {
		return (int) get_post_meta( $course_id, '_szp_coach_enabled', true ) === 1;
	}

	public static function coach_ids( $course_id ) {
		$ids = get_post_meta( $course_id, '_szp_coach_ids', true );
		return array_filter( array_map( 'intval', is_array( $ids ) ? $ids : array() ) );
	}

	/** آیا کاربر برای این دوره (یا کلی) کوچ است؟ */
	public static function is_coach( $uid, $course_id = 0 ) {
		if ( user_can( $uid, 'manage_options' ) ) {
			return true;
		}
		if ( $course_id ) {
			return in_array( (int) $uid, self::coach_ids( $course_id ), true );
		}
		return ! empty( self::coach_course_ids( $uid ) );
	}

	/** دوره‌هایی که کوچینگ آن‌ها فعال است و کاربر کوچ/مدیر آن‌هاست. */
	public static function coach_course_ids( $uid ) {
		$q = new WP_Query( array(
			'post_type'      => 'szp_course',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( array( 'key' => '_szp_coach_enabled', 'value' => '1' ) ),
		) );
		if ( user_can( $uid, 'manage_options' ) ) {
			return $q->posts;
		}
		$out = array();
		foreach ( $q->posts as $cid ) {
			if ( in_array( (int) $uid, self::coach_ids( $cid ), true ) ) {
				$out[] = $cid;
			}
		}
		return $out;
	}

	/** دوره‌های کوچینگ که دانشجو به آن‌ها دسترسی دارد. */
	public static function student_course_ids( $uid ) {
		$out = array();
		foreach ( SZP_Access::user_courses( $uid ) as $cid ) {
			if ( self::is_enabled( $cid ) ) {
				$out[] = (int) $cid;
			}
		}
		return $out;
	}

	/** تعریف فرم تحلیلی دوره: آرایه‌ای از { q, type } (type: text|textarea|number|scale|choice + opts). */
	public static function form_def( $course_id ) {
		$raw = get_post_meta( $course_id, '_szp_coach_form', true );
		$out = array();
		foreach ( (array) $raw as $row ) {
			$q = trim( (string) ( $row['q'] ?? '' ) );
			if ( $q === '' ) { continue; }
			$out[] = array(
				'q'    => $q,
				'type' => in_array( ( $row['type'] ?? 'textarea' ), array( 'text', 'textarea', 'number', 'scale', 'choice' ), true ) ? $row['type'] : 'textarea',
				'opts' => array_values( array_filter( array_map( 'trim', explode( '|', (string) ( $row['opts'] ?? '' ) ) ), 'strlen' ) ),
			);
		}
		return $out;
	}

	/* ==================== journeys ==================== */

	public static function get_journey( $course_id, $user_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::t_j() . ' WHERE course_id=%d AND user_id=%d', $course_id, $user_id ) );
	}

	public static function get_journey_by_id( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t_j() . ' WHERE id=%d', $id ) );
	}

	public static function ensure_journey( $course_id, $user_id ) {
		$j = self::get_journey( $course_id, $user_id );
		if ( $j ) { return $j; }
		global $wpdb;
		$now = current_time( 'mysql' );
		$wpdb->insert( self::t_j(), array(
			'course_id'  => (int) $course_id,
			'user_id'    => (int) $user_id,
			'stage'      => self::STAGE_SCAN,
			'status'     => 'active',
			'data'       => wp_json_encode( array() ),
			'created_at' => $now,
			'updated_at' => $now,
		), array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' ) );
		return self::get_journey( $course_id, $user_id );
	}

	public static function journey_data( $journey ) {
		$d = json_decode( $journey->data, true );
		return is_array( $d ) ? $d : array();
	}

	public static function set_stage( $journey_id, $stage ) {
		global $wpdb;
		$wpdb->update( self::t_j(), array( 'stage' => $stage, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $journey_id ), array( '%s', '%s' ), array( '%d' ) );
	}

	public static function save_scan( $journey_id, $data ) {
		global $wpdb;
		$wpdb->update( self::t_j(), array(
			'data'       => wp_json_encode( $data ),
			'updated_at' => current_time( 'mysql' ),
		), array( 'id' => (int) $journey_id ), array( '%s', '%s' ), array( '%d' ) );
	}

	/** همه سفرهای یک دوره (برای روستر کوچ). */
	public static function course_journeys( $course_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::t_j() . ' WHERE course_id=%d ORDER BY updated_at DESC', $course_id ) );
	}

	/* ==================== KPIs ==================== */

	public static function get_kpis( $journey_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::t_k() . ' WHERE journey_id=%d ORDER BY sort ASC, id ASC', $journey_id ) );
	}

	/** جایگزینی کامل لیست شاخص‌ها. $rows = [{name,unit,direction,baseline,target}] */
	public static function sync_kpis( $journey, $rows ) {
		global $wpdb;
		$jid = (int) $journey->id;
		$wpdb->delete( self::t_k(), array( 'journey_id' => $jid ), array( '%d' ) );
		$now  = current_time( 'mysql' );
		$sort = 0;
		foreach ( (array) $rows as $r ) {
			$name = trim( (string) ( $r['name'] ?? '' ) );
			if ( $name === '' ) { continue; }
			$wpdb->insert( self::t_k(), array(
				'journey_id' => $jid,
				'course_id'  => (int) $journey->course_id,
				'user_id'    => (int) $journey->user_id,
				'name'       => $name,
				'unit'       => sanitize_text_field( (string) ( $r['unit'] ?? '' ) ),
				'direction'  => ( ( $r['direction'] ?? 'up' ) === 'down' ) ? 'down' : 'up',
				'baseline'   => (float) ( $r['baseline'] ?? 0 ),
				'target'     => (float) ( $r['target'] ?? 0 ),
				'sort'       => $sort++,
				'created_at' => $now,
			), array( '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%d', '%s' ) );
		}
	}

	/* ==================== form answers ==================== */

	public static function get_answers( $journey_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::t_a() . ' WHERE journey_id=%d', $journey_id ) );
		if ( ! $row ) { return array(); }
		$a = json_decode( $row->answers, true );
		return is_array( $a ) ? $a : array();
	}

	public static function save_answers( $journey, $answers ) {
		global $wpdb;
		$now = current_time( 'mysql' );
		$wpdb->query( $wpdb->prepare(
			'INSERT INTO ' . self::t_a() . ' (journey_id,course_id,user_id,answers,created_at,updated_at)
			 VALUES (%d,%d,%d,%s,%s,%s)
			 ON DUPLICATE KEY UPDATE answers=VALUES(answers), updated_at=VALUES(updated_at)',
			(int) $journey->id, (int) $journey->course_id, (int) $journey->user_id, wp_json_encode( $answers ), $now, $now ) );
	}

	/* ==================== weeks ==================== */

	public static function get_weeks( $journey_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::t_w() . ' WHERE journey_id=%d ORDER BY week_no ASC', $journey_id ) );
	}

	public static function get_week( $journey_id, $week_no ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::t_w() . ' WHERE journey_id=%d AND week_no=%d', $journey_id, $week_no ) );
	}

	public static function last_week_no( $journey_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT MAX(week_no) FROM ' . self::t_w() . ' WHERE journey_id=%d', $journey_id ) );
	}

	public static function decode( $val ) {
		$v = json_decode( (string) $val, true );
		return is_array( $v ) ? $v : array();
	}

	/** ساخت/به‌روزرسانی یک هفته (سمت کوچ). */
	public static function upsert_week( $journey, $week_no, $fields ) {
		global $wpdb;
		$now      = current_time( 'mysql' );
		$existing = self::get_week( $journey->id, $week_no );

		$data = array();
		$fmt  = array();
		$map  = array(
			'title'          => '%s',
			'session_at'     => '%s',
			'actions'        => '%s',
			'tasks'          => '%s',
			'metrics'        => '%s',
			'student_report' => '%s',
			'coach_feedback' => '%s',
			'score'          => '%f',
			'status'         => '%s',
		);
		foreach ( $map as $k => $f ) {
			if ( array_key_exists( $k, $fields ) ) {
				$data[ $k ] = $fields[ $k ];
				$fmt[]      = $f;
			}
		}
		$data['updated_at'] = $now;
		$fmt[]              = '%s';

		if ( $existing ) {
			$wpdb->update( self::t_w(), $data, array( 'id' => (int) $existing->id ), $fmt, array( '%d' ) );
			return (int) $existing->id;
		}
		$data['journey_id'] = (int) $journey->id;
		$data['course_id']  = (int) $journey->course_id;
		$data['user_id']    = (int) $journey->user_id;
		$data['week_no']    = (int) $week_no;
		$data['created_at'] = $now;
		$fmt = array_merge( $fmt, array( '%d', '%d', '%d', '%d', '%s' ) );
		$wpdb->insert( self::t_w(), $data, $fmt );
		return (int) $wpdb->insert_id;
	}

	/* ==================== growth analytics ==================== */

	/** درصد پیشرفت یک مقدار نسبت به پایه/هدف یک شاخص (0..~1.2). */
	public static function kpi_progress( $kpi, $value ) {
		$base = (float) $kpi->baseline;
		$targ = (float) $kpi->target;
		$v    = (float) $value;
		if ( $kpi->direction === 'down' ) {
			if ( $base > $targ ) {
				$p = ( $base - $v ) / ( $base - $targ );
			} else {
				$p = ( $base > 0 ) ? ( $base - $v ) / $base : 0;
			}
		} else {
			if ( $targ > $base ) {
				$p = ( $v - $base ) / ( $targ - $base );
			} else {
				$p = ( $base > 0 ) ? ( $v - $base ) / $base + 0 : 0;
			}
		}
		return max( 0, min( 1.2, $p ) );
	}

	/** شاخص رشد کلی یک هفته (0..120) بر اساس میانگین پیشرفت شاخص‌ها. */
	public static function week_growth( $kpis, $metrics ) {
		if ( ! $kpis ) { return 0; }
		$sum = 0; $n = 0;
		foreach ( $kpis as $k ) {
			if ( isset( $metrics[ $k->id ] ) && $metrics[ $k->id ] !== '' ) {
				$sum += self::kpi_progress( $k, $metrics[ $k->id ] );
				$n++;
			}
		}
		return $n ? round( ( $sum / $n ) * 100, 1 ) : 0;
	}

	/** داده‌ی کامل نمودارها برای فرانت/جاوااسکریپت. */
	public static function chart_payload( $journey ) {
		$kpis  = self::get_kpis( $journey->id );
		$weeks = self::get_weeks( $journey->id );

		$labels = array();
		$growth = array();
		$series = array(); // per kpi
		foreach ( $kpis as $k ) {
			$series[ $k->id ] = array(
				'name'      => $k->name,
				'unit'      => $k->unit,
				'baseline'  => (float) $k->baseline,
				'target'    => (float) $k->target,
				'direction' => $k->direction,
				'values'    => array(),
			);
		}
		foreach ( $weeks as $w ) {
			if ( $w->status === 'draft' ) { continue; }
			$m = self::decode( $w->metrics );
			$labels[] = 'هفته ' . szp_fa_digits( $w->week_no );
			$growth[] = self::week_growth( $kpis, $m );
			foreach ( $kpis as $k ) {
				$series[ $k->id ]['values'][] = isset( $m[ $k->id ] ) && $m[ $k->id ] !== '' ? (float) $m[ $k->id ] : null;
			}
		}
		return array(
			'labels' => $labels,
			'growth' => $growth,
			'kpis'   => array_values( $series ),
		);
	}

	/** آخرین مقدار ثبت‌شده هر شاخص + اختلاف با هفته قبل. */
	public static function kpi_snapshot( $journey ) {
		$kpis  = self::get_kpis( $journey->id );
		$weeks = array_values( array_filter( self::get_weeks( $journey->id ), function ( $w ) { return $w->status !== 'draft'; } ) );
		$out   = array();
		$n     = count( $weeks );
		foreach ( $kpis as $k ) {
			$cur = null; $prev = null;
			for ( $i = $n - 1; $i >= 0; $i-- ) {
				$m = self::decode( $weeks[ $i ]->metrics );
				if ( isset( $m[ $k->id ] ) && $m[ $k->id ] !== '' ) {
					if ( $cur === null ) { $cur = (float) $m[ $k->id ]; }
					elseif ( $prev === null ) { $prev = (float) $m[ $k->id ]; break; }
				}
			}
			$out[] = array(
				'kpi'      => $k,
				'current'  => $cur,
				'prev'     => $prev,
				'progress' => $cur !== null ? self::kpi_progress( $k, $cur ) : null,
			);
		}
		return $out;
	}
}
