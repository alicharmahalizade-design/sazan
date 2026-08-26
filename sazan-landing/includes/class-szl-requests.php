<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Storage + AJAX endpoint for "ثبت درخواست حضور در دوره".
 *
 * Submissions land in the szl_request CPT (private, not publicly queryable)
 * and optionally trigger an e-mail to the site admin.
 */
class SZL_Requests {

	const CPT       = 'szl_request';
	const NONCE     = 'szl_request';
	const RATE_META = 'szl_rate_';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'wp_ajax_szl_submit', array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_szl_submit', array( __CLASS__, 'handle' ) );
	}

	public static function activate() {
		self::register_cpt();
		flush_rewrite_rules();
	}

	public static function register_cpt() {
		register_post_type( self::CPT, array(
			'labels'              => array(
				'name'          => 'درخواست‌های دوره',
				'singular_name' => 'درخواست دوره',
				'menu_name'     => 'درخواست‌های دوره',
				'all_items'     => 'همه درخواست‌ها',
				'search_items'  => 'جستجوی درخواست',
				'not_found'     => 'درخواستی ثبت نشده است.',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-clipboard',
			'menu_position'       => 26,
			'capability_type'     => 'post',
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'        => true,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'show_in_rest'        => false,
		) );
	}

	/** Normalise Persian/Arabic digits to ASCII so phone validation works. */
	public static function to_en_digits( $str ) {
		$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		$ar = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
		$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		return str_replace( array_merge( $fa, $ar ), array_merge( $en, $en ), $str );
	}

	/** Iranian mobile: 09xxxxxxxxx, tolerating +98 / 0098 / spaces / dashes. */
	public static function normalize_phone( $raw ) {
		$p = self::to_en_digits( (string) $raw );
		$p = preg_replace( '/[\s\-\(\)]/', '', $p );
		$p = preg_replace( '/^(\+98|0098|98)/', '0', $p );
		if ( preg_match( '/^9\d{9}$/', $p ) ) {
			$p = '0' . $p;
		}
		return preg_match( '/^09\d{9}$/', $p ) ? $p : '';
	}

	private static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}

	/** Max 5 submissions per IP per hour. */
	private static function rate_limited() {
		$key   = self::RATE_META . md5( self::client_ip() );
		$count = (int) get_transient( $key );
		if ( $count >= 5 ) {
			return true;
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return false;
	}

	public static function handle() {
		check_ajax_referer( self::NONCE, 'nonce' );

		// Honeypot: real users never fill this.
		if ( ! empty( $_POST['szl_hp'] ) ) {
			wp_send_json_success( array( 'message' => 'درخواست شما ثبت شد.' ) );
		}

		if ( self::rate_limited() ) {
			wp_send_json_error( array( 'message' => 'تعداد درخواست‌های شما زیاد بود. لطفاً کمی بعد دوباره تلاش کنید.' ), 429 );
		}

		$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$phone = self::normalize_phone( wp_unslash( $_POST['phone'] ?? '' ) );
		$biz   = sanitize_text_field( wp_unslash( $_POST['business'] ?? '' ) );
		$note  = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
		$src   = esc_url_raw( wp_unslash( $_POST['source'] ?? '' ) );

		$errors = array();
		if ( mb_strlen( $name ) < 3 ) {
			$errors['name'] = 'لطفاً نام و نام خانوادگی خود را وارد کنید.';
		}
		if ( '' === $phone ) {
			$errors['phone'] = 'شماره موبایل معتبر نیست. مثال: ۰۹۱۲۳۴۵۶۷۸۹';
		}
		if ( $errors ) {
			wp_send_json_error( array( 'message' => 'لطفاً خطاهای فرم را برطرف کنید.', 'errors' => $errors ), 422 );
		}

		$post_id = wp_insert_post( array(
			'post_type'   => self::CPT,
			'post_status' => 'publish',
			'post_title'  => $name . ' — ' . $phone,
		), true );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => 'ثبت درخواست با خطا مواجه شد. لطفاً دوباره تلاش کنید.' ), 500 );
		}

		update_post_meta( $post_id, '_szl_name', $name );
		update_post_meta( $post_id, '_szl_phone', $phone );
		update_post_meta( $post_id, '_szl_business', $biz );
		update_post_meta( $post_id, '_szl_note', $note );
		update_post_meta( $post_id, '_szl_source', $src );
		update_post_meta( $post_id, '_szl_ip', self::client_ip() );

		/**
		 * Fires after a course request is stored.
		 * Hook here to push the lead into a CRM or send an SMS.
		 */
		do_action( 'szl_request_saved', $post_id, compact( 'name', 'phone', 'biz', 'note', 'src' ) );

		if ( apply_filters( 'szl_notify_admin', true, $post_id ) ) {
			$to      = apply_filters( 'szl_notify_email', get_option( 'admin_email' ) );
			$subject = 'درخواست جدید حضور در دوره — ' . $name;
			$body    = "نام: {$name}\nموبایل: {$phone}\nکسب‌وکار: {$biz}\nچالش: {$note}\nصفحه: {$src}";
			wp_mail( $to, $subject, $body );
		}

		wp_send_json_success( array( 'message' => 'درخواست شما ثبت شد. به‌زودی با شما تماس می‌گیریم.' ) );
	}
}
