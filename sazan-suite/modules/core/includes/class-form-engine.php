<?php
/**
 * موتور فرم‌ساز سازان:
 *   - سازنده‌ی فرم در پیشخوان (متاباکس فیلدها + تنظیمات پیامک هر فرم)
 *   - تنظیمات سراسری پیامک فراز اس ام اس
 *   - شورت‌کد [sazan_form id=".."] (درون‌خطی یا دکمه‌ی پاپ‌آپ)
 *   - دریافت و ذخیره‌ی ثبت‌ها (AJAX) + ارسال پیامک پترن (کاربر/مدیریت)
 *   - تایید شماره با کد پیامکی (OTP) — اختیاری
 *   - تزریق مودال پاپ‌آپ در wp_footer برای دکمه‌های فراخوان (مثلاً تقویم آموزشی)
 *   - خروجی CSV هر فرم
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Form_Engine {

	const ENDPOINT_PATTERN = 'https://api2.ippanel.com/api/v1/sms/pattern/normal/send';
	const OPTION           = 'sazan_form_settings';

	private static $instance = null;
	private $enqueued    = false;
	private $modal_queue = array(); // شناسه‌ی فرم‌هایی که باید مودالشان در فوتر چاپ شود

	/** انواع فیلد پشتیبانی‌شده. */
	public static function field_types() {
		return array(
			'text'     => esc_html__( 'متن کوتاه', 'sazan-core' ),
			'tel'      => esc_html__( 'شماره موبایل', 'sazan-core' ),
			'email'    => esc_html__( 'ایمیل', 'sazan-core' ),
			'number'   => esc_html__( 'عدد', 'sazan-core' ),
			'textarea' => esc_html__( 'متن بلند', 'sazan-core' ),
			'select'   => esc_html__( 'لیست کشویی', 'sazan-core' ),
			'date'     => esc_html__( 'تاریخ (شمسی)', 'sazan-core' ),
		);
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'sazan_form', array( $this, 'shortcode' ) );

		add_action( 'wp_ajax_sazan_form_submit', array( $this, 'ajax_submit' ) );
		add_action( 'wp_ajax_nopriv_sazan_form_submit', array( $this, 'ajax_submit' ) );
		add_action( 'wp_ajax_sazan_form_send_code', array( $this, 'ajax_send_code' ) );
		add_action( 'wp_ajax_nopriv_sazan_form_send_code', array( $this, 'ajax_send_code' ) );

		add_action( 'wp_footer', array( $this, 'print_modals' ), 99 );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );
			add_action( 'save_post_' . Form_CPT::POST_TYPE, array( $this, 'save_form' ), 10, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'builder_assets' ) );
			add_action( 'admin_menu', array( $this, 'menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_post_sazan_form_export', array( $this, 'export_csv' ) );
		}
	}

	/* ===================== تنظیمات سراسری ===================== */

	public static function settings() {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), array(
			'sms_apikey'    => '',
			'sms_sender'    => '',
			'admin_mobiles' => '',
		) );
	}

	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Form_CPT::POST_TYPE,
			esc_html__( 'تنظیمات پیامک فرم‌ساز', 'sazan-core' ),
			esc_html__( 'تنظیمات پیامک', 'sazan-core' ),
			'manage_options',
			'sazan-form-settings',
			array( $this, 'render_settings' )
		);
	}

	public function register_settings() {
		register_setting( 'sazan_form_group', self::OPTION, array( $this, 'sanitize_settings' ) );
	}

	public function sanitize_settings( $in ) {
		return array(
			'sms_apikey'    => sanitize_text_field( $in['sms_apikey'] ?? '' ),
			'sms_sender'    => sanitize_text_field( $in['sms_sender'] ?? '' ),
			'admin_mobiles' => sanitize_textarea_field( $in['admin_mobiles'] ?? '' ),
		);
	}

	public function render_settings() {
		$s = self::settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'تنظیمات پیامک فرم‌ساز سازان', 'sazan-core' ); ?></h1>
			<p class="description"><?php esc_html_e( 'کلید و فرستنده‌ی پیش‌فرض برای همه‌ی فرم‌ها. کدهای پترن را داخل هر فرم تعیین کنید.', 'sazan-core' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'sazan_form_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'کلید API (apikey)', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[sms_apikey]" value="<?php echo esc_attr( $s['sms_apikey'] ); ?>" class="regular-text" style="direction:ltr"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'شماره فرستنده', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[sms_sender]" value="<?php echo esc_attr( $s['sms_sender'] ); ?>" class="regular-text" style="direction:ltr" placeholder="+983000505"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'شماره‌های مدیریت', 'sazan-core' ); ?></label></th>
						<td><textarea name="<?php echo esc_attr( self::OPTION ); ?>[admin_mobiles]" rows="2" class="large-text" style="direction:ltr" placeholder="09120000000، 09130000000"><?php echo esc_textarea( $s['admin_mobiles'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'پیامک «ثبت جدید» به این شماره‌ها ارسال می‌شود (در صورت تنظیم پترن مدیریت در فرم).', 'sazan-core' ); ?></p></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/* ===================== مدل داده‌ی فرم ===================== */

	/** خواندن فیلدهای یک فرم. */
	public static function get_fields( $form_id ) {
		$raw = json_decode( (string) get_post_meta( $form_id, '_szf_fields', true ), true );
		return is_array( $raw ) ? $raw : array();
	}

	/** خواندن تنظیمات یک فرم. */
	public static function get_cfg( $form_id ) {
		$raw = json_decode( (string) get_post_meta( $form_id, '_szf_cfg', true ), true );
		return wp_parse_args( is_array( $raw ) ? $raw : array(), array(
			'submit'      => esc_html__( 'ارسال', 'sazan-core' ),
			'success'     => esc_html__( 'اطلاعات شما با موفقیت ثبت شد.', 'sazan-core' ),
			'redirect'    => '',
			'pat_admin'   => '',
			'pat_user'    => '',
			'otp_enabled' => 0,
			'pat_otp'     => '',
		) );
	}

	/** کلید فیلد شماره موبایل (اولین فیلد از نوع tel). */
	private static function phone_key( $fields ) {
		foreach ( $fields as $f ) {
			if ( 'tel' === ( $f['type'] ?? '' ) ) {
				return $f['name'];
			}
		}
		return '';
	}

	/* ===================== متاباکس سازنده ===================== */

	public function add_metaboxes() {
		add_meta_box( 'sazan_form_builder', esc_html__( 'طراحی فرم', 'sazan-core' ),
			array( $this, 'render_builder' ), Form_CPT::POST_TYPE, 'normal', 'high' );
		add_meta_box( 'sazan_form_sms', esc_html__( 'پیامک و رفتار فرم', 'sazan-core' ),
			array( $this, 'render_form_settings' ), Form_CPT::POST_TYPE, 'normal', 'default' );
		add_meta_box( 'sazan_form_side', esc_html__( 'استفاده', 'sazan-core' ),
			array( $this, 'render_sidebar' ), Form_CPT::POST_TYPE, 'side', 'high' );
	}

	public function render_sidebar( $post ) {
		$export = wp_nonce_url( admin_url( 'admin-post.php?action=sazan_form_export&form=' . $post->ID ), 'sazan_form_export_' . $post->ID );
		?>
		<p><strong><?php esc_html_e( 'شورت‌کد درون‌خطی:', 'sazan-core' ); ?></strong></p>
		<code style="user-select:all;display:block;padding:8px;background:#f0f0f1">[sazan_form id="<?php echo (int) $post->ID; ?>"]</code>
		<p><strong><?php esc_html_e( 'دکمه‌ی پاپ‌آپ:', 'sazan-core' ); ?></strong></p>
		<code style="user-select:all;display:block;padding:8px;background:#f0f0f1">[sazan_form id="<?php echo (int) $post->ID; ?>" popup="yes" trigger="ثبت نام"]</code>
		<p class="description"><?php esc_html_e( 'در ویجت «تقویم آموزشی» هم می‌توانید این فرم را به‌صورت پاپ‌آپ یا صفحه‌ی جداگانه به دکمه‌ی ثبت‌نام وصل کنید.', 'sazan-core' ); ?></p>
		<hr>
		<a class="button" href="<?php echo esc_url( $export ); ?>"><?php esc_html_e( 'خروجی CSV ثبت‌ها', 'sazan-core' ); ?></a>
		<?php
	}

	public function render_builder( $post ) {
		wp_nonce_field( 'sazan_form_save', 'sazan_form_nonce' );
		$fields = self::get_fields( $post->ID );
		$types  = self::field_types();
		?>
		<div class="szf-builder" id="szf-builder">
			<div class="szf-rows"></div>
			<button type="button" class="button button-secondary szf-add"><?php esc_html_e( '+ افزودن فیلد', 'sazan-core' ); ?></button>

			<script type="text/template" id="szf-row-tpl">
				<div class="szf-row">
					<span class="szf-drag dashicons dashicons-move" title="<?php esc_attr_e( 'جابجایی', 'sazan-core' ); ?>"></span>
					<select class="szf-f-type">
						<?php foreach ( $types as $tk => $tl ) : ?>
							<option value="<?php echo esc_attr( $tk ); ?>"><?php echo esc_html( $tl ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="text" class="szf-f-label" placeholder="<?php esc_attr_e( 'برچسب (مثلاً: نام و نام خانوادگی)', 'sazan-core' ); ?>">
					<input type="text" class="szf-f-name" placeholder="<?php esc_attr_e( 'کلید لاتین (name)', 'sazan-core' ); ?>" dir="ltr">
					<input type="text" class="szf-f-ph" placeholder="<?php esc_attr_e( 'متن راهنما', 'sazan-core' ); ?>">
					<input type="text" class="szf-f-opts" placeholder="<?php esc_attr_e( 'گزینه‌ها (با کاما)', 'sazan-core' ); ?>" hidden>
					<select class="szf-f-width">
						<option value="full"><?php esc_html_e( 'تمام‌عرض', 'sazan-core' ); ?></option>
						<option value="half"><?php esc_html_e( 'نیم‌عرض', 'sazan-core' ); ?></option>
					</select>
					<label class="szf-f-req"><input type="checkbox"> <?php esc_html_e( 'اجباری', 'sazan-core' ); ?></label>
					<button type="button" class="szf-del dashicons dashicons-trash" title="<?php esc_attr_e( 'حذف', 'sazan-core' ); ?>"></button>
				</div>
			</script>

			<input type="hidden" name="szf_fields" id="szf-fields-json" value="<?php echo esc_attr( wp_json_encode( $fields ) ); ?>">
		</div>
		<?php
	}

	public function render_form_settings( $post ) {
		$cfg = self::get_cfg( $post->ID );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th><label><?php esc_html_e( 'متن دکمه‌ی ارسال', 'sazan-core' ); ?></label></th>
				<td><input type="text" name="szf_cfg[submit]" value="<?php echo esc_attr( $cfg['submit'] ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'پیام موفقیت', 'sazan-core' ); ?></label></th>
				<td><input type="text" name="szf_cfg[success]" value="<?php echo esc_attr( $cfg['success'] ); ?>" class="large-text"></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'هدایت پس از ثبت (اختیاری)', 'sazan-core' ); ?></label></th>
				<td><input type="url" name="szf_cfg[redirect]" value="<?php echo esc_attr( $cfg['redirect'] ); ?>" class="large-text" style="direction:ltr" placeholder="https://...">
				<p class="description"><?php esc_html_e( 'در صورت تکمیل، کاربر پس از ثبت به این آدرس منتقل می‌شود.', 'sazan-core' ); ?></p></td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'پیامک فراز اس ام اس', 'sazan-core' ); ?></h3>
		<p class="description"><?php echo wp_kses_post( __( 'متغیرهای پترن باید با <strong>کلید لاتین</strong> فیلدها یکی باشند. مثلاً اگر فیلدی با کلید <code>name</code> دارید، در پترن از متغیر <code>name</code> استفاده کنید.', 'sazan-core' ) ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th><label><?php esc_html_e( 'پترن «ثبت جدید» (به مدیریت)', 'sazan-core' ); ?></label></th>
				<td><input type="text" name="szf_cfg[pat_admin]" value="<?php echo esc_attr( $cfg['pat_admin'] ); ?>" class="regular-text" style="direction:ltr"></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'پترن «تایید ثبت» (به کاربر)', 'sazan-core' ); ?></label></th>
				<td><input type="text" name="szf_cfg[pat_user]" value="<?php echo esc_attr( $cfg['pat_user'] ); ?>" class="regular-text" style="direction:ltr">
				<p class="description"><?php esc_html_e( 'برای ارسال به کاربر باید فرم یک فیلد از نوع «شماره موبایل» داشته باشد.', 'sazan-core' ); ?></p></td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'تایید شماره با کد (OTP)', 'sazan-core' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th><label><?php esc_html_e( 'فعال‌سازی', 'sazan-core' ); ?></label></th>
				<td><label><input type="checkbox" name="szf_cfg[otp_enabled]" value="1" <?php checked( $cfg['otp_enabled'], 1 ); ?>> <?php esc_html_e( 'قبل از ثبت، کد تایید به موبایل کاربر ارسال شود.', 'sazan-core' ); ?></label></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'پترن کد تایید', 'sazan-core' ); ?></label></th>
				<td><input type="text" name="szf_cfg[pat_otp]" value="<?php echo esc_attr( $cfg['pat_otp'] ); ?>" class="regular-text" style="direction:ltr">
				<p class="description"><?php echo wp_kses_post( __( 'پترن با متغیر <code>code</code>.', 'sazan-core' ) ); ?></p></td>
			</tr>
		</table>
		<?php
	}

	public function save_form( $post_id, $post ) {
		if ( ! isset( $_POST['sazan_form_nonce'] ) || ! wp_verify_nonce( $_POST['sazan_form_nonce'], 'sazan_form_save' ) ) { return; }
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

		// فیلدها
		$raw    = json_decode( wp_unslash( $_POST['szf_fields'] ?? '[]' ), true );
		$fields = array();
		$used   = array();
		if ( is_array( $raw ) ) {
			foreach ( $raw as $i => $f ) {
				$type = isset( $f['type'] ) && array_key_exists( $f['type'], self::field_types() ) ? $f['type'] : 'text';
				$name = sanitize_key( $f['name'] ?? '' );
				if ( '' === $name ) { $name = 'field_' . ( $i + 1 ); }
				while ( in_array( $name, $used, true ) ) { $name .= '_2'; }
				$used[] = $name;

				$opts = array();
				foreach ( explode( ',', (string) ( $f['options'] ?? '' ) ) as $o ) {
					$o = trim( $o );
					if ( '' !== $o ) { $opts[] = sanitize_text_field( $o ); }
				}

				$fields[] = array(
					'type'        => $type,
					'label'       => sanitize_text_field( $f['label'] ?? '' ),
					'name'        => $name,
					'placeholder' => sanitize_text_field( $f['placeholder'] ?? '' ),
					'width'       => ( 'half' === ( $f['width'] ?? 'full' ) ) ? 'half' : 'full',
					'required'    => ! empty( $f['required'] ) ? 1 : 0,
					'options'     => $opts,
				);
			}
		}
		update_post_meta( $post_id, '_szf_fields', wp_json_encode( $fields ) );

		// تنظیمات
		$c   = wp_unslash( (array) ( $_POST['szf_cfg'] ?? array() ) );
		$cfg = array(
			'submit'      => sanitize_text_field( $c['submit'] ?? 'ارسال' ),
			'success'     => sanitize_text_field( $c['success'] ?? '' ),
			'redirect'    => esc_url_raw( $c['redirect'] ?? '' ),
			'pat_admin'   => sanitize_text_field( $c['pat_admin'] ?? '' ),
			'pat_user'    => sanitize_text_field( $c['pat_user'] ?? '' ),
			'otp_enabled' => empty( $c['otp_enabled'] ) ? 0 : 1,
			'pat_otp'     => sanitize_text_field( $c['pat_otp'] ?? '' ),
		);
		update_post_meta( $post_id, '_szf_cfg', wp_json_encode( $cfg ) );
	}

	public function builder_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || Form_CPT::POST_TYPE !== $screen->post_type ) { return; }
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) { return; }

		$v = function ( $rel ) {
			$p = SAZAN_CORE_PATH . $rel;
			return file_exists( $p ) ? filemtime( $p ) : SAZAN_CORE_VERSION;
		};
		wp_enqueue_style( 'sazan-form-builder', SAZAN_CORE_URL . 'assets/form/form-builder.css', array(), $v( 'assets/form/form-builder.css' ) );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'sazan-form-builder', SAZAN_CORE_URL . 'assets/form/form-builder.js', array( 'jquery', 'jquery-ui-sortable' ), $v( 'assets/form/form-builder.js' ), true );
	}

	/* ===================== رندر فرانت ===================== */

	public function enqueue_front() {
		if ( $this->enqueued ) { return; }
		$this->enqueued = true;
		$v = function ( $rel ) {
			$p = SAZAN_CORE_PATH . $rel;
			return file_exists( $p ) ? filemtime( $p ) : SAZAN_CORE_VERSION;
		};
		wp_enqueue_style( 'sazan-form', SAZAN_CORE_URL . 'assets/form/form-front.css', array(), $v( 'assets/form/form-front.css' ) );
		wp_enqueue_script( 'sazan-form', SAZAN_CORE_URL . 'assets/form/form-front.js', array(), $v( 'assets/form/form-front.js' ), true );
		wp_localize_script( 'sazan-form', 'SazanForm', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'sazan_form' ),
		) );
	}

	/** صف کردن یک فرم برای چاپ مودال در فوتر (فراخوانی از تقویم/شورت‌کد پاپ‌آپ). */
	public function queue_modal( $form_id ) {
		$form_id = (int) $form_id;
		if ( $form_id && ! in_array( $form_id, $this->modal_queue, true ) ) {
			$this->modal_queue[] = $form_id;
			$this->enqueue_front();
		}
	}

	public function print_modals() {
		foreach ( $this->modal_queue as $fid ) {
			printf(
				'<div class="szf-modal" data-form="%1$d" hidden><div class="szf-modal-overlay"></div><div class="szf-modal-box"><button type="button" class="szf-modal-close" aria-label="بستن">&times;</button>%2$s</div></div>',
				(int) $fid,
				$this->render_form( $fid ) // قبلاً escape شده
			);
		}
	}

	/**
	 * شورت‌کد:
	 *   [sazan_form id="12"]                              → فرم درون‌خطی
	 *   [sazan_form id="12" popup="yes" trigger="ثبت نام"] → دکمه‌ای که مودال باز می‌کند
	 */
	public function shortcode( $atts ) {
		$a = shortcode_atts( array(
			'id'      => 0,
			'popup'   => 'no',
			'trigger' => esc_html__( 'باز کردن فرم', 'sazan-core' ),
		), $atts, 'sazan_form' );

		$id = (int) $a['id'];
		if ( ! $id || Form_CPT::POST_TYPE !== get_post_type( $id ) ) {
			return '';
		}

		if ( in_array( strtolower( (string) $a['popup'] ), array( 'yes', '1', 'true' ), true ) ) {
			$this->queue_modal( $id );
			return sprintf(
				'<button type="button" class="sazan-btn sazan-btn-orange szf-trigger" data-sz-form="%1$d">%2$s</button>',
				$id,
				esc_html( $a['trigger'] )
			);
		}

		$this->enqueue_front();
		return $this->render_form( $id );
	}

	/** رندر HTML خود فرم (بدون مودال). */
	public function render_form( $form_id ) {
		$form_id = (int) $form_id;
		$fields  = self::get_fields( $form_id );
		$cfg     = self::get_cfg( $form_id );
		if ( empty( $fields ) ) {
			return '<div class="sazan-sec szf"><p class="szf-empty">' . esc_html__( 'این فرم هنوز فیلدی ندارد.', 'sazan-core' ) . '</p></div>';
		}

		$phone_key = self::phone_key( $fields );
		$otp       = ( ! empty( $cfg['otp_enabled'] ) && '' !== $phone_key ) ? 1 : 0;

		ob_start();
		?>
		<div class="sazan-sec szf"
			data-form="<?php echo esc_attr( $form_id ); ?>"
			data-otp="<?php echo esc_attr( $otp ); ?>"
			data-phone="<?php echo esc_attr( $phone_key ); ?>"
			data-success="<?php echo esc_attr( $cfg['success'] ); ?>"
			data-redirect="<?php echo esc_attr( $cfg['redirect'] ); ?>">

			<form class="szf-form" novalidate>
				<div class="szf-grid">
					<?php foreach ( $fields as $f ) : ?>
						<?php echo $this->render_field( $f ); // escape داخل متد ?>
					<?php endforeach; ?>
				</div>

				<?php if ( $otp ) : ?>
					<div class="szf-otp" hidden>
						<label class="szf-field szf-full">
							<span><?php esc_html_e( 'کد تایید پیامک‌شده', 'sazan-core' ); ?> <i>*</i></span>
							<input type="text" name="otp_code" dir="ltr" inputmode="numeric" maxlength="6" autocomplete="one-time-code">
						</label>
					</div>
				<?php endif; ?>

				<div class="szf-hp" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
					<label>اگر انسان هستید خالی بگذارید<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
				</div>

				<div class="szf-msg" hidden></div>
				<button type="submit" class="szf-submit"><?php echo esc_html( $cfg['submit'] ); ?></button>
			</form>

			<div class="szf-done" hidden>
				<svg viewBox="0 0 24 24" width="56" height="56" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>
				<p class="szf-done-msg"></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/** رندر یک فیلد. */
	private function render_field( $f ) {
		$type = $f['type'];
		$name = $f['name'];
		$req  = ! empty( $f['required'] );
		$cls  = 'szf-field ' . ( 'half' === $f['width'] ? 'szf-half' : 'szf-full' );
		$reqa = $req ? ' required' : '';
		$star = $req ? ' <i>*</i>' : '';
		$ph   = esc_attr( $f['placeholder'] );

		ob_start();
		if ( 'date' === $type ) {
			// تاریخ شمسی: نمایش readonly + hidden میلادی/شمسی + تقویم
			printf( '<div class="%s szf-date">', esc_attr( $cls ) );
			printf( '<span>%s%s</span>', esc_html( $f['label'] ), $star );
			printf( '<input type="text" class="szf-date-display" readonly placeholder="%s">', $ph ?: esc_attr__( 'برای انتخاب تاریخ کلیک کنید', 'sazan-core' ) );
			printf( '<input type="hidden" name="%s" class="szf-gdate"%s>', esc_attr( $name ), $reqa );
			printf( '<input type="hidden" name="%s_jalali" class="szf-jdate">', esc_attr( $name ) );
			echo '<div class="szf-cal" hidden></div></div>';
		} elseif ( 'textarea' === $type ) {
			printf( '<label class="%s">', esc_attr( $cls ) );
			printf( '<span>%s%s</span>', esc_html( $f['label'] ), $star );
			printf( '<textarea name="%s" rows="4" placeholder="%s"%s></textarea>', esc_attr( $name ), $ph, $reqa );
			echo '</label>';
		} elseif ( 'select' === $type ) {
			printf( '<label class="%s">', esc_attr( $cls ) );
			printf( '<span>%s%s</span>', esc_html( $f['label'] ), $star );
			printf( '<select name="%s"%s>', esc_attr( $name ), $reqa );
			printf( '<option value="">%s</option>', esc_html( $f['placeholder'] ?: __( 'انتخاب کنید', 'sazan-core' ) ) );
			foreach ( (array) $f['options'] as $o ) {
				printf( '<option value="%1$s">%1$s</option>', esc_attr( $o ) );
			}
			echo '</select></label>';
		} else {
			$html_type = in_array( $type, array( 'tel', 'email', 'number' ), true ) ? $type : 'text';
			$dir       = ( 'tel' === $type || 'email' === $type ) ? ' dir="ltr"' : '';
			$mode      = ( 'tel' === $type || 'number' === $type ) ? ' inputmode="numeric"' : '';
			printf( '<label class="%s">', esc_attr( $cls ) );
			printf( '<span>%s%s</span>', esc_html( $f['label'] ), $star );
			printf( '<input type="%s" name="%s" placeholder="%s"%s%s%s>', esc_attr( $html_type ), esc_attr( $name ), $ph, $dir, $mode, $reqa );
			echo '</label>';
		}
		return ob_get_clean();
	}

	/* ===================== دریافت ثبت (AJAX) ===================== */

	public function ajax_submit() {
		check_ajax_referer( 'sazan_form', 'nonce' );

		// honeypot
		if ( ! empty( $_POST['website'] ) ) {
			wp_send_json_success( array( 'msg' => esc_html__( 'ثبت شد.', 'sazan-core' ) ) );
		}

		$form_id = (int) ( $_POST['form_id'] ?? 0 );
		if ( ! $form_id || Form_CPT::POST_TYPE !== get_post_type( $form_id ) ) {
			wp_send_json_error( array( 'msg' => esc_html__( 'فرم نامعتبر است.', 'sazan-core' ) ) );
		}

		$fields    = self::get_fields( $form_id );
		$cfg       = self::get_cfg( $form_id );
		$phone_key = self::phone_key( $fields );

		$vars  = array();   // برای پترن: name => value
		$data  = array();   // برای ذخیره: [ {label,value} ]
		$phone = '';

		foreach ( $fields as $f ) {
			$name = $f['name'];
			$jal  = ( 'date' === $f['type'] ) ? sanitize_text_field( wp_unslash( $_POST[ $name . '_jalali' ] ?? '' ) ) : '';
			$val  = sanitize_text_field( wp_unslash( $_POST[ $name ] ?? '' ) );

			if ( 'tel' === $f['type'] ) {
				$val = preg_replace( '/\D/', '', $val );
				if ( '98' === substr( $val, 0, 2 ) && 12 === strlen( $val ) ) { $val = '0' . substr( $val, 2 ); }
				if ( '9' === substr( $val, 0, 1 ) && 10 === strlen( $val ) )   { $val = '0' . $val; }
			}

			if ( ! empty( $f['required'] ) && '' === $val ) {
				wp_send_json_error( array( 'msg' => sprintf( esc_html__( 'فیلد «%s» الزامی است.', 'sazan-core' ), $f['label'] ) ) );
			}
			if ( 'tel' === $f['type'] && '' !== $val && ! preg_match( '/^09\d{9}$/', $val ) ) {
				wp_send_json_error( array( 'msg' => esc_html__( 'شماره موبایل معتبر نیست.', 'sazan-core' ) ) );
			}
			if ( 'email' === $f['type'] && '' !== $val && ! is_email( $val ) ) {
				wp_send_json_error( array( 'msg' => esc_html__( 'ایمیل معتبر نیست.', 'sazan-core' ) ) );
			}

			$store         = ( 'date' === $f['type'] && '' !== $jal ) ? $jal : $val;
			$vars[ $name ] = (string) $val;            // مقدار خام برای پترن
			$data[]        = array( 'label' => $f['label'], 'value' => $store, 'name' => $name );

			if ( $name === $phone_key ) { $phone = $val; }
		}

		$s = self::settings();

		// محدودیت نرخ بر اساس شماره (در صورت وجود)
		if ( '' !== $phone ) {
			$rl_key = 'szf_rl_' . md5( $phone . '_' . $form_id );
			$rl     = (int) get_transient( $rl_key );
			if ( $rl >= 5 ) {
				wp_send_json_error( array( 'msg' => esc_html__( 'تعداد درخواست‌ها زیاد است؛ کمی بعد تلاش کنید.', 'sazan-core' ) ) );
			}
		}

		// تایید OTP
		if ( ! empty( $cfg['otp_enabled'] ) && '' !== $phone ) {
			$code  = preg_replace( '/\D/', '', wp_unslash( $_POST['otp_code'] ?? '' ) );
			$saved = get_transient( 'szf_otp_' . md5( $phone ) );
			if ( ! $saved || ! hash_equals( (string) $saved, (string) $code ) ) {
				wp_send_json_error( array( 'msg' => esc_html__( 'کد تایید نادرست یا منقضی است.', 'sazan-core' ) ) );
			}
		}

		// ذخیره‌ی ثبت
		$title   = get_the_title( $form_id ) . ' — ' . ( $data[0]['value'] ?? gmdate( 'H:i' ) );
		$post_id = wp_insert_post( array(
			'post_type'   => Form_CPT::ENTRY_TYPE,
			'post_title'  => $title,
			'post_status' => 'publish',
		), true );
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			wp_send_json_error( array( 'msg' => esc_html__( 'خطا در ذخیره‌سازی.', 'sazan-core' ) ) );
		}
		update_post_meta( $post_id, '_szf_form', $form_id );
		update_post_meta( $post_id, '_szf_data', wp_json_encode( $data ) );
		if ( '' !== $phone ) { update_post_meta( $post_id, '_szf_phone', $phone ); }

		// پیامک به مدیریت
		if ( ! empty( $cfg['pat_admin'] ) ) {
			foreach ( $this->admin_mobiles( $s ) as $am ) {
				$this->send_pattern( $am, $cfg['pat_admin'], $vars, $s );
			}
		}
		// پیامک تایید به کاربر
		if ( ! empty( $cfg['pat_user'] ) && '' !== $phone ) {
			$this->send_pattern( $phone, $cfg['pat_user'], $vars, $s );
		}

		if ( '' !== $phone ) {
			set_transient( $rl_key, ( $rl ?? 0 ) + 1, HOUR_IN_SECONDS );
			delete_transient( 'szf_otp_' . md5( $phone ) );
		}

		wp_send_json_success( array(
			'msg'      => $cfg['success'],
			'redirect' => $cfg['redirect'],
		) );
	}

	public function ajax_send_code() {
		check_ajax_referer( 'sazan_form', 'nonce' );
		$form_id = (int) ( $_POST['form_id'] ?? 0 );
		$cfg     = self::get_cfg( $form_id );
		if ( empty( $cfg['otp_enabled'] ) ) { wp_send_json_error( array( 'msg' => 'OTP غیرفعال است.' ) ); }

		$phone = preg_replace( '/\D/', '', wp_unslash( $_POST['phone'] ?? '' ) );
		if ( '9' === substr( $phone, 0, 1 ) && 10 === strlen( $phone ) ) { $phone = '0' . $phone; }
		if ( ! preg_match( '/^09\d{9}$/', $phone ) ) { wp_send_json_error( array( 'msg' => 'شماره معتبر نیست.' ) ); }

		if ( get_transient( 'szf_otp_wait_' . md5( $phone ) ) ) {
			wp_send_json_error( array( 'msg' => 'کمی صبر کنید و دوباره تلاش کنید.' ) );
		}
		$code = (string) wp_rand( 10000, 99999 );
		set_transient( 'szf_otp_' . md5( $phone ), $code, 5 * MINUTE_IN_SECONDS );
		set_transient( 'szf_otp_wait_' . md5( $phone ), 1, 90 );
		$this->send_pattern( $phone, $cfg['pat_otp'], array( 'code' => $code ), self::settings() );
		wp_send_json_success();
	}

	/* ===================== خروجی CSV ===================== */

	public function export_csv() {
		$form_id = (int) ( $_GET['form'] ?? 0 );
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'sazan_form_export_' . $form_id ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'sazan-core' ) );
		}
		$fields = self::get_fields( $form_id );
		$labels = wp_list_pluck( $fields, 'label', 'name' );

		$ids = get_posts( array(
			'post_type'   => Form_CPT::ENTRY_TYPE,
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
			'orderby'     => 'date',
			'order'       => 'DESC',
			'meta_query'  => array( array( 'key' => '_szf_form', 'value' => $form_id ) ),
		) );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=sazan-form-' . $form_id . '-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fprintf( $out, "\xEF\xBB\xBF" );
		fputcsv( $out, array_merge( array( esc_html__( 'تاریخ ثبت', 'sazan-core' ) ), array_values( $labels ) ) );

		foreach ( $ids as $id ) {
			$data = json_decode( (string) get_post_meta( $id, '_szf_data', true ), true );
			$map  = array();
			if ( is_array( $data ) ) {
				foreach ( $data as $row ) { $map[ $row['name'] ] = $row['value']; }
			}
			$line = array( get_the_date( 'Y/m/d H:i', $id ) );
			foreach ( $labels as $k => $lbl ) { $line[] = $map[ $k ] ?? ''; }
			fputcsv( $out, $line );
		}
		fclose( $out );
		exit;
	}

	/* ===================== ابزارها ===================== */

	private function admin_mobiles( $s ) {
		$out = array();
		foreach ( preg_split( '/[,\n\r]+/', (string) $s['admin_mobiles'] ) as $m ) {
			$m = preg_replace( '/\D/', '', $m );
			if ( '98' === substr( $m, 0, 2 ) && 12 === strlen( $m ) ) { $m = '0' . substr( $m, 2 ); }
			if ( '9' === substr( $m, 0, 1 ) && 10 === strlen( $m ) )   { $m = '0' . $m; }
			if ( preg_match( '/^09\d{9}$/', $m ) ) { $out[] = $m; }
		}
		return array_unique( $out );
	}

	private function send_pattern( $mobile, $code, $vars, $s ) {
		if ( empty( $s['sms_apikey'] ) || empty( $s['sms_sender'] ) || empty( $code ) || empty( $mobile ) ) {
			return;
		}
		$body = array(
			'code'      => $code,
			'sender'    => $s['sms_sender'],
			'recipient' => $mobile,
			'variable'  => array_map( 'strval', $vars ),
		);
		$res = wp_remote_post( self::ENDPOINT_PATTERN, array(
			'timeout' => 20,
			'headers' => array(
				'Content-Type' => 'application/json',
				'apikey'       => $s['sms_apikey'],
			),
			'body'    => wp_json_encode( $body ),
		) );
		if ( is_wp_error( $res ) ) {
			error_log( 'Sazan Form SMS error: ' . $res->get_error_message() );
		}
	}
}
