<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * تارگت روزانه‌ی تماس موفق و اعلان مدیریتی.
 *
 * تماس موفق یعنی فعالیت call با نتیجه‌ی answered. اعلان‌ها در آستانه‌های
 * ۵۰، ۸۰ و ۱۰۰ درصد و یک‌بار در جمع‌بندی پایان روز ارسال می‌شوند.
 */
class SZC_Goals {

	const STATE_OPTION = 'szc_goal_alert_state';

	/** شماره‌های معتبر مدیران: فهرست دستی + موبایل کاربران مدیر CRM. */
	public static function manager_mobiles() {
		$raw = (string) SZC_Settings::get( 'manager_alert_mobiles' );
		$parts = preg_split( '/[\s,،;]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY );
		$out = array();
		foreach ( (array) $parts as $part ) {
			$m = szc_normalize_mobile( $part );
			if ( szc_is_valid_mobile( $m ) ) {
				$out[] = $m;
			}
		}
		foreach ( SZC_Settings::manager_ids() as $uid ) {
			$m = szc_user_mobile( $uid );
			if ( szc_is_valid_mobile( $m ) ) {
				$out[] = $m;
			}
		}
		return array_values( array_unique( $out ) );
	}

	protected static function state() {
		$today = wp_date( 'Y-m-d' );
		$state = get_option( self::STATE_OPTION, array() );
		if ( ! is_array( $state ) || ( $state['date'] ?? '' ) !== $today ) {
			$state = array( 'date' => $today, 'thresholds' => array(), 'summaries' => array(), 'risks' => array() );
		}
		return $state;
	}

	protected static function save_state( $state ) {
		update_option( self::STATE_OPTION, $state, false );
	}

	protected static function threshold_message( $agent, $stats, $threshold ) {
		$target = max( 1, (int) $agent->daily_answered_target );
		$status = $threshold >= 100 ? 'هدف روزانه کامل شد.'
			: ( $threshold >= 80 ? 'به تکمیل هدف نزدیک است.' : 'نیمی از هدف تکمیل شد.' );
		return "گزارش CRM امروز\n"
			. $agent->name . ': ' . szc_fa_digits( $stats['answered'] ) . ' از ' . szc_fa_digits( $target )
			. ' تماس موفق (' . szc_fa_digits( min( 100, $stats['progress'] ) ) . "٪)\n"
			. 'کل تماس ' . szc_fa_digits( $stats['calls'] )
			. ' | بی‌پاسخ ' . szc_fa_digits( $stats['no_answer'] )
			. ' | پیگیری ' . szc_fa_digits( $stats['followups'] ) . "\n"
			. 'وضعیت: ' . $status;
	}

	/** بررسی آستانه‌ها روی کرون پنج‌دقیقه‌ای؛ پرش مستقیم، فقط بالاترین آستانه را می‌فرستد. */
	public static function run_alerts() {
		if ( ! SZC_SMS::enabled() ) {
			return;
		}
		// از ارسال تکراری در اجرای هم‌زمان WP-Cron جلوگیری شود.
		if ( get_transient( 'szc_goal_alert_lock' ) ) {
			return;
		}
		set_transient( 'szc_goal_alert_lock', 1, 4 * MINUTE_IN_SECONDS );
		$mobiles = self::manager_mobiles();
		if ( ! $mobiles ) {
			delete_transient( 'szc_goal_alert_lock' );
			return;
		}
		if ( SZC_Settings::get( 'goal_alerts' ) ) {
			$state = self::state();
			foreach ( SZC_Agents::active() as $agent ) {
				$target = (int) ( $agent->daily_answered_target ?? 0 );
				if ( $target <= 0 ) {
					continue;
				}
				$owner = SZC_Agents::to_owner( (int) $agent->id );
				$stats = SZC_Reports::agent_day_stats( $owner );
				$crossed = 0;
				foreach ( array( 50, 80, 100 ) as $threshold ) {
					if ( $stats['progress'] >= $threshold ) {
						$crossed = $threshold;
					}
				}
				if ( ! $crossed ) {
					continue;
				}
				foreach ( $mobiles as $mobile ) {
					$key = $owner . ':' . $mobile;
					$sent = (int) ( $state['thresholds'][ $key ] ?? 0 );
					if ( $sent >= $crossed ) {
						continue;
					}
					$res = SZC_SMS::send_system_pattern( $mobile, self::threshold_message( $agent, $stats, $crossed ) );
					if ( ! empty( $res['ok'] ) ) {
						$state['thresholds'][ $key ] = $crossed;
					}
				}
			}
			self::save_state( $state );
			self::send_risk_alerts( $state, $mobiles );
		}
		self::maybe_send_daily_summary();
		delete_transient( 'szc_goal_alert_lock' );
	}

	protected static function send_risk_alerts( &$state, $mobiles ) {
		global $wpdb;
		if ( (int) wp_date( 'G' ) < 14 ) { return; }
		$act = $wpdb->prefix . 'szc_activities';
		$from = wp_date( 'Y-m-d 00:00:00', time() - 7 * DAY_IN_SECONDS );
		$to   = wp_date( 'Y-m-d 23:59:59', time() - DAY_IN_SECONDS );
		foreach ( SZC_Agents::active() as $agent ) {
			$owner = SZC_Agents::to_owner( (int) $agent->id );
			$today = SZC_Reports::agent_day_stats( $owner );
			$avg_calls = (float) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*)/7 FROM $act WHERE user_id=%d AND type='call' AND created_at BETWEEN %s AND %s",
				$owner, $from, $to
			) );
			$alerts = array();
			if ( $today['overdue'] >= 5 ) {
				$alerts['overdue'] = $agent->name . ' دارای ' . szc_fa_digits( $today['overdue'] ) . ' پیگیری عقب‌افتاده است.';
			}
			if ( $avg_calls >= 5 && $today['calls'] < $avg_calls * 0.6 ) {
				$alerts['decline'] = 'عملکرد تماس ' . $agent->name . ' افت کرده: امروز ' . szc_fa_digits( $today['calls'] ) . ' در برابر میانگین ' . szc_fa_digits( round( $avg_calls, 1 ) ) . ' تماس.';
			}
			foreach ( $alerts as $type => $message ) {
				$key = $owner . ':' . $type;
				if ( ! empty( $state['risks'][ $key ] ) ) { continue; }
				$sent = false;
				foreach ( $mobiles as $mobile ) {
					$res = SZC_SMS::send_system_pattern( $mobile, "هشدار مدیریتی CRM\n" . $message );
					$sent = $sent || ! empty( $res['ok'] );
				}
				if ( $sent ) { $state['risks'][ $key ] = 1; }
			}
		}
		self::save_state( $state );
	}

	protected static function summary_message() {
		$lines = array( 'جمع‌بندی تیم فروش - ' . wp_date( 'Y/m/d' ) );
		$total_answered = 0;
		$total_target   = 0;
		foreach ( SZC_Agents::active() as $agent ) {
			$target = max( 0, (int) ( $agent->daily_answered_target ?? 0 ) );
			$owner  = SZC_Agents::to_owner( (int) $agent->id );
			$stats  = SZC_Reports::agent_day_stats( $owner );
			$total_answered += $stats['answered'];
			$total_target   += $target;
			$line = $agent->name . ': ' . szc_fa_digits( $stats['answered'] ) . '/' . szc_fa_digits( $target )
				. ' تماس موفق، ' . szc_fa_digits( $stats['followups'] ) . '/' . szc_fa_digits( $stats['followup_target'] )
				. ' پیگیری، ' . szc_fa_digits( $stats['won_contacts'] ) . '/' . szc_fa_digits( $stats['conversion_target'] ) . ' تبدیل';
			if ( $stats['overdue'] > 0 ) {
				$line .= '، ' . szc_fa_digits( $stats['overdue'] ) . ' پیگیری عقب‌افتاده';
			}
			$lines[] = $line;
		}
		$team_pct = $total_target > 0 ? round( $total_answered / $total_target * 100 ) : 0;
		$lines[] = 'کل تیم: ' . szc_fa_digits( $total_answered ) . '/' . szc_fa_digits( $total_target )
			. ' (' . szc_fa_digits( $team_pct ) . '٪)';
		return implode( "\n", $lines );
	}

	public static function maybe_send_daily_summary( $force = false ) {
		if ( ! SZC_SMS::enabled() ) {
			return array( 'sent' => 0, 'message' => 'سرویس پیامک فعال نیست.' );
		}
		if ( ! $force && ! SZC_Settings::get( 'goal_daily_summary' ) ) {
			return array( 'sent' => 0, 'message' => 'جمع‌بندی روزانه غیرفعال است.' );
		}
		$hour = min( 23, max( 0, (int) SZC_Settings::get( 'goal_summary_hour' ) ) );
		if ( ! $force && (int) wp_date( 'G' ) < $hour ) {
			return array( 'sent' => 0, 'message' => 'هنوز زمان جمع‌بندی نرسیده است.' );
		}
		$mobiles = self::manager_mobiles();
		if ( ! $mobiles ) {
			return array( 'sent' => 0, 'message' => 'شماره مدیر تعریف نشده است.' );
		}
		$state = self::state();
		$text  = self::summary_message();
		$sent  = 0;
		foreach ( $mobiles as $mobile ) {
			if ( ! $force && ! empty( $state['summaries'][ $mobile ] ) ) {
				continue;
			}
			$res = SZC_SMS::send_system_pattern( $mobile, $text );
			if ( ! empty( $res['ok'] ) ) {
				$state['summaries'][ $mobile ] = 1;
				$sent++;
			}
		}
		self::save_state( $state );
		return array(
			'sent'    => $sent,
			'message' => $sent ? 'جمع‌بندی برای ' . szc_fa_digits( $sent ) . ' مدیر ارسال شد.' : 'پیام تازه‌ای برای ارسال نبود.',
		);
	}
}
