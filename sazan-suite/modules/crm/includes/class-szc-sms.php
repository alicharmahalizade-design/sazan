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
		return ! empty( $s['sms_enabled'] ) && $s['sms_apikey'] !== '' && ( self::provider() === 'smsir' || $s['sms_originator'] !== '' );
	}

	/** سرویس‌دهنده‌ی پیامک: ippanel (فراز/آی‌پی‌پنل) یا smsir. */
	protected static function provider() {
		return 'smsir';
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

	/** آیا ارسال به پترن محدود است؟ نسخه فعلی پترن و متن آزاد را پشتیبانی می‌کند. */
	public static function pattern_only() {
		return false;
	}

	/** متن آزاد SMS.ir علاوه بر API Key به خط ارسال‌کننده نیاز دارد. */
	public static function free_text_ready() {
		return self::enabled() && trim( (string) SZC_Settings::get( 'sms_originator' ) ) !== '';
	}

	public static function send_text( $to, $message ) {
		if ( ! self::free_text_ready() ) {
			return array( 'ok' => false, 'msg' => 'برای ارسال متن آزاد، خط ارسال‌کننده را در تنظیمات پیامک وارد کنید.' );
		}
		$to = szc_normalize_mobile( $to );
		$message = trim( wp_strip_all_tags( (string) $message ) );
		if ( $to === '' || $message === '' ) {
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

	/** اعلان‌های داخلی مدیر/کارشناس با یک پترن عمومی و پارامتر message. */
	public static function send_system_pattern( $to, $message ) {
		$code  = trim( (string) SZC_Settings::get( 'system_pattern_code' ) );
		$param = preg_replace( '/[^A-Za-z0-9_]/', '', (string) SZC_Settings::get( 'system_pattern_param' ) );
		if ( $code === '' ) {
			return array( 'ok' => false, 'msg' => 'کد پترن اعلان‌های سیستمی تنظیم نشده است.' );
		}
		return self::send_pattern( $to, $code, array( $param ?: 'message' => wp_strip_all_tags( (string) $message ) ) );
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
		$pattern = trim( (string) $template->pattern_code );
		$pattern_values = array();
		if ( preg_match_all( '/%([A-Za-z0-9_]+)%/', (string) $template->body, $matches ) ) {
			foreach ( array_unique( $matches[1] ) as $key ) {
				if ( array_key_exists( $key, $vars ) ) {
					$pattern_values[ $key ] = $vars[ $key ];
				}
			}
		}
		return array(
			'message'      => $body,
			'pattern_code' => $pattern,
			'values'       => $pattern_values,
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
		if ( trim( (string) $tpl->pattern_code ) === '' ) {
			return array( 'ok' => false, 'msg' => 'این قالب کد پترن ندارد و ارسال متوقف شد.' );
		}
		if ( SZC_Blacklist::is_blocked( $contact->mobile ) ) {
			return array( 'ok' => false, 'msg' => 'این شماره در لیست سیاه است.' );
		}
		$r   = self::resolve( $tpl, $contact );
		$res = self::send_pattern( $contact->mobile, $r['pattern_code'], $r['values'] );

		SZC_Activity::log( (int) $contact->id, 'sms', array(
			'outcome' => ! empty( $res['ok'] ) ? 'sent' : 'failed',
			'body'    => $r['message'],
			'meta'    => array( 'template_id' => (int) $template_id, 'response' => $res['msg'] ?? '', 'source' => 'immediate' ),
		) );
		if ( ! empty( $res['ok'] ) ) {
			self::cancel_pending_auto_for_contact( (int) $contact->id );
		}
		return $res;
	}

	/* ==================== صف / زمان‌بندی ==================== */

	public static function enqueue( $contact_id, $mobile, $message, $send_at, $opts = array() ) {
		global $wpdb;
		$o = wp_parse_args( $opts, array( 'pattern_code' => '', 'values' => array(), 'template_id' => 0, 'enrollment_id' => 0, 'source' => '' ) );
		$wpdb->insert( self::queue_table(), array(
			'contact_id'     => (int) $contact_id,
			'mobile'         => szc_normalize_mobile( $mobile ),
			'message'        => (string) $message,
			'pattern_code'   => (string) $o['pattern_code'],
			'pattern_values' => wp_json_encode( (array) $o['values'] ),
			'template_id'    => (int) $o['template_id'],
			'enrollment_id'  => (int) $o['enrollment_id'],
			'source'         => sanitize_key( $o['source'] ),
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
	public static function schedule_thanks( $contact, $template_id = 0, $source = 'auto_after_call' ) {
		if ( ! self::is_sendable( $contact->mobile, $contact ) ) {
			return false;
		}
		$tid = $template_id ?: (int) SZC_Settings::get( 'auto_template_id' );
		$tpl = $tid ? SZC_Templates::get( $tid ) : null;
		if ( ! $tpl ) {
			$all = SZC_Templates::all_patterned();
			$tpl = $all ? $all[0] : null;
		}
		if ( ! $tpl ) {
			return false;
		}
		if ( trim( (string) $tpl->pattern_code ) === '' ) {
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
			'source'       => sanitize_key( $source ),
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
		if ( ! $tpl || trim( (string) $tpl->pattern_code ) === '' ) {
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
				'source'       => 'bulk',
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
		if ( ! self::free_text_ready() ) {
			return array( 'queued' => 0, 'skipped' => count( (array) $contact_ids ), 'error' => 'برای ارسال متن آزاد، خط ارسال‌کننده را در تنظیمات پیامک وارد کنید.' );
		}
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
			self::enqueue( (int) $c->id, $c->mobile, $body, $when, array( 'source' => 'bulk' ) );
			$queued++;
		}
		return array( 'queued' => $queued, 'skipped' => $skipped );
	}

	public static function cancel_queued_for_contact( $contact_id ) {
		global $wpdb;
		$wpdb->update( self::queue_table(), array( 'status' => 'canceled' ),
			array( 'contact_id' => (int) $contact_id, 'status' => 'pending' ) );
	}

	/** لغو فقط پیامکِ خودکارِ پس از تماس؛ صف‌های دستی، گروهی و دنباله‌ها دست‌نخورده می‌مانند. */
	public static function cancel_pending_auto_for_contact( $contact_id ) {
		global $wpdb;
		return (int) $wpdb->update(
			self::queue_table(),
			array( 'status' => 'canceled', 'response' => 'لغو شد: پیامک فوری ارسال شد' ),
			array( 'contact_id' => (int) $contact_id, 'status' => 'pending', 'source' => 'auto_after_call' )
		);
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
		$out  = array( 'pending' => 0, 'processing' => 0, 'sent' => 0, 'failed' => 0, 'canceled' => 0 );
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
		$lock_name = 'szc_sms_queue_' . (int) get_current_blog_id();
		$locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,0)', $lock_name ) );
		if ( $locked !== 1 ) {
			return;
		}
		try {
		// Recover only jobs whose worker disappeared; a normal send should finish well before 10 minutes.
		$stale = wp_date( 'Y-m-d H:i:s', time() - 10 * MINUTE_IN_SECONDS );
		$wpdb->query( $wpdb->prepare(
			'UPDATE ' . self::queue_table() . " SET status='pending', locked_at=NULL, response='بازیابی قفل منقضی‌شده' WHERE status='processing' AND locked_at<%s",
			$stale
		) );
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
			$claimed = $wpdb->update(
				self::queue_table(),
				array( 'status' => 'processing', 'locked_at' => current_time( 'mysql' ) ),
				array( 'id' => (int) $row->id, 'status' => 'pending' )
			);
			if ( $claimed !== 1 ) {
				continue;
			}
			$contact = SZC_Contacts::get( $row->contact_id );
			if ( ( (int) $row->contact_id > 0 && ! $contact ) || ( $contact && $contact->opt_out ) || SZC_Blacklist::is_blocked( $row->mobile ) ) {
				$wpdb->update( self::queue_table(), array( 'status' => 'canceled', 'locked_at' => null, 'response' => 'لغو دریافت/لیست سیاه' ), array( 'id' => (int) $row->id, 'status' => 'processing' ) );
				continue;
			}
			$res = $row->pattern_code !== ''
				? self::send_pattern( $row->mobile, $row->pattern_code, json_decode( (string) $row->pattern_values, true ) ?: array() )
				: self::send_text( $row->mobile, $row->message );

			$ok = ! empty( $res['ok'] );
			$attempts = (int) $row->attempts + 1;
			$upd = array(
				'status'   => $ok ? 'sent' : ( $attempts < 3 ? 'pending' : 'failed' ),
				'attempts' => $attempts,
				'response' => (string) ( $res['msg'] ?? '' ),
				'sent_at'  => $ok ? current_time( 'mysql' ) : null,
				'locked_at'=> null,
			);
			if ( ! $ok && $attempts < 3 ) {
				$delays = array( 1 => 5, 2 => 30 );
				$upd['send_at'] = wp_date( 'Y-m-d H:i:s', time() + $delays[ $attempts ] * MINUTE_IN_SECONDS );
				$upd['response'] .= ' — تلاش بعدی ' . $delays[ $attempts ] . ' دقیقه دیگر';
			}
			if ( $ok ) {
				$upd['provider_msgid'] = (string) ( $res['msgid'] ?? '' );
				$upd['delivery']       = ( $res['msgid'] ?? '' ) !== '' ? 'pending' : '';
			}
			$wpdb->update( self::queue_table(), $upd, array( 'id' => (int) $row->id, 'status' => 'processing' ) );

			if ( $row->contact_id ) {
				SZC_Activity::log( (int) $row->contact_id, 'sms', array(
					'outcome' => $ok ? 'sent' : ( $attempts < 3 ? 'retry' : 'failed' ),
					'body'    => $row->message,
					'meta'    => array( 'queue_id' => (int) $row->id, 'response' => $res['msg'] ?? '' ),
				) );
			}
		}
		self::refresh_delivery();
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		}
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

	public static function analytics( $from, $to ) {
		global $wpdb;
		$from = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $from ) ? $from : wp_date( 'Y-m-d', time() - 30 * DAY_IN_SECONDS );
		$to   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $to ) ? $to : wp_date( 'Y-m-d' );
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT COUNT(*) total,
			 SUM(status='sent') sent,
			 SUM(status='failed') failed,
			 SUM(status='pending' OR status='processing') pending,
			 SUM(status='canceled') canceled,
			 SUM(delivery='delivered') delivered,
			 SUM(delivery='undelivered') undelivered,
			 SUM(CASE WHEN status='sent' THEN GREATEST(1,CEIL(CHAR_LENGTH(COALESCE(message,''))/70)) ELSE 0 END) parts
			 FROM " . self::queue_table() . ' WHERE created_at BETWEEN %s AND %s',
			$from . ' 00:00:00', $to . ' 23:59:59'
		), ARRAY_A );
		$total = (int) ( $row['total'] ?? 0 );
		$sent  = (int) ( $row['sent'] ?? 0 );
		$parts = (int) ( $row['parts'] ?? 0 );
		$contacts = max( 1, SZC_Contacts::total() );
		$optouts = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . SZC_Contacts::table() . ' WHERE deleted_at IS NULL AND opt_out=1' );
		return array(
			'total' => $total, 'sent' => $sent, 'failed' => (int) ( $row['failed'] ?? 0 ),
			'pending' => (int) ( $row['pending'] ?? 0 ), 'canceled' => (int) ( $row['canceled'] ?? 0 ),
			'delivered' => (int) ( $row['delivered'] ?? 0 ), 'undelivered' => (int) ( $row['undelivered'] ?? 0 ),
			'delivery_rate' => $sent > 0 ? round( (int) ( $row['delivered'] ?? 0 ) / $sent * 100, 1 ) : 0,
			'error_rate' => $total > 0 ? round( (int) ( $row['failed'] ?? 0 ) / $total * 100, 1 ) : 0,
			'cancel_rate' => round( $optouts / $contacts * 100, 1 ),
			'parts' => $parts,
			'estimated_cost' => $parts * (float) SZC_Settings::get( 'sms_cost_per_part' ),
		);
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
			if ( strpos( $txt, 'undeliver' ) === false && strpos( $txt, 'deliver' ) !== false ) {
				return 'delivered';
			}
			if ( strpos( $txt, 'undeliver' ) !== false || strpos( $txt, 'fail' ) !== false || strpos( $txt, 'expire' ) !== false ) {
				return 'undelivered';
			}
		}
		return 'pending';
	}
}
