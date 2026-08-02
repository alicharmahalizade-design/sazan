<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class SZP_Woo {

	public static function init() {
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'grant_order' ) );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'grant_order' ) );
	}

	public static function grant_order( $order_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$user_id = $order->get_user_id();
		if ( ! $user_id ) {
			return;
		}
		foreach ( $order->get_items() as $item ) {
			$pid = $item->get_product_id();
			if ( ! $pid ) {
				continue;
			}
			foreach ( self::courses_for_product( $pid ) as $cid ) {
				SZP_Access::grant( $cid, 'user', $user_id, 'woo' );
			}
		}
	}

	/** Courses whose _szp_products list contains this product id. */
	public static function courses_for_product( $product_id ) {
		$product_id = (int) $product_id;
		$q = new WP_Query( array(
			'post_type'      => 'szp_course',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array( 'key' => '_szp_products', 'compare' => 'EXISTS' ),
			),
		) );
		$out = array();
		foreach ( $q->posts as $cid ) {
			$list = get_post_meta( $cid, '_szp_products', true );
			if ( is_array( $list ) && in_array( $product_id, array_map( 'intval', $list ), true ) ) {
				$out[] = (int) $cid;
			}
		}
		return $out;
	}
}
