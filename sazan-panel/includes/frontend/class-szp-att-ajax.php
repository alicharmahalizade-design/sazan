<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** «حضور و غیاب» — ثبت حضور با اسکن و دریافت زندهٔ دادهٔ تابلو (کاربر واردشده یا مهمان با توکن). */
class SZP_Att_Ajax {

	public static function init() {
		add_action( 'wp_ajax_szp_att_checkin', array( __CLASS__, 'checkin' ) );
		add_action( 'wp_ajax_nopriv_szp_att_checkin', array( __CLASS__, 'checkin' ) );
		add_action( 'wp_ajax_szp_att_board', array( __CLASS__, 'board' ) );
		add_action( 'wp_ajax_nopriv_szp_att_board', array( __CLASS__, 'board' ) );
	}

	/** ثبت حضور: توکنِ جلسه اعتبارسنجی می‌شود؛ کاربر از حساب فعلی یا شمارهٔ موبایل شناسایی می‌شود. */
	public static function checkin() {
		check_ajax_referer( 'szp_front', 'nonce' );

		$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
		$token      = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

		if ( ! $session_id || get_post_type( $session_id ) !== 'szp_session' ) {
			wp_send_json_error( array( 'msg' => 'جلسه نامعتبر است.' ) );
		}
		if ( ! SZP_Attendance::token_valid( $session_id, $token ) ) {
			wp_send_json_error( array( 'msg' => 'این کیوآرکد معتبر نیست یا منقضی شده است.' ) );
		}

		// شناسایی کاربر: واردشده یا از طریق موبایل.
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			if ( ! SZP_Attendance::allow_guest() ) {
				wp_send_json_error( array( 'msg' => 'برای ثبت حضور ابتدا وارد شوید.' ), 401 );
			}
			$mobile = isset( $_POST['mobile'] ) ? szp_normalize_mobile( wp_unslash( $_POST['mobile'] ) ) : '';
			if ( strlen( $mobile ) < 10 ) {
				wp_send_json_error( array( 'msg' => 'شمارهٔ موبایل معتبر وارد کنید.' ) );
			}
			$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$parts  = preg_split( '/\s+/', trim( $name ), 2 );
			$first  = $parts[0] ?? '';
			$last   = $parts[1] ?? '';
			$res    = SZP_Groups::create_user_from_phone( $mobile, $first, $last );
			$user_id = (int) $res['id'];
			if ( ! $user_id ) {
				wp_send_json_error( array( 'msg' => 'ثبت حضور ناموفق بود. شماره را بررسی کنید.' ) );
			}
		}

		$rec = SZP_Attendance::record( $session_id, $user_id );
		if ( empty( $rec['ok'] ) ) {
			wp_send_json_error( array( 'msg' => 'ثبت حضور ناموفق بود.' ) );
		}

		$info = SZP_Groups::user_info( $user_id );
		$name = $info ? $info['name'] : '';

		if ( ! empty( $rec['already'] ) ) {
			wp_send_json_success( array(
				'already' => true,
				'msg'     => 'حضور شما قبلاً ثبت شده بود ✓',
				'name'    => $name,
			) );
		}

		$bucket = SZP_Attendance::bucket( $rec['status'], $rec['warnings'] );
		$labels = SZP_Attendance::statuses();
		if ( $rec['status'] === 'ontime' ) {
			$msg = 'حضور شما به‌موقع ثبت شد ✅';
		} elseif ( $bucket === 'penalized' ) {
			$msg = 'حضور شما با تأخیر ثبت شد ⛔ — به‌دلیل تعداد اخطارها مشمول جریمه شدید.';
		} else {
			$msg = 'حضور شما با تأخیر ثبت شد ⚠️ — یک اخطار برای شما ثبت شد.';
		}

		wp_send_json_success( array(
			'already'  => false,
			'msg'      => $msg,
			'name'     => $name,
			'status'   => $rec['status'],
			'bucket'   => $bucket,
			'late_min' => (int) ( $rec['late_min'] ?? 0 ),
			'warnings' => (int) $rec['warnings'],
			'label'    => $labels[ $bucket ]['label'],
		) );
	}

	/** دادهٔ زندهٔ تابلو برای پیمایش (polling). دسترسی: کاربر دارای دسترسی یا توکن معتبر. */
	public static function board() {
		check_ajax_referer( 'szp_front', 'nonce' );
		$session_id = isset( $_POST['session_id'] ) ? absint( $_POST['session_id'] ) : 0;
		$token      = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';

		if ( ! $session_id || get_post_type( $session_id ) !== 'szp_session' ) {
			wp_send_json_error( array( 'msg' => 'جلسه نامعتبر است.' ) );
		}
		if ( ! SZP_Attendance::can_view_board( $session_id, $token ) ) {
			wp_send_json_error( array( 'msg' => 'عدم دسترسی.' ), 403 );
		}

		$data = SZP_Attendance::board_data( $session_id );
		$html = array();
		foreach ( array( 'ontime', 'warned', 'penalized' ) as $key ) {
			$html[ $key ] = SZP_Attendance::rows_html( $data['columns'][ $key ] );
		}
		wp_send_json_success( array(
			'total'   => $data['total'],
			'counts'  => array(
				'ontime'    => count( $data['columns']['ontime'] ),
				'warned'    => count( $data['columns']['warned'] ),
				'penalized' => count( $data['columns']['penalized'] ),
			),
			'html'    => $html,
		) );
	}
}
