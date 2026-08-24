<?php
/**
 * ثبت‌نام کارت‌به‌کارت — دریافت رسید واریزی و مشخصات کاربر از پاپ‌آپ صفحه دوره.
 *
 * کاربر مبلغ دوره را به شماره کارت اعلام‌شده واریز می‌کند و سپس تصویر رسید،
 * نام و نام خانوادگی و شماره تماسش را در پاپ‌آپ ثبت می‌کند. هر درخواست در
 * نوع‌محتوای «ثبت‌نام دوره» ذخیره می‌شود و پس از تأیید مدیر نهایی می‌شود.
 *
 * @package Sazan\ProductPage\V3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SPP_V3_Enroll {

	const NONCE = 'spp_v3_enroll';
	const CPT   = 'spp_enrollment';

	/** حداکثر حجم رسید واریزی (بایت). */
	const MAX_SIZE = 8388608;

	/** حداکثر تعداد ثبت درخواست از یک IP در یک ساعت. */
	const RATE_LIMIT = 5;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_cpt' ) );
		add_action( 'wp_ajax_spp_v3_enroll', array( __CLASS__, 'submit' ) );
		add_action( 'wp_ajax_nopriv_spp_v3_enroll', array( __CLASS__, 'submit' ) );

		add_action( 'add_meta_boxes', array( __CLASS__, 'metabox' ) );
		add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_status' ), 10, 2 );
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'column_value' ), 10, 2 );
	}

	/* =====================================================================
	 * نوع‌محتوای درخواست‌ها
	 * =================================================================== */

	public static function register_cpt() {
		register_post_type( self::CPT, array(
			'labels' => array(
				'name'          => 'ثبت‌نام‌های دوره',
				'singular_name' => 'ثبت‌نام دوره',
				'menu_name'     => 'ثبت‌نام‌های دوره',
				'all_items'     => 'ثبت‌نام‌های دوره',
				'search_items'  => 'جستجوی ثبت‌نام',
				'not_found'     => 'هنوز درخواست ثبت‌نامی ثبت نشده است.',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => post_type_exists( 'product' ) ? 'edit.php?post_type=product' : true,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			'supports'            => array( 'title' ),
			'menu_icon'           => 'dashicons-money-alt',
		) );
	}

	/** وضعیت‌های قابل انتخاب برای یک درخواست. */
	public static function statuses() {
		return array(
			'pending'  => 'در انتظار بررسی',
			'approved' => 'تأییدشده',
			'rejected' => 'ردشده',
		);
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

		if ( self::rate_limited() ) {
			wp_send_json_error( array( 'message' => 'تعداد درخواست‌های شما زیاد است. کمی بعد دوباره تلاش کنید.' ), 429 );
		}

		$product_id = isset( $_POST['product'] ) ? absint( $_POST['product'] ) : 0;
		$product    = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : false;

		if ( ! $product || 'publish' !== get_post_status( $product_id ) ) {
			wp_send_json_error( array( 'message' => 'دوره پیدا نشد.' ), 404 );
		}

		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$phone      = isset( $_POST['phone'] ) ? self::normalize_phone( wp_unslash( $_POST['phone'] ) ) : '';
		$note       = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

		/* اعتبارسنجی ------------------------------------------------------ */
		$errors = array();

		if ( mb_strlen( $first_name ) < 2 ) {
			$errors[] = 'نام را وارد کنید.';
		}
		if ( mb_strlen( $last_name ) < 2 ) {
			$errors[] = 'نام خانوادگی را وارد کنید.';
		}
		if ( ! self::valid_phone( $phone ) ) {
			$errors[] = 'شماره موبایل را به شکل ۰۹۱۲۳۴۵۶۷۸۹ وارد کنید.';
		}
		$upload_error = isset( $_FILES['receipt']['error'] ) ? (int) $_FILES['receipt']['error'] : UPLOAD_ERR_NO_FILE;

		if ( in_array( $upload_error, array( UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE ), true ) ) {
			$errors[] = 'حجم رسید بیشتر از حد مجاز سرور است؛ فایل کوچک‌تری بفرستید.';
		} elseif ( UPLOAD_ERR_OK !== $upload_error || empty( $_FILES['receipt']['size'] ) ) {
			$errors[] = 'تصویر رسید واریزی را بارگذاری کنید.';
		} elseif ( (int) $_FILES['receipt']['size'] > self::max_size() ) {
			$errors[] = 'حجم رسید نباید بیشتر از ' . self::max_size_label() . ' باشد.';
		}

		if ( $errors ) {
			wp_send_json_error( array( 'message' => implode( ' ', $errors ) ), 422 );
		}

		/* بارگذاری رسید --------------------------------------------------- */
		$attachment_id = self::store_receipt();

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => $attachment_id->get_error_message() ), 422 );
		}

		/* ثبت درخواست ----------------------------------------------------- */
		$user    = wp_get_current_user();
		$post_id = wp_insert_post( array(
			'post_type'   => self::CPT,
			'post_status' => 'publish',
			'post_title'  => trim( $first_name . ' ' . $last_name ) . ' — ' . $product->get_name(),
			'post_author' => $user && $user->exists() ? $user->ID : 0,
		), true );

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			wp_delete_attachment( $attachment_id, true );
			wp_send_json_error( array( 'message' => 'ثبت درخواست انجام نشد. دوباره تلاش کنید.' ), 500 );
		}

		wp_update_post( array( 'ID' => $attachment_id, 'post_parent' => $post_id ) );

		$meta = array(
			'_spp_enroll_first_name' => $first_name,
			'_spp_enroll_last_name'  => $last_name,
			'_spp_enroll_phone'      => $phone,
			'_spp_enroll_note'       => $note,
			'_spp_enroll_product'    => $product_id,
			'_spp_enroll_amount'     => wp_strip_all_tags( $product->get_price_html() ),
			'_spp_enroll_receipt'    => $attachment_id,
			'_spp_enroll_status'     => 'pending',
			'_spp_enroll_user'       => $user && $user->exists() ? $user->ID : 0,
			'_spp_enroll_ip'         => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		self::bump_rate();
		self::notify( $post_id, $product, $meta );

		/**
		 * پس از ثبت یک درخواست ثبت‌نام کارت‌به‌کارت.
		 *
		 * @param int        $post_id    شناسه‌ی درخواست.
		 * @param WC_Product $product    دوره‌ی مربوطه.
		 * @param array      $meta       اطلاعات ثبت‌شده.
		 */
		do_action( 'spp_v3_enrollment_created', $post_id, $product, $meta );

		wp_send_json_success( array(
			'message' => self::plain( spp_global( 'enroll_success', '' ), 'ثبت‌نام شما ثبت شد. رسید واریزی بررسی می‌شود و به‌زودی با شما تماس می‌گیریم.' ),
		) );
	}

	/* =====================================================================
	 * ابزارها
	 * =================================================================== */

	private static function plain( $value, $fallback = '' ) {
		$value = trim( wp_strip_all_tags( (string) $value ) );
		return '' !== $value ? $value : $fallback;
	}

	/** حداکثر حجم مجاز رسید. */
	public static function max_size() {
		return (int) apply_filters( 'spp_v3_enroll_max_size', self::MAX_SIZE );
	}

	/** حداکثر حجم مجاز رسید به‌صورت متن فارسی. */
	public static function max_size_label() {
		return number_format_i18n( self::max_size() / MB_IN_BYTES ) . ' مگابایت';
	}

	/** پسوندهای مجاز رسید. */
	public static function allowed_mimes() {
		return (array) apply_filters( 'spp_v3_enroll_mimes', array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
			'pdf'          => 'application/pdf',
		) );
	}

	/**
	 * تبدیل ارقام فارسی/عربی به لاتین، بدون دست‌زدن به بقیه‌ی کاراکترها.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function latin_digits( $value ) {
		$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		$ar = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
		$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );

		return str_replace( $fa, $en, str_replace( $ar, $en, (string) $value ) );
	}

	/**
	 * تبدیل ارقام فارسی/عربی به لاتین و حذف هر کاراکتر غیرعددی.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function normalize_digits( $value ) {
		return (string) preg_replace( '/[^0-9]/', '', self::latin_digits( $value ) );
	}

	/**
	 * یکسان‌سازی شماره شبا: حذف فاصله و خط تیره و افزودن پیشوند IR.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function normalize_iban( $value ) {
		$value = strtoupper( (string) preg_replace( '/[^0-9A-Za-z]/', '', self::latin_digits( $value ) ) );

		if ( '' === $value ) {
			return '';
		}

		if ( 0 !== strpos( $value, 'IR' ) && 24 === strlen( $value ) && ctype_digit( $value ) ) {
			$value = 'IR' . $value;
		}

		return $value;
	}

	/**
	 * گروه‌بندی چهارتایی برای خواناتر شدن شماره کارت و شبا.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function group( $value ) {
		return trim( chunk_split( (string) $value, 4, ' ' ) );
	}

	/**
	 * پاک‌سازی شماره موبایل و یکسان‌سازی پیش‌شماره.
	 *
	 * @param string $value
	 * @return string
	 */
	public static function normalize_phone( $value ) {
		$value = self::normalize_digits( $value );

		if ( 0 === strpos( $value, '0098' ) ) {
			$value = '0' . substr( $value, 4 );
		} elseif ( 0 === strpos( $value, '98' ) && 12 === strlen( $value ) ) {
			$value = '0' . substr( $value, 2 );
		} elseif ( 10 === strlen( $value ) && 0 === strpos( $value, '9' ) ) {
			$value = '0' . $value;
		}

		return $value;
	}

	/**
	 * بررسی معتبر بودن شماره موبایل ایران.
	 *
	 * @param string $phone
	 * @return bool
	 */
	public static function valid_phone( $phone ) {
		return (bool) preg_match( '/^09[0-9]{9}$/', (string) $phone );
	}

	/**
	 * بارگذاری رسید واریزی در کتابخانه رسانه.
	 *
	 * @return int|WP_Error
	 */
	private static function store_receipt() {

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$overrides = array(
			'test_form' => false,
			'mimes'     => self::allowed_mimes(),
		);

		$attachment_id = media_handle_upload( 'receipt', 0, array(), $overrides );

		if ( is_wp_error( $attachment_id ) ) {
			return new WP_Error( 'spp_receipt', 'رسید بارگذاری نشد؛ فقط تصویر (JPG، PNG، WEBP) یا PDF تا ' . self::max_size_label() . ' پذیرفته می‌شود.' );
		}

		return (int) $attachment_id;
	}

	/** کلید محدودیت ارسال بر اساس IP. */
	private static function rate_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return 'spp_enroll_' . md5( $ip );
	}

	private static function rate_limited() {
		return (int) get_transient( self::rate_key() ) >= (int) apply_filters( 'spp_v3_enroll_rate_limit', self::RATE_LIMIT );
	}

	private static function bump_rate() {
		set_transient( self::rate_key(), (int) get_transient( self::rate_key() ) + 1, HOUR_IN_SECONDS );
	}

	/**
	 * اعلان ایمیلی به مدیر.
	 *
	 * @param int        $post_id
	 * @param WC_Product $product
	 * @param array      $meta
	 */
	private static function notify( $post_id, $product, $meta ) {

		$to = self::plain( spp_global( 'enroll_admin_email', '' ), get_option( 'admin_email' ) );
		$to = array_values( array_filter( array_map( 'trim', explode( ',', $to ) ), 'is_email' ) );

		if ( ! $to ) {
			return;
		}

		$lines = array(
			'درخواست ثبت‌نام تازه‌ای برای «' . $product->get_name() . '» ثبت شد.',
			'',
			'نام: ' . $meta['_spp_enroll_first_name'] . ' ' . $meta['_spp_enroll_last_name'],
			'شماره تماس: ' . $meta['_spp_enroll_phone'],
			'مبلغ دوره: ' . $meta['_spp_enroll_amount'],
		);

		if ( '' !== $meta['_spp_enroll_note'] ) {
			$lines[] = 'توضیح کاربر: ' . $meta['_spp_enroll_note'];
		}

		$lines[] = 'رسید واریزی: ' . wp_get_attachment_url( $meta['_spp_enroll_receipt'] );
		$lines[] = 'بررسی درخواست: ' . admin_url( 'post.php?post=' . absint( $post_id ) . '&action=edit' );

		wp_mail(
			$to,
			'ثبت‌نام جدید — ' . $product->get_name(),
			implode( "\n", $lines )
		);
	}

	/* =====================================================================
	 * پیشخوان
	 * =================================================================== */

	public static function metabox() {
		add_meta_box( 'spp-enroll-details', 'اطلاعات ثبت‌نام', array( __CLASS__, 'metabox_render' ), self::CPT, 'normal', 'high' );
	}

	public static function metabox_render( $post ) {

		$product_id = (int) get_post_meta( $post->ID, '_spp_enroll_product', true );
		$receipt_id = (int) get_post_meta( $post->ID, '_spp_enroll_receipt', true );
		$status     = (string) get_post_meta( $post->ID, '_spp_enroll_status', true );
		$statuses   = self::statuses();
		$rows       = array(
			'نام و نام خانوادگی' => trim( get_post_meta( $post->ID, '_spp_enroll_first_name', true ) . ' ' . get_post_meta( $post->ID, '_spp_enroll_last_name', true ) ),
			'شماره تماس'         => get_post_meta( $post->ID, '_spp_enroll_phone', true ),
			'دوره'               => $product_id ? get_the_title( $product_id ) : '—',
			'مبلغ دوره'          => get_post_meta( $post->ID, '_spp_enroll_amount', true ),
			'توضیح کاربر'        => get_post_meta( $post->ID, '_spp_enroll_note', true ),
			'تاریخ ثبت'          => get_the_date( 'Y/m/d — H:i', $post ),
		);

		wp_nonce_field( 'spp_enroll_status', 'spp_enroll_status_nonce' );
		echo '<table class="widefat striped" style="max-width:760px">';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th style="width:180px">' . esc_html( $label ) . '</th><td>' . esc_html( '' !== $value ? $value : '—' ) . '</td></tr>';
		}

		echo '<tr><th>رسید واریزی</th><td>';
		if ( $receipt_id ) {
			$url = wp_get_attachment_url( $receipt_id );
			if ( wp_attachment_is_image( $receipt_id ) ) {
				echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . wp_get_attachment_image( $receipt_id, 'medium', false, array( 'style' => 'max-width:280px;height:auto;border-radius:8px' ) ) . '</a>';
			} else {
				echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">مشاهده فایل رسید</a>';
			}
		} else {
			echo '—';
		}
		echo '</td></tr>';

		echo '<tr><th><label for="spp_enroll_status">وضعیت</label></th><td><select id="spp_enroll_status" name="spp_enroll_status">';
		foreach ( $statuses as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></td></tr></table>';
	}

	/**
	 * ذخیره وضعیت درخواست.
	 *
	 * @param int     $post_id
	 * @param WP_Post $post
	 */
	public static function save_status( $post_id, $post ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['spp_enroll_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['spp_enroll_status_nonce'] ) ), 'spp_enroll_status' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$status = isset( $_POST['spp_enroll_status'] ) ? sanitize_key( wp_unslash( $_POST['spp_enroll_status'] ) ) : 'pending';
		$status = array_key_exists( $status, self::statuses() ) ? $status : 'pending';
		$before = (string) get_post_meta( $post_id, '_spp_enroll_status', true );

		update_post_meta( $post_id, '_spp_enroll_status', $status );

		if ( $before !== $status ) {
			/**
			 * تغییر وضعیت یک درخواست ثبت‌نام.
			 *
			 * @param int    $post_id
			 * @param string $status
			 * @param string $before
			 */
			do_action( 'spp_v3_enrollment_status_changed', $post_id, $status, $before );
		}
	}

	public static function columns( $columns ) {
		return array(
			'cb'           => isset( $columns['cb'] ) ? $columns['cb'] : '',
			'title'        => 'ثبت‌نام‌کننده',
			'spp_phone'    => 'شماره تماس',
			'spp_product'  => 'دوره',
			'spp_receipt'  => 'رسید',
			'spp_status'   => 'وضعیت',
			'date'         => 'تاریخ ثبت',
		);
	}

	public static function column_value( $column, $post_id ) {
		switch ( $column ) {
			case 'spp_phone':
				echo esc_html( get_post_meta( $post_id, '_spp_enroll_phone', true ) );
				break;
			case 'spp_product':
				$product_id = (int) get_post_meta( $post_id, '_spp_enroll_product', true );
				echo $product_id ? '<a href="' . esc_url( (string) get_edit_post_link( $product_id ) ) . '">' . esc_html( get_the_title( $product_id ) ) . '</a>' : '—';
				break;
			case 'spp_receipt':
				$receipt_id = (int) get_post_meta( $post_id, '_spp_enroll_receipt', true );
				echo $receipt_id ? '<a href="' . esc_url( (string) wp_get_attachment_url( $receipt_id ) ) . '" target="_blank" rel="noopener">مشاهده</a>' : '—';
				break;
			case 'spp_status':
				$statuses = self::statuses();
				$status   = (string) get_post_meta( $post_id, '_spp_enroll_status', true );
				echo esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : $statuses['pending'] );
				break;
		}
	}

	/* =====================================================================
	 * داده‌های نمایشی پاپ‌آپ
	 * =================================================================== */

	/**
	 * اطلاعات کارت و متن‌های پاپ‌آپ برای یک دوره.
	 *
	 * @param WC_Product $product
	 * @param array      $data داده‌های نسل دوم همان محصول.
	 * @return array
	 */
	public static function card_details( $product, $data ) {

		$d = isset( $data['enrollment'] ) ? $data['enrollment'] : array();

		$number = self::plain( isset( $d['manual_card_number'] ) ? $d['manual_card_number'] : '', self::plain( spp_global( 'enroll_card_number', '' ) ) );
		$digits = self::normalize_digits( $number );
		$iban   = self::plain( isset( $d['manual_iban'] ) ? $d['manual_iban'] : '', self::plain( spp_global( 'enroll_iban', '' ) ) );

		return array(
			'enabled' => '1' === ( isset( $d['manual_enabled'] ) ? $d['manual_enabled'] : '1' ),
			'number'  => $digits ? $digits : $number,
			'iban'    => self::normalize_iban( $iban ),
			'holder'  => self::plain( isset( $d['manual_card_holder'] ) ? $d['manual_card_holder'] : '', self::plain( spp_global( 'enroll_card_holder', '' ) ) ),
			'bank'    => self::plain( isset( $d['manual_bank'] ) ? $d['manual_bank'] : '', self::plain( spp_global( 'enroll_bank', '' ) ) ),
			'amount'  => self::plain( isset( $d['manual_amount'] ) ? $d['manual_amount'] : '', wp_strip_all_tags( $product->get_price_html() ) ),
			'note'    => self::plain( isset( $d['manual_note'] ) ? $d['manual_note'] : '', self::plain( spp_global( 'enroll_note', '' ), 'مبلغ دوره را به شماره کارت زیر واریز کنید، سپس تصویر رسید واریزی و مشخصات خود را در همین فرم ثبت کنید. پس از بررسی رسید، ثبت‌نام شما نهایی می‌شود.' ) ),
		);
	}

	/**
	 * آیا پاپ‌آپ ثبت‌نام برای این دوره فعال است؟
	 *
	 * دست‌کم یکی از شماره کارت یا شماره شبا شرط فعال‌شدن است؛ بدون آن‌ها پاپ‌آپ
	 * چیزی برای نمایش ندارد و دکمه‌ها به مسیر همیشگی سبد خرید برمی‌گردند.
	 *
	 * @param WC_Product $product
	 * @param array      $data
	 * @return bool
	 */
	public static function is_active( $product, $data ) {
		$card = self::card_details( $product, $data );
		return $card['enabled'] && ( '' !== $card['number'] || '' !== $card['iban'] );
	}
}
