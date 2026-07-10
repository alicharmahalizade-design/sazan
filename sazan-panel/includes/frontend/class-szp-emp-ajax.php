<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** «حضور و غیاب کارمندان» — ثبت ورود/خروج و دریافت زندهٔ تابلو (واردشده یا مهمان با توکن). */
class SZP_Emp_Ajax {

	public static function init() {
		add_action( 'wp_ajax_szp_emp_checkin', array( __CLASS__, 'checkin' ) );
		add_action( 'wp_ajax_nopriv_szp_emp_checkin', array( __CLASS__, 'checkin' ) );
		add_action( 'wp_ajax_szp_emp_board', array( __CLASS__, 'board' ) );
		add_action( 'wp_ajax_nopriv_szp_emp_board', array( __CLASS__, 'board' ) );
	}

	public static function checkin() {
		check_ajax_referer( 'szp_front', 'nonce' );
		$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		if ( ! SZP_Emp_Attendance::token_valid( $token ) ) {
			wp_send_json_error( array( 'msg' => 'این کیوآرکد معتبر نیست یا منقضی شده است.' ) );
		}

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} else {
			if ( ! SZP_Emp_Attendance::allow_guest() ) {
				wp_send_json_error( array( 'msg' => 'برای ثبت حضور ابتدا وارد شوید.' ), 401 );
			}
			$mobile = isset( $_POST['mobile'] ) ? szp_normalize_mobile( wp_unslash( $_POST['mobile'] ) ) : '';
			if ( strlen( $mobile ) < 10 ) {
				wp_send_json_error( array( 'msg' => 'شمارهٔ موبایل معتبر وارد کنید.' ) );
			}
			$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$parts = preg_split( '/\s+/', trim( $name ), 2 );
			$res   = SZP_Groups::create_user_from_phone( $mobile, $parts[0] ?? '', $parts[1] ?? '' );
			$user_id = (int) $res['id'];
			if ( ! $user_id ) {
				wp_send_json_error( array( 'msg' => 'ثبت حضور ناموفق بود. شماره را بررسی کنید.' ) );
			}
		}

		$rec = SZP_Emp_Attendance::record( $user_id );
		if ( empty( $rec['ok'] ) ) {
			wp_send_json_error( array( 'msg' => $rec['msg'] ?? 'ثبت حضور ناموفق بود.' ) );
		}

		$info = SZP_Groups::user_info( $user_id );
		$name = $info ? $info['name'] : '';

		if ( $rec['action'] === 'checkout' ) {
			wp_send_json_success( array( 'already' => false, 'bucket' => 'ontime', 'name' => $name, 'msg' => 'خروج شما ثبت شد. خسته نباشید ✓' ) );
		}
		if ( $rec['action'] === 'already' ) {
			wp_send_json_success( array( 'already' => true, 'name' => $name, 'msg' => 'حضور شما امروز قبلاً ثبت شده بود ✓' ) );
		}

		$bucket = SZP_Emp_Attendance::bucket( $rec['status'], $rec['warnings'] );
		if ( $rec['status'] === 'ontime' ) {
			$msg = 'ورود شما به‌موقع ثبت شد ✅';
		} elseif ( $bucket === 'penalized' ) {
			$msg = 'ورود شما با تأخیر ثبت شد ⛔ — به‌دلیل تعداد اخطارها مشمول جریمه شدید.';
		} else {
			$msg = 'ورود شما با تأخیر ثبت شد ⚠️ — یک اخطار برای شما ثبت شد.';
		}

		wp_send_json_success( array(
			'already'  => false,
			'msg'      => $msg,
			'name'     => $name,
			'status'   => $rec['status'],
			'bucket'   => $bucket,
			'late_min' => (int) ( $rec['late_min'] ?? 0 ),
			'warnings' => (int) $rec['warnings'],
		) );
	}

	public static function board() {
		check_ajax_referer( 'szp_front', 'nonce' );
		$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		if ( ! SZP_Emp_Attendance::can_view_board( $token ) ) {
			wp_send_json_error( array( 'msg' => 'عدم دسترسی.' ), 403 );
		}
		$data = SZP_Emp_Attendance::board_data();
		$html = array();
		$counts = array();
		foreach ( array( 'ontime', 'warned', 'penalized', 'absent' ) as $key ) {
			$html[ $key ]   = SZP_Emp_Attendance::rows_html( $data['columns'][ $key ] );
			$counts[ $key ] = count( $data['columns'][ $key ] );
		}
		wp_send_json_success( array(
			'total'  => $data['present'],
			'counts' => $counts,
			'html'   => $html,
		) );
	}
}
