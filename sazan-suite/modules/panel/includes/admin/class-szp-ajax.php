<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZP_Ajax {

	public static function init() {
		add_action( 'wp_ajax_szp_user_search', array( __CLASS__, 'user_search' ) );
	}

	public static function user_search() {
		check_ajax_referer( 'szp_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		if ( strlen( $q ) < 2 ) {
			wp_send_json_success( array() );
		}
		$users = get_users( array(
			'search'         => '*' . $q . '*',
			'search_columns' => array( 'user_login', 'user_email', 'display_name', 'user_nicename' ),
			'number'         => 15,
			'fields'         => array( 'ID', 'display_name', 'user_email' ),
		) );
		$out = array();
		foreach ( $users as $u ) {
			$info = SZP_Groups::user_info( $u->ID );
			$out[] = array(
				'id'     => (int) $u->ID,
				'text'   => $u->display_name . ' (' . $u->user_email . ')',
				'first'  => $info ? $info['first'] : '',
				'last'   => $info ? $info['last'] : '',
				'mobile' => $info ? $info['mobile'] : '',
				'email'  => (string) $u->user_email,
				'name'   => $info ? $info['name'] : $u->display_name,
			);
		}
		wp_send_json_success( $out );
	}
}
