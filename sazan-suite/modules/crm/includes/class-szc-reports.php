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
			FROM $act WHERE user_id=%d AND created_at BETWEEN %s AND %s",
			(int) $owner, $start, $end
		), ARRAY_A );
		$new_leads = (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . SZC_Contacts::table() . ' WHERE deleted_at IS NULL AND created_by=%d AND created_at BETWEEN %s AND %s',
			(int) $owner, $start, $end
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
			"SELECT COUNT(*) FROM $act WHERE user_id=%d AND type='followup' AND done=1 AND created_at BETWEEN %s AND %s",
			(int) $owner, $start, $end
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
