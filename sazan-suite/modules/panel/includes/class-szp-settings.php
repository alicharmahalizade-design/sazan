<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * تنظیمات عمومی و قابل‌ویرایش رابط سازان.
 *
 * این کلاس عمداً مستقل از تنظیمات تخصصی ارزیابی است تا متن‌ها و ظاهر
 * سایر بخش‌های افزونه نیز از یک نقطه قابل مدیریت باشند.
 */
class SZP_Settings {

	const OPTION = 'szp_panel_settings';

	public static function defaults() {
		return array(
			// عمومی.
			'brand_name'               => 'سازان پنل',
			'login_message'             => 'برای مشاهده این بخش، ابتدا وارد حساب کاربری خود شوید.',
			'forbidden_message'         => 'شما به این بخش دسترسی ندارید.',

			// متن‌های فهرست‌ها.
			'courses_title'             => 'دوره‌های من',
			'courses_empty'             => 'هنوز دوره‌ای برای شما ثبت نشده است.',
			'chat_title'                => 'اتاق گفتگو',
			'chat_intro'                => 'برای ورود به اتاق گفتگوی هر دوره، آن را انتخاب کنید.',
			'chat_empty'                => 'هنوز دوره‌ای برای شما ثبت نشده است.',
			'back_courses'              => 'بازگشت به دوره‌ها',
			'back_course'               => 'بازگشت به دوره',

			// عنوان‌های دوره.
			'course_identity_title'     => 'اطلاعات و شناسنامه دوره',
			'course_outline_title'      => 'سرفصل‌ها',
			'course_goals_title'        => 'اهداف نهایی',
			'course_schedule_title'     => 'زمان‌بندی جلسات',
			'course_schedule_hint'      => 'برای دیدن هر جلسه، روی آن بزنید.',
			'course_next_session'       => 'جلسه بعدی',
			'course_past_sessions'      => 'جلسات برگزارشده',
			'course_no_sessions'        => 'هنوز جلسه‌ای ثبت نشده است.',
			'course_announcements'      => 'تابلو اعلانات کلاس',
			'course_files'              => 'فایل‌ها و منابع تکمیلی',
			'course_workbench'          => 'میز کار تمرین‌ها',
			'course_group'              => 'پنل گروه من',
			'course_survey'             => 'نظرسنجی‌های دوره',
			'show_more'                 => 'مشاهده بیشتر',

			// عنوان‌های جلسه.
			'session_identity_title'    => 'شناسنامه جلسه',
			'session_goals_title'       => 'اهداف کلیدی',
			'session_pack_title'        => 'فایل‌ها و محتوای جلسه',
			'session_assignment_title'  => 'تکلیف اختصاصی جلسه',
			'session_checklist_title'   => 'چک‌لیست جلسه',
			'session_survey_title'      => 'نظرسنجی بازخورد سریع',
			'submit_answer'             => 'ارسال پاسخ',
			'update_answer'             => 'ویرایش پاسخ',
			'submit_feedback'           => 'ثبت نظر',
			'course_table_session'      => 'جلسه',
			'course_table_deadline'     => 'ددلاین',
			'course_table_status'       => 'وضعیت',
			'course_status_sent'        => 'ارسال شد',
			'course_status_pending'     => 'ارسال نشده',
			'session_play_label'        => 'پخش',
			'session_speed_label'       => 'سرعت پخش',
			'session_download_label'    => 'دانلود',
			'session_pdf_label'         => 'اسلایدهای جلسه (PDF)',
			'session_sales_soon'        => 'فرم ارزیابی فروش شخصی به‌زودی فعال می‌شود.',
			'session_quiz_entry'        => 'ورود به تمرین / آزمون',
			'session_answer_saved'      => 'پاسخ شما ثبت شده است.',
			'session_last_edit'         => 'آخرین ویرایش',
			'session_sent_file'         => 'فایل ارسالی شما',
			'session_answer_placeholder'=> 'پاسخ خود را بنویسید...',
			'session_attachment_label'  => 'پیوست فایل (اختیاری)',
			'session_checklist_hint'    => 'تیک هر مورد بلافاصله ذخیره می‌شود.',
			'session_survey_answered'   => 'شما قبلاً پاسخ داده‌اید؛ می‌توانید ویرایش کنید.',

			// داشبورد رشد.
			'growth_title'              => 'ارزیابی هفتگی من',
			'growth_subtitle'           => 'برنامه هفتگی، نتیجه‌ها، تیم و کوچینگ در یک مسیر ساده',
			'growth_today_title'        => 'قدم بعدی شما',
			'growth_explain_title'      => 'نتیجه چگونه مشخص می‌شود؟',
			'growth_explain_text'       => 'هفته بعد «فروش واقعی» را در فرم نتیجه وارد می‌کنید؛ سیستم آن را با تارگت مقایسه می‌کند و درصد تحقق را خودکار نشان می‌دهد.',
			'growth_target_title'       => 'مرحله ۱: برنامه این هفته',
			'growth_result_title'       => 'مرحله ۲: نتیجه این هفته',
			'growth_target_button'      => 'ثبت تارگت هفتگی',
			'growth_result_button'      => 'ثبت نتیجه این هفته',
			'growth_history_title'      => 'هفته‌های گذشته',
			'growth_history_help'       => 'برای دیدن جزئیات، هر هفته را باز کنید.',
			'growth_pending_label'      => 'نتیجه ثبت نشده',
			'growth_next_title'         => 'قدم بعدی: فروش واقعی این هفته را ثبت کنید.',
			'growth_next_text'          => 'بعد از ثبت، درصد تحقق و وضعیت هدف به‌صورت خودکار مشخص می‌شود.',
			'growth_next_button'        => 'رفتن به فرم ثبت نتیجه',
			'status_beyond'             => 'بیشتر از هدف',
			'status_success'            => 'هدف کامل محقق شد',
			'status_improve'            => 'نزدیک به هدف',
			'status_ontrack'            => 'کمتر از هدف',
			'show_growth_explanation'  => 1,
			'show_growth_summary'      => 1,
			'show_history_help'        => 1,
			'profile_title'             => 'اطلاعات پایه',
			'profile_incomplete_hint'   => 'برای شروع، این بخش را یک‌بار تکمیل کنید.',
			'profile_complete_label'    => 'تکمیل شده',
			'profile_required_label'    => 'نیازمند تکمیل',
			'profile_full_name_label'   => 'نام و نام خانوادگی',
			'profile_business_label'    => 'نام کسب‌وکار',
			'profile_position_label'    => 'سمت در مجموعه',
			'profile_position_hint'     => 'مثلاً مدیر مجموعه، مدیر فروش یا تیم فروش',
			'profile_industry_label'    => 'حوزه فعالیت',
			'profile_industry_hint'     => 'مثلاً آموزش، پوشاک یا خدمات',
			'profile_save_button'       => 'ذخیره اطلاعات پایه',
			'profile_required_callout'  => 'ابتدا اطلاعات پایه بالا را تکمیل کنید؛ سپس فرم هفتگی برای شما فعال می‌شود.',
			'growth_schedule_text'      => 'دوشنبه: ثبت نتیجه هفته قبل · سه‌شنبه: ثبت نتیجه هفته قبل و تارگت هفته جدید',
			'growth_schedule_monday'    => 'ثبت نتیجه تارگت هفته قبل',
			'growth_schedule_tuesday'   => 'فرصت مجدد برای ثبت نتیجه تارگت هفته قبل و ثبت تارگت هفته جدید',
			'growth_result_ready_label' => 'نتیجه تارگت هفته قبل',
			'growth_result_ready_button'=> 'ثبت نتیجه تارگت',
			'growth_result_waiting_label'=> 'زمان بازشدن ثبت نتیجه',
			'growth_countdown_days'     => 'روز',
			'growth_countdown_hours'    => 'ساعت',
			'growth_countdown_minutes'  => 'دقیقه',
			'dashboard_kicker'          => 'ارزیابی من',
			'dashboard_title'           => 'داشبورد فعالیت هفتگی',
			'dashboard_current_session' => 'جلسه جاری',
			'dashboard_status_label'    => 'وضعیت فعلی',
			'dashboard_profile_status'  => 'اطلاعات پایه تکمیل نشده',
			'dashboard_profile_detail'  => 'مرحله اول شروع ارزیابی',
			'dashboard_profile_button'  => 'تکمیل اطلاعات پایه',
			'dashboard_missing_result_status' => 'نتیجه ثبت نشده',
			'dashboard_missing_result_button' => 'ثبت نتیجه عقب‌افتاده',
			'dashboard_result_status'   => 'زمان ثبت نتیجه است',
			'dashboard_result_button'   => 'ثبت نتیجه هفته قبل',
			'dashboard_target_status'   => 'زمان ثبت تارگت است',
			'dashboard_target_button'   => 'ثبت تارگت این هفته',
			'dashboard_overdue_status'  => 'سوابق ناقص است',
			'dashboard_overdue_button'  => 'مشاهده وضعیت ناقص',
			'dashboard_overdue_label'   => 'نقص‌های قبلی',
			'dashboard_overdue_count_unit' => 'مورد ناقص',
			'dashboard_wait_result_status' => 'در انتظار ثبت نتیجه',
			'dashboard_wait_result_button' => 'در انتظار ثبت نتیجه',
			'dashboard_result_countdown'=> 'تا بازشدن ثبت نتیجه',
			'dashboard_wait_target_status' => 'در انتظار ثبت تارگت',
			'dashboard_wait_target_button' => 'در انتظار ثبت تارگت',
			'dashboard_target_countdown'=> 'تا بازشدن ثبت تارگت',
			'mobile_quick_title'        => 'دید سریع',
			'mobile_quick_summary'      => 'خلاصه من',
			'mobile_quick_history'      => 'سوابق',
			'mobile_quick_team'         => 'تیم من',
			'mobile_quick_coaching'     => 'کوچینگ',
			'mobile_quick_members'      => 'اعضای تیم',
			'mobile_quick_members_hint' => 'جست‌وجو و مشاهده گزارش اعضا',
			'dashboard_current_actions_label'  => 'ثبت‌های این هفته',
			'dashboard_current_result_button'  => 'ثبت نتیجه این هفته',
			'dashboard_current_target_button'  => 'ثبت تارگت این هفته',
			'wizard_step_label'         => 'مرحله فعلی',
			'wizard_close_label'        => 'بستن پنجره',
			'wizard_previous_button'    => 'مرحله قبل',
			'wizard_next_button'        => 'مرحله بعد',
			'target_saved_text'         => 'تارگت ثبت شده است.',
			'target_action_help'        => 'اقدام اول را با توضیح کامل بنویسید. اگر لازم بود، اقدام‌های بعدی را اضافه کنید.',
			'target_action_label'       => 'اقدام',
			'target_action_placeholder'=> 'این اقدام را دقیق و قابل اجرا توضیح دهید',
			'target_action_remove'      => 'حذف اقدام',
			'target_action_add'         => 'افزودن اقدام دیگر',
			'target_locked_title'       => 'امروز کاری برای این بخش ندارید.',
			'target_locked_text'        => 'فرم برنامه هفته جدید، روز سه‌شنبه باز می‌شود.',
			'result_saved_text'         => 'گزارش عملکرد ثبت شده است.',
			'result_intro_title'        => 'برای مشخص‌شدن نتیجه، این فرم را تکمیل کنید.',
			'result_intro_text'         => 'مهم‌ترین عدد «فروش واقعی» است. سامانه بعد از ثبت، آن را با تارگت مقایسه و وضعیت را خودکار محاسبه می‌کند.',
			'result_locked_title'       => 'ثبت نتیجه بسته است',
			'result_locked_text'        => 'زمان عادی ثبت نتیجه دوشنبه و سه‌شنبه است. برای بازکردن فرم خارج از این زمان، با مدیر دوره هماهنگ کنید.',
			'result_preserved_text'     => 'نتیجه قدیمی شما محفوظ است.',
			'select_placeholder'        => 'انتخاب کنید',
			'summary_target_label'      => 'تارگت ثبت‌شده',
			'summary_result_label'      => 'نتیجه ثبت‌شده',
			'summary_average_label'     => 'میانگین تحقق',
			'history_empty_title'       => 'هنوز سابقه‌ای ثبت نشده است.',
			'history_empty_text'        => 'بعد از اولین ثبت هفتگی، سوابق شما اینجا نمایش داده می‌شود.',
			'completed_history_title'   => 'هفته‌های تکمیل‌شده',
			'completed_history_empty_title' => 'هنوز هفته کاملی ثبت نشده است.',
			'completed_history_empty_text' => 'پس از ثبت تارگت و نتیجه، هفته کامل اینجا نمایش داده می‌شود.',
			'history_target_label'      => 'هدف مالی',
			'history_actual_label'      => 'فروش واقعی',
			'history_not_registered'    => 'ثبت نشده',
			'team_coach_kicker'         => 'نمای کوچ',
			'team_manager_kicker'       => 'نمای مدیر',
			'team_coach_title'          => 'تیم‌های سازمانی',
			'team_manager_title'        => 'زیرمجموعه‌های من',
			'team_empty_title'          => 'هنوز عضوی برای نمایش وجود ندارد.',
			'team_empty_text'           => 'پس از تعریف اعضا، وضعیت هفتگی آن‌ها اینجا دیده می‌شود.',
			'coaching_kicker'           => 'همراهی و اجرا',
			'coaching_title'            => 'کوچینگ کسب‌وکار',
			'coaching_admin_help'       => 'مدیریت تخصصی برنامه‌ها، شاخص‌ها و بازخوردها از صفحه مدیریت کوچینگ در پیشخوان انجام می‌شود.',

			// پرسش‌های تارگت.
			'target_q1'                 => '۱. تارگت مالی این هفته',
			'target_q2'                 => '۲. اگر این هفته را موفق بدانید، چه نتیجه‌ای باید به دست آمده باشد؟',
			'target_q3'                 => '۳. تارگت تعداد مشتری جدید',
			'target_q4'                 => '۴. اقدام‌های اصلی برای رسیدن به هدف',
			'target_q5'                 => '۵. بزرگ‌ترین مانع احتمالی این هفته',
			'target_q6'                 => '۶. برنامه شما برای رفع این مانع',
			'target_q7'                 => '۷. میزان انگیزه',
			'target_q8'                 => '۸. میزان تعهد به اجرا',

			// پرسش‌های نتیجه.
			'result_q1'                 => '۱. هدف این هفته',
			'result_q2'                 => '۲. ارزیابی خودت: چند درصد به هدف رسیدی؟',
			'result_q3'                 => '۳. فروش واقعی این هفته',
			'result_q4'                 => '۴. تعداد مشتری واقعی',
			'result_q5'                 => '۵. بزرگ‌ترین موفقیت این هفته چه بود؟',
			'result_q6'                 => '۶. مهم‌ترین اشتباه یا نقطه ضعف چه بود؟',
			'result_q7'                 => '۷. مهم‌ترین چیزی که این هفته یاد گرفتی چه بود؟',

			// ظاهر.
			'color_primary'             => '#2563eb',
			'color_secondary'           => '#f7941d',
			'color_success'             => '#15803d',
			'color_danger'              => '#b91c1c',
			'color_background'          => '#f7f9fc',
			'color_surface'             => '#ffffff',
			'color_text'                => '#172033',
			'color_muted'               => '#64748b',
			'color_border'              => '#dbe3ef',
			'font_family'               => 'inherit',
			'base_font_size'            => 16,
			'content_width'             => 1040,
			'card_radius'               => 18,
			'button_radius'             => 11,
			'shadow_strength'           => 8,
			'custom_css'                => '',
		);
	}

	public static function all() {
		$stored = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
	}

	public static function get( $key, $fallback = null ) {
		$all = self::all();
		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}
		return $fallback;
	}

	public static function text( $key, $fallback = '' ) {
		$value = trim( (string) self::get( $key, $fallback ) );
		// جایگزینی فقط برای متن‌های پیش‌فرض قدیمی؛ متن سفارشی مدیر دست‌نخورده می‌ماند.
		$legacy = array(
			'dashboard_missing_result_status' => array(
				'نتیجه هفته قبل ثبت نشده' => 'نتیجه ثبت نشده',
			),
			'dashboard_overdue_status' => array(
				'ثبت‌های هفته‌های قبل ناقص است' => 'سوابق ناقص است',
				'تارگت یکی از هفته‌های قبل ثبت نشده' => 'تارگت ثبت نشده',
			),
			'dashboard_overdue_label' => array(
				'ثبت ناقص از هفته‌های قبل' => 'نقص‌های قبلی',
			),
			'mobile_quick_history' => array(
				'هفته‌های قبل' => 'سوابق',
			),
			'team_coach_title' => array(
				'دانشجویان و اعضای تیم' => 'تیم‌های سازمانی',
			),
			'growth_result_title' => array(
				'مرحله ۲: نتیجه هفته قبل' => 'مرحله ۲: نتیجه این هفته',
			),
			'growth_result_button' => array(
				'ثبت نتیجه هفته گذشته' => 'ثبت نتیجه این هفته',
			),
			'dashboard_current_result_button' => array(
				'ثبت نتیجه هفته قبل' => 'ثبت نتیجه این هفته',
			),
			'result_q1' => array(
				'۱. هدف هفته گذشته' => '۱. هدف این هفته',
			),
			'result_q3' => array(
				'۳. فروش واقعی هفته گذشته' => '۳. فروش واقعی این هفته',
			),
		);
		if ( isset( $legacy[ $key ][ $value ] ) ) {
			$value = $legacy[ $key ][ $value ];
		}
		return $value !== '' ? $value : $fallback;
	}

	public static function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();
		$out      = array();
		$colors   = array( 'color_primary', 'color_secondary', 'color_success', 'color_danger', 'color_background', 'color_surface', 'color_text', 'color_muted', 'color_border' );
		$booleans = array( 'show_growth_explanation', 'show_growth_summary', 'show_history_help' );
		$numbers  = array(
			'base_font_size'  => array( 13, 22 ),
			'content_width'   => array( 680, 1600 ),
			'card_radius'     => array( 0, 40 ),
			'button_radius'   => array( 0, 30 ),
			'shadow_strength' => array( 0, 30 ),
		);

		foreach ( $defaults as $key => $default ) {
			if ( in_array( $key, $booleans, true ) ) {
				$out[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
			} elseif ( in_array( $key, $colors, true ) ) {
				$color       = sanitize_hex_color( $input[ $key ] ?? '' );
				$out[ $key ] = $color ? $color : $default;
			} elseif ( isset( $numbers[ $key ] ) ) {
				$value       = absint( $input[ $key ] ?? $default );
				$out[ $key ] = min( $numbers[ $key ][1], max( $numbers[ $key ][0], $value ) );
			} elseif ( $key === 'custom_css' ) {
				$out[ $key ] = wp_strip_all_tags( (string) ( $input[ $key ] ?? '' ) );
			} elseif ( $key === 'font_family' ) {
				$out[ $key ] = sanitize_text_field( $input[ $key ] ?? $default );
			} else {
				$out[ $key ] = sanitize_textarea_field( $input[ $key ] ?? $default );
			}
		}
		return $out;
	}

	public static function inline_css() {
		$s      = self::all();
		$shadow = (int) $s['shadow_strength'];
		$font   = preg_replace( '/[^a-zA-Z0-9_\-\s,"\',\.]/u', '', (string) $s['font_family'] );
		$font   = $font !== '' ? $font : 'inherit';

		$css = sprintf(
			'.szp{--szp-primary:%1$s;--szp-secondary:%2$s;--szp-bg:%3$s;--szp-glass:%4$s;--szp-glass-2:%4$s;--szp-border:%5$s;--szp-border-2:%1$s;--szp-text:%6$s;--szp-muted:%7$s;--szp-radius:%8$dpx;--szp-grad:linear-gradient(135deg,%1$s 0%%,%2$s 100%%);--szp-grad-soft:linear-gradient(135deg,%1$s1f,%2$s1a);--szp-shadow:0 %9$dpx %10$dpx rgba(15,23,42,.12);max-width:%11$dpx;font-size:%12$dpx;font-family:%13$s}.szp-growth{--gr-primary:%1$s;--gr-success:%14$s;--gr-text:%6$s;--gr-muted:%7$s;--gr-border:%5$s;--gr-surface:%4$s;--gr-bg:%3$s;--gr-btn-radius:%15$dpx;max-width:%11$dpx;margin-inline:auto;font-size:%12$dpx;font-family:%13$s}.szp-growth-card,.szp-growth-profile,.szp-growth-today{border-radius:%8$dpx}.szp button,.szp-growth button,.szp-growth a.szp-growth-btn{border-radius:%15$dpx}',
			esc_attr( $s['color_primary'] ),
			esc_attr( $s['color_secondary'] ),
			esc_attr( $s['color_background'] ),
			esc_attr( $s['color_surface'] ),
			esc_attr( $s['color_border'] ),
			esc_attr( $s['color_text'] ),
			esc_attr( $s['color_muted'] ),
			(int) $s['card_radius'],
			max( 0, (int) round( $shadow / 2 ) ),
			max( 0, $shadow * 4 ),
			(int) $s['content_width'],
			(int) $s['base_font_size'],
			$font,
			esc_attr( $s['color_success'] ),
			(int) $s['button_radius']
		);
		$css .= sprintf(
			'.szp h2,.szp h3,.szp .szp-sec-h,.szp .szp-card-title,.szp .szp-next-title,.szp .szp-sessions a,.szp .szp-table a,.szp .szp-q-text,.szp .szp-quiz-title,.szp .szp-ap-name{color:%1$s!important}.szp .szp-rich,.szp .szp-list>li,.szp .szp-hero-sub,.szp .szp-an-text,.szp .szp-check label,.szp .szp-file{color:%1$s}.szp input:not([type=submit]):not([type=button]),.szp textarea,.szp select{background:%2$s;color:%1$s;border-color:%3$s}.szp-eval2{--ev-accent:%4$s;--ev-blue:%4$s;--ev-green:%5$s;--ev-bg:%2$s;--ev-bg2:%6$s;--ev-text:%1$s;--ev-text2:%7$s;--ev-line:%3$s;--ev-input-bg:%2$s;--ev-input-text:%1$s}.szp-growth input,.szp-growth textarea,.szp-growth select{background:%2$s;color:%1$s;border-color:%3$s}.szp-growth .szp-growth-msg.is-err,.szp-growth .szp-ev-msg.is-err{color:%8$s}',
			esc_attr( $s['color_text'] ),
			esc_attr( $s['color_surface'] ),
			esc_attr( $s['color_border'] ),
			esc_attr( $s['color_primary'] ),
			esc_attr( $s['color_success'] ),
			esc_attr( $s['color_background'] ),
			esc_attr( $s['color_muted'] ),
			esc_attr( $s['color_danger'] )
		);
		if ( trim( (string) $s['custom_css'] ) !== '' ) {
			$css .= "\n" . (string) $s['custom_css'];
		}
		return $css;
	}
}
