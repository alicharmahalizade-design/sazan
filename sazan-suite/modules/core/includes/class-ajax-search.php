<?php
/**
 * هندلر جستجوی سریع ایجکسی.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Ajax_Search {

	/** @var Ajax_Search|null */
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_sazan_search', array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_sazan_search', array( $this, 'handle' ) );
	}

	public function handle() {
		// بررسی سبک nonce (در صورت ارسال)
		if ( isset( $_REQUEST['nonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'sazan_search' ) ) {
			wp_send_json_error( array( 'message' => 'bad nonce' ), 403 );
		}

		$q = isset( $_REQUEST['q'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['q'] ) ) : '';
		if ( mb_strlen( $q ) < 2 ) {
			wp_send_json_success( array( 'items' => array() ) );
		}

		$source = isset( $_REQUEST['source'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['source'] ) ) : 'any';
		$count  = isset( $_REQUEST['count'] ) ? max( 1, min( 12, (int) $_REQUEST['count'] ) ) : 6;

		if ( 'any' === $source || '' === $source ) {
			$post_type = 'any';
		} else {
			$post_type = array_map( 'sanitize_key', explode( ',', $source ) );
		}

		$query = new \WP_Query( array(
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			's'                   => $q,
			'posts_per_page'      => $count,
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		) );

		$items = array();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$id   = get_the_ID();
				$item = array(
					'title' => get_the_title(),
					'link'  => get_permalink(),
					'thumb' => get_the_post_thumbnail_url( $id, 'thumbnail' ),
					'type'  => get_post_type(),
					'price' => '',
				);
				// قیمت برای محصولات ووکامرس
				if ( 'product' === $item['type'] && function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( $id );
					if ( $product ) {
						$item['price'] = wp_strip_all_tags( $product->get_price_html() );
					}
				}
				$items[] = $item;
			}
		}
		wp_reset_postdata();

		wp_send_json_success( array( 'items' => $items ) );
	}
}
