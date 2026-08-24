<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** گزارش‌ها: قیف فروش، تماس‌های امروز، پیامک‌های ارسالی و نرخ تبدیل. */
class SZC_Reports {

	/** مرز دقیق امروز در منطقه زمانی تنظیم‌شده وردپرس. */
	protected static function today_bounds() {
		$start = current_datetime()->setTime( 0, 0, 0 );
		$end   = $start->modify( '+1 day' );
		return array( $start->format( 'Y-m-d H:i:s' ), $end->format( 'Y-m-d H:i:s' ) );
	}

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
		list( $start, $end ) = self::today_bounds();
		$sql   = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . "szc_activities WHERE type='call' AND created_at>=%s AND created_at<%s";
		$args  = array( $start, $end );
		if ( $user_id > 0 ) {
			$sql   .= ' AND user_id=%d';
			$args[] = (int) $user_id;
		}
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
	}

	public static function answered_calls_today( $user_id = 0 ) {
		global $wpdb;
		list( $start, $end ) = self::today_bounds();
		$sql   = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . "szc_activities WHERE type='call' AND outcome='answered' AND created_at>=%s AND created_at<%s";
		$args  = array( $start, $end );
		if ( $user_id > 0 ) {
			$sql   .= ' AND user_id=%d';
			$args[] = (int) $user_id;
		}
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
	}

	protected static function normalize_date( $date, $fallback ) {
		$date = sanitize_text_field( (string) $date );
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : $fallback;
	}

	/** آمار جامع یک کارشناس در بازه‌ی تاریخ. */
	public static function agent_stats( $owner, $from = '', $to = '' ) {
		global $wpdb;
		$today = wp_date( 'Y-m-d' );
		$from  = self::normalize_date( $from, $today );
		$to    = self::normalize_date( $to, $today );
		if ( $from > $to ) {
			$tmp = $from; $from = $to; $to = $tmp;
		}
		$start = $from . ' 00:00:00';
		$end   = $to . ' 23:59:59';
		$act   = $wpdb->prefix . 'szc_activities';
		// سوابقِ پیش از مهاجرتِ کارشناسان زیرِ شناسه‌ی کاربرِ وردپرسیِ قدیمی ثبت شده‌اند.
		list( $actor_sql, $actor_ids ) = self::actor_in( $owner, '' );
		$row   = $wpdb->get_row( $wpdb->prepare(
			"SELECT
				SUM(type='call') calls,
				SUM(type='call' AND outcome='answered') answered,
				SUM(type='call' AND outcome='no_answer') no_answer,
				SUM(type='call' AND outcome='busy') busy,
				SUM(type='call' AND outcome='callback') callback,
				SUM(type='call' AND outcome='not_interested') not_interested,
				SUM(type='followup') followups,
				SUM(type='sms' AND outcome='sent') sms,
				SUM(type='stage' AND outcome='registered') registered,
				MAX(created_at) last_activity
			FROM $act WHERE $actor_sql AND created_at BETWEEN %s AND %s",
			array_merge( $actor_ids, array( $start, $end ) )
		), ARRAY_A );
		$created_sql = str_replace( 'user_id', 'created_by', $actor_sql );
		$new_leads = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . SZC_Contacts::table() . " WHERE deleted_at IS NULL AND $created_sql AND created_at BETWEEN %s AND %s",
			array_merge( $actor_ids, array( $start, $end ) )
		) );
		$assigned = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . SZC_Contacts::table() . ' WHERE deleted_at IS NULL AND owner_id=%d',
			(int) $owner
		) );
		$overdue = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $act . ' a JOIN ' . SZC_Contacts::table() . " c ON c.id=a.contact_id
			 WHERE c.deleted_at IS NULL AND c.owner_id=%d AND a.type='followup' AND a.done=0 AND a.due_at IS NOT NULL AND a.due_at<%s",
			(int) $owner, current_time( 'mysql' )
		) );
		$assigned_in_range = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(DISTINCT contact_id) FROM ' . SZC_Contacts::history_table()
			. " WHERE event_type='owner' AND new_value=%s AND created_at BETWEEN %s AND %s",
			(string) $owner, $start, $end
		) );
		$followups_done = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $act WHERE $actor_sql AND type='followup' AND done=1 AND created_at BETWEEN %s AND %s",
			array_merge( $actor_ids, array( $start, $end ) )
		) );
		$sales = $wpdb->get_row( $wpdb->prepare(
			'SELECT COUNT(*) won_contacts, SUM(COALESCE(NULLIF(final_value,0),deal_value)) revenue FROM ' . SZC_Contacts::table()
			. " WHERE deleted_at IS NULL AND owner_id=%d AND won_at BETWEEN %s AND %s",
			(int) $owner, $start, $end
		), ARRAY_A );
		$out = array(
			'calls'          => (int) ( $row['calls'] ?? 0 ),
			'answered'       => (int) ( $row['answered'] ?? 0 ),
			'no_answer'      => (int) ( $row['no_answer'] ?? 0 ),
			'busy'           => (int) ( $row['busy'] ?? 0 ),
			'callback'       => (int) ( $row['callback'] ?? 0 ),
			'not_interested' => (int) ( $row['not_interested'] ?? 0 ),
			'followups'      => (int) ( $row['followups'] ?? 0 ),
			'sms'            => (int) ( $row['sms'] ?? 0 ),
			'registered'     => (int) ( $row['registered'] ?? 0 ),
			'new_leads'      => $new_leads,
			'assigned'       => $assigned,
			'assigned_in_range' => $assigned_in_range,
			'overdue'        => $overdue,
			'last_activity'  => (string) ( $row['last_activity'] ?? '' ),
			'won_contacts'   => (int) ( $sales['won_contacts'] ?? 0 ),
			'revenue'        => (float) ( $sales['revenue'] ?? 0 ),
			'followups_done' => $followups_done,
		);
		$out['answer_rate'] = $out['calls'] > 0 ? round( $out['answered'] / $out['calls'] * 100, 1 ) : 0;
		$out['followup_success_rate'] = $out['followups'] > 0 ? round( $followups_done / $out['followups'] * 100, 1 ) : 0;
		$fair_base = $assigned_in_range > 0 ? $assigned_in_range : $new_leads;
		$out['conversion_rate'] = $fair_base > 0 ? round( $out['won_contacts'] / $fair_base * 100, 1 ) : 0;
		return $out;
	}

	/* ==================== ریز فعالیت کارشناس ==================== */

	/** بازه‌های آماده‌ی گزارش ریز فعالیت (کلید ⇒ برچسب و تعداد روز). */
	public static function activity_periods() {
		return array(
			'7'  => array( 'label' => 'یک هفته', 'days' => 7 ),
			'14' => array( 'label' => 'دو هفته', 'days' => 14 ),
			'21' => array( 'label' => 'سه هفته', 'days' => 21 ),
			'30' => array( 'label' => 'یک ماه', 'days' => 30 ),
		);
	}

	/**
	 * بازه‌ی تاریخ گزارش را از ورودی‌ها می‌سازد.
	 * اگر $period یکی از بازه‌های آماده باشد، بازه بر همان اساس از امروز به عقب حساب می‌شود؛
	 * در غیر این صورت از $from/$to استفاده می‌شود.
	 *
	 * @return array{from:string,to:string,days:int,period:string}
	 */
	public static function activity_range( $period = '7', $from = '', $to = '' ) {
		$periods = self::activity_periods();
		$period  = (string) $period;
		$today   = wp_date( 'Y-m-d' );
		if ( isset( $periods[ $period ] ) ) {
			$days = (int) $periods[ $period ]['days'];
			$from = wp_date( 'Y-m-d', time() - ( $days - 1 ) * DAY_IN_SECONDS );
			$to   = $today;
		} else {
			$period = 'custom';
			$from   = self::normalize_date( $from, wp_date( 'Y-m-d', time() - 6 * DAY_IN_SECONDS ) );
			$to     = self::normalize_date( $to, $today );
			if ( $from > $to ) {
				$tmp = $from; $from = $to; $to = $tmp;
			}
			$days = (int) round( ( strtotime( $to . ' 12:00:00 UTC' ) - strtotime( $from . ' 12:00:00 UTC' ) ) / DAY_IN_SECONDS ) + 1;
		}
		return array( 'from' => $from, 'to' => $to, 'days' => max( 1, $days ), 'period' => $period );
	}

	/**
	 * مبناهای محاسبه‌ی گزارش ریز فعالیت.
	 *  actor → فعالیت‌هایی که خودِ کارشناس ثبت کرده (شاملِ شناسه‌ی وردپرسیِ قدیمی‌اش).
	 *  owner → همه‌ی فعالیت‌های مخاطبینی که هم‌اکنون در اختیارِ اوست (حتی اگر مدیر یا
	 *          کاربرِ دیگری آن‌ها را ثبت کرده باشد) — برای دیدنِ سوابقِ قدیمی.
	 */
	public static function activity_bases() {
		return array(
			'actor' => 'ثبت‌کننده‌ی فعالیت (کارشناس)',
			'owner' => 'مالکِ فعلیِ مخاطب',
		);
	}

	/** قطعه‌ی IN برای همه‌ی شناسه‌های بازیگرِ یک کارشناس (نو و قدیمی). */
	protected static function actor_in( $owner, $alias = 'a' ) {
		$ids = SZC_Agents::actor_ids( $owner );
		if ( ! $ids ) {
			$ids = array( (int) $owner );
		}
		$col = ( $alias ? $alias . '.' : '' ) . 'user_id';
		$ph  = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		return array( "$col IN ($ph)", $ids );
	}

	/**
	 * FROM/WHERE مشترکِ گزارش ریز فعالیت.
	 *
	 * @return array{0:string,1:string,2:array} قطعه‌ی JOIN، شرط‌ها و مقادیر.
	 */
	protected static function activity_log_parts( $owner, $from, $to, $type = 'call', $outcome = '', $basis = 'actor', $need_contact = true ) {
		$where = array();
		$vals  = array();
		$join  = ( $basis === 'owner' || $need_contact )
			? ' LEFT JOIN ' . SZC_Contacts::table() . ' c ON c.id=a.contact_id'
			: '';
		if ( $basis === 'owner' ) {
			$where[] = 'c.owner_id=%d';
			$vals[]  = (int) $owner;
		} else {
			list( $in_sql, $in_vals ) = self::actor_in( $owner );
			$where[] = $in_sql;
			$vals    = array_merge( $vals, $in_vals );
		}
		$where[] = 'a.created_at BETWEEN %s AND %s';
		$vals[]  = $from . ' 00:00:00';
		$vals[]  = $to . ' 23:59:59';
		if ( $type !== '' && $type !== 'all' ) {
			$where[] = 'a.type=%s';
			$vals[]  = sanitize_key( $type );
		}
		if ( $outcome !== '' ) {
			$where[] = 'a.outcome=%s';
			$vals[]  = sanitize_text_field( $outcome );
		}
		return array( $join, implode( ' AND ', $where ), $vals );
	}

	/**
	 * ریز فعالیت‌های یک کارشناس در بازه: هر ردیف یک تماس/پیگیری/پیامک با تاریخ و ساعتِ دقیق.
	 *
	 * @param int    $owner   شناسه‌ی مالک (کارشناس) در فضای‌نامِ CRM.
	 * @param string $from    تاریخ شروع (Y-m-d).
	 * @param string $to      تاریخ پایان (Y-m-d).
	 * @param string $type    نوع فعالیت؛ 'call' (پیش‌فرض) یا 'all' یا هر نوع دیگر.
	 * @param string $outcome فیلتر برونداد (مثلاً 'answered').
	 * @param int    $limit   حداکثر ردیف.
	 * @param int    $offset  پرش برای صفحه‌بندی.
	 * @param string $basis   مبنای محاسبه: actor | owner.
	 */
	public static function agent_activity_log( $owner, $from, $to, $type = 'call', $outcome = '', $limit = 200, $offset = 0, $basis = 'actor' ) {
		global $wpdb;
		list( $join, $where, $vals ) = self::activity_log_parts( $owner, $from, $to, $type, $outcome, $basis );
		$vals[] = max( 1, min( 5000, (int) $limit ) );
		$vals[] = max( 0, (int) $offset );
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT a.id, a.contact_id, a.user_id, a.type, a.outcome, a.body, a.due_at, a.done, a.created_at,
				c.first_name, c.last_name, c.mobile, c.stage, c.company, c.source
			 FROM ' . self::act_table() . " a$join
			 WHERE $where ORDER BY a.created_at DESC, a.id DESC LIMIT %d OFFSET %d",
			$vals
		) );
	}

	/** شمارِ کلِ ردیف‌های ریز فعالیت (برای صفحه‌بندی). */
	public static function agent_activity_count( $owner, $from, $to, $type = 'call', $outcome = '', $basis = 'actor' ) {
		global $wpdb;
		list( $join, $where, $vals ) = self::activity_log_parts( $owner, $from, $to, $type, $outcome, $basis, false );
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . self::act_table() . " a$join WHERE $where", $vals ) );
	}

	/** خلاصه‌ی تماس‌های کارشناس در بازه: تفکیک برونداد، نرخ موفقیت و میانگین روزانه. */
	public static function agent_activity_summary( $owner, $from, $to, $basis = 'actor' ) {
		global $wpdb;
		$act = self::act_table();
		list( $join, $where, $vals ) = self::activity_log_parts( $owner, $from, $to, 'call', '', $basis, false );
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT COUNT(*) calls,
				COUNT(DISTINCT DATE(a.created_at)) active_days,
				COUNT(DISTINCT a.contact_id) contacts,
				MIN(a.created_at) first_at,
				MAX(a.created_at) last_at
			 FROM $act a$join WHERE $where",
			$vals ), ARRAY_A );
		$by_outcome = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.outcome, COUNT(*) c FROM $act a$join WHERE $where GROUP BY a.outcome",
			$vals ), OBJECT_K );
		$outcomes = array();
		foreach ( SZC_Settings::call_outcomes() as $key => $label ) {
			$outcomes[ $key ] = array( 'label' => $label, 'count' => isset( $by_outcome[ $key ] ) ? (int) $by_outcome[ $key ]->c : 0 );
		}
		list( $join2, $where2, $vals2 ) = self::activity_log_parts( $owner, $from, $to, 'all', '', $basis, false );
		$mix = $wpdb->get_row( $wpdb->prepare(
			"SELECT SUM(a.type='followup') followups, SUM(a.type='sms' AND a.outcome='sent') sms FROM $act a$join2 WHERE $where2",
			$vals2 ), ARRAY_A );

		$calls    = (int) ( $row['calls'] ?? 0 );
		$answered = (int) ( $outcomes['answered']['count'] ?? 0 );
		$days     = max( 1, (int) round( ( strtotime( $to . ' 12:00:00 UTC' ) - strtotime( $from . ' 12:00:00 UTC' ) ) / DAY_IN_SECONDS ) + 1 );
		return array(
			'calls'        => $calls,
			'answered'     => $answered,
			'answer_rate'  => $calls > 0 ? round( $answered / $calls * 100, 1 ) : 0,
			'contacts'     => (int) ( $row['contacts'] ?? 0 ),
			'active_days'  => (int) ( $row['active_days'] ?? 0 ),
			'range_days'   => $days,
			'per_day'      => round( $calls / $days, 1 ),
			'per_active_day' => ( $row['active_days'] ?? 0 ) > 0 ? round( $calls / (int) $row['active_days'], 1 ) : 0,
			'first_at'     => (string) ( $row['first_at'] ?? '' ),
			'last_at'      => (string) ( $row['last_at'] ?? '' ),
			'outcomes'     => $outcomes,
			'followups'    => (int) ( $mix['followups'] ?? 0 ),
			'sms'          => (int) ( $mix['sms'] ?? 0 ),
		);
	}

	/** تفکیک روزانه‌ی تماس‌ها در بازه (همه‌ی روزها، حتی روزهای بدون تماس). */
	public static function agent_activity_daily( $owner, $from, $to, $basis = 'actor' ) {
		global $wpdb;
		list( $join, $where, $vals ) = self::activity_log_parts( $owner, $from, $to, 'call', '', $basis, false );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT DATE(a.created_at) d, COUNT(*) calls, SUM(a.outcome='answered') answered
			 FROM " . self::act_table() . " a$join WHERE $where GROUP BY DATE(a.created_at)",
			$vals ), OBJECT_K );
		$out  = array();
		$cur  = strtotime( $from . ' 12:00:00 UTC' );
		$end  = strtotime( $to . ' 12:00:00 UTC' );
		$safe = 0;
		while ( $cur <= $end && $safe++ < 400 ) {
			$key   = gmdate( 'Y-m-d', $cur );
			$calls = isset( $rows[ $key ] ) ? (int) $rows[ $key ]->calls : 0;
			$ans   = isset( $rows[ $key ] ) ? (int) $rows[ $key ]->answered : 0;
			$out[] = array(
				'date'     => $key,
				'label'    => szc_format_mysql( $key . ' 00:00:00', false ),
				'weekday'  => szc_weekday_fa( $key ),
				'calls'    => $calls,
				'answered' => $ans,
				'rate'     => $calls > 0 ? round( $ans / $calls * 100, 1 ) : 0,
			);
			$cur += DAY_IN_SECONDS;
		}
		return $out;
	}

	/** توزیع ساعتی تماس‌ها (۰ تا ۲۳) برای دیدن پرکارترین ساعاتِ روز. */
	public static function agent_activity_hourly( $owner, $from, $to, $basis = 'actor' ) {
		global $wpdb;
		list( $join, $where, $vals ) = self::activity_log_parts( $owner, $from, $to, 'call', '', $basis, false );
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT HOUR(a.created_at) h, COUNT(*) calls, SUM(a.outcome='answered') answered
			 FROM " . self::act_table() . " a$join WHERE $where GROUP BY HOUR(a.created_at)",
			$vals ), OBJECT_K );
		$out = array();
		for ( $h = 0; $h < 24; $h++ ) {
			$calls = isset( $rows[ $h ] ) ? (int) $rows[ $h ]->calls : 0;
			$out[] = array(
				'hour'     => $h,
				'calls'    => $calls,
				'answered' => isset( $rows[ $h ] ) ? (int) $rows[ $h ]->answered : 0,
			);
		}
		return $out;
	}

	/**
	 * تماس‌هایی که به هیچ کارشناسِ فعلی نسبت داده نمی‌شوند (شناسه‌ی ثبت‌کننده‌ی ناشناس
	 * یا صفر). برای عیب‌یابیِ سوابقِ قدیمی که پیش از مهاجرتِ کارشناسان ثبت شده‌اند.
	 */
	public static function unattributed_actors( $from, $to ) {
		global $wpdb;
		$known = array();
		foreach ( SZC_Agents::all() as $agent ) {
			$known = array_merge( $known, SZC_Agents::actor_ids( SZC_Agents::to_owner( (int) $agent->id ) ) );
		}
		$known = array_values( array_unique( array_map( 'intval', $known ) ) );
		$sql   = 'SELECT user_id, COUNT(*) calls, MIN(created_at) first_at, MAX(created_at) last_at FROM '
			. self::act_table() . " WHERE type='call' AND created_at BETWEEN %s AND %s";
		$vals  = array( $from . ' 00:00:00', $to . ' 23:59:59' );
		if ( $known ) {
			$sql .= ' AND user_id NOT IN (' . implode( ',', array_fill( 0, count( $known ), '%d' ) ) . ')';
			$vals = array_merge( $vals, $known );
		}
		$sql .= ' GROUP BY user_id ORDER BY calls DESC LIMIT 20';
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $vals ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		foreach ( $rows as $r ) {
			$r->name = (int) $r->user_id === 0
				? 'بدون ثبت‌کننده (سیستمی/ایمپورت)'
				: ( SZC_Auth::display_name( (int) $r->user_id ) ?: 'کاربر #' . (int) $r->user_id );
		}
		return $rows;
	}

	/** میان‌بر: نامِ جدول فعالیت‌ها. */
	protected static function act_table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_activities';
	}

	/** آمار امروز همراه با تارگت و درصد پیشرفت. */
	public static function agent_day_stats( $owner ) {
		$out   = self::agent_stats( $owner, wp_date( 'Y-m-d' ), wp_date( 'Y-m-d' ) );
		$aid   = SZC_Agents::from_owner( (int) $owner );
		$agent = $aid ? SZC_Agents::get( $aid ) : null;
		$target = $agent ? max( 0, (int) ( $agent->daily_answered_target ?? 0 ) ) : 0;
		$out['target']   = $target;
		$out['progress'] = $target > 0 ? round( $out['answered'] / $target * 100 ) : 0;
		$out['followup_target'] = $agent ? max( 0, (int) ( $agent->daily_followup_target ?? 0 ) ) : 0;
		$out['conversion_target'] = $agent ? max( 0, (int) ( $agent->daily_conversion_target ?? 0 ) ) : 0;
		$out['followup_progress'] = $out['followup_target'] > 0 ? round( $out['followups'] / $out['followup_target'] * 100 ) : 0;
		$out['conversion_progress'] = $out['conversion_target'] > 0 ? round( $out['won_contacts'] / $out['conversion_target'] * 100 ) : 0;
		return $out;
	}

	/** جدول عملکرد همه‌ی کارشناسان در یک بازه. */
	public static function team_performance( $from, $to ) {
		$out = array();
		foreach ( SZC_Agents::all() as $agent ) {
			$owner = SZC_Agents::to_owner( (int) $agent->id );
			$out[] = array(
				'agent' => $agent,
				'owner' => $owner,
				'range' => self::agent_stats( $owner, $from, $to ),
				'today' => self::agent_day_stats( $owner ),
			);
		}
		usort( $out, function ( $a, $b ) {
			return $b['today']['progress'] <=> $a['today']['progress'];
		} );
		return $out;
	}

	/** Cohort and revenue metrics for contacts created in the selected period. */
	public static function cohort( $from, $to, $owner = 0 ) {
		global $wpdb;
		$from  = self::normalize_date( $from, wp_date( 'Y-m-d' ) );
		$to    = self::normalize_date( $to, wp_date( 'Y-m-d' ) );
		$start = $from . ' 00:00:00';
		$end   = $to . ' 23:59:59';
		$where = 'deleted_at IS NULL AND created_at BETWEEN %s AND %s' . self::owner_where( $owner );
		$row = $wpdb->get_row( $wpdb->prepare(
			'SELECT COUNT(*) leads, SUM(won_at IS NOT NULL) won, SUM(CASE WHEN won_at IS NOT NULL THEN COALESCE(NULLIF(final_value,0),deal_value) ELSE 0 END) value FROM ' . SZC_Contacts::table() . " WHERE $where",
			$start, $end
		), ARRAY_A );
		$leads = (int) ( $row['leads'] ?? 0 );
		$won   = (int) ( $row['won'] ?? 0 );
		return array(
			'leads' => $leads,
			'won' => $won,
			'conversion' => $leads > 0 ? round( $won / $leads * 100, 1 ) : 0,
			'value' => (float) ( $row['value'] ?? 0 ),
		);
	}

	public static function stage_aging( $owner = 0 ) {
		global $wpdb;
		$where = 'deleted_at IS NULL' . self::owner_where( $owner );
		$rows = $wpdb->get_results(
			'SELECT stage, COUNT(*) contacts, AVG(TIMESTAMPDIFF(HOUR, COALESCE(stage_changed_at,created_at), NOW())) avg_hours '
			. 'FROM ' . SZC_Contacts::table() . " WHERE $where GROUP BY stage",
			OBJECT_K
		);
		$out = array();
		foreach ( SZC_Settings::stages() as $key => $label ) {
			$out[] = array(
				'stage' => $key,
				'label' => $label,
				'contacts' => isset( $rows[ $key ] ) ? (int) $rows[ $key ]->contacts : 0,
				'avg_days' => isset( $rows[ $key ] ) ? round( (float) $rows[ $key ]->avg_hours / 24, 1 ) : 0,
			);
		}
		return $out;
	}

	public static function source_performance( $from, $to, $owner = 0 ) {
		global $wpdb;
		$start = self::normalize_date( $from, wp_date( 'Y-m-d' ) ) . ' 00:00:00';
		$end   = self::normalize_date( $to, wp_date( 'Y-m-d' ) ) . ' 23:59:59';
		$where = 'deleted_at IS NULL AND created_at BETWEEN %s AND %s' . self::owner_where( $owner );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT IF(source='','بدون منبع',source) source, COUNT(*) leads, SUM(won_at IS NOT NULL) won,
			 SUM(CASE WHEN won_at IS NOT NULL THEN COALESCE(NULLIF(final_value,0),deal_value) ELSE 0 END) value
			 FROM " . SZC_Contacts::table() . " WHERE $where GROUP BY source ORDER BY leads DESC LIMIT 20",
			$start, $end
		) );
	}

	public static function campaign_performance( $from, $to, $owner = 0 ) {
		global $wpdb;
		$start = self::normalize_date( $from, wp_date( 'Y-m-d' ) ) . ' 00:00:00';
		$end   = self::normalize_date( $to, wp_date( 'Y-m-d' ) ) . ' 23:59:59';
		$where = 'deleted_at IS NULL AND created_at BETWEEN %s AND %s' . self::owner_where( $owner );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT IF(campaign='','بدون کمپین',campaign) campaign, COUNT(*) leads,
			 SUM(won_at IS NOT NULL) won,
			 SUM(COALESCE(expected_value,0)) expected_value,
			 SUM(CASE WHEN won_at IS NOT NULL THEN COALESCE(NULLIF(final_value,0),deal_value) ELSE 0 END) final_value
			 FROM " . SZC_Contacts::table() . " WHERE $where
			 GROUP BY campaign ORDER BY final_value DESC, leads DESC LIMIT 20",
			$start, $end
		) );
	}

	protected static function management_where( $filters, $alias = 'c' ) {
		global $wpdb;
		$p = $alias ? $alias . '.' : '';
		$where = array( $p . 'deleted_at IS NULL' );
		$vals = array();
		$from = self::normalize_date( $filters['from'] ?? '', wp_date( 'Y-m-d', time() - 30 * DAY_IN_SECONDS ) );
		$to   = self::normalize_date( $filters['to'] ?? '', wp_date( 'Y-m-d' ) );
		$where[] = $p . 'created_at BETWEEN %s AND %s'; $vals[] = $from . ' 00:00:00'; $vals[] = $to . ' 23:59:59';
		foreach ( array( 'source', 'campaign' ) as $key ) {
			if ( ! empty( $filters[ $key ] ) ) { $where[] = $p . "$key=%s"; $vals[] = sanitize_text_field( $filters[ $key ] ); }
		}
		if ( ! empty( $filters['owner'] ) ) { $where[] = $p . 'owner_id=%d'; $vals[] = (int) $filters['owner']; }
		if ( ! empty( $filters['group'] ) ) { $where[] = $p . 'group_id=%d'; $vals[] = (int) $filters['group']; }
		return array( implode( ' AND ', $where ), $vals );
	}

	/** Filtered manager KPIs, SLA, forecast and problem-lead counts. */
	public static function management_metrics( $filters ) {
		global $wpdb;
		list( $where, $vals ) = self::management_where( $filters );
		$table = SZC_Contacts::table();
		$sql = "SELECT COUNT(*) leads,
			SUM(owner_id=0) unassigned,
			SUM(stage NOT IN ('registered','not_interested','wrong') AND COALESCE(last_contacted_at,created_at)<DATE_SUB(NOW(),INTERVAL 3 DAY)) stagnant,
			SUM(expected_value) expected_value,
			SUM(CASE WHEN won_at IS NOT NULL THEN final_value ELSE 0 END) final_value
			FROM $table c WHERE $where";
		$row = $wpdb->get_row( $vals ? $wpdb->prepare( $sql, $vals ) : $sql, ARRAY_A );

		$sla_sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE,c.created_at,x.first_call)) avg_first_minutes,
			SUM(TIMESTAMPDIFF(MINUTE,c.created_at,x.first_call)<=60) within_sla,
			COUNT(x.first_call) contacted
			FROM $table c LEFT JOIN (
			 SELECT contact_id,MIN(created_at) first_call FROM {$wpdb->prefix}szc_activities WHERE type='call' GROUP BY contact_id
			) x ON x.contact_id=c.id WHERE $where";
		$sla = $wpdb->get_row( $vals ? $wpdb->prepare( $sla_sql, $vals ) : $sla_sql, ARRAY_A );
		$overdue_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}szc_activities a JOIN $table c ON c.id=a.contact_id
			WHERE $where AND a.type='followup' AND a.done=0 AND a.due_at<%s";
		$overdue_args = array_merge( $vals, array( current_time( 'mysql' ) ) );
		$overdue = (int) $wpdb->get_var( $wpdb->prepare( $overdue_sql, $overdue_args ) );
		$stage_sql = "SELECT c.stage,SUM(c.expected_value) value FROM $table c WHERE $where GROUP BY c.stage";
		$stage_values = $vals ? $wpdb->get_results( $wpdb->prepare( $stage_sql, $vals ) ) : $wpdb->get_results( $stage_sql );
		$keys = SZC_Settings::funnel_stage_keys(); $forecast = 0;
		foreach ( $stage_values as $sv ) {
			$pos = array_search( $sv->stage, $keys, true );
			if ( $sv->stage === 'registered' ) { $probability = 1; }
			elseif ( $pos === false ) { $probability = 0; }
			else { $probability = ( $pos + 1 ) / max( 1, count( $keys ) ); }
			$forecast += (float) $sv->value * $probability;
		}
		$contacted = (int) ( $sla['contacted'] ?? 0 );
		return array(
			'leads' => (int) ( $row['leads'] ?? 0 ),
			'unassigned' => (int) ( $row['unassigned'] ?? 0 ),
			'stagnant' => (int) ( $row['stagnant'] ?? 0 ),
			'expected_value' => (float) ( $row['expected_value'] ?? 0 ),
			'final_value' => (float) ( $row['final_value'] ?? 0 ),
			'forecast' => round( $forecast ),
			'overdue' => $overdue,
			'avg_first_minutes' => round( (float) ( $sla['avg_first_minutes'] ?? 0 ), 1 ),
			'sla_rate' => $contacted > 0 ? round( (int) $sla['within_sla'] / $contacted * 100, 1 ) : 0,
		);
	}

	public static function stage_conversion( $filters ) {
		global $wpdb;
		list( $where, $vals ) = self::management_where( $filters );
		$contacts = SZC_Contacts::table();
		$hist = SZC_Contacts::history_table();
		$stages = SZC_Settings::funnel_stage_keys();
		$out = array();
		$previous = 0;
		foreach ( $stages as $index => $stage ) {
			if ( $index === 0 ) {
				$sql = "SELECT COUNT(*) FROM $contacts c WHERE $where";
				$count = (int) ( $vals ? $wpdb->get_var( $wpdb->prepare( $sql, $vals ) ) : $wpdb->get_var( $sql ) );
			} else {
				$sql = "SELECT COUNT(DISTINCT h.contact_id) FROM $hist h JOIN $contacts c ON c.id=h.contact_id WHERE $where AND h.event_type='stage' AND h.new_value=%s";
				$args = array_merge( $vals, array( $stage ) );
				$count = (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
			}
			$out[] = array(
				'stage' => $stage, 'label' => SZC_Settings::stage_label( $stage ), 'count' => $count,
				'rate' => $index === 0 ? 100 : ( $previous > 0 ? round( $count / $previous * 100, 1 ) : 0 ),
			);
			$previous = $count;
		}
		return $out;
	}

	public static function historical_stage_aging( $filters = array() ) {
		global $wpdb;
		list( $where, $vals ) = self::management_where( $filters );
		$sql = 'SELECT h.old_value stage,COUNT(*) transitions,AVG(h.duration_hours) avg_hours FROM '
			. SZC_Contacts::history_table() . ' h JOIN ' . SZC_Contacts::table() . " c ON c.id=h.contact_id
			 WHERE $where AND h.event_type='stage' AND h.old_value<>'' GROUP BY h.old_value";
		$rows = $vals ? $wpdb->get_results( $wpdb->prepare( $sql, $vals ), OBJECT_K ) : $wpdb->get_results( $sql, OBJECT_K );
		$out = array();
		foreach ( SZC_Settings::stages() as $key => $label ) {
			$out[] = array( 'label' => $label, 'transitions' => isset( $rows[ $key ] ) ? (int) $rows[ $key ]->transitions : 0, 'avg_days' => isset( $rows[ $key ] ) ? round( $rows[ $key ]->avg_hours / 24, 1 ) : 0 );
		}
		return $out;
	}

	public static function filter_options() {
		global $wpdb;
		$t = SZC_Contacts::table();
		return array(
			'sources' => $wpdb->get_col( "SELECT DISTINCT source FROM $t WHERE deleted_at IS NULL AND source<>'' ORDER BY source LIMIT 200" ),
			'campaigns' => $wpdb->get_col( "SELECT DISTINCT campaign FROM $t WHERE deleted_at IS NULL AND campaign<>'' ORDER BY campaign LIMIT 200" ),
		);
	}

	/** جریان آخرین فعالیت‌های ثبت‌شده‌ی کارشناسان برای نظارت مدیریت. */
	public static function recent_team_activity( $limit = 50 ) {
		global $wpdb;
		$limit = max( 1, min( 200, (int) $limit ) );
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT x.*, c.first_name, c.last_name, c.mobile
			 FROM (
				SELECT id, contact_id, user_id, type, outcome, body, created_at
				FROM {$wpdb->prefix}szc_activities
				UNION ALL
				SELECT id, contact_id, user_id, 'note' type, '' outcome, body, created_at
				FROM {$wpdb->prefix}szc_notes
			 ) x
			 LEFT JOIN " . SZC_Contacts::table() . ' c ON c.id=x.contact_id
			 WHERE x.user_id>=%d AND (c.deleted_at IS NULL OR c.id IS NULL)
			 ORDER BY x.created_at DESC, x.id DESC LIMIT %d',
			SZC_Agents::OFFSET,
			$limit
		) );
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
		$where = 'deleted_at IS NULL' . self::owner_where( $owner );
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
				'answered' => self::answered_calls_today( $uid ),
			);
		}
		usort( $out, function ( $a, $b ) { return $b['calls'] <=> $a['calls']; } );
		return $out;
	}
}
