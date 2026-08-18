<?php
/**
 * شِمای کامل تنظیمات سوئیت سازان.
 *
 * همه‌ی تنظیماتِ چهار ماژول اینجا به‌صورت «داده» توصیف شده‌اند: دسته → بخش →
 * فیلد. کنترل پنل، بازرسِ سلامت و ابزار خروجی/ورودی همگی از همین یک منبع
 * تغذیه می‌شوند؛ بنابراین افزودن یک تنظیم جدید فقط یک ورودی در این فایل است.
 *
 * @package Sazan\Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sazan_Suite_Schema {

	/** @var array|null کش شِمای ساخته‌شده. */
	private static $cache = null;

	/* =====================================================================
	 * فهرست‌های پویا
	 * =================================================================== */

	/** گزینه‌های ارائه‌دهنده‌ی پیامک. */
	public static function sms_providers() {
		return array(
			'smsir'   => 'SMS.ir',
			'ippanel' => 'فراز اس‌ام‌اس / آی‌پی‌پنل',
		);
	}

	/** حالت‌های ارسال پیامک پنل. */
	public static function sms_modes_panel() {
		return array(
			'pattern' => 'پترن (خط خدماتی)',
			'text'    => 'متن آزاد (خط اختصاصی)',
		);
	}

	/** حالت‌های ارسال پیامک CRM. */
	public static function sms_modes_crm() {
		return array(
			'pattern' => 'فقط پترن (خط خدماتی)',
			'text'    => 'فقط متن آزاد (خط اختصاصی)',
			'mixed'   => 'ترکیبی — پترن برای خدماتی، متن آزاد برای تبلیغاتی',
		);
	}

	/** روزهای هفته برای انتخاب روز ثبت تارگت/نتیجه. */
	public static function weekdays() {
		return array(
			1 => 'دوشنبه',
			2 => 'سه‌شنبه',
			3 => 'چهارشنبه',
			4 => 'پنج‌شنبه',
			5 => 'جمعه',
			6 => 'شنبه',
			7 => 'یکشنبه',
		);
	}

	/** ساعت‌های شبانه‌روز (۰ تا ۲۳). */
	public static function hours() {
		$out = array();
		for ( $i = 0; $i <= 23; $i++ ) {
			$out[ $i ] = sprintf( '%02d:00', $i );
		}
		return $out;
	}

	/** قالب‌های پیامک CRM (برای اتوماسیون پس از تماس). */
	public static function crm_templates() {
		$out = array( 0 => '— بدون قالب —' );

		if ( ! class_exists( 'SZC_Templates' ) || ! method_exists( 'SZC_Templates', 'all' ) ) {
			return $out;
		}

		foreach ( (array) SZC_Templates::all() as $tpl ) {
			$id = isset( $tpl->id ) ? (int) $tpl->id : (int) ( $tpl['id'] ?? 0 );
			if ( ! $id ) {
				continue;
			}
			$title      = isset( $tpl->title ) ? $tpl->title : ( $tpl['title'] ?? ( 'قالب #' . $id ) );
			$out[ $id ] = $title;
		}

		return $out;
	}

	/** مراحل قیف فروش CRM (کلید ⇒ برچسب). */
	public static function crm_stages() {
		if ( class_exists( 'SZC_Settings' ) ) {
			return (array) SZC_Settings::stages();
		}
		return array();
	}

	/** نتیجه‌های تماس CRM. */
	public static function crm_outcomes() {
		if ( class_exists( 'SZC_Settings' ) ) {
			return (array) SZC_Settings::call_outcomes();
		}
		return array();
	}

	/** مجوزهای کارشناس CRM. */
	public static function crm_permission_labels() {
		if ( class_exists( 'SZC_Settings' ) ) {
			return (array) SZC_Settings::permission_labels();
		}
		return array();
	}

	/** فهرست‌های واقعی وردپرس برای هدر صفحات دوره. */
	public static function navigation_menus() {
		$options = array( 0 => '— منوی داخلی پیش‌فرض دوره —' );
		$menus   = function_exists( 'wp_get_nav_menus' ) ? wp_get_nav_menus() : array();

		foreach ( (array) $menus as $menu ) {
			if ( ! isset( $menu->term_id, $menu->name ) ) {
				continue;
			}
			$options[ (int) $menu->term_id ] = (string) $menu->name;
		}

		return $options;
	}

	/* =====================================================================
	 * شِما
	 * =================================================================== */

	/**
	 * شِمای کامل: دسته‌ها.
	 *
	 * @return array<string,array>
	 */
	public static function categories() {

		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$categories = array(
			'general'     => self::category_general(),
			'appearance'  => self::category_appearance(),
			'panel_texts' => self::category_panel_texts(),
			'evaluation'  => self::category_evaluation(),
			'sms'         => self::category_sms(),
			'crm'         => self::category_crm(),
			'ai'          => self::category_ai(),
			'product'     => self::category_product(),
			'engines'     => self::category_engines(),
		);

		/**
		 * امکان افزودن دسته یا بخش تازه به کنترل پنل.
		 *
		 * @param array $categories
		 */
		$categories = (array) apply_filters( 'sazan_suite_settings_schema', $categories );

		self::$cache = $categories;

		return $categories;
	}

	/** خالی‌کردن کش شِما. */
	public static function flush() {
		self::$cache = null;
	}

	/** یک دسته. */
	public static function category( $id ) {
		$all = self::categories();
		return isset( $all[ $id ] ) ? $all[ $id ] : null;
	}

	/**
	 * فهرست تخت همه‌ی بخش‌ها: [ section_id => section + category_id ].
	 *
	 * @return array<string,array>
	 */
	public static function sections() {
		$out = array();

		foreach ( self::categories() as $cat_id => $cat ) {
			foreach ( $cat['sections'] as $sec_id => $section ) {
				$section['category']    = $cat_id;
				$section['category_label'] = $cat['label'];
				$out[ $sec_id ]         = $section;
			}
		}

		return $out;
	}

	/** یک بخش. */
	public static function section( $id ) {
		$all = self::sections();
		return isset( $all[ $id ] ) ? $all[ $id ] : null;
	}

	/* =====================================================================
	 * دسته: عمومی
	 * =================================================================== */

	private static function category_general() {
		return array(
			'label'       => 'عمومی و برند',
			'icon'        => 'dashicons-admin-home',
			'description' => 'نام و پیام‌های پایه‌ی سامانه و رفتارهای عمومی سایت.',
			'sections'    => array(

				'general_brand' => array(
					'label'       => 'هویت و نام‌گذاری',
					'description' => 'نامی که در رابط کاربری پنل به کاربر نشان داده می‌شود.',
					'store'       => 'panel_ui',
					'module'      => 'panel',
					'fields'      => array(
						'brand_name' => array(
							'type'  => 'text',
							'label' => 'نام سامانه در رابط کاربری',
							'hint'  => 'در سرتیتر پنل کاربری و پیام‌های سیستمی استفاده می‌شود.',
						),
					),
				),

				'general_messages' => array(
					'label'       => 'پیام‌های سیستمی',
					'description' => 'متنی که هنگام نبود دسترسی یا نیاز به ورود نمایش داده می‌شود.',
					'store'       => 'panel_ui',
					'module'      => 'panel',
					'fields'      => array(
						'login_message' => array(
							'type'  => 'textarea',
							'label' => 'پیام نیاز به ورود',
							'rows'  => 2,
						),
						'forbidden_message' => array(
							'type'  => 'textarea',
							'label' => 'پیام نداشتن دسترسی',
							'rows'  => 2,
						),
					),
				),

				'general_frontend' => array(
					'label'       => 'ابزارهای عمومی سایت',
					'description' => 'امکاناتی که روی کل سایت اثر می‌گذارند.',
					'store'       => 'core',
					'module'      => 'core',
					'fields'      => array(
						'font_resizer' => array(
							'type'  => 'switch',
							'label' => 'دکمه شناور تغییر سایز فونت',
							'hint'  => 'به بازدیدکننده اجازه می‌دهد فونت کل سایت را درشت‌تر یا ریزتر کند. انتخاب او در مرورگر خودش ذخیره می‌شود.',
						),
					),
				),
			),
		);
	}

	/* =====================================================================
	 * دسته: ظاهر
	 * =================================================================== */

	private static function category_appearance() {
		return array(
			'label'       => 'ظاهر و رابط کاربری',
			'icon'        => 'dashicons-art',
			'description' => 'رنگ‌ها، تایپوگرافی، ابعاد و CSS سفارشی پنل کاربری.',
			'sections'    => array(

				'appearance_colors' => array(
					'label'       => 'رنگ‌ها',
					'description' => 'پالت رنگی پنل کاربری، داشبورد رشد و فرم‌های ارزیابی.',
					'store'       => 'panel_ui',
					'module'      => 'panel',
					'columns'     => 3,
					'fields'      => array(
						'color_primary'    => array( 'type' => 'color', 'label' => 'رنگ اصلی' ),
						'color_secondary'  => array( 'type' => 'color', 'label' => 'رنگ مکمل' ),
						'color_success'    => array( 'type' => 'color', 'label' => 'رنگ موفقیت' ),
						'color_danger'     => array( 'type' => 'color', 'label' => 'رنگ خطا' ),
						'color_background' => array( 'type' => 'color', 'label' => 'پس‌زمینه' ),
						'color_surface'    => array( 'type' => 'color', 'label' => 'سطح کارت‌ها' ),
						'color_text'       => array( 'type' => 'color', 'label' => 'متن اصلی' ),
						'color_muted'      => array( 'type' => 'color', 'label' => 'متن کم‌رنگ' ),
						'color_border'     => array( 'type' => 'color', 'label' => 'خطوط و حاشیه' ),
					),
				),

				'appearance_layout' => array(
					'label'       => 'تایپوگرافی و ابعاد',
					'description' => 'اندازه‌ی فونت پایه، عرض محتوا و گردی گوشه‌ها.',
					'store'       => 'panel_ui',
					'module'      => 'panel',
					'columns'     => 2,
					'fields'      => array(
						'font_family' => array(
							'type'  => 'text',
							'label' => 'خانواده فونت',
							'ltr'   => true,
							'hint'  => 'خالی یا inherit یعنی همان فونت قالب سایت.',
						),
						'base_font_size' => array(
							'type'  => 'number',
							'label' => 'اندازه فونت پایه',
							'min'   => 13,
							'max'   => 22,
							'unit'  => 'px',
						),
						'content_width' => array(
							'type'  => 'number',
							'label' => 'حداکثر عرض محتوا',
							'min'   => 680,
							'max'   => 1600,
							'unit'  => 'px',
						),
						'card_radius' => array(
							'type'  => 'number',
							'label' => 'گردی گوشه کارت‌ها',
							'min'   => 0,
							'max'   => 40,
							'unit'  => 'px',
						),
						'button_radius' => array(
							'type'  => 'number',
							'label' => 'گردی گوشه دکمه‌ها',
							'min'   => 0,
							'max'   => 30,
							'unit'  => 'px',
						),
						'shadow_strength' => array(
							'type'  => 'number',
							'label' => 'شدت سایه',
							'min'   => 0,
							'max'   => 30,
						),
					),
				),

				'appearance_visibility' => array(
					'label'       => 'نمایش بخش‌های راهنما',
					'description' => 'بخش‌های توضیحی داشبورد رشد را می‌توانید پنهان کنید.',
					'store'       => 'panel_ui',
					'module'      => 'panel',
					'columns'     => 3,
					'fields'      => array(
						'show_growth_explanation' => array( 'type' => 'switch', 'label' => 'نمایش «نتیجه چگونه مشخص می‌شود؟»' ),
						'show_growth_summary'     => array( 'type' => 'switch', 'label' => 'نمایش کارت خلاصه' ),
						'show_history_help'       => array( 'type' => 'switch', 'label' => 'نمایش راهنمای سوابق' ),
					),
				),

				'appearance_css' => array(
					'label'       => 'CSS سفارشی',
					'description' => 'استایل دلخواه که پس از استایل‌های افزونه بارگذاری می‌شود.',
					'store'       => 'panel_ui',
					'module'      => 'panel',
					'fields'      => array(
						'custom_css' => array(
							'type'  => 'css',
							'label' => 'کد CSS',
							'rows'  => 12,
							'hint'  => 'بدون تگ &lt;style&gt; بنویسید. برای محدودکردن به پنل از پیشوند <code>.szp</code> استفاده کنید.',
						),
					),
				),
			),
		);
	}

	/* =====================================================================
	 * دسته: متن‌های پنل
	 * =================================================================== */

	private static function category_panel_texts() {

		$sections = array(

			'texts_lists' => array(
				'label'       => 'فهرست دوره‌ها و گفتگو',
				'description' => 'عنوان‌ها و پیام‌های خالی‌بودن فهرست‌ها.',
				'fields'      => array(
					'courses_title' => 'عنوان فهرست دوره‌ها',
					'courses_empty' => 'پیام خالی‌بودن دوره‌ها',
					'chat_title'    => 'عنوان اتاق گفتگو',
					'chat_intro'    => 'راهنمای اتاق گفتگو',
					'chat_empty'    => 'پیام خالی‌بودن گفتگو',
					'back_courses'  => 'متن بازگشت به دوره‌ها',
					'back_course'   => 'متن بازگشت به دوره',
					'show_more'     => 'متن دکمه مشاهده بیشتر',
				),
			),

			'texts_course' => array(
				'label'       => 'صفحه دوره',
				'description' => 'عنوان بخش‌های صفحه‌ی هر دوره.',
				'fields'      => array(
					'course_identity_title' => 'عنوان شناسنامه دوره',
					'course_outline_title'  => 'عنوان سرفصل‌ها',
					'course_goals_title'    => 'عنوان اهداف نهایی',
					'course_schedule_title' => 'عنوان زمان‌بندی جلسات',
					'course_schedule_hint'  => 'راهنمای زمان‌بندی',
					'course_next_session'   => 'برچسب جلسه بعدی',
					'course_past_sessions'  => 'برچسب جلسات برگزارشده',
					'course_no_sessions'    => 'پیام نبود جلسه',
					'course_announcements'  => 'عنوان تابلو اعلانات',
					'course_files'          => 'عنوان فایل‌ها و منابع',
					'course_workbench'      => 'عنوان میز کار تمرین‌ها',
					'course_group'          => 'عنوان پنل گروه',
					'course_survey'         => 'عنوان نظرسنجی‌های دوره',
					'course_table_session'  => 'ستون جدول: جلسه',
					'course_table_deadline' => 'ستون جدول: ددلاین',
					'course_table_status'   => 'ستون جدول: وضعیت',
					'course_status_sent'    => 'وضعیت: ارسال شد',
					'course_status_pending' => 'وضعیت: ارسال نشده',
				),
			),

			'texts_session' => array(
				'label'       => 'صفحه جلسه',
				'description' => 'عنوان‌ها، دکمه‌ها و راهنماهای صفحه‌ی هر جلسه.',
				'fields'      => array(
					'session_identity_title'     => 'عنوان شناسنامه جلسه',
					'session_goals_title'        => 'عنوان اهداف کلیدی',
					'session_pack_title'         => 'عنوان فایل‌ها و محتوا',
					'session_assignment_title'   => 'عنوان تکلیف جلسه',
					'session_checklist_title'    => 'عنوان چک‌لیست',
					'session_survey_title'       => 'عنوان نظرسنجی بازخورد',
					'session_play_label'         => 'برچسب پخش',
					'session_speed_label'        => 'برچسب سرعت پخش',
					'session_download_label'     => 'برچسب دانلود',
					'session_pdf_label'          => 'عنوان اسلایدهای PDF',
					'session_quiz_entry'         => 'دکمه ورود به تمرین/آزمون',
					'session_sales_soon'         => 'پیام فرم در دست ساخت',
					'submit_answer'              => 'دکمه ارسال پاسخ',
					'update_answer'              => 'دکمه ویرایش پاسخ',
					'submit_feedback'            => 'دکمه ثبت نظر',
					'session_answer_saved'       => 'پیام ذخیره‌شدن پاسخ',
					'session_last_edit'          => 'برچسب آخرین ویرایش',
					'session_sent_file'          => 'عنوان فایل ارسالی',
					'session_answer_placeholder' => 'متن راهنمای کادر پاسخ',
					'session_attachment_label'   => 'عنوان پیوست فایل',
					'session_checklist_hint'     => 'راهنمای چک‌لیست',
					'session_survey_answered'    => 'پیام پاسخ قبلی نظرسنجی',
				),
			),

			'texts_growth' => array(
				'label'       => 'داشبورد رشد هفتگی',
				'description' => 'عنوان‌ها و راهنماهای مسیر تارگت و نتیجه.',
				'fields'      => array(
					'growth_title'                => 'عنوان اصلی',
					'growth_subtitle'             => 'زیرعنوان',
					'growth_today_title'          => 'عنوان قدم بعدی',
					'growth_explain_title'        => 'عنوان بخش توضیح نتیجه',
					'growth_explain_text'         => 'متن توضیح نتیجه',
					'growth_target_title'         => 'عنوان مرحله تارگت',
					'growth_result_title'         => 'عنوان مرحله نتیجه',
					'growth_target_button'        => 'دکمه ثبت تارگت',
					'growth_result_button'        => 'دکمه ثبت نتیجه',
					'growth_history_title'        => 'عنوان سوابق',
					'growth_history_help'         => 'راهنمای سوابق',
					'growth_pending_label'        => 'برچسب نتیجه ثبت‌نشده',
					'growth_next_title'           => 'عنوان قدم بعدی',
					'growth_next_text'            => 'متن قدم بعدی',
					'growth_next_button'          => 'دکمه قدم بعدی',
					'growth_schedule_text'        => 'خلاصه برنامه هفتگی',
					'growth_schedule_monday'      => 'متن برنامه دوشنبه',
					'growth_schedule_tuesday'     => 'متن برنامه سه‌شنبه',
					'growth_result_ready_label'   => 'برچسب نتیجه آماده ثبت',
					'growth_result_ready_button'  => 'دکمه نتیجه آماده ثبت',
					'growth_result_waiting_label' => 'برچسب انتظار ثبت نتیجه',
					'growth_countdown_days'       => 'واحد روز',
					'growth_countdown_hours'      => 'واحد ساعت',
					'growth_countdown_minutes'    => 'واحد دقیقه',
				),
			),

			'texts_dashboard' => array(
				'label'       => 'داشبورد وضعیت و ویزارد',
				'description' => 'برچسب وضعیت‌ها، دکمه‌ها و منوی سریع موبایل.',
				'fields'      => array(
					'dashboard_kicker'                 => 'برچسب بالای داشبورد',
					'dashboard_title'                  => 'عنوان داشبورد',
					'dashboard_current_session'        => 'برچسب جلسه جاری',
					'dashboard_status_label'           => 'برچسب وضعیت فعلی',
					'dashboard_profile_status'         => 'وضعیت: اطلاعات پایه ناقص',
					'dashboard_profile_detail'         => 'زیرعنوان اطلاعات پایه',
					'dashboard_profile_button'         => 'دکمه تکمیل اطلاعات پایه',
					'dashboard_missing_result_status'  => 'وضعیت: نتیجه ثبت‌نشده',
					'dashboard_missing_result_button'  => 'دکمه نتیجه عقب‌افتاده',
					'dashboard_result_status'          => 'وضعیت: زمان ثبت نتیجه',
					'dashboard_result_button'          => 'دکمه ثبت نتیجه',
					'dashboard_target_status'          => 'وضعیت: زمان ثبت تارگت',
					'dashboard_target_button'          => 'دکمه ثبت تارگت',
					'dashboard_overdue_status'         => 'وضعیت: سوابق ناقص',
					'dashboard_overdue_button'         => 'دکمه سوابق ناقص',
					'dashboard_overdue_label'          => 'برچسب تعداد نقص',
					'dashboard_overdue_count_unit'     => 'واحد تعداد نقص',
					'dashboard_wait_result_status'     => 'وضعیت: انتظار ثبت نتیجه',
					'dashboard_wait_result_button'     => 'دکمه انتظار ثبت نتیجه',
					'dashboard_result_countdown'       => 'عنوان شمارش معکوس نتیجه',
					'dashboard_wait_target_status'     => 'وضعیت: انتظار ثبت تارگت',
					'dashboard_wait_target_button'     => 'دکمه انتظار ثبت تارگت',
					'dashboard_target_countdown'       => 'عنوان شمارش معکوس تارگت',
					'dashboard_current_actions_label'  => 'عنوان ثبت‌های این هفته',
					'dashboard_current_result_button'  => 'دکمه ثبت نتیجه این هفته',
					'dashboard_current_target_button'  => 'دکمه ثبت تارگت این هفته',
					'mobile_quick_title'               => 'عنوان دید سریع (موبایل)',
					'mobile_quick_summary'             => 'گزینه خلاصه من',
					'mobile_quick_history'             => 'گزینه سوابق',
					'mobile_quick_team'                => 'گزینه تیم من',
					'mobile_quick_coaching'            => 'گزینه کوچینگ',
					'mobile_quick_members'             => 'گزینه اعضای تیم',
					'mobile_quick_members_hint'        => 'توضیح گزینه اعضای تیم',
					'wizard_step_label'                => 'برچسب مرحله ویزارد',
					'wizard_close_label'               => 'عنوان دکمه بستن',
					'wizard_previous_button'           => 'دکمه مرحله قبل',
					'wizard_next_button'               => 'دکمه مرحله بعد',
				),
			),

			'texts_profile' => array(
				'label'       => 'اطلاعات پایه کاربر',
				'description' => 'عنوان‌های فرم اطلاعات پایه که یک‌بار تکمیل می‌شود.',
				'fields'      => array(
					'profile_title'            => 'عنوان بخش',
					'profile_incomplete_hint'  => 'راهنمای تکمیل اولیه',
					'profile_complete_label'   => 'برچسب تکمیل‌شده',
					'profile_required_label'   => 'برچسب نیازمند تکمیل',
					'profile_full_name_label'  => 'عنوان نام و نام خانوادگی',
					'profile_business_label'   => 'عنوان نام کسب‌وکار',
					'profile_position_label'   => 'عنوان سمت',
					'profile_position_hint'    => 'نمونه سمت',
					'profile_industry_label'   => 'عنوان حوزه فعالیت',
					'profile_industry_hint'    => 'نمونه حوزه فعالیت',
					'profile_save_button'      => 'متن دکمه ذخیره',
					'profile_required_callout' => 'پیام الزام تکمیل اطلاعات پایه',
				),
			),

			'texts_forms' => array(
				'label'       => 'فرم تارگت و فرم نتیجه',
				'description' => 'پیام‌های ذخیره، قفل‌بودن و راهنمای اقدام‌ها.',
				'fields'      => array(
					'target_saved_text'         => 'پیام تارگت ثبت‌شده',
					'target_action_help'        => 'راهنمای نوشتن اقدام',
					'target_action_label'       => 'عنوان هر اقدام',
					'target_action_placeholder' => 'نمونه توضیح اقدام',
					'target_action_remove'      => 'دکمه حذف اقدام',
					'target_action_add'         => 'دکمه افزودن اقدام',
					'target_locked_title'       => 'عنوان قفل‌بودن تارگت',
					'target_locked_text'        => 'توضیح قفل‌بودن تارگت',
					'result_saved_text'         => 'پیام نتیجه ثبت‌شده',
					'result_intro_title'        => 'عنوان راهنمای فرم نتیجه',
					'result_intro_text'         => 'متن راهنمای فرم نتیجه',
					'result_locked_title'       => 'عنوان قفل‌بودن نتیجه',
					'result_locked_text'        => 'توضیح قفل‌بودن نتیجه',
					'result_preserved_text'     => 'پیام حفظ نتیجه قبلی',
					'select_placeholder'        => 'متن پیش‌فرض انتخاب‌گر',
				),
			),

			'texts_history' => array(
				'label'       => 'خلاصه و سوابق',
				'description' => 'برچسب‌های کارت خلاصه و جدول هفته‌های گذشته.',
				'fields'      => array(
					'summary_target_label'          => 'برچسب تارگت ثبت‌شده',
					'summary_result_label'          => 'برچسب نتیجه ثبت‌شده',
					'summary_average_label'         => 'برچسب میانگین تحقق',
					'history_empty_title'           => 'عنوان نبود سابقه',
					'history_empty_text'            => 'توضیح نبود سابقه',
					'history_target_label'          => 'برچسب هدف مالی',
					'history_actual_label'          => 'برچسب فروش واقعی',
					'history_not_registered'        => 'برچسب ثبت‌نشده',
					'completed_history_title'       => 'عنوان هفته‌های تکمیل‌شده',
					'completed_history_empty_title' => 'عنوان نبود هفته کامل',
					'completed_history_empty_text'  => 'توضیح نبود هفته کامل',
				),
			),

			'texts_team' => array(
				'label'       => 'تیم و کوچینگ',
				'description' => 'عنوان‌های نمای مدیر، نمای کوچ و بخش کوچینگ.',
				'fields'      => array(
					'team_coach_kicker'   => 'برچسب نمای کوچ',
					'team_manager_kicker' => 'برچسب نمای مدیر',
					'team_coach_title'    => 'عنوان تیم‌های سازمانی',
					'team_manager_title'  => 'عنوان زیرمجموعه‌های من',
					'team_empty_title'    => 'عنوان نبود عضو',
					'team_empty_text'     => 'توضیح نبود عضو',
					'coaching_kicker'     => 'برچسب کوچینگ',
					'coaching_title'      => 'عنوان کوچینگ',
					'coaching_admin_help' => 'راهنمای مدیریت کوچینگ',
				),
			),

			'texts_status' => array(
				'label'       => 'برچسب وضعیت نتیجه',
				'description' => 'عبارتی که پس از مقایسه‌ی نتیجه با تارگت نمایش داده می‌شود.',
				'columns'     => 2,
				'fields'      => array(
					'status_beyond'  => 'بیشتر از هدف',
					'status_success' => 'تحقق کامل هدف',
					'status_improve' => 'نزدیک به هدف',
					'status_ontrack' => 'کمتر از هدف',
				),
			),

			'texts_q_target' => array(
				'label'       => 'پرسش‌های فرم تارگت',
				'description' => 'متن هشت پرسش فرم ثبت تارگت هفتگی.',
				'fields'      => array(
					'target_q1' => 'پرسش ۱',
					'target_q2' => 'پرسش ۲',
					'target_q3' => 'پرسش ۳',
					'target_q4' => 'پرسش ۴',
					'target_q5' => 'پرسش ۵',
					'target_q6' => 'پرسش ۶',
					'target_q7' => 'پرسش ۷',
					'target_q8' => 'پرسش ۸',
				),
			),

			'texts_q_result' => array(
				'label'       => 'پرسش‌های فرم نتیجه',
				'description' => 'متن هفت پرسش فرم ثبت نتیجه‌ی هفتگی.',
				'fields'      => array(
					'result_q1' => 'پرسش ۱',
					'result_q2' => 'پرسش ۲',
					'result_q3' => 'پرسش ۳',
					'result_q4' => 'پرسش ۴',
					'result_q5' => 'پرسش ۵',
					'result_q6' => 'پرسش ۶',
					'result_q7' => 'پرسش ۷',
				),
			),
		);

		// همه‌ی این بخش‌ها متن ساده‌اند و در یک مخزن ذخیره می‌شوند.
		foreach ( $sections as $id => $section ) {
			$fields = array();
			foreach ( $section['fields'] as $key => $label ) {
				$fields[ $key ] = array(
					'type'  => self::text_field_type( $key ),
					'label' => $label,
				);
			}
			$sections[ $id ]['fields']  = $fields;
			$sections[ $id ]['store']   = 'panel_ui';
			$sections[ $id ]['module']  = 'panel';
			$sections[ $id ]['columns'] = isset( $section['columns'] ) ? $section['columns'] : 2;
		}

		return array(
			'label'       => 'متن‌های پنل کاربری',
			'icon'        => 'dashicons-edit-page',
			'description' => 'هر عبارتی که کاربر در پنل می‌بیند از اینجا قابل تغییر است.',
			'sections'    => $sections,
		);
	}

	/** متن‌های بلند با کادر چندخطی نمایش داده می‌شوند. */
	private static function text_field_type( $key ) {

		$multiline = array(
			'growth_explain_text', 'growth_next_text', 'growth_schedule_text',
			'growth_schedule_monday', 'growth_schedule_tuesday', 'growth_history_help',
			'target_action_help', 'target_locked_text', 'result_intro_text',
			'result_locked_text', 'result_preserved_text', 'profile_required_callout',
			'profile_incomplete_hint', 'coaching_admin_help', 'team_empty_text',
			'history_empty_text', 'completed_history_empty_text', 'chat_intro',
			'course_schedule_hint', 'session_checklist_hint', 'session_survey_answered',
			'session_sales_soon', 'mobile_quick_members_hint', 'courses_empty',
			'chat_empty', 'course_no_sessions', 'target_q2', 'target_q4', 'target_q5',
			'target_q6', 'result_q5', 'result_q6', 'result_q7',
		);

		return in_array( $key, $multiline, true ) ? 'textarea' : 'text';
	}

	/* =====================================================================
	 * دسته: ارزیابی
	 * =================================================================== */

	private static function category_evaluation() {
		return array(
			'label'       => 'ارزیابی و تارگت هفتگی',
			'icon'        => 'dashicons-chart-line',
			'description' => 'تقویم جلسات، روزهای مجاز ثبت، واحد پول، آستانه‌ی موفقیت و دسترسی‌ها.',
			'sections'    => array(

				'eval_calendar' => array(
					'label'       => 'تقویم جلسات',
					'description' => 'شماره‌ی جلسه‌ی جاری و تاریخ آن، مبنای محاسبه‌ی همه‌ی هفته‌هاست.',
					'store'       => 'eval',
					'module'      => 'panel',
					'columns'     => 2,
					'fields'      => array(
						'anchor_session' => array(
							'type'  => 'number',
							'label' => 'الان جلسه چندم هستیم؟',
							'min'   => 0,
							'max'   => 500,
							'hint'  => 'شماره‌ی جلسه‌ای که هم‌اکنون در آن هستیم. صفر یعنی محاسبه‌ی خودکار.',
						),
						'anchor_date' => array(
							'type'  => 'date',
							'label' => 'تاریخ همان جلسه',
							'hint'  => 'یک سه‌شنبه انتخاب کنید (میلادی، Y-m-d).',
						),
						'start_week' => array(
							'type'  => 'number',
							'label' => 'شماره‌ی نخستین هفته',
							'min'   => 1,
							'max'   => 100,
						),
						'session1_date' => array(
							'type'     => 'date',
							'label'    => 'تاریخ جلسه ۱ (قدیمی)',
							'hint'     => 'فقط برای سازگاری با نصب‌های قدیمی نگه داشته شده؛ در صورت تنظیم «تاریخ همان جلسه» نادیده گرفته می‌شود.',
							'advanced' => true,
						),
					),
				),

				'eval_windows' => array(
					'label'       => 'روزهای مجاز ثبت',
					'description' => 'در چه روزی از هفته فرم تارگت و فرم نتیجه باز باشد.',
					'store'       => 'eval',
					'module'      => 'panel',
					'columns'     => 3,
					'fields'      => array(
						'day_target' => array(
							'type'    => 'select',
							'label'   => 'روز ثبت تارگت',
							'options' => array( __CLASS__, 'weekdays' ),
						),
						'day_result' => array(
							'type'    => 'select',
							'label'   => 'روز ثبت نتیجه',
							'options' => array( __CLASS__, 'weekdays' ),
						),
						'result_lock_enabled' => array(
							'type'  => 'switch',
							'label' => 'قفل‌کردن ثبت نتیجه خارج از روز مجاز',
							'hint'  => 'خاموش یعنی کاربر هر زمان می‌تواند نتیجه‌ی عقب‌افتاده را ثبت کند.',
						),
					),
				),

				'eval_scoring' => array(
					'label'       => 'محاسبه و واحد',
					'description' => 'واحد پول و آستانه‌ی «نزدیک به هدف».',
					'store'       => 'eval',
					'module'      => 'panel',
					'columns'     => 2,
					'fields'      => array(
						'currency' => array(
							'type'  => 'text',
							'label' => 'واحد پول',
						),
						'near' => array(
							'type'  => 'number',
							'label' => 'آستانه «نزدیک به هدف»',
							'min'   => 1,
							'max'   => 100,
							'unit'  => '٪',
							'hint'  => 'درصد تحققی که از آن به بالا، وضعیت «نزدیک به هدف» در نظر گرفته می‌شود.',
						),
					),
				),

				'eval_access' => array(
					'label'       => 'دسترسی‌های ویژه',
					'description' => 'چه کسانی نمای کوچ و تابلوی افراد را می‌بینند.',
					'store'       => 'eval',
					'module'      => 'panel',
					'fields'      => array(
						'coaching_users' => array(
							'type'  => 'textarea',
							'label' => 'کاربران با نقش کوچ',
							'rows'  => 3,
							'hint'  => 'نام کاربری، ایمیل یا شناسه‌ی عددی — هر کدام در یک خط یا جداشده با کاما.',
						),
						'board_viewers' => array(
							'type'  => 'textarea',
							'label' => 'بینندگان تابلوی افراد',
							'rows'  => 3,
							'hint'  => 'نام کاربری، ایمیل یا شناسه‌ی عددی — هر کدام در یک خط یا جداشده با کاما.',
						),
					),
				),
			),
		);
	}

	/* =====================================================================
	 * دسته: پیامک
	 * =================================================================== */

	private static function category_sms() {
		return array(
			'label'       => 'پیامک و اطلاع‌رسانی',
			'icon'        => 'dashicons-email-alt',
			'description' => 'اتصال به سامانه‌ی پیامکی و متن همه‌ی پیامک‌های خودکار سازان.',
			'sections'    => array(

				'sms_panel_connection' => array(
					'label'       => 'اتصال پیامک — پنل و ارزیابی',
					'description' => 'کلید و خط ارسال یادآورهای تارگت و نتیجه.',
					'store'       => 'eval',
					'module'      => 'panel',
					'columns'     => 2,
					'fields'      => array(
						'sms_enabled' => array(
							'type'  => 'switch',
							'label' => 'ارسال پیامک فعال باشد',
							'full'  => true,
						),
						'sms_provider' => array(
							'type'    => 'select',
							'label'   => 'ارائه‌دهنده',
							'options' => array( __CLASS__, 'sms_providers' ),
						),
						'sms_base' => array(
							'type'        => 'text',
							'label'       => 'آدرس پایه‌ی API',
							'ltr'         => true,
							'placeholder' => 'https://rest.ippanel.com/v1',
							'hint'        => 'برای SMS.ir خالی بگذارید تا آدرس پیش‌فرض استفاده شود.',
						),
						'sms_apikey' => array(
							'type'    => 'secret',
							'label'   => 'کلید API',
							'ltr'     => true,
						),
						'sms_originator' => array(
							'type'        => 'text',
							'label'       => 'شماره فرستنده',
							'ltr'         => true,
							'placeholder' => '+983000505',
						),
						'sms_mode' => array(
							'type'    => 'select',
							'label'   => 'حالت ارسال',
							'options' => array( __CLASS__, 'sms_modes_panel' ),
						),
						'sms_var' => array(
							'type'  => 'text',
							'label' => 'نام متغیر نام کاربر در پترن',
							'ltr'   => true,
							'hint'  => 'کلیدی که در پترن به‌جای نام کاربر می‌نشیند؛ معمولاً <code>name</code>.',
						),
					),
					'after' => array( __CLASS__, 'render_sms_test' ),
				),

				'sms_panel_reminders' => array(
					'label'       => 'یادآور تارگت و نتیجه',
					'description' => 'پیامکی که در روز مجاز به کاربر یادآوری می‌کند. متغیر در دسترس: <code>%name%</code>',
					'store'       => 'eval',
					'module'      => 'panel',
					'columns'     => 2,
					'fields'      => array(
						'sms_pattern_target' => array( 'type' => 'text', 'label' => 'کد پترن یادآور تارگت', 'ltr' => true ),
						'sms_pattern_result' => array( 'type' => 'text', 'label' => 'کد پترن یادآور نتیجه', 'ltr' => true ),
						'sms_text_target'    => array( 'type' => 'textarea', 'label' => 'متن یادآور تارگت', 'rows' => 3 ),
						'sms_text_result'    => array( 'type' => 'textarea', 'label' => 'متن یادآور نتیجه', 'rows' => 3 ),
					),
				),

				'sms_panel_notify' => array(
					'label'       => 'اطلاع‌رسانی به مدیر و کوچ',
					'description' => 'پیامک هنگام ثبت تارگت یا نتیجه توسط اعضای تیم. متغیرها: <code>%name%</code> <code>%session%</code> <code>%amount%</code> <code>%status%</code>',
					'store'       => 'eval',
					'module'      => 'panel',
					'columns'     => 2,
					'fields'      => array(
						'notify_enabled' => array(
							'type'  => 'switch',
							'label' => 'اطلاع‌رسانی فعال باشد',
							'full'  => true,
						),
						'sms_pattern_notify_target' => array( 'type' => 'text', 'label' => 'کد پترن اطلاع ثبت تارگت', 'ltr' => true ),
						'sms_pattern_notify_result' => array( 'type' => 'text', 'label' => 'کد پترن اطلاع ثبت نتیجه', 'ltr' => true ),
						'sms_text_notify_target'    => array( 'type' => 'textarea', 'label' => 'متن اطلاع ثبت تارگت', 'rows' => 3 ),
						'sms_text_notify_result'    => array( 'type' => 'textarea', 'label' => 'متن اطلاع ثبت نتیجه', 'rows' => 3 ),
					),
				),

				'sms_sessions' => array(
					'label'       => 'جلسات و نظرسنجی',
					'description' => 'پیامک ثبت جلسه و لینک نظرسنجی پس از جلسه. متغیرها: <code>%name%</code> <code>%title%</code> <code>%coach%</code> <code>%date%</code> <code>%time%</code> <code>%link%</code>',
					'store'       => 'eval',
					'module'      => 'panel',
					'columns'     => 2,
					'fields'      => array(
						'sms_pattern_session' => array( 'type' => 'text', 'label' => 'کد پترن اطلاع جلسه', 'ltr' => true ),
						'sms_pattern_survey'  => array( 'type' => 'text', 'label' => 'کد پترن نظرسنجی', 'ltr' => true ),
						'sms_text_session'    => array( 'type' => 'textarea', 'label' => 'متن اطلاع جلسه', 'rows' => 3 ),
						'sms_text_survey'     => array( 'type' => 'textarea', 'label' => 'متن دعوت به نظرسنجی', 'rows' => 3 ),
						'survey_url'          => array( 'type' => 'url', 'label' => 'آدرس صفحه نظرسنجی', 'ltr' => true ),
						'survey_delay'        => array(
							'type'  => 'number',
							'label' => 'تأخیر ارسال نظرسنجی',
							'min'   => 0,
							'max'   => 1440,
							'unit'  => 'دقیقه',
							'hint'  => 'چند دقیقه پس از پایان جلسه، لینک نظرسنجی ارسال شود.',
						),
					),
				),

				'sms_crm_connection' => array(
					'label'       => 'اتصال پیامک — CRM',
					'description' => 'خط و کلید ارسال پیامک تیم فروش (مستقل از پنل).',
					'store'       => 'crm',
					'module'      => 'crm',
					'columns'     => 2,
					'fields'      => array(
						'sms_enabled' => array(
							'type'  => 'switch',
							'label' => 'ارسال پیامک CRM فعال باشد',
							'full'  => true,
						),
						'sms_provider' => array(
							'type'    => 'select',
							'label'   => 'ارائه‌دهنده',
							'options' => array( __CLASS__, 'sms_providers' ),
						),
						'sms_base' => array(
							'type'        => 'text',
							'label'       => 'آدرس پایه‌ی API',
							'ltr'         => true,
							'placeholder' => 'https://rest.ippanel.com/v1',
						),
						'sms_apikey' => array( 'type' => 'secret', 'label' => 'کلید API', 'ltr' => true ),
						'sms_originator' => array( 'type' => 'text', 'label' => 'شماره فرستنده', 'ltr' => true ),
						'sms_mode' => array(
							'type'    => 'select',
							'label'   => 'حالت ارسال',
							'options' => array( __CLASS__, 'sms_modes_crm' ),
						),
						'system_pattern_code' => array(
							'type'  => 'text',
							'label' => 'کد پترن اعلان‌های سیستمی',
							'ltr'   => true,
							'hint'  => 'برای یادآوری پیگیری و اعلان‌های مدیریتی استفاده می‌شود.',
						),
						'system_pattern_param' => array(
							'type'  => 'text',
							'label' => 'نام متغیر پترن سیستمی',
							'ltr'   => true,
						),
					),
				),

				'sms_crm_limits' => array(
					'label'       => 'سقف ارسال و ساعات مجاز',
					'description' => 'کنترل حجم و زمان ارسال صف پیامک CRM.',
					'store'       => 'crm',
					'module'      => 'crm',
					'columns'     => 2,
					'fields'      => array(
						'max_per_run' => array(
							'type'  => 'number',
							'label' => 'سقف ارسال در هر اجرای صف',
							'min'   => 1,
							'max'   => 5000,
							'hint'  => 'صف هر ۵ دقیقه یک‌بار اجرا می‌شود.',
						),
						'max_per_day' => array(
							'type'  => 'number',
							'label' => 'سقف ارسال روزانه',
							'min'   => 0,
							'max'   => 500000,
							'hint'  => 'صفر یعنی نامحدود.',
						),
						'sms_cost_per_part' => array(
							'type'  => 'number',
							'label' => 'هزینه‌ی هر بخش پیامک',
							'min'   => 0,
							'max'   => 100000,
							'hint'  => 'برای برآورد هزینه در گزارش‌ها. صفر یعنی محاسبه نشود.',
						),
						'send_from' => array(
							'type'    => 'select',
							'label'   => 'ساعت شروع مجاز ارسال',
							'options' => array( __CLASS__, 'hours' ),
						),
						'send_to' => array(
							'type'    => 'select',
							'label'   => 'ساعت پایان مجاز ارسال',
							'options' => array( __CLASS__, 'hours' ),
						),
					),
				),

				'sms_quiz' => array(
					'label'       => 'پیامک آزمون‌ساز',
					'description' => 'پیامک نتیجه‌ی آزمون به شرکت‌کننده. متغیرها: <code>%name%</code> <code>%score%</code> <code>%tier%</code> <code>%link%</code>',
					'store'       => 'quiz',
					'module'      => 'core',
					'columns'     => 2,
					'fields'      => array(
						'sms_apikey'  => array( 'type' => 'secret', 'label' => 'کلید API', 'ltr' => true ),
						'sms_sender'  => array( 'type' => 'text', 'label' => 'شماره فرستنده', 'ltr' => true, 'placeholder' => '+983000505' ),
						'sms_mode'    => array(
							'type'    => 'select',
							'label'   => 'حالت ارسال',
							'options' => array(
								'pattern' => 'پترن (پیشنهادی برای خط خدماتی)',
								'simple'  => 'متن آزاد (نیازمند خط اختصاصی)',
							),
						),
						'sms_pattern' => array( 'type' => 'text', 'label' => 'کد پترن نتیجه آزمون', 'ltr' => true ),
					),
				),

				'sms_consult' => array(
					'label'       => 'پیامک رزرو مشاوره',
					'description' => 'پیامک‌های چرخه‌ی رزرو مشاوره: ثبت، تأیید، یادآوری، انجام و لغو.',
					'store'       => 'consult',
					'module'      => 'core',
					'columns'     => 2,
					'fields'      => array(
						'sms_apikey'    => array( 'type' => 'secret', 'label' => 'کلید API', 'ltr' => true ),
						'sms_sender'    => array( 'type' => 'text', 'label' => 'شماره فرستنده', 'ltr' => true ),
						'admin_mobiles' => array(
							'type'  => 'textarea',
							'label' => 'شماره‌های مدیریت',
							'rows'  => 2,
							'ltr'   => true,
							'hint'  => 'جداشده با کاما یا خط جدید.',
						),
						'pat_admin'     => array( 'type' => 'text', 'label' => 'پترن: مشاوره جدید (به مدیریت)', 'ltr' => true ),
						'pat_user'      => array( 'type' => 'text', 'label' => 'پترن: تأیید ثبت (به کاربر)', 'ltr' => true ),
						'pat_reminder'  => array( 'type' => 'text', 'label' => 'پترن: یادآوری پیش از جلسه', 'ltr' => true ),
						'pat_confirmed' => array( 'type' => 'text', 'label' => 'پترن: تأیید شد', 'ltr' => true ),
						'pat_done'      => array( 'type' => 'text', 'label' => 'پترن: انجام شد', 'ltr' => true ),
						'pat_cancelled' => array( 'type' => 'text', 'label' => 'پترن: لغو شد', 'ltr' => true ),
					),
				),

				'sms_form' => array(
					'label'       => 'پیامک فرم‌ساز',
					'description' => 'اعلان ثبت فرم و کد تأیید شماره در فرم‌های سازان.',
					'store'       => 'form',
					'module'      => 'core',
					'columns'     => 2,
					'fields'      => array(
						'sms_apikey'    => array( 'type' => 'secret', 'label' => 'کلید API', 'ltr' => true ),
						'sms_sender'    => array( 'type' => 'text', 'label' => 'شماره فرستنده', 'ltr' => true ),
						'admin_mobiles' => array(
							'type'  => 'textarea',
							'label' => 'شماره‌های دریافت اعلان',
							'rows'  => 2,
							'ltr'   => true,
							'hint'  => 'جداشده با کاما یا خط جدید.',
						),
					),
				),
			),
		);
	}

	/**
	 * ابزار «ارسال پیامک آزمایشی» زیر بخش اتصال پیامک پنل.
	 *
	 * از همان هندلر AJAX ماژول پنل استفاده می‌کند، بنابراین دقیقاً همان مسیری
	 * را می‌آزماید که یادآورهای واقعی از آن ارسال می‌شوند.
	 */
	public static function render_sms_test() {

		if ( ! class_exists( 'SZP_SMS' ) ) {
			return;
		}
		?>
		<div class="szs-tester" data-action="szp_eval_test_sms" data-nonce="<?php echo esc_attr( wp_create_nonce( 'szp_admin' ) ); ?>">
			<label class="szs-field__label" for="szs-sms-test">ارسال پیامک آزمایشی</label>
			<div class="szs-tester__row">
				<input type="tel" id="szs-sms-test" class="szs-input szs-tester__input" dir="ltr" placeholder="09120000000" autocomplete="off">
				<button type="button" class="button szs-tester__send">ارسال آزمایشی</button>
			</div>
			<p class="szs-field__hint">ابتدا تنظیمات بالا را ذخیره کنید، سپس یادآور «تارگت» را برای این شماره بفرستید.</p>
			<p class="szs-tester__result" role="status" aria-live="polite"></p>
		</div>
		<?php
	}

	/* =====================================================================
	 * دسته: CRM
	 * =================================================================== */

	private static function category_crm() {
		return array(
			'label'       => 'CRM تیم فروش',
			'icon'        => 'dashicons-phone',
			'description' => 'دسترسی کارشناسان، قیف فروش، اتوماسیون، اهداف و راهنمای مکالمه.',
			'sections'    => array(

				'crm_access' => array(
					'label'       => 'نقش‌ها و دسترسی',
					'description' => 'مدیر همه‌ی سرنخ‌ها را می‌بیند؛ کارشناس فقط سرنخ‌های خودش را (مگر استخر مشترک روشن باشد).',
					'store'       => 'crm',
					'module'      => 'crm',
					'fields'      => array(
						'managers' => array(
							'type'  => 'users',
							'label' => 'مدیران فروش',
							'hint'  => 'دسترسی کامل به همه‌ی مخاطبین، گزارش‌ها و تنظیمات CRM.',
						),
						'agents' => array(
							'type'  => 'users',
							'label' => 'کارشناسان فروش',
							'hint'  => 'دسترسی محدود بر اساس مجوزهای پایین.',
						),
						'shared_pool' => array(
							'type'  => 'switch',
							'label' => 'استخر مشترک مخاطبین',
							'hint'  => 'روشن یعنی همه‌ی کارشناسان همه‌ی مخاطبین را می‌بینند.',
						),
					),
				),

				'crm_permissions' => array(
					'label'       => 'مجوزهای کارشناسان',
					'description' => 'هر کارشناس فقط کارهای تیک‌خورده را می‌تواند انجام دهد.',
					'store'       => 'crm',
					'module'      => 'crm',
					'fields'      => array(
						'agent_permissions' => array(
							'type'    => 'permissions',
							'label'   => 'مجوزهای پایه',
							'options' => array( __CLASS__, 'crm_permission_labels' ),
						),
					),
				),

				'crm_login' => array(
					'label'       => 'ورود کارشناسان به پورتال',
					'description' => 'ورود با شماره موبایل و کد یک‌بارمصرف.',
					'store'       => 'crm',
					'module'      => 'crm',
					'columns'     => 2,
					'fields'      => array(
						'pass_login' => array(
							'type'  => 'switch',
							'label' => 'ورود کارشناسان فعال باشد',
							'full'  => true,
						),
						'otp_expiry' => array(
							'type'  => 'number',
							'label' => 'اعتبار کد ورود',
							'min'   => 30,
							'max'   => 3600,
							'unit'  => 'ثانیه',
						),
						'otp_resend' => array(
							'type'  => 'number',
							'label' => 'فاصله مجاز ارسال مجدد',
							'min'   => 10,
							'max'   => 3600,
							'unit'  => 'ثانیه',
						),
						'otp_max_attempts' => array(
							'type'  => 'number',
							'label' => 'حداکثر تلاش برای هر کد',
							'min'   => 1,
							'max'   => 20,
						),
						'otp_pattern_code' => array(
							'type'  => 'text',
							'label' => 'کد پترن پیامک ورود',
							'ltr'   => true,
						),
						'otp_pattern_param' => array(
							'type'  => 'text',
							'label' => 'نام متغیر کد در پترن',
							'ltr'   => true,
						),
					),
				),

				'crm_funnel' => array(
					'label'       => 'قیف فروش و فیلدها',
					'description' => 'مراحل قیف، اولویت‌ها و فیلدهای سفارشی مخاطب.',
					'store'       => 'crm',
					'module'      => 'crm',
					'fields'      => array(
						'stages' => array(
							'type'        => 'kv_rows',
							'label'       => 'مراحل قیف فروش',
							'key_label'   => 'شناسه (انگلیسی)',
							'value_label' => 'برچسب نمایشی',
							'hint'        => 'ترتیب ردیف‌ها همان ترتیب قیف است. شناسه‌های <code>not_interested</code>، <code>wrong</code>، <code>lost</code> و <code>blacklist</code> خارج از قیف تبدیل محاسبه می‌شوند.',
						),
						'priorities' => array(
							'type'      => 'repeater',
							'label'     => 'اولویت‌ها',
							'keyed'     => true,
							'key_label' => 'شناسه',
							'row_label' => 'اولویت',
							'fields'    => array(
								'label' => array( 'type' => 'text', 'label' => 'برچسب' ),
								'color' => array( 'type' => 'color', 'label' => 'رنگ' ),
							),
						),
						'custom_fields' => array(
							'type'      => 'repeater',
							'label'     => 'فیلدهای سفارشی مخاطب',
							'row_label' => 'فیلد',
							'fields'    => array(
								'key'   => array( 'type' => 'text', 'label' => 'شناسه (انگلیسی)', 'ltr' => true ),
								'label' => array( 'type' => 'text', 'label' => 'عنوان' ),
							),
						),
					),
				),

				'crm_automation' => array(
					'label'       => 'اتوماسیون پس از تماس',
					'description' => 'ارسال خودکار پیامک پس از ثبت نتیجه‌ی تماس.',
					'store'       => 'crm',
					'module'      => 'crm',
					'columns'     => 2,
					'fields'      => array(
						'auto_after_call' => array(
							'type'  => 'switch',
							'label' => 'اتوماسیون پس از تماس فعال باشد',
							'full'  => true,
						),
						'auto_template_id' => array(
							'type'    => 'select',
							'label'   => 'قالب پیش‌فرض',
							'options' => array( __CLASS__, 'crm_templates' ),
						),
						'auto_delay_min' => array(
							'type'  => 'number',
							'label' => 'تأخیر ارسال',
							'min'   => 0,
							'max'   => 10080,
							'unit'  => 'دقیقه',
						),
						'outcome_templates' => array(
							'type'         => 'kv_select',
							'label'        => 'قالب اختصاصی هر نتیجه تماس',
							'full'         => true,
							'keys'         => array( __CLASS__, 'crm_outcomes' ),
							'options'      => array( __CLASS__, 'crm_templates' ),
							'hint'         => 'اگر برای یک نتیجه قالبی انتخاب شود، جای قالب پیش‌فرض را می‌گیرد.',
						),
					),
				),

				'crm_goals' => array(
					'label'       => 'اهداف، یادآوری و اعلان',
					'description' => 'یادآوری پیگیری‌ها و گزارش پیشرفت تارگت به مدیر.',
					'store'       => 'crm',
					'module'      => 'crm',
					'columns'     => 2,
					'fields'      => array(
						'followup_remind' => array( 'type' => 'switch', 'label' => 'پیامک یادآوری پیگیری به کارشناس' ),
						'followup_remind_email' => array( 'type' => 'switch', 'label' => 'ایمیل یادآوری پیگیری به کارشناس' ),
						'goal_alerts' => array(
							'type'  => 'switch',
							'label' => 'اعلان عبور از ۵۰٪، ۸۰٪ و ۱۰۰٪ تارگت',
						),
						'goal_daily_summary' => array( 'type' => 'switch', 'label' => 'جمع‌بندی پایان روز برای مدیر' ),
						'goal_summary_hour' => array(
							'type'    => 'select',
							'label'   => 'ساعت ارسال جمع‌بندی',
							'options' => array( __CLASS__, 'hours' ),
						),
						'manager_alert_mobiles' => array(
							'type'  => 'textarea',
							'label' => 'شماره‌های دریافت اعلان مدیریتی',
							'rows'  => 2,
							'ltr'   => true,
							'hint'  => 'جداشده با کاما یا خط جدید.',
						),
					),
				),

				'crm_links' => array(
					'label'       => 'لینک‌های پرکاربرد',
					'description' => 'لینک‌هایی که در قالب‌های پیامکی و پورتال کارشناس استفاده می‌شوند.',
					'store'       => 'crm',
					'module'      => 'crm',
					'columns'     => 2,
					'fields'      => array(
						'mini_link'  => array( 'type' => 'url', 'label' => 'لینک دوره‌ی مینی / معرفی کوتاه', 'ltr' => true ),
						'intro_link' => array( 'type' => 'url', 'label' => 'لینک صفحه‌ی معرفی اصلی', 'ltr' => true ),
					),
				),

				'crm_conversation' => array(
					'label'       => 'راهنمای مکالمه',
					'description' => 'متن‌هایی که هنگام تماس به کارشناس نشان داده می‌شود.',
					'store'       => 'crm',
					'module'      => 'crm',
					'fields'      => array(
						'conversation_enabled' => array(
							'type'  => 'switch',
							'label' => 'نمایش راهنمای مکالمه در صفحه‌ی تماس',
						),
						'conversation_opening' => array(
							'type'  => 'textarea',
							'label' => 'جمله‌ی آغازین',
							'rows'  => 3,
							'hint'  => 'متغیرها: <code>%last%</code> نام خانوادگی، <code>%first%</code> نام.',
						),
						'conversation_questions' => array(
							'type'  => 'lines',
							'label' => 'پرسش‌های کاوشی',
							'rows'  => 6,
							'hint'  => 'هر خط یک پرسش.',
						),
						'conversation_value_points' => array(
							'type'  => 'lines',
							'label' => 'نکات ارزش‌آفرین',
							'rows'  => 5,
						),
						'conversation_closings' => array(
							'type'  => 'lines',
							'label' => 'جمله‌های بستن گفت‌وگو',
							'rows'  => 5,
						),
						'conversation_guardrails' => array(
							'type'  => 'lines',
							'label' => 'بایدها و نبایدها',
							'rows'  => 5,
						),
						'conversation_stage_goals' => array(
							'type'  => 'kv_textarea',
							'label' => 'هدف گفت‌وگو در هر مرحله',
							'keys'  => array( __CLASS__, 'crm_stages' ),
							'hint'  => 'برای هر مرحله‌ی قیف بنویسید که هدف تماس چیست.',
						),
						'conversation_objections' => array(
							'type'      => 'repeater',
							'label'     => 'پاسخ به اعتراض‌های رایج',
							'row_label' => 'اعتراض',
							'fields'    => array(
								'title'    => array( 'type' => 'text', 'label' => 'عنوان اعتراض' ),
								'signals'  => array( 'type' => 'text', 'label' => 'کلیدواژه‌های تشخیص', 'hint' => 'جداشده با کاما.' ),
								'response' => array( 'type' => 'textarea', 'label' => 'پاسخ پیشنهادی', 'rows' => 3 ),
								'question' => array( 'type' => 'textarea', 'label' => 'پرسش پیگیری', 'rows' => 2 ),
							),
						),
					),
				),
			),
		);
	}

	/* =====================================================================
	 * دسته: هوش مصنوعی
	 * =================================================================== */

	private static function category_ai() {
		return array(
			'label'       => 'هوش مصنوعی',
			'icon'        => 'dashicons-lightbulb',
			'description' => 'اتصال به سرویس هوش مصنوعی برای تحلیل پاسخ‌ها و پیشنهاد کوچینگ.',
			'sections'    => array(
				'ai_connection' => array(
					'label'       => 'اتصال سرویس',
					'description' => 'کلید در پایگاه داده ذخیره می‌شود؛ آن را با کسی به اشتراک نگذارید.',
					'store'       => 'ai',
					'module'      => 'panel',
					'fields'      => array(
						'key' => array(
							'type'  => 'secret',
							'label' => 'کلید API',
							'ltr'   => true,
						),
						'model' => array(
							'type'        => 'text',
							'label'       => 'نام مدل',
							'ltr'         => true,
							'placeholder' => 'gpt-4o-mini',
							'hint'        => 'خالی بگذارید تا مدل پیش‌فرض افزونه استفاده شود.',
						),
						'base' => array(
							'type'        => 'text',
							'label'       => 'آدرس پایه‌ی سرویس',
							'ltr'         => true,
							'placeholder' => 'https://api.openai.com/v1',
							'hint'        => 'برای سرویس‌های سازگار (پروکسی داخلی و …) آدرس اختصاصی را وارد کنید.',
						),
					),
				),
			),
		);
	}

	/* =====================================================================
	 * دسته: صفحه محصول
	 * =================================================================== */

	private static function category_product() {
		return array(
			'label'       => 'صفحه اختصاصی محصول',
			'icon'        => 'dashicons-cart',
			'description' => 'مقادیر مشترکی که در سکشن‌های همه‌ی دوره‌ها تکرار می‌شوند.',
			'sections'    => array(

				'product_header' => array(
					'label'       => 'هدر مشترک همه دوره‌ها',
					'description' => 'لوگو، فهرست و دکمه‌ای که در بالای تمام صفحات دوره نمایش داده می‌شوند.',
					'store'       => 'spp',
					'module'      => 'product-page',
					'columns'     => 2,
					'fields'      => array(
						'header_logo' => array(
							'type'  => 'image',
							'label' => 'تصویر لوگوی هدر',
							'hint'  => 'لوگوی اصلی سایت را از کتابخانه رسانه انتخاب کنید. اگر خالی بماند، نشان SAZAN نمایش داده می‌شود.',
						),
						'header_logo_url' => array(
							'type'        => 'link',
							'label'       => 'لینک لوگو',
							'placeholder' => 'https://irsazan.com/',
							'ltr'         => true,
							'hint'        => 'اگر خالی بماند، لوگو به صفحه اصلی سایت می‌رود.',
						),
						'header_menu' => array(
							'type'    => 'select',
							'label'   => 'فهرست وردپرس هدر',
							'options' => array( __CLASS__, 'navigation_menus' ),
							'default' => 0,
							'hint'    => 'فهرستی را که در نمایش ← فهرست‌ها ساخته‌اید انتخاب کنید. گزینه پیش‌فرض، لینک‌های داخلی همان دوره را نشان می‌دهد.',
						),
						'header_button_text' => array(
							'type'        => 'text',
							'label'       => 'متن دکمه هدر',
							'default'     => 'ورود / ثبت‌نام',
							'placeholder' => 'ورود / ثبت‌نام',
						),
						'header_button_url' => array(
							'type'        => 'link',
							'label'       => 'لینک دکمه هدر',
							'placeholder' => '#enroll یا https://irsazan.com/my-account/',
							'ltr'         => true,
							'hint'        => 'لینک کامل یا لینک داخلی صفحه مانند #enroll وارد کنید. اگر خالی بماند، دکمه به بخش ثبت‌نام همان دوره می‌رود.',
						),
					),
				),

				'product_students' => array(
					'label'       => 'دانشجویان (سکشن هیرو)',
					'description' => 'اگر در متاباکس یک محصول آواتار تعریف شود، جای این‌ها را می‌گیرد.',
					'store'       => 'spp',
					'module'      => 'product-page',
					'fields'      => array(
						'avatars' => array(
							'type'      => 'repeater',
							'label'     => 'آواتار دانشجویان',
							'row_label' => 'آواتار',
							'max'       => 8,
							'fields'    => array(
								'img' => array( 'type' => 'image', 'label' => 'تصویر' ),
							),
						),
						'students_text' => array(
							'type'  => 'text',
							'label' => 'متن کنار تعداد دانشجو',
							'hint'  => 'اگر در محصول خالی باشد، از این استفاده می‌شود.',
						),
					),
				),

				'product_images' => array(
					'label'       => 'تصاویر تزئینی مشترک',
					'description' => 'این تصاویر یک‌بار انتخاب می‌شوند و در سکشن‌های مرتبط همه‌ی دوره‌ها به‌کار می‌روند.',
					'store'       => 'spp',
					'module'      => 'product-page',
					'columns'     => 3,
					'fields'      => array(
						'about_image' => array( 'type' => 'image', 'label' => 'تصویر سکشن «درباره دوره»' ),
						'faq_image'   => array(
							'type'  => 'image',
							'label' => 'تصویر سکشن سوالات متداول',
							'hint'  => 'خالی بگذارید تا علامت «؟» گرافیکی خود افزونه نمایش داده شود.',
						),
					),
				),

				'product_instructor' => array(
					'label'       => 'مدرس ثابت همه دوره‌ها',
					'description' => 'اطلاعات مدرس را یک‌بار وارد کنید. فیلدهای تکمیل‌شده در تمام صفحات دوره نمایش داده می‌شوند.',
					'store'       => 'spp',
					'module'      => 'product-page',
					'columns'     => 2,
					'fields'      => array(
						'global_instructor_enabled' => array( 'type' => 'switch', 'label' => 'استفاده در همه دوره‌ها' ),
						'inst_image' => array( 'type' => 'image', 'label' => 'تصویر مدرس' ),
						'inst_name' => array( 'type' => 'text', 'label' => 'نام مدرس' ),
						'inst_role' => array( 'type' => 'text', 'label' => 'عنوان و تخصص' ),
						'inst_short_description' => array( 'type' => 'textarea', 'label' => 'معرفی کوتاه' ),
						'inst_bio' => array( 'type' => 'textarea', 'label' => 'معرفی کامل' ),
						'inst_experience' => array( 'type' => 'text', 'label' => 'نشان تجربه', 'hint' => 'عبارت کامل؛ نمونه: ۱۸ سال تجربه اجرایی' ),
						'inst_projects' => array( 'type' => 'text', 'label' => 'نشان پروژه‌ها', 'hint' => 'عبارت کامل وارد شود.' ),
						'inst_teaching_hours' => array( 'type' => 'text', 'label' => 'نشان سابقه آموزش', 'hint' => 'عبارت کامل وارد شود.' ),
						'inst_linkedin' => array( 'type' => 'url', 'label' => 'LinkedIn' ),
						'inst_website' => array( 'type' => 'url', 'label' => 'وب‌سایت مدرس' ),
					),
				),

				'product_certificate' => array(
					'label'       => 'گواهینامه ثابت دوره‌ها',
					'description' => 'تصویر و متن ثابت گواهینامه را یک‌بار تنظیم کنید. با کلید اول می‌توانید آن را برای همه دوره‌ها فعال کنید.',
					'store'       => 'spp',
					'module'      => 'product-page',
					'columns'     => 2,
					'fields'      => array(
						'certificate_enabled_all' => array( 'type' => 'switch', 'label' => 'نمایش در همه دوره‌ها' ),
						'certificate_image' => array( 'type' => 'image', 'label' => 'تصویر گواهینامه' ),
						'certificate_badge' => array( 'type' => 'text', 'label' => 'برچسب سکشن' ),
						'certificate_title' => array( 'type' => 'text', 'label' => 'عنوان سکشن' ),
						'certificate_description' => array( 'type' => 'textarea', 'label' => 'توضیح گواهینامه' ),
						'certificate_conditions' => array( 'type' => 'textarea', 'label' => 'شرایط دریافت' ),
					),
				),
			),
		);
	}

	/* =====================================================================
	 * دسته: موتورها
	 * =================================================================== */

	private static function category_engines() {
		return array(
			'label'       => 'آزمون، مشاوره و فرم‌ساز',
			'icon'        => 'dashicons-forms',
			'description' => 'رفتار موتورهای هسته، جدا از تنظیمات پیامکی‌شان.',
			'sections'    => array(

				'engine_quiz' => array(
					'label'       => 'آزمون‌ساز',
					'description' => 'صفحه‌ای که نتیجه‌ی آزمون در آن نمایش داده می‌شود.',
					'store'       => 'quiz',
					'module'      => 'core',
					'fields'      => array(
						'result_page' => array(
							'type'  => 'page',
							'label' => 'صفحه نمایش نتیجه',
							'hint'  => 'یک صفحه بسازید و شورت‌کد <code>[sazan_quiz_result]</code> را داخل آن بگذارید.',
						),
					),
				),

				'engine_consult' => array(
					'label'       => 'رزرو مشاوره',
					'description' => 'ظرفیت‌ها، یادآوری و تأیید شماره.',
					'store'       => 'consult',
					'module'      => 'core',
					'columns'     => 2,
					'fields'      => array(
						'slot_cap' => array(
							'type'  => 'number',
							'label' => 'ظرفیت هر بازه زمانی',
							'min'   => 1,
							'max'   => 100,
						),
						'daily_cap' => array(
							'type'  => 'number',
							'label' => 'سقف رزرو روزانه',
							'min'   => 0,
							'max'   => 1000,
							'hint'  => 'صفر یعنی نامحدود.',
						),
						'reminder_hours' => array(
							'type'  => 'number',
							'label' => 'یادآوری چند ساعت قبل',
							'min'   => 1,
							'max'   => 168,
							'unit'  => 'ساعت',
						),
						'otp_enabled' => array(
							'type'  => 'switch',
							'label' => 'تأیید شماره با کد پیامکی',
						),
						'pat_otp' => array(
							'type'  => 'text',
							'label' => 'کد پترن کد تأیید',
							'ltr'   => true,
							'hint'  => 'متغیر پترن: <code>code</code>',
						),
					),
				),
			),
		);
	}
}
