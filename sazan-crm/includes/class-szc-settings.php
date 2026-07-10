<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** تنظیمات، دسترسی، و تعریف مراحل/اولویت‌های CRM. */
class SZC_Settings {

	const OPTION = 'szc_settings';
	const CAP    = 'szc_access';

	public static function init() {
		add_filter( 'user_has_cap', array( __CLASS__, 'grant_cap' ), 10, 3 );
	}

	/** دسترسی مجازی szc_access را به مدیران و کاربران مجاز می‌دهد. */
	public static function grant_cap( $allcaps, $caps, $args ) {
		if ( ! in_array( self::CAP, (array) $caps, true ) ) {
			return $allcaps;
		}
		$uid = isset( $args[1] ) ? (int) $args[1] : 0;
		if ( ! empty( $allcaps['manage_options'] ) || ( $uid && in_array( $uid, self::allowed_user_ids(), true ) ) ) {
			$allcaps[ self::CAP ] = true;
		}
		return $allcaps;
	}

	public static function can_access() {
		return current_user_can( self::CAP );
	}

	public static function defaults() {
		return array(
			'access_users'   => array(),
			'sms_enabled'    => 0,
			'sms_base'       => 'https://rest.ippanel.com/v1',
			'sms_apikey'     => '',
			'sms_originator' => '',
			'sms_mode'       => 'text', // text | pattern
			'send_from'      => 9,      // ساعت شروع مجاز ارسال
			'send_to'        => 21,     // ساعت پایان مجاز ارسال
			'auto_after_call' => 1,
			'auto_template_id' => 0,
			'auto_delay_min' => 60,
			'mini_link'      => '',
			'intro_link'     => '',
		);
	}

	public static function all() {
		$s = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $s ) ? $s : array(), self::defaults() );
	}

	public static function get( $key ) {
		$s = self::all();
		return isset( $s[ $key ] ) ? $s[ $key ] : null;
	}

	public static function save( $new ) {
		update_option( self::OPTION, wp_parse_args( $new, self::all() ) );
	}

	public static function allowed_user_ids() {
		$ids = self::get( 'access_users' );
		return array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
	}

	/**
	 * بار اول (یا ارتقا): اگر تنظیمات پیامک خالی بود، از افزونه‌ی سازان پنل
	 * (همان پنل فراز/آی‌پی‌پنل) پیش‌فرض بردار و یک قالب تشکر نمونه بساز.
	 */
	public static function maybe_seed() {
		$cur = get_option( self::OPTION, null );
		if ( ! is_array( $cur ) ) {
			$cur = array();
		}
		if ( empty( $cur['sms_apikey'] ) ) {
			$sazan = get_option( 'szp_eval_settings', array() );
			if ( is_array( $sazan ) && ! empty( $sazan['sms_apikey'] ) ) {
				$cur['sms_base']       = $sazan['sms_base'] ?? 'https://rest.ippanel.com/v1';
				$cur['sms_apikey']     = $sazan['sms_apikey'];
				$cur['sms_originator'] = $sazan['sms_originator'] ?? '';
				$cur['sms_mode']       = ( ( $sazan['sms_mode'] ?? 'text' ) === 'pattern' ) ? 'pattern' : 'text';
			}
		}
		update_option( self::OPTION, wp_parse_args( $cur, self::defaults() ) );

		if ( class_exists( 'SZC_Templates' ) ) {
			SZC_Templates::seed_defaults();
		}
	}

	/* ==================== مراحل و اولویت‌ها ==================== */

	/** مراحل قیف فروش: key => label. قابل توسعه در فازهای بعد. */
	public static function stages() {
		return apply_filters( 'szc_stages', array(
			'new'            => 'جدید',
			'contacted'      => 'تماس گرفته شد',
			'interested'     => 'علاقه‌مند',
			'mini_sent'      => 'مینی‌دوره ارسال شد',
			'invited'        => 'دعوت به معارفه',
			'registered'     => 'ثبت‌نام کرد',
			'not_interested' => 'بی‌علاقه',
			'wrong'          => 'شماره اشتباه',
		) );
	}

	public static function stage_label( $key ) {
		$all = self::stages();
		return isset( $all[ $key ] ) ? $all[ $key ] : $key;
	}

	/** اولویت‌ها: key => [label, color]. */
	public static function priorities() {
		return array(
			'hot'  => array( 'label' => 'داغ',  'color' => '#ef4444' ),
			'warm' => array( 'label' => 'گرم',  'color' => '#f59e0b' ),
			'cold' => array( 'label' => 'سرد',  'color' => '#3b82f6' ),
		);
	}

	public static function priority_meta( $key ) {
		$all = self::priorities();
		return isset( $all[ $key ] ) ? $all[ $key ] : $all['warm'];
	}

	/** نتیجه‌های تماس: key => label. */
	public static function call_outcomes() {
		return array(
			'answered'  => 'پاسخ داد',
			'no_answer' => 'پاسخ نداد',
			'busy'      => 'مشغول',
			'callback'  => 'درخواست تماس مجدد',
			'wrong'     => 'شماره اشتباه',
			'not_interested' => 'بی‌علاقه',
		);
	}

	public static function outcome_label( $key ) {
		$all = self::call_outcomes();
		return isset( $all[ $key ] ) ? $all[ $key ] : $key;
	}
}
