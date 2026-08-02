<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * زمان‌بندی جلسات کوچینگ.
 *
 * کوچ یا مانتور یک جلسه را برای «مشتری» ثبت می‌کند: تاریخ، ساعت شروع و پایان.
 * هنگام ثبت، پیامک «ثبت جلسه» برای هر سه نفر (مشتری، کوچ، مانتور) ارسال می‌شود.
 * یک ساعت پس از پایان جلسه (قابل تنظیم)، پیامکِ حاوی «لینک نظرسنجی» برای مشتری می‌رود.
 *
 * ارسال نظرسنجی هم با رویداد تک‌بارِ کرون (دقیق) و هم با جاروبِ ساعتی (پشتیبان) انجام می‌شود
 * تا اگر کرونِ وردپرس دیر اجرا شد، باز هم پیامک از دست نرود. عملیات ارسال idempotent است.
 */
class SZP_Sessions {

	const HOOK_SURVEY = 'szp_session_survey_send'; // رویداد تک‌بار برای یک جلسه
	const HOOK_SWEEP  = 'szp_session_survey_sweep'; // جاروب ساعتی پشتیبان

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'szp_coach_sessions';
	}

	/* ==================== دسترسی ==================== */

	/** کوچ‌ها و مدیران می‌توانند جلسه ثبت کنند (مانتورها با دسترسی کوچ). */
	public static function can_manage( $uid ) {
		return SZP_Coach::is_coach( (int) $uid );
	}

	/** فهرست کاربرانی که به‌عنوان کوچ قابل انتخاب‌اند: [id => user_info]. */
	public static function coach_users() {
		$ids = array();
		foreach ( get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) as $a ) {
			$ids[ (int) $a ] = 1;
		}
		$q = new WP_Query( array(
			'post_type'      => 'szp_course',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( array( 'key' => '_szp_coach_enabled', 'value' => '1' ) ),
		) );
		foreach ( $q->posts as $cid ) {
			foreach ( SZP_Coach::coach_ids( $cid ) as $u ) {
				$ids[ (int) $u ] = 1;
			}
		}
		$out = array();
		foreach ( array_keys( $ids ) as $u ) {
			$info = SZP_Groups::user_info( $u );
			if ( $info ) {
				$out[ (int) $u ] = $info;
			}
		}
		return $out;
	}

	/* ==================== تنظیمات ==================== */

	/** تعداد دقیقه پس از پایان جلسه برای ارسال نظرسنجی (پیش‌فرض ۶۰). */
	public static function survey_delay() {
		$m = (int) SZP_Eval::opt( 'survey_delay' );
		return $m > 0 ? $m : 60;
	}

	/** آدرس پیش‌فرض صفحه‌ی نظرسنجی از تنظیمات. */
	public static function default_survey_url() {
		return (string) SZP_Eval::opt( 'survey_url' );
	}

	/** لینک نظرسنجیِ نهایی برای یک جلسه (per-session یا پیش‌فرض تنظیمات) + شناسه‌ی جلسه. */
	public static function survey_link( $row ) {
		$url = trim( (string) $row->survey_url );
		if ( $url === '' ) {
			$url = self::default_survey_url();
		}
		if ( $url === '' ) {
			return '';
		}
		return add_query_arg( 'szp_session', (int) $row->id, $url );
	}

	/* ==================== خواندن ==================== */

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', (int) $id ) );
	}

	/**
	 * جلسات قابل مشاهده برای یک کوچ.
	 * مدیر همه را می‌بیند؛ کوچ فقط جلساتی که خودش ساخته یا کوچ/مانتورشان است.
	 */
	public static function list_for( $uid ) {
		global $wpdb;
		$uid = (int) $uid;
		if ( user_can( $uid, 'manage_options' ) ) {
			return $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY start_ts DESC' );
		}
		return $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::table() . ' WHERE coach_id=%d OR mentor_id=%d OR created_by=%d ORDER BY start_ts DESC',
			$uid, $uid, $uid ) );
	}

	/** آیا این کاربر اجازه ویرایش/حذف این جلسه را دارد؟ */
	public static function can_edit( $uid, $row ) {
		if ( ! $row ) {
			return false;
		}
		if ( user_can( $uid, 'manage_options' ) ) {
			return true;
		}
		$uid = (int) $uid;
		return $uid === (int) $row->coach_id || $uid === (int) $row->mentor_id || $uid === (int) $row->created_by;
	}

	/* ==================== ثبت/ویرایش ==================== */

	/**
	 * ثبت یا ویرایش یک جلسه. $in آرایه‌ی ورودیِ خام است.
	 * خروجی: array( ok, id?, msg, notice? ).
	 */
	public static function save( $in, $editor_id ) {
		global $wpdb;
		$editor_id = (int) $editor_id;

		$id             = isset( $in['id'] ) ? absint( $in['id'] ) : 0;
		$title          = sanitize_text_field( $in['title'] ?? '' );
		$customer_name  = sanitize_text_field( $in['customer_name'] ?? '' );
		$customer_mobile = szp_normalize_mobile( $in['customer_mobile'] ?? '' );
		$coach_id       = absint( $in['coach_id'] ?? 0 );
		$mentor_name    = sanitize_text_field( $in['mentor_name'] ?? '' );
		$mentor_mobile  = szp_normalize_mobile( $in['mentor_mobile'] ?? '' );
		$date           = sanitize_text_field( $in['date'] ?? '' ); // Y-m-d (میلادی)
		$start          = sanitize_text_field( $in['start'] ?? '' ); // H:i
		$end            = sanitize_text_field( $in['end'] ?? '' );   // H:i
		$survey_url     = esc_url_raw( trim( (string) ( $in['survey_url'] ?? '' ) ) );
		$note           = sanitize_textarea_field( $in['note'] ?? '' );

		// اعتبارسنجی.
		if ( strlen( $customer_mobile ) < 7 ) {
			return array( 'ok' => false, 'msg' => 'موبایل مشتری معتبر نیست.' );
		}
		if ( $date === '' || $start === '' || $end === '' ) {
			return array( 'ok' => false, 'msg' => 'تاریخ و ساعت شروع و پایان را کامل وارد کنید.' );
		}
		$start_ts = szp_ts_from_datetime( $date . ' ' . $start );
		$end_ts   = szp_ts_from_datetime( $date . ' ' . $end );
		if ( ! $start_ts || ! $end_ts ) {
			return array( 'ok' => false, 'msg' => 'تاریخ/ساعت نامعتبر است.' );
		}
		if ( $end_ts <= $start_ts ) {
			return array( 'ok' => false, 'msg' => 'ساعت پایان باید بعد از ساعت شروع باشد.' );
		}
		if ( $title === '' ) {
			$title = 'جلسه کوچینگ';
		}

		// مشتری: یافتن/ساخت کاربر از روی موبایل.
		$cust = SZP_Groups::create_user_from_phone( $customer_mobile, $customer_name, '' );
		$customer_id = (int) $cust['id'];
		if ( $customer_name === '' && $customer_id ) {
			$ci = SZP_Groups::user_info( $customer_id );
			$customer_name = $ci ? $ci['name'] : '';
		}

		// کوچ: از فهرست کوچ‌ها؛ در نبود انتخاب، خودِ ثبت‌کننده.
		if ( ! $coach_id ) {
			$coach_id = self::can_manage( $editor_id ) ? $editor_id : 0;
		}
		$coach_info   = $coach_id ? SZP_Groups::user_info( $coach_id ) : null;
		$coach_name   = $coach_info ? $coach_info['name'] : '';
		$coach_mobile = $coach_info ? szp_normalize_mobile( $coach_info['mobile'] ) : '';

		// مانتور: اختیاری. اگر موبایل داده شده، کاربر متناظر را هم پیدا می‌کنیم (بدون ساخت).
		$mentor_id = $mentor_mobile !== '' ? SZP_Groups::find_user_by_mobile( $mentor_mobile ) : 0;
		if ( $mentor_name === '' && $mentor_id ) {
			$mi = SZP_Groups::user_info( $mentor_id );
			$mentor_name = $mi ? $mi['name'] : '';
		}

		$now  = current_time( 'mysql' );
		$data = array(
			'title'           => $title,
			'customer_id'     => $customer_id,
			'customer_name'   => $customer_name,
			'customer_mobile' => $customer_mobile,
			'coach_id'        => $coach_id,
			'coach_name'      => $coach_name,
			'coach_mobile'    => $coach_mobile,
			'mentor_id'       => (int) $mentor_id,
			'mentor_name'     => $mentor_name,
			'mentor_mobile'   => $mentor_mobile,
			'start_ts'        => $start_ts,
			'end_ts'          => $end_ts,
			'survey_url'      => $survey_url,
			'note'            => $note,
			'updated_at'      => $now,
		);
		$fmt = array( '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s' );

		if ( $id ) {
			$existing = self::get( $id );
			if ( ! $existing || ! self::can_edit( $editor_id, $existing ) ) {
				return array( 'ok' => false, 'msg' => 'دسترسی به این جلسه ندارید.' );
			}
			$time_changed = ( (int) $existing->start_ts !== $start_ts || (int) $existing->end_ts !== $end_ts );
			if ( $time_changed ) {
				$data['survey_sent'] = 0; // بازنشانی نظرسنجی در صورت تغییر زمان
				$fmt[]               = '%d';
			}
			$wpdb->update( self::table(), $data, array( 'id' => (int) $id ), $fmt, array( '%d' ) );
			$row = self::get( $id );
			if ( $time_changed && $row->status === 'scheduled' ) {
				self::schedule_survey( $row );
				$sent = self::send_notice( $row );
				return array( 'ok' => true, 'id' => $id, 'msg' => 'جلسه به‌روزرسانی شد. زمان جدید و پیامک اطلاع‌رسانی ارسال شد (' . szp_fa_digits( $sent ) . ' پیامک).' );
			}
			return array( 'ok' => true, 'id' => $id, 'msg' => 'جلسه به‌روزرسانی شد.' );
		}

		// ایجاد.
		$data['status']      = 'scheduled';
		$data['notify_sent'] = 0;
		$data['survey_sent'] = 0;
		$data['created_by']  = $editor_id;
		$data['created_at']  = $now;
		$fmt = array_merge( $fmt, array( '%s', '%d', '%d', '%d', '%s' ) );
		$wpdb->insert( self::table(), $data, $fmt );
		$row = self::get( (int) $wpdb->insert_id );

		self::schedule_survey( $row );
		$sent = self::send_notice( $row );

		$msg = 'جلسه ثبت شد.';
		if ( SZP_SMS::enabled() ) {
			$msg .= ' ' . szp_fa_digits( $sent ) . ' پیامک ثبت جلسه ارسال شد.';
		} else {
			$msg .= ' (سرویس پیامک غیرفعال است؛ پیامکی ارسال نشد.)';
		}
		return array( 'ok' => true, 'id' => (int) $row->id, 'msg' => $msg );
	}

	public static function cancel( $id ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'status' => 'canceled', 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $id ), array( '%s', '%s' ), array( '%d' ) );
		self::clear_survey( (int) $id );
	}

	public static function mark_done( $id ) {
		global $wpdb;
		$wpdb->update( self::table(), array( 'status' => 'done', 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $id ), array( '%s', '%s' ), array( '%d' ) );
	}

	public static function delete( $id ) {
		global $wpdb;
		self::clear_survey( (int) $id );
		$wpdb->delete( self::table(), array( 'id' => (int) $id ), array( '%d' ) );
	}

	/* ==================== پیامک ثبت جلسه ==================== */

	/** ارسال پیامک «ثبت جلسه» به مشتری/کوچ/مانتور. خروجی: تعداد پیامک ارسال‌شده. */
	public static function send_notice( $row ) {
		if ( ! SZP_SMS::enabled() ) {
			return 0;
		}
		$base = array(
			'date'   => self::fa_date( $row->start_ts ),
			'time'   => self::fa_time_range( $row ),
			'coach'  => $row->coach_name !== '' ? $row->coach_name : '—',
			'mentor' => $row->mentor_name !== '' ? $row->mentor_name : '—',
			'title'  => $row->title,
		);
		$sent = 0;
		$recipients = array(
			array( $row->customer_mobile, $row->customer_name ),
			array( $row->coach_mobile, $row->coach_name ),
			array( $row->mentor_mobile, $row->mentor_name ),
		);
		$seen = array();
		foreach ( $recipients as $r ) {
			$mobile = szp_normalize_mobile( $r[0] );
			if ( $mobile === '' || isset( $seen[ $mobile ] ) ) {
				continue;
			}
			$seen[ $mobile ] = 1;
			$vars = array_merge( $base, array( 'name' => $r[1] !== '' ? $r[1] : $base['coach'] ) );
			$res  = SZP_SMS::send_session_notice( $mobile, $vars );
			if ( ! empty( $res['ok'] ) ) {
				$sent++;
			}
		}
		if ( $sent > 0 ) {
			global $wpdb;
			$wpdb->update( self::table(), array( 'notify_sent' => 1 ), array( 'id' => (int) $row->id ), array( '%d' ), array( '%d' ) );
		}
		return $sent;
	}

	/* ==================== نظرسنجی پس از جلسه ==================== */

	/** زمان‌بندی رویداد تک‌بارِ ارسال نظرسنجی (۱ ساعت پس از پایان). */
	public static function schedule_survey( $row ) {
		self::clear_survey( (int) $row->id );
		if ( $row->status !== 'scheduled' || ! $row->end_ts ) {
			return;
		}
		$when = (int) $row->end_ts + self::survey_delay() * MINUTE_IN_SECONDS;
		wp_schedule_single_event( $when, self::HOOK_SURVEY, array( (int) $row->id ) );
	}

	public static function clear_survey( $id ) {
		wp_clear_scheduled_hook( self::HOOK_SURVEY, array( (int) $id ) );
	}

	/** ارسال پیامک نظرسنجی برای یک جلسه (idempotent). */
	public static function send_survey_for( $id ) {
		global $wpdb;
		$row = self::get( $id );
		if ( ! $row || $row->status !== 'scheduled' || (int) $row->survey_sent === 1 ) {
			return;
		}
		if ( ! SZP_SMS::enabled() ) {
			return;
		}
		$mobile = szp_normalize_mobile( $row->customer_mobile );
		if ( $mobile === '' ) {
			return;
		}
		$link = self::survey_link( $row );
		if ( $link === '' ) {
			return; // بدون لینک نظرسنجی چیزی نمی‌فرستیم
		}
		$res = SZP_SMS::send_survey( $mobile, array(
			'name'  => $row->customer_name !== '' ? $row->customer_name : 'کاربر',
			'link'  => $link,
			'coach' => $row->coach_name,
		) );
		if ( ! empty( $res['ok'] ) ) {
			$wpdb->update( self::table(), array( 'survey_sent' => 1, 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => (int) $row->id ), array( '%d', '%s' ), array( '%d' ) );
		}
	}

	/** جاروبِ ساعتیِ پشتیبان: هر جلسه‌ی سررسیدشده که نظرسنجی‌اش نرفته را می‌فرستد. */
	public static function run_survey_sweep() {
		global $wpdb;
		if ( ! SZP_SMS::enabled() ) {
			return;
		}
		$now = time() - self::survey_delay() * MINUTE_IN_SECONDS;
		$ids = $wpdb->get_col( $wpdb->prepare(
			'SELECT id FROM ' . self::table() . " WHERE survey_sent=0 AND status='scheduled' AND end_ts>0 AND end_ts<=%d LIMIT 50",
			$now ) );
		foreach ( $ids as $id ) {
			self::send_survey_for( (int) $id );
		}
	}

	/* ==================== قالب‌بندی ==================== */

	protected static function months() {
		return array( '', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
	}

	/** تاریخ شمسی (بدون ساعت). */
	public static function fa_date( $ts ) {
		if ( ! $ts ) {
			return '';
		}
		$dt = new DateTime( '@' . (int) $ts );
		$dt->setTimezone( wp_timezone() );
		list( $jy, $jm, $jd ) = szp_g2j( (int) $dt->format( 'Y' ), (int) $dt->format( 'n' ), (int) $dt->format( 'j' ) );
		$m = self::months();
		return szp_fa_digits( $jd . ' ' . $m[ $jm ] . ' ' . $jy );
	}

	/** ساعت (H:i) با ارقام فارسی. */
	public static function fa_time( $ts ) {
		if ( ! $ts ) {
			return '';
		}
		return szp_fa_digits( wp_date( 'H:i', (int) $ts ) );
	}

	/** بازه‌ی ساعت: «۱۰:۰۰ تا ۱۱:۳۰». */
	public static function fa_time_range( $row ) {
		return self::fa_time( $row->start_ts ) . ' تا ' . self::fa_time( $row->end_ts );
	}

	public static function status_label( $status ) {
		$map = array(
			'scheduled' => 'ثبت‌شده',
			'done'      => 'برگزار شد',
			'canceled'  => 'لغو شد',
		);
		return isset( $map[ $status ] ) ? $map[ $status ] : $status;
	}
}
