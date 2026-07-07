<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** «ارزیابی من» — ثبت تارگت و نتیجه از طریق AJAX (کاربر واردشده). */
class SZP_Eval_Ajax {

	public static function init() {
		add_action( 'wp_ajax_szp_eval_target', array( __CLASS__, 'set_target' ) );
		add_action( 'wp_ajax_szp_eval_result', array( __CLASS__, 'set_result' ) );
		add_action( 'wp_ajax_szp_eval_edit_target', array( __CLASS__, 'edit_target' ) );
	}

	protected static function guard() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => 'لطفاً وارد شوید.' ), 401 );
		}
		check_ajax_referer( 'szp_front', 'nonce' );
	}

	public static function set_target() {
		self::guard();
		if ( SZP_Eval::day_locked( 'target' ) ) {
			wp_send_json_error( array( 'msg' => 'ثبت تارگت فقط در روز ' . SZP_Eval::day_name( SZP_Eval::DAY_TARGET ) . ' امکان‌پذیر است.' ) );
		}
		$amount = isset( $_POST['amount'] ) ? szp_parse_amount( wp_unslash( $_POST['amount'] ) ) : 0;
		if ( $amount <= 0 ) {
			wp_send_json_error( array( 'msg' => 'لطفاً یک عدد معتبر برای تارگت وارد کنید.' ) );
		}
		$res = SZP_Eval::save_target( get_current_user_id(), $amount );
		wp_send_json_success( array( 'msg' => 'تارگت هفته ' . szp_fa_digits( $res['week'] ) . ' ثبت شد.', 'week' => $res['week'] ) );
	}

	public static function set_result() {
		self::guard();
		if ( SZP_Eval::day_locked( 'result' ) ) {
			wp_send_json_error( array( 'msg' => 'ثبت نتیجه فقط در روز ' . SZP_Eval::day_name( SZP_Eval::DAY_RESULT ) . ' امکان‌پذیر است.' ) );
		}
		$amount = isset( $_POST['amount'] ) ? szp_parse_amount( wp_unslash( $_POST['amount'] ) ) : 0;
		$note   = isset( $_POST['note'] ) ? (string) wp_unslash( $_POST['note'] ) : '';
		$res    = SZP_Eval::save_result( get_current_user_id(), $amount, $note );
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( array( 'msg' => 'هفته‌ی بازی برای ثبت نتیجه وجود ندارد. ابتدا تارگت را ثبت کنید.' ) );
		}
		$meta = SZP_Eval::status_meta( $res['status'] );
		wp_send_json_success( array(
			'msg'    => 'نتیجه ثبت شد — وضعیت: ' . $meta['label'],
			'status' => $res['status'],
		) );
	}

	/** ویرایش تارگت یک هفته‌ی موجودِ همین کاربر. */
	public static function edit_target() {
		self::guard();
		if ( ! SZP_Eval::can_edit_target() ) {
			wp_send_json_error( array( 'msg' => 'ویرایش تارگت غیرفعال است.' ) );
		}
		$week   = isset( $_POST['week'] ) ? absint( $_POST['week'] ) : 0;
		$amount = isset( $_POST['amount'] ) ? szp_parse_amount( wp_unslash( $_POST['amount'] ) ) : 0;
		if ( $week < 1 ) {
			wp_send_json_error( array( 'msg' => 'هفته‌ی نامعتبر است.' ) );
		}
		if ( $amount <= 0 ) {
			wp_send_json_error( array( 'msg' => 'لطفاً یک عدد معتبر برای تارگت وارد کنید.' ) );
		}
		$res = SZP_Eval::edit_target( get_current_user_id(), $week, $amount );
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( array( 'msg' => 'این هفته پیدا نشد.' ) );
		}
		wp_send_json_success( array(
			'msg'  => 'تارگت هفته ' . szp_fa_digits( $week ) . ' ویرایش شد.',
			'week' => $res['week'],
		) );
	}
}
