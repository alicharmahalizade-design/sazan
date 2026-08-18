<?php
/**
 * Native WordPress fields used only for single-blog data without a core equivalent.
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Single_Blog_Meta {

	const META_ENABLED     = '_sazan_sb_download_enabled';
	const META_TITLE       = '_sazan_sb_download_title';
	const META_DESCRIPTION = '_sazan_sb_download_description';
	const META_BUTTON      = '_sazan_sb_download_button';
	const META_FILE_ID     = '_sazan_sb_download_file_id';
	const META_FILE_URL    = '_sazan_sb_download_file_url';
	const NONCE_ACTION     = 'sazan_sb_save_post_fields';
	const NONCE_NAME       = 'sazan_sb_fields_nonce';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'add_meta_boxes_post', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
	}

	public function register_meta() {
		$text_fields = array( self::META_ENABLED, self::META_TITLE, self::META_DESCRIPTION, self::META_BUTTON, self::META_FILE_URL );
		foreach ( $text_fields as $key ) {
			register_post_meta( 'post', $key, array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => self::META_DESCRIPTION === $key ? 'sanitize_textarea_field' : ( self::META_FILE_URL === $key ? 'esc_url_raw' : 'sanitize_text_field' ),
				'auth_callback'     => array( $this, 'can_edit_meta' ),
			) );
		}

		register_post_meta( 'post', self::META_FILE_ID, array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => array( $this, 'can_edit_meta' ),
		) );
	}

	public function can_edit_meta() {
		return current_user_can( 'edit_posts' );
	}

	public function add_meta_box() {
		add_meta_box(
			'sazan-single-blog-fields',
			'تنظیمات تک بلاگ سازان',
			array( $this, 'render_meta_box' ),
			'post',
			'normal',
			'default'
		);
	}

	public function admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'post' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		$css = SAZAN_CORE_PATH . 'assets/css/sazan-single-blog-admin.css';
		$js  = SAZAN_CORE_PATH . 'assets/js/sazan-single-blog-admin.js';
		wp_enqueue_style( 'sazan-single-blog-admin', SAZAN_CORE_URL . 'assets/css/sazan-single-blog-admin.css', array(), file_exists( $css ) ? filemtime( $css ) : SAZAN_CORE_VERSION );
		wp_enqueue_script( 'sazan-single-blog-admin', SAZAN_CORE_URL . 'assets/js/sazan-single-blog-admin.js', array(), file_exists( $js ) ? filemtime( $js ) : SAZAN_CORE_VERSION, true );
	}

	public function render_meta_box( $post ) {
		$data      = self::download_data( $post->ID );
		$file_path = $data['url'] ? wp_parse_url( $data['url'], PHP_URL_PATH ) : '';
		$file_name = is_string( $file_path ) && '' !== $file_path ? wp_basename( $file_path ) : 'فایل رسانه';
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<div class="sazan-sb-meta-box" dir="rtl">
			<div class="sazan-sb-native-map">
				<strong>اتصال به فیلدهای پیش‌فرض وردپرس</strong>
				<p>عنوان، متن، خلاصه، تصویر شاخص، نویسنده، تاریخ، دسته‌ها، برچسب‌ها و دیدگاه‌ها مستقیماً از فیلدهای استاندارد همین نوشته خوانده می‌شوند. عنوان نوشته برای SEO، خلاصه برای Meta Description و تصویر شاخص برای شبکه‌های اجتماعی و Schema استفاده می‌شود. اگر افزونه‌ای مانند Yoast، Rank Math یا AIOSEO فعال باشد، تولید متای داخلی سازان خودکار متوقف می‌شود تا خروجی تکراری ساخته نشود.</p>
			</div>

			<label class="sazan-sb-switch-row">
				<input type="checkbox" name="<?php echo esc_attr( self::META_ENABLED ); ?>" value="1" <?php checked( $data['enabled'] ); ?>>
				<span><b>نمایش کارت دانلود راهنما</b><small>برای این نوشته یک فایل تکمیلی قابل دانلود نمایش داده شود.</small></span>
			</label>

			<div class="sazan-sb-download-fields<?php echo $data['enabled'] ? '' : ' is-disabled'; ?>">
				<div class="sazan-sb-field sazan-sb-field-wide">
					<label for="sazan-sb-download-title">عنوان راهنما</label>
					<input id="sazan-sb-download-title" class="widefat" type="text" name="<?php echo esc_attr( self::META_TITLE ); ?>" value="<?php echo esc_attr( $data['title'] ); ?>" placeholder="راهنمای جامع تدوین استراتژی بازاریابی">
				</div>

				<div class="sazan-sb-field sazan-sb-field-wide">
					<label for="sazan-sb-download-description">توضیح کوتاه</label>
					<textarea id="sazan-sb-download-description" class="widefat" rows="2" name="<?php echo esc_attr( self::META_DESCRIPTION ); ?>" placeholder="فایل PDF رایگان شامل چک‌لیست‌ها و قالب‌های کاربردی"><?php echo esc_textarea( $data['description'] ); ?></textarea>
				</div>

				<div class="sazan-sb-field">
					<label for="sazan-sb-download-button">متن دکمه</label>
					<input id="sazan-sb-download-button" class="widefat" type="text" name="<?php echo esc_attr( self::META_BUTTON ); ?>" value="<?php echo esc_attr( $data['button'] ); ?>" placeholder="دانلود رایگان">
				</div>

				<div class="sazan-sb-field sazan-sb-field-wide">
					<label for="sazan-sb-download-url">فایل راهنما</label>
					<div class="sazan-sb-media-row">
						<input id="sazan-sb-download-url" class="widefat sazan-sb-media-url" type="url" name="<?php echo esc_attr( self::META_FILE_URL ); ?>" value="<?php echo esc_attr( $data['stored_url'] ); ?>" placeholder="انتخاب از کتابخانه رسانه یا درج نشانی مستقیم">
						<input class="sazan-sb-media-id" type="hidden" name="<?php echo esc_attr( self::META_FILE_ID ); ?>" value="<?php echo esc_attr( $data['file_id'] ); ?>">
						<button type="button" class="button button-primary sazan-sb-media-select">انتخاب از رسانه وردپرس</button>
						<button type="button" class="button sazan-sb-media-remove"<?php echo $data['file_id'] || $data['stored_url'] ? '' : ' hidden'; ?>>حذف فایل</button>
					</div>
					<small class="description sazan-sb-file-status"><?php echo $data['url'] ? 'فایل انتخاب‌شده: ' . esc_html( $file_name ) : 'هنوز فایلی انتخاب نشده است.'; ?></small>
				</div>
			</div>

			<p class="description">این فیلدها فقط برای اطلاعاتی هستند که وردپرس فیلد پیش‌فرض مناسبی برای آن‌ها ندارد. در ویجت Elementor می‌توانید منبع را به «تنظیمات دستی» تغییر دهید.</p>
		</div>
		<?php
	}

	public function save( $post_id, $post ) {
		if ( ! $post || 'post' !== $post->post_type || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$enabled = isset( $_POST[ self::META_ENABLED ] ) ? '1' : '0'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $post_id, self::META_ENABLED, $enabled );

		$text_fields = array(
			self::META_TITLE       => 'sanitize_text_field',
			self::META_DESCRIPTION => 'sanitize_textarea_field',
			self::META_BUTTON      => 'sanitize_text_field',
			self::META_FILE_URL    => 'esc_url_raw',
		);
		foreach ( $text_fields as $key => $sanitize ) {
			$value = isset( $_POST[ $key ] ) ? call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $post_id, $key, $value );
		}

		$file_id = isset( $_POST[ self::META_FILE_ID ] ) ? absint( $_POST[ self::META_FILE_ID ] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $post_id, self::META_FILE_ID, $file_id );
	}

	/**
	 * Return normalized download data for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>
	 */
	public static function download_data( $post_id ) {
		$post_id   = absint( $post_id );
		$file_id   = absint( get_post_meta( $post_id, self::META_FILE_ID, true ) );
		$stored    = esc_url_raw( (string) get_post_meta( $post_id, self::META_FILE_URL, true ) );
		$media_url = $file_id ? wp_get_attachment_url( $file_id ) : '';
		$configured = metadata_exists( 'post', $post_id, self::META_ENABLED ) || metadata_exists( 'post', $post_id, self::META_FILE_ID ) || metadata_exists( 'post', $post_id, self::META_FILE_URL );

		return array(
			'configured' => (bool) $configured,
			'enabled'    => '1' === (string) get_post_meta( $post_id, self::META_ENABLED, true ),
			'title'      => sanitize_text_field( (string) get_post_meta( $post_id, self::META_TITLE, true ) ),
			'description'=> sanitize_textarea_field( (string) get_post_meta( $post_id, self::META_DESCRIPTION, true ) ),
			'button'     => sanitize_text_field( (string) get_post_meta( $post_id, self::META_BUTTON, true ) ),
			'file_id'    => $file_id,
			'stored_url' => $stored,
			'url'        => $media_url ? $media_url : $stored,
		);
	}
}
