<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * پورتال فرانت‌اندِ CRM: یک صفحه‌ی اختصاصی برای تیم فروش، بدون نیاز به ورود به پیشخوان وردپرس.
 *
 * با شورت‌کد [sazan_crm] در یک برگه قرار می‌گیرد. همه‌ی اکشن‌ها از هندلرهای AJAXِ موجود
 * (wp_ajax_szc_*) و اسکریپت admin.js استفاده می‌کنند؛ رابط کاربری کاملاً اختصاصی است.
 *
 * دسترسی: کاربران دارای szc_access. کارشناسان (نه‌مدیر) از پیشخوان وردپرس مسدود و به پورتال
 * هدایت می‌شوند.
 */
class SZC_Portal {

	const PAGE_OPTION = 'szc_portal_page_id';
	protected static $rendering = false;

	public static function init() {
		add_shortcode( 'sazan_crm', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'lock_admin' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'maybe_hide_admin_bar' ) );
		add_filter( 'show_admin_bar', array( __CLASS__, 'hide_bar_on_portal' ), 100 );
		add_action( 'template_redirect', array( __CLASS__, 'portal_nocache' ) );
		add_action( 'wp_ajax_szc_portal_view', array( __CLASS__, 'ajax_view' ) );
		add_action( 'wp_ajax_nopriv_szc_portal_view', array( __CLASS__, 'ajax_view' ) );
		add_action( 'wp_ajax_nopriv_szc_portal_otp_request', array( __CLASS__, 'ajax_otp_request' ) );
		add_action( 'wp_ajax_szc_portal_otp_request', array( __CLASS__, 'ajax_otp_request' ) );
		add_action( 'wp_ajax_nopriv_szc_portal_otp_verify', array( __CLASS__, 'ajax_otp_verify' ) );
		add_action( 'wp_ajax_szc_portal_otp_verify', array( __CLASS__, 'ajax_otp_verify' ) );
		add_action( 'wp_ajax_nopriv_szc_portal_password_login', array( __CLASS__, 'ajax_password_login' ) );
		add_action( 'wp_ajax_szc_portal_password_login', array( __CLASS__, 'ajax_password_login' ) );
		add_action( 'init', array( __CLASS__, 'maybe_serve_pwa' ) );
		add_action( 'wp_head', array( __CLASS__, 'pwa_head' ), 1 );
	}

	/* ==================== PWA (نصب روی گوشی) ==================== */

	/** مسیرِ ریشه‌ی نصبِ وردپرس (برای scope سرویس‌ورکر). */
	protected static function home_path() {
		$p = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		return $p ? trailingslashit( $p ) : '/';
	}

	/** آیا صفحه‌ی فعلی، برگه‌ی پورتال است؟ */
	protected static function is_portal_singular() {
		if ( ! is_singular() ) {
			return false;
		}
		global $post;
		return $post && has_shortcode( (string) $post->post_content, 'sazan_crm' );
	}

	/** سروِ manifest.json و service worker از طریق پارامترِ szc_pwa. */
	public static function maybe_serve_pwa() {
		if ( ! isset( $_GET['szc_pwa'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$what = sanitize_key( wp_unslash( $_GET['szc_pwa'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'manifest' === $what ) {
			header( 'Content-Type: application/manifest+json; charset=utf-8' );
			echo wp_json_encode( self::manifest_data() ); // phpcs:ignore WordPress.Security.EscapeOutput
			exit;
		}
		if ( 'sw' === $what ) {
			header( 'Content-Type: application/javascript; charset=utf-8' );
			header( 'Service-Worker-Allowed: ' . self::home_path() );
			echo self::sw_js(); // phpcs:ignore WordPress.Security.EscapeOutput
			exit;
		}
	}

	protected static function manifest_data() {
		return array(
			'name'             => 'سازان CRM',
			'short_name'       => 'سازان CRM',
			'lang'             => 'fa',
			'dir'              => 'rtl',
			'start_url'        => self::page_url(),
			'scope'            => self::home_path(),
			'display'          => 'standalone',
			'orientation'      => 'portrait',
			'background_color' => '#0e1319',
			'theme_color'      => '#0f9fb3',
			'icons'            => array(
				array( 'src' => SZC_URL . 'assets/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ),
				array( 'src' => SZC_URL . 'assets/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ),
				array( 'src' => SZC_URL . 'assets/icons/icon-maskable.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ),
			),
		);
	}

	protected static function sw_js() {
		$ver    = SZC_VERSION;
		$start  = wp_json_encode( self::page_url() );
		$assets = wp_json_encode( array_values( array_filter( array(
			SZC_URL . 'assets/css/portal.css',
			SZC_URL . 'assets/js/admin.js',
			SZC_URL . 'assets/js/portal.js',
			SZC_URL . 'assets/fonts/YekanBakhFaNumVF.ttf',
		) ) ) );
		return <<<JS
/* سازان CRM — Service Worker v{$ver} */
var CACHE='szc-crm-{$ver}';
var START={$start};
var ASSETS={$assets};
self.addEventListener('install',function(e){self.skipWaiting();e.waitUntil(caches.open(CACHE).then(function(c){return c.addAll(ASSETS).catch(function(){});}));});
self.addEventListener('activate',function(e){e.waitUntil(caches.keys().then(function(ks){return Promise.all(ks.map(function(k){return k!==CACHE?caches.delete(k):null;}));}).then(function(){return self.clients.claim();}));});
self.addEventListener('fetch',function(e){
  var r=e.request; if(r.method!=='GET'){return;}
  if(r.mode==='navigate'){ e.respondWith(fetch(r).catch(function(){return caches.match(r).then(function(m){return m||caches.match(START);});})); return; }
  if(/\.(css|js|ttf|woff2?|png|svg|jpe?g)$/.test(new URL(r.url).pathname)){
    e.respondWith(caches.match(r).then(function(m){return m||fetch(r).then(function(res){var cp=res.clone();caches.open(CACHE).then(function(c){c.put(r,cp);});return res;}).catch(function(){return m;});}));
  }
});
self.addEventListener('notificationclick',function(e){e.notification.close();e.waitUntil(self.clients.matchAll({type:'window'}).then(function(cl){for(var i=0;i<cl.length;i++){if('focus' in cl[i]){return cl[i].focus();}}if(self.clients.openWindow){return self.clients.openWindow(START);}}));});
JS;
	}

	/** تگ‌های PWA در <head> برگه‌ی پورتال. */
	public static function pwa_head() {
		if ( ! self::is_portal_singular() ) {
			return;
		}
		$manifest = add_query_arg( 'szc_pwa', 'manifest', home_url( '/' ) );
		$icon     = SZC_URL . 'assets/icons/icon-192.png';
		echo "\n<link rel=\"manifest\" href=\"" . esc_url( $manifest ) . "\">\n";
		echo '<meta name="theme-color" content="#0f9fb3">' . "\n";
		echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' . "\n";
		echo '<meta name="apple-mobile-web-app-title" content="سازان CRM">' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( $icon ) . "\">\n";
	}

	/** بارگذاری AJAXِ یک نما (SPA). خروجی: html فرگمنت. */
	public static function ajax_view() {
		if ( ! SZC_Settings::can_access() ) {
			wp_send_json_error( array( 'msg' => 'دسترسی غیرمجاز' ), 403 );
		}
		check_ajax_referer( 'szc_admin', 'nonce' );
		self::$rendering = true;
		$view   = isset( $_POST['view'] ) ? sanitize_key( wp_unslash( $_POST['view'] ) ) : 'dashboard';
		$params = ( isset( $_POST['params'] ) && is_array( $_POST['params'] ) ) ? (array) wp_unslash( $_POST['params'] ) : array();
		$_GET   = array_merge( $_GET, $params ); // متدهای نما پارامترها را از $_GET می‌خوانند و خودشان پاک‌سازی می‌کنند.
		$html   = self::render_view( $view );
		self::$rendering = false;
		wp_send_json_success( array( 'html' => $html, 'view' => $view ) );
	}

	/* ==================== ورود کارشناسان با کد یک‌بارمصرف ==================== */

	public static function ajax_otp_request() {
		if ( ! SZC_Settings::pass_login_enabled() ) {
			wp_send_json_error( array( 'msg' => 'ورودِ کارشناسان غیرفعال است.' ), 403 );
		}
		check_ajax_referer( 'szc_portal_otp', 'nonce' );
		$mobile = isset( $_POST['mobile'] ) ? szc_normalize_mobile( wp_unslash( $_POST['mobile'] ) ) : '';
		$result = SZC_Auth::request_otp( $mobile );
		if ( empty( $result['ok'] ) ) {
			wp_send_json_error( $result, ! empty( $result['retry_after'] ) ? 429 : 400 );
		}
		wp_send_json_success( $result );
	}

	public static function ajax_otp_verify() {
		if ( ! SZC_Settings::pass_login_enabled() ) {
			wp_send_json_error( array( 'msg' => 'ورودِ کارشناسان غیرفعال است.' ), 403 );
		}
		check_ajax_referer( 'szc_portal_otp', 'nonce' );
		$mobile = isset( $_POST['mobile'] ) ? szc_normalize_mobile( wp_unslash( $_POST['mobile'] ) ) : '';
		$code = isset( $_POST['code'] ) ? sanitize_text_field( wp_unslash( $_POST['code'] ) ) : '';
		$result = SZC_Auth::verify_otp( $mobile, $code );
		if ( empty( $result['ok'] ) || empty( $result['agent'] ) ) {
			unset( $result['agent'] );
			wp_send_json_error( $result, 400 );
		}
		$agent = $result['agent'];
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
		SZC_Agents::record_login( (int) $agent->id, $ip );
		if ( class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'login_success', 'agent', (int) $agent->id, 'ورود موفق کارشناس با کد یک‌بارمصرف', null, null, SZC_Agents::to_owner( (int) $agent->id ) );
		}
		SZC_Auth::login_agent( $agent );
		wp_send_json_success( array( 'redirect' => self::page_url(), 'msg' => 'خوش آمدید' ) );
	}

	public static function ajax_password_login() {
		if ( ! SZC_Settings::pass_login_enabled() ) {
			wp_send_json_error( array( 'msg' => 'ورود کارشناسان غیرفعال است.' ), 403 );
		}
		check_ajax_referer( 'szc_portal_otp', 'nonce' );
		$mobile   = isset( $_POST['mobile'] ) ? szc_normalize_mobile( wp_unslash( $_POST['mobile'] ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$result   = SZC_Auth::verify_fixed_password( $mobile, $password );
		if ( empty( $result['ok'] ) || empty( $result['agent'] ) ) {
			unset( $result['agent'] );
			wp_send_json_error( $result, ! empty( $result['retry_after'] ) ? 429 : 400 );
		}
		$agent = $result['agent'];
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
		SZC_Agents::record_login( (int) $agent->id, $ip );
		if ( class_exists( 'SZC_Audit' ) ) {
			SZC_Audit::log( 'login_success', 'agent', (int) $agent->id, 'ورود موفق کارشناس با رمز ثابت', null, null, SZC_Agents::to_owner( (int) $agent->id ) );
		}
		SZC_Auth::login_agent( $agent );
		wp_send_json_success( array( 'redirect' => self::page_url(), 'msg' => 'خوش آمدید' ) );
	}

	/** فرم ورود دوحالته: کد یک‌بارمصرف یا رمز ثابت. */
	protected static function login_form_html() {
		ob_start(); ?>
		<div class="szc-portal szc-portal--login" dir="rtl">
			<span class="szc-p-login-orb szc-p-login-orb--one" aria-hidden="true"></span>
			<span class="szc-p-login-orb szc-p-login-orb--two" aria-hidden="true"></span>
			<form class="szc-p-loginbox" data-login-form>
				<div class="szc-p-login-content" data-login-content>
					<span class="szc-p-login-kicker">ورود امن کارشناسان فروش</span>
					<span class="szc-p-login-logo"><?php echo szc_icon( 'idcard' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h1 class="szc-p-login-title">پنل فروش سازان</h1>
					<p class="szc-p-login-sub" data-login-sub>شماره موبایل ثبت‌شده خود را وارد کنید تا کد ورود برایتان پیامک شود.</p>
				<div class="szc-p-login-methods" role="tablist" aria-label="روش ورود">
					<button type="button" role="tab" class="is-active" aria-selected="true" data-login-method="otp"><?php echo szc_icon( 'smartphone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> کد یک‌بارمصرف</button>
					<button type="button" role="tab" aria-selected="false" data-login-method="password"><?php echo szc_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> رمز ثابت</button>
				</div>
				<label class="szc-p-login-label" for="szc-agent-mobile">شماره موبایل</label>
				<div class="szc-p-login-field">
					<span class="szc-p-login-ico"><?php echo szc_icon( 'smartphone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<input id="szc-agent-mobile" type="tel" data-login-mobile dir="ltr" placeholder="مثلاً ۰۹۱۲۱۲۳۴۵۶۷" autocomplete="tel" inputmode="numeric" enterkeyhint="send" autocapitalize="off" spellcheck="false">
				</div>
				<div data-login-code-wrap hidden>
					<label class="szc-p-login-label" for="szc-agent-code">کد یک‌بارمصرف</label>
					<div class="szc-p-login-field szc-p-login-code">
						<span class="szc-p-login-ico"><?php echo szc_icon( 'check-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<input id="szc-agent-code" type="text" data-login-code dir="ltr" maxlength="6" pattern="[0-9۰-۹]{6}" placeholder="کد ۶ رقمی" autocomplete="one-time-code" inputmode="numeric" enterkeyhint="go" autocapitalize="off" spellcheck="false">
					</div>
					<div class="szc-p-otp-meta">
						<button type="button" data-login-change-mobile>تغییر شماره</button>
						<button type="button" data-login-resend disabled>ارسال مجدد <span data-login-countdown></span></button>
					</div>
				</div>
				<div data-login-password-wrap hidden>
					<label class="szc-p-login-label" for="szc-agent-password-login">رمز ثابت</label>
					<div class="szc-p-login-field szc-p-login-password">
						<span class="szc-p-login-ico"><?php echo szc_icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<input id="szc-agent-password-login" type="password" data-login-password dir="ltr" maxlength="200" placeholder="رمز عبور" autocomplete="current-password" enterkeyhint="go" autocapitalize="off" spellcheck="false">
						<button type="button" data-login-password-toggle aria-label="نمایش رمز" aria-pressed="false"><?php echo szc_icon( 'eye' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					</div>
				</div>
				<input type="hidden" data-login-nonce value="<?php echo esc_attr( wp_create_nonce( 'szc_portal_otp' ) ); ?>">
				<button type="submit" class="szc-p-btn szc-p-btn-primary szc-p-login-btn"><?php echo szc_icon( 'send' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span data-login-button-label>دریافت کد ورود</span></button>
				<div class="szc-p-login-msg" data-login-msg role="status" aria-live="polite"></div>
				<p class="szc-p-otp-help" data-login-help>کد ورود کوتاه‌مدت است و فقط یک‌بار قابل استفاده خواهد بود.</p>
				<a class="szc-p-login-admin" href="<?php echo esc_url( wp_login_url( self::page_url() ) ); ?>">ورود مدیر (وردپرس)</a>
				</div>
				<div class="szc-p-login-success" data-login-success hidden aria-live="assertive">
					<span class="szc-p-login-success-ring"><?php echo szc_icon( 'check-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<h2>ورود موفق بود</h2>
					<p>در حال آماده‌سازی میزکار شما…</p>
				</div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ==================== صفحه و URL ==================== */

	/** شناسه‌ی برگه‌ی پورتال (کش‌شده؛ در صورت نبود، اسکنِ محتوای برگه‌ها). */
	public static function portal_page_id() {
		$id = (int) get_option( self::PAGE_OPTION, 0 );
		if ( $id && get_post_status( $id ) === 'publish' ) {
			return $id;
		}
		global $wpdb;
		$found = (int) $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ('page','post') AND post_content LIKE '%[sazan_crm%' ORDER BY ID ASC LIMIT 1"
		);
		if ( $found ) {
			update_option( self::PAGE_OPTION, $found, false );
		}
		return $found;
	}

	public static function page_url() {
		$id = self::portal_page_id();
		return $id ? get_permalink( $id ) : home_url( '/' );
	}

	/** آدرس یک نمای پورتال. */
	public static function url( $view = 'dashboard', $args = array() ) {
		$args = array_merge( array( 'szc_view' => $view ), $args );
		unset( $args['page'] );
		return add_query_arg( $args, self::page_url() );
	}

	public static function contact_url( $id ) {
		return self::url( 'contact', array( 'id' => (int) $id ) );
	}

	/** آیا در بافتِ پورتال هستیم؟ (هنگام رندر شورت‌کد یا درخواست AJAX از صفحه‌ی پورتال) */
	public static function is_portal_context() {
		if ( self::$rendering ) {
			return true;
		}
		$id = self::portal_page_id();
		if ( ! $id ) {
			return false;
		}
		$ref = wp_get_referer();
		return $ref && strpos( $ref, get_permalink( $id ) ) !== false;
	}

	/* ==================== قفل پیشخوان ==================== */

	protected static function is_agent_only() {
		return SZC_Settings::can_access() && ! SZC_Settings::is_manager();
	}

	/** کارشناسِ نه‌مدیر نباید وارد پیشخوان شود؛ به پورتال هدایت می‌شود. */
	public static function lock_admin() {
		if ( ! self::is_agent_only() ) {
			return;
		}
		if ( wp_doing_ajax() ) {
			return; // admin-ajax باید کار کند
		}
		$self = isset( $_SERVER['PHP_SELF'] ) ? basename( $_SERVER['PHP_SELF'] ) : '';
		if ( in_array( $self, array( 'admin-post.php', 'admin-ajax.php' ), true ) ) {
			return;
		}
		wp_safe_redirect( self::page_url() );
		exit;
	}

	public static function maybe_hide_admin_bar() {
		if ( self::is_agent_only() ) {
			add_filter( 'show_admin_bar', '__return_false' );
		}
	}

	/** نوارِ ابزارِ وردپرس روی برگه‌ی پورتال همیشه مخفی بماند (برای همه، از جمله مدیر). */
	public static function hide_bar_on_portal( $show ) {
		return self::is_portal_singular() ? false : $show;
	}

	/** برگه‌ی پورتال برای نشستِ کارشناس کش نشود (پیش از ارسالِ خروجی). */
	public static function portal_nocache() {
		if ( self::is_portal_singular() && SZC_Auth::is_agent() ) {
			nocache_headers();
		}
	}

	/* ==================== assets ==================== */

	public static function assets() {
		if ( ! is_singular() ) {
			return;
		}
		global $post;
		if ( ! $post || ! has_shortcode( (string) $post->post_content, 'sazan_crm' ) ) {
			return;
		}
		$css = SZC_DIR . 'assets/css/portal.css';
		$js  = SZC_DIR . 'assets/js/admin.js';
		$pjs = SZC_DIR . 'assets/js/portal.js';
		wp_enqueue_style( 'szc-portal', SZC_URL . 'assets/css/portal.css', array(), file_exists( $css ) ? filemtime( $css ) : SZC_VERSION );
		wp_enqueue_script( 'szc-admin', SZC_URL . 'assets/js/admin.js', array(), file_exists( $js ) ? filemtime( $js ) : SZC_VERSION, true );
		wp_enqueue_script( 'szc-portal', SZC_URL . 'assets/js/portal.js', array( 'szc-admin' ), file_exists( $pjs ) ? filemtime( $pjs ) : SZC_VERSION, true );
		wp_localize_script( 'szc-admin', 'SZC_ADMIN', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'szc_admin' ),
		) );
		wp_localize_script( 'szc-portal', 'SZC_PORTAL', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'szc_admin' ),
			'base'  => self::page_url(),
			'sw'    => add_query_arg( 'szc_pwa', 'sw', home_url( '/' ) ),
			'scope' => self::home_path(),
			'permissions' => SZC_Auth::is_manager() ? array_fill_keys( array_keys( SZC_Settings::permission_labels() ), 1 ) : ( SZC_Auth::current_agent() && SZC_Agents::permissions( SZC_Auth::current_agent() ) ? SZC_Agents::permissions( SZC_Auth::current_agent() ) : SZC_Settings::agent_permissions() ),
		) );
	}

	/* ==================== نماها ==================== */

	protected static function nav_items() {
		$is_manager = SZC_Settings::is_manager();
		$items = array(
			'dashboard' => array( $is_manager ? 'مرکز کنترل' : 'داشبورد', 'home' ),
			'dialer'    => array( 'تماس پشت‌سرهم', 'phone' ),
			'contacts'  => array( 'مخاطبین', 'users' ),
			'kanban'    => array( 'کانبان فروش', 'shuffle' ),
			'add'       => array( 'افزودن مخاطب', 'user-plus' ),
			'followups' => array( 'پیگیری‌ها', 'bell' ),
			'reports'   => array( $is_manager ? 'تحلیل مدیریتی' : 'گزارش‌ها', 'chart' ),
		);
		if ( SZC_Auth::can( 'bulk_sms' ) ) {
			$items['broadcast'] = array( 'ارسال گروهی', 'send' );
		}
		return $items;
	}

	public static function shortcode( $atts = array() ) {
		if ( ! SZC_Settings::can_access() ) {
			if ( SZC_Settings::pass_login_enabled() ) {
				return self::login_form_html();
			}
			return '<div class="szc-portal"><div class="szc-p-login">شما به پنل فروش دسترسی ندارید. با مدیر تماس بگیرید.</div></div>';
		}

		// نشستِ کارشناس شخصی است؛ کش‌ نشود تا داده‌ی یک کارشناس به دیگری نشت نکند.
		if ( SZC_Auth::is_agent() && ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		// ثبت برگه‌ی پورتال برای هدایت‌ها.
		$pid = get_the_ID();
		if ( $pid && (int) get_option( self::PAGE_OPTION, 0 ) !== $pid ) {
			update_option( self::PAGE_OPTION, $pid, false );
		}

		self::$rendering = true;
		$view = isset( $_GET['szc_view'] ) ? sanitize_key( wp_unslash( $_GET['szc_view'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification
		$fs   = ! isset( $atts['fullscreen'] ) || ! in_array( (string) $atts['fullscreen'], array( '0', 'no', 'false' ), true );

		ob_start(); ?>
		<div class="szc-portal<?php echo $fs ? ' is-fullscreen' : ''; ?>" dir="rtl">
			<a class="szc-p-skip" href="#szc-sales-main">رفتن به محتوای اصلی</a>
			<?php echo self::topbar_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="szc-p-body">
				<?php echo self::sidebar_html( $view ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<main class="szc-p-main" id="szc-sales-main" tabindex="-1">
					<?php echo self::render_view( $view ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</main>
			</div>
			<?php echo self::bottomnav_html( $view ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php
		self::$rendering = false;
		return ob_get_clean();
	}

	protected static function topbar_html() {
		$name    = SZC_Auth::current_name();
		$initial = $name !== '' ? mb_substr( $name, 0, 1 ) : '؟';
		$logout  = wp_nonce_url( add_query_arg( 'szc_logout', '1', self::page_url() ), 'szc_agent_logout' );
		ob_start(); ?>
		<header class="szc-p-top">
			<div class="szc-p-brand">
				<span class="szc-p-logo"><?php echo szc_icon( 'idcard' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="szc-p-brandtext"><span class="szc-p-title">سازان CRM</span><small><?php echo SZC_Settings::is_manager() ? 'مرکز نظارت مدیریت' : 'میزکار فروش'; ?></small></span>
			</div>
			<form class="szc-p-search" method="get" action="<?php echo esc_url( self::page_url() ); ?>">
				<input type="hidden" name="szc_view" value="contacts">
				<span class="szc-p-searchico"><?php echo szc_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<label class="screen-reader-text" for="szc-global-search">جستجوی مخاطب</label>
				<input id="szc-global-search" type="search" name="s" placeholder="نام، شماره، شرکت یا منبع…" value="<?php echo isset( $_GET['s'] ) ? esc_attr( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore ?>" autocomplete="off">
				<kbd>/</kbd>
			</form>
			<div class="szc-p-user">
				<button type="button" class="szc-p-themebtn" data-theme-toggle aria-label="حالت روشن/تاریک" title="حالت روشن/تاریک">
					<span class="szc-p-theme-sun"><?php echo szc_icon( 'sun' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="szc-p-theme-moon"><?php echo szc_icon( 'moon' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				</button>
				<span class="szc-p-avatar szc-p-avatar--initial"><?php echo esc_html( $initial ); ?></span>
				<span class="szc-p-uname"><?php echo esc_html( $name ); ?></span>
				<a class="szc-p-logout" href="<?php echo esc_url( $logout ); ?>" aria-label="خروج"><?php echo szc_icon( 'log-out' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			</div>
		</header>
		<?php
		return ob_get_clean();
	}

	protected static function sidebar_html( $active ) {
		$items = self::nav_items();
		$management_items = array( 'kanban', 'add', 'reports' );
		if ( isset( $items['broadcast'] ) ) { $management_items[] = 'broadcast'; }
		$groups = array(
			'کار روزانه' => array( 'dashboard', 'dialer', 'contacts', 'followups' ),
			'مدیریت فروش' => $management_items,
		);
		ob_start(); ?>
		<aside class="szc-p-side">
			<a class="szc-p-sidecall" data-view="<?php echo SZC_Settings::is_manager() ? 'reports' : 'dialer'; ?>" href="<?php echo esc_url( self::url( SZC_Settings::is_manager() ? 'reports' : 'dialer' ) ); ?>"><?php echo szc_icon( SZC_Settings::is_manager() ? 'chart' : 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><b><?php echo SZC_Settings::is_manager() ? 'گزارش کامل تیم' : 'شروع تماس‌ها'; ?></b><small><?php echo SZC_Settings::is_manager() ? 'تحلیل عملکرد و فروش' : 'صف اولویت‌دار امروز'; ?></small></span><?php echo szc_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
			<nav class="szc-p-nav">
				<?php foreach ( $groups as $group_label => $keys ) : ?>
					<div class="szc-p-navgroup"><span class="szc-p-navlabel"><?php echo esc_html( $group_label ); ?></span>
					<?php foreach ( $keys as $key ) : $it = $items[ $key ]; ?>
						<a class="szc-p-navlink<?php echo $active === $key ? ' is-active' : ''; ?>" data-view="<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( self::url( $key ) ); ?>">
							<span class="szc-p-navico"><?php echo szc_icon( $it[1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span><?php echo esc_html( $it[0] ); ?></span>
						</a>
					<?php endforeach; ?></div>
				<?php endforeach; ?>
			</nav>
			<?php if ( SZC_Settings::is_manager() ) : ?>
				<a class="szc-p-adminlink" href="<?php echo esc_url( admin_url( 'admin.php?page=sazan-suite&tab=crm' ) ); ?>"><?php echo szc_icon( 'settings' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>تنظیمات (مدیر)</span></a>
			<?php endif; ?>
		</aside>
		<?php
		return ob_get_clean();
	}

	/** نوار پایینِ اپلیکیشنی برای موبایل (۵ آیتم اصلی). */
	protected static function bottomnav_html( $active ) {
		$main = array( 'dashboard', 'dialer', 'kanban', 'contacts', 'followups' );
		$all  = self::nav_items();
		// برچسبِ کوتاه برای تب‌بارِ باریکِ موبایل (برچسبِ بلندِ ساید‌بار دست‌نخورده می‌ماند).
		$short = array( 'dialer' => 'تماس‌ها', 'kanban' => 'قیف' );
		ob_start(); ?>
		<nav class="szc-p-bottomnav">
			<?php foreach ( $main as $key ) : if ( ! isset( $all[ $key ] ) ) { continue; }
				$label = isset( $short[ $key ] ) ? $short[ $key ] : $all[ $key ][0]; ?>
				<a class="szc-p-bnitem<?php echo $active === $key ? ' is-active' : ''; ?>" data-view="<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( self::url( $key ) ); ?>">
					<span class="szc-p-bnico"><?php echo szc_icon( $all[ $key ][1] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="szc-p-bnlbl"><?php echo esc_html( $label ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
		return ob_get_clean();
	}

	protected static function render_view( $view ) {
		switch ( $view ) {
			case 'contacts':
				return self::view_contacts();
			case 'contact':
				return self::view_contact();
			case 'kanban':
				return self::view_kanban();
			case 'add':
				return self::view_add();
			case 'dialer':
				return self::view_dialer();
			case 'followups':
				return self::view_followups();
			case 'reports':
				return self::view_reports();
			case 'broadcast':
				return self::view_broadcast();
			case 'dashboard':
			default:
				return self::view_dashboard();
		}
	}

	/* ---------- داشبورد ---------- */

	protected static function view_dashboard() {
		if ( SZC_Settings::is_manager() ) {
			return self::view_manager_dashboard();
		}
		$owner  = SZC_Settings::scope_owner();
		$agent  = SZC_Auth::current_agent();
		// استخر مشترک فقط دامنه مشاهده مخاطبان را باز می‌کند؛ KPI و تارگت باید همیشه شخصی بمانند.
		$activity_owner = $agent ? SZC_Agents::to_owner( (int) $agent->id ) : $owner;
		$counts = SZC_Contacts::counts_by_stage( $owner );
		$total  = SZC_Contacts::total( $owner );
		$queue  = SZC_SMS::queue_counts();
		$due    = SZC_Activity::due_followups( 15, $owner );
		$calls  = SZC_Reports::calls_today( $activity_owner );
		$answered = SZC_Reports::answered_calls_today( $activity_owner );
		$target   = $agent ? max( 0, (int) ( $agent->daily_answered_target ?? 0 ) ) : 0;
		$day_stats = $agent ? SZC_Reports::agent_day_stats( $activity_owner ) : array();
		$funnel = SZC_Reports::funnel( $owner );
		$unattended = SZC_Workspace::unattended( $owner, 8 );
		$upcoming   = SZC_Workspace::upcoming( $owner, 24, 8 );
		$unassigned = SZC_Auth::is_agent() ? SZC_Workspace::unassigned( 8 ) : array();
		$hello_name = SZC_Auth::current_name();

		ob_start(); ?>
		<section class="szc-p-welcome">
			<div>
				<span class="szc-p-eyebrow"><?php echo esc_html( wp_date( 'l، j F' ) ); ?></span>
				<h1><?php echo $hello_name ? 'سلام ' . esc_html( $hello_name ) . '،' : 'سلام،'; ?> آماده‌ای فروش امروز را جلو ببریم؟</h1>
				<p><?php echo count( $due ) ? esc_html( szc_fa_digits( count( $due ) ) ) . ' پیگیری سررسیده داری. از مهم‌ترین تماس شروع کن.' : 'پیگیری عقب‌افتاده‌ای نداری؛ فرصت خوبی برای تماس با لیدهای تازه است.'; ?></p>
			</div>
			<div class="szc-p-welcome-actions">
				<a class="szc-p-btn szc-p-btn-primary" data-view="dialer" href="<?php echo esc_url( self::url( 'dialer' ) ); ?>"><?php echo szc_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>شروع تماس‌های امروز</span></a>
				<a class="szc-p-btn szc-p-btn-soft" data-view="add" href="<?php echo esc_url( self::url( 'add' ) ); ?>"><?php echo szc_icon( 'user-plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>مخاطب جدید</span></a>
			</div>
		</section>

		<div class="szc-p-pwabar" data-overdue="<?php echo (int) count( $due ); ?>" hidden>
			<button type="button" class="szc-p-btn szc-p-btn-primary" data-pwa-install hidden><?php echo szc_icon( 'smartphone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>نصب روی گوشی</span></button>
			<button type="button" class="szc-p-btn" data-notify-enable hidden><?php echo szc_icon( 'bell' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>فعال‌سازی اعلان پیگیری</span></button>
		</div>

		<?php if ( $agent && $target > 0 ) : ?>
		<div class="szc-p-goal" data-goal-calls="<?php echo (int) $answered; ?>" data-goal-target-value="<?php echo (int) $target; ?>">
			<div class="szc-p-goal-ring" data-goal-ring>
				<svg viewBox="0 0 44 44" class="szc-p-goal-svg" aria-hidden="true">
					<circle class="szc-p-goal-bg" cx="22" cy="22" r="19"></circle>
					<circle class="szc-p-goal-fg" cx="22" cy="22" r="19" data-goal-fg></circle>
				</svg>
				<span class="szc-p-goal-pct" data-goal-pct><?php echo esc_html( szc_fa_digits( 0 ) ); ?>٪</span>
			</div>
			<div class="szc-p-goal-info">
				<span class="szc-p-goal-title">تارگت تماس موفق امروز</span>
				<span class="szc-p-goal-nums"><b data-goal-done><?php echo esc_html( szc_fa_digits( $answered ) ); ?></b> از <span data-goal-target><?php echo esc_html( szc_fa_digits( $target ) ); ?></span> تماس پاسخ‌داده‌شده</span>
				<span class="szc-p-muted">این هدف توسط مدیریت تعیین شده است.</span>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( $agent ) : ?>
		<div class="szc-p-section-head"><div><h2>هدف‌های امروز</h2><p>پیشرفت روزانه خودت را در یک نگاه ببین.</p></div></div>
		<div class="szc-p-stats szc-p-stats--goals">
			<?php
			echo self::stat_card( 'پیشرفت پیگیری', szc_fa_digits( $day_stats['followups'] ) . '/' . szc_fa_digits( $day_stats['followup_target'] ), 'calendar', self::url( 'followups' ), 'violet' );
			echo self::stat_card( 'پیشرفت تبدیل', szc_fa_digits( $day_stats['won_contacts'] ) . '/' . szc_fa_digits( $day_stats['conversion_target'] ), 'target', self::url( 'kanban' ), 'green' );
			?>
		</div>
		<?php endif; ?>

		<div class="szc-p-section-head"><div><h2>نبض امروز</h2><p>فقط شاخص‌هایی که برای اقدام روزانه مهم‌اند.</p></div></div>
		<div class="szc-p-stats">
			<?php
			echo self::stat_card( 'کل مخاطبین', szc_fa_digits( $total ), 'users', self::url( 'contacts' ), 'blue' );
			echo self::stat_card( 'تماس‌های امروز', szc_fa_digits( $calls ), 'phone', self::url( 'dialer' ), 'cyan' );
			echo self::stat_card( 'تماس موفق امروز', szc_fa_digits( $answered ), 'check-circle', self::url( 'reports' ), 'green' );
			echo self::stat_card( 'پیگیری سررسیده', szc_fa_digits( count( $due ) ), 'bell', self::url( 'followups' ), count( $due ) ? 'red' : 'green' );
			echo self::stat_card( 'پیامک در صف', szc_fa_digits( $queue['pending'] ), 'mail', self::url( 'dashboard' ), 'violet' );
			echo self::stat_card( 'نرخ تبدیل', szc_fa_digits( $funnel['conversion'] ) . '٪', 'target', self::url( 'kanban' ), 'amber' );
			?>
		</div>

		<section class="szc-p-card szc-p-today">
			<div class="szc-p-cardhead"><div><span class="szc-p-cardicon"><?php echo szc_icon( 'sparkles' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><h2 class="szc-p-h2">میزکار امروز من</h2><p>اقدام‌ها بر اساس فوریت و زمان پیگیری مرتب شده‌اند.</p></div></div><a data-view="dialer" href="<?php echo esc_url( self::url( 'dialer' ) ); ?>">اجرای صف تماس <?php echo szc_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></div>
			<div class="szc-p-grid2">
				<div><h3>اقدام‌های اولویت‌دار</h3>
					<?php if ( ! $unattended ) : ?><p class="szc-p-muted">مخاطب فراموش‌شده‌ای ندارید.</p><?php else : ?><ul class="szc-p-duelist">
					<?php foreach ( $unattended as $item ) : $next = SZC_Workspace::next_action( $item ); ?><li class="szc-p-dueitem"><span class="szc-p-dueinfo"><a href="<?php echo esc_url( self::contact_url( $item->id ) ); ?>"><b><?php echo esc_html( SZC_Contacts::full_name( $item ) ); ?></b></a><small><?php echo esc_html( $next['reason'] ); ?></small></span><span class="szc-next-action"><?php echo esc_html( $next['label'] ); ?></span></li><?php endforeach; ?>
					</ul><?php endif; ?>
				</div>
				<div><h3>پیگیری‌های ۲۴ ساعت آینده</h3>
					<?php if ( ! $upcoming ) : ?><p class="szc-p-muted">پیگیری نزدیکی وجود ندارد.</p><?php else : ?><ul class="szc-p-duelist">
					<?php foreach ( $upcoming as $item ) : ?><li class="szc-p-dueitem"><span class="szc-p-dueinfo"><a href="<?php echo esc_url( self::contact_url( $item->id ) ); ?>"><b><?php echo esc_html( SZC_Contacts::full_name( $item ) ); ?></b></a></span><span class="szc-p-due"><?php echo esc_html( szc_format_mysql( $item->next_followup_at ) ); ?></span></li><?php endforeach; ?>
					</ul><?php endif; ?>
				</div>
			</div>
			<?php if ( $unassigned ) : ?><div><h3>لیدهای بدون مسئول</h3><div class="szc-p-btnrow">
				<?php foreach ( $unassigned as $item ) : ?><button type="button" class="szc-p-btn" data-szc-act="claim_lead" data-id="<?php echo (int) $item->id; ?>"><?php echo esc_html( SZC_Contacts::full_name( $item ) ); ?> — Claim</button><?php endforeach; ?>
			</div></div><?php endif; ?>
		</section>

		<div class="szc-p-grid2">
			<section class="szc-p-card">
				<h2 class="szc-p-h2">قیف فروش</h2>
				<div class="szc-p-funnel">
					<?php
					$max = 1;
					foreach ( $funnel['stages'] as $st ) { $max = max( $max, $st['count'] ); }
					foreach ( $funnel['stages'] as $k => $st ) :
						$w = (int) round( $st['count'] / $max * 100 );
						?>
						<a class="szc-p-frow" href="<?php echo esc_url( self::url( 'contacts', array( 'stage' => $k ) ) ); ?>">
							<span class="szc-p-flabel"><?php echo esc_html( $st['label'] ); ?></span>
							<span class="szc-p-fbar"><span class="szc-p-ffill" style="width:<?php echo esc_attr( max( 4, $w ) ); ?>%"></span></span>
							<span class="szc-p-fcount"><?php echo esc_html( szc_fa_digits( $st['count'] ) ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="szc-p-card">
				<h2 class="szc-p-h2">پیگیری‌های سررسیده</h2>
				<?php if ( ! $due ) : ?>
					<p class="szc-p-empty szc-p-empty-ok"><?php echo szc_icon( 'check-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> پیگیری سررسیده‌ای نیست.</p>
				<?php else : ?>
					<ul class="szc-p-duelist">
						<?php foreach ( $due as $r ) :
							$name    = trim( $r->first_name . ' ' . $r->last_name ) ?: szc_fa_digits( $r->mobile );
							$overdue = strtotime( $r->due_at ) < current_time( 'timestamp' );
							?>
							<li class="szc-p-dueitem">
								<button type="button" class="szc-p-donebtn" data-szc-act="done_followup" data-id="<?php echo (int) $r->id; ?>" title="انجام شد" aria-label="علامت انجام‌شده"><?php echo szc_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
								<span class="szc-p-dueinfo">
									<a href="<?php echo esc_url( self::contact_url( $r->contact_id ) ); ?>"><b><?php echo esc_html( $name ); ?></b></a>
									<?php if ( ! empty( $r->body ) ) : ?><span class="szc-p-duenote"><?php echo esc_html( $r->body ); ?></span><?php endif; ?>
								</span>
								<span class="szc-p-due<?php echo $overdue ? ' is-overdue' : ''; ?>"><?php echo esc_html( szc_format_mysql( $r->due_at ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
					<a class="szc-p-morelink" href="<?php echo esc_url( self::url( 'followups' ) ); ?>">همه‌ی پیگیری‌ها <?php echo szc_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<?php endif; ?>
			</section>
		</div>
		<?php
		return ob_get_clean();
	}

	/** مرکز فرماندهی مدیر در همان پرتال روزانه کارشناسان. */
	protected static function view_manager_dashboard() {
		$today = wp_date( 'Y-m-d' );
		$from  = isset( $_GET['from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : wp_date( 'Y-m-d', time() - 6 * DAY_IN_SECONDS ); // phpcs:ignore WordPress.Security.NonceVerification
		$to    = isset( $_GET['to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : $today; // phpcs:ignore WordPress.Security.NonceVerification
		if ( $from > $to ) { $swap = $from; $from = $to; $to = $swap; }

		$team       = SZC_Reports::team_performance( $from, $to );
		$owner      = absint( $_GET['agent'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$owner_map  = array();
		foreach ( $team as $member ) { $owner_map[ (int) $member['owner'] ] = $member['agent']->name; }
		if ( $owner && ! isset( $owner_map[ $owner ] ) ) { $owner = 0; }
		$filters    = array( 'from' => $from, 'to' => $to, 'owner' => $owner );
		$metrics    = SZC_Reports::management_metrics( $filters );
		$funnel     = SZC_Reports::stage_conversion( $filters );
		$activities = SZC_Reports::recent_team_activity( 40 );
		if ( $owner ) {
			$team = array_values( array_filter( $team, function ( $member ) use ( $owner ) { return (int) $member['owner'] === $owner; } ) );
			$activities = array_values( array_filter( $activities, function ( $activity ) use ( $owner ) { return (int) $activity->user_id === $owner; } ) );
		}
		$activities = array_slice( $activities, 0, 20 );
		$type_labels = array( 'call' => 'تماس', 'followup' => 'پیگیری', 'sms' => 'پیامک', 'note' => 'یادداشت', 'stage' => 'تغییر مرحله', 'external' => 'واتساپ/تلگرام' );
		$queue = SZC_SMS::queue_counts();
		ob_start(); ?>
		<section class="szc-manager-hero">
			<div><span class="szc-p-eyebrow">نمای مدیریتی و نظارتی</span><h1>مرکز کنترل تیم فروش</h1><p>سلام <?php echo esc_html( SZC_Auth::current_name() ); ?>؛ وضعیت تیم، گلوگاه‌ها و اقدام‌های نیازمند توجه را در یک نگاه ببینید.</p></div>
			<div class="szc-manager-hero__actions"><a class="szc-p-btn szc-p-btn-primary" data-view="reports" href="<?php echo esc_url( self::url( 'reports' ) ); ?>"><?php echo szc_icon( 'chart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> تحلیل کامل</a><a class="szc-p-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=szc-agents' ) ); ?>"><?php echo szc_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> مدیریت تیم</a></div>
		</section>

		<form class="szc-manager-filters" method="get" action="<?php echo esc_url( self::page_url() ); ?>">
			<input type="hidden" name="szc_view" value="dashboard">
			<label>از تاریخ<input type="date" name="from" value="<?php echo esc_attr( $from ); ?>"></label>
			<label>تا تاریخ<input type="date" name="to" value="<?php echo esc_attr( $to ); ?>"></label>
			<label>کارشناس<select name="agent"><option value="0">کل تیم</option><?php foreach ( $owner_map as $oid => $name ) : ?><option value="<?php echo (int) $oid; ?>" <?php selected( $owner, $oid ); ?>><?php echo esc_html( $name ); ?></option><?php endforeach; ?></select></label>
			<button class="szc-p-btn szc-p-btn-primary" type="submit"><?php echo szc_icon( 'filter' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> اعمال فیلتر</button>
			<?php if ( $owner || $from !== wp_date( 'Y-m-d', time() - 6 * DAY_IN_SECONDS ) || $to !== $today ) : ?><a class="szc-p-btn" href="<?php echo esc_url( self::url( 'dashboard' ) ); ?>">پاک‌کردن</a><?php endif; ?>
		</form>

		<div class="szc-manager-kpis">
			<?php echo self::stat_card( 'لیدهای بازه', szc_fa_digits( $metrics['leads'] ), 'users', self::url( 'contacts' ), 'blue' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo self::stat_card( 'بدون مسئول', szc_fa_digits( $metrics['unassigned'] ), 'user-plus', self::url( 'contacts' ), $metrics['unassigned'] ? 'red' : 'green' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo self::stat_card( 'لید راکد', szc_fa_digits( $metrics['stagnant'] ), 'clock', self::url( 'contacts' ), $metrics['stagnant'] ? 'amber' : 'green' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo self::stat_card( 'پیگیری عقب‌افتاده', szc_fa_digits( $metrics['overdue'] ), 'bell', self::url( 'followups' ), $metrics['overdue'] ? 'red' : 'green' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo self::stat_card( 'پاسخ زیر ۱ ساعت', szc_fa_digits( $metrics['sla_rate'] ) . '٪', 'check-circle', self::url( 'reports' ), 'cyan' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo self::stat_card( 'پیش‌بینی فروش', number_format_i18n( $metrics['forecast'], 0 ), 'target', self::url( 'kanban' ), 'green' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>

		<div class="szc-manager-alerts" role="status">
			<span class="<?php echo $metrics['overdue'] ? 'is-danger' : 'is-ok'; ?>"><?php echo szc_icon( $metrics['overdue'] ? 'bell' : 'check-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo $metrics['overdue'] ? esc_html( szc_fa_digits( $metrics['overdue'] ) . ' پیگیری عقب‌افتاده نیازمند رسیدگی است' ) : 'پیگیری عقب‌افتاده‌ای وجود ندارد'; ?></span>
			<span class="<?php echo $queue['failed'] ? 'is-danger' : 'is-ok'; ?>"><?php echo szc_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> صف پیامک: <?php echo esc_html( szc_fa_digits( $queue['pending'] ) ); ?> در انتظار، <?php echo esc_html( szc_fa_digits( $queue['failed'] ) ); ?> ناموفق</span>
			<span><?php echo szc_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> میانگین اولین تماس: <?php echo esc_html( szc_fa_digits( $metrics['avg_first_minutes'] ) ); ?> دقیقه</span>
		</div>

		<section class="szc-p-card szc-manager-team">
			<div class="szc-p-cardhead"><div><span class="szc-p-cardicon"><?php echo szc_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><h2 class="szc-p-h2">عملکرد کارشناسان</h2><p>مقایسه بر اساس خروجی واقعی بازه و هدف امروز؛ نه فقط تعداد تماس.</p></div></div></div>
			<div class="szc-p-tablewrap"><table class="szc-p-table"><thead><tr><th>کارشناس</th><th>هدف امروز</th><th>تماس</th><th>نرخ پاسخ</th><th>پیگیری انجام‌شده</th><th>فروش</th><th>مخاطب جاری</th><th>عقب‌افتاده</th><th>آخرین فعالیت</th></tr></thead><tbody>
			<?php if ( ! $team ) : ?><tr><td colspan="9">برای این فیلتر کارشناسی پیدا نشد.</td></tr><?php else : foreach ( $team as $member ) : $r = $member['range']; $d = $member['today']; ?>
				<tr><td data-th="کارشناس"><b><?php echo esc_html( $member['agent']->name ); ?></b></td><td data-th="هدف امروز"><span class="szc-manager-progress"><i style="width:<?php echo esc_attr( min( 100, (int) $d['progress'] ) ); ?>%"></i></span><small><?php echo esc_html( szc_fa_digits( $d['answered'] ) . '/' . szc_fa_digits( $d['target'] ) ); ?></small></td><td data-th="تماس"><?php echo esc_html( szc_fa_digits( $r['calls'] ) ); ?></td><td data-th="نرخ پاسخ"><?php echo esc_html( szc_fa_digits( $r['answer_rate'] ) ); ?>٪</td><td data-th="پیگیری"><?php echo esc_html( szc_fa_digits( $r['followups_done'] ) ); ?></td><td data-th="فروش"><?php echo esc_html( szc_fa_digits( $r['won_contacts'] ) ); ?><small class="szc-manager-money"><?php echo esc_html( number_format_i18n( $r['revenue'], 0 ) ); ?></small></td><td data-th="مخاطب جاری"><?php echo esc_html( szc_fa_digits( $r['assigned'] ) ); ?></td><td data-th="عقب‌افتاده"><span class="<?php echo $r['overdue'] ? 'szc-manager-bad' : 'szc-manager-good'; ?>"><?php echo esc_html( szc_fa_digits( $r['overdue'] ) ); ?></span></td><td data-th="آخرین فعالیت"><?php echo $r['last_activity'] ? esc_html( szc_format_mysql( $r['last_activity'] ) ) : '—'; ?></td></tr>
			<?php endforeach; endif; ?></tbody></table></div>
		</section>

		<div class="szc-p-grid2 szc-manager-lower">
			<section class="szc-p-card"><h2 class="szc-p-h2">عبور از مراحل فروش</h2><div class="szc-p-funnel"><?php $max = 1; foreach ( $funnel as $stage ) { $max = max( $max, $stage['count'] ); } foreach ( $funnel as $stage ) : $width = (int) round( $stage['count'] / $max * 100 ); ?><div class="szc-p-frow"><span class="szc-p-flabel"><?php echo esc_html( $stage['label'] ); ?><small><?php echo esc_html( szc_fa_digits( $stage['rate'] ) ); ?>٪ از مرحله قبل</small></span><span class="szc-p-fbar"><span class="szc-p-ffill" style="width:<?php echo esc_attr( max( 4, $width ) ); ?>%"></span></span><span class="szc-p-fcount"><?php echo esc_html( szc_fa_digits( $stage['count'] ) ); ?></span></div><?php endforeach; ?></div></section>
			<section class="szc-p-card"><div class="szc-p-cardhead"><div><span class="szc-p-cardicon"><?php echo szc_icon( 'history' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><h2 class="szc-p-h2">آخرین فعالیت تیم</h2><p>۲۰ رویداد آخر برای نظارت سریع</p></div></div></div><div class="szc-manager-feed"><?php if ( ! $activities ) : ?><p class="szc-p-empty">فعالیتی ثبت نشده است.</p><?php else : foreach ( $activities as $activity ) : $contact_name = trim( (string) $activity->first_name . ' ' . (string) $activity->last_name ) ?: szc_fa_digits( $activity->mobile ); ?><article><span class="szc-p-cardicon"><?php echo szc_icon( $activity->type === 'call' ? 'phone' : ( $activity->type === 'sms' ? 'mail' : 'clock' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><b><?php echo esc_html( $owner_map[ (int) $activity->user_id ] ?? 'کارشناس' ); ?> · <?php echo esc_html( $type_labels[ $activity->type ] ?? $activity->type ); ?></b><a href="<?php echo esc_url( self::contact_url( $activity->contact_id ) ); ?>"><?php echo esc_html( $contact_name ); ?></a><small><?php echo esc_html( wp_trim_words( $activity->body ?: $activity->outcome, 12 ) ); ?></small></div><time><?php echo esc_html( szc_format_mysql( $activity->created_at ) ); ?></time></article><?php endforeach; endif; ?></div></section>
		</div>
		<?php
		return ob_get_clean();
	}

	protected static function stat_card( $label, $value, $icon, $url = '', $tone = 'blue' ) {
		$tag = $url ? 'a' : 'div';
		$href = $url ? ' href="' . esc_url( $url ) . '"' : '';
		return '<' . $tag . ' class="szc-p-stat is-' . esc_attr( $tone ) . '"' . $href . '><span class="szc-p-stat-ico szc-p-stat-ico--' . esc_attr( $icon ) . '">' . szc_icon( $icon ) . '</span>'
			. '<span class="szc-p-stat-v">' . esc_html( $value ) . '</span>'
			. '<span class="szc-p-stat-l">' . esc_html( $label ) . '</span>'
			. ( $url ? '<span class="szc-p-stat-go">' . szc_icon( 'chevron-left' ) . '</span>' : '' )
			. '</' . $tag . '>';
	}

	/** ارسال یک‌جای پیام آماده یا متن آزاد به همه مخاطبین مجاز کارشناس. */
	protected static function view_broadcast() {
		if ( ! SZC_Auth::can( 'bulk_sms' ) ) {
			return '<div class="szc-p-empty-state"><p class="szc-p-empty">دسترسی ارسال پیامک گروهی برای شما فعال نیست.</p></div>';
		}
		$owner = SZC_Settings::scope_owner();
		$args  = array( 'opt_out' => '0' );
		if ( $owner ) { $args['owner'] = $owner; }
		$total      = count( SZC_Contacts::ids_matching( $args ) );
		$templates  = SZC_Templates::all_patterned();
		$stages     = SZC_Settings::stages();
		$priorities = SZC_Settings::priorities();
		$free_ready = SZC_SMS::free_text_ready();
		$default_type = $templates ? 'template' : 'free';
		ob_start(); ?>
		<div class="szc-p-report-head szc-broadcast-head"><div><span class="szc-p-eyebrow">ارسال به همه مخاطبین مجاز</span><h1 class="szc-p-h1">ارسال گروهی پیامک</h1><p class="szc-p-muted">پیام آماده یا متن آزاد را انتخاب کنید؛ لغو دریافت‌ها از شمارش حذف و شماره‌های لیست سیاه هنگام تشکیل صف کنار گذاشته می‌شوند.</p></div><span class="szc-broadcast-total"><b data-broadcast-live-count><?php echo esc_html( szc_fa_digits( $total ) ); ?></b><small>مخاطب منتخب</small></span></div>
		<?php if ( ! SZC_SMS::enabled() ) : ?><div class="szc-p-notice is-danger">سرویس پیامک فعال نیست. از مدیریت بخواهید اتصال SMS.ir را تکمیل کند.</div><?php endif; ?>
		<form class="szc-portal-broadcast" data-portal-broadcast data-total="<?php echo (int) $total; ?>">
			<section class="szc-p-card">
				<div class="szc-p-cardhead"><div><span class="szc-p-cardicon"><?php echo szc_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><h2 class="szc-p-h2">۱. گیرندگان</h2><p>پیش‌فرض روی همه مخاطبین مجاز شماست.</p></div></div></div>
				<div class="szc-broadcast-audience">
					<label><span>مرحله فروش</span><select data-broadcast-stage><option value="">همه مراحل</option><?php foreach ( $stages as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span>اولویت</span><select data-broadcast-priority><option value="">همه اولویت‌ها</option><?php foreach ( $priorities as $key => $meta ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $meta['label'] ); ?></option><?php endforeach; ?></select></label>
				</div>
				<p class="szc-p-muted">اگر فیلتری انتخاب نکنید، پیام برای همه <?php echo esc_html( szc_fa_digits( $total ) ); ?> مخاطب مجاز در صف قرار می‌گیرد.</p>
			</section>

			<section class="szc-p-card">
				<div class="szc-p-cardhead"><div><span class="szc-p-cardicon"><?php echo szc_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><h2 class="szc-p-h2">۲. نوع پیام</h2><p>یکی از پیام‌های آماده یا متن دلخواه خودتان</p></div></div></div>
				<div class="szc-broadcast-types" role="radiogroup" aria-label="نوع پیام">
					<label><input type="radio" name="broadcast_type" value="template" <?php checked( $default_type, 'template' ); ?> <?php disabled( ! $templates ); ?>><span><b>پیام آماده</b><small>پترن تأییدشده با متغیر نام و اطلاعات مخاطب</small></span></label>
					<label><input type="radio" name="broadcast_type" value="free" <?php checked( $default_type, 'free' ); ?> <?php disabled( ! $free_ready ); ?>><span><b>متن آزاد</b><small>ارسال از خط اختصاصی ثبت‌شده در SMS.ir</small></span></label>
				</div>
				<div class="szc-broadcast-compose" data-broadcast-panel="template" <?php echo $default_type !== 'template' ? 'hidden' : ''; ?>>
					<?php if ( $templates ) : ?><label><span>انتخاب پیام آماده</span><select data-broadcast-template><?php foreach ( $templates as $template ) : ?><option value="<?php echo (int) $template->id; ?>" data-body="<?php echo esc_attr( wp_strip_all_tags( $template->body ) ); ?>"><?php echo esc_html( $template->name ); ?></option><?php endforeach; ?></select></label><div class="szc-broadcast-preview" data-broadcast-template-preview><?php echo esc_html( wp_strip_all_tags( $templates[0]->body ) ); ?></div><?php else : ?><p class="szc-p-empty">پیام آماده‌ای تعریف نشده است.</p><?php endif; ?>
				</div>
				<div class="szc-broadcast-compose" data-broadcast-panel="free" <?php echo $default_type !== 'free' ? 'hidden' : ''; ?>>
					<?php if ( $free_ready ) : ?><label><span>متن پیام</span><textarea data-broadcast-text rows="5" maxlength="1000" placeholder="متن پیام را بنویسید…"></textarea></label><div class="szc-broadcast-meta"><span><b data-broadcast-chars>۰</b> نویسه</span><span>حدود <b data-broadcast-parts>۱</b> بخش برای هر نفر</span></div><p class="szc-p-muted">متغیرهای قابل استفاده: <code>%first%</code>، <code>%last%</code>، <code>%name%</code>، <code>%company%</code> و <code>%city%</code>.</p><?php else : ?><div class="szc-p-notice is-warning">برای متن آزاد، مدیریت باید «شماره خط ارسال‌کننده» را در تنظیمات پیامک وارد کند.</div><?php endif; ?>
				</div>
			</section>

			<section class="szc-p-card szc-broadcast-confirm"><div><h2 class="szc-p-h2">۳. بررسی و ارسال</h2><p class="szc-p-muted">ارسال از طریق صف و در ساعات مجاز انجام می‌شود.</p></div><button type="submit" class="szc-p-btn szc-p-btn-primary" <?php disabled( ! SZC_SMS::enabled() || $total < 1 ); ?>><?php echo szc_icon( 'send' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>ارسال برای همه مخاطبین</span></button></section>
		</form>
		<?php
		return ob_get_clean();
	}

	protected static function view_kanban() {
		$owner = SZC_Settings::scope_owner();
		$stages = SZC_Settings::stages();
		ob_start(); ?>
		<div class="szc-p-report-head"><h1 class="szc-p-h1">کانبان قیف فروش</h1><p class="szc-p-muted">کارت مخاطب را بکشید و در مرحله مقصد رها کنید.</p></div>
		<div class="szc-kanban" data-kanban>
			<?php foreach ( $stages as $stage => $label ) :
				$result = SZC_Contacts::query( array( 'stage' => $stage, 'owner' => $owner, 'per_page' => 100, 'orderby' => 'updated_at', 'order' => 'DESC' ) );
				$stage_color = self::stage_color( $stage ); ?>
				<section class="szc-kanban-col" style="--stage-color:<?php echo esc_attr( $stage_color ); ?>" data-drop-stage="<?php echo esc_attr( $stage ); ?>">
					<header><b><?php echo esc_html( $label ); ?></b><span><?php echo esc_html( szc_fa_digits( $result['total'] ) ); ?></span></header>
					<div class="szc-kanban-cards">
						<?php foreach ( $result['items'] as $c ) : $next = SZC_Workspace::next_action( $c ); ?>
							<article class="szc-kanban-card" draggable="true" data-kanban-contact="<?php echo (int) $c->id; ?>">
								<a href="<?php echo esc_url( self::contact_url( $c->id ) ); ?>"><b><?php echo esc_html( SZC_Contacts::full_name( $c ) ); ?></b></a>
								<small dir="ltr"><?php echo esc_html( szc_fa_digits( $c->mobile ) ); ?></small>
								<span class="szc-next-action"><?php echo esc_html( $next['label'] ); ?></span>
							</article>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
		<?php return ob_get_clean();
	}

	/* ---------- فهرست مخاطبین ---------- */

	/** رنگِ نماینده‌ی یک مرحله (برای نوارِ رنگیِ کارتِ مخاطب). */
	protected static function stage_color( $key ) {
		$neg = array( 'not_interested' => '#94a3b8', 'wrong' => '#ef4444', 'lost' => '#94a3b8', 'blacklist' => '#334155' );
		if ( isset( $neg[ $key ] ) ) {
			return $neg[ $key ];
		}
		$funnel = SZC_Settings::funnel_stage_keys();
		$i      = array_search( $key, $funnel, true );
		$pal    = array( '#3b82f6', '#0ea5e9', '#06b6d4', '#14b8a6', '#10b981', '#22c55e', '#16a34a' );
		if ( $i === false ) {
			return '#0f9fb3';
		}
		$n   = max( 1, count( $funnel ) - 1 );
		$idx = (int) round( $i / $n * ( count( $pal ) - 1 ) );
		return $pal[ max( 0, min( count( $pal ) - 1, $idx ) ) ];
	}

	/** رندر بازگشتیِ درختِ پوشه‌ها. */
	protected static function folder_tree_html( $nodes, $active ) {
		if ( ! $nodes ) {
			return '';
		}
		$out = '<ul class="szc-p-ftree">';
		foreach ( $nodes as $n ) {
			$on   = ( (int) $active === (int) $n['id'] );
			$out .= '<li class="szc-p-fnode">';
			$out .= '<div class="szc-p-frow' . ( $on ? ' is-active' : '' ) . '">';
			$out .= '<a class="szc-p-flink" data-drop-group="' . (int) $n['id'] . '" href="' . esc_url( self::url( 'contacts', array( 'group' => $n['id'] ) ) ) . '">' . szc_icon( 'folder' ) . '<span class="szc-p-fname">' . esc_html( $n['name'] ) . '</span><span class="szc-p-fcount">' . esc_html( szc_fa_digits( $n['count'] ) ) . '</span></a>';
			$out .= '<span class="szc-p-fops">';
			$out .= '<button type="button" class="szc-p-iconbtn" data-folder-act="create" data-parent="' . (int) $n['id'] . '" title="زیرپوشه" aria-label="زیرپوشه">' . szc_icon( 'plus' ) . '</button>';
			$out .= '<button type="button" class="szc-p-iconbtn" data-folder-act="rename" data-id="' . (int) $n['id'] . '" data-name="' . esc_attr( $n['name'] ) . '" title="تغییر نام" aria-label="تغییر نام">' . szc_icon( 'edit' ) . '</button>';
			$out .= '<button type="button" class="szc-p-iconbtn szc-p-danger" data-folder-act="delete" data-id="' . (int) $n['id'] . '" title="حذف" aria-label="حذف">' . szc_icon( 'trash' ) . '</button>';
			$out .= '</span></div>';
			$out .= self::folder_tree_html( $n['children'], $active );
			$out .= '</li>';
		}
		$out .= '</ul>';
		return $out;
	}

	protected static function view_contacts() {
		$owner  = SZC_Settings::scope_owner();
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore
		$stage  = isset( $_GET['stage'] ) ? sanitize_key( wp_unslash( $_GET['stage'] ) ) : ''; // phpcs:ignore
		$prio   = isset( $_GET['priority'] ) ? sanitize_key( wp_unslash( $_GET['priority'] ) ) : ''; // phpcs:ignore
		$due    = isset( $_GET['due'] ) ? sanitize_key( wp_unslash( $_GET['due'] ) ) : ''; // phpcs:ignore
		$sort   = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'recent'; // phpcs:ignore
		$group  = isset( $_GET['group'] ) ? absint( $_GET['group'] ) : 0; // phpcs:ignore
		$page   = isset( $_GET['pn'] ) ? max( 1, absint( $_GET['pn'] ) ) : 1; // phpcs:ignore

		$sortmap = array(
			'recent'   => array( 'updated_at', 'DESC', 'جدیدترین' ),
			'name'     => array( 'first_name', 'ASC', 'نام (الفبا)' ),
			'priority' => array( 'priority', 'ASC', 'اولویت' ),
			'oldcall'  => array( 'last_contacted_at', 'ASC', 'قدیمی‌ترین تماس' ),
		);
		if ( ! isset( $sortmap[ $sort ] ) ) { $sort = 'recent'; }
		$so = $sortmap[ $sort ];

		$res = SZC_Contacts::query( array(
			'search' => $search, 'stage' => $stage, 'priority' => $prio, 'group' => $group, 'due' => $due,
			'owner' => $owner, 'page' => $page, 'per_page' => 25, 'orderby' => $so[0], 'order' => $so[1],
		) );
		$stages    = SZC_Settings::stages();
		$prios     = SZC_Settings::priorities();
		$templates = SZC_Templates::all_patterned();
		$pages     = max( 1, (int) ceil( $res['total'] / $res['per_page'] ) );
		$title     = $group ? SZC_Groups::name( $group ) : 'مخاطبین';

		// پارامترهای پایدارِ فیلتر برای ساخت لینک‌ها (چیپ‌ها، صفحه‌بندی، بارگذاری بیشتر).
		$keep = array_filter( array( 's' => $search, 'stage' => $stage, 'group' => $group, 'sort' => ( $sort !== 'recent' ? $sort : '' ) ) );
		$chip_url = function ( $p, $d = '' ) use ( $keep ) {
			return self::url( 'contacts', array_filter( array_merge( $keep, array( 'priority' => $p, 'due' => $d ) ), 'strlen' ) );
		};

		ob_start(); ?>
		<div class="szc-p-folderlayout">
			<aside class="szc-p-folders is-collapsed" data-folders>
				<div class="szc-p-folders-head">
					<button type="button" class="szc-p-foldertoggle" data-folders-toggle aria-expanded="false"><?php echo szc_icon( 'folder' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>پوشه‌ها</span><small>نمایش و مدیریت</small><?php echo szc_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					<button type="button" class="szc-p-iconbtn szc-p-iconbtn--text" data-folder-act="create" data-parent="0" title="پوشه‌ی جدید"><?php echo szc_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>پوشه</span></button>
				</div>
				<div class="szc-p-folders-body">
					<a class="szc-p-flink szc-p-frootlink<?php echo $group === 0 ? ' is-active' : ''; ?>" data-drop-group="0" href="<?php echo esc_url( self::url( 'contacts' ) ); ?>"><?php echo szc_icon( 'idcard' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="szc-p-fname">همه‌ی مخاطبین</span></a>
					<?php echo self::folder_tree_html( SZC_Groups::tree(), $group ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</aside>

			<div class="szc-p-contactsmain">
				<div class="szc-p-listhead">
					<h1 class="szc-p-h1"><?php echo esc_html( $title ); ?> <span class="szc-p-count"><?php echo esc_html( szc_fa_digits( $res['total'] ) ); ?></span></h1>
					<a class="szc-p-btn szc-p-btn-primary" data-view="add" href="<?php echo esc_url( self::url( 'add' ) ); ?>"><?php echo szc_icon( 'user-plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>افزودن مخاطب</span></a>
				</div>

				<form class="szc-p-filters" method="get" action="<?php echo esc_url( self::page_url() ); ?>">
					<input type="hidden" name="szc_view" value="contacts">
					<?php if ( $group ) : ?><input type="hidden" name="group" value="<?php echo (int) $group; ?>"><?php endif; ?>
					<label class="szc-p-filterfield is-search"><span>جستجو</span><input type="search" name="s" placeholder="نام، موبایل، شرکت یا فیلد سفارشی…" value="<?php echo esc_attr( $search ); ?>" data-live-search autocomplete="off"></label>
					<label class="szc-p-filterfield"><span>مرحله فروش</span><select name="stage" data-autosubmit>
							<option value="">همه‌ی مراحل</option>
							<?php foreach ( $stages as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $stage, $k ); ?>><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
						</select></label>
					<label class="szc-p-filterfield"><span>مرتب‌سازی</span><select name="sort" data-autosubmit>
							<?php foreach ( $sortmap as $sk => $sv ) : ?><option value="<?php echo esc_attr( $sk ); ?>" <?php selected( $sort, $sk ); ?>><?php echo esc_html( $sv[2] ); ?></option><?php endforeach; ?>
						</select></label>
					<button class="szc-p-btn" type="submit">فیلتر</button>
					<?php if ( $search || $stage || $prio || $due || $sort !== 'recent' ) : ?><a class="szc-p-clearfilters" href="<?php echo esc_url( self::url( 'contacts', $group ? array( 'group' => $group ) : array() ) ); ?>">پاک‌کردن فیلترها</a><?php endif; ?>
				</form>

				<div class="szc-p-chips" role="tablist">
					<a class="szc-p-fchip<?php echo ( ! $prio && ! $due ) ? ' is-active' : ''; ?>" href="<?php echo esc_url( $chip_url( '', '' ) ); ?>">همه</a>
					<?php foreach ( $prios as $pk => $pmv ) : ?>
						<a class="szc-p-fchip<?php echo $prio === $pk ? ' is-active' : ''; ?>" style="--c:<?php echo esc_attr( $pmv['color'] ); ?>" href="<?php echo esc_url( $chip_url( $pk, '' ) ); ?>"><span class="szc-p-fchip-dot"></span><?php echo esc_html( $pmv['label'] ); ?></a>
					<?php endforeach; ?>
					<a class="szc-p-fchip szc-p-fchip-due<?php echo $due === 'overdue' ? ' is-active' : ''; ?>" href="<?php echo esc_url( $chip_url( '', 'overdue' ) ); ?>"><?php echo szc_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>سررسیده</a>
				</div>

				<div class="szc-p-selbar" data-f-s="<?php echo esc_attr( $search ); ?>" data-f-stage="<?php echo esc_attr( $stage ); ?>" data-f-priority="<?php echo esc_attr( $prio ); ?>" data-f-due="<?php echo esc_attr( $due ); ?>" data-f-group="<?php echo (int) $group; ?>" data-total="<?php echo (int) $res['total']; ?>">
					<button type="button" class="szc-p-btn szc-p-selbtn" data-sel-toggle><?php echo szc_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>انتخاب گروهی</span></button>
					<div class="szc-p-selactions" hidden>
						<label class="szc-p-selchk"><input type="checkbox" data-sel-all> این صفحه</label>
						<span class="szc-p-selcount"><b data-sel-count>۰</b> انتخاب‌شده</span>
						<?php if ( $templates ) : ?>
							<span class="szc-p-selgroup">
								<select data-sel-tpl aria-label="قالب پیامک">
									<?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>" data-body="<?php echo esc_attr( wp_strip_all_tags( $t->body ) ); ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?>
								</select>
								<button type="button" class="szc-p-btn" data-sel-act="sms"><?php echo szc_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>پیامک</span></button>
							</span>
						<?php endif; ?>
						<span class="szc-p-selgroup">
							<select data-sel-folder aria-label="انتقال به پوشه">
								<option value="">— انتقال به پوشه —</option>
								<option value="0">بدون پوشه (ریشه)</option>
								<?php foreach ( SZC_Groups::all() as $g ) : ?><option value="<?php echo (int) $g->id; ?>"><?php echo esc_html( $g->name ); ?></option><?php endforeach; ?>
							</select>
							<button type="button" class="szc-p-btn" data-sel-act="move"><?php echo szc_icon( 'folder' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>انتقال</span></button>
						</span>
						<label class="szc-p-selchk szc-p-selscope"><input type="checkbox" data-sel-scope-all> روی کلِ نتایج (<?php echo esc_html( szc_fa_digits( $res['total'] ) ); ?>)</label>
					</div>
				</div>

				<?php if ( ! $res['items'] ) : ?>
					<div class="szc-p-empty-state">
						<span class="szc-p-empty-ico"><?php echo szc_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<p class="szc-p-empty">مخاطبی یافت نشد.</p>
						<a class="szc-p-btn szc-p-btn-primary" data-view="add" href="<?php echo esc_url( self::url( 'add' ) ); ?>"><?php echo szc_icon( 'user-plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>افزودن مخاطب</span></a>
					</div>
				<?php else : ?>
					<div class="szc-p-listwrap">
						<ul class="szc-p-clist" data-list>
							<?php foreach ( $res['items'] as $c ) { echo self::contact_row_html( $c ); } // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</ul>
						<?php echo self::alpha_index_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>

					<?php if ( $page < $pages ) :
						$next = self::url( 'contacts', array_filter( array_merge( $keep, array( 'priority' => $prio, 'due' => $due, 'pn' => $page + 1 ) ), 'strlen' ) ); ?>
						<a class="szc-p-loadmore" data-loadmore data-next-page="<?php echo (int) ( $page + 1 ); ?>" data-total-pages="<?php echo (int) $pages; ?>" href="<?php echo esc_url( $next ); ?>"><?php echo szc_icon( 'repeat' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>بارگذاری بیشتر</span></a>
					<?php endif; ?>

					<?php if ( $pages > 1 ) : ?>
						<div class="szc-p-pager">
							<?php for ( $i = 1; $i <= $pages; $i++ ) :
								$url = self::url( 'contacts', array_filter( array_merge( $keep, array( 'priority' => $prio, 'due' => $due, 'pn' => $i ) ), 'strlen' ) );
								?>
								<a class="<?php echo $i === $page ? 'is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( szc_fa_digits( $i ) ); ?></a>
							<?php endfor; ?>
						</div>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<a class="szc-p-fab" data-view="add" href="<?php echo esc_url( self::url( 'add' ) ); ?>" aria-label="افزودن مخاطب" title="افزودن مخاطب"><?php echo szc_icon( 'user-plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		</div>
		<?php
		return ob_get_clean();
	}

	/** یک ردیفِ کارتِ مخاطب (چهره‌ی لغزنده برای Swipe + نوارِ رنگیِ مرحله). */
	protected static function contact_row_html( $c ) {
		$pm    = SZC_Settings::priority_meta( $c->priority );
		$cname = SZC_Contacts::full_name( $c );
		$sc    = self::stage_color( $c->stage );
		$next  = SZC_Workspace::next_action( $c );
		ob_start(); ?>
		<li class="szc-p-crow" draggable="true" data-contact="<?php echo (int) $c->id; ?>" data-mobile="<?php echo esc_attr( $c->mobile ); ?>" data-name="<?php echo esc_attr( $cname ); ?>" style="--stage-c:<?php echo esc_attr( $sc ); ?>;--c:<?php echo esc_attr( $pm['color'] ); ?>">
			<div class="szc-p-swipeback" aria-hidden="true">
				<a class="szc-p-swa szc-p-swa-follow" href="<?php echo esc_url( self::contact_url( $c->id ) ); ?>"><?php echo szc_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>پیگیری</span></a>
				<a class="szc-p-swa szc-p-swa-sms" href="<?php echo esc_url( self::contact_url( $c->id ) ); ?>"><?php echo szc_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>پیامک</span></a>
			</div>
			<div class="szc-p-cface">
				<label class="szc-p-selcb"><input type="checkbox" data-sel-cb value="<?php echo (int) $c->id; ?>" aria-label="انتخاب مخاطب"></label>
				<span class="szc-p-grip" title="بکشید و روی یک پوشه رها کنید" aria-hidden="true"><?php echo szc_icon( 'grip' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<a class="szc-p-cmain" draggable="false" href="<?php echo esc_url( self::contact_url( $c->id ) ); ?>">
					<span class="szc-p-cav"><?php echo esc_html( mb_substr( $cname, 0, 1 ) ); ?></span>
					<span class="szc-p-cbody">
						<span class="szc-p-cname"><?php echo esc_html( $cname ); ?><?php echo $c->opt_out ? ' <span class="szc-p-tag szc-p-tag-red">لغو پیامک</span>' : ''; ?></span>
						<span class="szc-p-csub">
							<span class="szc-p-cnum" dir="ltr"><?php echo esc_html( szc_fa_digits( $c->mobile ) ); ?></span>
							<?php if ( ! empty( $c->company ) ) : ?><span class="szc-p-company"><?php echo esc_html( $c->company ); ?></span><?php endif; ?>
							<span class="szc-p-stage"><?php echo esc_html( SZC_Settings::stage_label( $c->stage ) ); ?></span>
							<span class="szc-p-prio"><?php echo esc_html( $pm['label'] ); ?></span>
							<span class="szc-next-action"><?php echo esc_html( $next['label'] ); ?></span>
						</span>
					</span>
				</a>
				<a draggable="false" class="szc-p-callbtn" data-call data-reserve-call data-contact="<?php echo (int) $c->id; ?>" href="tel:<?php echo esc_attr( $c->mobile ); ?>" title="تماس با <?php echo esc_attr( $cname ); ?>" aria-label="تماس با <?php echo esc_attr( $cname ); ?>"><?php echo szc_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>تماس</span></a>
			</div>
		</li>
		<?php
		return ob_get_clean();
	}

	/** ایندکسِ حروفِ الفبا (پرش سریع در فهرست، مثلِ اپ مخاطبین). */
	protected static function alpha_index_html() {
		$letters = array( 'آ','ا','ب','پ','ت','ث','ج','چ','ح','خ','د','ذ','ر','ز','س','ش','ص','ط','ع','ف','ق','ک','گ','ل','م','ن','و','ه','ی' );
		$out = '<nav class="szc-p-alpha" aria-label="پرش الفبایی">';
		foreach ( $letters as $l ) {
			$out .= '<button type="button" class="szc-p-alpha-l" data-letter="' . esc_attr( $l ) . '">' . esc_html( $l ) . '</button>';
		}
		$out .= '</nav>';
		return $out;
	}

	/* ---------- پرونده‌ی مخاطب ---------- */

	protected static function view_contact() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore
		$c  = $id ? SZC_Contacts::get( $id ) : null;
		if ( ! $c ) {
			return '<p class="szc-p-empty">مخاطب یافت نشد. <a href="' . esc_url( self::url( 'contacts' ) ) . '">بازگشت</a></p>';
		}
		if ( ! SZC_Auth::can_access_contact( $c ) ) {
			return '<p class="szc-p-empty">به این مخاطب دسترسی ندارید.</p>';
		}

		$pm          = SZC_Settings::priority_meta( $c->priority );
		$stages      = SZC_Settings::stages();
		$prios       = SZC_Settings::priorities();
		$outcomes    = SZC_Settings::call_outcomes();
		$templates   = SZC_Templates::all_patterned();
		$timeline    = SZC_Activity::timeline( $c->id );
		$is_manager  = SZC_Settings::is_manager();
		$assignees   = SZC_Settings::assignable_users();
		$sequences   = SZC_Sequences::active_sequences();
		$enrollments = SZC_Sequences::enrollments_for_contact( $c->id );
		$blocked     = SZC_Blacklist::is_blocked( $c->mobile );
		$customs     = SZC_Settings::custom_fields();
		$cmeta       = SZC_Contacts::get_meta( $c );
		$next_action = SZC_Workspace::next_action( $c );

		ob_start(); ?>
		<div class="szc-single" data-contact="<?php echo (int) $c->id; ?>">
			<div class="szc-p-crumb"><a href="<?php echo esc_url( self::url( 'contacts' ) ); ?>"><?php echo szc_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>مخاطبین</span></a></div>

			<div class="szc-p-chead">
				<div class="szc-p-cavatar" style="--c:<?php echo esc_attr( $pm['color'] ); ?>"><?php echo esc_html( mb_substr( SZC_Contacts::full_name( $c ), 0, 1 ) ); ?></div>
				<div class="szc-p-cinfo">
					<h1 class="szc-p-h1"><?php echo esc_html( SZC_Contacts::full_name( $c ) ); ?></h1>
					<div class="szc-p-cmeta">
						<a href="tel:<?php echo esc_attr( $c->mobile ); ?>" dir="ltr" class="szc-p-phone"><?php echo szc_icon( 'smartphone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( szc_fa_digits( $c->mobile ) ); ?></span></a>
						<?php if ( $c->company ) : ?><span><?php echo szc_icon( 'briefcase' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $c->company ); ?></span><?php endif; ?>
						<?php if ( $c->city ) : ?><span><?php echo szc_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $c->city ); ?></span><?php endif; ?>
					</div>
				</div>
				<div class="szc-p-cquick">
					<?php $wa = '98' . ltrim( preg_replace( '/\D+/', '', (string) $c->mobile ), '0' ); ?>
					<a class="szc-p-callbig" data-reserve-call data-contact="<?php echo (int) $c->id; ?>" href="tel:<?php echo esc_attr( $c->mobile ); ?>"><?php echo szc_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>تماس</span></a>
					<a class="szc-p-wabtn" data-external="whatsapp" data-contact="<?php echo (int) $c->id; ?>" href="https://wa.me/<?php echo esc_attr( $wa ); ?>" target="_blank" rel="noopener" title="واتساپ" aria-label="واتساپ"><?php echo szc_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<a class="szc-p-tgbtn" data-external="telegram" data-contact="<?php echo (int) $c->id; ?>" href="tg://resolve?phone=<?php echo esc_attr( $wa ); ?>" title="تلگرام" aria-label="تلگرام"><?php echo szc_icon( 'telegram' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<select data-szc-act="set_field" data-field="stage" class="szc-p-sel">
						<?php foreach ( $stages as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $c->stage, $k ); ?>><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
					</select>
					<select data-szc-act="set_field" data-field="priority" class="szc-p-sel">
						<?php foreach ( $prios as $k => $m ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $c->priority, $k ); ?>><?php echo esc_html( $m['label'] ); ?></option><?php endforeach; ?>
					</select>
					<?php $folders = SZC_Groups::all(); if ( $folders ) : ?>
						<select data-szc-act="set_group" class="szc-p-sel" title="انتقال به پوشه">
							<option value="0" <?php selected( (int) $c->group_id, 0 ); ?>>بدون پوشه</option>
							<?php foreach ( $folders as $g ) : ?><option value="<?php echo (int) $g->id; ?>" <?php selected( (int) $c->group_id, (int) $g->id ); ?>><?php echo esc_html( $g->name ); ?></option><?php endforeach; ?>
						</select>
					<?php endif; ?>
				</div>
			</div>
			<div class="szc-p-card"><b>بهترین اقدام بعدی: <?php echo esc_html( $next_action['label'] ); ?></b><p class="szc-p-muted"><?php echo esc_html( $next_action['reason'] ); ?></p></div>

			<?php echo SZC_Conversation::render( $c, 'portal' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<div class="szc-p-grid2">
				<div class="szc-p-col">
					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>ثبت تماس</span></h2>
						<div class="szc-p-row">
							<label class="szc-p-control"><span>نتیجه تماس</span><select data-call-outcome>
									<?php foreach ( $outcomes as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
								</select></label>
							<label class="szc-p-control"><span>پیامک بعد از تماس</span><select data-call-template title="پیامکِ همراهِ این تماس (موفق یا ناموفق)">
									<option value="0">پیش‌فرض خودکار</option>
									<option value="-1">بدون پیامک</option>
									<?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?>
								</select></label>
						</div>
						<label class="szc-p-control"><span>خلاصه مکالمه <small>برای بی‌علاقه یا شماره اشتباه الزامی است</small></span><textarea data-call-note rows="2" placeholder="نکته مهم یا نتیجه گفتگو را کوتاه بنویسید…"></textarea></label>
						<div class="szc-p-row">
							<label class="szc-p-control"><span>زمان پیگیری بعدی <small>اختیاری</small></span><input type="datetime-local" data-call-followup-at></label>
							<label class="szc-p-control"><span>موضوع پیگیری <small>اختیاری</small></span><input type="text" data-call-followup-note placeholder="مثلاً بررسی پیش‌فاکتور"></label>
						</div>
						<button class="szc-p-btn szc-p-btn-primary szc-p-btn-block" data-szc-act="log_call"><?php echo szc_icon( 'check-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>ثبت نتیجه تماس</span></button>
					</section>

					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>پیگیری بعدی</span></h2>
						<div class="szc-p-presets">
							<button type="button" class="szc-p-chip" data-preset="tomorrow10">فردا ۱۰ صبح</button>
							<button type="button" class="szc-p-chip" data-preset="today17">امروز ۱۷</button>
							<button type="button" class="szc-p-chip" data-preset="d3">۳ روز دیگر</button>
							<button type="button" class="szc-p-chip" data-preset="week">هفته بعد</button>
						</div>
						<div class="szc-p-row">
							<span class="szc-jp" data-jp="datetime">
								<input type="text" class="szc-jp-disp" readonly placeholder="زمان پیگیری (تقویم شمسی)">
								<input type="hidden" class="szc-jp-val" data-followup-at>
							</span>
							<input type="text" data-followup-note placeholder="موضوع (اختیاری)">
						</div>
						<button class="szc-p-btn" data-szc-act="add_followup">ثبت پیگیری</button>
					</section>

					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>پیامک</span></h2>
						<?php if ( $templates ) : ?>
							<div class="szc-p-row">
								<select data-sms-template>
									<?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?>
								</select>
							</div>
							<div class="szc-p-btnrow">
								<button class="szc-p-btn szc-p-btn-primary" data-szc-act="send_sms">ارسال فوری</button>
								<button class="szc-p-btn" data-szc-act="schedule_sms">زمان‌بندی</button>
							</div>
						<?php else : ?>
							<p class="szc-p-muted">قالبی تعریف نشده است.</p>
						<?php endif; ?>
						<p class="szc-p-muted">ارسال فقط از قالب‌های پترن‌دار و تأییدشده انجام می‌شود.</p>
						<?php if ( $c->opt_out ) : ?><p class="szc-p-muted">این مخاطب لغو دریافت پیامک دارد.</p><?php endif; ?>
					</section>

					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'file-text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>یادداشت</span></h2>
						<textarea data-note-body rows="2" placeholder="یادداشت درباره‌ی این مخاطب…"></textarea>
						<button class="szc-p-btn" data-szc-act="add_note">افزودن یادداشت</button>
					</section>
				</div>

				<div class="szc-p-col">
					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>اطلاعات</span></h2>
						<div class="szc-p-form2">
							<label>نام<input type="text" data-f="first_name" value="<?php echo esc_attr( $c->first_name ); ?>"></label>
							<label>نام خانوادگی<input type="text" data-f="last_name" value="<?php echo esc_attr( $c->last_name ); ?>"></label>
							<label>موبایل<input type="text" dir="ltr" data-f="mobile" value="<?php echo esc_attr( $c->mobile ); ?>"></label>
							<label>شغل<input type="text" data-f="job" value="<?php echo esc_attr( $c->job ); ?>"></label>
							<label>شرکت<input type="text" data-f="company" value="<?php echo esc_attr( $c->company ); ?>"></label>
							<label>شهر<input type="text" data-f="city" value="<?php echo esc_attr( $c->city ); ?>"></label>
							<label>ایمیل<input type="email" dir="ltr" data-f="email" value="<?php echo esc_attr( $c->email ); ?>"></label>
							<label>منبع<input type="text" data-f="source" value="<?php echo esc_attr( $c->source ); ?>"></label>
							<label>کمپین<input type="text" data-f="campaign" value="<?php echo esc_attr( $c->campaign ?? '' ); ?>"></label>
							<label>ارزش احتمالی (تومان)<input type="number" min="0" step="1" data-f="expected_value" value="<?php echo esc_attr( $c->expected_value ?? $c->deal_value ?? 0 ); ?>"></label>
							<label>مبلغ نهایی (تومان)<input type="number" min="0" step="1" data-f="final_value" value="<?php echo esc_attr( $c->final_value ?? 0 ); ?>"></label>
							<?php foreach ( $customs as $cf ) : ?>
								<label><?php echo esc_html( $cf['label'] ); ?><input type="text" data-cf="<?php echo esc_attr( $cf['key'] ); ?>" value="<?php echo esc_attr( $cmeta[ $cf['key'] ] ?? '' ); ?>"></label>
							<?php endforeach; ?>
							<?php if ( $is_manager && $assignees ) : ?>
								<label>کارشناس مسئول
									<select data-szc-act="set_field" data-field="owner">
										<option value="0">— بدون تخصیص —</option>
										<?php foreach ( $assignees as $uid => $nm ) : ?><option value="<?php echo (int) $uid; ?>" <?php selected( (int) $c->owner_id, (int) $uid ); ?>><?php echo esc_html( $nm ); ?></option><?php endforeach; ?>
									</select>
								</label>
							<?php endif; ?>
						</div>
						<div class="szc-p-btnrow">
							<button class="szc-p-btn szc-p-btn-primary" data-szc-act="save_contact">ذخیره</button>
							<?php if ( $blocked ) : ?>
								<button class="szc-p-btn" data-szc-act="blacklist" data-op="remove">خروج از لیست سیاه</button>
							<?php else : ?>
								<button class="szc-p-btn szc-p-btn-ghost" data-szc-act="blacklist" data-op="add">لیست سیاه</button>
							<?php endif; ?>
							<button class="szc-p-btn szc-p-btn-danger" data-szc-act="del_contact">حذف</button>
						</div>
					</section>

					<?php if ( $sequences ) : ?>
						<section class="szc-p-card">
							<h2 class="szc-p-h2"><?php echo szc_icon( 'repeat' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>دنباله پیامکی</span></h2>
							<div class="szc-p-row">
								<select data-seq>
									<?php foreach ( $sequences as $sq ) : ?><option value="<?php echo (int) $sq->id; ?>"><?php echo esc_html( $sq->name ); ?></option><?php endforeach; ?>
								</select>
								<button class="szc-p-btn" data-szc-act="enroll">ثبت در دنباله</button>
							</div>
							<?php if ( $enrollments ) : ?>
								<ul class="szc-p-enr">
									<?php foreach ( $enrollments as $en ) : ?><li><b><?php echo esc_html( $en->name ); ?></b> — <?php echo esc_html( $en->status === 'active' ? 'فعال' : ( $en->status === 'canceled' ? 'لغوشده' : $en->status ) ); ?></li><?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</section>
					<?php endif; ?>

					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'merge' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>ادغام تکراری</span></h2>
						<p class="szc-p-muted">اگر همین مخاطب رکوردِ تکراری دارد، موبایلِ رکورد دوم را وارد کنید تا در این مخاطب ادغام و سپس حذف شود.</p>
						<div class="szc-p-row">
							<input type="text" dir="ltr" data-merge-mobile placeholder="۰۹...">
							<button class="szc-p-btn" data-szc-act="merge">ادغام</button>
						</div>
					</section>

					<section class="szc-p-card">
						<h2 class="szc-p-h2"><?php echo szc_icon( 'history' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>تاریخچه</span></h2>
						<?php echo self::timeline_html( $timeline ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</section>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	protected static function timeline_html( $items ) {
		if ( ! $items ) {
			return '<p class="szc-p-muted">هنوز فعالیتی ثبت نشده است.</p>';
		}
		$type_lbl = array( 'note' => 'یادداشت', 'call' => 'تماس', 'sms' => 'پیامک', 'stage' => 'تغییر مرحله', 'followup' => 'پیگیری', 'external' => 'تعامل بیرونی' );
		$sms_lbl  = array( 'sent' => 'ارسال شد', 'failed' => 'ناموفق', 'scheduled' => 'زمان‌بندی شد' );
		$ico      = array( 'note' => 'file-text', 'call' => 'phone', 'sms' => 'mail', 'stage' => 'shuffle', 'followup' => 'calendar', 'external' => 'mail' );
		ob_start();
		echo '<ul class="szc-p-timeline">';
		foreach ( $items as $it ) {
			$who   = $it['user_id'] ? SZC_Auth::display_name( $it['user_id'] ) : '';
			$head  = $type_lbl[ $it['type'] ] ?? $it['type'];
			$extra = '';
			if ( $it['type'] === 'call' && $it['outcome'] ) {
				$extra = ' — ' . SZC_Settings::outcome_label( $it['outcome'] );
			} elseif ( $it['type'] === 'sms' && $it['outcome'] ) {
				$extra = ' — ' . ( $sms_lbl[ $it['outcome'] ] ?? $it['outcome'] );
			} elseif ( $it['type'] === 'followup' && $it['due_at'] ) {
				$extra = ' — سررسید: ' . szc_format_mysql( $it['due_at'] ) . ( $it['done'] ? ' (انجام شد)' : '' );
			}
			echo '<li class="szc-p-tl">';
			echo '<span class="szc-p-tlico">' . szc_icon( $ico[ $it['type'] ] ?? 'check' ) . '</span>';
			echo '<div class="szc-p-tlbody">';
			echo '<div class="szc-p-tlhead"><b>' . esc_html( $head . $extra ) . '</b><span class="szc-p-muted">' . esc_html( szc_time_ago( $it['created_at'] ) ) . ( $who ? ' · ' . esc_html( $who ) : '' ) . '</span></div>';
			if ( $it['body'] !== '' ) {
				echo '<p>' . nl2br( esc_html( $it['body'] ) ) . '</p>';
			}
			echo '<div class="szc-p-tlacts">';
			if ( $it['type'] === 'followup' && ! $it['done'] ) {
				echo '<button class="szc-p-linkbtn" data-szc-act="done_followup" data-id="' . (int) $it['id'] . '">علامت انجام‌شده</button>';
			}
			$del_act = $it['kind'] === 'note' ? 'del_note' : 'del_activity';
			echo '<button class="szc-p-linkbtn szc-p-danger" data-szc-act="' . esc_attr( $del_act ) . '" data-id="' . (int) $it['id'] . '">حذف</button>';
			echo '</div></div></li>';
		}
		echo '</ul>';
		return ob_get_clean();
	}

	/* ---------- افزودن مخاطب ---------- */

	protected static function view_add() {
		$prios   = SZC_Settings::priorities();
		$customs = SZC_Settings::custom_fields();
		ob_start(); ?>
		<h1 class="szc-p-h1">افزودن مخاطب</h1>
		<section class="szc-p-card szc-add-form" style="max-width:680px">
			<div class="szc-p-form2">
				<label>نام<input type="text" data-f="first_name"></label>
				<label>نام خانوادگی<input type="text" data-f="last_name"></label>
				<label>موبایل *<input type="text" dir="ltr" data-f="mobile" placeholder="۰۹۱۲..."></label>
				<label>شغل<input type="text" data-f="job"></label>
				<label>شرکت<input type="text" data-f="company"></label>
				<label>شهر<input type="text" data-f="city"></label>
				<label>ایمیل<input type="email" dir="ltr" data-f="email"></label>
				<label>منبع<input type="text" data-f="source"></label>
				<label>کمپین<input type="text" data-f="campaign"></label>
				<label>ارزش احتمالی<input type="number" min="0" data-f="expected_value"></label>
				<label>اولویت
					<select data-f="priority">
						<?php foreach ( $prios as $k => $m ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, 'warm' ); ?>><?php echo esc_html( $m['label'] ); ?></option><?php endforeach; ?>
					</select>
				</label>
				<?php foreach ( $customs as $cf ) : ?>
					<label><?php echo esc_html( $cf['label'] ); ?><input type="text" data-cf="<?php echo esc_attr( $cf['key'] ); ?>"></label>
				<?php endforeach; ?>
			</div>
			<button class="szc-p-btn szc-p-btn-primary" data-szc-act="add_contact">افزودن مخاطب</button>
		</section>
		<?php
		return ob_get_clean();
	}

	/* ---------- تماس پشت‌سرهم (Power Dialer) ---------- */

	protected static function view_dialer() {
		$owner = SZC_Settings::scope_owner();
		$keys  = SZC_Settings::funnel_stage_keys();
		$lead  = $keys ? $keys[0] : 'lead';
		$i     = isset( $_GET['i'] ) ? max( 0, absint( $_GET['i'] ) ) : 0; // phpcs:ignore

		// صف: ابتدا پیگیری‌های سررسیده (نزدیک‌ترین اول)، سپس لیدهای تماس‌نگرفته.
		$queue = array();
		$seen  = array();
		$why   = array();
		foreach ( SZC_Activity::due_followups( 100, $owner ) as $r ) {
			$cid = (int) $r->contact_id;
			if ( isset( $seen[ $cid ] ) ) { continue; }
			$c = SZC_Contacts::get( $cid );
			if ( ! $c ) { continue; }
			$seen[ $cid ] = true;
			$queue[]      = $c;
			$why[ $cid ]  = array( 'type' => 'followup', 'due' => $r->due_at, 'note' => $r->body );
		}
		$leads = SZC_Contacts::query( array( 'stage' => $lead, 'owner' => $owner, 'orderby' => 'last_contacted_at', 'order' => 'ASC', 'per_page' => 100 ) );
		foreach ( $leads['items'] as $c ) {
			if ( isset( $seen[ (int) $c->id ] ) ) { continue; }
			$seen[ (int) $c->id ] = true;
			$queue[]              = $c;
			$why[ (int) $c->id ]  = array( 'type' => 'lead' );
		}
		$total = count( $queue );

		ob_start(); ?>
		<h1 class="szc-p-h1"><?php echo szc_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>تماس پشت‌سرهم</span> <span class="szc-p-count"><?php echo esc_html( szc_fa_digits( $total ) ); ?></span></h1>
		<?php if ( ! $queue ) : ?>
			<div class="szc-p-dialer-empty">
				<span class="szc-p-empty-ico"><?php echo szc_icon( 'check-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<p class="szc-p-empty">صف تماسِ امروز خالی است — پیگیری سررسیده یا لیدِ تازه‌ای نیست.</p>
				<a class="szc-p-btn" href="<?php echo esc_url( self::url( 'contacts' ) ); ?>">مشاهده‌ی مخاطبین</a>
			</div>
		<?php else :
			$i = min( $i, count( $queue ) - 1 );
			$c = $queue[ $i ];
			$w = $why[ (int) $c->id ] ?? array( 'type' => 'lead' );
			$outcomes = SZC_Settings::call_outcomes();
			$templates = SZC_Templates::all_patterned();
			$pm = SZC_Settings::priority_meta( $c->priority );
			$latest_followup = SZC_Activity::latest_followup( $c->id );
			?>
			<div class="szc-single szc-p-dialer" data-contact="<?php echo (int) $c->id; ?>">
				<div class="szc-p-dialer-card">
					<div class="szc-p-dialer-progress"><?php echo esc_html( szc_fa_digits( $i + 1 ) . ' از ' . szc_fa_digits( $total ) ); ?></div>
					<?php if ( $w['type'] === 'followup' ) : ?>
						<div class="szc-p-dialer-why is-followup"><?php echo szc_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>پیگیری سررسیده: <?php echo esc_html( szc_format_mysql( $w['due'] ) ); ?><?php echo ! empty( $w['note'] ) ? ' — ' . esc_html( $w['note'] ) : ''; ?></div>
					<?php else : ?>
						<div class="szc-p-dialer-why is-lead"><?php echo szc_icon( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>لید جدید</div>
					<?php endif; ?>
					<div class="szc-p-dialer-avatar" style="--c:<?php echo esc_attr( $pm['color'] ); ?>"><?php echo esc_html( mb_substr( SZC_Contacts::full_name( $c ), 0, 1 ) ); ?></div>
					<h2 class="szc-p-dialer-name"><a href="<?php echo esc_url( self::contact_url( $c->id ) ); ?>"><?php echo esc_html( SZC_Contacts::full_name( $c ) ); ?></a></h2>
					<div class="szc-p-dialer-meta">
						<?php if ( $c->company ) : ?><span><?php echo szc_icon( 'briefcase' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $c->company ); ?></span><?php endif; ?>
						<?php if ( $c->city ) : ?><span><?php echo szc_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $c->city ); ?></span><?php endif; ?>
					</div>
					<div class="szc-p-dialer-why">
						آخرین تماس: <?php echo esc_html( $c->last_contacted_at ? szc_format_mysql( $c->last_contacted_at ) : 'هنوز ثبت نشده' ); ?>
						<?php if ( $c->next_followup_at ) : ?> · پیگیری بعدی: <?php echo esc_html( szc_format_mysql( $c->next_followup_at ) ); ?><?php endif; ?>
						<?php if ( $latest_followup && $latest_followup->body ) : ?><br>موضوع پیگیری: <?php echo esc_html( $latest_followup->body ); ?><?php endif; ?>
					</div>
					<a class="szc-p-dialer-call" data-reserve-call data-contact="<?php echo (int) $c->id; ?>" href="tel:<?php echo esc_attr( $c->mobile ); ?>"><?php echo szc_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span dir="ltr"><?php echo esc_html( szc_fa_digits( $c->mobile ) ); ?></span></a>
					<?php echo SZC_Conversation::render( $c, 'dialer' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

					<div class="szc-p-dialer-log">
						<div class="szc-p-row">
							<select data-call-outcome>
								<?php foreach ( $outcomes as $k => $lbl ) : ?><option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $lbl ); ?></option><?php endforeach; ?>
							</select>
							<select data-call-template>
								<option value="0">پیامک: پیش‌فرض</option>
								<option value="-1">بدون پیامک</option>
								<?php foreach ( $templates as $t ) : ?><option value="<?php echo (int) $t->id; ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?>
							</select>
						</div>
						<textarea data-call-note rows="2" placeholder="یادداشت تماس (اختیاری)"></textarea>
						<div class="szc-p-btnrow">
							<button class="szc-p-btn szc-p-btn-primary" data-szc-act="dialer_call">ثبت تماس و بعدی</button>
							<a class="szc-p-btn" href="<?php echo esc_url( self::url( 'dialer', array( 'i' => $i + 1 ) ) ); ?>"><span>رد کردن</span><?php echo szc_icon( 'chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/* ---------- پیگیری‌ها ---------- */

	protected static function view_followups() {
		$owner    = SZC_Settings::scope_owner();
		$tab      = ( isset( $_GET['tab'] ) && $_GET['tab'] === 'upcoming' ) ? 'upcoming' : 'due'; // phpcs:ignore
		$rows     = SZC_Activity::followups( $tab, $owner, 200 );
		ob_start(); ?>
		<h1 class="szc-p-h1">پیگیری‌ها</h1>
		<div class="szc-p-tabs">
			<a class="<?php echo $tab === 'due' ? 'is-active' : ''; ?>" href="<?php echo esc_url( self::url( 'followups', array( 'tab' => 'due' ) ) ); ?>">امروز و عقب‌افتاده</a>
			<a class="<?php echo $tab === 'upcoming' ? 'is-active' : ''; ?>" href="<?php echo esc_url( self::url( 'followups', array( 'tab' => 'upcoming' ) ) ); ?>">آینده</a>
		</div>
		<?php if ( ! $rows ) : ?>
			<p class="szc-p-empty">پیگیری‌ای در این بخش نیست.</p>
		<?php else : ?>
			<div class="szc-p-tablewrap">
				<table class="szc-p-table">
					<thead><tr><th>مخاطب</th><th>موبایل</th><th>سررسید</th><th>موضوع</th></tr></thead>
					<tbody>
					<?php foreach ( $rows as $r ) :
						$name = trim( $r->first_name . ' ' . $r->last_name ) ?: szc_fa_digits( $r->mobile );
						?>
						<tr>
							<td data-th="مخاطب"><a href="<?php echo esc_url( self::contact_url( $r->contact_id ) ); ?>"><b><?php echo esc_html( $name ); ?></b></a></td>
							<td data-th="موبایل" dir="ltr"><?php echo esc_html( szc_fa_digits( $r->mobile ) ); ?></td>
							<td data-th="سررسید"><?php echo esc_html( szc_format_mysql( $r->due_at ) ); ?></td>
							<td data-th="موضوع"><?php echo esc_html( $r->body ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}

	/* ---------- گزارش‌ها ---------- */

	protected static function view_reports() {
		$owner   = SZC_Settings::scope_owner();
		$funnel  = SZC_Reports::funnel( $owner );
		$byout   = SZC_Reports::calls_by_outcome( 7 );
		$board   = SZC_Settings::is_manager() ? SZC_Reports::agent_leaderboard() : array();
		ob_start(); ?>
		<h1 class="szc-p-h1">گزارش‌ها</h1>
		<div class="szc-p-grid2">
			<section class="szc-p-card">
				<h2 class="szc-p-h2">قیف تبدیل</h2>
				<div class="szc-p-kpi"><b><?php echo esc_html( szc_fa_digits( $funnel['conversion'] ) ); ?>٪</b> نرخ تبدیل · <?php echo esc_html( szc_fa_digits( $funnel['registered'] ) ); ?> ثبت‌نام از <?php echo esc_html( szc_fa_digits( $funnel['total'] ) ); ?> سرنخ</div>
				<div class="szc-p-funnel">
					<?php $max = 1; foreach ( $funnel['stages'] as $st ) { $max = max( $max, $st['count'] ); }
					foreach ( $funnel['stages'] as $st ) :
						$w = (int) round( $st['count'] / $max * 100 ); ?>
						<div class="szc-p-frow">
							<span class="szc-p-flabel"><?php echo esc_html( $st['label'] ); ?></span>
							<span class="szc-p-fbar"><span class="szc-p-ffill" style="width:<?php echo esc_attr( max( 4, $w ) ); ?>%"></span></span>
							<span class="szc-p-fcount"><?php echo esc_html( szc_fa_digits( $st['count'] ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
			<section class="szc-p-card">
				<h2 class="szc-p-h2">تماس‌ها (۷ روز اخیر)</h2>
				<div class="szc-p-funnel">
					<?php $max = 1; foreach ( $byout as $o ) { $max = max( $max, $o['count'] ); }
					foreach ( $byout as $o ) :
						$w = (int) round( $o['count'] / $max * 100 ); ?>
						<div class="szc-p-frow">
							<span class="szc-p-flabel"><?php echo esc_html( $o['label'] ); ?></span>
							<span class="szc-p-fbar"><span class="szc-p-ffill" style="width:<?php echo esc_attr( max( 4, $w ) ); ?>%"></span></span>
							<span class="szc-p-fcount"><?php echo esc_html( szc_fa_digits( $o['count'] ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		</div>
		<?php if ( $board ) : ?>
			<section class="szc-p-card">
				<h2 class="szc-p-h2">عملکرد کارشناسان (تماس‌های امروز)</h2>
				<div class="szc-p-tablewrap">
					<table class="szc-p-table">
						<thead><tr><th>کارشناس</th><th>تماس امروز</th></tr></thead>
						<tbody>
						<?php foreach ( $board as $b ) : ?>
							<tr><td data-th="کارشناس"><?php echo esc_html( $b['name'] ); ?></td><td data-th="تماس امروز"><?php echo esc_html( szc_fa_digits( $b['calls'] ) ); ?></td></tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>
		<?php endif; ?>
		<?php
		return ob_get_clean();
	}
}
