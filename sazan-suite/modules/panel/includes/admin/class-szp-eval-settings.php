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
		add_submenu_page( 'sazan-panel', 'مرکز تنظیمات سازان', 'تنظیمات سازان', 'manage_options', 'szp-eval-settings', array( __CLASS__, 'render' ) );
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
		$label_html = '<div class="szp-f-lbl">' . $label . '</div>';
		if ( strpos( $ctrl, 'class="szp-switch"' ) === false && preg_match( '/\sid="([^"]+)"/', $ctrl, $match ) ) {
			$label_html = '<label class="szp-f-lbl" for="' . esc_attr( $match[1] ) . '">' . $label . '</label>';
		}
		echo '<div class="szp-f">' . $label_html . '<div class="szp-f-ctrl">' . $ctrl // phpcs:ignore WordPress.Security.EscapeOutput
			. ( $hint !== '' ? '<p class="szp-f-hint">' . $hint . '</p>' : '' ) . '</div></div>';
	}

	protected static function inp( $name, $val, $type = 'text', $extra = '' ) {
		$id = 'szp-setting-' . sanitize_html_class( str_replace( array( '[', ']' ), array( '-', '' ), $name ) );
		return '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $val ) . '" ' . $extra . '>';
	}

	protected static function ta( $name, $val, $rows = 2, $extra = '' ) {
		$id = 'szp-setting-' . sanitize_html_class( str_replace( array( '[', ']' ), array( '-', '' ), $name ) );
		return '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" rows="' . (int) $rows . '" ' . $extra . '>' . esc_textarea( (string) $val ) . '</textarea>';
	}

	protected static function switch_ctrl( $name, $checked, $label ) {
		$id = 'szp-setting-' . sanitize_html_class( str_replace( array( '[', ']' ), array( '-', '' ), $name ) );
		return '<label class="szp-switch" for="' . esc_attr( $id ) . '"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1" ' . checked( (bool) $checked, true, false )
			. '><span class="track"></span><span>' . esc_html( $label ) . '</span></label>';
	}

	protected static function select_ctrl( $name, $options, $current, $extra = '' ) {
		$id   = 'szp-setting-' . sanitize_html_class( str_replace( array( '[', ']' ), array( '-', '' ), $name ) );
		$html = '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" ' . $extra . '>';
		foreach ( $options as $val => $label ) {
			$html .= '<option value="' . esc_attr( $val ) . '" ' . selected( (string) $current, (string) $val, false ) . '>' . esc_html( $label ) . '</option>';
		}
		return $html . '</select>';
	}

	/* ==================== رندر ==================== */

	public static function render() {
		self::render_center();
		return;

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
							<?php
							self::f(
								'قفل زمانی ثبت نتیجه',
								self::switch_ctrl( 'result_lock_enabled', ! empty( $s['result_lock_enabled'] ), 'ثبت نتیجه فقط دوشنبه و سه‌شنبه‌ی هفته بعد امکان‌پذیر باشد' ),
								'اگر این گزینه را خاموش کنید، فرم نتیجه‌ی هفته قبل در تمام روزها تا زمان ثبت در دسترس کاربر می‌ماند. خاموش‌کردن این قفل هیچ اطلاعاتی را حذف نمی‌کند.'
							);
							?>
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

	/**
	 * مرکز تنظیمات ساده و جامع.
	 * گزینه‌های روزمره در سطح اول و گزینه‌های تخصصی داخل details قرار گرفته‌اند.
	 */
	protected static function render_center() {
		$s        = SZP_Eval::settings();
		$ui       = SZP_Settings::all();
		$cur_no   = SZP_Eval::current_session();
		$an_no    = (int) $s['anchor_session'] >= 1 ? (int) $s['anchor_session'] : $cur_no;
		$an_date  = ! empty( $s['anchor_date'] ) ? $s['anchor_date'] : self::nearest_tuesday();
		$provider = $s['sms_provider'];
		$ai_key   = (string) get_option( SZP_AI::OPT_KEY, '' );
		$ai_model = SZP_AI::model();
		$ai_base  = SZP_AI::base();

		$content_groups = array(
			'رابط عمومی و فهرست‌ها' => array(
				'brand_name'       => 'نام افزونه در رابط',
				'login_message'    => 'پیام نیاز به ورود',
				'forbidden_message'=> 'پیام نداشتن دسترسی',
				'courses_title'    => 'عنوان فهرست دوره‌ها',
				'courses_empty'    => 'پیام خالی‌بودن دوره‌ها',
				'chat_title'       => 'عنوان اتاق گفتگو',
				'chat_intro'       => 'راهنمای اتاق گفتگو',
				'chat_empty'       => 'پیام خالی‌بودن گفتگو',
				'back_courses'     => 'متن بازگشت به دوره‌ها',
				'back_course'      => 'متن بازگشت به دوره',
			),
			'داشبورد رشد و کوچینگ' => array(
				'growth_title'         => 'عنوان اصلی',
				'growth_subtitle'      => 'زیرعنوان',
				'growth_today_title'   => 'عنوان قدم بعدی',
				'growth_target_title'  => 'عنوان مرحله تارگت',
				'growth_result_title'  => 'عنوان مرحله نتیجه',
				'growth_target_button' => 'متن دکمه ثبت تارگت',
				'growth_result_button' => 'متن دکمه ثبت نتیجه',
				'growth_history_title' => 'عنوان سوابق',
				'growth_pending_label' => 'برچسب نتیجه ثبت‌نشده',
			),
			'داشبورد وضعیت و ویزارد' => array(
				'dashboard_kicker' => 'برچسب بالای داشبورد',
				'dashboard_title' => 'عنوان داشبورد فعالیت',
				'dashboard_current_session' => 'برچسب جلسه جاری',
				'dashboard_status_label' => 'برچسب وضعیت فعلی',
				'dashboard_profile_status' => 'وضعیت اطلاعات پایه',
				'dashboard_profile_detail' => 'زیرعنوان اطلاعات پایه',
				'dashboard_profile_button' => 'دکمه اطلاعات پایه',
				'dashboard_missing_result_status' => 'وضعیت نتیجه ثبت‌نشده',
				'dashboard_missing_result_button' => 'دکمه نتیجه عقب‌افتاده',
				'dashboard_result_status' => 'وضعیت زمان ثبت نتیجه',
				'dashboard_result_button' => 'دکمه ثبت نتیجه',
				'dashboard_target_status' => 'وضعیت زمان ثبت تارگت',
				'dashboard_target_button' => 'دکمه ثبت تارگت',
				'dashboard_overdue_status' => 'وضعیت ثبت‌های ناقص',
				'dashboard_overdue_button' => 'دکمه ثبت‌های ناقص',
				'dashboard_overdue_label' => 'برچسب تعداد ثبت ناقص',
				'dashboard_overdue_count_unit' => 'واحد تعداد ثبت ناقص',
				'dashboard_wait_result_status' => 'وضعیت انتظار نتیجه',
				'dashboard_wait_result_button' => 'دکمه انتظار نتیجه',
				'dashboard_result_countdown' => 'عنوان شمارش معکوس نتیجه',
				'dashboard_wait_target_status' => 'وضعیت انتظار تارگت',
				'dashboard_wait_target_button' => 'دکمه انتظار تارگت',
				'dashboard_target_countdown' => 'عنوان شمارش معکوس تارگت',
				'wizard_step_label' => 'برچسب مرحله ویزارد',
				'wizard_close_label' => 'عنوان دکمه بستن ویزارد',
				'wizard_previous_button' => 'دکمه مرحله قبل',
				'wizard_next_button' => 'دکمه مرحله بعد',
			),
			'اطلاعات پایه دانشجو' => array(
				'profile_title' => 'عنوان اطلاعات پایه', 'profile_incomplete_hint' => 'راهنمای تکمیل اولیه',
				'profile_complete_label' => 'برچسب تکمیل‌شده', 'profile_required_label' => 'برچسب نیازمند تکمیل',
				'profile_full_name_label' => 'عنوان نام و نام خانوادگی', 'profile_business_label' => 'عنوان نام کسب‌وکار',
				'profile_position_label' => 'عنوان سمت', 'profile_position_hint' => 'نمونه سمت',
				'profile_industry_label' => 'عنوان حوزه فعالیت', 'profile_industry_hint' => 'نمونه حوزه فعالیت',
				'profile_save_button' => 'متن دکمه ذخیره', 'profile_required_callout' => 'پیام تکمیل اطلاعات پایه',
			),
			'راهنمای فرم هفتگی و سوابق' => array(
				'growth_schedule_monday' => 'متن برنامه دوشنبه',
				'growth_schedule_tuesday' => 'متن برنامه سه‌شنبه',
				'growth_result_ready_label' => 'عنوان نتیجه آماده ثبت',
				'growth_result_ready_button' => 'دکمه بالای ثبت نتیجه',
				'growth_result_waiting_label' => 'عنوان شمارش معکوس',
				'growth_countdown_days' => 'برچسب روز',
				'growth_countdown_hours' => 'برچسب ساعت',
				'growth_countdown_minutes' => 'برچسب دقیقه',
				'target_saved_text' => 'پیام تارگت ثبت‌شده',
				'target_action_help' => 'راهنمای نوشتن اقدام', 'target_action_label' => 'عنوان هر اقدام',
				'target_action_placeholder' => 'نمونه توضیح اقدام', 'target_action_remove' => 'دکمه حذف اقدام',
				'target_action_add' => 'دکمه افزودن اقدام', 'target_locked_title' => 'عنوان قفل تارگت',
				'target_locked_text' => 'توضیح قفل تارگت', 'result_saved_text' => 'پیام گزارش ثبت‌شده',
				'result_locked_title' => 'عنوان قفل نتیجه',
				'result_preserved_text' => 'پیام حفظ نتیجه قدیمی', 'select_placeholder' => 'متن انتخاب‌گر',
			),
			'خلاصه، تیم و کوچینگ' => array(
				'mobile_quick_title' => 'عنوان دید سریع', 'mobile_quick_summary' => 'گزینه خلاصه من',
				'mobile_quick_history' => 'گزینه هفته‌های قبل', 'mobile_quick_team' => 'گزینه تیم من',
				'mobile_quick_coaching' => 'گزینه کوچینگ', 'mobile_quick_members' => 'گزینه اعضای تیم',
				'mobile_quick_members_hint' => 'توضیح گزینه اعضای تیم',
				'dashboard_current_actions_label' => 'عنوان ثبت‌های در دسترس',
				'dashboard_current_result_button' => 'دکمه ثبت نتیجه هفته قبل',
				'dashboard_current_target_button' => 'دکمه ثبت تارگت این هفته',
				'summary_target_label' => 'برچسب تعداد تارگت', 'summary_result_label' => 'برچسب تعداد نتیجه',
				'summary_average_label' => 'برچسب میانگین', 'history_empty_title' => 'عنوان نبود سابقه',
				'history_empty_text' => 'توضیح نبود سابقه', 'history_target_label' => 'برچسب هدف مالی',
				'completed_history_title' => 'عنوان هفته‌های تکمیل‌شده',
				'completed_history_empty_title' => 'عنوان نبود هفته کامل',
				'completed_history_empty_text' => 'توضیح نبود هفته کامل',
				'history_actual_label' => 'برچسب فروش واقعی', 'history_not_registered' => 'برچسب ثبت‌نشده',
				'team_coach_kicker' => 'برچسب نمای کوچ', 'team_manager_kicker' => 'برچسب نمای مدیر',
				'team_coach_title' => 'عنوان تیم‌های سازمانی کوچ', 'team_manager_title' => 'عنوان زیرمجموعه مدیر',
				'team_empty_title' => 'عنوان نبود عضو', 'team_empty_text' => 'توضیح نبود عضو',
				'coaching_kicker' => 'برچسب کوچینگ', 'coaching_title' => 'عنوان کوچینگ',
				'coaching_admin_help' => 'راهنمای مدیریت کوچینگ',
			),
			'وضعیت‌های نتیجه' => array(
				'status_beyond'  => 'بیشتر از هدف',
				'status_success' => 'تحقق کامل هدف',
				'status_improve' => 'نزدیک به هدف',
				'status_ontrack' => 'کمتر از هدف',
			),
			'پرسش‌های ثبت تارگت' => array(
				'target_q1' => 'پرسش ۱', 'target_q2' => 'پرسش ۲', 'target_q3' => 'پرسش ۳', 'target_q4' => 'پرسش ۴',
				'target_q5' => 'پرسش ۵', 'target_q6' => 'پرسش ۶', 'target_q7' => 'پرسش ۷', 'target_q8' => 'پرسش ۸',
			),
			'پرسش‌های ثبت نتیجه' => array(
				'result_q1' => 'پرسش ۱', 'result_q2' => 'پرسش ۲', 'result_q3' => 'پرسش ۳', 'result_q4' => 'پرسش ۴',
				'result_q5' => 'پرسش ۵', 'result_q6' => 'پرسش ۶', 'result_q7' => 'پرسش ۷',
			),
			'عنوان‌های صفحه دوره' => array(
				'course_identity_title' => 'شناسنامه دوره', 'course_outline_title' => 'سرفصل‌ها',
				'course_goals_title' => 'اهداف', 'course_schedule_title' => 'زمان‌بندی',
				'course_schedule_hint' => 'راهنمای زمان‌بندی', 'course_next_session' => 'جلسه بعدی',
				'course_past_sessions' => 'جلسات گذشته', 'course_no_sessions' => 'پیام نبود جلسه',
				'course_announcements' => 'اعلانات', 'course_files' => 'فایل‌ها', 'course_workbench' => 'تمرین‌ها',
				'course_group' => 'گروه', 'course_survey' => 'نظرسنجی', 'show_more' => 'دکمه مشاهده بیشتر',
			),
			'عنوان‌های صفحه جلسه' => array(
				'session_identity_title' => 'شناسنامه جلسه', 'session_goals_title' => 'اهداف جلسه',
				'session_pack_title' => 'محتوای جلسه', 'session_assignment_title' => 'تکلیف',
				'session_checklist_title' => 'چک‌لیست', 'session_survey_title' => 'نظرسنجی',
				'submit_answer' => 'دکمه ارسال پاسخ', 'update_answer' => 'دکمه ویرایش پاسخ',
				'submit_feedback' => 'دکمه ثبت نظر',
				'course_table_session' => 'ستون جلسه', 'course_table_deadline' => 'ستون ددلاین',
				'course_table_status' => 'ستون وضعیت', 'course_status_sent' => 'وضعیت ارسال‌شده',
				'course_status_pending' => 'وضعیت ارسال‌نشده', 'session_play_label' => 'برچسب پخش',
				'session_speed_label' => 'برچسب سرعت پخش', 'session_download_label' => 'دکمه دانلود',
				'session_pdf_label' => 'عنوان فایل اسلاید', 'session_sales_soon' => 'پیام فرم آینده',
				'session_quiz_entry' => 'دکمه ورود به تمرین', 'session_answer_saved' => 'پیام ذخیره پاسخ',
				'session_last_edit' => 'برچسب آخرین ویرایش', 'session_sent_file' => 'عنوان فایل ارسالی',
				'session_answer_placeholder' => 'نمونه متن پاسخ', 'session_attachment_label' => 'عنوان پیوست',
				'session_checklist_hint' => 'راهنمای چک‌لیست', 'session_survey_answered' => 'پیام پاسخ قبلی نظرسنجی',
			),
		);
		?>
		<div class="wrap">
			<div class="szp-set szp-settings-center">
				<?php if ( isset( $_GET['msg'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
					<div class="notice notice-success is-dismissible"><p><strong>تنظیمات ذخیره شد.</strong> تغییرات رابط از همین حالا فعال است.</p></div>
				<?php endif; ?>

				<header class="szp-set-hero">
					<div>
						<span class="szp-set-eyebrow">مرکز کنترل افزونه</span>
						<h1>تنظیمات سازان</h1>
						<p>تنظیمات ضروری ساده و در دسترس است؛ گزینه‌های فنی فقط در صورت نیاز باز می‌شوند.</p>
					</div>
					<div class="szp-set-health">
						<span>جلسه جاری</span>
						<b><?php echo esc_html( SZP_Eval::session_label( $cur_no ) ); ?></b>
						<small><?php echo esc_html( SZP_Eval::session_date_fa( $cur_no, true ) ); ?></small>
					</div>
				</header>

				<div class="szp-set-toolbar">
					<label class="szp-set-search">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<input type="search" data-settings-search aria-label="جستجو در تنظیمات سازان" placeholder="جستجو در تنظیمات؛ مثلاً رنگ، پیامک، تارگت…">
					</label>
					<a class="szp-set-help-link" href="<?php echo esc_url( admin_url( 'admin.php?page=sazan-panel' ) ); ?>">راهنمای شروع</a>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-settings-form>
					<?php wp_nonce_field( 'szp_eval_settings' ); ?>
					<input type="hidden" name="action" value="szp_eval_settings_save">

					<nav class="szp-set-tabs" role="tablist" aria-label="بخش‌های تنظیمات">
						<button type="button" class="szp-set-tab" role="tab" aria-controls="szp-panel-start" data-tab="start"><span class="dashicons dashicons-admin-home" aria-hidden="true"></span>شروع سریع</button>
						<button type="button" class="szp-set-tab" role="tab" aria-controls="szp-panel-content" data-tab="content"><span class="dashicons dashicons-edit-page" aria-hidden="true"></span>متن‌ها</button>
						<button type="button" class="szp-set-tab" role="tab" aria-controls="szp-panel-appearance" data-tab="appearance"><span class="dashicons dashicons-art" aria-hidden="true"></span>ظاهر</button>
						<button type="button" class="szp-set-tab" role="tab" aria-controls="szp-panel-access" data-tab="access"><span class="dashicons dashicons-groups" aria-hidden="true"></span>دسترسی</button>
						<button type="button" class="szp-set-tab" role="tab" aria-controls="szp-panel-notifications" data-tab="notifications"><span class="dashicons dashicons-email-alt" aria-hidden="true"></span>پیامک</button>
						<button type="button" class="szp-set-tab" role="tab" aria-controls="szp-panel-coaching" data-tab="coaching"><span class="dashicons dashicons-welcome-learn-more" aria-hidden="true"></span>کوچینگ و ابزارها</button>
					</nav>

					<div class="szp-set-panel" id="szp-panel-start" role="tabpanel" data-panel="start">
						<div class="szp-set-onboarding">
							<div><span>۱</span><b>تقویم را تنظیم کنید</b><small>جلسه جاری و تاریخ سه‌شنبه را مشخص کنید.</small></div>
							<div><span>۲</span><b>نقش‌ها را تعریف کنید</b><small>کوچ، مدیر و اعضای تیم را تعیین کنید.</small></div>
							<div><span>۳</span><b>صفحه را منتشر کنید</b><small>شورت‌کد رشد را در صفحه کاربر بگذارید.</small></div>
						</div>

						<section class="szp-set-card" data-search-terms="جلسه تاریخ تقویم تارگت نتیجه واحد پول">
							<div class="szp-set-card-head"><div><h2>تقویم و چرخه هفتگی</h2><p>این چهار گزینه برای شروع کافی است.</p></div><span class="szp-set-tag required">ضروری</span></div>
							<?php
							self::f( 'شماره جلسه جاری', '<div class="szp-set-inline">' . self::inp( 'anchor_session', $an_no, 'number', 'class="small" min="1"' ) . '<span class="szp-set-suffix">اُمین جلسه دوره</span></div>', 'شماره‌ای که کاربران اکنون در آن قرار دارند.' );
							self::f( 'تاریخ همین جلسه', self::select_ctrl( 'anchor_date', self::tuesday_options( $an_date ), $an_date ), 'سه‌شنبه مربوط به همین شماره جلسه را انتخاب کنید.' );
							self::f( 'واحد پول', self::inp( 'currency', $s['currency'], 'text', 'placeholder="تومان"' ), 'در همه تارگت‌ها، نتیجه‌ها و گزارش‌ها نمایش داده می‌شود.' );
							self::f( 'ثبت نتیجه خارج از دوشنبه/سه‌شنبه', self::switch_ctrl( 'result_always_open', empty( $s['result_lock_enabled'] ), 'در تمام روزها اجازه ثبت نتیجه بده' ), 'روشن: فرم نتیجه همیشه در دسترس است. خاموش: فقط دوشنبه و سه‌شنبه هفته بعد باز می‌شود.' );
							?>
						</section>

						<section class="szp-set-card" data-search-terms="وضعیت درصد نزدیک هدف آستانه">
							<div class="szp-set-card-head"><div><h2>محاسبه وضعیت هدف</h2><p>سیستم فروش واقعی را با هدف مقایسه می‌کند.</p></div></div>
							<?php self::f( 'مرز «نزدیک به هدف»', '<div class="szp-set-inline">' . self::inp( 'near', $s['near'], 'number', 'class="small" min="1" max="99"' ) . '<span class="szp-set-suffix">درصد</span></div>', 'مثلاً ۸۵ یعنی نتیجه ۸۵٪ تا ۹۹٪ با وضعیت «نزدیک به هدف» نمایش داده شود.' ); ?>
						</section>

						<section class="szp-set-card" data-search-terms="شورت کد صفحه نمایش المنتور">
							<div class="szp-set-card-head"><div><h2>نمایش در سایت</h2><p>کد موردنیاز را کپی و در برگه یا ابزارک شورت‌کد المنتور قرار دهید.</p></div></div>
							<div class="szp-shortcode-list">
								<div><span>داشبورد کامل رشد و کوچینگ</span><code>[sazan_growth]</code><button type="button" data-copy="[sazan_growth]">کپی</button></div>
								<div><span>پنل دوره‌ها و جلسات</span><code>[sazan_panel]</code><button type="button" data-copy="[sazan_panel]">کپی</button></div>
								<div><span>زمان‌بندی جلسات کوچینگ</span><code>[sazan_coach_sessions]</code><button type="button" data-copy="[sazan_coach_sessions]">کپی</button></div>
							</div>
						</section>
					</div>

					<div class="szp-set-panel" id="szp-panel-content" role="tabpanel" data-panel="content">
						<div class="szp-set-panel-intro"><h2>ویرایش متن‌های رابط</h2><p>هر متنی که کاربران بیشتر می‌بینند از اینجا قابل تغییر است. خالی‌گذاشتن یک فیلد، متن پیش‌فرض را نگه می‌دارد.</p></div>
						<?php foreach ( $content_groups as $group_title => $fields ) : ?>
							<details class="szp-set-accordion" data-search-terms="<?php echo esc_attr( $group_title . ' ' . implode( ' ', $fields ) ); ?>">
								<summary><span><?php echo esc_html( $group_title ); ?></span><small><?php echo esc_html( szp_fa_digits( count( $fields ) ) ); ?> متن قابل ویرایش</small></summary>
								<div class="szp-set-accordion-body">
									<?php foreach ( $fields as $key => $label ) :
										$is_long = in_array(
											$key,
											array(
												'login_message', 'forbidden_message', 'chat_intro', 'growth_subtitle',
												'course_schedule_hint', 'profile_incomplete_hint', 'profile_required_callout',
												'growth_schedule_tuesday', 'target_action_help', 'target_locked_text',
												'history_empty_text', 'team_empty_text',
												'coaching_admin_help', 'session_sales_soon', 'session_checklist_hint', 'session_survey_answered',
											),
											true
										);
										self::f(
											esc_html( $label ),
											$is_long ? self::ta( 'panel[' . $key . ']', $ui[ $key ], 2 ) : self::inp( 'panel[' . $key . ']', $ui[ $key ] ),
											'کلید تنظیم: <code>' . esc_html( $key ) . '</code>'
										);
									endforeach; ?>
								</div>
							</details>
						<?php endforeach; ?>
					</div>

					<div class="szp-set-panel" id="szp-panel-appearance" role="tabpanel" data-panel="appearance">
						<div class="szp-set-panel-intro"><h2>ظاهر و هویت بصری</h2><p>رنگ‌ها و اندازه‌ها بدون نیاز به CSS روی بخش‌های کاربری اعمال می‌شوند.</p></div>
						<div class="szp-appearance-layout">
							<section class="szp-set-card" data-search-terms="رنگ اصلی پس زمینه متن حاشیه موفقیت خطا">
								<div class="szp-set-card-head"><div><h2>رنگ‌ها</h2><p>برای انتخاب دقیق، روی نمونه رنگ بزنید.</p></div></div>
								<div class="szp-color-grid">
									<?php
									$colors = array(
										'color_primary' => 'رنگ اصلی', 'color_secondary' => 'رنگ مکمل', 'color_success' => 'موفقیت',
										'color_danger' => 'خطا', 'color_background' => 'پس‌زمینه', 'color_surface' => 'کارت‌ها',
										'color_text' => 'متن اصلی', 'color_muted' => 'متن کم‌رنگ', 'color_border' => 'حاشیه',
									);
									foreach ( $colors as $key => $label ) : ?>
										<label><span><?php echo esc_html( $label ); ?></span><span class="szp-color-control"><input type="color" name="panel[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $ui[ $key ] ); ?>" data-preview-var="<?php echo esc_attr( $key ); ?>"><code><?php echo esc_html( $ui[ $key ] ); ?></code></span></label>
									<?php endforeach; ?>
								</div>
							</section>

							<aside class="szp-set-preview" data-settings-preview>
								<span class="preview-kicker">پیش‌نمایش زنده</span>
								<h3><?php echo esc_html( $ui['growth_title'] ); ?></h3>
								<p><?php echo esc_html( $ui['growth_subtitle'] ); ?></p>
								<div><b><?php echo esc_html( $ui['growth_target_title'] ); ?></b><span>نمونه کارت و فیلد</span><input value="۱۲۳٬۴۵۶٬۷۸۹" readonly><button type="button"><?php echo esc_html( $ui['growth_target_button'] ); ?></button></div>
							</aside>
						</div>

						<section class="szp-set-card" data-search-terms="فونت اندازه عرض گردی سایه کارت دکمه">
							<div class="szp-set-card-head"><div><h2>اندازه‌ها و فرم کلی</h2><p>مقادیر امن و استاندارد برای موبایل و دسکتاپ.</p></div></div>
							<?php
							self::f( 'خانواده فونت', self::inp( 'panel[font_family]', $ui['font_family'], 'text', 'placeholder="inherit یا Vazirmatn, Tahoma"' ), 'اگر فونت در سایت بارگذاری نشده باشد، مرورگر از فونت بعدی استفاده می‌کند.' );
							self::f( 'اندازه متن پایه', '<div class="szp-set-inline">' . self::inp( 'panel[base_font_size]', $ui['base_font_size'], 'number', 'class="small" min="13" max="22"' ) . '<span class="szp-set-suffix">پیکسل</span></div>' );
							self::f( 'حداکثر عرض محتوا', '<div class="szp-set-inline">' . self::inp( 'panel[content_width]', $ui['content_width'], 'number', 'class="small" min="680" max="1600"' ) . '<span class="szp-set-suffix">پیکسل</span></div>' );
							self::f( 'گردی کارت‌ها', '<div class="szp-set-inline">' . self::inp( 'panel[card_radius]', $ui['card_radius'], 'number', 'class="small" min="0" max="40"' ) . '<span class="szp-set-suffix">پیکسل</span></div>' );
							self::f( 'گردی دکمه‌ها', '<div class="szp-set-inline">' . self::inp( 'panel[button_radius]', $ui['button_radius'], 'number', 'class="small" min="0" max="30"' ) . '<span class="szp-set-suffix">پیکسل</span></div>' );
							self::f( 'شدت سایه', '<div class="szp-set-inline">' . self::inp( 'panel[shadow_strength]', $ui['shadow_strength'], 'number', 'class="small" min="0" max="30"' ) . '<span class="szp-set-suffix">۰ تا ۳۰</span></div>' );
							?>
						</section>

						<section class="szp-set-card" data-search-terms="نمایش خلاصه آمار">
							<div class="szp-set-card-head"><div><h2>نمایش یا مخفی‌کردن بخش‌ها</h2><p>برای خلوت‌ترشدن صفحه کاربر.</p></div></div>
							<?php
							self::f( 'خلاصه آماری', self::switch_ctrl( 'panel[show_growth_summary]', ! empty( $ui['show_growth_summary'] ), 'نمایش داده شود' ) );
							?>
						</section>

						<details class="szp-set-accordion advanced" data-search-terms="css سفارشی پیشرفته">
							<summary><span>CSS سفارشی</span><small>فقط برای طراح یا توسعه‌دهنده</small></summary>
							<div class="szp-set-accordion-body">
								<?php self::f( 'کد CSS', self::ta( 'panel[custom_css]', $ui['custom_css'], 8, 'dir="ltr" spellcheck="false"' ), 'این کد بعد از استایل‌های افزونه بارگذاری می‌شود. در صورت ناآشنایی خالی بگذارید.' ); ?>
							</div>
						</details>
					</div>

					<div class="szp-set-panel" id="szp-panel-access" role="tabpanel" data-panel="access">
						<div class="szp-set-panel-intro"><h2>نقش‌ها و دسترسی‌ها</h2><p>مدیریت روزمره نقش‌ها از صفحه اختصاصی انجام می‌شود؛ لیست‌های تکمیلی اینجا در دسترس‌اند.</p></div>
						<div class="szp-set-action-grid" data-search-terms="نقش تیم کوچ مدیر سازمان گروه ارزیابی گزارش">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=szp-roles' ) ); ?>"><span class="dashicons dashicons-groups"></span><b>نقش‌ها و تیم‌ها</b><small>کوچ، مدیر سازمان و تیم فروش</small></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=szp-groups' ) ); ?>"><span class="dashicons dashicons-networking"></span><b>گروه‌ها</b><small>عضویت کاربران در گروه‌ها</small></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=szp-eval' ) ); ?>"><span class="dashicons dashicons-chart-line"></span><b>دفتر ارزیابی</b><small>مشاهده و ویرایش داده‌های هفتگی</small></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=szp-coaching' ) ); ?>"><span class="dashicons dashicons-welcome-learn-more"></span><b>مدیریت کوچینگ</b><small>برنامه‌ها و گزارش دانشجویان</small></a>
						</div>
						<section class="szp-set-card" data-search-terms="کوچ کاربران مجاز تابلو دسترسی شناسه ایمیل">
							<div class="szp-set-card-head"><div><h2>دسترسی‌های تکمیلی</h2><p>برای موارد استثنایی؛ در استفاده عادی از صفحه نقش‌ها و تیم‌ها استفاده کنید.</p></div><span class="szp-set-tag">اختیاری</span></div>
							<?php
							self::f( 'کاربران کوچینگ اضافه', self::ta( 'coaching_users', $s['coaching_users'] ?? '', 3, 'dir="ltr" placeholder="username, user@mail.com, 42"' ), 'نام کاربری، ایمیل یا شناسه را با کاما جدا کنید.' );
							self::f( 'مشاهده‌کنندگان اضافه تابلو', self::ta( 'board_viewers', $s['board_viewers'] ?? '', 3, 'dir="ltr" placeholder="username, user@mail.com, 42"' ), 'افرادی که علاوه بر نقش‌های مدیریتی، تابلو و دفتر ارزیابی را می‌بینند.' );
							?>
						</section>
					</div>

					<div class="szp-set-panel" id="szp-panel-notifications" role="tabpanel" data-panel="notifications">
						<div class="szp-set-panel-intro"><h2>پیامک و اطلاع‌رسانی</h2><p>ابتدا اتصال را برقرار کنید؛ سپس فقط اعلان‌های موردنیاز را روشن کنید.</p></div>
						<section class="szp-set-card" data-search-terms="پیامک sms api فراز ippanel خط ارسال">
							<div class="szp-set-card-head"><div><h2>اتصال سرویس پیامک</h2><p>اطلاعاتی که از پنل پیامکی خود دریافت کرده‌اید.</p></div><span class="szp-set-tag required">مرحله اول</span></div>
							<?php
							self::f( 'سرویس‌دهنده', self::select_ctrl( 'sms_provider', array( 'smsir' => 'sms.ir', 'ippanel' => 'فراز / IPPanel' ), $provider ) );
							self::f( 'کلید API', self::inp( 'sms_apikey', $s['sms_apikey'], 'text', 'dir="ltr" autocomplete="off"' ) );
							self::f( 'خط ارسال', self::inp( 'sms_originator', $s['sms_originator'], 'text', 'dir="ltr" placeholder="3000..."' ) );
							self::f( 'حالت ارسال', self::select_ctrl( 'sms_mode', array( 'pattern' => 'پترن خدماتی', 'text' => 'متن آزاد' ), $s['sms_mode'] ) );
							?>
							<div class="szp-set-test">
								<label class="screen-reader-text" for="szp-ev-testnum">شماره موبایل دریافت‌کننده پیامک آزمایشی</label>
								<input type="text" id="szp-ev-testnum" dir="ltr" inputmode="tel" autocomplete="tel" placeholder="۰۹۱۲...">
								<button type="button" class="button button-primary szp-ev-testsms" data-nonce="<?php echo esc_attr( wp_create_nonce( 'szp_admin' ) ); ?>">ارسال پیامک آزمایشی</button>
								<span class="szp-ev-testmsg" aria-live="polite"></span>
							</div>
						</section>

						<section class="szp-set-card" data-search-terms="یادآوری اعلان مدیر تارگت نتیجه">
							<div class="szp-set-card-head"><div><h2>کدام پیام‌ها ارسال شوند؟</h2><p>هر مورد را مستقل روشن یا خاموش کنید.</p></div></div>
							<?php
							self::f( 'یادآوری به خود کاربر', self::switch_ctrl( 'sms_enabled', ! empty( $s['sms_enabled'] ), 'روز تارگت و نتیجه پیام یادآوری ارسال شود' ) );
							self::f( 'اطلاع به مدیر و کوچ', self::switch_ctrl( 'notify_enabled', ! empty( $s['notify_enabled'] ), 'پس از ثبت تارگت یا نتیجه پیام ارسال شود' ) );
							?>
						</section>

						<details class="szp-set-accordion advanced" data-search-terms="پترن متن پیامک متغیر آدرس api">
							<summary><span>متن‌ها و پترن‌های تخصصی</span><small>تنظیمات فنی پیامک</small></summary>
							<div class="szp-set-accordion-body">
								<?php
								self::f( 'آدرس پایه API', self::inp( 'sms_base', $s['sms_base'], 'text', 'dir="ltr"' ), 'برای sms.ir معمولاً خالی است.' );
								self::f( 'نام متغیر نام کاربر', self::inp( 'sms_var', $s['sms_var'], 'text', 'dir="ltr" placeholder="name"' ) );
								self::f( 'پترن یادآوری تارگت', self::inp( 'sms_pattern_target', $s['sms_pattern_target'], 'text', 'dir="ltr"' ) );
								self::f( 'پترن یادآوری نتیجه', self::inp( 'sms_pattern_result', $s['sms_pattern_result'], 'text', 'dir="ltr"' ) );
								self::f( 'متن یادآوری تارگت', self::ta( 'sms_text_target', $s['sms_text_target'], 3 ) );
								self::f( 'متن یادآوری نتیجه', self::ta( 'sms_text_result', $s['sms_text_result'], 3 ) );
								self::f( 'پترن اطلاع مدیر از تارگت', self::inp( 'sms_pattern_notify_target', $s['sms_pattern_notify_target'] ?? '', 'text', 'dir="ltr"' ) );
								self::f( 'پترن اطلاع مدیر از نتیجه', self::inp( 'sms_pattern_notify_result', $s['sms_pattern_notify_result'] ?? '', 'text', 'dir="ltr"' ) );
								self::f( 'متن اطلاع مدیر از تارگت', self::ta( 'sms_text_notify_target', $s['sms_text_notify_target'] ?? '', 3 ) );
								self::f( 'متن اطلاع مدیر از نتیجه', self::ta( 'sms_text_notify_result', $s['sms_text_notify_result'] ?? '', 3 ) );
								?>
							</div>
						</details>
					</div>

					<div class="szp-set-panel" id="szp-panel-coaching" role="tabpanel" data-panel="coaching">
						<div class="szp-set-panel-intro"><h2>کوچینگ، نظرسنجی و هوش مصنوعی</h2><p>اتصال‌ها و ابزارهای جانبی افزونه در یک محل.</p></div>
						<section class="szp-set-card" data-search-terms="کوچینگ نظرسنجی لینک تاخیر جلسه">
							<div class="szp-set-card-head"><div><h2>نظرسنجی جلسه کوچینگ</h2><p>لینک پس از پایان جلسه برای مشتری ارسال می‌شود.</p></div></div>
							<?php
							self::f( 'لینک پیش‌فرض نظرسنجی', self::inp( 'survey_url', $s['survey_url'], 'url', 'dir="ltr" placeholder="https://..."' ), 'شناسه جلسه خودکار به لینک افزوده می‌شود.' );
							self::f( 'زمان ارسال', '<div class="szp-set-inline">' . self::inp( 'survey_delay', $s['survey_delay'], 'number', 'class="small" min="1"' ) . '<span class="szp-set-suffix">دقیقه پس از پایان</span></div>' );
							?>
						</section>

						<details class="szp-set-accordion" data-search-terms="پیام جلسه نظرسنجی پترن">
							<summary><span>متن پیام‌های جلسه و نظرسنجی</span><small>پترن یا متن آزاد</small></summary>
							<div class="szp-set-accordion-body">
								<?php
								self::f( 'پترن ثبت جلسه', self::inp( 'sms_pattern_session', $s['sms_pattern_session'], 'text', 'dir="ltr"' ) );
								self::f( 'پترن نظرسنجی', self::inp( 'sms_pattern_survey', $s['sms_pattern_survey'], 'text', 'dir="ltr"' ) );
								self::f( 'متن ثبت جلسه', self::ta( 'sms_text_session', $s['sms_text_session'], 3 ) );
								self::f( 'متن نظرسنجی', self::ta( 'sms_text_survey', $s['sms_text_survey'], 3 ) );
								?>
							</div>
						</details>

						<section class="szp-set-card" data-search-terms="هوش مصنوعی ai api model canvas بوم">
							<div class="szp-set-card-head"><div><h2>هوش مصنوعی بوم خدمت</h2><p>اختیاری؛ فقط اگر از پیشنهادهای هوشمند بوم استفاده می‌کنید.</p></div><span class="szp-set-tag">اختیاری</span></div>
							<?php
							self::f( 'کلید API', self::inp( 'ai_key', $ai_key, 'password', 'dir="ltr" autocomplete="new-password"' ) );
							self::f( 'مدل', self::inp( 'ai_model', $ai_model, 'text', 'dir="ltr"' ) );
							self::f( 'آدرس API', self::inp( 'ai_base', $ai_base, 'url', 'dir="ltr"' ) );
							?>
						</section>

						<div class="szp-set-action-grid" data-search-terms="کوچینگ بوم طراحی خدمت هوش مصنوعی اتاق گفتگو">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=szp-coaching' ) ); ?>"><span class="dashicons dashicons-welcome-learn-more"></span><b>مدیریت کوچینگ</b><small>برنامه و گزارش دانشجویان</small></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=szp-canvas' ) ); ?>"><span class="dashicons dashicons-layout"></span><b>بوم طراحی خدمت</b><small>پرسش‌ها و پیشنهاد هوشمند</small></a>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=szp-chat' ) ); ?>"><span class="dashicons dashicons-format-chat"></span><b>اتاق گفتگو</b><small>مدیریت گفتگوهای دوره</small></a>
						</div>
					</div>

					<div class="szp-set-empty-search" hidden>
						<b>تنظیمی با این عبارت پیدا نشد.</b>
						<span>عبارت کوتاه‌تری مانند «رنگ»، «نتیجه» یا «پیامک» امتحان کنید.</span>
					</div>

					<footer class="szp-set-save">
						<button type="submit"><span class="dashicons dashicons-saved"></span>ذخیره همه تغییرات</button>
						<span data-save-state role="status" aria-live="polite">همه بخش‌ها با یک بار ذخیره ثبت می‌شوند.</span>
					</footer>
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
			'result_lock_enabled'       => 0,
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
		update_option( SZP_Settings::OPTION, SZP_Settings::sanitize( $p['panel'] ?? array() ) );
		update_option( SZP_AI::OPT_KEY, sanitize_text_field( $p['ai_key'] ?? '' ) );
		update_option( SZP_AI::OPT_MODEL, sanitize_text_field( $p['ai_model'] ?? '' ) );
		update_option( SZP_AI::OPT_BASE, esc_url_raw( $p['ai_base'] ?? '' ) );
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
