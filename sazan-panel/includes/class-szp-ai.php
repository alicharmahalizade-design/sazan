<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * لایه ارتباط با API هوش مصنوعی گپ جی‌پی‌تی (سازگار با OpenAI).
 * کلید/مدل/آدرس پایه در options ذخیره می‌شوند و فقط برای مدیر استفاده می‌شوند.
 * نکته امنیتی: کلید فقط سمت سرور به‌کار می‌رود و هرگز به فرانت ارسال نمی‌شود.
 */
class SZP_AI {

	const OPT_KEY   = 'szp_ai_key';
	const OPT_MODEL = 'szp_ai_model';
	const OPT_BASE  = 'szp_ai_base';

	public static function key() {
		return (string) get_option( self::OPT_KEY, '' );
	}
	public static function model() {
		$m = (string) get_option( self::OPT_MODEL, '' );
		return $m !== '' ? $m : 'gpt-4o';
	}
	public static function base() {
		$b = (string) get_option( self::OPT_BASE, '' );
		$b = $b !== '' ? $b : 'https://api.gapgpt.app/v1';
		return rtrim( $b, '/' );
	}
	public static function is_configured() {
		return self::key() !== '';
	}

	/**
	 * هستهٔ درخواست chat/completions با اعتبارنامهٔ دلخواه.
	 * $creds = array( key, model, base ). خروجی: متن پاسخ یا WP_Error.
	 */
	public static function request( $messages, $creds, $args = array() ) {
		$key   = (string) ( $creds['key'] ?? '' );
		$model = (string) ( $creds['model'] ?? 'gpt-4o' );
		$base  = rtrim( (string) ( $creds['base'] ?? 'https://api.gapgpt.app/v1' ), '/' );
		if ( $key === '' ) {
			return new WP_Error( 'no_key', 'کلید API هوش مصنوعی تنظیم نشده است.' );
		}
		$body = array_merge( array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => 0,
		), $args );

		$res = wp_remote_post( $base . '/chat/completions', array(
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
		) );

		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$raw  = wp_remote_retrieve_body( $res );
		$json = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = $json['error']['message'] ?? ( 'خطای سرویس هوش مصنوعی (کد ' . $code . ').' );
			return new WP_Error( 'ai_http', $msg );
		}
		$content = $json['choices'][0]['message']['content'] ?? '';
		if ( $content === '' ) {
			return new WP_Error( 'ai_empty', 'پاسخ خالی از سرویس هوش مصنوعی دریافت شد.' );
		}
		return (string) $content;
	}

	/**
	 * فراخوانی chat/completions با اعتبارنامهٔ ذخیره‌شده. خروجی: متن پاسخ یا WP_Error.
	 */
	public static function chat( $messages, $args = array() ) {
		if ( ! self::is_configured() ) {
			return new WP_Error( 'no_key', 'کلید API هوش مصنوعی تنظیم نشده است.' );
		}
		return self::request( $messages, array(
			'key'   => self::key(),
			'model' => self::model(),
			'base'  => self::base(),
		), $args );
	}

	/**
	 * تست اتصال با اعتبارنامهٔ دلخواه (برای دکمهٔ تست پیش از ذخیره).
	 * خروجی: true در موفقیت یا WP_Error با پیام خطا.
	 */
	public static function test_connection( $key, $model, $base ) {
		$out = self::request(
			array( array( 'role' => 'user', 'content' => 'پاسخ را فقط با کلمهٔ «ok» بده.' ),
			), array(
				'key'   => $key,
				'model' => $model,
				'base'  => $base,
			), array( 'max_tokens' => 5 ) );
		return is_wp_error( $out ) ? $out : true;
	}

	/**
	 * تشخیص موارد تکراری/هم‌معنا.
	 * ورودی: آرایه‌ای از رشته‌ها (با کلید عددی پایدار = شناسه).
	 * خروجی: آرایه‌ای از گروه‌ها؛ هر گروه آرایه‌ای از شناسه‌ها (>=2)، یا WP_Error.
	 */
	public static function find_duplicate_groups( $items ) {
		$lines = array();
		foreach ( $items as $id => $text ) {
			$lines[] = 'id=' . (int) $id . ': ' . str_replace( array( "\n", "\r" ), ' ', (string) $text );
		}
		if ( count( $lines ) < 2 ) {
			return array();
		}

		$sys  = 'تو یک دستیار تحلیل متن فارسی هستی. فقط و فقط JSON معتبر و بدون توضیح اضافه برمی‌گردانی.';
		$user = "هر مورد زیر یک «روش طراحی خدمت» است. مواردی را که از نظر معنایی تکراری یا هم‌معنا هستند "
			. "(حتی با جمله‌بندی متفاوت) در گروه‌های جدا دسته‌بندی کن. فقط گروه‌هایی را بیاور که حداقل ۲ مورد دارند. "
			. "اگر هیچ تکراری نبود، groups را خالی بگذار.\n"
			. "خروجی دقیقاً با این قالب: {\"groups\": [[id, id, ...], ...]}\n\nموارد:\n"
			. implode( "\n", $lines );

		$out = self::chat( array(
			array( 'role' => 'system', 'content' => $sys ),
			array( 'role' => 'user', 'content' => $user ),
		) );
		if ( is_wp_error( $out ) ) {
			return $out;
		}

		$parsed = self::extract_json( $out );
		if ( ! is_array( $parsed ) || ! isset( $parsed['groups'] ) || ! is_array( $parsed['groups'] ) ) {
			return new WP_Error( 'ai_parse', 'پاسخ هوش مصنوعی قابل پردازش نبود.' );
		}

		$valid  = array_keys( $items );
		$groups = array();
		foreach ( $parsed['groups'] as $g ) {
			if ( ! is_array( $g ) ) { continue; }
			$ids = array();
			foreach ( $g as $id ) {
				$id = (int) $id;
				if ( in_array( $id, $valid, true ) && ! in_array( $id, $ids, true ) ) {
					$ids[] = $id;
				}
			}
			if ( count( $ids ) >= 2 ) {
				$groups[] = $ids;
			}
		}
		return $groups;
	}

	/** استخراج اولین شیء JSON از متن (در صورت وجود متن اضافه پیرامون آن). */
	protected static function extract_json( $text ) {
		$text = trim( (string) $text );
		// حذف حصار کد مارک‌داون در صورت وجود.
		$text = preg_replace( '/^```(?:json)?|```$/m', '', $text );
		$start = strpos( $text, '{' );
		$end   = strrpos( $text, '}' );
		if ( $start === false || $end === false || $end <= $start ) {
			return null;
		}
		return json_decode( substr( $text, $start, $end - $start + 1 ), true );
	}
}
