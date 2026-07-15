<?php
/**
 * موتور فرم مشاوره کسب‌وکار:
 *  - رندر فرم (فراخوانی از ویجت)
 *  - دریافت و ذخیره‌ی درخواست (AJAX)
 *  - ارسال پیامک پترن فراز اس ام اس (به مدیریت + کاربر)
 *  - زمان‌بندی پیامک یادآوری ۵ ساعت قبل از جلسه (WP-Cron)
 *  - صفحه‌ی تنظیمات
 *
 * @package Sazan\Core
 */

namespace Sazan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Consult_Engine {

	const ENDPOINT_PATTERN = 'https://api2.ippanel.com/api/v1/sms/pattern/normal/send';
	const CRON_HOOK        = 'sazan_consult_reminder';

	private static $instance = null;
	private $enqueued = false;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_ajax_sazan_consult_submit', array( $this, 'ajax_submit' ) );
		add_action( 'wp_ajax_nopriv_sazan_consult_submit', array( $this, 'ajax_submit' ) );

		// ظرفیت ساعت‌ها + ارسال کد تایید
		add_action( 'wp_ajax_sazan_consult_slots', array( $this, 'ajax_slots' ) );
		add_action( 'wp_ajax_nopriv_sazan_consult_slots', array( $this, 'ajax_slots' ) );
		add_action( 'wp_ajax_sazan_consult_send_code', array( $this, 'ajax_send_code' ) );
		add_action( 'wp_ajax_nopriv_sazan_consult_send_code', array( $this, 'ajax_send_code' ) );

		// یادآوری زمان‌بندی‌شده
		add_action( self::CRON_HOOK, array( $this, 'cron_reminder' ), 10, 1 );
		add_action( 'before_delete_post', array( $this, 'clear_scheduled' ) );
		add_action( 'wp_trash_post', array( $this, 'clear_scheduled' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
			add_action( 'save_post_' . Consult_CPT::POST_TYPE, array( $this, 'save_metabox' ), 10, 2 );
			add_action( 'admin_post_sazan_consult_export', array( $this, 'export_csv' ) );
		}
	}

	/* ===================== تنظیمات ===================== */

	public static function settings() {
		return wp_parse_args( (array) get_option( 'sazan_consult_settings', array() ), array(
			'sms_apikey'      => '',
			'sms_sender'      => '',
			'admin_mobiles'   => '',   // شماره‌های مدیریت، با کاما یا خط جدید
			'pat_admin'       => '',   // پترن: مشاوره جدید ثبت شد (به مدیریت)
			'pat_user'        => '',   // پترن: تایید ثبت (به کاربر)
			'pat_reminder'    => '',   // پترن: یادآوری ۵ ساعت قبل (کاربر + مدیریت)
			'reminder_hours'  => 5,
			'daily_cap'       => 0,    // سقف رزرو هر روز (۰ = نامحدود)
			'slot_cap'        => 1,    // ظرفیت هر ساعت
			'otp_enabled'     => 0,    // تایید شماره با کد پیامکی
			'pat_otp'         => '',   // پترن کد تایید (متغیر: code)
			'pat_confirmed'   => '',   // پیامک هنگام تغییر وضعیت به «تایید شده»
			'pat_done'        => '',
			'pat_cancelled'   => '',
		) );
	}

	public function menu() {
		add_submenu_page(
			'edit.php?post_type=' . Consult_CPT::POST_TYPE,
			esc_html__( 'تنظیمات پیامک مشاوره', 'sazan-core' ),
			esc_html__( 'تنظیمات پیامک', 'sazan-core' ),
			'manage_options',
			'sazan-consult-settings',
			array( $this, 'render_settings' )
		);
		add_submenu_page(
			'edit.php?post_type=' . Consult_CPT::POST_TYPE,
			esc_html__( 'خروجی CSV', 'sazan-core' ),
			esc_html__( 'خروجی CSV', 'sazan-core' ),
			'manage_options',
			'sazan-consult-export',
			array( $this, 'render_export' )
		);
	}

	public function render_export() {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=sazan_consult_export' ), 'sazan_consult_export' );
		echo '<div class="wrap"><h1>' . esc_html__( 'خروجی CSV درخواست‌های مشاوره', 'sazan-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'فایل CSV همه‌ی درخواست‌ها (سازگار با Excel و CRM) دانلود می‌شود.', 'sazan-core' ) . '</p>';
		echo '<a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'دانلود CSV', 'sazan-core' ) . '</a></div>';
	}

	public function register_settings() {
		register_setting( 'sazan_consult_group', 'sazan_consult_settings', array( $this, 'sanitize' ) );
	}

	public function sanitize( $in ) {
		return array(
			'sms_apikey'     => sanitize_text_field( $in['sms_apikey'] ?? '' ),
			'sms_sender'     => sanitize_text_field( $in['sms_sender'] ?? '' ),
			'admin_mobiles'  => sanitize_textarea_field( $in['admin_mobiles'] ?? '' ),
			'pat_admin'      => sanitize_text_field( $in['pat_admin'] ?? '' ),
			'pat_user'       => sanitize_text_field( $in['pat_user'] ?? '' ),
			'pat_reminder'   => sanitize_text_field( $in['pat_reminder'] ?? '' ),
			'reminder_hours' => max( 1, absint( $in['reminder_hours'] ?? 5 ) ),
			'daily_cap'      => absint( $in['daily_cap'] ?? 0 ),
			'slot_cap'       => max( 1, absint( $in['slot_cap'] ?? 1 ) ),
			'otp_enabled'    => empty( $in['otp_enabled'] ) ? 0 : 1,
			'pat_otp'        => sanitize_text_field( $in['pat_otp'] ?? '' ),
			'pat_confirmed'  => sanitize_text_field( $in['pat_confirmed'] ?? '' ),
			'pat_done'       => sanitize_text_field( $in['pat_done'] ?? '' ),
			'pat_cancelled'  => sanitize_text_field( $in['pat_cancelled'] ?? '' ),
		);
	}

	public function render_settings() {
		$s = self::settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'تنظیمات پیامک مشاوره کسب‌وکار', 'sazan-core' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'sazan_consult_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'کلید API (apikey)', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_consult_settings[sms_apikey]" value="<?php echo esc_attr( $s['sms_apikey'] ); ?>" class="regular-text" style="direction:ltr"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'شماره فرستنده', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_consult_settings[sms_sender]" value="<?php echo esc_attr( $s['sms_sender'] ); ?>" class="regular-text" style="direction:ltr" placeholder="+983000505"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'شماره‌های مدیریت', 'sazan-core' ); ?></label></th>
						<td><textarea name="sazan_consult_settings[admin_mobiles]" rows="2" class="large-text" style="direction:ltr" placeholder="09120000000، 09130000000"><?php echo esc_textarea( $s['admin_mobiles'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'هر شماره در یک خط یا جدا با کاما؛ پیامک «مشاوره جدید» و «یادآوری» به این شماره‌ها هم می‌رود.', 'sazan-core' ); ?></p></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'کدهای پترن', 'sazan-core' ); ?></h2>
				<p class="description"><?php echo wp_kses_post( __( 'پترن‌ها را در پنل فراز با این متغیرها بسازید: <code>name</code> (نام)، <code>business</code> (نام کسب‌وکار)، <code>field</code> (حوزه)، <code>staff</code> (تعداد پرسنل)، <code>phone</code> (تماس)، <code>address</code> (آدرس)، <code>date</code> (تاریخ شمسی)، <code>time</code> (ساعت).', 'sazan-core' ) ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'پترن «مشاوره جدید» (به مدیریت)', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_consult_settings[pat_admin]" value="<?php echo esc_attr( $s['pat_admin'] ); ?>" class="regular-text" style="direction:ltr"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'پترن «تایید ثبت» (به کاربر)', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_consult_settings[pat_user]" value="<?php echo esc_attr( $s['pat_user'] ); ?>" class="regular-text" style="direction:ltr"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'پترن «یادآوری جلسه»', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_consult_settings[pat_reminder]" value="<?php echo esc_attr( $s['pat_reminder'] ); ?>" class="regular-text" style="direction:ltr"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'یادآوری چند ساعت قبل؟', 'sazan-core' ); ?></label></th>
						<td><input type="number" min="1" name="sazan_consult_settings[reminder_hours]" value="<?php echo esc_attr( $s['reminder_hours'] ); ?>" class="small-text"> <?php esc_html_e( 'ساعت', 'sazan-core' ); ?></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'ظرفیت رزرو', 'sazan-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'ظرفیت هر ساعت', 'sazan-core' ); ?></label></th>
						<td><input type="number" min="1" name="sazan_consult_settings[slot_cap]" value="<?php echo esc_attr( $s['slot_cap'] ); ?>" class="small-text">
						<p class="description"><?php esc_html_e( 'پس از تکمیل، آن ساعت برای بقیه غیرفعال می‌شود.', 'sazan-core' ); ?></p></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'سقف رزرو روزانه', 'sazan-core' ); ?></label></th>
						<td><input type="number" min="0" name="sazan_consult_settings[daily_cap]" value="<?php echo esc_attr( $s['daily_cap'] ); ?>" class="small-text"> <?php esc_html_e( '(۰ = نامحدود)', 'sazan-core' ); ?></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'پیامک تغییر وضعیت', 'sazan-core' ); ?></h2>
				<p class="description"><?php esc_html_e( 'با تغییر وضعیت درخواست در پنل، پیامک پترن مربوطه به کاربر ارسال می‌شود (متغیرها مانند بالا).', 'sazan-core' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'پترن «تایید شده»', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_consult_settings[pat_confirmed]" value="<?php echo esc_attr( $s['pat_confirmed'] ); ?>" class="regular-text" style="direction:ltr"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'پترن «انجام شده»', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_consult_settings[pat_done]" value="<?php echo esc_attr( $s['pat_done'] ); ?>" class="regular-text" style="direction:ltr"></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'پترن «لغو شده»', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_consult_settings[pat_cancelled]" value="<?php echo esc_attr( $s['pat_cancelled'] ); ?>" class="regular-text" style="direction:ltr"></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'ضد اسپم / تایید شماره', 'sazan-core' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th><label><?php esc_html_e( 'تایید شماره با کد پیامکی', 'sazan-core' ); ?></label></th>
						<td><label><input type="checkbox" name="sazan_consult_settings[otp_enabled]" value="1" <?php checked( $s['otp_enabled'], 1 ); ?>> <?php esc_html_e( 'فعال', 'sazan-core' ); ?></label>
						<p class="description"><?php esc_html_e( 'قبل از ثبت، کد تایید به موبایل کاربر ارسال و بررسی می‌شود.', 'sazan-core' ); ?></p></td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'پترن کد تایید', 'sazan-core' ); ?></label></th>
						<td><input type="text" name="sazan_consult_settings[pat_otp]" value="<?php echo esc_attr( $s['pat_otp'] ); ?>" class="regular-text" style="direction:ltr">
						<p class="description"><?php echo wp_kses_post( __( 'پترن با متغیر <code>code</code>.', 'sazan-core' ) ); ?></p></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/* ===================== رندر فرم ===================== */

	public function enqueue_front() {
		if ( $this->enqueued ) { return; }
		$this->enqueued = true;
		$ver = function ( $rel ) {
			$p = SAZAN_CORE_PATH . $rel;
			return file_exists( $p ) ? filemtime( $p ) : SAZAN_CORE_VERSION;
		};
		wp_enqueue_style( 'sazan-consult', SAZAN_CORE_URL . 'assets/consult/consult-front.css', array(), $ver( 'assets/consult/consult-front.css' ) );
		wp_enqueue_script( 'sazan-consult', SAZAN_CORE_URL . 'assets/consult/consult-front.js', array(), $ver( 'assets/consult/consult-front.js' ), true );
		$s = self::settings();
		wp_localize_script( 'sazan-consult', 'SazanConsult', array(
			'ajax'    => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'sazan_consult' ),
			'slotCap' => (int) $s['slot_cap'],
			'dayCap'  => (int) $s['daily_cap'],
			'otp'     => (int) $s['otp_enabled'],
		) );
	}

	/**
	 * @param array $a تنظیمات ویجت (intro, success, slots, btn, title …).
	 */
	public function render( $a = array() ) {
		$this->enqueue_front();

		$slots = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) ( $a['slots'] ?? '' ) ) as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) { $slots[] = $line; }
		}
		if ( empty( $slots ) ) {
			$slots = array( '۹:۰۰', '۱۰:۰۰', '۱۱:۰۰', '۱۲:۰۰', '۱۴:۰۰', '۱۵:۰۰', '۱۶:۰۰', '۱۷:۰۰' );
		}

		$btn     = $a['btn'] ?? esc_html__( 'ثبت درخواست مشاوره', 'sazan-core' );
		$intro   = $a['intro'] ?? '';
		$success = $a['success'] ?? esc_html__( 'درخواست شما با موفقیت ثبت شد. به‌زودی با شما تماس می‌گیریم.', 'sazan-core' );
		$cfg     = self::settings();
		$a['otp'] = (int) $cfg['otp_enabled'];

		ob_start();
		?>
		<div class="sazan-sec sazan-consult" data-success="<?php echo esc_attr( $success ); ?>" data-otp="<?php echo esc_attr( $a['otp'] ); ?>">
			<form class="szc-form" novalidate>
				<?php if ( $intro ) : ?><p class="szc-intro"><?php echo esc_html( $intro ); ?></p><?php endif; ?>

				<div class="szc-grid">
					<label class="szc-field">
						<span><?php esc_html_e( 'نام کسب‌وکار', 'sazan-core' ); ?> <i>*</i></span>
						<input type="text" name="business" required>
					</label>
					<label class="szc-field">
						<span><?php esc_html_e( 'حوزه‌ی کسب‌وکار', 'sazan-core' ); ?> <i>*</i></span>
						<input type="text" name="field" required>
					</label>
					<label class="szc-field">
						<span><?php esc_html_e( 'تعداد پرسنل', 'sazan-core' ); ?></span>
						<input type="number" name="staff" min="0" inputmode="numeric">
					</label>
					<label class="szc-field">
						<span><?php esc_html_e( 'نام و نام خانوادگی', 'sazan-core' ); ?> <i>*</i></span>
						<input type="text" name="name" required>
					</label>
					<label class="szc-field">
						<span><?php esc_html_e( 'شماره تماس', 'sazan-core' ); ?> <i>*</i></span>
						<input type="tel" name="phone" required dir="ltr" inputmode="numeric" placeholder="09xxxxxxxxx">
					</label>
					<label class="szc-field szc-col2">
						<span><?php esc_html_e( 'آدرس', 'sazan-core' ); ?></span>
						<input type="text" name="address">
					</label>

					<div class="szc-field szc-col2">
						<span><?php esc_html_e( 'تاریخ مدنظر برای مشاوره', 'sazan-core' ); ?> <i>*</i></span>
						<input type="text" class="szc-date-display" readonly placeholder="<?php esc_attr_e( 'برای انتخاب تاریخ کلیک کنید', 'sazan-core' ); ?>">
						<input type="hidden" name="gdate" class="szc-gdate" required>
						<input type="hidden" name="jdate" class="szc-jdate">
						<div class="szc-cal" hidden></div>
					</div>

					<div class="szc-field szc-col2">
						<span><?php esc_html_e( 'ساعت مدنظر', 'sazan-core' ); ?> <i>*</i></span>
						<div class="szc-slots">
							<?php foreach ( $slots as $i => $slot ) : ?>
								<label class="szc-slot">
									<input type="radio" name="time" value="<?php echo esc_attr( $slot ); ?>" <?php checked( 0, $i ); ?>>
									<span><?php echo esc_html( $slot ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<div class="szc-msg" hidden></div>

				<div class="szc-hp" aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden">
					<label>اگر انسان هستید این فیلد را خالی بگذارید
						<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
				</div>

				<?php if ( ! empty( $a['otp'] ) ) : ?>
				<div class="szc-otp" hidden>
					<label class="szc-field">
						<span><?php esc_html_e( 'کد تایید پیامک‌شده', 'sazan-core' ); ?> <i>*</i></span>
						<input type="text" name="otp_code" dir="ltr" inputmode="numeric" maxlength="6" autocomplete="one-time-code">
					</label>
				</div>
				<?php endif; ?>
				<button type="submit" class="szc-submit"><?php echo esc_html( $btn ); ?></button>
			</form>
			<div class="szc-done" hidden>
				<svg viewBox="0 0 24 24" width="56" height="56" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>
				<p class="szc-done-msg"></p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ===================== ثبت درخواست (AJAX) ===================== */

	public function ajax_submit() {
		check_ajax_referer( 'sazan_consult', 'nonce' );

		// honeypot: ربات‌ها این فیلد را پر می‌کنند
		if ( ! empty( $_POST['website'] ) ) {
			wp_send_json_success( array( 'msg' => esc_html__( 'ثبت شد.', 'sazan-core' ) ) ); // پاسخ موفق ساختگی
		}

		$business = sanitize_text_field( wp_unslash( $_POST['business'] ?? '' ) );
		$field    = sanitize_text_field( wp_unslash( $_POST['field'] ?? '' ) );
		$staff    = sanitize_text_field( wp_unslash( $_POST['staff'] ?? '' ) );
		$name     = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$address  = sanitize_text_field( wp_unslash( $_POST['address'] ?? '' ) );
		$jdate    = sanitize_text_field( wp_unslash( $_POST['jdate'] ?? '' ) );
		$gdate    = sanitize_text_field( wp_unslash( $_POST['gdate'] ?? '' ) ); // Y-m-d میلادی
		$time     = sanitize_text_field( wp_unslash( $_POST['time'] ?? '' ) );
		$phone    = preg_replace( '/\D/', '', wp_unslash( $_POST['phone'] ?? '' ) );

		// نرمال‌سازی موبایل
		if ( '98' === substr( $phone, 0, 2 ) && 12 === strlen( $phone ) ) { $phone = '0' . substr( $phone, 2 ); }
		if ( '9' === substr( $phone, 0, 1 ) && 10 === strlen( $phone ) )   { $phone = '0' . $phone; }

		if ( '' === $business || '' === $field || '' === $name ) {
			wp_send_json_error( array( 'msg' => esc_html__( 'لطفاً فیلدهای ضروری را کامل کنید.', 'sazan-core' ) ) );
		}
		if ( ! preg_match( '/^09\d{9}$/', $phone ) ) {
			wp_send_json_error( array( 'msg' => esc_html__( 'شماره تماس معتبر نیست.', 'sazan-core' ) ) );
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $gdate ) || '' === $time ) {
			wp_send_json_error( array( 'msg' => esc_html__( 'تاریخ و ساعت مشاوره را انتخاب کنید.', 'sazan-core' ) ) );
		}

		$s = self::settings();

		// محدودیت نرخ بر اساس شماره (حداکثر ۵ ثبت در ساعت)
		$rl_key = 'szc_rl_' . md5( $phone );
		$rl     = (int) get_transient( $rl_key );
		if ( $rl >= 5 ) {
			wp_send_json_error( array( 'msg' => esc_html__( 'تعداد درخواست‌های شما زیاد است؛ کمی بعد دوباره تلاش کنید.', 'sazan-core' ) ) );
		}

		// تایید کد پیامکی
		if ( ! empty( $s['otp_enabled'] ) ) {
			$code  = preg_replace( '/\D/', '', wp_unslash( $_POST['otp_code'] ?? '' ) );
			$saved = get_transient( 'szc_otp_' . md5( $phone ) );
			if ( ! $saved || ! hash_equals( (string) $saved, (string) $code ) ) {
				wp_send_json_error( array( 'msg' => esc_html__( 'کد تایید نادرست یا منقضی است.', 'sazan-core' ) ) );
			}
		}

		// بررسی ظرفیت
		$usage = $this->slot_usage( $gdate );
		if ( ! empty( $s['daily_cap'] ) && $usage['day'] >= (int) $s['daily_cap'] ) {
			wp_send_json_error( array( 'msg' => esc_html__( 'ظرفیت این روز تکمیل شده است.', 'sazan-core' ) ) );
		}
		if ( ( $usage['slots'][ $time ] ?? 0 ) >= max( 1, (int) $s['slot_cap'] ) ) {
			wp_send_json_error( array( 'msg' => esc_html__( 'این ساعت پر شده است؛ ساعت دیگری انتخاب کنید.', 'sazan-core' ) ) );
		}

		// ذخیره به‌صورت پست
		$post_id = wp_insert_post( array(
			'post_type'   => Consult_CPT::POST_TYPE,
			'post_title'  => $name . ' — ' . $business,
			'post_status' => 'publish',
		), true );

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			wp_send_json_error( array( 'msg' => esc_html__( 'خطا در ذخیره‌سازی. دوباره تلاش کنید.', 'sazan-core' ) ) );
		}

		$meta = array(
			'_sz_business' => $business,
			'_sz_field'    => $field,
			'_sz_staff'    => $staff,
			'_sz_name'     => $name,
			'_sz_phone'    => $phone,
			'_sz_address'  => $address,
			'_sz_jdate'    => $jdate,
			'_sz_gdate'    => $gdate,
			'_sz_time'     => $time,
			'_sz_status'   => 'pending',
		);
		foreach ( $meta as $k => $v ) {
			update_post_meta( $post_id, $k, $v );
		}

		$vars = array(
			'name'     => $name,
			'business' => $business,
			'field'    => $field,
			'staff'    => (string) $staff,
			'phone'    => $phone,
			'address'  => $address,
			'date'     => $jdate,
			'time'     => $time,
		);

		// ۱) پیامک به مدیریت: مشاوره‌ی جدید ثبت شد
		foreach ( $this->admin_mobiles( $s ) as $admin_mob ) {
			$this->send_pattern( $admin_mob, $s['pat_admin'], $vars, $s );
		}
		// ۲) پیامک تایید به کاربر
		$this->send_pattern( $phone, $s['pat_user'], $vars, $s );

		// ۳) زمان‌بندی یادآوری
		$this->schedule_reminder( $post_id, $gdate, $time, (int) $s['reminder_hours'] );

		// به‌روزرسانی محدودیت نرخ و پاک‌سازی کد تایید
		set_transient( $rl_key, $rl + 1, HOUR_IN_SECONDS );
		delete_transient( 'szc_otp_' . md5( $phone ) );

		wp_send_json_success( array( 'msg' => esc_html__( 'ثبت شد.', 'sazan-core' ) ) );
	}

	/* ===================== یادآوری (WP-Cron) ===================== */

	private function schedule_reminder( $post_id, $gdate, $time, $hours ) {
		// استخراج ساعت/دقیقه از رشته‌ی ساعت (با ارقام فارسی یا انگلیسی)
		$norm = $this->fa_to_en( $time );
		if ( ! preg_match( '/(\d{1,2})\s*:\s*(\d{2})/', $norm, $m ) ) {
			$m = array( 0, '9', '00' );
		}
		$hm = sprintf( '%02d:%02d:00', (int) $m[1], (int) $m[2] );

		$ts = strtotime( $gdate . ' ' . $hm . ' ' . wp_timezone_string() );
		if ( ! $ts ) { return; }

		$remind_at = $ts - ( max( 1, $hours ) * HOUR_IN_SECONDS );
		if ( $remind_at <= time() ) { return; } // جلسه خیلی نزدیک است؛ یادآوری زمان‌بندی نمی‌شود

		wp_schedule_single_event( $remind_at, self::CRON_HOOK, array( $post_id ) );
	}

	public function clear_scheduled( $post_id ) {
		if ( get_post_type( $post_id ) === Consult_CPT::POST_TYPE ) {
			wp_clear_scheduled_hook( self::CRON_HOOK, array( $post_id ) );
		}
	}

	public function cron_reminder( $post_id ) {
		if ( get_post_type( $post_id ) !== Consult_CPT::POST_TYPE ) { return; }

		$s    = self::settings();
		$vars = array(
			'name'     => get_post_meta( $post_id, '_sz_name', true ),
			'business' => get_post_meta( $post_id, '_sz_business', true ),
			'field'    => get_post_meta( $post_id, '_sz_field', true ),
			'staff'    => (string) get_post_meta( $post_id, '_sz_staff', true ),
			'phone'    => get_post_meta( $post_id, '_sz_phone', true ),
			'address'  => get_post_meta( $post_id, '_sz_address', true ),
			'date'     => get_post_meta( $post_id, '_sz_jdate', true ),
			'time'     => get_post_meta( $post_id, '_sz_time', true ),
		);

		// به کاربر
		$this->send_pattern( $vars['phone'], $s['pat_reminder'], $vars, $s );
		// به مدیریت
		foreach ( $this->admin_mobiles( $s ) as $admin_mob ) {
			$this->send_pattern( $admin_mob, $s['pat_reminder'], $vars, $s );
		}
	}

	/* ===================== ابزارها ===================== */

	/** شمارش رزروهای یک روز به تفکیک ساعت (وضعیت لغو حساب نمی‌شود). */
	private function slot_usage( $gdate ) {
		$ids = get_posts( array(
			'post_type'   => Consult_CPT::POST_TYPE,
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
			'meta_query'  => array( array( 'key' => '_sz_gdate', 'value' => $gdate ) ),
		) );
		$slots = array();
		$day   = 0;
		foreach ( $ids as $id ) {
			if ( 'cancelled' === get_post_meta( $id, '_sz_status', true ) ) { continue; }
			$t = get_post_meta( $id, '_sz_time', true );
			$slots[ $t ] = ( $slots[ $t ] ?? 0 ) + 1;
			$day++;
		}
		return array( 'slots' => $slots, 'day' => $day );
	}

	public function ajax_slots() {
		check_ajax_referer( 'sazan_consult', 'nonce' );
		$gdate = sanitize_text_field( wp_unslash( $_POST['gdate'] ?? '' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $gdate ) ) {
			wp_send_json_error();
		}
		$s     = self::settings();
		$usage = $this->slot_usage( $gdate );
		$full  = ! empty( $s['daily_cap'] ) && $usage['day'] >= (int) $s['daily_cap'];
		wp_send_json_success( array( 'taken' => $usage['slots'], 'full' => $full ) );
	}

	public function ajax_send_code() {
		check_ajax_referer( 'sazan_consult', 'nonce' );
		$s = self::settings();
		if ( empty( $s['otp_enabled'] ) ) { wp_send_json_error( array( 'msg' => 'غیرفعال است.' ) ); }

		$phone = preg_replace( '/\D/', '', wp_unslash( $_POST['phone'] ?? '' ) );
		if ( '9' === substr( $phone, 0, 1 ) && 10 === strlen( $phone ) ) { $phone = '0' . $phone; }
		if ( ! preg_match( '/^09\d{9}$/', $phone ) ) { wp_send_json_error( array( 'msg' => 'شماره معتبر نیست.' ) ); }

		// محدودیت ارسال کد: هر شماره حداکثر هر ۹۰ ثانیه یک‌بار
		if ( get_transient( 'szc_otp_wait_' . md5( $phone ) ) ) {
			wp_send_json_error( array( 'msg' => 'کمی صبر کنید و دوباره تلاش کنید.' ) );
		}
		$code = (string) wp_rand( 10000, 99999 );
		set_transient( 'szc_otp_' . md5( $phone ), $code, 5 * MINUTE_IN_SECONDS );
		set_transient( 'szc_otp_wait_' . md5( $phone ), 1, 90 );
		$this->send_pattern( $phone, $s['pat_otp'], array( 'code' => $code ), $s );
		wp_send_json_success();
	}

	/* ===================== متاباکس وضعیت + پیامک ===================== */

	public function add_metabox() {
		add_meta_box( 'sazan_consult_status', esc_html__( 'وضعیت و جزئیات', 'sazan-core' ),
			array( $this, 'render_metabox' ), Consult_CPT::POST_TYPE, 'side', 'high' );
	}

	public function render_metabox( $post ) {
		wp_nonce_field( 'sazan_consult_meta', 'sazan_consult_meta_nonce' );
		$cur = get_post_meta( $post->ID, '_sz_status', true ) ?: 'pending';
		echo '<p><label><strong>' . esc_html__( 'وضعیت درخواست', 'sazan-core' ) . '</strong></label><br>';
		echo '<select name="sz_status" style="width:100%">';
		foreach ( Consult_CPT::statuses() as $k => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $cur, $k, false ), esc_html( $label ) );
		}
		echo '</select></p>';
		echo '<p class="description">' . esc_html__( 'با تغییر وضعیت، در صورت تنظیم پترن، پیامک به کاربر ارسال می‌شود.', 'sazan-core' ) . '</p>';

		$rows = array(
			esc_html__( 'کسب‌وکار', 'sazan-core' )  => get_post_meta( $post->ID, '_sz_business', true ),
			esc_html__( 'حوزه', 'sazan-core' )       => get_post_meta( $post->ID, '_sz_field', true ),
			esc_html__( 'پرسنل', 'sazan-core' )      => get_post_meta( $post->ID, '_sz_staff', true ),
			esc_html__( 'تماس', 'sazan-core' )       => get_post_meta( $post->ID, '_sz_phone', true ),
			esc_html__( 'آدرس', 'sazan-core' )       => get_post_meta( $post->ID, '_sz_address', true ),
			esc_html__( 'تاریخ', 'sazan-core' )      => get_post_meta( $post->ID, '_sz_jdate', true ),
			esc_html__( 'ساعت', 'sazan-core' )       => get_post_meta( $post->ID, '_sz_time', true ),
		);
		echo '<hr>';
		foreach ( $rows as $k => $v ) {
			if ( '' === $v ) { continue; }
			echo '<p style="margin:4px 0"><strong>' . esc_html( $k ) . ':</strong> ' . esc_html( $v ) . '</p>';
		}
	}

	public function save_metabox( $post_id, $post ) {
		if ( ! isset( $_POST['sazan_consult_meta_nonce'] ) || ! wp_verify_nonce( $_POST['sazan_consult_meta_nonce'], 'sazan_consult_meta' ) ) { return; }
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
		if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
		if ( ! isset( $_POST['sz_status'] ) ) { return; }

		$new = sanitize_key( $_POST['sz_status'] );
		if ( ! array_key_exists( $new, Consult_CPT::statuses() ) ) { return; }
		$old = get_post_meta( $post_id, '_sz_status', true );
		update_post_meta( $post_id, '_sz_status', $new );

		if ( $new !== $old ) {
			$this->send_status_sms( $post_id, $new );
		}
	}

	private function send_status_sms( $post_id, $status ) {
		$s   = self::settings();
		$map = array( 'confirmed' => 'pat_confirmed', 'done' => 'pat_done', 'cancelled' => 'pat_cancelled' );
		if ( empty( $map[ $status ] ) || empty( $s[ $map[ $status ] ] ) ) { return; }

		$phone = get_post_meta( $post_id, '_sz_phone', true );
		$vars  = array(
			'name'     => get_post_meta( $post_id, '_sz_name', true ),
			'business' => get_post_meta( $post_id, '_sz_business', true ),
			'field'    => get_post_meta( $post_id, '_sz_field', true ),
			'staff'    => (string) get_post_meta( $post_id, '_sz_staff', true ),
			'phone'    => $phone,
			'address'  => get_post_meta( $post_id, '_sz_address', true ),
			'date'     => get_post_meta( $post_id, '_sz_jdate', true ),
			'time'     => get_post_meta( $post_id, '_sz_time', true ),
		);
		$this->send_pattern( $phone, $s[ $map[ $status ] ], $vars, $s );

		// لغو، یادآوری زمان‌بندی‌شده را پاک می‌کند
		if ( 'cancelled' === $status ) {
			wp_clear_scheduled_hook( self::CRON_HOOK, array( $post_id ) );
		}
	}

	/* ===================== خروجی CSV ===================== */

	public function export_csv() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'sazan_consult_export' ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز.', 'sazan-core' ) );
		}
		$ids = get_posts( array(
			'post_type'   => Consult_CPT::POST_TYPE,
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=sazan-consult-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fprintf( $out, "\xEF\xBB\xBF" ); // BOM برای نمایش صحیح فارسی در اکسل
		$status = Consult_CPT::statuses();
		fputcsv( $out, array( 'تاریخ ثبت', 'نام', 'کسب‌وکار', 'حوزه', 'پرسنل', 'تماس', 'آدرس', 'تاریخ مشاوره', 'ساعت', 'وضعیت' ) );
		foreach ( $ids as $id ) {
			$st = get_post_meta( $id, '_sz_status', true ) ?: 'pending';
			fputcsv( $out, array(
				get_the_date( 'Y/m/d H:i', $id ),
				get_post_meta( $id, '_sz_name', true ),
				get_post_meta( $id, '_sz_business', true ),
				get_post_meta( $id, '_sz_field', true ),
				get_post_meta( $id, '_sz_staff', true ),
				get_post_meta( $id, '_sz_phone', true ),
				get_post_meta( $id, '_sz_address', true ),
				get_post_meta( $id, '_sz_jdate', true ),
				get_post_meta( $id, '_sz_time', true ),
				$status[ $st ] ?? $st,
			) );
		}
		fclose( $out );
		exit;
	}

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
			error_log( 'Sazan Consult SMS error: ' . $res->get_error_message() );
		}
	}

	private function fa_to_en( $str ) {
		$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		$ar = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
		$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		return str_replace( $ar, $en, str_replace( $fa, $en, (string) $str ) );
	}
}
