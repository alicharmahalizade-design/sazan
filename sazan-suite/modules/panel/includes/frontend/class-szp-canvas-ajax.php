<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** بوم طراحی خدمت — ذخیره داده کاربر از طریق AJAX. */
class SZP_Canvas_Ajax {

	public static function init() {
		add_action( 'wp_ajax_szp_canvas_save', array( __CLASS__, 'save' ) );
	}

	public static function save() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'msg' => 'لطفاً وارد شوید.' ), 401 );
		}
		check_ajax_referer( 'szp_front', 'nonce' );

		$uid = get_current_user_id();
		$key = isset( $_POST['canvas'] ) ? sanitize_key( wp_unslash( $_POST['canvas'] ) ) : SZP_Canvas::KEY;
		if ( $key === '' ) {
			$key = SZP_Canvas::KEY;
		}

		$in   = isset( $_POST['items'] ) ? (array) wp_unslash( $_POST['items'] ) : array();
		$data = array();
		foreach ( array_keys( SZP_Canvas::sections() ) as $skey ) {
			$list  = ( isset( $in[ $skey ] ) && is_array( $in[ $skey ] ) ) ? $in[ $skey ] : array();
			$clean = array();
			foreach ( $list as $text ) {
				$text = trim( sanitize_textarea_field( (string) $text ) );
				if ( $text !== '' ) {
					$clean[] = $text;
				}
			}
			$data[ $skey ] = $clean;
		}

		SZP_Canvas::save_data( $key, $uid, $data );
		wp_send_json_success( array( 'msg' => 'ذخیره شد.' ) );
	}
}
