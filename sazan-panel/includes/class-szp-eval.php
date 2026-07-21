<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * «ارزیابی» — تارگت مالیِ هر «جلسه» و سنجش تحقق آن. موبایل‌اول و ساده برای کاربران غیرفنی.
 *
 * مدل جلسه (سراسری، تاریخ‌محور):
 *   - جلسات هر «سه‌شنبه‌ی شمسی» برگزار می‌شوند؛ تاریخِ «جلسه ۱» در تنظیمات تعیین می‌شود.
 *   - جلسه N برای همه یک سه‌شنبه‌ی مشخص است: تاریخِ جلسه ۱ + (N-۱) هفته.
 *
 * چرخه‌ی هر جلسه:
 *   ۱) ثبت تارگت: در هفته‌ی همان جلسه.
 *   ۲) ثبت نتیجه: «دوشنبه و سه‌شنبه‌ی هفته‌ی بعد» (خودکار از روی تاریخ جلسه محاسبه می‌شود).
 *
 * نقش‌ها (SZP_Eval_Roles):
 *   کوچینگ → همه را می‌بیند | مدیر سازمان → زیرمجموعه را | تیم فروش → خودش ثبت می‌کند |
 *   مدیر سازمان/تیم فروش → هم تیمش را می‌بیند هم خودش ثبت می‌کند.
 */
class SZP_Eval {

	const DAY_TARGET = 2; // سه‌شنبه
	const DAY_RESULT = 1; // دوشنبه
	const NEAR       = 0.85;
	const OPTION     = 'szp_eval_settings';

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szp_eval';
	}

	/* ==================== تنظیمات ==================== */

	public static function defaults() {
		return array(
			'start_week'         => 1,
			'currency'           => 'تومان',
			'near'               => 85,
			'day_target'         => 2,
			'day_result'         => 1,
			'session1_date'      => '', // (قدیمی) تاریخ میلادی سه‌شنبه‌ی «جلسه ۱»
			'anchor_session'     => 0,  // «الان جلسه چند هستیم»
			'anchor_date'        => '', // تاریخ میلادیِ همان جلسه (یک سه‌شنبه، Y-m-d)
			'coaching_users'     => '', // کاربران نقش کوچینگ (نام‌کاربری/ایمیل/شناسه)
			'sms_enabled'        => 0,
			'sms_provider'       => 'smsir',
			'sms_base'           => '',
			'sms_apikey'         => '',
			'sms_originator'     => '',
			'sms_mode'           => 'pattern',
			'sms_var'            => 'name',
			'sms_pattern_target' => '',
			'sms_pattern_result' => '',
			'sms_text_target'    => '%name% عزیز، امروز روز ثبت تارگت این جلسه است. لطفاً تارگت را در پنل ثبت کنید.',
			'sms_text_result'    => '%name% عزیز، امروز مهلت ثبت نتیجه‌ی تارگت جلسه‌ی قبل است. لطفاً نتیجه را در پنل وارد کنید.',
			// پیامک اطلاع‌رسانی به مدیر سازمان و کوچ‌ها هنگام ثبت تارگت/نتیجه‌ی تیم فروش.
			'notify_enabled'            => 1,
			'sms_pattern_notify_target' => '',
			'sms_pattern_notify_result' => '',
			'sms_text_notify_target'    => 'تیم فروش «%name%» برای %session% تارگت %amount% ثبت کرد.',
			'sms_text_notify_result'    => 'تیم فروش «%name%» نتیجه‌ی %session% را %amount% ثبت کرد (وضعیت: %status%).',
			'board_viewers'      => '',
			'sms_pattern_session' => '',
			'sms_pattern_survey'  => '',
			'sms_text_session'    => '%name% عزیز، جلسه‌ی «%title%» با کوچ %coach% در تاریخ %date% ساعت %time% ثبت شد.',
			'sms_text_survey'     => '%name% عزیز، از حضور شما در جلسه سپاسگزاریم. لطفاً نظرسنجی کوتاه را تکمیل کنید: %link%',
			'survey_url'          => '',
			'survey_delay'        => 60,
		);
	}

	public static function settings() {
		$s = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $s ) ? $s : array(), self::defaults() );
	}

	public static function opt( $key ) {
		$s = self::settings();
		return isset( $s[ $key ] ) ? $s[ $key ] : null;
	}

	public static function near() {
		$n = (float) self::opt( 'near' );
		if ( $n <= 0 || $n >= 100 ) {
			$n = 85;
		}
		return $n / 100;
	}

	public static function day_target() { return (int) self::opt( 'day_target' ); }
	public static function day_result() { return (int) self::opt( 'day_result' ); }
	public static function start_week() { return max( 1, (int) self::opt( 'start_week' ) ); }
	public static function currency() { return (string) self::opt( 'currency' ); }

	/* ==================== تقویم جلسات (سراسری، سه‌شنبه‌ها) ==================== */

	/** نزدیک‌ترین سه‌شنبه (امروز یا قبل‌تر) به‌صورت Y-m-d. */
	protected static function nearest_tuesday_ymd() {
		$now  = new DateTime( 'now', wp_timezone() );
		$dow  = (int) $now->format( 'N' ); // دوشنبه=۱ … سه‌شنبه=۲
		$back = ( $dow - self::DAY_TARGET + 7 ) % 7;
		if ( $back ) {
			$now->modify( '-' . $back . ' days' );
		}
		return $now->format( 'Y-m-d' );
	}

	/**
	 * لنگرِ تقویم جلسات: array( شماره‌ی جلسه، تاریخ میلادی آن جلسه Y-m-d ).
	 * ادمین می‌گوید «الان جلسه چند هستیم» و «تاریخِ همین جلسه»؛ بقیه از روی همین محاسبه می‌شود.
	 */
	public static function anchor() {
		$no = (int) self::opt( 'anchor_session' );
		$d  = trim( (string) self::opt( 'anchor_date' ) );
		if ( $no >= 1 && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ) {
			return array( $no, $d );
		}
		// سازگاری با تنظیم قدیمیِ «تاریخ جلسه ۱».
		$s1 = trim( (string) self::opt( 'session1_date' ) );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s1 ) ) {
			return array( 1, $s1 );
		}
		return array( 1, self::nearest_tuesday_ymd() );
	}

	/** تاریخ میلادیِ «جلسه ۱» به‌صورت Y-m-d (از روی لنگر). */
	public static function session1_date() {
		return self::session_date( 1 )->format( 'Y-m-d' );
	}

	/** DateTime تاریخِ جلسه N (ظهرِ همان روز، برای مصونیت از تغییر ساعت). */
	public static function session_date( $n ) {
		list( $ano, $ad ) = self::anchor();
		$dt  = new DateTime( $ad . ' 12:00:00', wp_timezone() );
		$off = 7 * ( (int) $n - $ano );
		if ( $off > 0 ) {
			$dt->modify( '+' . $off . ' days' );
		} elseif ( $off < 0 ) {
			$dt->modify( $off . ' days' );
		}
		return $dt;
	}

	/** شماره‌ی روز (از مبدأ) در منطقه‌ی زمانی سایت. */
	protected static function daynum( DateTime $dt ) {
		return (int) floor( ( $dt->getTimestamp() + $dt->getOffset() ) / DAY_IN_SECONDS );
	}

	protected static function today_daynum() {
		return self::daynum( new DateTime( 'now', wp_timezone() ) );
	}

	protected static function session_daynum( $n ) {
		return self::daynum( self::session_date( $n ) );
	}

	/** جلسه‌ی «جاری» برای ثبت تارگت (بر اساس امروز). */
	public static function current_session() {
		$mon1 = self::session_daynum( 1 ) - 1; // دوشنبه‌ی هفته‌ی جلسه ۱
		$diff = self::today_daynum() - $mon1;
		$n    = 1 + (int) floor( $diff / 7 );
		return max( 1, $n );
	}

	/** جلسه‌ای که «امروز» زمان ثبت نتیجه‌اش است (۰ اگر امروز روز نتیجه نیست). */
	public static function due_result_session() {
		$cand = self::current_session() - 1;
		return ( $cand >= 1 && self::result_open( $cand ) ) ? $cand : 0;
	}

	/** آیا امروز در بازه‌ی ثبتِ تارگتِ جلسه N است؟ (هفته‌ی همان جلسه) */
	public static function target_open( $n ) {
		$d = self::session_daynum( $n );
		$t = self::today_daynum();
		return $t >= ( $d - 1 ) && $t <= ( $d + 5 );
	}

	/** آیا امروز در بازه‌ی ثبتِ نتیجه‌ی جلسه N است؟ (دوشنبه و سه‌شنبه‌ی هفته‌ی بعد) */
	public static function result_open( $n ) {
		$d = self::session_daynum( $n );
		$t = self::today_daynum();
		return $t >= ( $d + 6 ) && $t <= ( $d + 7 );
	}

	/** برچسب فارسیِ جلسه: «جلسه ۹». */
	public static function session_label( $n ) {
		return 'جلسه ' . szp_fa_digits( (int) $n );
	}

	/** تاریخ شمسیِ روز جلسه N (با نام روز هفته). */
	public static function session_date_fa( $n, $with_year = false ) {
		return self::fa_date_of( self::session_date( $n ), $with_year );
	}

	/** تاریخ شمسی نتیجه‌ی جلسه N (دوشنبه‌ی هفته‌ی بعد). */
	public static function result_date_fa( $n ) {
		$dt = self::session_date( $n );
		$dt->modify( '+6 days' );
		return self::fa_date_of( $dt );
	}

	protected static function fa_date_of( DateTime $dt, $with_year = false ) {
		list( $jy, $jm, $jd ) = szp_g2j( (int) $dt->format( 'Y' ), (int) $dt->format( 'n' ), (int) $dt->format( 'j' ) );
		$months = array( '', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
		$dow    = self::day_name( (int) $dt->format( 'N' ) );
		$out    = $dow . ' ' . $jd . ' ' . $months[ $jm ] . ( $with_year ? ( ' ' . $jy ) : '' );
		return szp_fa_digits( $out );
	}

	/** آیا کاربر می‌تواند قفلِ روزها را دور بزند؟ (ادمین/کوچ/مدیر سازمان) */
	public static function can_bypass( $uid = 0 ) {
		$uid = $uid ? (int) $uid : get_current_user_id();
		if ( user_can( $uid, 'manage_options' ) ) {
			return true;
		}
		if ( ! apply_filters( 'szp_eval_enforce_days', true ) ) {
			return true;
		}
		return class_exists( 'SZP_Eval_Roles' ) && ( SZP_Eval_Roles::is_coaching( $uid ) || SZP_Eval_Roles::is_org_manager( $uid ) );
	}

	/* ==================== داده ==================== */

	public static function weeks( $user_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' WHERE user_id=%d ORDER BY week_no ASC', (int) $user_id ) );
	}

	public static function latest( $user_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' WHERE user_id=%d ORDER BY week_no DESC LIMIT 1', (int) $user_id ) );
	}

	public static function get_week( $user_id, $week_no ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' WHERE user_id=%d AND week_no=%d', (int) $user_id, (int) $week_no ) );
	}

	/** ثبت/به‌روزرسانیِ تارگتِ یک جلسه‌ی مشخص. خروجی: array( ok, session, created, status ). */
	public static function set_session_target( $user_id, $session_no, $amount ) {
		global $wpdb;
		$user_id    = (int) $user_id;
		$session_no = (int) $session_no;
		$amount     = max( 0, (float) $amount );
		if ( $user_id < 1 || $session_no < 1 ) {
			return array( 'ok' => false );
		}
		$now = current_time( 'mysql' );
		$row = self::get_week( $user_id, $session_no );
		if ( $row ) {
			$wpdb->update( self::table(),
				array( 'target' => $amount, 'target_set_at' => $now, 'updated_at' => $now ),
				array( 'id' => (int) $row->id ), array( '%f', '%s', '%s' ), array( '%d' ) );
			return array( 'ok' => true, 'session' => $session_no, 'created' => false, 'status' => self::compute_status( $amount, $row->result, (bool) $row->has_result ) );
		}
		$wpdb->insert( self::table(), array(
			'user_id'       => $user_id,
			'week_no'       => $session_no,
			'target'        => $amount,
			'result'        => 0,
			'has_result'    => 0,
			'target_set_at' => $now,
			'created_at'    => $now,
			'updated_at'    => $now,
		), array( '%d', '%d', '%f', '%f', '%d', '%s', '%s', '%s' ) );
		return array( 'ok' => true, 'session' => $session_no, 'created' => true, 'status' => 'pending' );
	}

	/** ثبتِ نتیجه‌ی یک جلسه‌ی مشخص (باید تارگت داشته باشد). خروجی: array( ok, session, status، reason? ). */
	public static function set_session_result( $user_id, $session_no, $amount, $note = '' ) {
		global $wpdb;
		$user_id    = (int) $user_id;
		$session_no = (int) $session_no;
		$amount     = max( 0, (float) $amount );
		$note       = sanitize_textarea_field( $note );
		$row        = self::get_week( $user_id, $session_no );
		if ( ! $row || (float) $row->target <= 0 ) {
			return array( 'ok' => false, 'reason' => 'notarget' );
		}
		$now = current_time( 'mysql' );
		$wpdb->update( self::table(),
			array( 'result' => $amount, 'has_result' => 1, 'note' => $note, 'result_set_at' => $row->result_set_at ?: $now, 'updated_at' => $now ),
			array( 'id' => (int) $row->id ), array( '%f', '%d', '%s', '%s', '%s' ), array( '%d' ) );
		return array( 'ok' => true, 'session' => $session_no, 'status' => self::compute_status( $row->target, $amount, true ) );
	}

	public static function can_edit_target() { return (bool) apply_filters( 'szp_eval_allow_edit_target', true ); }
	public static function can_edit_result() { return (bool) apply_filters( 'szp_eval_allow_edit_result', true ); }

	public static function edit_target( $user_id, $week_no, $amount ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$week_no = (int) $week_no;
		$amount  = max( 0, (float) $amount );
		$row     = self::get_week( $user_id, $week_no );
		if ( ! $row ) {
			return array( 'ok' => false );
		}
		$now = current_time( 'mysql' );
		$wpdb->update( self::table(),
			array( 'target' => $amount, 'target_set_at' => $now, 'updated_at' => $now ),
			array( 'id' => (int) $row->id ), array( '%f', '%s', '%s' ), array( '%d' ) );
		return array(
			'ok'     => true,
			'week'   => $week_no,
			'target' => $amount,
			'status' => self::compute_status( $amount, $row->result, (bool) $row->has_result ),
			'pct'    => $row->has_result ? self::pct( $amount, $row->result ) : 0,
		);
	}

	public static function edit_result( $user_id, $week_no, $amount ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$week_no = (int) $week_no;
		$amount  = max( 0, (float) $amount );
		$row     = self::get_week( $user_id, $week_no );
		if ( ! $row ) {
			return array( 'ok' => false );
		}
		$now = current_time( 'mysql' );
		$wpdb->update( self::table(),
			array( 'result' => $amount, 'has_result' => 1, 'result_set_at' => $row->result_set_at ?: $now, 'updated_at' => $now ),
			array( 'id' => (int) $row->id ), array( '%f', '%d', '%s', '%s' ), array( '%d' ) );
		return array(
			'ok'     => true,
			'week'   => $week_no,
			'result' => $amount,
			'status' => self::compute_status( $row->target, $amount, true ),
			'pct'    => self::pct( $row->target, $amount ),
		);
	}

	/** درج/به‌روزرسانی دستی یک جلسه (برای مدیر/پیشخوان). */
	public static function upsert( $user_id, $week_no, $target, $result = null, $note = null ) {
		global $wpdb;
		$user_id = (int) $user_id;
		$week_no = (int) $week_no;
		if ( $user_id < 1 || $week_no < 1 ) {
			return;
		}
		$now        = current_time( 'mysql' );
		$has_result = ( $result !== null && $result !== '' ) ? 1 : 0;
		$result_val = $has_result ? (float) $result : 0;
		$note_val   = ( $note === null ) ? null : sanitize_textarea_field( $note );
		$existing   = self::get_week( $user_id, $week_no );

		if ( $existing ) {
			$data    = array(
				'target'        => (float) $target,
				'result'        => $result_val,
				'has_result'    => $has_result,
				'result_set_at' => $has_result ? ( $existing->result_set_at ?: $now ) : null,
				'updated_at'    => $now,
			);
			$formats = array( '%f', '%f', '%d', '%s', '%s' );
			if ( $note_val !== null ) {
				$data['note'] = $note_val;
				$formats[]    = '%s';
			}
			$wpdb->update( self::table(), $data, array( 'id' => (int) $existing->id ), $formats, array( '%d' ) );
		} else {
			$wpdb->insert( self::table(), array(
				'user_id'       => $user_id,
				'week_no'       => $week_no,
				'target'        => (float) $target,
				'result'        => $result_val,
				'has_result'    => $has_result,
				'note'          => $note_val === null ? '' : $note_val,
				'target_set_at' => $now,
				'result_set_at' => $has_result ? $now : null,
				'created_at'    => $now,
				'updated_at'    => $now,
			), array( '%d', '%d', '%f', '%f', '%d', '%s', '%s', '%s', '%s', '%s' ) );
		}
	}

	public static function delete_week( $user_id, $week_no ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'user_id' => (int) $user_id, 'week_no' => (int) $week_no ), array( '%d', '%d' ) );
	}

	/* ==================== وضعیت ==================== */

	public static function statuses() {
		return array(
			'beyond'  => array( 'label' => 'فراتر از تارگت', 'color' => '#0ea5e9', 'emoji' => '🚀', 'msg' => 'فراتر از تارگت رفتی، فوق‌العاده بود!' ),
			'success' => array( 'label' => 'موفق', 'color' => '#16a34a', 'emoji' => '🎯', 'msg' => 'دقیقاً به تارگت رسیدی!' ),
			'improve' => array( 'label' => 'قابل بهبود', 'color' => '#f59e0b', 'emoji' => '📈', 'msg' => 'نزدیک بودی؛ کمی تا تارگت فاصله داری.' ),
			'ontrack' => array( 'label' => 'در مسیر', 'color' => '#ef4444', 'emoji' => '🧭', 'msg' => 'در مسیر هستی؛ فاصله تا تارگت زیاد است.' ),
			'pending' => array( 'label' => 'در انتظار نتیجه', 'color' => '#9ca3af', 'emoji' => '⏳', 'msg' => 'هنوز نتیجه ثبت نشده است.' ),
		);
	}

	public static function status_meta( $key ) {
		$all = self::statuses();
		return isset( $all[ $key ] ) ? $all[ $key ] : $all['pending'];
	}

	public static function compute_status( $target, $result, $has_result ) {
		if ( ! $has_result ) {
			return 'pending';
		}
		$target = (float) $target;
		$result = (float) $result;
		if ( $target <= 0 ) {
			return 'pending';
		}
		if ( $result > $target ) {
			return 'beyond';
		}
		if ( $result == $target ) {
			return 'success';
		}
		if ( $result >= $target * self::near() ) {
			return 'improve';
		}
		return 'ontrack';
	}

	public static function pct( $target, $result ) {
		$target = (float) $target;
		if ( $target <= 0 ) {
			return 0;
		}
		return (int) round( ( (float) $result / $target ) * 100 );
	}

	/* ==================== قفل روز (سازگاری قدیمی) ==================== */

	public static function day_locked( $which ) {
		if ( current_user_can( 'manage_options' ) ) {
			return false;
		}
		if ( ! apply_filters( 'szp_eval_enforce_days', true ) ) {
			return false;
		}
		$need = ( $which === 'target' ) ? self::day_target() : self::day_result();
		return (int) wp_date( 'N' ) !== $need;
	}

	public static function day_name( $n ) {
		$names = array( 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه', 6 => 'شنبه', 7 => 'یکشنبه' );
		return isset( $names[ $n ] ) ? $names[ $n ] : '';
	}

	public static function days_until( $target_dow ) {
		$today = (int) wp_date( 'N' );
		return ( (int) $target_dow - $today + 7 ) % 7;
	}

	/* ==================== دسترسی به تابلو/دفتر ==================== */

	protected static function parse_viewers( $str ) {
		return class_exists( 'SZP_Eval_Roles' ) ? SZP_Eval_Roles::parse_user_list( $str ) : array();
	}

	public static function board_viewer_ids( $extra = '' ) {
		$ids = self::parse_viewers( (string) self::opt( 'board_viewers' ) );
		if ( $extra !== '' ) {
			$ids = array_merge( $ids, self::parse_viewers( $extra ) );
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	public static function can_view_board( $extra = '' ) {
		$cap = apply_filters( 'szp_eval_board_cap', 'manage_options' );
		if ( current_user_can( $cap ) ) {
			return true;
		}
		$uid = get_current_user_id();
		if ( $uid && class_exists( 'SZP_Eval_Roles' ) && SZP_Eval_Roles::sees_team( $uid ) ) {
			return true;
		}
		if ( $uid && in_array( $uid, self::board_viewer_ids( $extra ), true ) ) {
			return true;
		}
		return (bool) apply_filters( 'szp_eval_can_view_board', false, $uid, $extra );
	}

	public static function participants() {
		global $wpdb;
		return array_map( 'intval', $wpdb->get_col( 'SELECT DISTINCT user_id FROM ' . self::table() ) );
	}

	/* ==================== رندرِ ویجت نقش‌محور ==================== */

	public static function render( $atts = array() ) {
		$title    = ( isset( $atts['title'] ) && $atts['title'] !== '' ) ? $atts['title'] : 'ارزیابی';
		$currency = ( isset( $atts['currency'] ) && $atts['currency'] !== '' ) ? (string) $atts['currency'] : self::currency();

		if ( ! is_user_logged_in() ) {
			return '<div class="szp"><div class="szp-empty">برای مشاهده‌ی ارزیابی ابتدا وارد شوید.</div></div>';
		}
		$uid = get_current_user_id();

		$has_roles    = class_exists( 'SZP_Eval_Roles' );
		$can_register = ! $has_roles || SZP_Eval_Roles::registers_own( $uid );
		$sees_team    = $has_roles && SZP_Eval_Roles::sees_team( $uid );
		// اگر هیچ نقشی نداشت، مثل «تیم فروش» فقط ثبتِ خودش را ببیند (سازگاری قدیمی).
		if ( $has_roles && ! $can_register && ! $sees_team ) {
			$can_register = true;
		}

		$role_slug  = $has_roles ? SZP_Eval_Roles::primary_role( $uid ) : '';
		$role_label = $role_slug ? SZP_Eval_Roles::role_label( $role_slug ) : '';
		if ( $sees_team && $has_roles && SZP_Eval_Roles::is_coaching( $uid ) && ! $role_label ) {
			$role_label = 'کوچینگ';
		}

		ob_start(); ?>
		<div class="szp">
			<div class="szp-eval2" data-currency="<?php echo esc_attr( $currency ); ?>" data-near="<?php echo esc_attr( self::near() ); ?>">
				<div class="szp-ev2-top">
					<h3 class="szp-ev2-title"><?php echo esc_html( $title ); ?></h3>
					<?php if ( $role_label ) : ?><span class="szp-ev2-role"><?php echo esc_html( $role_label ); ?></span><?php endif; ?>
				</div>

				<?php
				if ( $can_register ) {
					echo self::self_panel( $uid, $currency ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				if ( $sees_team ) {
					echo self::team_panel( $uid, $currency ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** پنلِ «ثبتِ خودم»: انتخاب جلسه، مرحله‌ی تارگت و نتیجه (کاملاً متمایز)، خلاصه و سوابق. */
	protected static function self_panel( $uid, $currency ) {
		$weeks   = self::weeks( $uid );
		$sel     = self::selected_session( $uid );
		$row     = self::get_week( $uid, $sel );
		$bypass  = self::can_bypass( $uid );
		$manager = class_exists( 'SZP_Eval_Roles' ) ? SZP_Eval_Roles::manager_name( $uid ) : '';

		ob_start(); ?>
		<div class="szp-ev2-self">
			<?php if ( $manager !== '' ) : ?>
				<div class="szp-ev2-manager">زیرمجموعه‌ی مدیر سازمان: <b><?php echo esc_html( $manager ); ?></b></div>
			<?php endif; ?>

			<?php echo self::session_picker( $uid, $sel, $weeks ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<?php echo self::step_card( $uid, $sel, $row, $currency, $bypass ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<?php echo self::summary_html( $weeks ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<?php echo self::history_html( $weeks, $currency, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/** جلسه‌ی انتخاب‌شده: از پارامتر URL یا پیش‌فرضِ هوشمند. */
	protected static function selected_session( $uid ) {
		if ( isset( $_GET['szpev'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return max( 1, (int) $_GET['szpev'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		return self::default_session( $uid );
	}

	/** پیش‌فرض: اگر نتیجه‌ی جلسه‌ای امروز موعد دارد و هنوز ثبت نشده، همان؛ وگرنه جلسه‌ی جاری. */
	protected static function default_session( $uid ) {
		$due = self::due_result_session();
		if ( $due ) {
			$r = self::get_week( $uid, $due );
			if ( $r && (float) $r->target > 0 && ! $r->has_result ) {
				return $due;
			}
		}
		return self::current_session();
	}

	/** انتخابگرِ جلسه (کشویی) — با تغییر، به همان جلسه می‌رود. */
	protected static function session_picker( $uid, $sel, $weeks ) {
		$cur = self::current_session();
		$max = max( $cur + 1, $sel );
		foreach ( $weeks as $w ) {
			$max = max( $max, (int) $w->week_no );
		}
		$have = array();
		foreach ( $weeks as $w ) {
			$have[ (int) $w->week_no ] = $w;
		}

		ob_start(); ?>
		<div class="szp-ev2-picker">
			<label class="szp-ev2-picker-lbl">جلسه:</label>
			<select class="szp-ev2-session" onchange="if(this.value)window.location.href=this.value">
				<?php for ( $n = $max; $n >= 1; $n-- ) :
					$url  = esc_url( add_query_arg( 'szpev', $n ) );
					$mark = '';
					if ( isset( $have[ $n ] ) ) {
						$mark = $have[ $n ]->has_result ? ' ✓' : ( (float) $have[ $n ]->target > 0 ? ' •' : '' );
					}
					$is_cur = ( $n === $cur ) ? ' (جاری)' : '';
					?>
					<option value="<?php echo $url; ?>" <?php selected( $n, $sel ); ?>>
						<?php echo esc_html( self::session_label( $n ) . ' — ' . self::session_date_fa( $n ) . $is_cur . $mark ); ?>
					</option>
				<?php endfor; ?>
			</select>
		</div>
		<?php
		return ob_get_clean();
	}

	/** کارتِ مرحله‌ای: تارگت (آبی) و نتیجه (سبز) کاملاً متمایز از هم. */
	protected static function step_card( $uid, $sel, $row, $currency, $bypass ) {
		$has_target = $row && (float) $row->target > 0;
		$has_result = $row && $row->has_result;
		$t_open     = self::target_open( $sel ) || $bypass;
		$r_open     = self::result_open( $sel ) || $bypass;
		$date_fa    = self::session_date_fa( $sel, true );

		ob_start(); ?>
		<div class="szp-ev2-card">
			<div class="szp-ev2-card-head">
				<span class="szp-ev2-badge-session"><?php echo esc_html( self::session_label( $sel ) ); ?></span>
				<span class="szp-ev2-card-date">🗓 <?php echo esc_html( $date_fa ); ?></span>
			</div>

			<?php
			/* ---- مرحله ۱: تارگت ---- */
			if ( ! $has_target ) :
				if ( $t_open ) :
					echo self::form_target( $sel, $currency ); // phpcs:ignore WordPress.Security.EscapeOutput
				else :
					?>
					<div class="szp-ev2-step szp-ev2-step-locked">
						<div class="szp-ev2-step-t"><span class="szp-ev2-step-n step-blue">۱</span> ثبت تارگت</div>
						<p class="szp-ev2-lock">⏳ زمان ثبت تارگت این جلسه فرا نرسیده یا گذشته است. تارگت را در هفته‌ی جلسه (حدود <?php echo esc_html( self::session_date_fa( $sel ) ); ?>) ثبت کنید.</p>
					</div>
					<?php
				endif;
			else :
				/* تارگت ثبت‌شده — کارت خلاصه‌ی آبی */
				?>
				<div class="szp-ev2-done szp-ev2-done-target">
					<div class="szp-ev2-done-row">
						<span class="szp-ev2-step-n step-blue">۱</span>
						<span class="szp-ev2-done-lbl">تارگت این جلسه</span>
						<b class="szp-ev2-done-val"><?php echo esc_html( szp_money( $row->target, $currency ) ); ?></b>
						<button type="button" class="szp-ev2-editbtn szp-ev-edit-target" data-week="<?php echo esc_attr( $sel ); ?>" data-target="<?php echo esc_attr( $row->target ); ?>" title="ویرایش تارگت">✏️</button>
					</div>
				</div>
				<?php
			endif;

			/* ---- مرحله ۲: نتیجه ---- */
			if ( $has_target ) :
				if ( $has_result ) :
					$st   = self::compute_status( $row->target, $row->result, true );
					$meta = self::status_meta( $st );
					$pct  = self::pct( $row->target, $row->result );
					?>
					<div class="szp-ev2-done szp-ev2-done-result">
						<div class="szp-ev2-done-row">
							<span class="szp-ev2-step-n step-green">۲</span>
							<span class="szp-ev2-done-lbl">نتیجه‌ی این جلسه</span>
							<b class="szp-ev2-done-val"><?php echo esc_html( szp_money( $row->result, $currency ) ); ?></b>
							<button type="button" class="szp-ev2-editbtn szp-ev-edit-result" data-week="<?php echo esc_attr( $sel ); ?>" data-result="<?php echo esc_attr( $row->result ); ?>" title="ویرایش نتیجه">✏️</button>
						</div>
						<div class="szp-ev2-result-status">
							<span class="szp-ev2-statusbadge" style="--c:<?php echo esc_attr( $meta['color'] ); ?>"><?php echo esc_html( $meta['emoji'] . ' ' . $meta['label'] ); ?></span>
							<span class="szp-ev2-pct"><?php echo esc_html( szp_fa_digits( $pct ) ); ?>٪ تحقق</span>
						</div>
					</div>
					<?php
				elseif ( $r_open ) :
					echo self::form_result( $sel, $row->target, $currency ); // phpcs:ignore WordPress.Security.EscapeOutput
				else :
					?>
					<div class="szp-ev2-step szp-ev2-step-locked szp-ev2-step-green-locked">
						<div class="szp-ev2-step-t"><span class="szp-ev2-step-n step-green">۲</span> ثبت نتیجه</div>
						<p class="szp-ev2-lock">🔒 ثبت نتیجه از <b><?php echo esc_html( self::result_date_fa( $sel ) ); ?></b> (دوشنبه و سه‌شنبه‌ی هفته‌ی بعد) باز می‌شود.</p>
					</div>
					<?php
				endif;
			endif;
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/** فرمِ ثبتِ تارگت (تم آبی، مرحله ۱). */
	protected static function form_target( $session_no, $currency ) {
		ob_start(); ?>
		<div class="szp-ev2-step szp-ev2-step-target szp-ev-form" data-mode="target" data-session="<?php echo esc_attr( $session_no ); ?>">
			<div class="szp-ev2-step-t"><span class="szp-ev2-step-n step-blue">۱</span> ثبت تارگت این جلسه</div>
			<p class="szp-ev2-step-hint">می‌خواهی این هفته چقدر بفروشی؟ عدد تارگت را وارد کن.</p>
			<div class="szp-ev2-inrow">
				<input type="text" inputmode="numeric" class="szp-ev-amount" placeholder="مثلاً ۵۰۰٬۰۰۰٬۰۰۰">
				<span class="szp-ev2-unit"><?php echo esc_html( $currency ); ?></span>
			</div>
			<div class="szp-ev-preview" aria-live="polite"></div>
			<button type="button" class="szp-ev-submit szp-ev2-btn szp-ev2-btn-blue">ثبت تارگت 🎯</button>
			<div class="szp-ev-msg" aria-live="polite"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** فرمِ ثبتِ نتیجه (تم سبز، مرحله ۲). */
	protected static function form_result( $session_no, $target, $currency ) {
		ob_start(); ?>
		<div class="szp-ev2-step szp-ev2-step-result szp-ev-form" data-mode="result" data-session="<?php echo esc_attr( $session_no ); ?>" data-target="<?php echo esc_attr( $target ); ?>">
			<div class="szp-ev2-step-t"><span class="szp-ev2-step-n step-green">۲</span> ثبت نتیجه‌ی این جلسه</div>
			<p class="szp-ev2-step-hint">این هفته واقعاً چقدر فروختی؟ عدد واقعی را وارد کن. (تارگت: <b><?php echo esc_html( szp_money( $target, $currency ) ); ?></b>)</p>
			<div class="szp-ev2-inrow">
				<input type="text" inputmode="numeric" class="szp-ev-amount" placeholder="نتیجه‌ی واقعی (عدد)">
				<span class="szp-ev2-unit"><?php echo esc_html( $currency ); ?></span>
			</div>
			<textarea class="szp-ev-note" rows="2" placeholder="یادداشت (اختیاری) — چرا رسیدی/نرسیدی؟"></textarea>
			<div class="szp-ev-preview" aria-live="polite"></div>
			<button type="button" class="szp-ev-submit szp-ev2-btn szp-ev2-btn-green">ثبت نتیجه ✅</button>
			<div class="szp-ev-msg" aria-live="polite"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** کارت‌های خلاصه: تعداد جلسات، محقق‌شده، میانگین تحقق. */
	protected static function summary_html( $weeks ) {
		$done = 0; $hit = 0; $sum = 0;
		foreach ( $weeks as $w ) {
			if ( ! $w->has_result ) {
				continue;
			}
			$done++;
			$sum += self::pct( $w->target, $w->result );
			$st   = self::compute_status( $w->target, $w->result, true );
			if ( $st === 'success' || $st === 'beyond' ) {
				$hit++;
			}
		}
		$avg = $done ? (int) round( $sum / $done ) : 0;

		ob_start(); ?>
		<div class="szp-ev2-summary">
			<div class="szp-ev2-stat"><span class="szp-ev2-stat-n"><?php echo esc_html( szp_fa_digits( count( $weeks ) ) ); ?></span><span class="szp-ev2-stat-l">جلسه ثبت‌شده</span></div>
			<div class="szp-ev2-stat"><span class="szp-ev2-stat-n"><?php echo esc_html( szp_fa_digits( $hit ) ); ?></span><span class="szp-ev2-stat-l">تارگت محقق‌شده</span></div>
			<div class="szp-ev2-stat"><span class="szp-ev2-stat-n"><?php echo esc_html( szp_fa_digits( $avg ) ); ?>٪</span><span class="szp-ev2-stat-l">میانگین تحقق</span></div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** فهرست جلساتِ گذشته (کارتی، موبایل‌اول). $editable=true دکمه‌های ویرایش را نشان می‌دهد. */
	protected static function history_html( $weeks, $currency, $editable = false ) {
		if ( ! $weeks ) {
			return '<div class="szp-ev2-history"><h4 class="szp-ev2-sec">سوابق جلسات</h4><p class="szp-empty">هنوز جلسه‌ای ثبت نشده است.</p></div>';
		}
		$list       = array_reverse( $weeks );
		$can_edit   = $editable && self::can_edit_target();
		$can_edit_r = $editable && self::can_edit_result();

		ob_start(); ?>
		<div class="szp-ev2-history">
			<h4 class="szp-ev2-sec">سوابق جلسات</h4>
			<div class="szp-ev2-hlist">
				<?php foreach ( $list as $w ) :
					$st   = self::compute_status( $w->target, $w->result, $w->has_result );
					$meta = self::status_meta( $st );
					$pct  = $w->has_result ? self::pct( $w->target, $w->result ) : 0;
					?>
					<div class="szp-ev2-hrow">
						<div class="szp-ev2-hrow-top">
							<span class="szp-ev2-hsession"><?php echo esc_html( self::session_label( $w->week_no ) ); ?></span>
							<span class="szp-ev2-hdate"><?php echo esc_html( self::session_date_fa( $w->week_no ) ); ?></span>
							<span class="szp-ev2-statusbadge sm" style="--c:<?php echo esc_attr( $meta['color'] ); ?>"><?php echo esc_html( $meta['emoji'] . ' ' . $meta['label'] ); ?></span>
						</div>
						<div class="szp-ev2-hrow-body">
							<div class="szp-ev2-hcell szp-ev-targetcell">
								<span class="szp-ev2-hk">تارگت</span>
								<span class="szp-ev2-hv szp-ev-target-val"><?php echo esc_html( szp_money( $w->target, $currency ) ); ?></span>
								<?php if ( $can_edit ) : ?><button type="button" class="szp-ev2-editbtn szp-ev-edit-target" data-week="<?php echo esc_attr( $w->week_no ); ?>" data-target="<?php echo esc_attr( $w->target ); ?>" title="ویرایش تارگت">✏️</button><?php endif; ?>
							</div>
							<div class="szp-ev2-hcell szp-ev-resultcell">
								<span class="szp-ev2-hk">نتیجه</span>
								<span class="szp-ev2-hv szp-ev-result-val"><?php echo $w->has_result ? esc_html( szp_money( $w->result, $currency ) ) : '—'; ?></span>
								<?php if ( $can_edit_r ) : ?><button type="button" class="szp-ev2-editbtn szp-ev-edit-result" data-week="<?php echo esc_attr( $w->week_no ); ?>" data-result="<?php echo esc_attr( $w->has_result ? $w->result : '' ); ?>" title="ویرایش نتیجه">✏️</button><?php endif; ?>
							</div>
							<div class="szp-ev2-hcell">
								<span class="szp-ev2-hk">تحقق</span>
								<span class="szp-ev2-hv"><?php echo $w->has_result ? esc_html( szp_fa_digits( $pct ) . '٪' ) : '—'; ?></span>
							</div>
						</div>
						<?php if ( ! empty( $w->note ) ) : ?>
							<div class="szp-ev2-hnote">📝 <?php echo esc_html( $w->note ); ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ==================== پنلِ تیم (کوچینگ / مدیر سازمان) ==================== */

	protected static function team_panel( $uid, $currency ) {
		$coaching = class_exists( 'SZP_Eval_Roles' ) && SZP_Eval_Roles::is_coaching( $uid );
		if ( $coaching ) {
			$ids   = SZP_Eval_Roles::all_people_ids();
			$title = 'همه‌ی نفرات';
			$hint  = 'شما به‌عنوان کوچینگ، تارگت و نتیجه‌ی همه‌ی نفرات را می‌بینید.';
		} else {
			$ids   = class_exists( 'SZP_Eval_Roles' ) ? SZP_Eval_Roles::subordinates( $uid ) : array();
			$title = 'زیرمجموعه‌ی من';
			$hint  = 'تارگت و نتیجه‌ی اعضای تیم شما.';
		}
		$ids = array_values( array_filter( array_map( 'intval', $ids ), function ( $id ) use ( $uid ) {
			return $id && $id !== (int) $uid;
		} ) );

		// مرتب‌سازی: میانگین تحقق نزولی.
		$people = array();
		foreach ( $ids as $id ) {
			$info = SZP_Groups::user_info( $id );
			if ( ! $info ) {
				continue;
			}
			$people[] = array( 'info' => $info, 'sum' => self::user_summary( $id ), 'weeks' => self::weeks( $id ), 'id' => $id );
		}
		usort( $people, function ( $a, $b ) {
			return $b['sum']['avg'] <=> $a['sum']['avg'];
		} );

		ob_start(); ?>
		<div class="szp-ev2-team">
			<div class="szp-ev2-team-head">
				<h4 class="szp-ev2-sec"><?php echo esc_html( $title ); ?></h4>
				<span class="szp-ev2-team-hint"><?php echo esc_html( $hint ); ?></span>
			</div>
			<?php echo self::legend_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( ! $people ) : ?>
				<p class="szp-empty">هنوز کسی در تیم شما تعریف/ثبت نشده است.</p>
			<?php else : ?>
				<div class="szp-ev2-people">
					<?php foreach ( $people as $p ) :
						$info  = $p['info'];
						$sum   = $p['sum'];
						$lw    = $sum['latest'];
						$st    = $lw ? self::compute_status( $lw->target, $lw->result, $lw->has_result ) : 'pending';
						$meta  = self::status_meta( $st );
						$mname = $coaching && class_exists( 'SZP_Eval_Roles' ) ? SZP_Eval_Roles::manager_name( $p['id'] ) : '';
						?>
						<details class="szp-ev2-person">
							<summary class="szp-ev2-person-sum">
								<span class="szp-ev2-person-av"><?php echo get_avatar( $info['id'], 38 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
								<span class="szp-ev2-person-id">
									<span class="szp-ev2-person-name"><?php echo esc_html( $info['name'] ); ?></span>
									<span class="szp-ev2-person-meta">
										<?php if ( $mname !== '' ) : ?>مدیر: <?php echo esc_html( $mname ); ?> · <?php endif; ?>
										میانگین <?php echo esc_html( szp_fa_digits( $sum['avg'] ) ); ?>٪
									</span>
								</span>
								<span class="szp-ev2-statusbadge sm" style="--c:<?php echo esc_attr( $meta['color'] ); ?>"><?php echo esc_html( $meta['emoji'] . ' ' . $meta['label'] ); ?></span>
							</summary>
							<div class="szp-ev2-person-body">
								<?php if ( $lw ) : ?>
									<div class="szp-ev2-person-latest">
										<span><?php echo esc_html( self::session_label( $lw->week_no ) ); ?></span>
										<span>تارگت: <b><?php echo esc_html( szp_money( $lw->target, $currency ) ); ?></b></span>
										<span>نتیجه: <b><?php echo $lw->has_result ? esc_html( szp_money( $lw->result, $currency ) ) : '—'; ?></b></span>
									</div>
								<?php endif; ?>
								<?php echo self::history_html( $p['weeks'], $currency, false ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</div>
						</details>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	protected static function legend_html() {
		$out = '<div class="szp-ev2-legend">';
		foreach ( array( 'beyond', 'success', 'improve', 'ontrack' ) as $k ) {
			$m    = self::status_meta( $k );
			$out .= '<span class="szp-ev2-leg"><i style="background:' . esc_attr( $m['color'] ) . '"></i>' . esc_html( $m['label'] ) . '</span>';
		}
		return $out . '</div>';
	}

	/* ==================== خلاصه‌ی کاربر و تابلوها (سازگاری) ==================== */

	public static function user_summary( $user_id ) {
		$weeks  = self::weeks( $user_id );
		$done   = 0; $hit = 0; $sum = 0; $latest = null;
		foreach ( $weeks as $w ) {
			$latest = $w;
			if ( ! $w->has_result ) {
				continue;
			}
			$done++;
			$sum += self::pct( $w->target, $w->result );
			$st   = self::compute_status( $w->target, $w->result, true );
			if ( $st === 'success' || $st === 'beyond' ) {
				$hit++;
			}
		}
		return array(
			'weeks'  => count( $weeks ),
			'done'   => $done,
			'hit'    => $hit,
			'avg'    => $done ? (int) round( $sum / $done ) : 0,
			'latest' => $latest,
		);
	}

	/** فهرست افرادِ قابل‌نمایش در تابلو بر اساس نقشِ بیننده (+فیلتر گروه اختیاری). */
	protected static function board_people_ids( $group = 0 ) {
		$uid = get_current_user_id();
		if ( current_user_can( 'manage_options' ) || ( class_exists( 'SZP_Eval_Roles' ) && SZP_Eval_Roles::is_coaching( $uid ) ) ) {
			$ids = self::participants();
			if ( class_exists( 'SZP_Eval_Roles' ) ) {
				$ids = array_merge( $ids, SZP_Eval_Roles::all_people_ids() );
			}
		} elseif ( class_exists( 'SZP_Eval_Roles' ) && SZP_Eval_Roles::is_org_manager( $uid ) ) {
			$ids = SZP_Eval_Roles::subordinates( $uid );
		} else {
			$ids = self::participants();
		}
		$ids = array_values( array_unique( array_map( 'intval', $ids ) ) );

		if ( $group && class_exists( 'SZP_Groups' ) ) {
			$members = array_flip( SZP_Groups::members( $group ) );
			$ids     = array_values( array_filter( $ids, function ( $id ) use ( $members ) {
				return isset( $members[ $id ] );
			} ) );
		}
		return $ids;
	}

	/** تابلوی افراد (سازگاری با ویجت‌های المنتور). */
	public static function board( $atts = array() ) {
		if ( ! self::can_view_board( isset( $atts['viewers'] ) ? (string) $atts['viewers'] : '' ) ) {
			return '<div class="szp"><div class="szp-empty">شما به تابلوی ارزیابی دسترسی ندارید.</div></div>';
		}
		$title    = ( isset( $atts['title'] ) && $atts['title'] !== '' ) ? $atts['title'] : 'تابلوی ارزیابی';
		$currency = ( isset( $atts['currency'] ) && $atts['currency'] !== '' ) ? (string) $atts['currency'] : self::currency();
		$group    = isset( $atts['group'] ) ? absint( $atts['group'] ) : 0;

		$ids   = self::board_people_ids( $group );
		$cards = array();
		foreach ( $ids as $id ) {
			$info = SZP_Groups::user_info( $id );
			if ( ! $info ) {
				continue;
			}
			$cards[] = array( 'info' => $info, 'sum' => self::user_summary( $id ) );
		}
		usort( $cards, function ( $a, $b ) {
			return $b['sum']['avg'] <=> $a['sum']['avg'];
		} );

		ob_start(); ?>
		<div class="szp">
			<div class="szp-eval2 szp-eval-board">
				<div class="szp-ev2-top"><h3 class="szp-ev2-title"><?php echo esc_html( $title ); ?></h3></div>
				<?php echo self::legend_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php if ( ! $cards ) : ?>
					<p class="szp-empty">هنوز هیچ کاربری تارگتی ثبت نکرده است.</p>
				<?php else : ?>
					<div class="szp-ev2-people">
						<?php foreach ( $cards as $c ) :
							$info = $c['info'];
							$sum  = $c['sum'];
							$lw   = $sum['latest'];
							$st   = $lw ? self::compute_status( $lw->target, $lw->result, $lw->has_result ) : 'pending';
							$meta = self::status_meta( $st );
							?>
							<div class="szp-ev2-person szp-ev2-person-static">
								<div class="szp-ev2-person-sum">
									<span class="szp-ev2-person-av"><?php echo get_avatar( $info['id'], 38 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<span class="szp-ev2-person-id">
										<span class="szp-ev2-person-name"><?php echo esc_html( $info['name'] ); ?></span>
										<span class="szp-ev2-person-meta">میانگین <?php echo esc_html( szp_fa_digits( $sum['avg'] ) ); ?>٪ · <?php echo esc_html( szp_fa_digits( $sum['hit'] ) ); ?> محقق</span>
									</span>
									<span class="szp-ev2-statusbadge sm" style="--c:<?php echo esc_attr( $meta['color'] ); ?>"><?php echo esc_html( $meta['emoji'] . ' ' . $meta['label'] ); ?></span>
								</div>
								<?php if ( $lw ) : ?>
									<div class="szp-ev2-person-latest">
										<span><?php echo esc_html( self::session_label( $lw->week_no ) ); ?></span>
										<span>تارگت: <b><?php echo esc_html( szp_money( $lw->target, $currency ) ); ?></b></span>
										<span>نتیجه: <b><?php echo $lw->has_result ? esc_html( szp_money( $lw->result, $currency ) ) : '—'; ?></b></span>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** دفتر کامل: همه‌ی افراد + ریز همه‌ی جلسات. */
	public static function board_full( $atts = array() ) {
		if ( ! self::can_view_board( isset( $atts['viewers'] ) ? (string) $atts['viewers'] : '' ) ) {
			return '<div class="szp"><div class="szp-empty">شما به دفتر ارزیابی دسترسی ندارید.</div></div>';
		}
		$title    = ( isset( $atts['title'] ) && $atts['title'] !== '' ) ? $atts['title'] : 'دفتر ارزیابی — همه‌ی تارگت‌ها و نتایج';
		$currency = ( isset( $atts['currency'] ) && $atts['currency'] !== '' ) ? (string) $atts['currency'] : self::currency();
		$group    = isset( $atts['group'] ) ? absint( $atts['group'] ) : 0;

		$ids    = self::board_people_ids( $group );
		$people = array();
		foreach ( $ids as $id ) {
			$info = SZP_Groups::user_info( $id );
			if ( ! $info ) {
				continue;
			}
			$people[] = array( 'info' => $info, 'weeks' => self::weeks( $id ), 'sum' => self::user_summary( $id ) );
		}
		usort( $people, function ( $a, $b ) {
			return $b['sum']['avg'] <=> $a['sum']['avg'];
		} );

		ob_start(); ?>
		<div class="szp">
			<div class="szp-eval2 szp-eval-ledger">
				<div class="szp-ev2-top"><h3 class="szp-ev2-title"><?php echo esc_html( $title ); ?></h3></div>
				<?php echo self::legend_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php if ( ! $people ) : ?>
					<p class="szp-empty">هنوز هیچ کاربری تارگتی ثبت نکرده است.</p>
				<?php else : ?>
					<div class="szp-ev2-people">
						<?php foreach ( $people as $p ) :
							$info = $p['info'];
							$sum  = $p['sum'];
							?>
							<details class="szp-ev2-person" open>
								<summary class="szp-ev2-person-sum">
									<span class="szp-ev2-person-av"><?php echo get_avatar( $info['id'], 38 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<span class="szp-ev2-person-id">
										<span class="szp-ev2-person-name"><?php echo esc_html( $info['name'] ); ?></span>
										<span class="szp-ev2-person-meta"><?php echo esc_html( szp_fa_digits( $sum['weeks'] ) ); ?> جلسه · میانگین <?php echo esc_html( szp_fa_digits( $sum['avg'] ) ); ?>٪</span>
									</span>
								</summary>
								<div class="szp-ev2-person-body"><?php echo self::history_html( $p['weeks'], $currency, false ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
							</details>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
