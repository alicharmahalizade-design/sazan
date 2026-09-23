<?php
/**
 * Stores the pre-registration form submissions (the form UX is unchanged: the
 * visitor sees the success message immediately, the request is saved in the
 * background) and optionally e-mails them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HKL_Leads {

	const POST_TYPE    = 'hkl_lead';
	const EMAIL_OPTION = 'hkl_lead_email';

	const FIELDS = [
		'fullName'   => 'نام و نام خانوادگی',
		'phone'      => 'شماره تلفن',
		'job'        => 'شغل / سمت',
		'staffCount' => 'تعداد پرسنل',
		'page_url'   => 'صفحه',
	];

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_post_type' ] );
		add_action( 'wp_ajax_hkl_submit_lead', [ __CLASS__, 'submit' ] );
		add_action( 'wp_ajax_nopriv_hkl_submit_lead', [ __CLASS__, 'submit' ] );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ __CLASS__, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ __CLASS__, 'column' ], 10, 2 );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, [ __CLASS__, 'meta_box' ] );
	}

	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'          => [
					'name'          => 'درخواست‌های پیش‌ثبت‌نام',
					'singular_name' => 'درخواست پیش‌ثبت‌نام',
					'menu_name'     => 'درخواست‌ها',
					'all_items'     => 'درخواست‌های پیش‌ثبت‌نام',
				],
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => HKL_Page_Builder::MENU_SLUG,
				'supports'        => [ 'title' ],
				'capability_type' => 'post',
				'capabilities'    => [ 'create_posts' => 'do_not_allow' ],
				'map_meta_cap'    => true,
			]
		);
	}

	public static function submit() {
		// Public form on (possibly cached) pages: no nonce, but a simple per-IP rate limit.
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key   = 'hkl_lead_' . md5( $ip );
		$count = (int) get_transient( $key );
		if ( $count >= 5 ) {
			wp_send_json_error( [ 'message' => 'too_many_requests' ], 429 );
		}
		set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );

		$values = [];
		foreach ( array_keys( self::FIELDS ) as $key ) {
			$raw            = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$values[ $key ] = 'page_url' === $key ? esc_url_raw( $raw ) : sanitize_text_field( $raw );
		}
		$values['phone'] = preg_replace( '/[\s-]/', '', $values['phone'] );
		if ( '' === $values['fullName'] || ! preg_match( '/^09[0-9]{9}$/', $values['phone'] ) ) {
			wp_send_json_error( [ 'message' => 'invalid_data' ], 400 );
		}

		$post_id = wp_insert_post(
			[
				'post_type'   => self::POST_TYPE,
				'post_status' => 'private',
				'post_title'  => $values['fullName'] . ' — ' . $values['phone'],
			],
			true
		);
		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( [ 'message' => 'save_failed' ], 500 );
		}
		foreach ( $values as $key => $value ) {
			update_post_meta( $post_id, '_hkl_' . $key, $value );
		}

		$email = get_option( self::EMAIL_OPTION, '' );
		if ( $email && is_email( $email ) ) {
			$lines = [];
			foreach ( self::FIELDS as $key => $label ) {
				$lines[] = $label . ': ' . $values[ $key ];
			}
			wp_mail( $email, 'درخواست پیش‌ثبت‌نام جدید — ' . $values['fullName'], implode( "\n", $lines ) );
		}

		wp_send_json_success();
	}

	public static function columns( $columns ) {
		return [
			'cb'         => $columns['cb'] ?? '',
			'title'      => 'درخواست',
			'hkl_job'    => 'شغل / سمت',
			'hkl_staff'  => 'تعداد پرسنل',
			'date'       => 'تاریخ',
		];
	}

	public static function column( $column, $post_id ) {
		if ( 'hkl_job' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_hkl_job', true ) );
		} elseif ( 'hkl_staff' === $column ) {
			echo esc_html( get_post_meta( $post_id, '_hkl_staffCount', true ) );
		}
	}

	public static function meta_box() {
		add_meta_box(
			'hkl_lead_details',
			'جزئیات درخواست',
			static function ( $post ) {
				echo '<table class="widefat striped"><tbody>';
				foreach ( self::FIELDS as $key => $label ) {
					echo '<tr><th style="width:160px">' . esc_html( $label ) . '</th><td>' . esc_html( get_post_meta( $post->ID, '_hkl_' . $key, true ) ) . '</td></tr>';
				}
				echo '</tbody></table>';
			},
			self::POST_TYPE,
			'normal',
			'high'
		);
	}
}
