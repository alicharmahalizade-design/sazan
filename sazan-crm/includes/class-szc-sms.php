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

	/** آیا می‌توان به این مخاطب/شماره پیامک زد؟ (لیست سیاه + لغو دریافت) */
	public static function is_sendable( $mobile, $contact = null ) {
		if ( $contact && (int) $contact->opt_out === 1 ) {
			return false;
		}
		return ! SZC_Blacklist::is_blocked( $mobile );
	}

	public static function queue_table() {
		global $wpdb;
		return $wpdb->prefix . 'szc_sms_queue';
	}

	/* ==================== دروازه ==================== */

	public static function enabled() {
		$s = SZC_Settings::all();
		return ! empty( $s['sms_enabled'] ) && $s['sms_apikey'] !== '' && $s['sms_originator'] !== '';
	}

	/** سرویس‌دهنده‌ی پیامک: ippanel (فراز/آی‌پی‌پنل) یا smsir. */
	protected static function provider() {
		return SZC_Settings::get( 'sms_provider' ) === 'smsir' ? 'smsir' : 'ippanel';
	}

	protected static function base() {
		$b = rtrim( (string) SZC_Settings::get( 'sms_base' ), '/' );
		if ( self::provider() === 'smsir' ) {
			return ( $b !== '' && strpos( $b, 'sms.ir' ) !== false ) ? $b : 'https://api.sms.ir';
		}
		return $b !== '' ? $b : 'https://rest.ippanel.com/v1';
	}

	protected static function headers() {
		if ( self::provider() === 'smsir' ) {
			return array(
				'X-API-KEY'    => (string) SZC_Settings::get( 'sms_apikey' ),
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			);
		}
		return array(
			'apikey'       => (string) SZC_Settings::get( 'sms_apikey' ),
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		);
	}

	protected static function line_number() {
		$o = trim( (string) SZC_Settings::get( 'sms_originator' ) );
		return ctype_digit( $o ) ? (int) $o : $o;
	}

	public static function send_text( $to, $message ) {
		$to = szc_normalize_mobile( $to );
		if ( $to === '' || trim( (string) $message ) === '' ) {
			return array( 'ok' => false, 'msg' => 'گیرنده یا متن نامعتبر است.' );
		}
		if ( self::provider() === 'smsir' ) {
			return self::post( '/v1/send/bulk', array(
				'lineNumber'  => self::line_number(),
				'messageText' => (string) $message,
				'mobiles'     => array( $to ),
			) );
		}
		return self::post( '/messages', array(
			'originator' => (string) SZC_Settings::get( 'sms_originator' ),
			'recipients' => array( $to ),
			'message'    => (string) $message,
		) );
	}

	/** ارسال پترن. در sms.ir «کد پترن» همان templateId عددی است. */
	public static function send_pattern( $to, $pattern_code, $values ) {
		$to = szc_normalize_mobile( $to );
		if ( $to === '' || trim( (string) $pattern_code ) === '' ) {
			return array( 'ok' => false, 'msg' => 'گیرنده یا کد پترن نامعتبر است.' );
		}
		if ( self::provider() === 'smsir' ) {
			$params = array();
			foreach ( (array) $values as $k => $v ) {
				$params[] = array( 'name' => (string) $k, 'value' => (string) $v );
			}
			return self::post( '/v1/send/verify', array(
				'mobile'     => $to,
				'templateId' => (int) $pattern_code,
				'parameters' => $params,
			) );
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
		$json = json_decode( (string) $raw, true );
		if ( $ok && self::provider() === 'smsir' ) {
			if ( is_array( $json ) && isset( $json['status'] ) && (int) $json['status'] !== 1 ) {
				$ok  = false;
				$raw = $json['message'] ?? $raw;
			}
		}
		return array(
			'ok'    => $ok,
			'msg'   => $ok ? 'ارسال شد.' : ( 'خطای سرویس پیامک (' . $code . '): ' . wp_strip_all_tags( (string) $raw ) ),
			'code'  => $code,
			'msgid' => $ok && is_array( $json ) ? self::extract_msgid( $json ) : '',
		);
	}

	/** استخراجِ شناسه‌ی پیامِ سرویس‌دهنده از پاسخِ ارسال (برای پیگیریِ تحویل). */
	protected static function extract_msgid( $json ) {
		$data = isset( $json['data'] ) && is_array( $json['data'] ) ? $json['data'] : $json;
		foreach ( array( 'message_id', 'messageId', 'bulk_id', 'bulkId', 'packId', 'pack_id', 'recId', 'messageIds' ) as $k ) {
			if ( isset( $data[ $k ] ) ) {
				$v = $data[ $k ];
				if ( is_array( $v ) ) {
					$v = reset( $v );
				}
				if ( $v !== '' && $v !== null ) {
					return (string) $v;
				}
			}
		}
		return '';
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
		if ( SZC_Blacklist::is_blocked( $contact->mobile ) ) {
			return array( 'ok' => false, 'msg' => 'این شماره در لیست سیاه است.' );
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
		$o = wp_parse_args( $opts, array( 'pattern_code' => '', 'values' => array(), 'template_id' => 0, 'enrollment_id' => 0 ) );
		$wpdb->insert( self::queue_table(), array(
			'contact_id'     => (int) $contact_id,
			'mobile'         => szc_normalize_mobile( $mobile ),
			'message'        => (string) $message,
			'pattern_code'   => (string) $o['pattern_code'],
			'pattern_values' => wp_json_encode( (array) $o['values'] ),
			'template_id'    => (int) $o['template_id'],
			'enrollment_id'  => (int) $o['enrollment_id'],
			'send_at'        => $send_at,
			'status'         => 'pending',
			'attempts'       => 0,
			'created_by'     => SZC_Auth::actor_id(),
			'created_at'     => current_time( 'mysql' ),
		) );
		return (int) $wpdb->insert_id;
	}

	/**
	 * زمان‌بندی پیامک تشکر/دعوت پس از تماس.
	 * template_id=0 یعنی از قالب پیش‌فرضِ تنظیمات (یا اولین قالب) استفاده شود.
	 */
	public static function schedule_thanks( $contact, $template_id = 0 ) {
		if ( ! self::is_sendable( $contact->mobile, $contact ) ) {
			return false;
		}
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

	/**
	 * صف‌بندی یک قالب برای گروهی از مخاطبین (ارسال گروهی).
	 * $when_mysql=null یعنی همین حالا (در جاروب بعدی و در بازه‌ی مجاز ارسال می‌شود).
	 * خروجی: array( queued, skipped ).
	 */
	public static function enqueue_template_bulk( $contact_ids, $template_id, $when_mysql = null ) {
		$tpl = SZC_Templates::get( $template_id );
		if ( ! $tpl ) {
			return array( 'queued' => 0, 'skipped' => 0 );
		}
		$when    = $when_mysql ?: current_time( 'mysql' );
		$queued  = 0;
		$skipped = 0;
		foreach ( (array) $contact_ids as $cid ) {
			$c = SZC_Contacts::get( $cid );
			if ( ! $c || ! self::is_sendable( $c->mobile, $c ) ) {
				$skipped++;
				continue;
			}
			$r = self::resolve( $tpl, $c );
			self::enqueue( (int) $c->id, $c->mobile, $r['message'], $when, array(
				'pattern_code' => $r['pattern_code'],
				'values'       => $r['values'],
				'template_id'  => (int) $tpl->id,
			) );
			$queued++;
		}
		return array( 'queued' => $queued, 'skipped' => $skipped );
	}

	/**
	 * صف‌بندی یک متنِ آزاد (با متغیر) برای گروهی از مخاطبین (ارسال همگانی).
	 * متن برای هر مخاطب با متغیرهای همان مخاطب پر می‌شود.
	 * خروجی: array( queued, skipped ).
	 */
	public static function enqueue_text_bulk( $contact_ids, $text, $when_mysql = null ) {
		$text = trim( (string) $text );
		if ( $text === '' ) {
			return array( 'queued' => 0, 'skipped' => 0 );
		}
		$when    = $when_mysql ?: current_time( 'mysql' );
		$queued  = 0;
		$skipped = 0;
		foreach ( (array) $contact_ids as $cid ) {
			$c = SZC_Contacts::get( $cid );
			if ( ! $c || ! self::is_sendable( $c->mobile, $c ) ) {
				$skipped++;
				continue;
			}
			$body = SZC_Templates::fill( wp_strip_all_tags( $text ), SZC_Contacts::vars( $c ) );
			self::enqueue( (int) $c->id, $c->mobile, $body, $when );
			$queued++;
		}
		return array( 'queued' => $queued, 'skipped' => $skipped );
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

	/** تعداد پیامکِ ارسال‌شده از ابتدای امروز (برای سقف روزانه). */
	public static function sent_today() {
		global $wpdb;
		$start = wp_date( 'Y-m-d 00:00:00' );
		return (int) $wpdb->get_var( $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . self::queue_table() . " WHERE status='sent' AND sent_at>=%s", $start ) );
	}

	/** جاروب صف: ارسال پیامک‌های سررسیدشده در بازه‌ی مجاز، با سقف در هر اجرا و روزانه. */
	public static function run_queue() {
		global $wpdb;
		if ( ! self::enabled() || ! self::within_window() ) {
			return;
		}
		$per_run = max( 1, (int) SZC_Settings::get( 'max_per_run' ) );
		$per_day = (int) SZC_Settings::get( 'max_per_day' );
		if ( $per_day > 0 ) {
			$remaining = $per_day - self::sent_today();
			if ( $remaining <= 0 ) {
				return;
			}
			$per_run = min( $per_run, $remaining );
		}
		$rows = $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM ' . self::queue_table() . " WHERE status='pending' AND send_at<=%s ORDER BY send_at ASC LIMIT %d",
			current_time( 'mysql' ), $per_run ) );
		foreach ( $rows as $row ) {
			$contact = SZC_Contacts::get( $row->contact_id );
			if ( ( $contact && $contact->opt_out ) || SZC_Blacklist::is_blocked( $row->mobile ) ) {
				$wpdb->update( self::queue_table(), array( 'status' => 'canceled', 'response' => 'لغو دریافت/لیست سیاه' ), array( 'id' => (int) $row->id ) );
				continue;
			}
			$res = ( $row->pattern_code !== '' && SZC_Settings::get( 'sms_mode' ) === 'pattern' )
				? self::send_pattern( $row->mobile, $row->pattern_code, json_decode( (string) $row->pattern_values, true ) ?: array() )
				: self::send_text( $row->mobile, $row->message );

			$ok = ! empty( $res['ok'] );
			$upd = array(
				'status'   => $ok ? 'sent' : 'failed',
				'attempts' => (int) $row->attempts + 1,
				'response' => (string) ( $res['msg'] ?? '' ),
				'sent_at'  => $ok ? current_time( 'mysql' ) : null,
			);
			if ( $ok ) {
				$upd['provider_msgid'] = (string) ( $res['msgid'] ?? '' );
				$upd['delivery']       = ( $res['msgid'] ?? '' ) !== '' ? 'pending' : '';
			}
			$wpdb->update( self::queue_table(), $upd, array( 'id' => (int) $row->id ) );

			if ( $row->contact_id ) {
				SZC_Activity::log( (int) $row->contact_id, 'sms', array(
					'outcome' => $ok ? 'sent' : 'failed',
					'body'    => $row->message,
					'meta'    => array( 'queue_id' => (int) $row->id, 'response' => $res['msg'] ?? '' ),
				) );
			}
		}
		self::refresh_delivery();
	}

	/* ==================== گزارشِ تحویل (Delivery report) ==================== */

	const DELIVERY_MAX_CHECKS = 6;

	/** شمارِ وضعیت‌های تحویل در N روز اخیر برای گزارش. */
	public static function delivery_counts( $days = 7 ) {
		global $wpdb;
		$since = wp_date( 'Y-m-d 00:00:00', time() - ( max( 1, (int) $days ) - 1 ) * DAY_IN_SECONDS );
		$rows  = $wpdb->get_results( $wpdb->prepare(
			'SELECT delivery, COUNT(*) c FROM ' . self::queue_table() . " WHERE status='sent' AND sent_at>=%s GROUP BY delivery", $since ), OBJECT_K );
		$out = array( 'delivered' => 0, 'undelivered' => 0, 'pending' => 0, 'unknown' => 0, 'sent' => 0 );
		foreach ( $rows as $k => $r ) {
			$key = in_array( $k, array( 'delivered', 'undelivered', 'pending' ), true ) ? $k : 'unknown';
			$out[ $key ] += (int) $r->c;
			$out['sent'] += (int) $r->c;
		}
		$done          = $out['delivered'] + $out['undelivered'];
		$out['rate']   = $done > 0 ? round( $out['delivered'] / $done * 100, 1 ) : 0;
		return $out;
	}

	/**
	 * به‌روزرسانیِ وضعیتِ تحویلِ پیامک‌های ارسال‌شده از سرویس‌دهنده (best-effort).
	 * فقط ردیف‌هایی که شناسه‌ی سرویس‌دهنده دارند و هنوز قطعی نشده‌اند بررسی می‌شوند.
	 */
	public static function refresh_delivery( $limit = 40 ) {
		global $wpdb;
		if ( ! self::enabled() ) {
			return 0;
		}
		$since = wp_date( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS );
		$rows  = $wpdb->get_results( $wpdb->prepare(
			'SELECT id, provider_msgid, delivery_checks FROM ' . self::queue_table()
			. " WHERE status='sent' AND provider_msgid<>'' AND delivery IN ('pending','') AND delivery_checks<%d AND sent_at>=%s ORDER BY sent_at DESC LIMIT %d",
			self::DELIVERY_MAX_CHECKS, $since, (int) $limit ) );
		$done = 0;
		foreach ( $rows as $row ) {
			$state = self::query_delivery_status( $row->provider_msgid );
			$upd   = array( 'delivery_checks' => (int) $row->delivery_checks + 1 );
			if ( in_array( $state, array( 'delivered', 'undelivered' ), true ) ) {
				$upd['delivery']    = $state;
				$upd['delivery_at'] = current_time( 'mysql' );
				$done++;
			} elseif ( (int) $row->delivery_checks + 1 >= self::DELIVERY_MAX_CHECKS ) {
				$upd['delivery'] = 'unknown'; // پس از چند تلاش، نامشخص می‌ماند.
			} else {
				$upd['delivery'] = 'pending';
			}
			$wpdb->update( self::queue_table(), $upd, array( 'id' => (int) $row->id ) );
		}
		return $done;
	}

	/**
	 * استعلامِ وضعیتِ تحویلِ یک پیام از سرویس‌دهنده.
	 * خروجی: 'delivered' | 'undelivered' | 'pending' | 'unknown'.
	 * کدهای دقیق بین پنل‌ها فرق دارد؛ این‌جا رایج‌ترین حالت‌ها تفسیر می‌شود.
	 */
	protected static function query_delivery_status( $msgid ) {
		$msgid = rawurlencode( (string) $msgid );
		if ( self::provider() === 'smsir' ) {
			$url = self::base() . '/v1/send/' . $msgid;
		} else {
			$url = self::base() . '/messages/' . $msgid;
		}
		$res = wp_remote_get( $url, array( 'timeout' => 15, 'headers' => self::headers() ) );
		if ( is_wp_error( $res ) ) {
			return 'pending';
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( $code < 200 || $code >= 300 ) {
			return 'pending';
		}
		$j = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $j ) ) {
			return 'pending';
		}
		$data = isset( $j['data'] ) && is_array( $j['data'] ) ? $j['data'] : $j;

		// وضعیتِ عددیِ تحویل (deliveryState) در sms.ir و مشابه: 1=رسید، سایرِ مقادیرِ نهایی=نرسید.
		foreach ( array( 'deliveryState', 'delivery_state', 'status', 'state' ) as $k ) {
			if ( isset( $data[ $k ] ) && is_numeric( $data[ $k ] ) ) {
				$v = (int) $data[ $k ];
				if ( $v === 1 ) {
					return 'delivered';
				}
				if ( in_array( $v, array( 2, 3, 4, 5, 6, 8, 9 ), true ) ) {
					return 'undelivered';
				}
				return 'pending';
			}
		}
		// وضعیتِ متنی.
		$txt = strtolower( (string) ( $data['deliveryStatus'] ?? $data['status'] ?? '' ) );
		if ( $txt !== '' ) {
			if ( strpos( $txt, 'deliver' ) !== false || strpos( $txt, 'sent' ) !== false ) {
				return 'delivered';
			}
			if ( strpos( $txt, 'undeliver' ) !== false || strpos( $txt, 'fail' ) !== false || strpos( $txt, 'expire' ) !== false ) {
				return 'undelivered';
			}
		}
		return 'pending';
	}
}
