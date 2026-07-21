<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** تنظیمات «ارزیابی»: تقویم جلسات، واحد پول، آستانه، و پیامک (sms.ir/فراز). */
class SZP_Eval_Settings {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 12 );
		add_action( 'admin_post_szp_eval_settings_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'wp_ajax_szp_eval_test_sms', array( __CLASS__, 'ajax_test_sms' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function menu() {
		add_submenu_page( 'sazan-panel', 'تنظیمات ارزیابی', 'تنظیمات ارزیابی', 'manage_options', 'szp-eval-settings', array( __CLASS__, 'render' ) );
	}

	public static function assets( $hook ) {
		if ( strpos( (string) $hook, 'szp-eval-settings' ) === false ) {
			return;
		}
		$css = SZP_DIR . 'assets/css/sazan-eval-settings.css';
		$js  = SZP_DIR . 'assets/js/sazan-eval-settings.js';
		wp_enqueue_style( 'szp-eval-settings', SZP_URL . 'assets/css/sazan-eval-settings.css', array(), file_exists( $css ) ? filemtime( $css ) : SZP_VERSION );
		wp_enqueue_script( 'szp-eval-settings', SZP_URL . 'assets/js/sazan-eval-settings.js', array(), file_exists( $js ) ? filemtime( $js ) : SZP_VERSION, true );
	}

	/* ==================== کمکی‌های تاریخ شمسی ==================== */

	protected static function months() {
		return array( '', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
	}

	/** برچسب شمسیِ یک تاریخ میلادی: «سه‌شنبه ۲ مرداد ۱۴۰۴». */
	protected static function fa_date_label( DateTime $d ) {
		list( $jy, $jm, $jd ) = szp_g2j( (int) $d->format( 'Y' ), (int) $d->format( 'n' ), (int) $d->format( 'j' ) );
		$m   = self::months();
		$dow = SZP_Eval::day_name( (int) $d->format( 'N' ) );
		return szp_fa_digits( $dow . ' ' . $jd . ' ' . $m[ $jm ] . ' ' . $jy );
	}

	/** نزدیک‌ترین سه‌شنبه (امروز/قبل‌تر) به‌صورت Y-m-d. */
	protected static function nearest_tuesday() {
		$now  = new DateTime( 'now', wp_timezone() );
		$dow  = (int) $now->format( 'N' );
		$back = ( $dow - 2 + 7 ) % 7;
		if ( $back ) {
			$now->modify( '-' . $back . ' days' );
		}
		return $now->format( 'Y-m-d' );
	}

	/** فهرست سه‌شنبه‌ها (حدود ۲۰ هفته قبل تا ۲۰ هفته بعد) با برچسب شمسی: [ymd => label]. */
	protected static function tuesday_options( $current = '' ) {
		$out   = array();
		$start = new DateTime( self::nearest_tuesday() . ' 12:00:00', wp_timezone() );
		$start->modify( '-20 weeks' );
		for ( $i = 0; $i < 41; $i++ ) {
			$d = clone $start;
			if ( $i ) {
				$d->modify( '+' . ( $i * 7 ) . ' days' );
			}
			$out[ $d->format( 'Y-m-d' ) ] = self::fa_date_label( $d );
		}
		if ( $current !== '' && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $current ) && ! isset( $out[ $current ] ) ) {
			try {
				$d              = new DateTime( $current . ' 12:00:00', wp_timezone() );
				$out[ $current ] = self::fa_date_label( $d );
				ksort( $out );
			} catch ( Exception $e ) {} // phpcs:ignore
		}
		return $out;
	}

	/* ==================== سازنده‌های فیلد ==================== */

	protected static function f( $label, $ctrl, $hint = '' ) {
		echo '<div class="szp-f"><div class="szp-f-lbl">' . $label . '</div><div class="szp-f-ctrl">' . $ctrl // phpcs:ignore WordPress.Security.EscapeOutput
			. ( $hint !== '' ? '<p class="szp-f-hint">' . $hint . '</p>' : '' ) . '</div></div>';
	}

	protected static function inp( $name, $val, $type = 'text', $extra = '' ) {
		return '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '" ' . $extra . '>';
	}

	protected static function ta( $name, $val, $rows = 2, $extra = '' ) {
		return '<textarea name="' . esc_attr( $name ) . '" rows="' . (int) $rows . '" ' . $extra . '>' . esc_textarea( (string) $val ) . '</textarea>';
	}

	protected static function switch_ctrl( $name, $checked, $label ) {
		return '<label class="szp-switch"><input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( (bool) $checked, true, false )
			. '><span class="track"></span><span>' . esc_html( $label ) . '</span></label>';
	}

	protected static function select_ctrl( $name, $options, $current, $extra = '' ) {
		$html = '<select name="' . esc_attr( $name ) . '" ' . $extra . '>';
		foreach ( $options as $val => $label ) {
			$html .= '<option value="' . esc_attr( $val ) . '" ' . selected( (string) $current, (string) $val, false ) . '>' . esc_html( $label ) . '</option>';
		}
		return $html . '</select>';
	}

	/* ==================== رندر ==================== */

	public static function render() {
		$s        = SZP_Eval::settings();
		$cur_no   = SZP_Eval::current_session();
		$an_no    = (int) $s['anchor_session'] >= 1 ? (int) $s['anchor_session'] : $cur_no;
		$an_date  = ( ! empty( $s['anchor_date'] ) ) ? $s['anchor_date'] : self::nearest_tuesday();
		$provider = $s['sms_provider'];
		?>
		<div class="wrap">
			<div class="szp-set">
				<?php if ( isset( $_GET['msg'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
					<div class="notice notice-success is-dismissible" style="margin:0 0 14px"><p>✓ تنظیمات ذخیره شد.</p></div>
				<?php endif; ?>

				<div class="szp-set-hero">
					<h1>⚙️ تنظیمات ارزیابی</h1>
					<p>همه‌چیز مرتب و دسته‌بندی‌شده. از تب‌های زیر بخش موردنظرت را باز کن.</p>
					<div class="szp-set-badge">📅 هم‌اکنون: <?php echo esc_html( SZP_Eval::session_label( $cur_no ) . ' — ' . SZP_Eval::session_date_fa( $cur_no, true ) ); ?></div>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'szp_eval_settings' ); ?>
					<input type="hidden" name="action" value="szp_eval_settings_save">

					<div class="szp-set-tabs">
						<button type="button" class="szp-set-tab" data-tab="sessions">⏱ جلسات و تارگت</button>
						<button type="button" class="szp-set-tab" data-tab="sms">📱 اتصال پیامک</button>
						<button type="button" class="szp-set-tab" data-tab="notify">🔔 اطلاع‌رسانی و یادآوری</button>
						<button type="button" class="szp-set-tab" data-tab="coaching">🎓 جلسات کوچینگ</button>
						<button type="button" class="szp-set-tab" data-tab="access">🔑 دسترسی</button>
					</div>

					<!-- ============ تب جلسات و تارگت ============ -->
					<div class="szp-set-panel" data-panel="sessions">
						<div class="szp-set-card">
							<h2>📅 تقویم جلسات</h2>
							<p class="szp-set-sub">فقط بگو «الان جلسه چند هستیم» و «این جلسه کدام سه‌شنبه است». بقیه‌ی جلسات خودکار محاسبه می‌شوند — همه‌ی تاریخ‌ها شمسی‌اند.</p>

							<?php
							self::f(
								'الان جلسه چند هستیم؟',
								'<div class="szp-set-inline">' . self::inp( 'anchor_session', $an_no, 'number', 'class="small" min="1"' ) . '<span class="szp-set-suffix">اُمین جلسه‌ی دوره</span></div>',
								'مثلاً اگر تازه از جلسه‌ی ۹ می‌خواهید شروع کنید، بنویسید <b>۹</b>.'
							);
							self::f(
								'تاریخِ همین جلسه (سه‌شنبه)',
								self::select_ctrl( 'anchor_date', self::tuesday_options( $an_date ), $an_date ),
								'سه‌شنبه‌ای که همین جلسه (' . esc_html( self::to_fa( $an_no ) ) . 'اُم) در آن برگزار می‌شود را انتخاب کنید.'
							);
							?>
							<div class="szp-set-datehint">
								با این تنظیم: <b><?php echo esc_html( SZP_Eval::session_label( $cur_no ) ); ?></b> جاری است.
								ثبت نتیجه‌ی هر جلسه، <b>دوشنبه و سه‌شنبه‌ی هفته‌ی بعد</b> باز می‌شود.
							</div>
						</div>

						<div class="szp-set-card">
							<h2>🎯 تارگت</h2>
							<?php
							self::f( 'واحد پول', self::inp( 'currency', $s['currency'], 'text' ), 'مثلاً «تومان».' );
							self::f(
								'آستانه‌ی «قابل بهبود»',
								'<div class="szp-set-inline">' . self::inp( 'near', $s['near'], 'number', 'class="small" min="1" max="99"' ) . '<span class="szp-set-suffix">درصد</span></div>',
								'اگر تحققِ نتیجه از این درصد بیشتر (ولی زیر ۱۰۰٪) باشد وضعیت «قابل بهبود» می‌شود، وگرنه «در مسیر». پیش‌فرض ۸۵.'
							);
							?>
						</div>
					</div>

					<!-- ============ تب اتصال پیامک ============ -->
					<div class="szp-set-panel" data-panel="sms">
						<div class="szp-set-card">
							<h2>📱 اتصال سرویس پیامک</h2>
							<p class="szp-set-sub">یک‌بار این‌جا را پر کن؛ همه‌ی پیامک‌ها (یادآوری، اطلاع‌رسانی، جلسات) از همین اتصال استفاده می‌کنند.</p>
							<?php
							self::f(
								'سرویس‌دهنده',
								self::select_ctrl( 'sms_provider', array( 'smsir' => 'اس‌ام‌اس‌دات‌آی‌آر (sms.ir)', 'ippanel' => 'فراز / آی‌پی‌پنل (ippanel)' ), $provider ),
								'برای <b>sms.ir</b>: «کلید API» همان API Key پنل، «خط ارسال» شماره‌ی خط، و «کد پترن» همان <code>templateId</code> عددی است. آدرس پایه را خالی بگذارید.'
							);
							self::f( 'کلید API', self::inp( 'sms_apikey', $s['sms_apikey'], 'text', 'dir="ltr" autocomplete="off"' ) );
							self::f( 'خط ارسال (Originator)', self::inp( 'sms_originator', $s['sms_originator'], 'text', 'dir="ltr" placeholder="30002108..."' ) );
							self::f( 'آدرس پایه API (اختیاری)', self::inp( 'sms_base', $s['sms_base'], 'text', 'dir="ltr" placeholder="برای sms.ir خالی بگذارید"' ), 'فراز/آی‌پی‌پنل: <code>https://rest.ippanel.com/v1</code> — برای sms.ir خالی.' );
							self::f(
								'حالت ارسال',
								self::select_ctrl( 'sms_mode', array( 'pattern' => 'پترن (پیامک خدماتی)', 'text' => 'متن آزاد' ), $s['sms_mode'] ),
								'برای پیامک خدماتی در ایران معمولاً «پترن» با کد مصوب لازم است.'
							);
							self::f( 'نام متغیر پترن برای نام کاربر', self::inp( 'sms_var', $s['sms_var'], 'text', 'dir="ltr" placeholder="name"' ), 'نام متغیری که در پترن برای «نام کاربر» تعریف کرده‌اید.' );
							?>
						</div>

						<div class="szp-set-card">
							<h2>🧪 تست پیامک</h2>
							<p class="szp-set-sub">یک شماره وارد کن تا یک پیامک آزمایشی (یادآوری تارگت) فرستاده شود. اول تنظیمات را ذخیره کن.</p>
							<div class="szp-set-test">
								<input type="text" id="szp-ev-testnum" dir="ltr" placeholder="۰۹۱۲...">
								<button type="button" class="button button-primary szp-ev-testsms" data-nonce="<?php echo esc_attr( wp_create_nonce( 'szp_admin' ) ); ?>">ارسال پیامک تست</button>
								<span class="szp-ev-testmsg"></span>
							</div>
						</div>
					</div>

					<!-- ============ تب اطلاع‌رسانی و یادآوری ============ -->
					<div class="szp-set-panel" data-panel="notify">
						<div class="szp-set-card">
							<h2>🔔 اطلاع‌رسانی ثبت تارگت/نتیجه</h2>
							<p class="szp-set-sub">وقتی «تیم فروش» تارگت یا نتیجه ثبت می‌کند، برای <b>مدیر سازمانش و همه‌ی کوچ‌ها</b> پیامک می‌رود. متغیرها: <code>%name%</code> <code>%session%</code> <code>%amount%</code> <code>%status%</code>.</p>
							<?php
							self::f( 'وضعیت', self::switch_ctrl( 'notify_enabled', ! empty( $s['notify_enabled'] ), 'ارسال پیامک هنگام ثبت تارگت/نتیجه‌ی تیم فروش' ) );
							self::f( 'کد پترن اطلاع‌رسانی تارگت', self::inp( 'sms_pattern_notify_target', $s['sms_pattern_notify_target'] ?? '', 'text', 'dir="ltr"' ) );
							self::f( 'کد پترن اطلاع‌رسانی نتیجه', self::inp( 'sms_pattern_notify_result', $s['sms_pattern_notify_result'] ?? '', 'text', 'dir="ltr"' ) );
							self::f( 'متن اطلاع‌رسانی تارگت (حالت متن)', self::ta( 'sms_text_notify_target', $s['sms_text_notify_target'] ?? '', 2 ) );
							self::f( 'متن اطلاع‌رسانی نتیجه (حالت متن)', self::ta( 'sms_text_notify_result', $s['sms_text_notify_result'] ?? '', 2 ) );
							?>
						</div>

						<div class="szp-set-card">
							<h2>⏰ یادآوری خودکار به تیم فروش</h2>
							<p class="szp-set-sub">در روز تارگت و روز نتیجه، یک یادآوری برای خود فرد فرستاده می‌شود. متغیر: <code>%name%</code>.</p>
							<?php
							self::f( 'وضعیت', self::switch_ctrl( 'sms_enabled', ! empty( $s['sms_enabled'] ), 'ارسال پیامک یادآوری در روز تارگت و روز نتیجه' ) );
							self::f( 'کد پترن روز تارگت', self::inp( 'sms_pattern_target', $s['sms_pattern_target'], 'text', 'dir="ltr"' ) );
							self::f( 'کد پترن روز نتیجه', self::inp( 'sms_pattern_result', $s['sms_pattern_result'], 'text', 'dir="ltr"' ) );
							self::f( 'متن یادآوری تارگت (حالت متن)', self::ta( 'sms_text_target', $s['sms_text_target'], 2 ) );
							self::f( 'متن یادآوری نتیجه (حالت متن)', self::ta( 'sms_text_result', $s['sms_text_result'], 2 ) );
							?>
						</div>
					</div>

					<!-- ============ تب جلسات کوچینگ ============ -->
					<div class="szp-set-panel" data-panel="coaching">
						<div class="szp-set-card">
							<h2>🎓 جلسات کوچینگ (ثبت جلسه و نظرسنجی)</h2>
							<p class="szp-set-sub">مربوط به ویجت «زمان‌بندی جلسات کوچینگ». از همان اتصال پیامک بالا استفاده می‌شود.</p>
							<?php
							self::f( 'لینک پیش‌فرض نظرسنجی', self::inp( 'survey_url', $s['survey_url'], 'url', 'dir="ltr" placeholder="https://..."' ), 'شناسه‌ی جلسه به‌صورت <code>?szp_session=ID</code> افزوده می‌شود.' );
							self::f(
								'فاصله‌ی ارسال نظرسنجی',
								'<div class="szp-set-inline">' . self::inp( 'survey_delay', $s['survey_delay'], 'number', 'class="small" min="1"' ) . '<span class="szp-set-suffix">دقیقه پس از پایان جلسه</span></div>',
								'پیش‌فرض ۶۰ دقیقه.'
							);
							self::f( 'کد پترن «ثبت جلسه»', self::inp( 'sms_pattern_session', $s['sms_pattern_session'], 'text', 'dir="ltr"' ), 'متغیرها: <code>name</code> <code>title</code> <code>date</code> <code>time</code> <code>coach</code> <code>mentor</code>.' );
							self::f( 'کد پترن «نظرسنجی»', self::inp( 'sms_pattern_survey', $s['sms_pattern_survey'], 'text', 'dir="ltr"' ), 'متغیرها: <code>name</code> <code>link</code> <code>coach</code>.' );
							self::f( 'متن «ثبت جلسه» (حالت متن)', self::ta( 'sms_text_session', $s['sms_text_session'], 2 ) );
							self::f( 'متن «نظرسنجی» (حالت متن)', self::ta( 'sms_text_survey', $s['sms_text_survey'], 2 ) );
							?>
						</div>
					</div>

					<!-- ============ تب دسترسی ============ -->
					<div class="szp-set-panel" data-panel="access">
						<div class="szp-set-card">
							<h2>🔑 دسترسی و کوچینگ</h2>
							<p class="szp-set-sub">نقش‌ها و تیم‌ها را بهتر است از پنل «<b>نقش‌ها و تیم‌ها</b>» مدیریت کنید. این‌جا فقط لیست‌های تکمیلی است.</p>
							<?php
							self::f(
								'کاربران «کوچینگ» (لیست تکمیلی)',
								self::ta( 'coaching_users', $s['coaching_users'] ?? '', 2, 'dir="ltr" placeholder="username, user@mail.com, 42"' ),
								'این افراد هم نقش کوچینگ می‌گیرند و همه‌ی نفرات را می‌بینند. نام‌کاربری/ایمیل/شناسه، جدا با کاما.'
							);
							self::f(
								'کاربران مجاز به مشاهده‌ی تابلو',
								self::ta( 'board_viewers', $s['board_viewers'] ?? '', 2, 'dir="ltr" placeholder="username, user@mail.com, 42"' ),
								'(اختیاری) افراد اضافه‌ای که «تابلوی ارزیابی» و «دفتر ارزیابی» را می‌بینند — علاوه بر مدیران، کوچینگ و مدیران سازمان.'
							);
							?>
						</div>
					</div>

					<div class="szp-set-save">
						<button type="submit">💾 ذخیره‌ی تنظیمات</button>
						<span>تغییرات هر پنج تب با یک بار ذخیره اعمال می‌شوند.</span>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	protected static function to_fa( $n ) {
		return szp_fa_digits( (int) $n );
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'szp_eval_settings' ) ) {
			wp_die( 'دسترسی غیرمجاز' );
		}
		$p   = wp_unslash( $_POST );
		$cur = SZP_Eval::settings();

		$an_no   = max( 1, absint( $p['anchor_session'] ?? 1 ) );
		$an_date = trim( (string) ( $p['anchor_date'] ?? '' ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $an_date ) ) {
			$an_date = '';
		}

		$new = array(
			'anchor_session'            => $an_date !== '' ? $an_no : 0,
			'anchor_date'               => $an_date,
			'currency'                  => sanitize_text_field( $p['currency'] ?? 'تومان' ),
			'near'                      => min( 99, max( 1, absint( $p['near'] ?? 85 ) ) ),
			'coaching_users'            => sanitize_textarea_field( $p['coaching_users'] ?? '' ),
			'board_viewers'             => sanitize_textarea_field( $p['board_viewers'] ?? '' ),
			'notify_enabled'            => empty( $p['notify_enabled'] ) ? 0 : 1,
			'sms_pattern_notify_target' => sanitize_text_field( $p['sms_pattern_notify_target'] ?? '' ),
			'sms_pattern_notify_result' => sanitize_text_field( $p['sms_pattern_notify_result'] ?? '' ),
			'sms_text_notify_target'    => sanitize_textarea_field( $p['sms_text_notify_target'] ?? '' ),
			'sms_text_notify_result'    => sanitize_textarea_field( $p['sms_text_notify_result'] ?? '' ),
			'sms_enabled'               => empty( $p['sms_enabled'] ) ? 0 : 1,
			'sms_provider'              => ( ( $p['sms_provider'] ?? 'smsir' ) === 'ippanel' ) ? 'ippanel' : 'smsir',
			'sms_base'                  => esc_url_raw( $p['sms_base'] ?? '' ),
			'sms_apikey'                => sanitize_text_field( $p['sms_apikey'] ?? '' ),
			'sms_originator'            => sanitize_text_field( $p['sms_originator'] ?? '' ),
			'sms_mode'                  => ( ( $p['sms_mode'] ?? 'pattern' ) === 'text' ) ? 'text' : 'pattern',
			'sms_var'                   => sanitize_key( $p['sms_var'] ?? 'name' ),
			'sms_pattern_target'        => sanitize_text_field( $p['sms_pattern_target'] ?? '' ),
			'sms_pattern_result'        => sanitize_text_field( $p['sms_pattern_result'] ?? '' ),
			'sms_text_target'           => sanitize_textarea_field( $p['sms_text_target'] ?? '' ),
			'sms_text_result'           => sanitize_textarea_field( $p['sms_text_result'] ?? '' ),
			'sms_pattern_session'       => sanitize_text_field( $p['sms_pattern_session'] ?? '' ),
			'sms_pattern_survey'        => sanitize_text_field( $p['sms_pattern_survey'] ?? '' ),
			'sms_text_session'          => sanitize_textarea_field( $p['sms_text_session'] ?? '' ),
			'sms_text_survey'           => sanitize_textarea_field( $p['sms_text_survey'] ?? '' ),
			'survey_url'                => esc_url_raw( $p['survey_url'] ?? '' ),
			'survey_delay'              => max( 1, absint( $p['survey_delay'] ?? 60 ) ),
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
