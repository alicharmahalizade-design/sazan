<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** «ارزیابی» — ثبت تارگت و نتیجه‌ی هر جلسه از طریق AJAX (کاربر واردشده). */
class SZP_Eval_Ajax {

	public static function init() {
		add_action( 'wp_ajax_szp_eval_target', array( __CLASS__, 'set_target' ) );
		add_action( 'wp_ajax_szp_eval_result', array( __CLASS__, 'set_result' ) );
		add_action( 'wp_ajax_szp_eval_edit_target', array( __CLASS__, 'edit_target' ) );
		add_action( 'wp_ajax_szp_eval_edit_result', array( __CLASS__, 'edit_result' ) );
	}

	protected static function guard() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => 'لطفاً وارد شوید.' ), 401 );
		}
		check_ajax_referer( 'szp_front', 'nonce' );
	}

	/** شماره‌ی جلسه‌ی معتبر از ورودی (پیش‌فرض: جلسه‌ی جاری). */
	protected static function session_from_request() {
		$n = isset( $_POST['session'] ) ? absint( $_POST['session'] ) : 0;
		return $n > 0 ? $n : SZP_Eval::current_session();
	}

	public static function set_target() {
		self::guard();
		$uid     = get_current_user_id();
		$session = self::session_from_request();
		if ( ! SZP_Eval::target_open( $session ) && ! SZP_Eval::can_bypass( $uid ) ) {
			wp_send_json_error( array( 'msg' => 'ثبت تارگت ' . SZP_Eval::session_label( $session ) . ' در این روز باز نیست.' ) );
		}
		$amount = isset( $_POST['amount'] ) ? szp_parse_amount( wp_unslash( $_POST['amount'] ) ) : 0;
		if ( $amount <= 0 ) {
			wp_send_json_error( array( 'msg' => 'لطفاً یک عدد معتبر برای تارگت وارد کنید.' ) );
		}
		$res = SZP_Eval::set_session_target( $uid, $session, $amount );
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( array( 'msg' => 'ثبت تارگت ناموفق بود.' ) );
		}
		self::notify_managers_and_coaches( $uid, 'target', $session, $amount, '' );
		wp_send_json_success( array(
			'msg'     => 'تارگت ' . SZP_Eval::session_label( $session ) . ' ثبت شد ✓',
			'session' => $session,
		) );
	}

	public static function set_result() {
		self::guard();
		$uid     = get_current_user_id();
		$session = self::session_from_request();
		if ( ! SZP_Eval::result_open( $session ) && ! SZP_Eval::can_bypass( $uid ) ) {
			wp_send_json_error( array( 'msg' => 'ثبت نتیجه‌ی ' . SZP_Eval::session_label( $session ) . ' از ' . SZP_Eval::result_date_fa( $session ) . ' باز می‌شود.' ) );
		}
		$amount = isset( $_POST['amount'] ) ? szp_parse_amount( wp_unslash( $_POST['amount'] ) ) : 0;
		$note   = isset( $_POST['note'] ) ? (string) wp_unslash( $_POST['note'] ) : '';
		$res    = SZP_Eval::set_session_result( $uid, $session, $amount, $note );
		if ( empty( $res['ok'] ) ) {
			$msg = ( isset( $res['reason'] ) && $res['reason'] === 'notarget' )
				? 'برای این جلسه تارگتی ثبت نشده است. ابتدا تارگت را ثبت کنید.'
				: 'ثبت نتیجه ناموفق بود.';
			wp_send_json_error( array( 'msg' => $msg ) );
		}
		$meta = SZP_Eval::status_meta( $res['status'] );
		self::notify_managers_and_coaches( $uid, 'result', $session, $amount, $meta['label'] );
		wp_send_json_success( array(
			'msg'    => 'نتیجه ثبت شد — وضعیت: ' . $meta['label'],
			'status' => $res['status'],
		) );
	}

	/** ویرایش تارگت یک جلسه‌ی موجودِ همین کاربر. */
	public static function edit_target() {
		self::guard();
		if ( ! SZP_Eval::can_edit_target() ) {
			wp_send_json_error( array( 'msg' => 'ویرایش تارگت غیرفعال است.' ) );
		}
		$week   = isset( $_POST['week'] ) ? absint( $_POST['week'] ) : 0;
		$amount = isset( $_POST['amount'] ) ? szp_parse_amount( wp_unslash( $_POST['amount'] ) ) : 0;
		if ( $week < 1 ) {
			wp_send_json_error( array( 'msg' => 'جلسه‌ی نامعتبر است.' ) );
		}
		if ( $amount <= 0 ) {
			wp_send_json_error( array( 'msg' => 'لطفاً یک عدد معتبر برای تارگت وارد کنید.' ) );
		}
		$res = SZP_Eval::edit_target( get_current_user_id(), $week, $amount );
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( array( 'msg' => 'این جلسه پیدا نشد.' ) );
		}
		wp_send_json_success( array( 'msg' => 'تارگت ' . SZP_Eval::session_label( $week ) . ' ویرایش شد.', 'week' => $res['week'] ) );
	}

	/** ویرایش نتیجه‌ی یک جلسه‌ی موجودِ همین کاربر. */
	public static function edit_result() {
		self::guard();
		if ( ! SZP_Eval::can_edit_result() ) {
			wp_send_json_error( array( 'msg' => 'ویرایش نتیجه غیرفعال است.' ) );
		}
		$week = isset( $_POST['week'] ) ? absint( $_POST['week'] ) : 0;
		$raw  = isset( $_POST['amount'] ) ? trim( (string) wp_unslash( $_POST['amount'] ) ) : '';
		if ( $week < 1 ) {
			wp_send_json_error( array( 'msg' => 'جلسه‌ی نامعتبر است.' ) );
		}
		if ( $raw === '' ) {
			wp_send_json_error( array( 'msg' => 'لطفاً یک عدد برای نتیجه وارد کنید.' ) );
		}
		$amount = szp_parse_amount( $raw );
		$res    = SZP_Eval::edit_result( get_current_user_id(), $week, $amount );
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( array( 'msg' => 'این جلسه پیدا نشد.' ) );
		}
		wp_send_json_success( array( 'msg' => 'نتیجه ' . SZP_Eval::session_label( $week ) . ' ویرایش شد.', 'week' => $res['week'] ) );
	}

	/**
	 * پیامک اطلاع‌رسانی به «مدیر سازمانِ» فرد و «کوچ‌ها» هنگام ثبت تارگت/نتیجه توسط تیم فروش.
	 * فقط برای کاربرانِ نقشِ تیم فروش (یا مدیر/فروش) اجرا می‌شود.
	 */
	protected static function notify_managers_and_coaches( $uid, $kind, $session, $amount, $status ) {
		if ( ! class_exists( 'SZP_SMS' ) || ! SZP_SMS::enabled() ) {
			return;
		}
		if ( ! SZP_Eval::opt( 'notify_enabled' ) ) {
			return;
		}
		if ( ! class_exists( 'SZP_Eval_Roles' ) || ! SZP_Eval_Roles::is_sales( $uid ) ) {
			return;
		}

		$info = SZP_Groups::user_info( $uid );
		$name = $info ? $info['name'] : '';
		$vars = array(
			'name'    => $name,
			'session' => SZP_Eval::session_label( $session ),
			'amount'  => szp_money( $amount, SZP_Eval::currency() ),
			'status'  => $status,
		);

		// گیرندگان: مدیر سازمانِ فرد + همه‌ی کوچ‌ها.
		$recipients = array();
		$mid        = SZP_Eval_Roles::manager_id( $uid );
		if ( $mid ) {
			$recipients[] = $mid;
		}
		$recipients = array_merge( $recipients, SZP_Eval_Roles::coach_recipient_ids() );
		$recipients = array_values( array_unique( array_filter( array_map( 'intval', $recipients ) ) ) );

		$seen = array();
		foreach ( $recipients as $rid ) {
			if ( $rid === (int) $uid ) {
				continue;
			}
			$mobile = szp_normalize_mobile( SZP_Groups::user_mobile( $rid ) );
			if ( $mobile === '' || isset( $seen[ $mobile ] ) ) {
				continue;
			}
			$seen[ $mobile ] = 1;
			SZP_SMS::send_registration_notice( $mobile, $kind, $vars );
		}
	}
}
