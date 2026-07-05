<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** تنظیمات «ارزیابی من»: هفته‌ی شروع، روزها، آستانه، واحد پول و پیامک فراز. */
class SZP_Eval_Settings {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 12 );
		add_action( 'admin_post_szp_eval_settings_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'wp_ajax_szp_eval_test_sms', array( __CLASS__, 'ajax_test_sms' ) );
	}

	public static function menu() {
		add_submenu_page( 'sazan-panel', 'تنظیمات ارزیابی', 'تنظیمات ارزیابی', 'manage_options', 'szp-eval-settings', array( __CLASS__, 'render' ) );
	}

	protected static function day_select( $name, $current ) {
		echo '<select name="' . esc_attr( $name ) . '">';
		for ( $i = 1; $i <= 7; $i++ ) {
			printf( '<option value="%d"%s>%s</option>', $i, selected( (int) $current, $i, false ), esc_html( SZP_Eval::day_name( $i ) ) );
		}
		echo '</select>';
	}

	public static function render() {
		$s = SZP_Eval::settings();
		?>
		<div class="wrap szp-groups-wrap">
			<h1>تنظیمات ارزیابی من</h1>
			<?php if ( isset( $_GET['msg'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'szp_eval_settings' ); ?>
				<input type="hidden" name="action" value="szp_eval_settings_save">

				<h2>عمومی</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label>هفته‌ی شروعِ کاربر</label></th>
						<td><input type="number" name="start_week" min="1" value="<?php echo esc_attr( $s['start_week'] ); ?>" class="small-text">
							<p class="description">اولین هفته‌ای که خودِ کاربر ثبت می‌کند (پیش‌فرض ۶). هفته‌های قبل را شما از صفحه «ارزیابی» وارد می‌کنید.</p></td>
					</tr>
					<tr>
						<th scope="row"><label>واحد پول</label></th>
						<td><input type="text" name="currency" value="<?php echo esc_attr( $s['currency'] ); ?>" class="regular-text"></td>
					</tr>
					<tr>
						<th scope="row"><label>آستانه «قابل بهبود»</label></th>
						<td><input type="number" name="near" min="1" max="99" value="<?php echo esc_attr( $s['near'] ); ?>" class="small-text">٪
							<p class="description">اگر تحقق از این درصد بیشتر (ولی زیر ۱۰۰٪) باشد «قابل بهبود»، در غیر این صورت «در مسیر». پیش‌فرض ۸۵.</p></td>
					</tr>
					<tr>
						<th scope="row">قفل روزِ ثبت</th>
						<td><label><input type="checkbox" name="enforce_days" value="1" <?php checked( ! empty( $s['enforce_days'] ) ); ?>> فعال باشد (ثبت تارگت/نتیجه فقط در روزهای تعیین‌شده مجاز است)</label>
							<p class="description">اگر تیک را بردارید، کاربران در <strong>هر روز</strong> می‌توانند تارگت و نتیجه ثبت کنند. (مدیران همیشه آزادند.)</p></td>
					</tr>
					<tr>
						<th scope="row"><label>روز ثبت تارگت</label></th>
						<td><?php self::day_select( 'day_target', $s['day_target'] ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label>روز ثبت نتیجه</label></th>
						<td><?php self::day_select( 'day_result', $s['day_result'] ); ?></td>
					</tr>
					<tr>
						<th scope="row">امروز از نظر سایت</th>
						<td><strong><?php echo esc_html( SZP_Eval::day_name( (int) wp_date( 'N' ) ) ); ?></strong>
							— <?php echo esc_html( szp_fa_digits( wp_date( 'Y-m-d H:i' ) ) ); ?>
							<p class="description">اگر این با روز واقعی فرق دارد، «منطقه زمانی» وردپرس (تنظیمات ← همگانی) را روی «تهران» بگذارید؛ قفلِ روز بر همین مبنا کار می‌کند.</p></td>
					</tr>
				</table>

				<h2>پیامک یادآوری (ایران‌پیامک / فراز اس‌ام‌اس)</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">فعال‌سازی</th>
						<td><label><input type="checkbox" name="sms_enabled" value="1" <?php checked( ! empty( $s['sms_enabled'] ) ); ?>> ارسال پیامک یادآوری در روز تارگت و روز نتیجه</label></td>
					</tr>
					<tr>
						<th scope="row"><label>آدرس پایه API</label></th>
						<td><input type="text" name="sms_base" value="<?php echo esc_attr( $s['sms_base'] ); ?>" class="regular-text" dir="ltr">
							<p class="description">پیش‌فرض ایران‌پیامک: <code>https://api.iranpayamak.com</code></p></td>
					</tr>
					<tr>
						<th scope="row"><label>کلید API (Api-Key)</label></th>
						<td><input type="text" name="sms_apikey" value="<?php echo esc_attr( $s['sms_apikey'] ); ?>" class="regular-text" dir="ltr" autocomplete="off">
							<p class="description">از پنل ایران‌پیامک، بخش «کلید وب‌سرویس / Api-Key».</p></td>
					</tr>
					<tr>
						<th scope="row"><label>خط ارسال (line_number)</label></th>
						<td><input type="text" name="sms_originator" value="<?php echo esc_attr( $s['sms_originator'] ); ?>" class="regular-text" dir="ltr" placeholder="2000... / 50002...">
							<p class="description">شماره خطی که در پنل ایران‌پیامک به شما اختصاص داده شده است.</p></td>
					</tr>
					<tr>
						<th scope="row"><label>متن یادآوری تارگت</label></th>
						<td><textarea name="sms_text_target" rows="2" class="large-text"><?php echo esc_textarea( $s['sms_text_target'] ); ?></textarea>
							<p class="description"><code>%name%</code> با نام کاربر جایگزین می‌شود.</p></td>
					</tr>
					<tr>
						<th scope="row"><label>متن یادآوری نتیجه</label></th>
						<td><textarea name="sms_text_result" rows="2" class="large-text"><?php echo esc_textarea( $s['sms_text_result'] ); ?></textarea></td>
					</tr>
				</table>
				<input type="hidden" name="sms_mode" value="text">
				<input type="hidden" name="sms_var" value="<?php echo esc_attr( $s['sms_var'] ); ?>">

				<p><button class="button button-primary">ذخیره تنظیمات</button></p>
			</form>

			<hr>
			<h2>تست پیامک</h2>
			<p>یک شماره موبایل وارد کنید تا پیامک یادآوریِ «روز تارگت» به‌صورت آزمایشی ارسال شود.</p>
			<p>
				<input type="text" id="szp-ev-testnum" class="regular-text" dir="ltr" placeholder="۰۹۱۲...">
				<button type="button" class="button szp-ev-testsms" data-nonce="<?php echo esc_attr( wp_create_nonce( 'szp_admin' ) ); ?>">ارسال پیامک تست</button>
				<span class="szp-ev-testmsg" style="margin-inline-start:8px;font-weight:600"></span>
			</p>
		</div>
		<?php
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_eval_settings' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$p   = wp_unslash( $_POST );
		$cur = SZP_Eval::settings();
		$new = array(
			'start_week'         => max( 1, absint( $p['start_week'] ?? 6 ) ),
			'currency'           => sanitize_text_field( $p['currency'] ?? 'تومان' ),
			'near'               => min( 99, max( 1, absint( $p['near'] ?? 85 ) ) ),
			'day_target'         => min( 7, max( 1, absint( $p['day_target'] ?? 2 ) ) ),
			'day_result'         => min( 7, max( 1, absint( $p['day_result'] ?? 1 ) ) ),
			'enforce_days'       => empty( $p['enforce_days'] ) ? 0 : 1,
			'sms_enabled'        => empty( $p['sms_enabled'] ) ? 0 : 1,
			'sms_base'           => esc_url_raw( $p['sms_base'] ?? '' ),
			'sms_apikey'         => sanitize_text_field( $p['sms_apikey'] ?? '' ),
			'sms_originator'     => sanitize_text_field( $p['sms_originator'] ?? '' ),
			'sms_mode'           => 'text',
			'sms_var'            => sanitize_key( $p['sms_var'] ?? 'name' ),
			'sms_text_target'    => sanitize_textarea_field( $p['sms_text_target'] ?? '' ),
			'sms_text_result'    => sanitize_textarea_field( $p['sms_text_result'] ?? '' ),
		);
		update_option( SZP_Eval::OPTION, array_merge( $cur, $new ) );
		wp_safe_redirect( add_query_arg( array( 'page' => 'szp-eval-settings', 'msg' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function ajax_test_sms() {
		check_ajax_referer( 'szp_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'msg' => 'دسترسی غیرمجاز' ) );
		}
		if ( ! SZP_SMS::enabled() ) {
			wp_send_json_error( array( 'msg' => 'سرویس پیامک فعال نیست یا کلید/خط ارسال خالی است. ابتدا ذخیره کنید.' ) );
		}
		$to = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';
		if ( szp_normalize_mobile( $to ) === '' ) {
			wp_send_json_error( array( 'msg' => 'شماره موبایل معتبر وارد کنید.' ) );
		}
		$res = SZP_SMS::send_reminder( $to, 'target', wp_get_current_user()->display_name );
		if ( ! empty( $res['ok'] ) ) {
			wp_send_json_success( array( 'msg' => 'پیامک تست ارسال شد ✓' ) );
		}
		wp_send_json_error( array( 'msg' => $res['msg'] ?? 'ارسال ناموفق بود.' ) );
	}
}
