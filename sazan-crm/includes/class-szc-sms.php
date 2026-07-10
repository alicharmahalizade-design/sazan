<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * دروازه‌ی پیامک (فراز/آی‌پی‌پنل) + صفِ ارسال زمان‌بندی‌شده.
 * ارسال‌های خودکار (مثل پیامک تشکرِ ۱ ساعت بعد از تماس) در صف قرار می‌گیرند و
 * جاروبِ کرونِ ۵ دقیقه‌ای آن‌ها را در بازه‌ی مجاز ارسال می‌فرستد (idempotent).
 */
class SZC_SMS {

	const HOOK_SWEEP = 'szc_queue_sweep';
	const MAX_PER_RUN = 80;

	public static function init() {}

	public static function queue_table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_sms_queue';
	}

	/* ==================== دروازه ==================== */

	public static function enabled() {
		$s = SZC_Settings::all();
		return ! empty( $s['sms_enabled'] ) && $s['sms_apikey'] !== '' && $s['sms_originator'] !== '';
	}

	protected static function base() {
		$b = rtrim( (string) SZC_Settings::get( 'sms_base' ), '/' );
		return $b !== '' ? $b : 'https://rest.ippanel.com/v1';
	}

	protected static function headers() {
		return array(
			'apikey'       => (string) SZC_Settings::get( 'sms_apikey' ),
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		);
	}

	public static function send_text( $to, $message ) {
		$to = szc_normalize_mobile( $to );
		if ( $to === '' || trim( (string) $message ) === '' ) {
			return array( 'ok' => false, 'msg' => 'گیرنده یا متن نامعتبر است.' );
		}
		return self::post( '/messages', array(
			'originator' => (string) SZC_Settings::get( 'sms_originator' ),
			'recipients' => array( $to ),
			'message'    => (string) $message,
		) );
	}

	public static function send_pattern( $to, $pattern_code, $values ) {
		$to = szc_normalize_mobile( $to );
		if ( $to === '' || trim( (string) $pattern_code ) === '' ) {
			return array( 'ok' => false, 'msg' => 'گیرنده یا کد پترن نامعتبر است.' );
		}
		return self::post( '/messages/patterns/send', array(
			'pattern_code' => (string) $pattern_code,
			'originator'   => (string) SZC_Settings::get( 'sms_originator' ),
			'recipient'    => $to,
			'values'       => (array) $values,
		) );
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

	/* ==================== ارسال فوری به یک مخاطب ==================== */

	/** ساخت پیام از قالب برای یک مخاطب. خروجی: array( message, pattern_code, values ). */
	public static function resolve( $template, $contact ) {
		$vars = SZC_Contacts::vars( $contact );
		$body = SZC_Templates::fill( $template->body, $vars );
		$pattern = ( SZC_Settings::get( 'sms_mode' ) === 'pattern' && $template->pattern_code !== '' ) ? $template->pattern_code : '';
		return array(
			'message'      => $body,
			'pattern_code' => $pattern,
			'values'       => $vars,
		);
	}

	/** ارسال فوریِ یک قالب به یک مخاطب. خروجی: array( ok, msg ). */
	public static function send_template_now( $contact, $template_id ) {
		if ( ! self::enabled() ) {
			return array( 'ok' => false, 'msg' => 'سرویس پیامک فعال نیست.' );
		}
		if ( $contact->opt_out ) {
			return array( 'ok' => false, 'msg' => 'این مخاطب دریافت پیامک را لغو کرده است.' );
		}
		$tpl = SZC_Templates::get( $template_id );
		if ( ! $tpl ) {
			return array( 'ok' => false, 'msg' => 'قالب یافت نشد.' );
		}
		$r   = self::resolve( $tpl, $contact );
		$res = ( $r['pattern_code'] !== '' )
			? self::send_pattern( $contact->mobile, $r['pattern_code'], $r['values'] )
			: self::send_text( $contact->mobile, $r['message'] );

		SZC_Activity::log( (int) $contact->id, 'sms', array(
			'outcome' => ! empty( $res['ok'] ) ? 'sent' : 'failed',
			'body'    => $r['message'],
			'meta'    => array( 'template_id' => (int) $template_id, 'response' => $res['msg'] ?? '' ),
		) );
		return $res;
	}

	/* ==================== صف / زمان‌بندی ==================== */

	public static function enqueue( $contact_id, $mobile, $message, $send_at, $opts = array() ) {
		global $wpdb;
		$o = wp_parse_args( $opts, array( 'pattern_code' => '', 'values' => array(), 'template_id' => 0 ) );
		$wpdb->insert( self::queue_table(), array(
			'contact_id'     => (int) $contact_id,
			'mobile'         => szc_normalize_mobile( $mobile ),
			'message'        => (string) $message,
			'pattern_code'   => (string) $o['pattern_code'],
			'pattern_values' => wp_json_encode( (array) $o['values'] ),
			'template_id'    => (int) $o['template_id'],
			'send_at'        => $send_at,
			'status'         => 'pending',
			'attempts'       => 0,
			'created_by'     => get_current_user_id(),
			'created_at'     => current_time( 'mysql' ),
		) );
		return (int) $wpdb->insert_id;
	}

	/**
	 * زمان‌بندی پیامک تشکر/دعوت پس از تماس.
	 * template_id=0 یعنی از قالب پیش‌فرضِ تنظیمات (یا اولین قالب) استفاده شود.
	 */
	public static function schedule_thanks( $contact, $template_id = 0 ) {
		$tid = $template_id ?: (int) SZC_Settings::get( 'auto_template_id' );
		$tpl = $tid ? SZC_Templates::get( $tid ) : null;
		if ( ! $tpl ) {
			$all = SZC_Templates::all();
			$tpl = $all ? $all[0] : null;
		}
		if ( ! $tpl ) {
			return false;
		}
		$delay = max( 1, (int) SZC_Settings::get( 'auto_delay_min' ) );
		$dt    = new DateTime( 'now', wp_timezone() );
		$dt->modify( '+' . $delay . ' minutes' );
		$send_at = $dt->format( 'Y-m-d H:i:s' );

		$r = self::resolve( $tpl, $contact );
		self::enqueue( (int) $contact->id, $contact->mobile, $r['message'], $send_at, array(
			'pattern_code' => $r['pattern_code'],
			'values'       => $r['values'],
			'template_id'  => (int) $tpl->id,
		) );

		SZC_Activity::log( (int) $contact->id, 'sms', array(
			'outcome' => 'scheduled',
			'body'    => $r['message'],
			'meta'    => array( 'template_id' => (int) $tpl->id, 'send_at' => $send_at ),
		) );
		return true;
	}

	public static function cancel_queued_for_contact( $contact_id ) {
		global $wpdb;
		$wpdb->update( self::queue_table(), array( 'status' => 'canceled' ),
			array( 'contact_id' => (int) $contact_id, 'status' => 'pending' ) );
	}

	protected static function within_window() {
		$s    = SZC_Settings::all();
		$from = (int) $s['send_from'];
		$to   = (int) $s['send_to'];
		$h    = (int) wp_date( 'G' );
		if ( $from >= $to ) {
			return true; // پیکربندی نامعتبر → محدود نکن
		}
		return $h >= $from && $h < $to;
	}

	public static function queue_counts() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT status, COUNT(*) c FROM ' . self::queue_table() . ' GROUP BY status', OBJECT_K );
		$out  = array( 'pending' => 0, 'sent' => 0, 'failed' => 0, 'canceled' => 0 );
		foreach ( $rows as $k => $r ) {
			$out[ $k ] = (int) $r->c;
		}
		return $out;
	}

	/** جاروب صف: ارسال پیامک‌های سررسیدشده در بازه‌ی مجاز. */
	public static function run_queue() {
		global $wpdb;
		if ( ! self::enabled() || ! self::within_window() ) {
			return;
		}
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::queue_table() . " WHERE status='pending' AND send_at<=%s ORDER BY send_at ASC LIMIT %d",
			current_time( 'mysql' ), self::MAX_PER_RUN ) );
		foreach ( $rows as $row ) {
			$contact = SZC_Contacts::get( $row->contact_id );
			if ( $contact && $contact->opt_out ) {
				$wpdb->update( self::queue_table(), array( 'status' => 'canceled', 'response' => 'لغو دریافت' ), array( 'id' => (int) $row->id ) );
				continue;
			}
			$res = ( $row->pattern_code !== '' && SZC_Settings::get( 'sms_mode' ) === 'pattern' )
				? self::send_pattern( $row->mobile, $row->pattern_code, json_decode( (string) $row->pattern_values, true ) ?: array() )
				: self::send_text( $row->mobile, $row->message );

			$ok = ! empty( $res['ok'] );
			$wpdb->update( self::queue_table(), array(
				'status'   => $ok ? 'sent' : 'failed',
				'attempts' => (int) $row->attempts + 1,
				'response' => (string) ( $res['msg'] ?? '' ),
				'sent_at'  => $ok ? current_time( 'mysql' ) : null,
			), array( 'id' => (int) $row->id ) );

			if ( $row->contact_id ) {
				SZC_Activity::log( (int) $row->contact_id, 'sms', array(
					'outcome' => $ok ? 'sent' : 'failed',
					'body'    => $row->message,
					'meta'    => array( 'queue_id' => (int) $row->id, 'response' => $res['msg'] ?? '' ),
				) );
			}
		}
	}
}
