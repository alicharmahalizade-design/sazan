<?php
/**
 * دریافت «ثبت تجربه» از بازدیدکننده و خواندن نظرات تأییدشده.
 *
 * نظر ارسالی همیشه در حالت «در انتظار تأیید» ذخیره می‌شود و تا وقتی مدیر
 * تأییدش نکند هیچ‌جا نمایش داده نمی‌شود.
 *
 * @package Sazan\ProductPage
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SPP_Reviews {

	const NONCE     = 'spp_review';
	const META_ROLE = 'spp_role';

	public static function init() {
		add_action( 'wp_ajax_spp_submit_review', array( __CLASS__, 'submit' ) );
		add_action( 'wp_ajax_nopriv_spp_submit_review', array( __CLASS__, 'submit' ) );

		// نمایش «سمت شغلی» در ستون نظرات پیشخوان.
		add_filter( 'manage_edit-comments_columns', array( __CLASS__, 'admin_column' ) );
		add_action( 'manage_comments_custom_column', array( __CLASS__, 'admin_column_value' ), 10, 2 );
	}

	/* =====================================================================
	 * دریافت فرم
	 * =================================================================== */

	public static function submit() {

		check_ajax_referer( self::NONCE, 'nonce' );

		// تله‌ی ربات: فیلد مخفی باید خالی بماند.
		if ( ! empty( $_POST['spp_hp'] ) ) {
			wp_send_json_error( array( 'message' => 'ارسال ناموفق بود.' ), 400 );
		}

		$product_id = isset( $_POST['product'] ) ? absint( $_POST['product'] ) : 0;
		$post        = $product_id ? get_post( $product_id ) : null;

		if ( ! $post || 'publish' !== $post->post_status ) {
			wp_send_json_error( array( 'message' => 'محصول پیدا نشد.' ), 404 );
		}

		$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$role   = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
		$text   = isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '';
		$rating = isset( $_POST['rating'] ) ? absint( $_POST['rating'] ) : 0;

		$user = wp_get_current_user();
		if ( $user && $user->exists() ) {
			$name  = $name ? $name : $user->display_name;
			$email = $email ? $email : $user->user_email;
		}

		/* اعتبارسنجی ------------------------------------------------------ */
		$errors = array();

		if ( '' === $name ) {
			$errors[] = 'نام را وارد کنید.';
		}
		if ( get_option( 'require_name_email' ) && ! is_email( $email ) ) {
			$errors[] = 'ایمیل معتبر وارد کنید.';
		}
		if ( '' !== $email && ! is_email( $email ) ) {
			$errors[] = 'ایمیل معتبر وارد کنید.';
		}
		if ( mb_strlen( $text ) < 10 ) {
			$errors[] = 'متن تجربه باید حداقل ۱۰ کاراکتر باشد.';
		}
		if ( $rating < 1 || $rating > 5 ) {
			$errors[] = 'امتیاز را انتخاب کنید.';
		}

		if ( $errors ) {
			wp_send_json_error( array( 'message' => implode( ' ', $errors ) ), 422 );
		}

		/* ثبت ------------------------------------------------------------- */
		$comment_id = wp_insert_comment( array(
			'comment_post_ID'      => $product_id,
			'comment_author'       => $name,
			'comment_author_email' => $email,
			'comment_content'      => $text,
			'comment_type'         => 'review',
			'comment_parent'       => 0,
			'user_id'              => $user && $user->exists() ? $user->ID : 0,
			'comment_author_IP'    => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'comment_agent'        => isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 254 ) : '',
			'comment_date'         => current_time( 'mysql' ),
			// همیشه در انتظار تأیید.
			'comment_approved'     => 0,
		) );

		if ( ! $comment_id ) {
			wp_send_json_error( array( 'message' => 'ثبت نظر انجام نشد. دوباره تلاش کنید.' ), 500 );
		}

		add_comment_meta( $comment_id, 'rating', $rating );

		if ( '' !== $role ) {
			add_comment_meta( $comment_id, self::META_ROLE, $role );
		}

		wp_notify_moderator( $comment_id );

		wp_send_json_success( array(
			'message' => 'ممنون! تجربه‌ی شما ثبت شد و پس از تأیید نمایش داده می‌شود.',
		) );
	}

	/* =====================================================================
	 * خواندن نظرات تأییدشده
	 * =================================================================== */

	/**
	 * نظرات تأییدشده‌ی یک محصول، به شکل ساختارِ آیتم‌های این سکشن.
	 *
	 * @param int $product_id
	 * @param int $limit
	 * @return array
	 */
	public static function approved( $product_id, $limit = 12 ) {

		$comments = get_comments( array(
			'post_id' => (int) $product_id,
			'status'  => 'approve',
			'type'    => 'review',
			'number'  => (int) $limit,
			'orderby' => 'comment_date_gmt',
			'order'   => 'DESC',
		) );

		if ( empty( $comments ) ) {
			// برخی قالب‌ها نظرات را با type خالی ذخیره می‌کنند.
			$comments = get_comments( array(
				'post_id' => (int) $product_id,
				'status'  => 'approve',
				'number'  => (int) $limit,
				'orderby' => 'comment_date_gmt',
				'order'   => 'DESC',
			) );
		}

		$out = array();

		foreach ( $comments as $c ) {
			$out[] = array(
				'avatar_url' => get_avatar_url( $c, array( 'size' => 96 ) ),
				'avatar'     => 0,
				'name'       => $c->comment_author,
				'role'       => (string) get_comment_meta( $c->comment_ID, self::META_ROLE, true ),
				'text'       => $c->comment_content,
				'rating'     => (string) absint( get_comment_meta( $c->comment_ID, 'rating', true ) ),
			);
		}

		return $out;
	}

	/* =====================================================================
	 * پیشخوان
	 * =================================================================== */

	public static function admin_column( $cols ) {
		$cols['spp_role'] = 'سمت شغلی';
		return $cols;
	}

	public static function admin_column_value( $col, $comment_id ) {
		if ( 'spp_role' !== $col ) {
			return;
		}
		echo esc_html( (string) get_comment_meta( $comment_id, self::META_ROLE, true ) );
	}
}
