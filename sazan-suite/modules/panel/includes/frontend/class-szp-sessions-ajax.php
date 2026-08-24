<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** زمان‌بندی جلسات کوچینگ — endpointهای AJAX (کوچ/مانتور). */
class SZP_Sessions_Ajax {

	public static function init() {
		add_action( 'wp_ajax_szp_sess_save',   array( __CLASS__, 'save' ) );
		add_action( 'wp_ajax_szp_sess_cancel', array( __CLASS__, 'cancel' ) );
		add_action( 'wp_ajax_szp_sess_done',   array( __CLASS__, 'done' ) );
		add_action( 'wp_ajax_szp_sess_delete', array( __CLASS__, 'delete' ) );
	}

	protected static function guard() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => 'لطفاً وارد شوید.' ), 401 );
		}
		check_ajax_referer( 'szp_front', 'nonce' );
		$uid = get_current_user_id();
		if ( ! SZP_Sessions::can_manage( $uid ) ) {
			wp_send_json_error( array( 'msg' => 'دسترسی کوچ/مانتور ندارید.' ), 403 );
		}
		return (int) $uid;
	}

	public static function save() {
		$uid = self::guard();
		$in  = array(
			'id'              => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
			'title'           => isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '',
			'customer_name'   => isset( $_POST['customer_name'] ) ? wp_unslash( $_POST['customer_name'] ) : '',
			'customer_mobile' => isset( $_POST['customer_mobile'] ) ? wp_unslash( $_POST['customer_mobile'] ) : '',
			'coach_id'        => isset( $_POST['coach_id'] ) ? absint( $_POST['coach_id'] ) : 0,
			'mentor_name'     => isset( $_POST['mentor_name'] ) ? wp_unslash( $_POST['mentor_name'] ) : '',
			'mentor_mobile'   => isset( $_POST['mentor_mobile'] ) ? wp_unslash( $_POST['mentor_mobile'] ) : '',
			'date'            => isset( $_POST['date'] ) ? wp_unslash( $_POST['date'] ) : '',
			'start'           => isset( $_POST['start'] ) ? wp_unslash( $_POST['start'] ) : '',
			'end'             => isset( $_POST['end'] ) ? wp_unslash( $_POST['end'] ) : '',
			'survey_url'      => isset( $_POST['survey_url'] ) ? wp_unslash( $_POST['survey_url'] ) : '',
			'note'            => isset( $_POST['note'] ) ? wp_unslash( $_POST['note'] ) : '',
		);
		$res = SZP_Sessions::save( $in, $uid );
		if ( empty( $res['ok'] ) ) {
			wp_send_json_error( array( 'msg' => $res['msg'] ?? 'خطا در ثبت جلسه.' ) );
		}
		wp_send_json_success( array( 'msg' => $res['msg'], 'reload' => true ) );
	}

	/** بازیابی و بررسی دسترسی یک جلسه از روی id درخواست. */
	protected static function owned_row( $uid ) {
		$id  = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$row = $id ? SZP_Sessions::get( $id ) : null;
		if ( ! $row || ! SZP_Sessions::can_edit( $uid, $row ) ) {
			wp_send_json_error( array( 'msg' => 'دسترسی به این جلسه ندارید.' ), 403 );
		}
		return $row;
	}

	public static function cancel() {
		$uid = self::guard();
		$row = self::owned_row( $uid );
		SZP_Sessions::cancel( (int) $row->id );
		wp_send_json_success( array( 'msg' => 'جلسه لغو شد.', 'reload' => true ) );
	}

	public static function done() {
		$uid = self::guard();
		$row = self::owned_row( $uid );
		SZP_Sessions::mark_done( (int) $row->id );
		wp_send_json_success( array( 'msg' => 'جلسه «برگزار شد» علامت خورد.', 'reload' => true ) );
	}

	public static function delete() {
		$uid = self::guard();
		$row = self::owned_row( $uid );
		SZP_Sessions::delete( (int) $row->id );
		wp_send_json_success( array( 'msg' => 'جلسه حذف شد.', 'reload' => true ) );
	}
}
