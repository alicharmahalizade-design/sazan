<?php
/**
 * Data schema, defaults, sanitization and legacy fallbacks for Product Page v2.
 *
 * @package Sazan\ProductPage\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SPP_V2_Data {

	const META_KEY = '_spp_v2_course';

	/**
	 * Metabox tabs and their field schemas.
	 *
	 * @return array
	 */
	public static function tabs() {
		return array(
			'intro' => array(
				'label'  => 'معرفی دوره',
				'icon'   => 'dashicons-welcome-learn-more',
				'fields' => array(
					'eyebrow' => self::field( 'text', 'برچسب بالای عنوان', 'دوره تخصصی سازان' ),
					'subtitle' => self::field( 'text', 'زیرعنوان' ),
					'short_description' => self::field( 'textarea', 'توضیح کوتاه هیرو' ),
					'hero_image' => self::field( 'image', 'تصویر اختصاصی هیرو' ),
					'background_image' => self::field( 'image', 'تصویر پس‌زمینه تزئینی' ),
					'preview_video' => self::field( 'url', 'لینک ویدیوی معرفی', '', array( 'hint' => 'YouTube، Vimeo، آپارات یا فایل مستقیم MP4.' ) ),
					'primary_cta' => self::field( 'text', 'متن CTA اصلی', 'ثبت‌نام در دوره' ),
					'secondary_cta' => self::field( 'text', 'متن CTA دوم', 'مشاهده پیش‌نمایش' ),
					'secondary_url' => self::field( 'url', 'لینک CTA دوم' ),
					'badge' => self::field( 'text', 'Badge' ),
					'manual_rating' => self::field( 'number', 'امتیاز دستی اختیاری', '', array( 'min' => 0, 'max' => 5, 'step' => '0.1' ) ),
					'student_count' => self::field( 'number', 'تعداد دانشجو', '', array( 'min' => 0, 'step' => 1 ) ),
					'remaining_seats' => self::field( 'number', 'تعداد صندلی باقی‌مانده', '', array( 'min' => 0, 'step' => 1, 'hint' => 'فقط در صورت اعلام ظرفیت واقعی تکمیل شود.' ) ),
					'show_students' => self::field( 'switch', 'نمایش تعداد دانشجو', '' ),
					'course_code' => self::field( 'text', 'کد دوره' ),
					'show_navigation' => self::field( 'switch', 'نمایش ناوبری چسبان', '1' ),
					'show_mobile_bar' => self::field( 'switch', 'نمایش نوار خرید موبایل', '1' ),
				),
			),
			'seo' => array(
				'label'  => 'سئو و داده‌های دوره',
				'icon'   => 'dashicons-search',
				'fields' => array(
					'fallback_title' => self::field( 'text', 'عنوان سئو جایگزین', '', array( 'hint' => 'فقط وقتی افزونه سئو فعال نیست استفاده می‌شود. در حالت عادی عنوان را در Rank Math تنظیم کنید.' ) ),
					'fallback_description' => self::field( 'textarea', 'توضیحات متای جایگزین', '', array( 'hint' => 'فقط برای سایت‌هایی که افزونه سئو ندارند؛ پیشنهاد: ۱۴۰ تا ۱۶۰ نویسه.' ) ),
					'social_image' => self::field( 'image', 'تصویر اشتراک‌گذاری جایگزین', '', array( 'hint' => 'برای Open Graph و Twitter در نبود افزونه سئو. تصویر پیشنهادی ۱۲۰۰×۶۳۰ پیکسل.' ) ),
					'course_level' => self::field( 'text', 'سطح دوره', 'پیشرفته' ),
					'course_mode' => self::field( 'select', 'نحوه برگزاری', 'online', array( 'options' => array( 'online' => 'آنلاین', 'onsite' => 'حضوری', 'blended' => 'ترکیبی' ) ) ),
					'duration_iso' => self::field( 'text', 'مدت دوره با فرمت ISO 8601', '', array( 'hint' => 'نمونه: PT32H برای ۳۲ ساعت. این مقدار در Course Schema استفاده می‌شود.' ) ),
					'start_date' => self::field( 'datetime', 'تاریخ شروع دوره' ),
					'end_date' => self::field( 'datetime', 'تاریخ پایان دوره' ),
					'prerequisites' => self::field( 'textarea', 'پیش‌نیازهای دوره' ),
					'teaches' => self::field( 'textarea', 'مهارت‌ها و خروجی‌های یادگیری', '', array( 'hint' => 'هر مهارت را در یک خط جدا وارد کنید.' ) ),
					'credential' => self::field( 'text', 'عنوان گواهینامه', 'گواهینامه پایان دوره سازان' ),
					'provider_name' => self::field( 'text', 'نام برگزارکننده', 'آکادمی سازان' ),
					'provider_url' => self::field( 'url', 'نشانی برگزارکننده', home_url( '/' ) ),
				),
			),
			'facts' => array(
				'label'  => 'اطلاعات سریع',
				'icon'   => 'dashicons-info-outline',
				'fields' => array(
					'items' => self::repeater( 'مشخصات دوره', 'مشخصه', array(
						'icon'  => self::field( 'icon', 'آیکن', 'clock' ),
						'title' => self::field( 'text', 'عنوان' ),
						'value' => self::field( 'text', 'مقدار' ),
					), 12 ),
				),
			),
			'prerequisite' => array(
				'label'  => 'پیش‌نیاز',
				'icon'   => 'dashicons-yes-alt',
				'fields' => array(
					'enabled' => self::field( 'switch', 'نمایش سکشن پیش‌نیاز', '' ),
					'eyebrow' => self::field( 'text', 'برچسب', 'قبل از شروع' ),
					'title' => self::field( 'text', 'عنوان', 'پیش‌نیاز این دوره چیست؟' ),
					'description' => self::field( 'editor', 'توضیح' ),
					'image' => self::field( 'image', 'تصویر اختیاری' ),
					'cta_text' => self::field( 'text', 'متن CTA اختیاری' ),
					'cta_url' => self::field( 'url', 'لینک CTA' ),
				),
			),
			'benefits' => array(
				'label'  => 'مزیت‌ها',
				'icon'   => 'dashicons-awards',
				'fields' => array(
					'eyebrow' => self::field( 'text', 'برچسب', 'مزیت‌های دوره' ),
					'title' => self::field( 'text', 'عنوان', 'چرا این دوره برای شما ساخته شده؟' ),
					'items' => self::repeater( 'مزیت‌ها', 'مزیت', array(
						'icon' => self::field( 'icon', 'آیکن', 'check' ),
						'title' => self::field( 'text', 'عنوان' ),
						'description' => self::field( 'textarea', 'توضیح' ),
					), 12 ),
				),
			),
			'about' => array(
				'label'  => 'معرفی کامل',
				'icon'   => 'dashicons-text-page',
				'fields' => array(
					'eyebrow' => self::field( 'text', 'Eyebrow', 'درباره دوره' ),
					'title' => self::field( 'text', 'عنوان' ),
					'highlight' => self::field( 'text', 'عبارت برجسته در عنوان' ),
					'lead' => self::field( 'textarea', 'متن معرفی کوتاه' ),
					'description' => self::field( 'editor', 'توضیحات کامل' ),
					'image' => self::field( 'image', 'تصویر یا Illustration' ),
					'cta_text' => self::field( 'text', 'متن CTA' ),
					'cta_url' => self::field( 'url', 'لینک CTA' ),
					'before_title' => self::field( 'text', 'عنوان ستون پیش از شروع', 'سه مسئله‌ای که ممکن است امروز تجربه کنید' ),
					'before_items' => self::repeater( 'مشکلات پیش از شروع (دقیقاً ۳ مورد)', 'مسئله', array(
						'text' => self::field( 'textarea', 'شرح مسئله' ),
					), 3 ),
					'after_title' => self::field( 'text', 'عنوان ستون پس از پایان', 'سه تغییری که پس از پایان مسیر به دست می‌آورید' ),
					'after_items' => self::repeater( 'دستاوردهای پس از پایان (دقیقاً ۳ مورد)', 'دستاورد', array(
						'text' => self::field( 'textarea', 'شرح دستاورد' ),
					), 3 ),
				),
			),
			'curriculum' => array(
				'label'  => 'سرفصل‌ها',
				'icon'   => 'dashicons-list-view',
				'fields' => array(
					'eyebrow' => self::field( 'text', 'برچسب', 'مسیر یادگیری' ),
					'title' => self::field( 'text', 'عنوان', 'نقشه کامل یادگیری دوره' ),
					'description' => self::field( 'textarea', 'توضیح کوتاه' ),
					'groups' => self::repeater( 'گروه‌های اصلی', 'گروه', array(
						'title' => self::field( 'text', 'عنوان گروه' ),
						'description' => self::field( 'textarea', 'توضیح گروه' ),
						'output' => self::field( 'text', 'خروجی این مرحله' ),
						'course_count' => self::field( 'text', 'تعداد دوره/فصل' ),
						'duration' => self::field( 'text', 'مدت کل' ),
						'image' => self::field( 'image', 'تصویر یا آیکن' ),
						'chapters' => self::repeater( 'فصل‌های این گروه', 'فصل', array(
							'title' => self::field( 'text', 'عنوان فصل' ),
							'instructor' => self::field( 'text', 'مدرس' ),
							'instructor_role' => self::field( 'text', 'سمت مدرس' ),
							'instructor_image' => self::field( 'image', 'تصویر مدرس' ),
							'duration' => self::field( 'text', 'مدت' ),
							'description' => self::field( 'textarea', 'توضیح' ),
							'lessons' => self::repeater( 'جلسه‌ها', 'جلسه', array(
								'title' => self::field( 'text', 'عنوان جلسه' ),
								'duration' => self::field( 'text', 'زمان' ),
								'preview' => self::field( 'switch', 'قابل پیش‌نمایش' ),
								'preview_url' => self::field( 'url', 'لینک پیش‌نمایش' ),
								'icon' => self::field( 'icon', 'آیکن', 'video' ),
							), 80 ),
						), 40 ),
					), 12 ),
				),
			),
			'instructors' => array(
				'label'  => 'مدرس‌ها',
				'icon'   => 'dashicons-groups',
				'fields' => array(
					'eyebrow' => self::field( 'text', 'برچسب', 'تیم آموزشی' ),
					'title' => self::field( 'text', 'عنوان', 'با مدرس‌های دوره آشنا شوید' ),
					'items' => self::repeater( 'مدرس‌ها', 'مدرس', array(
						'name' => self::field( 'text', 'نام' ),
						'image' => self::field( 'image', 'تصویر' ),
						'role' => self::field( 'text', 'سمت' ),
						'short_description' => self::field( 'textarea', 'توضیح کوتاه' ),
						'bio' => self::field( 'editor', 'Bio' ),
						'linkedin' => self::field( 'url', 'LinkedIn' ),
						'website' => self::field( 'url', 'وب‌سایت' ),
						'experience' => self::field( 'text', 'سال تجربه' ),
						'projects' => self::field( 'text', 'تعداد پروژه' ),
						'teaching_hours' => self::field( 'text', 'ساعت آموزش' ),
						'company_logos' => self::repeater( 'لوگوی شرکت‌ها', 'لوگو', array(
							'image' => self::field( 'image', 'تصویر لوگو' ),
							'name' => self::field( 'text', 'نام شرکت' ),
						), 10 ),
					), 12 ),
				),
			),
			'certificate' => array(
				'label'  => 'گواهینامه',
				'icon'   => 'dashicons-media-document',
				'fields' => array(
					'enabled' => self::field( 'switch', 'نمایش سکشن گواهینامه', '' ),
					'badge' => self::field( 'text', 'Badge', 'گواهینامه پایان دوره' ),
					'title' => self::field( 'text', 'عنوان' ),
					'description' => self::field( 'editor', 'توضیح' ),
					'image' => self::field( 'image', 'تصویر گواهینامه' ),
					'conditions' => self::field( 'textarea', 'شرایط دریافت' ),
					'cta_text' => self::field( 'text', 'متن CTA اختیاری' ),
					'cta_url' => self::field( 'url', 'لینک CTA' ),
				),
			),
			'reviews' => array(
				'label'  => 'نظرات',
				'icon'   => 'dashicons-star-filled',
				'fields' => array(
					'eyebrow' => self::field( 'text', 'برچسب', 'تجربه دانشجویان' ),
					'title' => self::field( 'text', 'عنوان', 'از زبان شرکت‌کنندگان دوره' ),
					'source' => self::field( 'select', 'منبع نظرات', 'both', array( 'options' => array( 'both' => 'WooCommerce + دستی', 'woo' => 'فقط WooCommerce', 'manual' => 'فقط دستی' ) ) ),
					'initial_count' => self::field( 'number', 'تعداد نمایش اولیه', '6', array( 'min' => 3, 'max' => 12, 'step' => 1 ) ),
					'items' => self::repeater( 'نظرات دستی', 'نظر', array(
						'avatar' => self::field( 'image', 'Avatar' ),
						'name' => self::field( 'text', 'نام' ),
						'job' => self::field( 'text', 'سمت شغلی' ),
						'company' => self::field( 'text', 'شرکت' ),
						'rating' => self::field( 'number', 'امتیاز', '5', array( 'min' => 0, 'max' => 5, 'step' => 1 ) ),
						'text' => self::field( 'textarea', 'متن نظر' ),
						'video_id' => self::field( 'video', 'آپلود ویدیوی تجربه' ),
						'video_url' => self::field( 'url', 'لینک ویدیو اختیاری' ),
					), 60 ),
				),
			),
			'enrollment' => array(
				'label'  => 'ثبت‌نام',
				'icon'   => 'dashicons-cart',
				'fields' => array(
					'eyebrow' => self::field( 'text', 'برچسب', 'ثبت‌نام دوره' ),
					'title' => self::field( 'text', 'عنوان', 'مسیر مناسب پرداخت را انتخاب کنید' ),
					'status' => self::field( 'select', 'وضعیت ثبت‌نام', 'open', array( 'options' => array( 'open' => 'ثبت‌نام باز', 'limited' => 'ظرفیت محدود', 'soon' => 'به‌زودی', 'closed' => 'پایان ثبت‌نام', 'reserve' => 'رزرو', 'soldout' => 'Sold Out' ) ) ),
					'cash_enabled' => self::field( 'switch', 'پرداخت نقدی', '1' ),
					'cash_cta' => self::field( 'text', 'متن CTA نقدی', 'ثبت‌نام و پرداخت' ),
					'cash_deadline' => self::field( 'datetime', 'مهلت ثبت‌نام نقدی' ),
					'installment_enabled' => self::field( 'switch', 'پرداخت اقساطی', '' ),
					'installment_downpayment' => self::field( 'text', 'پیش‌پرداخت', '', array( 'show_if' => 'installment_enabled:1' ) ),
					'installment_count' => self::field( 'number', 'تعداد اقساط', '', array( 'min' => 1, 'step' => 1, 'show_if' => 'installment_enabled:1' ) ),
					'installment_amount' => self::field( 'text', 'مبلغ هر قسط', '', array( 'show_if' => 'installment_enabled:1' ) ),
					'installment_interval' => self::field( 'text', 'فاصله اقساط', '', array( 'show_if' => 'installment_enabled:1' ) ),
					'installment_description' => self::field( 'textarea', 'توضیح اقساط', '', array( 'show_if' => 'installment_enabled:1' ) ),
					'installment_cta' => self::field( 'text', 'متن CTA اقساط', 'درخواست خرید اقساطی', array( 'show_if' => 'installment_enabled:1' ) ),
					'installment_url' => self::field( 'url', 'لینک اقساط', '', array( 'show_if' => 'installment_enabled:1' ) ),
					'pre_enabled' => self::field( 'switch', 'پیش‌ثبت‌نام', '' ),
					'pre_amount' => self::field( 'text', 'مبلغ پیش‌ثبت‌نام', '', array( 'show_if' => 'pre_enabled:1' ) ),
					'pre_description' => self::field( 'textarea', 'توضیح پیش‌ثبت‌نام', '', array( 'show_if' => 'pre_enabled:1' ) ),
					'pre_deadline' => self::field( 'datetime', 'مهلت پیش‌ثبت‌نام', '', array( 'show_if' => 'pre_enabled:1' ) ),
					'pre_cta' => self::field( 'text', 'متن CTA پیش‌ثبت‌نام', 'پیش‌ثبت‌نام', array( 'show_if' => 'pre_enabled:1' ) ),
					'pre_url' => self::field( 'url', 'لینک پیش‌ثبت‌نام', '', array( 'show_if' => 'pre_enabled:1' ) ),
					'group_enabled' => self::field( 'switch', 'خرید گروهی', '' ),
					'group_min' => self::field( 'number', 'حداقل نفر', '', array( 'min' => 2, 'step' => 1, 'show_if' => 'group_enabled:1' ) ),
					'group_price' => self::field( 'text', 'قیمت هر نفر', '', array( 'show_if' => 'group_enabled:1' ) ),
					'group_description' => self::field( 'textarea', 'توضیح خرید گروهی', '', array( 'show_if' => 'group_enabled:1' ) ),
					'group_cta' => self::field( 'text', 'متن CTA گروهی', 'درخواست خرید گروهی', array( 'show_if' => 'group_enabled:1' ) ),
					'group_url' => self::field( 'url', 'لینک خرید گروهی', '', array( 'show_if' => 'group_enabled:1' ) ),
				),
			),
			'guarantee' => array(
				'label'  => 'ضمانت',
				'icon'   => 'dashicons-shield-alt',
				'fields' => array(
					'enabled' => self::field( 'switch', 'نمایش سکشن ضمانت', '' ),
					'eyebrow' => self::field( 'text', 'برچسب', 'خرید بدون ریسک' ),
					'title' => self::field( 'text', 'عنوان' ),
					'description' => self::field( 'editor', 'توضیح' ),
					'image' => self::field( 'image', 'Icon / Illustration' ),
					'duration' => self::field( 'text', 'مدت ضمانت' ),
					'cta_text' => self::field( 'text', 'متن CTA اختیاری' ),
					'cta_url' => self::field( 'url', 'لینک CTA' ),
				),
			),
			'faq' => array(
				'label'  => 'سوالات متداول',
				'icon'   => 'dashicons-editor-help',
				'fields' => array(
					'eyebrow' => self::field( 'text', 'برچسب', 'پرسش‌های پرتکرار' ),
					'title' => self::field( 'text', 'عنوان', 'هنوز سوالی دارید؟' ),
					'items' => self::repeater( 'سوال‌ها', 'سوال', array(
						'question' => self::field( 'text', 'سوال' ),
						'answer' => self::field( 'editor', 'پاسخ' ),
					), 40 ),
				),
			),
		);
	}

	private static function field( $type, $label, $default = '', $extra = array() ) {
		return array_merge( array( 'type' => $type, 'label' => $label, 'default' => $default ), $extra );
	}

	private static function repeater( $label, $row_label, $fields, $max = 20 ) {
		return array( 'type' => 'repeater', 'label' => $label, 'row_label' => $row_label, 'fields' => $fields, 'max' => $max, 'default' => array() );
	}

	/** Get hydrated v2 data for a product. */
	public static function get( $post_id ) {
		$post_id = absint( $post_id );
		$saved   = get_post_meta( $post_id, self::META_KEY, true );
		$exists  = metadata_exists( 'post', $post_id, self::META_KEY );
		$saved   = is_array( $saved ) ? $saved : array();
		$preset  = self::preset( $post_id );

		if ( ! $exists ) {
			$legacy = self::legacy_fallback( $post_id );
			$saved = $legacy ? $legacy : $preset;
		} elseif ( $preset ) {
			$saved = $saved ? self::fill_missing_course_sections( $saved, $preset ) : $preset;
		}

		// نمونه‌های متنی نسخه ۲.۶.۸ هرگز نباید به‌عنوان نظر کاربر نمایش داده
		// یا دوباره ذخیره شوند. فقط دیدگاه واقعی یا آیتم دستیِ بدون این نشان می‌ماند.
		if ( ! empty( $saved['reviews']['items'] ) && is_array( $saved['reviews']['items'] ) ) {
			$saved['reviews']['items'] = array_values( array_filter( $saved['reviews']['items'], static function ( $item ) {
				return is_array( $item ) && empty( $item['is_sample'] );
			} ) );
		}

		$out = array();
		foreach ( self::tabs() as $tab_id => $tab ) {
			$tab_saved      = isset( $saved[ $tab_id ] ) && is_array( $saved[ $tab_id ] ) ? $saved[ $tab_id ] : array();
			$out[ $tab_id ] = self::hydrate( $tab['fields'], $tab_saved );
		}

		$out['curriculum']['groups'] = self::normalize_curriculum_groups( $out['curriculum']['groups'] );
		$out = self::apply_global_course_data( $out );
		$out = self::upgrade_customer_facing_copy( $out );

		return apply_filters( 'spp_v2_course_data', $out, $post_id, $exists );
	}

	/**
	 * Replace legacy placeholder/system copy without overwriting genuine custom
	 * writing. Exact matches and known generated patterns are upgraded at read
	 * time, so existing products benefit without a destructive database rewrite.
	 */
	private static function upgrade_customer_facing_copy( $data ) {
		$replacements = array(
			'هر مرحله با یک خروجی روشن طراحی شده تا فاصله میان یادگیری و اجرا کوتاه شود.' => 'مسیر دوره قدم‌به‌قدم چیده شده است تا در هر بخش بدانید چه می‌آموزید، چه تمرینی انجام می‌دهید و در پایان به چه توانایی‌ای می‌رسید.',
			'چرا این محصول برای شما ساخته شده؟' => 'چرا این آموزش می‌تواند برای شما مؤثر باشد؟',
			'درباره محصول' => 'درباره دوره',
			'این محصول برای چه کسانی مناسب است؟' => 'این آموزش برای چه کسانی مناسب است؟',
			'شیوه برگزاری یا دسترسی چگونه است؟' => 'دوره چگونه برگزار می‌شود؟',
			'خروجی اصلی این مسیر چیست؟' => 'بعد از پایان دوره چه توانایی‌هایی به دست می‌آورم؟',
			'آیا امکان استفاده تیمی وجود دارد؟' => 'آیا ثبت‌نام تیمی هم امکان‌پذیر است؟',
			'دسترسی و پشتیبانی تا چه زمانی ادامه دارد؟' => 'تا چه زمانی به دوره و پشتیبانی دسترسی دارم؟',
			'این محصول برای استفاده فردی طراحی شده است؛ شرایط استفاده بیشتر از یک نفر را پیش از خرید با پشتیبانی بررسی کنید.' => 'ثبت‌نام این دوره برای یک نفر است. برای خرید تیمی، مشاوران سازان بهترین گزینه را به شما پیشنهاد می‌دهند.',
			'مدت دسترسی و شرایط دریافت فایل مطابق مشخصات محصول و قوانین حساب کاربری شماست.' => 'مدت دسترسی و پشتیبانی پیش از خرید به‌روشنی اعلام می‌شود تا با خیال راحت برنامه‌ریزی کنید.',
			'بله؛ برای ثبت‌نام تیمی یا سازمانی، درخواست خود را با واحد ثبت‌نام سازان هماهنگ کنید.' => 'بله؛ برای انتخاب بسته مناسب تیم یا سازمانتان با مشاوران سازان تماس بگیرید.',
			'زمان‌بندی جلسات، پشتیبانی و دسترسی به منابع مطابق برنامه اعلام‌شده برای دوره اجرا می‌شود.' => 'برنامه جلسات و مدت پشتیبانی پیش از شروع با شما هماهنگ می‌شود تا مسیر را بدون ابهام دنبال کنید.',
			'پس از تکمیل بخش‌های الزامی و احراز شرایط ارزیابی، گواهینامه پایان دوره سازان مطابق ضوابط دوره صادر می‌شود.' => 'پس از تکمیل تمرین‌های ضروری و موفقیت در ارزیابی پایانی، گواهینامه پایان دوره سازان را دریافت می‌کنید.',
		);

		$walk = function ( &$value ) use ( &$walk, $replacements ) {
			if ( is_array( $value ) ) {
				foreach ( $value as &$item ) {
					$walk( $item );
				}
				unset( $item );
				return;
			}
			if ( ! is_string( $value ) ) {
				return;
			}
			if ( isset( $replacements[ $value ] ) ) {
				$value = $replacements[ $value ];
				return;
			}
			if ( preg_match( '/^این محصول به‌صورت (.+) ارائه می‌شود\. جزئیات زمان‌بندی یا دسترسی نهایی در فرایند ثبت‌نام و حساب کاربری نمایش داده می‌شود\.$/u', $value, $matches ) ) {
				$value = 'این آموزش به‌صورت ' . $matches[1] . ' ارائه می‌شود. پس از ثبت‌نام، زمان شروع و روش دسترسی در اختیارتان قرار می‌گیرد.';
			}
		};
		$walk( $data );

		return $data;
	}

	/** Load the built-in preset for a known WooCommerce product. */
	private static function preset( $post_id ) {
		static $catalog = null;
		if ( null === $catalog ) {
			$path = defined( 'SPP_PATH' ) ? SPP_PATH . 'data/course-presets.json' : '';
			$json = $path && is_readable( $path ) ? file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$decoded = $json ? json_decode( $json, true ) : array();
			$catalog = is_array( $decoded ) ? $decoded : array();
		}
		$key = (string) absint( $post_id );
		return isset( $catalog[ $key ] ) && is_array( $catalog[ $key ] ) ? $catalog[ $key ] : array();
	}

	/**
	 * Preserve product edits while restoring only sections that older imports
	 * left empty. This avoids forcing users to re-import serialized PHP data.
	 */
	private static function fill_missing_course_sections( $saved, $preset ) {
		$saved = self::fill_missing_keys( $saved, $preset );
		$paths = array(
			array( 'facts', 'items' ),
			array( 'benefits', 'items' ),
			array( 'about', 'before_items' ),
			array( 'about', 'after_items' ),
			array( 'curriculum', 'groups' ),
			array( 'reviews', 'items' ),
			array( 'faq', 'items' ),
		);
		foreach ( $paths as $path ) {
			$tab = $path[0];
			$key = $path[1];
			if ( empty( $saved[ $tab ][ $key ] ) && ! empty( $preset[ $tab ][ $key ] ) ) {
				if ( ! isset( $saved[ $tab ] ) || ! is_array( $saved[ $tab ] ) ) {
					$saved[ $tab ] = array();
				}
				$saved[ $tab ][ $key ] = $preset[ $tab ][ $key ];
			}
		}
		foreach ( array( 'before_title', 'after_title' ) as $key ) {
			if ( empty( $saved['about'][ $key ] ) && ! empty( $preset['about'][ $key ] ) ) {
				$saved['about'][ $key ] = $preset['about'][ $key ];
			}
		}
		return $saved;
	}

	/** Recursively add keys that an older saved payload did not know about. */
	private static function fill_missing_keys( $saved, $preset ) {
		foreach ( $preset as $key => $value ) {
			if ( ! array_key_exists( $key, $saved ) ) {
				$saved[ $key ] = $value;
			} elseif ( is_array( $value ) && is_array( $saved[ $key ] ) && array_keys( $value ) !== array_keys( array_values( $value ) ) ) {
				$saved[ $key ] = self::fill_missing_keys( $saved[ $key ], $value );
			}
		}
		return $saved;
	}

	/**
	 * Normalize curriculum rows created by early 2.6.x imports where the stage
	 * output was stored in course_count. This is a read-time compatibility layer
	 * and never rewrites the original product meta.
	 */
	private static function normalize_curriculum_groups( $groups ) {
		$groups = is_array( $groups ) ? $groups : array();
		foreach ( $groups as &$group ) {
			$chapters = isset( $group['chapters'] ) && is_array( $group['chapters'] ) ? $group['chapters'] : array();
			$count = trim( (string) ( $group['course_count'] ?? '' ) );
			$output = trim( (string) ( $group['output'] ?? '' ) );
			$is_count = '' !== $count && preg_match( '/[0-9۰-۹]|فصل|جلسه|دوره|بخش/u', $count );

			if ( '' === $output && '' !== $count && ! $is_count ) {
				$group['output'] = $count;
				$group['course_count'] = count( $chapters ) . ' فصل';
			} else {
				if ( '' === $output ) {
					$group['output'] = trim( (string) ( $group['description'] ?? $group['title'] ?? '' ) );
				}
				if ( '' === $count ) {
					$group['course_count'] = count( $chapters ) . ' فصل';
				}
			}
		}
		unset( $group );
		return $groups;
	}

	/**
	 * Apply the instructor and certificate that the site owner configured once
	 * for every course. Empty global fields never erase product-specific data.
	 */
	private static function apply_global_course_data( $data ) {
		if ( ! class_exists( 'SPP_Settings' ) ) {
			return $data;
		}

		$global = SPP_Settings::data();
		if ( '1' === ( $global['global_instructor_enabled'] ?? '' ) ) {
			$keys = array( 'name', 'image', 'role', 'short_description', 'bio', 'linkedin', 'website', 'experience', 'projects', 'teaching_hours' );
			$has_global_instructor = false;
			foreach ( $keys as $key ) {
				$global_key = 'inst_' . $key;
				if ( '' !== trim( (string) ( $global[ $global_key ] ?? '' ) ) ) {
					$has_global_instructor = true;
					break;
				}
			}

			if ( $has_global_instructor ) {
				$local = ! empty( $data['instructors']['items'][0] ) && is_array( $data['instructors']['items'][0] ) ? $data['instructors']['items'][0] : array();
				foreach ( $keys as $key ) {
					$global_key = 'inst_' . $key;
					if ( '' !== trim( (string) ( $global[ $global_key ] ?? '' ) ) ) {
						$local[ $key ] = (string) $global[ $global_key ];
					}
				}
				$local['company_logos'] = isset( $local['company_logos'] ) && is_array( $local['company_logos'] ) ? $local['company_logos'] : array();
				$data['instructors']['items'] = array( $local );
			}
		}

		$global_certificate_image = trim( (string) ( $global['certificate_image'] ?? '' ) );
		if ( '1' === ( $global['certificate_enabled_all'] ?? '' ) || '' !== $global_certificate_image ) {
			$data['certificate']['enabled'] = '1';
		}
		foreach ( array( 'image', 'badge', 'title', 'description', 'conditions' ) as $key ) {
			$global_key = 'certificate_' . $key;
			if ( '' !== trim( (string) ( $global[ $global_key ] ?? '' ) ) ) {
				$data['certificate'][ $key ] = (string) $global[ $global_key ];
			}
		}

		return $data;
	}

	private static function hydrate( $fields, $saved ) {
		$out = array();
		foreach ( $fields as $key => $def ) {
			$has = array_key_exists( $key, $saved );
			if ( 'repeater' === $def['type'] ) {
				$rows = $has && is_array( $saved[ $key ] ) ? array_values( $saved[ $key ] ) : (array) $def['default'];
				$out[ $key ] = array();
				foreach ( $rows as $row ) {
					if ( is_array( $row ) ) {
						$out[ $key ][] = self::hydrate( $def['fields'], $row );
					}
				}
			} else {
				$out[ $key ] = $has && ! is_array( $saved[ $key ] ) ? (string) $saved[ $key ] : (string) $def['default'];
			}
		}
		return $out;
	}

	/** Sanitize the full posted payload against the schema. */
	public static function sanitize( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		$out = array();
		foreach ( self::tabs() as $tab_id => $tab ) {
			$values         = isset( $raw[ $tab_id ] ) && is_array( $raw[ $tab_id ] ) ? $raw[ $tab_id ] : array();
			$out[ $tab_id ] = self::sanitize_fields( $values, $tab['fields'] );
		}
		return $out;
	}

	private static function sanitize_fields( $raw, $fields ) {
		$clean = array();
		foreach ( $fields as $key => $def ) {
			$type = $def['type'];
			if ( 'repeater' === $type ) {
				$rows = isset( $raw[ $key ] ) && is_array( $raw[ $key ] ) ? array_slice( $raw[ $key ], 0, (int) $def['max'] ) : array();
				$clean[ $key ] = array();
				foreach ( $rows as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$item = self::sanitize_fields( $row, $def['fields'] );
					if ( self::row_has_content( $item, $def['fields'] ) ) {
						$clean[ $key ][] = $item;
					}
				}
				continue;
			}

			$value = isset( $raw[ $key ] ) && ! is_array( $raw[ $key ] ) ? wp_unslash( $raw[ $key ] ) : '';
			switch ( $type ) {
				case 'editor':
					$clean[ $key ] = wp_kses_post( $value );
					break;
				case 'textarea':
					$clean[ $key ] = sanitize_textarea_field( $value );
					break;
				case 'url':
					$clean[ $key ] = self::sanitize_course_url( $value );
					break;
				case 'image':
				case 'video':
					$clean[ $key ] = (string) absint( $value );
					break;
				case 'number':
					$clean[ $key ] = '' === $value ? '' : (string) floatval( $value );
					break;
				case 'switch':
					$clean[ $key ] = $value ? '1' : '';
					break;
				case 'select':
					$options = array_keys( isset( $def['options'] ) ? $def['options'] : array() );
					$clean[ $key ] = in_array( $value, $options, true ) ? $value : (string) $def['default'];
					break;
				case 'icon':
					$clean[ $key ] = array_key_exists( $value, spp_icon_list() ) ? $value : (string) $def['default'];
					break;
				case 'datetime':
					$clean[ $key ] = preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value ) ? $value : '';
					break;
				default:
					$clean[ $key ] = sanitize_text_field( $value );
			}
		}
		return $clean;
	}

	/** Accept full URLs and safe in-page anchors used by course CTAs. */
	private static function sanitize_course_url( $value ) {
		$value = trim( (string) $value );
		if ( preg_match( '/^#[A-Za-z][A-Za-z0-9_:.-]*$/', $value ) ) {
			return $value;
		}
		return esc_url_raw( $value );
	}

	private static function row_has_content( $row, $fields ) {
		foreach ( $fields as $key => $def ) {
			$value = isset( $row[ $key ] ) ? $row[ $key ] : '';
			if ( 'repeater' === $def['type'] && ! empty( $value ) ) {
				return true;
			}
			if ( in_array( $def['type'], array( 'switch', 'select', 'icon' ), true ) ) {
				continue;
			}
			if ( '' !== trim( (string) $value ) && '0' !== (string) $value ) {
				return true;
			}
		}
		return false;
	}

	/** Map the most useful legacy fields without modifying legacy metadata. */
	private static function legacy_fallback( $post_id ) {
		if ( ! function_exists( 'spp_get_section_data' ) ) {
			return array();
		}

		// پیش‌فرض‌های نسل قبل فقط وقتی legacy meta واقعاً وجود دارد map می‌شوند؛
		// محصول تازه نباید با داده‌های نمونه پر شود.
		$hero = metadata_exists( 'post', $post_id, spp_meta_key( 'hero' ) ) ? spp_get_section_data( $post_id, 'hero' ) : array();
		$about = metadata_exists( 'post', $post_id, spp_meta_key( 'about' ) ) ? spp_get_section_data( $post_id, 'about' ) : array();
		$curr = metadata_exists( 'post', $post_id, spp_meta_key( 'curriculum' ) ) ? spp_get_section_data( $post_id, 'curriculum' ) : array();
		$inst = metadata_exists( 'post', $post_id, spp_meta_key( 'instructor' ) ) ? spp_get_section_data( $post_id, 'instructor' ) : array();
		$testimonials = metadata_exists( 'post', $post_id, spp_meta_key( 'testimonials' ) ) ? spp_get_section_data( $post_id, 'testimonials' ) : array();
		$faq = metadata_exists( 'post', $post_id, spp_meta_key( 'faq' ) ) ? spp_get_section_data( $post_id, 'faq' ) : array();
		$legacy_facts = array();
		foreach ( isset( $hero['meta_items'] ) ? (array) $hero['meta_items'] : array() as $item ) {
			$legacy_facts[] = array( 'icon' => isset( $item['icon'] ) ? $item['icon'] : 'check', 'title' => isset( $item['label'] ) ? $item['label'] : '', 'value' => isset( $item['value'] ) ? $item['value'] : '' );
		}
		$legacy_benefits = array();
		foreach ( isset( $curr['feat_items'] ) ? (array) $curr['feat_items'] : array() as $item ) {
			$legacy_benefits[] = array( 'icon' => isset( $item['icon'] ) ? $item['icon'] : 'check', 'title' => isset( $item['title'] ) ? $item['title'] : '', 'description' => isset( $item['desc'] ) ? $item['desc'] : '' );
		}
		$legacy_faq = array();
		foreach ( isset( $faq['items'] ) ? (array) $faq['items'] : array() as $item ) {
			$legacy_faq[] = array( 'question' => isset( $item['q'] ) ? $item['q'] : '', 'answer' => isset( $item['a'] ) ? $item['a'] : '' );
		}

		$legacy_chapters = array();
		foreach ( isset( $curr['syl_items'] ) ? (array) $curr['syl_items'] : array() as $item ) {
			$legacy_chapters[] = array(
				'title' => isset( $item['title'] ) ? $item['title'] : '',
				'duration' => isset( $item['sessions'] ) ? $item['sessions'] : '',
				'description' => isset( $item['content'] ) ? $item['content'] : '',
			);
		}

		$instructors = array();
		if ( ! empty( $inst['name'] ) ) {
			$instructors[] = array(
				'name' => $inst['name'],
				'role' => isset( $inst['role'] ) ? $inst['role'] : '',
				'image' => function_exists( 'spp_image_id' ) ? (string) spp_image_id( isset( $inst['image'] ) ? $inst['image'] : 0, 'inst_image' ) : ( isset( $inst['image'] ) ? $inst['image'] : '' ),
				'experience' => isset( $inst['stats'][2]['value'] ) ? $inst['stats'][2]['value'] : '',
				'projects' => isset( $inst['stats'][1]['value'] ) ? $inst['stats'][1]['value'] : '',
				'teaching_hours' => isset( $inst['stats'][0]['value'] ) ? $inst['stats'][0]['value'] : '',
			);
		}

		$manual_reviews = array();
		foreach ( isset( $testimonials['items'] ) ? (array) $testimonials['items'] : array() as $item ) {
			$manual_reviews[] = array(
				'avatar' => isset( $item['avatar'] ) ? $item['avatar'] : '',
				'name' => isset( $item['name'] ) ? $item['name'] : '',
				'job' => isset( $item['role'] ) ? $item['role'] : '',
				'text' => isset( $item['text'] ) ? $item['text'] : '',
				'rating' => isset( $item['rating'] ) ? $item['rating'] : '5',
			);
		}

		return array(
			'intro' => array(
				'badge' => isset( $hero['badge'] ) ? $hero['badge'] : '',
				'subtitle' => isset( $hero['subtitle'] ) ? $hero['subtitle'] : '',
				'hero_image' => isset( $hero['image'] ) ? $hero['image'] : '',
				'background_image' => isset( $hero['bg_image'] ) ? $hero['bg_image'] : '',
				'preview_video' => isset( $hero['preview_video'] ) ? $hero['preview_video'] : '',
				'primary_cta' => isset( $hero['cta_text'] ) ? $hero['cta_text'] : 'ثبت‌نام در دوره',
				'secondary_cta' => isset( $hero['preview_text'] ) ? $hero['preview_text'] : 'مشاهده پیش‌نمایش',
				'student_count' => isset( $hero['students_count'] ) ? $hero['students_count'] : '',
			),
			'facts' => array( 'items' => $legacy_facts ),
			'benefits' => array( 'items' => $legacy_benefits ),
			'about' => array(
				'title' => isset( $about['about_title'] ) ? $about['about_title'] : '',
				'description' => isset( $about['about_text'] ) ? wpautop( $about['about_text'] ) : '',
				'image' => isset( $about['image'] ) ? $about['image'] : '',
			),
			'curriculum' => $legacy_chapters ? array( 'groups' => array( array( 'title' => isset( $curr['syl_title'] ) ? $curr['syl_title'] : 'سرفصل‌های دوره', 'chapters' => $legacy_chapters ) ) ) : array(),
			'instructors' => array( 'items' => $instructors ),
			'reviews' => array( 'source' => isset( $testimonials['source'] ) ? $testimonials['source'] : 'both', 'items' => $manual_reviews ),
			'faq' => array( 'title' => isset( $faq['title'] ) ? $faq['title'] : '', 'items' => $legacy_faq ),
		);
	}
}
