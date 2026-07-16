<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * کانال‌های پیام‌رسانِ گفت‌وگومحور: «بله» و «روبیکا».
 *
 * برخلافِ پیامک (که با شماره‌ی موبایل کار می‌کند)، بله و روبیکا از طریقِ
 * «بات» و شناسه‌ی گفتگو (chat_id) پیام می‌فرستند. برای هر مخاطب می‌توان یک
 * شناسه‌ی بله/روبیکا ذخیره کرد؛ اگر بات و شناسه هر دو موجود باشند، پیام
 * مستقیماً از طریقِ Bot API ارسال می‌شود.
 *
 * - بله (Bale):   API سازگار با تلگرام →  https://tapi.bale.ai/bot{token}/sendMessage
 * - روبیکا (Rubika): Bot API →           https://botapi.rubika.ir/v3/{token}/sendMessage
 */
class SZC_Messaging {

	/** فهرست کانال‌های پیام‌رسان (به‌جز پیامکِ ساده). */
	public static function channels() {
		return array(
			'bale'   => 'بله',
			'rubika' => 'روبیکا',
		);
	}

	/** برچسبِ فارسیِ یک کانال (شاملِ sms). */
	public static function channel_label( $channel ) {
		$map = array_merge( array( 'sms' => 'پیامک' ), self::channels() );
		return $map[ $channel ] ?? $channel;
	}

	/** آیا این کانالِ پیام‌رسان (بله/روبیکا) فعال و پیکربندی‌شده است؟ */
	public static function enabled( $channel ) {
		$s = SZC_Settings::all();
		if ( $channel === 'bale' ) {
			return ! empty( $s['bale_enabled'] ) && trim( (string) $s['bale_token'] ) !== '';
		}
		if ( $channel === 'rubika' ) {
			return ! empty( $s['rubika_enabled'] ) && trim( (string) $s['rubika_token'] ) !== '';
		}
		return false;
	}

	/** آیا حداقل یکی از کانال‌های پیام‌رسان فعال است؟ */
	public static function any_enabled() {
		foreach ( array_keys( self::channels() ) as $ch ) {
			if ( self::enabled( $ch ) ) {
				return true;
			}
		}
		return false;
	}

	/** شناسه‌ی گفتگوی مخاطب برای یک کانال (chat_id / نام‌کاربری). */
	public static function contact_peer( $contact, $channel ) {
		if ( ! $contact ) {
			return '';
		}
		$field = $channel === 'bale' ? 'bale_id' : ( $channel === 'rubika' ? 'rubika_id' : '' );
		return $field && isset( $contact->$field ) ? trim( (string) $contact->$field ) : '';
	}

	/**
	 * لینکِ بازکردنِ گفتگو با مخاطب در اپِ بله/روبیکا (در صورت داشتنِ نام‌کاربری).
	 * خروجی: URL یا '' اگر شناسه عددی/خالی باشد.
	 */
	public static function open_link( $contact, $channel ) {
		$peer = self::contact_peer( $contact, $channel );
		if ( $peer === '' ) {
			return '';
		}
		$user = ltrim( $peer, '@' );
		if ( $channel === 'bale' ) {
			return 'https://ble.ir/' . rawurlencode( $user );
		}
		if ( $channel === 'rubika' ) {
			return 'https://rubika.ir/' . rawurlencode( $user );
		}
		return '';
	}

	/* ==================== ارسال ==================== */

	protected static function bale_base() {
		$b = rtrim( (string) SZC_Settings::get( 'bale_base' ), '/' );
		return $b !== '' ? $b : 'https://tapi.bale.ai';
	}

	protected static function rubika_base() {
		$b = rtrim( (string) SZC_Settings::get( 'rubika_base' ), '/' );
		return $b !== '' ? $b : 'https://botapi.rubika.ir';
	}

	/**
	 * ارسالِ پیامِ متنی به یک شناسه از طریقِ کانالِ مشخص.
	 * خروجی: array( ok, msg ).
	 */
	public static function send( $channel, $peer, $message ) {
		$peer    = trim( (string) $peer );
		$message = trim( (string) $message );
		if ( ! self::enabled( $channel ) ) {
			return array( 'ok' => false, 'msg' => 'کانالِ ' . self::channel_label( $channel ) . ' فعال نیست.' );
		}
		if ( $peer === '' ) {
			return array( 'ok' => false, 'msg' => 'شناسه‌ی ' . self::channel_label( $channel ) . 'ِ مخاطب ثبت نشده است.' );
		}
		if ( $message === '' ) {
			return array( 'ok' => false, 'msg' => 'متنِ پیام خالی است.' );
		}

		if ( $channel === 'bale' ) {
			$token = rawurlencode( (string) SZC_Settings::get( 'bale_token' ) );
			$url   = self::bale_base() . '/bot' . $token . '/sendMessage';
			$body  = array( 'chat_id' => self::peer_value( $peer ), 'text' => $message );
			return self::request( $url, $body, 'bale' );
		}
		if ( $channel === 'rubika' ) {
			$token = rawurlencode( (string) SZC_Settings::get( 'rubika_token' ) );
			$url   = self::rubika_base() . '/v3/' . $token . '/sendMessage';
			$body  = array( 'chat_id' => self::peer_value( $peer ), 'text' => $message );
			return self::request( $url, $body, 'rubika' );
		}
		return array( 'ok' => false, 'msg' => 'کانالِ نامعتبر.' );
	}

	/** ارسال به یک مخاطب (شناسه از روی رکوردِ مخاطب خوانده می‌شود). */
	public static function send_to_contact( $channel, $contact, $message ) {
		return self::send( $channel, self::contact_peer( $contact, $channel ), $message );
	}

	/** اگر شناسه کاملاً عددی بود، عدد بفرست؛ وگرنه رشته (نام‌کاربری). */
	protected static function peer_value( $peer ) {
		return ctype_digit( $peer ) ? (int) $peer : $peer;
	}

	protected static function request( $url, $body, $channel ) {
		$res = wp_remote_post( $url, array(
			'timeout' => 20,
			'headers' => array( 'Content-Type' => 'application/json', 'Accept' => 'application/json' ),
			'body'    => wp_json_encode( $body ),
		) );
		if ( is_wp_error( $res ) ) {
			return array( 'ok' => false, 'msg' => $res->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$raw  = (string) wp_remote_retrieve_body( $res );
		$json = json_decode( $raw, true );
		$ok   = ( $code >= 200 && $code < 300 );

		// تفسیرِ پاسخِ استاندارد هر سرویس برای تشخیصِ خطاهای منطقی (۲۰۰ ولی ناموفق).
		if ( $ok && is_array( $json ) ) {
			if ( $channel === 'bale' && isset( $json['ok'] ) && ! $json['ok'] ) {
				$ok  = false;
				$raw = $json['description'] ?? $raw;
			} elseif ( $channel === 'rubika' && isset( $json['status'] ) && strtoupper( (string) $json['status'] ) !== 'OK' ) {
				$ok  = false;
				$raw = $json['status'] . ( isset( $json['data'] ) ? ' — ' . wp_json_encode( $json['data'] ) : '' );
			}
		}
		return array(
			'ok'   => $ok,
			'msg'  => $ok ? 'ارسال شد.' : ( 'خطای ' . self::channel_label( $channel ) . ' (' . $code . '): ' . wp_strip_all_tags( $raw ) ),
			'code' => $code,
		);
	}
}
