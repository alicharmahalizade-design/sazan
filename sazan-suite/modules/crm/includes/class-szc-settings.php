<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** تنظیمات، دسترسی، و تعریف مراحل/اولویت‌های CRM. */
class SZC_Settings {

	const OPTION = 'szc_settings';
	const CAP    = 'szc_access';

	public static function init() {
		add_filter( 'user_has_cap', array( __CLASS__, 'grant_cap' ), 10, 3 );
	}

	/** دسترسی مجازی szc_access را به مدیرانِ فروش (کاربرِ وردپرس) می‌دهد. */
	public static function grant_cap( $allcaps, $caps, $args ) {
		if ( ! in_array( self::CAP, (array) $caps, true ) ) {
			return $allcaps;
		}
		$uid = isset( $args[1] ) ? (int) $args[1] : 0;
		if ( ! empty( $allcaps['manage_options'] ) || ( $uid && in_array( $uid, self::manager_ids(), true ) ) ) {
			$allcaps[ self::CAP ] = true;
		}
		return $allcaps;
	}

	public static function can_access() {
		return SZC_Auth::can_access();
	}

	public static function defaults() {
		return array(
			'managers'       => array(), // نقش مدیر: همه‌ی سرنخ‌ها را می‌بیند
			'agents'         => array(), // نقش کارشناس: فقط سرنخ‌های خودش
			'pass_login'     => 1,       // ورود کارشناسان با موبایل و کد یک‌بارمصرف یا رمز ثابت
			'otp_expiry'     => 120,     // اعتبار کد ورود به ثانیه
			'otp_resend'     => 60,      // فاصله مجاز ارسال مجدد
			'otp_max_attempts' => 5,     // حداکثر تلاش برای هر کد
			'otp_pattern_code' => '',    // Template ID / pattern code اختیاری برای OTP
			'otp_pattern_param' => 'code',
			'shared_pool'    => 1,       // استخر مشترک: همه‌ی کارشناسان همه‌ی مخاطبین را می‌بینند
			'agent_permissions' => array(
				'edit_contact'   => 1,
				'delete_contact' => 0,
				'notes'          => 1,
				'calls'          => 1,
				'followups'      => 1,
				'sms'            => 1,
				'blacklist'      => 0,
				'manage_groups'  => 0,
				'move_groups'    => 1,
				'merge'          => 0,
				'sequences'      => 0,
				'bulk_sms'       => 1,
				'export'         => 0,
			),
			'max_per_run'    => 80,      // سقف ارسال در هر اجرای صف (هر ۵ دقیقه)
			'max_per_day'    => 0,       // سقف ارسال روزانه (۰ = نامحدود)
			'sms_cost_per_part' => 0,
			'sms_enabled'    => 0,
			'sms_provider'   => 'smsir', // smsir | ippanel
			'sms_base'       => 'https://rest.ippanel.com/v1',
			'sms_apikey'     => '',
			'sms_originator' => '',
			'sms_mode'       => 'mixed', // پترن خدماتی + متن آزاد از خط اختصاصی
			'system_pattern_code' => '', // پترن اعلان‌های مدیریتی/پیگیری
			'system_pattern_param' => 'message',
			'send_from'      => 9,      // ساعت شروع مجاز ارسال
			'send_to'        => 21,     // ساعت پایان مجاز ارسال
			'auto_after_call' => 1,
			'auto_template_id' => 0,
			'auto_delay_min' => 60,
			'outcome_templates' => array(), // نگاشت بروندادِ تماس → شناسه‌ی قالب (موفق/ناموفق)
			'mini_link'      => '',
			'intro_link'     => '',
			'followup_remind'       => 1, // پیامک یادآوری پیگیری به کارشناس
			'followup_remind_email' => 0, // ایمیل یادآوری پیگیری به کارشناس
			'goal_alerts'           => 1, // اعلان عبور از ۵۰/۸۰/۱۰۰ درصد تارگت
			'goal_daily_summary'    => 1, // جمع‌بندی پایان روز برای مدیر
			'goal_summary_hour'     => 20,
			'manager_alert_mobiles' => '',
			'conversation_enabled'  => 1,
			'conversation_opening'  => 'سلام %last% عزیز، من از تیم فروش سازان تماس می‌گیرم. الان زمان مناسبی است چند دقیقه درباره نیازتان صحبت کنیم؟',
			'conversation_questions' => "چه چیزی باعث شد با ما آشنا شوید یا درخواست اطلاعات بدهید؟\nالان مهم‌ترین مسئله‌ای که می‌خواهید حل کنید چیست؟\nبرای انتخاب راه‌حل چه معیارهایی برایتان مهم‌تر است؟\nچه زمانی دوست دارید به نتیجه برسید؟\nبه‌جز شما چه کسی در تصمیم‌گیری نقش دارد؟",
			'conversation_value_points' => "پیشنهاد را به نیاز واقعی مخاطب وصل کنید، نه به فهرست امکانات.\nبرای هر ادعا یک مثال، نتیجه یا شاهد کوتاه ارائه کنید.\nقبل از اعلام قیمت، مطمئن شوید مسئله و ارزش راه‌حل برای مخاطب روشن است.",
			'conversation_closings' => "اگر موافقید، قدم بعدی را همین حالا با زمان مشخص ثبت کنیم.\nاز صحبت امروز چه سؤالی هنوز بی‌پاسخ مانده است؟\nبرای تصمیم‌گیری، ارسال چه اطلاعاتی بیشتر به شما کمک می‌کند؟",
			'conversation_guardrails' => "حرف مخاطب را قطع نکنید و بیشتر از او صحبت نکنید.\nبدون اطمینان قول قطعی، تخفیف یا زمان تحویل ندهید.\nرقیب را تخریب نکنید؛ تفاوت قابل اثبات را توضیح دهید.\nتماس را بدون تعیین اقدام بعدی و زمان آن تمام نکنید.",
			'conversation_stage_goals' => array(
				'lead'           => 'اعتماد اولیه بسازید، دلیل درخواست را کشف کنید و اجازه ادامه گفت‌وگو بگیرید.',
				'answered'       => 'نیاز، فوریت، معیار تصمیم و افراد مؤثر در خرید را دقیق‌تر روشن کنید.',
				'info_want'      => 'اطلاعات متناسب با نیاز را جمع‌بندی کنید و زمان مشخصی برای دریافت بازخورد بگیرید.',
				'info_feedback'  => 'بازخورد را بشنوید، مانع اصلی تصمیم را پیدا کنید و ابهام‌ها را برطرف کنید.',
				'promised'       => 'تعهد مخاطب را به یک زمان و اقدام روشن تبدیل و موانع حضور را بررسی کنید.',
				'attended'       => 'نتیجه جلسه را مرور کنید، اعتراض نهایی را پاسخ دهید و تصمیم بعدی را قطعی کنید.',
				'registered'     => 'از انتخاب مخاطب اطمینان‌بخشی کنید و تجربه شروع همکاری را بدون اصطکاک پیش ببرید.',
				'not_interested' => 'محترمانه علت بی‌علاقگی را اعتبارسنجی کنید؛ برای تغییر نظر فشار وارد نکنید.',
				'wrong'          => 'اطلاعات درست را کوتاه بررسی و مکالمه را محترمانه پایان دهید.',
			),
			'conversation_objections' => array(
				array( 'title' => 'قیمت بالاست', 'signals' => 'گران، قیمت، بودجه، هزینه', 'response' => 'کاملاً درک می‌کنم. برای اینکه مقایسه دقیقی داشته باشیم، بیشتر هزینه اولیه برایتان مهم است یا نتیجه و بازگشت سرمایه؟', 'question' => 'اگر راه‌حل دقیقاً مسئله اصلی شما را حل کند، چه بازه بودجه‌ای منطقی است؟' ),
				array( 'title' => 'باید فکر کنم', 'signals' => 'فکر کنم، بررسی کنم، بعداً', 'response' => 'حتماً؛ تصمیم با بررسی بهتر ارزشمندتر است. معمولاً کدام بخش نیاز به فکر بیشتری دارد: قیمت، اطمینان از نتیجه یا زمان اجرا؟', 'question' => 'چه اطلاعاتی بدهم که بررسی شما راحت‌تر و تصمیم شفاف‌تر شود؟' ),
				array( 'title' => 'الان زمان مناسبی نیست', 'signals' => 'وقت ندارم، بعداً تماس، زمان مناسب نیست', 'response' => 'متوجه‌ام و مزاحم نمی‌شوم. فقط زمان مناسب را دقیق کنیم تا تماس بعدی هم برای شما مفید باشد.', 'question' => 'چه روز و ساعتی برای یک گفت‌وگوی کوتاه مناسب‌تر است؟' ),
				array( 'title' => 'با گزینه دیگری کار می‌کنیم', 'signals' => 'رقیب، شرکت دیگر، قبلاً خریدیم', 'response' => 'عالی است؛ یعنی موضوع برایتان جدی است. قصد ندارم انتخاب فعلی‌تان را زیر سؤال ببرم؛ می‌خواهم بدانم در راه‌حل فعلی چه چیزی خوب است و چه چیزی جای بهبود دارد.', 'question' => 'اگر فقط یک مورد را در تجربه فعلی بهتر کنید، آن مورد چیست؟' ),
			),
			'stages'         => self::default_stages(),
			'priorities'     => self::default_priorities(),
			'custom_fields'  => array(), // [ { key, label } ]
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

	public static function agent_permissions() {
		$defaults = self::defaults();
		$saved    = self::get( 'agent_permissions' );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults['agent_permissions'] );
	}

	public static function permission_labels() {
		return array(
			'edit_contact'   => 'ویرایش مخاطب و مرحله فروش',
			'delete_contact' => 'انتقال مخاطب به سطل زباله',
			'notes'          => 'ثبت و حذف یادداشت',
			'calls'          => 'ثبت تماس و نتیجه تماس',
			'followups'      => 'ثبت و تکمیل پیگیری',
			'sms'            => 'ارسال پیامک تکی',
			'blacklist'      => 'مدیریت لیست سیاه',
			'manage_groups'  => 'ساخت، تغییر و حذف پوشه‌ها',
			'move_groups'    => 'انتقال مخاطب بین پوشه‌ها',
			'merge'          => 'ادغام مخاطبان',
			'sequences'      => 'عضویت در دنباله پیامکی',
			'bulk_sms'       => 'ارسال پیامک گروهی',
			'export'         => 'خروجی گرفتن از داده‌ها',
		);
	}

	/** آیا این کاربر/بازیگر مدیرِ CRM است؟ (مدیرانِ سایت همیشه بله؛ کارشناس هرگز نه) */
	public static function is_manager( $uid = 0 ) {
		if ( $uid ) {
			$uid = (int) $uid;
			return user_can( $uid, 'manage_options' ) || in_array( $uid, self::manager_ids(), true );
		}
		return SZC_Auth::is_manager();
	}

	/** نقشِ بازیگرِ جاری در CRM: manager | agent | none. */
	public static function role() {
		if ( SZC_Auth::is_manager() ) {
			return 'manager';
		}
		return SZC_Auth::is_agent() ? 'agent' : 'none';
	}

	/** محدوده‌ی مالکیت برای کوئری‌ها: مدیر → 0 (بدون محدودیت)، کارشناس → owner_idِ خودش. */
	public static function scope_owner( $uid = 0 ) {
		return SZC_Auth::scope_owner();
	}

	/** آیا ورودِ کارشناسان به پورتال فعال است؟ */
	public static function pass_login_enabled() {
		return ! empty( self::get( 'pass_login' ) );
	}

	/** کارشناسانی که می‌توان سرنخ را به آن‌ها تخصیص داد ([owner_id => name]). */
	public static function assignable_users() {
		return SZC_Agents::assignable( true );
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

		// نسخه ۱.۲۶: ارسال گروهی برای کارشناسان در مجوزهای عمومی فعال شود؛
		// استثناهای اختصاصی هر کارشناس همچنان دست‌نخورده می‌ماند.
		if ( empty( $cur['bulk_sms_v126_enabled'] ) ) {
			$permissions = wp_parse_args( (array) ( $cur['agent_permissions'] ?? array() ), self::defaults()['agent_permissions'] );
			$permissions['bulk_sms'] = 1;
			$cur['agent_permissions'] = $permissions;
			$cur['bulk_sms_v126_enabled'] = 1;
		}

		if ( empty( $cur['sms_apikey'] ) ) {
			$sazan = get_option( 'szp_eval_settings', array() );
			if ( is_array( $sazan ) && ! empty( $sazan['sms_apikey'] ) ) {
				$cur['sms_base']       = $sazan['sms_base'] ?? 'https://rest.ippanel.com/v1';
				$cur['sms_apikey']     = $sazan['sms_apikey'];
				$cur['sms_originator'] = $sazan['sms_originator'] ?? '';
				$cur['sms_mode']       = 'mixed';
			}
		}
		update_option( self::OPTION, wp_parse_args( $cur, self::defaults() ) );

		if ( class_exists( 'SZC_Templates' ) ) {
			SZC_Templates::seed_defaults();
		}
	}

	/* ==================== مراحل و اولویت‌ها ==================== */

	public static function default_stages() {
		// قیف تبدیل (به ترتیب اولویت): از تماس اولیه تا ثبت‌نام.
		return array(
			'lead'           => 'لید (تماس روزانه)',
			'answered'       => 'پاسخ داده شد',
			'info_want'      => 'تمایل به دریافت اطلاعات',
			'info_feedback'  => 'بازخورد به اطلاعات',
			'promised'       => 'قول حضور داد',
			'attended'       => 'حضوری',
			'registered'     => 'ثبت‌نام کرد',
			'not_interested' => 'بی‌علاقه',
			'wrong'          => 'شماره اشتباه',
		);
	}

	/** کلیدهای مراحلِ قیفِ تبدیل (منفی‌ها حذف می‌شوند) به ترتیب. */
	public static function funnel_stage_keys() {
		$out = array();
		foreach ( self::stages() as $k => $lbl ) {
			if ( in_array( $k, array( 'not_interested', 'wrong', 'lost', 'blacklist' ), true ) ) {
				continue;
			}
			$out[] = $k;
		}
		return $out;
	}

	/**
	 * مرحله‌ی پیش‌فرض برای مخاطبِ جدید: نخستین مرحله‌ی قیف (لیدِ «تماس روزانه»).
	 *
	 * مخاطبِ تازه باید در همین مرحله ثبت شود تا در صفِ «تماس پشت‌سرهم» دیده شود؛
	 * وگرنه مقدارِ نامعتبری مثل 'new' باعث می‌شد مخاطب در دایلر ظاهر نشود.
	 */
	public static function default_stage() {
		$keys = self::funnel_stage_keys();
		if ( $keys ) {
			return $keys[0];
		}
		$all = self::stages();
		if ( $all ) {
			return (string) array_key_first( $all );
		}
		return 'lead';
	}

	/** آیا این کلیدِ مرحله در قیفِ فعلی تعریف شده است؟ */
	public static function is_valid_stage( $key ) {
		return array_key_exists( (string) $key, self::stages() );
	}

	public static function default_priorities() {
		return array(
			'hot'  => array( 'label' => 'داغ',  'color' => '#ef4444' ),
			'warm' => array( 'label' => 'گرم',  'color' => '#f59e0b' ),
			'cold' => array( 'label' => 'سرد',  'color' => '#3b82f6' ),
		);
	}

	/** مراحل قیف فروش (قابل تنظیم از پنل): key => label. */
	public static function stages() {
		$s = self::get( 'stages' );
		if ( ! is_array( $s ) || ! $s ) {
			$s = self::default_stages();
		}
		return apply_filters( 'szc_stages', $s );
	}

	public static function stage_label( $key ) {
		$all = self::stages();
		return isset( $all[ $key ] ) ? $all[ $key ] : $key;
	}

	/** اولویت‌ها (قابل تنظیم از پنل): key => [label, color]. */
	public static function priorities() {
		$p = self::get( 'priorities' );
		if ( ! is_array( $p ) || ! $p ) {
			$p = self::default_priorities();
		}
		return $p;
	}

	/** کلیدهای اولویت به ترتیب، برای مرتب‌سازی معنایی. */
	public static function priority_keys() {
		return array_keys( self::priorities() );
	}

	/** فیلدهای سفارشی: آرایه‌ای از { key, label }. */
	public static function custom_fields() {
		$cf  = self::get( 'custom_fields' );
		$out = array();
		foreach ( (array) $cf as $f ) {
			$k = sanitize_key( $f['key'] ?? '' );
			$l = isset( $f['label'] ) ? (string) $f['label'] : '';
			if ( $k !== '' && $l !== '' ) {
				$out[] = array( 'key' => $k, 'label' => $l );
			}
		}
		return $out;
	}

	public static function save_pipeline( $stages, $priorities, $custom_fields ) {
		$cur = self::all();
		$cur['stages']        = $stages;
		$cur['priorities']    = $priorities;
		$cur['custom_fields'] = $custom_fields;
		update_option( self::OPTION, $cur );
	}

	public static function priority_meta( $key ) {
		$all = self::priorities();
		if ( isset( $all[ $key ] ) ) {
			return $all[ $key ];
		}
		$first = reset( $all );
		return $first ?: array( 'label' => $key, 'color' => '#888888' );
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
