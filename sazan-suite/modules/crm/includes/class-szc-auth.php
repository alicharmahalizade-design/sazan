<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * لایه‌ی احراز هویت و «بازیگرِ جاری» (actor) برای CRM.
 *
 * دو نوع بازیگر داریم:
 *   - مدیر: کاربرِ وردپرس (مدیرِ سایت یا در فهرستِ managers). از پیشخوانِ وردپرس و پورتال.
 *   - کارشناس: موجودیتِ مستقلِ CRM (SZC_Agents) که با موبایل و کد یک‌بارمصرف یا رمز ثابت وارد «پورتال» می‌شود؛
 *     نشستِ او با یک کوکیِ امضاشده (HMAC) نگه‌داری می‌شود — بدونِ کاربرِ وردپرس.
 *
 * «شناسه‌ی بازیگر» (actor_id) یکپارچه است: مدیر → شناسه‌ی کاربرِ وردپرس،
 * کارشناس → SZC_Agents::to_owner(agent_id). همین مقدار در owner_id، created_by و
 * user_idِ فعالیت‌ها ذخیره می‌شود تا محدوده و نامِ بازیگر درست حل شود.
 */
class SZC_Auth {

	const COOKIE = 'szc_agent_auth';
	const TTL    = 86400; // ۲۴ ساعت

	protected static $agent = false; // sentinel «هنوز خوانده نشده»

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_logout' ) );
	}

	/* ==================== نشستِ کارشناس (کوکیِ امضاشده) ==================== */

	protected static function secret( $agent ) {
		// با تغییرِ رمزِ کارشناس، همه‌ی نشست‌های قبلی باطل می‌شوند.
		return wp_salt( 'auth' ) . '|szc-agent|' . $agent->pass_hash . '|' . (int) ( $agent->session_version ?? 1 );
	}

	protected static function sign( $agent, $expiry ) {
		$payload = $agent->id . '|' . $expiry;
		return hash_hmac( 'sha256', $payload, self::secret( $agent ) );
	}

	/** ورودِ کارشناس: تنظیمِ کوکیِ نشست. */
	public static function login_agent( $agent ) {
		$expiry = time() + self::TTL;
		$value  = $agent->id . '|' . $expiry . '|' . self::sign( $agent, $expiry );
		$secure = is_ssl();
		$path   = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		setcookie( self::COOKIE, $value, array(
			'expires'  => $expiry,
			'path'     => $path,
			'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
			'secure'   => $secure,
			'httponly' => true,
			'samesite' => 'Lax',
		) );
		$_COOKIE[ self::COOKIE ] = $value;
		self::$agent = $agent;
	}

	public static function logout_agent() {
		$path = defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/';
		setcookie( self::COOKIE, '', array(
			'expires' => time() - 3600,
			'path' => $path,
			'domain' => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
			'secure' => is_ssl(),
			'httponly' => true,
			'samesite' => 'Lax',
		) );
		unset( $_COOKIE[ self::COOKIE ] );
		self::$agent = null;
	}

	/** خروجِ کارشناس با پارامترِ szc_logout روی برگه‌ی پورتال. */
	public static function maybe_logout() {
		if ( isset( $_GET['szc_logout'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'szc_agent_logout' ) ) {
			self::logout_agent();
			if ( class_exists( 'SZC_Portal' ) ) {
				wp_safe_redirect( SZC_Portal::page_url() );
				exit;
			}
		}
	}

	/** کارشناسِ واردشده (از روی کوکی) یا null. کش‌شده در هر درخواست. */
	public static function current_agent() {
		if ( self::$agent !== false ) {
			return self::$agent;
		}
		self::$agent = null;
		$raw = isset( $_COOKIE[ self::COOKIE ] ) ? (string) $_COOKIE[ self::COOKIE ] : '';
		if ( $raw === '' || substr_count( $raw, '|' ) < 2 ) {
			return null;
		}
		list( $id, $expiry, $sig ) = explode( '|', $raw, 3 );
		if ( (int) $expiry < time() ) {
			return null;
		}
		$agent = SZC_Agents::get( (int) $id );
		if ( ! $agent || (int) $agent->active !== 1 ) {
			return null;
		}
		$expected = self::sign( $agent, (int) $expiry );
		if ( ! hash_equals( $expected, (string) $sig ) ) {
			return null;
		}
		self::$agent = $agent;
		return $agent;
	}

	public static function is_agent() {
		return (bool) self::current_agent();
	}

	/* ==================== ورود با رمز ثابت ==================== */

	/**
	 * احراز هویت موبایل + رمز ثابت با محدودیت تلاش و پاسخ عمومی.
	 * پاسخ شماره ناشناس و رمز اشتباه یکسان است تا عضویت افراد قابل تشخیص نباشد.
	 */
	public static function verify_fixed_password( $mobile, $password ) {
		$mobile   = szc_normalize_mobile( $mobile );
		$password = (string) $password;
		$generic  = 'شماره موبایل یا رمز عبور صحیح نیست.';
		$ip_sig   = hash_hmac( 'sha256', self::request_ip(), wp_salt( 'nonce' ) );
		$mob_sig  = hash_hmac( 'sha256', $mobile, wp_salt( 'nonce' ) );
		$ip_key   = 'szc_pw_ip_' . $ip_sig;
		$mob_key  = 'szc_pw_m_' . $mob_sig;
		$ttl      = 15 * MINUTE_IN_SECONDS;
		if ( (int) get_transient( $ip_key ) >= 30 || (int) get_transient( $mob_key ) >= 8 ) {
			return array( 'ok' => false, 'msg' => 'تلاش‌های ورود بیش از حد مجاز است؛ ۱۵ دقیقه دیگر دوباره امتحان کنید.', 'retry_after' => $ttl );
		}
		if ( ! szc_is_valid_mobile( $mobile ) || $password === '' || strlen( $password ) > 200 ) {
			self::bump_rate_limit( $ip_key, $ttl );
			self::bump_rate_limit( $mob_key, $ttl );
			return array( 'ok' => false, 'msg' => $generic );
		}
		$agent = SZC_Agents::get_by_mobile( $mobile );
		$valid = $agent ? SZC_Agents::verify( $agent, $password ) : password_verify( $password, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.' );
		if ( ! $agent || ! $valid ) {
			self::bump_rate_limit( $ip_key, $ttl );
			self::bump_rate_limit( $mob_key, $ttl );
			if ( $agent && class_exists( 'SZC_Audit' ) ) {
				SZC_Audit::log( 'password_login_failed', 'agent', (int) $agent->id, 'ورود ناموفق با رمز ثابت', null, null, 0 );
			}
			return array( 'ok' => false, 'msg' => $generic );
		}
		delete_transient( $mob_key );
		delete_transient( $ip_key );
		return array( 'ok' => true, 'agent' => $agent );
	}

	/* ==================== کد یک‌بارمصرف ورود ==================== */

	protected static function otp_limits() {
		return array(
			'expiry'   => min( 600, max( 60, (int) SZC_Settings::get( 'otp_expiry' ) ) ),
			'resend'   => min( 300, max( 30, (int) SZC_Settings::get( 'otp_resend' ) ) ),
			'attempts' => min( 10, max( 3, (int) SZC_Settings::get( 'otp_max_attempts' ) ) ),
		);
	}

	protected static function otp_key( $mobile ) {
		return 'szc_otp_' . hash_hmac( 'sha256', szc_normalize_mobile( $mobile ), wp_salt( 'nonce' ) );
	}

	protected static function otp_hash( $mobile, $code ) {
		return hash_hmac( 'sha256', szc_normalize_mobile( $mobile ) . '|' . (string) $code, wp_salt( 'auth' ) . '|szc-otp' );
	}

	protected static function request_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ), 0, 45 ) : '0';
	}

	protected static function bump_rate_limit( $key, $ttl ) {
		$count = (int) get_transient( $key );
		set_transient( $key, $count + 1, $ttl );
		return $count + 1;
	}

	/**
	 * درخواست OTP. پاسخ برای شماره ناشناس و شماره معتبر عمداً یکسان است تا
	 * امکان تشخیص اعضای تیم از بیرون وجود نداشته باشد.
	 */
	public static function request_otp( $mobile ) {
		$mobile = szc_normalize_mobile( $mobile );
		$limits = self::otp_limits();
		if ( ! szc_is_valid_mobile( $mobile ) ) {
			return array( 'ok' => false, 'msg' => 'شماره موبایل معتبر وارد کنید.' );
		}
		if ( ! SZC_SMS::enabled() ) {
			return array( 'ok' => false, 'msg' => 'ارسال کد ورود موقتاً در دسترس نیست. تنظیمات پنل پیامک باید توسط مدیر بررسی شود.' );
		}

		$ip = self::request_ip();
		$mobile_sig = hash_hmac( 'sha256', $mobile, wp_salt( 'nonce' ) );
		$ip_sig = hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) );
		$cooldown_key = 'szc_otp_cd_' . $mobile_sig;
		$cooldown = (int) get_transient( $cooldown_key );
		if ( $cooldown > time() ) {
			return array(
				'ok' => true,
				'msg' => 'کد قبلی هنوز معتبر است. پیامک‌های دریافتی را بررسی کنید.',
				'expires_in' => $limits['expiry'],
				'resend_after' => max( 1, $cooldown - time() ),
			);
		}
		$mobile_rate_key = 'szc_otp_hr_m_' . $mobile_sig;
		$ip_rate_key = 'szc_otp_hr_i_' . $ip_sig;
		if ( (int) get_transient( $mobile_rate_key ) >= 6 || (int) get_transient( $ip_rate_key ) >= 20 ) {
			return array( 'ok' => false, 'msg' => 'تعداد درخواست‌ها زیاد است. یک ساعت دیگر دوباره امتحان کنید.', 'retry_after' => HOUR_IN_SECONDS );
		}
		self::bump_rate_limit( $mobile_rate_key, HOUR_IN_SECONDS );
		self::bump_rate_limit( $ip_rate_key, HOUR_IN_SECONDS );
		set_transient( $cooldown_key, time() + $limits['resend'], $limits['resend'] );

		$agent = SZC_Agents::get_by_mobile( $mobile );
		$agent = ( $agent && (int) $agent->active === 1 ) ? $agent : null;
		$code = (string) random_int( 100000, 999999 );
		$record = array(
			'agent_id' => $agent ? (int) $agent->id : 0,
			'hash' => self::otp_hash( $mobile, $code ),
			'expires' => time() + $limits['expiry'],
			'attempts' => 0,
			'ip_sig' => $ip_sig,
		);
		// برای شماره ناشناس نیز رکورد ساختگی ساخته می‌شود تا پاسخ مرحله تأیید قابل تمایز نباشد.
		set_transient( self::otp_key( $mobile ), $record, $limits['expiry'] );
		if ( $agent ) {
			$pattern_code = trim( (string) SZC_Settings::get( 'otp_pattern_code' ) );
			$pattern_param = preg_replace( '/[^A-Za-z0-9_]/', '', (string) SZC_Settings::get( 'otp_pattern_param' ) );
			$pattern_param = $pattern_param !== '' ? $pattern_param : 'code';
			$sent = $pattern_code !== ''
				? SZC_SMS::send_pattern( $mobile, $pattern_code, array( $pattern_param => $code ) )
				: array( 'ok' => false, 'msg' => 'کد پترن ورود تنظیم نشده است.' );
			if ( empty( $sent['ok'] ) ) {
				if ( class_exists( 'SZC_Audit' ) ) {
					SZC_Audit::log( 'otp_send_failed', 'agent', (int) $agent->id, 'ارسال کد ورود ناموفق بود', array(), array( 'provider' => $sent['msg'] ?? '' ), 0 );
				}
			} elseif ( class_exists( 'SZC_Audit' ) ) {
				SZC_Audit::log( 'otp_requested', 'agent', (int) $agent->id, 'کد یک‌بارمصرف ورود درخواست شد', null, null, 0 );
			}
		}

		return array(
			'ok' => true,
			'msg' => 'اگر این شماره برای کارشناس فعال ثبت شده باشد، کد ورود ارسال می‌شود.',
			'expires_in' => $limits['expiry'],
			'resend_after' => $limits['resend'],
		);
	}

	/** اعتبارسنجی کد و بازگرداندن رکورد کارشناس در صورت موفقیت. */
	public static function verify_otp( $mobile, $code ) {
		$mobile = szc_normalize_mobile( $mobile );
		$code = szc_latin_digits( preg_replace( '/\s+/', '', (string) $code ) );
		if ( ! szc_is_valid_mobile( $mobile ) || ! preg_match( '/^\d{6}$/', $code ) ) {
			return array( 'ok' => false, 'msg' => 'کد ورود باید شش رقم باشد.' );
		}
		$key = self::otp_key( $mobile );
		$record = get_transient( $key );
		if ( ! is_array( $record ) || empty( $record['expires'] ) || (int) $record['expires'] < time() ) {
			delete_transient( $key );
			return array( 'ok' => false, 'msg' => 'کد ورود منقضی شده است. کد تازه دریافت کنید.', 'expired' => true );
		}
		$limits = self::otp_limits();
		$record['attempts'] = (int) ( $record['attempts'] ?? 0 ) + 1;
		if ( $record['attempts'] > $limits['attempts'] ) {
			delete_transient( $key );
			return array( 'ok' => false, 'msg' => 'تعداد تلاش‌ها بیش از حد مجاز بود. کد تازه دریافت کنید.', 'expired' => true );
		}
		$expected = (string) ( $record['hash'] ?? '' );
		$provided = self::otp_hash( $mobile, $code );
		if ( $expected === '' || ! hash_equals( $expected, $provided ) ) {
			set_transient( $key, $record, max( 1, (int) $record['expires'] - time() ) );
			if ( class_exists( 'SZC_Audit' ) ) {
				SZC_Audit::log( 'otp_failed', 'agent', (int) ( $record['agent_id'] ?? 0 ), 'کد ورود نادرست وارد شد', null, null, 0 );
			}
			$remaining = max( 0, $limits['attempts'] - $record['attempts'] );
			return array( 'ok' => false, 'msg' => 'کد ورود صحیح نیست. ' . szc_fa_digits( $remaining ) . ' تلاش باقی مانده است.' );
		}
		$agent = SZC_Agents::get( (int) ( $record['agent_id'] ?? 0 ) );
		if ( ! $agent || (int) $agent->active !== 1 || szc_normalize_mobile( $agent->mobile ) !== $mobile ) {
			delete_transient( $key );
			return array( 'ok' => false, 'msg' => 'کد ورود صحیح نیست. کد تازه دریافت کنید.', 'expired' => true );
		}
		delete_transient( $key );
		return array( 'ok' => true, 'agent' => $agent );
	}

	/* ==================== بازیگرِ جاری ==================== */

	/** آیا بازیگرِ جاری مدیرِ CRM است؟ (کاربرِ وردپرسِ مدیر) کارشناس هرگز مدیر نیست. */
	public static function is_manager() {
		if ( self::is_agent() ) {
			return false;
		}
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return false;
		}
		if ( user_can( $uid, 'manage_options' ) ) {
			return true;
		}
		return in_array( (int) $uid, SZC_Settings::manager_ids(), true );
	}

	/** آیا بازیگرِ جاری به CRM دسترسی دارد؟ (مدیر یا کارشناسِ واردشده) */
	public static function can_access() {
		return self::is_manager() || self::is_agent();
	}

	/** شناسه‌ی یکپارچه‌ی بازیگر (برای owner_id/created_by/user_id). */
	public static function actor_id() {
		$a = self::current_agent();
		if ( $a ) {
			return SZC_Agents::to_owner( (int) $a->id );
		}
		return get_current_user_id();
	}

	/** کلیدِ یکتا برای transientها (مدیر یا کارشناس). */
	public static function uid() {
		return self::actor_id();
	}

	/**
	 * آیا بازیگرِ جاری همه‌ی مخاطبین را می‌بیند؟
	 * مدیر همیشه، و کارشناس در حالتِ «استخر مشترک» (پیش‌فرض روشن).
	 */
	public static function can_see_all() {
		if ( self::is_manager() ) {
			return true;
		}
		return ! empty( SZC_Settings::get( 'shared_pool' ) );
	}

	public static function can( $permission ) {
		if ( self::is_manager() ) {
			return true;
		}
		if ( ! self::is_agent() ) {
			return false;
		}
		$agent = self::current_agent();
		$permissions = $agent ? SZC_Agents::permissions( $agent ) : array();
		if ( ! $permissions ) {
			$permissions = SZC_Settings::agent_permissions();
		}
		return ! empty( $permissions[ sanitize_key( $permission ) ] );
	}

	public static function can_access_contact( $contact, $permission = 'view' ) {
		if ( ! $contact || ! self::can_access() ) {
			return false;
		}
		if ( self::is_manager() ) {
			return true;
		}
		if ( ! self::can_see_all() && (int) $contact->owner_id !== (int) self::actor_id() ) {
			return false;
		}
		return $permission === 'view' ? true : self::can( $permission );
	}

	/** محدوده‌ی مالکیت برای کوئری‌ها: دیدنِ همه → 0، وگرنه owner_idِ خودِ کارشناس. */
	public static function scope_owner() {
		return self::can_see_all() ? 0 : self::actor_id();
	}

	/** نامِ نمایشیِ یک شناسه‌ی بازیگر (کارشناس یا کاربرِ وردپرس). */
	public static function display_name( $id ) {
		$id = (int) $id;
		if ( $id <= 0 ) {
			return '';
		}
		if ( SZC_Agents::is_agent_owner( $id ) ) {
			return SZC_Agents::owner_name( $id );
		}
		return (string) get_the_author_meta( 'display_name', $id );
	}

	/** موبایلِ یک شناسه‌ی بازیگر (برای یادآوری پیامکی). */
	public static function actor_mobile( $id ) {
		$id = (int) $id;
		if ( SZC_Agents::is_agent_owner( $id ) ) {
			return SZC_Agents::owner_mobile( $id );
		}
		return $id > 0 ? szc_user_mobile( $id ) : '';
	}

	/** نامِ نمایشیِ بازیگرِ جاری (برای نوارِ بالای پورتال). */
	public static function current_name() {
		$a = self::current_agent();
		if ( $a ) {
			return $a->name;
		}
		$u = wp_get_current_user();
		return $u ? $u->display_name : '';
	}
}
