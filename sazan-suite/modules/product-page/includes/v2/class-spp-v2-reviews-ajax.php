<?php
/** Paginated review output for Product Page v2. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SPP_V2_Reviews_Ajax {

	const NONCE = 'spp_v2_load_reviews';

	public static function init() {
		add_action( 'wp_ajax_spp_v2_load_reviews', array( __CLASS__, 'load' ) );
		add_action( 'wp_ajax_nopriv_spp_v2_load_reviews', array( __CLASS__, 'load' ) );
	}

	public static function load() {
		check_ajax_referer( self::NONCE, 'nonce' );
		$product_id = isset( $_POST['product'] ) ? absint( $_POST['product'] ) : 0;
		$offset = isset( $_POST['offset'] ) ? max( 0, absint( $_POST['offset'] ) ) : 0;
		if ( ! $product_id || 'product' !== get_post_type( $product_id ) || 'publish' !== get_post_status( $product_id ) ) {
			wp_send_json_error( array( 'message' => 'محصول معتبر نیست.' ), 404 );
		}
		$all = SPP_V2_Renderer::reviews_data( $product_id );
		$slice = array_slice( $all, $offset, 6 );
		$html = '';
		foreach ( $slice as $item ) {
			$html .= SPP_V2_Renderer::review_card( $item );
		}
		wp_send_json_success( array( 'html' => $html, 'next' => $offset + count( $slice ), 'hasMore' => $offset + count( $slice ) < count( $all ) ) );
	}
}
