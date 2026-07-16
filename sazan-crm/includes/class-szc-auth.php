<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * لایه‌ی احراز هویت و «بازیگرِ جاری» (actor) برای CRM.
 *
 * دو نوع بازیگر داریم:
 *   - مدیر: کاربرِ وردپرس (مدیرِ سایت یا در فهرستِ managers). از پیشخوانِ وردپرس و پورتال.
 *   - کارشناس: موجودیتِ مستقلِ CRM (SZC_Agents) که با موبایل+رمز وارد «پورتال» می‌شود؛
 *     نشستِ او با یک کوکیِ امضاشده (HMAC) نگه‌داری می‌شود — بدونِ کاربرِ وردپرس.
 *
 * «شناسه‌ی بازیگر» (actor_id) یکپارچه است: مدیر → شناسه‌ی کاربرِ وردپرس،
 * کارشناس → SZC_Agents::to_owner(agent_id). همین مقدار در owner_id، created_by و
 * user_idِ فعالیت‌ها ذخیره می‌شود تا محدوده و نامِ بازیگر درست حل شود.
 */
class SZC_Auth {

	const COOKIE = 'szc_agent_auth';
	const TTL    = 2592000; // ۳۰ روز

	protected static $agent = false; // sentinel «هنوز خوانده نشده»

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_logout' ) );
	}

	/* ==================== نشستِ کارشناس (کوکیِ امضاشده) ==================== */

	protected static function secret( $agent ) {
		// با تغییرِ رمزِ کارشناس، همه‌ی نشست‌های قبلی باطل می‌شوند.
		return wp_salt( 'auth' ) . '|szc-agent|' . $agent->pass_hash;
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
		setcookie( self::COOKIE, '', array( 'expires' => time() - 3600, 'path' => $path, 'domain' => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '' ) );
		unset( $_COOKIE[ self::COOKIE ] );
		self::$agent = null;
	}

	/** خروجِ کارشناس با پارامترِ szc_logout روی برگه‌ی پورتال. */
	public static function maybe_logout() {
		if ( isset( $_GET['szc_logout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
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

	/** محدوده‌ی مالکیت برای کوئری‌ها: مدیر → 0 (همه)، کارشناس → owner_idِ خودش. */
	public static function scope_owner() {
		return self::is_manager() ? 0 : self::actor_id();
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
