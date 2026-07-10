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
			'managers'       => array(), // نقش مدیر: همه‌ی سرنخ‌ها را می‌بیند
			'agents'         => array(), // نقش کارشناس: فقط سرنخ‌های خودش
			'max_per_run'    => 80,      // سقف ارسال در هر اجرای صف (هر ۵ دقیقه)
			'max_per_day'    => 0,       // سقف ارسال روزانه (۰ = نامحدود)
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

	public static function manager_ids() {
		return array_values( array_filter( array_map( 'intval', (array) self::get( 'managers' ) ) ) );
	}

	public static function agent_ids() {
		return array_values( array_filter( array_map( 'intval', (array) self::get( 'agents' ) ) ) );
	}

	/** همه‌ی کاربران دارای دسترسی (مدیر + کارشناس). */
	public static function allowed_user_ids() {
		return array_values( array_unique( array_merge( self::manager_ids(), self::agent_ids() ) ) );
	}

	/** آیا این کاربر مدیرِ CRM است؟ (مدیران سایت همیشه بله) */
	public static function is_manager( $uid = 0 ) {
		$uid = $uid ? (int) $uid : get_current_user_id();
		if ( user_can( $uid, 'manage_options' ) ) {
			return true;
		}
		return in_array( $uid, self::manager_ids(), true );
	}

	/** نقش کاربر در CRM: manager | agent | none. */
	public static function role( $uid = 0 ) {
		$uid = $uid ? (int) $uid : get_current_user_id();
		if ( self::is_manager( $uid ) ) {
			return 'manager';
		}
		return in_array( $uid, self::agent_ids(), true ) ? 'agent' : 'none';
	}

	/**
	 * محدوده‌ی مالکیت برای کوئری‌ها: مدیر → 0 (بدون محدودیت)، کارشناس → آیدی خودش.
	 * برای اعمال در فیلترِ owner استفاده می‌شود.
	 */
	public static function scope_owner( $uid = 0 ) {
		$uid = $uid ? (int) $uid : get_current_user_id();
		return self::is_manager( $uid ) ? 0 : $uid;
	}

	/** کاربرانی که می‌توان سرنخ را به آن‌ها تخصیص داد ([id => display_name]). */
	public static function assignable_users() {
		$ids = self::allowed_user_ids();
		// مدیران سایت را هم اضافه کن.
		foreach ( get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) ) as $a ) {
			$ids[] = (int) $a;
		}
		$ids = array_values( array_unique( array_filter( $ids ) ) );
		$out = array();
		foreach ( $ids as $id ) {
			$u = get_userdata( $id );
			if ( $u ) {
				$out[ $id ] = $u->display_name;
			}
		}
		return $out;
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
		// مهاجرت از نسخه‌ی قبلی: access_users → agents.
		if ( ! empty( $cur['access_users'] ) && empty( $cur['managers'] ) && empty( $cur['agents'] ) ) {
			$cur['agents'] = array_map( 'intval', (array) $cur['access_users'] );
		}
		unset( $cur['access_users'] );

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
