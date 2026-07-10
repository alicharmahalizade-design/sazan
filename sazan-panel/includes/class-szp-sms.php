<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ارسال پیامک از طریق فراز اس‌ام‌اس / آی‌پی‌پنل (REST API).
 * endpoint و کلید و خط ارسال از تنظیمات «ارزیابی» خوانده می‌شود.
 * دو حالت: pattern (پیامک خدماتی با کد پترن) و text (متن آزاد).
 *
 * مرجع: https://docs.farazsms.com/  — چون نسخه‌های API متفاوت‌اند،
 * آدرس پایه و کلید قابل‌تنظیم است و در صورت نیاز فقط همان‌ها را عوض کنید.
 */
class SZP_SMS {

	/** آیا سرویس پیامک فعال و حداقل تنظیمات لازم موجود است؟ */
	public static function enabled() {
		$s = SZP_Eval::settings();
		return ! empty( $s['sms_enabled'] ) && $s['sms_apikey'] !== '' && $s['sms_originator'] !== '';
	}

	protected static function base() {
		$b = rtrim( (string) SZP_Eval::opt( 'sms_base' ), '/' );
		return $b !== '' ? $b : 'https://rest.ippanel.com/v1';
	}

	protected static function headers() {
		return array(
			'apikey'       => (string) SZP_Eval::opt( 'sms_apikey' ),
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		);
	}

	/** ارسال متن آزاد به یک گیرنده. خروجی: array( ok, msg ). */
	public static function send_text( $to, $message ) {
		$to = szp_normalize_mobile( $to );
		if ( $to === '' || trim( (string) $message ) === '' ) {
			return array( 'ok' => false, 'msg' => 'گیرنده یا متن نامعتبر است.' );
		}
		$body = array(
			'originator' => (string) SZP_Eval::opt( 'sms_originator' ),
			'recipients' => array( $to ),
			'message'    => (string) $message,
		);
		return self::post( '/messages', $body );
	}

	/** ارسال پیامک پترن (خدماتی). $values نگاشت متغیرهای پترن. */
	public static function send_pattern( $to, $pattern_code, $values ) {
		$to = szp_normalize_mobile( $to );
		if ( $to === '' || trim( (string) $pattern_code ) === '' ) {
			return array( 'ok' => false, 'msg' => 'گیرنده یا کد پترن نامعتبر است.' );
		}
		$body = array(
			'pattern_code' => (string) $pattern_code,
			'originator'   => (string) SZP_Eval::opt( 'sms_originator' ),
			'recipient'    => $to,
			'values'       => (array) $values,
		);
		return self::post( '/messages/patterns/send', $body );
	}

	/** ارسال یادآوری بر اساس حالت تنظیم‌شده. $kind: target | result. */
	public static function send_reminder( $to, $kind, $name ) {
		$s   = SZP_Eval::settings();
		$var = $s['sms_var'] !== '' ? $s['sms_var'] : 'name';
		if ( $s['sms_mode'] === 'pattern' ) {
			$code = ( $kind === 'result' ) ? $s['sms_pattern_result'] : $s['sms_pattern_target'];
			if ( trim( (string) $code ) === '' ) {
				return array( 'ok' => false, 'msg' => 'کد پترن تنظیم نشده است.' );
			}
			return self::send_pattern( $to, $code, array( $var => $name ) );
		}
		$tpl = ( $kind === 'result' ) ? $s['sms_text_result'] : $s['sms_text_target'];
		$msg = str_replace( array( '%name%', '%' . $var . '%' ), $name, (string) $tpl );
		return self::send_text( $to, $msg );
	}

	/** جایگزینی متغیرها در قالب متن آزاد: %key% → مقدار. */
	protected static function fill( $tpl, $vars ) {
		$rep = array();
		foreach ( (array) $vars as $k => $v ) {
			$rep[ '%' . $k . '%' ] = (string) $v;
		}
		return strtr( (string) $tpl, $rep );
	}

	/**
	 * پیامک «ثبت جلسه» (خدماتی). $vars: name, date, time, coach, mentor, title.
	 * در حالت پترن، کلیدهای $vars باید با نام متغیرهای پترن یکی باشند.
	 */
	public static function send_session_notice( $to, $vars ) {
		$s = SZP_Eval::settings();
		if ( $s['sms_mode'] === 'pattern' ) {
			$code = (string) ( $s['sms_pattern_session'] ?? '' );
			if ( trim( $code ) === '' ) {
				return array( 'ok' => false, 'msg' => 'کد پترن «ثبت جلسه» تنظیم نشده است.' );
			}
			return self::send_pattern( $to, $code, $vars );
		}
		return self::send_text( $to, self::fill( $s['sms_text_session'] ?? '', $vars ) );
	}

	/** پیامک «لینک نظرسنجی» برای مشتری. $vars: name, link, coach. */
	public static function send_survey( $to, $vars ) {
		$s = SZP_Eval::settings();
		if ( $s['sms_mode'] === 'pattern' ) {
			$code = (string) ( $s['sms_pattern_survey'] ?? '' );
			if ( trim( $code ) === '' ) {
				return array( 'ok' => false, 'msg' => 'کد پترن «نظرسنجی» تنظیم نشده است.' );
			}
			return self::send_pattern( $to, $code, $vars );
		}
		return self::send_text( $to, self::fill( $s['sms_text_survey'] ?? '', $vars ) );
	}

	protected static function post( $path, $body ) {
		$res = wp_remote_post( self::base() . $path, array(
			'timeout' => 20,
			'headers' => self::headers(),
			'body'    => wp_json_encode( $body ),
		) );
		if ( is_wp_error( $res ) ) {
			return array( 'ok' => false, 'msg' => $res->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$raw  = wp_remote_retrieve_body( $res );
		$ok   = ( $code >= 200 && $code < 300 );
		return array( 'ok' => $ok, 'msg' => $ok ? 'ارسال شد.' : ( 'خطای سرویس پیامک (' . $code . '): ' . wp_strip_all_tags( (string) $raw ) ), 'code' => $code );
	}

	/* ==================== یادآور روزانه (Cron) ==================== */

	/** هر روز یک‌بار اجرا می‌شود؛ در روزِ تارگت و روزِ نتیجه به شرکت‌کنندگان پیامک می‌زند. */
	public static function run_daily_reminders() {
		if ( ! self::enabled() ) {
			return;
		}
		$today = (int) wp_date( 'N' );
		$kind  = null;
		if ( $today === SZP_Eval::day_target() ) {
			$kind = 'target';
		} elseif ( $today === SZP_Eval::day_result() ) {
			$kind = 'result';
		}
		if ( ! $kind ) {
			return;
		}

		foreach ( SZP_Eval::participants() as $uid ) {
			$latest = SZP_Eval::latest( $uid );
			// روز نتیجه فقط برای کسانی که هفته‌ی باز (بدون نتیجه) دارند.
			if ( $kind === 'result' && ( ! $latest || $latest->has_result ) ) {
				continue;
			}
			// روز تارگت فقط برای کسانی که هفته‌ی باز ندارند (آماده‌ی شروع هفته‌ی جدید).
			if ( $kind === 'target' && $latest && ! $latest->has_result ) {
				continue;
			}
			$mobile = SZP_Groups::user_mobile( $uid );
			if ( $mobile === '' ) {
				continue;
			}
			$info = SZP_Groups::user_info( $uid );
			$name = $info ? $info['name'] : '';
			self::send_reminder( $mobile, $kind, $name );
		}
	}
}
