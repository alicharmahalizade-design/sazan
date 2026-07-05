<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * ارسال پیامک از طریق «ایران‌پیامک / فراز اس‌ام‌اس» (REST API نسخه ws/v1).
 * مستندات: https://docs.iranpayamak.com
 *
 * احراز هویت: هدر «Api-Key».
 * ارسال ساده: POST /ws/v1/sms/simple با { text, line_number, recipients[], number_format, schedule }.
 * آدرس پایه، کلید و خط ارسال از تنظیمات «ارزیابی» خوانده می‌شوند.
 */
class SZP_SMS {

	/** آیا سرویس پیامک فعال و حداقل تنظیمات لازم موجود است؟ */
	public static function enabled() {
		$s = SZP_Eval::settings();
		return ! empty( $s['sms_enabled'] )
			&& trim( (string) $s['sms_apikey'] ) !== ''
			&& trim( (string) $s['sms_originator'] ) !== '';
	}

	protected static function base() {
		$b = rtrim( (string) SZP_Eval::opt( 'sms_base' ), '/' );
		// نصب‌های قدیمی که هنوز آدرس ippanel ذخیره دارند، خودکار به ایران‌پیامک منتقل شوند.
		if ( $b === '' || strpos( $b, 'ippanel' ) !== false ) {
			return 'https://api.iranpayamak.com';
		}
		return $b;
	}

	protected static function headers() {
		return array(
			'Api-Key'      => trim( (string) SZP_Eval::opt( 'sms_apikey' ) ),
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		);
	}

	/** خط ارسال (line_number) — ارقام فارسی به انگلیسی و حذف فاصله‌ها. */
	protected static function line() {
		$l = szp_latin_digits( (string) SZP_Eval::opt( 'sms_originator' ) );
		return trim( preg_replace( '/\s+/', '', $l ) );
	}

	/** ارسال متن آزاد به یک گیرنده. خروجی: array( ok, msg ). */
	public static function send_text( $to, $message ) {
		return self::send_text_bulk( array( $to ), $message );
	}

	/** ارسال متن آزاد یکسان به چند گیرنده در یک درخواست. خروجی: array( ok, msg, count ). */
	public static function send_text_bulk( $recipients, $message ) {
		$nums = array();
		foreach ( (array) $recipients as $r ) {
			$n = szp_normalize_mobile( $r );
			if ( $n !== '' ) {
				$nums[] = $n;
			}
		}
		$nums = array_values( array_unique( $nums ) );
		if ( ! $nums || trim( (string) $message ) === '' ) {
			return array( 'ok' => false, 'msg' => 'گیرنده یا متن نامعتبر است.', 'count' => 0 );
		}
		$body = array(
			'text'          => (string) $message,
			'line_number'   => self::line(),
			'recipients'    => $nums,
			'number_format' => 'english',
			'schedule'      => null,
		);
		$res          = self::post( '/ws/v1/sms/simple', $body );
		$res['count'] = count( $nums );
		return $res;
	}

	/** ارسال «نمونه» به صاحب حساب (تست بدون نیاز به گیرنده). */
	public static function send_sample( $message ) {
		$body = array(
			'text'          => (string) $message,
			'line_number'   => self::line(),
			'number_format' => 'english',
			'schedule'      => null,
		);
		return self::post( '/ws/v1/sms/sample', $body );
	}

	/** ارسال یادآوری بر اساس متن قالب. $kind: target | result. */
	public static function send_reminder( $to, $kind, $name ) {
		$s   = SZP_Eval::settings();
		$var = $s['sms_var'] !== '' ? $s['sms_var'] : 'name';
		$tpl = ( $kind === 'result' ) ? $s['sms_text_result'] : $s['sms_text_target'];
		$msg = str_replace( array( '%name%', '%' . $var . '%' ), $name, (string) $tpl );
		return self::send_text( $to, $msg );
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
		$code   = (int) wp_remote_retrieve_response_code( $res );
		$raw    = wp_remote_retrieve_body( $res );
		$json   = json_decode( $raw, true );
		$status = ( is_array( $json ) && isset( $json['status'] ) ) ? $json['status'] : '';
		$ok     = ( $code >= 200 && $code < 300 ) && ( $status === '' || $status === 'success' );

		if ( $ok ) {
			return array( 'ok' => true, 'msg' => 'ارسال شد.', 'code' => $code );
		}
		// پیام خطای خواناتر از فیلد messages در صورت وجود.
		$err = '';
		if ( is_array( $json ) && isset( $json['messages'] ) && $json['messages'] !== null ) {
			$err = is_scalar( $json['messages'] )
				? (string) $json['messages']
				: wp_json_encode( $json['messages'], JSON_UNESCAPED_UNICODE );
		}
		if ( $err === '' ) {
			$err = wp_strip_all_tags( (string) $raw );
		}
		return array( 'ok' => false, 'msg' => 'خطای سرویس پیامک (' . $code . '): ' . $err, 'code' => $code );
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
