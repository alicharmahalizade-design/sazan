<?php
/**
 * ویجت «اسلایدر دوره سازان» (sazan-product-slider).
 *
 * الهام‌گرفته از طرح Product Slider (سبک شناور/پوستری) ولی برای «دوره»:
 *   - اسلایدرِ فیدی؛ پوستر دوره/عکس مدرس که بین اسلایدها کراس‌فِید می‌شود.
 *   - واترمارک/لوگوی بزرگ در پس‌زمینه.
 *   - کارتِ دوره: عنوان، قیمت با تخفیف (قیمت قبلیِ خط‌خورده + قیمت جدید + بَجِ٪)،
 *     چیپ‌های مشخصات دوره (جلسه/ساعت/سطح/پشتیبانی — آیکن‌دار)، گیجِ دایره‌ایِ درصد
 *     (ظرفیت تکمیل‌شده/رضایت هنرجو)، نوارِ پیشرفتِ ظرفیت، امتیاز ستاره‌ای + تعداد هنرجو،
 *     بلوکِ مدرس، ربانِ گوشه، دکمه‌ی «ثبت‌نام دوره» و «دریافت سرفصل‌ها».
 *   - دکمه‌ی پخشِ تیزر روی پوستر → پاپ‌آپِ ویدیو (آپارات/یوتیوب/mp4).
 *   - پارالاکسِ پوستر با موس + Ken Burns.
 *   - اتصالِ خودکار به دوره‌های واقعیِ سایت (نوع‌پستِ دوره/ووکامرس).
 *
 * خودکفا: بدون Swiper/jQuery؛ جاوااسکریپتِ آن در sazan-sections.js (initProdSlider) است.
 * تمام رنگ‌ها با متغیرهای --sps-* کنترل می‌شوند.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Product_Slider extends Widget_Base {

	public function get_name() { return 'sazan-product-slider'; }
	public function get_title() { return esc_html__( 'اسلایدر دوره سازان (پوستری)', 'sazan-core' ); }
	public function get_icon() { return 'eicon-slider-push'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'course', 'دوره', 'slider', 'اسلایدر', 'آموزش', 'ثبت‌نام', 'card', 'product', 'محصول' ); }

	protected function register_controls() {

		/* ==================== محتوا: منبع داده ==================== */
		$this->start_controls_section( 'sec_source', array( 'label' => esc_html__( 'منبع داده', 'sazan-core' ) ) );
		$this->add_control( 'source', array(
			'label'   => esc_html__( 'منبع دوره‌ها', 'sazan-core' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'manual',
			'options' => array(
				'manual'  => esc_html__( 'دستی (اسلایدها را خودم می‌سازم)', 'sazan-core' ),
				'dynamic' => esc_html__( 'خودکار از دوره‌های واقعی سایت', 'sazan-core' ),
			),
		) );
		$this->add_control( 'source_pt', array(
			'label'       => esc_html__( 'نوع‌پستِ منبع', 'sazan-core' ),
			'type'        => Controls_Manager::SELECT,
			'default'     => $this->guess_course_post_type(),
			'options'     => $this->get_post_type_options(),
			'condition'   => array( 'source' => 'dynamic' ),
			'description' => esc_html__( 'اگر ووکامرس دارید «محصولات» و اگر نوع‌پستِ اختصاصیِ دوره دارید همان را انتخاب کنید.', 'sazan-core' ),
		) );
		$this->add_control( 'source_count', array(
			'label' => esc_html__( 'تعداد', 'sazan-core' ), 'type' => Controls_Manager::NUMBER,
			'default' => 6, 'min' => 1, 'max' => 24, 'condition' => array( 'source' => 'dynamic' ),
		) );
		$this->add_control( 'source_orderby', array(
			'label' => esc_html__( 'مرتب‌سازی بر اساس', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'date',
			'options' => array(
				'date'       => esc_html__( 'جدیدترین', 'sazan-core' ),
				'title'      => esc_html__( 'عنوان', 'sazan-core' ),
				'menu_order' => esc_html__( 'ترتیب دستی (menu_order)', 'sazan-core' ),
				'rand'       => esc_html__( 'تصادفی', 'sazan-core' ),
			),
			'condition' => array( 'source' => 'dynamic' ),
		) );
		$this->add_control( 'source_taxonomy', array(
			'label' => esc_html__( 'تاکسونومی (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::TEXT,
			'placeholder' => 'product_cat', 'condition' => array( 'source' => 'dynamic' ),
			'description' => esc_html__( 'برای فیلترِ دسته؛ مثلاً product_cat یا course_cat.', 'sazan-core' ),
		) );
		$this->add_control( 'source_terms', array(
			'label' => esc_html__( 'اسلاگِ دسته‌ها (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::TEXT,
			'placeholder' => 'modiriat, karafarini', 'condition' => array( 'source' => 'dynamic' ),
			'description' => esc_html__( 'با کاما جدا کنید. خالی = همه.', 'sazan-core' ),
		) );
		$this->add_control( 'source_percent_meta', array(
			'label' => esc_html__( 'کلیدِ متایِ درصدِ گیج (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::TEXT,
			'placeholder' => '_sazan_capacity', 'condition' => array( 'source' => 'dynamic' ),
			'description' => esc_html__( 'اگر ظرفیت/درصد را در متایِ دوره ذخیره می‌کنید، کلیدش را بنویسید.', 'sazan-core' ),
		) );
		$this->end_controls_section();

		/* ==================== محتوا: دوره‌ها (دستی) ==================== */
		$this->start_controls_section( 'sec_items', array(
			'label' => esc_html__( 'دوره‌ها (اسلایدها)', 'sazan-core' ),
			'condition' => array( 'source' => 'manual' ),
		) );

		$rep = new Repeater();
		$rep->add_control( 'image', array(
			'label'   => esc_html__( 'پوستر دوره / عکس مدرس (شناور)', 'sazan-core' ),
			'type'    => Controls_Manager::MEDIA,
			'default' => array( 'url' => \Elementor\Utils::get_placeholder_image_src() ),
			'description' => esc_html__( 'تصویر بزرگی که جلوی کارت شناور می‌شود (پوستر یا عکس مدرس؛ ترجیحاً PNG شفاف).', 'sazan-core' ),
		) );
		$rep->add_control( 'cover', array(
			'label'   => esc_html__( 'تصویر پس‌زمینه‌ی کارت', 'sazan-core' ),
			'type'    => Controls_Manager::MEDIA,
			'description' => esc_html__( 'پس‌زمینه‌ی داخل کارت (اختیاری). اگر خالی باشد گرادیانِ تیره نمایش داده می‌شود.', 'sazan-core' ),
		) );
		$rep->add_control( 'video_url', array(
			'label'   => esc_html__( '🎬 لینک تیزر (آپارات/یوتیوب/mp4)', 'sazan-core' ),
			'type'    => Controls_Manager::URL,
			'default' => array( 'url' => '' ),
			'placeholder' => 'https://www.aparat.com/v/XXXX',
			'description' => esc_html__( 'اگر پر باشد، دکمه‌ی پخش روی پوستر ظاهر می‌شود و پاپ‌آپِ ویدیو باز می‌کند.', 'sazan-core' ),
		) );
		$rep->add_control( 'title', array(
			'label'   => esc_html__( 'عنوان دوره', 'sazan-core' ),
			'type'    => Controls_Manager::TEXTAREA, 'rows' => 2,
			'default' => esc_html__( "دوره جامع\nحکمرانی بر بازار", 'sazan-core' ),
			'description' => esc_html__( 'هر خطِ تازه یک خطِ جدید در عنوان می‌شود.', 'sazan-core' ),
		) );

		/* ربانِ گوشه */
		$rep->add_control( 'ribbon_text', array( 'label' => esc_html__( '🏷️ ربانِ گوشه', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'پرطرفدار', 'sazan-core' ), 'placeholder' => esc_html__( 'مثلاً: جدید / ٪۳۰ تخفیف', 'sazan-core' ), 'separator' => 'before' ) );
		$rep->add_control( 'ribbon_style', array(
			'label' => esc_html__( 'رنگِ ربان', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'hot',
			'options' => array(
				'new' => esc_html__( 'جدید (سبز)', 'sazan-core' ),
				'off' => esc_html__( 'تخفیف (قرمز)', 'sazan-core' ),
				'hot' => esc_html__( 'پرطرفدار (برند)', 'sazan-core' ),
			),
		) );

		/* قیمت + تخفیف */
		$rep->add_control( 'price', array( 'label' => esc_html__( 'قیمت (فعلی/با تخفیف)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '۴٬۹۰۰٬۰۰۰', 'separator' => 'before' ) );
		$rep->add_control( 'price_old', array( 'label' => esc_html__( 'قیمتِ قبلی (خط‌خورده)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '۷٬۰۰۰٬۰۰۰', 'description' => esc_html__( 'اگر پر باشد خط‌خورده کنارِ قیمتِ جدید نمایش داده می‌شود.', 'sazan-core' ) ) );
		$rep->add_control( 'price_badge', array( 'label' => esc_html__( 'بَجِ تخفیف', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '٪۳۰', 'placeholder' => '٪۳۰' ) );
		$rep->add_control( 'price_pre', array( 'label' => esc_html__( 'پیشوند قیمت', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '', 'placeholder' => '$' ) );
		$rep->add_control( 'price_suf', array( 'label' => esc_html__( 'پسوند (بالانویس)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'تومان', 'sazan-core' ), 'description' => esc_html__( 'کوچک و بالا کنار قیمت می‌آید.', 'sazan-core' ) ) );

		/* چیپ‌های مشخصات دوره */
		$rep->add_control( 'chips', array(
			'label' => esc_html__( 'چیپ‌های مشخصات دوره', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 4,
			'default' => "[sessions] ۱۲ جلسه\n[hours] ۹۶ ساعت\n[level] سطح مقدماتی\n[support] پشتیبانی",
			'separator' => 'before',
			'description' => esc_html__( 'هر خط یک چیپ. برای آیکن، خط را با یکی از این توکن‌ها شروع کنید: [sessions] [hours] [level] [support] [online] [offline] [cert] [video] [calendar] [users].', 'sazan-core' ),
		) );

		/* گیجِ درصد */
		$rep->add_control( 'percent', array( 'label' => esc_html__( 'درصد گیج', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 100 ) ), 'default' => array( 'size' => 80 ), 'separator' => 'before' ) );
		$rep->add_control( 'percent_label', array( 'label' => esc_html__( 'برچسب گیج', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'رضایت هنرجو', 'sazan-core' ) ) );

		/* نوارِ ظرفیت */
		$rep->add_control( 'cap_percent', array( 'label' => esc_html__( '📊 درصدِ ظرفیتِ پرشده', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 100 ) ), 'default' => array( 'size' => 80 ), 'separator' => 'before' ) );
		$rep->add_control( 'cap_text', array( 'label' => esc_html__( 'متنِ ظرفیت', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( '۸۰٪ ظرفیت پر شد — ۵ صندلی مونده', 'sazan-core' ), 'description' => esc_html__( 'اگر درصدِ ظرفیت صفر باشد نوار پنهان می‌شود.', 'sazan-core' ) ) );

		/* امتیاز + هنرجو */
		$rep->add_control( 'rating', array( 'label' => esc_html__( '⭐ امتیاز (۰ تا ۵)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 5, 'step' => 0.1 ) ), 'default' => array( 'size' => 4.9 ), 'separator' => 'before' ) );
		$rep->add_control( 'students', array( 'label' => esc_html__( 'تعداد هنرجو', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( '+۲۰۰۰ هنرجو', 'sazan-core' ) ) );

		/* مدرس */
		$rep->add_control( 'tutor_img', array( 'label' => esc_html__( '👤 عکسِ مدرس', 'sazan-core' ), 'type' => Controls_Manager::MEDIA, 'separator' => 'before' ) );
		$rep->add_control( 'tutor_name', array( 'label' => esc_html__( 'نامِ مدرس', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'عباس شانه‌سازان', 'sazan-core' ) ) );
		$rep->add_control( 'tutor_role', array( 'label' => esc_html__( 'تخصصِ مدرس', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'مدرس مدیریت و بازاریابی', 'sazan-core' ) ) );

		/* دکمه‌ها */
		$rep->add_control( 'cart_text', array( 'label' => esc_html__( 'متن دکمه‌ی اصلی', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'ثبت‌نام دوره', 'sazan-core' ), 'separator' => 'before' ) );
		$rep->add_control( 'cart_link', array( 'label' => esc_html__( 'لینک دکمه‌ی اصلی', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$rep->add_control( 'fav_text', array( 'label' => esc_html__( 'متن دکمه‌ی دوم', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'دریافت سرفصل‌ها', 'sazan-core' ) ) );
		$rep->add_control( 'fav_link', array( 'label' => esc_html__( 'لینک دکمه‌ی دوم', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ), 'description' => esc_html__( 'مثلاً لینکِ دانلودِ سرفصل یا فرمِ مشاوره. خالی = فقط تاگلِ نشان‌کردن.', 'sazan-core' ) ) );
		$rep->add_control( 'fav_icon', array(
			'label' => esc_html__( 'آیکنِ دکمه‌ی دوم', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'doc',
			'options' => array(
				'doc'   => esc_html__( 'سرفصل/سند', 'sazan-core' ),
				'chat'  => esc_html__( 'مشاوره/گفتگو', 'sazan-core' ),
				'heart' => esc_html__( 'علاقه‌مندی (قلب)', 'sazan-core' ),
				'none'  => esc_html__( 'بدون آیکن', 'sazan-core' ),
			),
		) );

		$this->add_control( 'items', array(
			'label'       => esc_html__( 'دوره‌ها', 'sazan-core' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ title }}}',
			'default'     => array(
				array( 'title' => "دوره جامع\nحکمرانی بر بازار", 'price' => '۴٬۹۰۰٬۰۰۰', 'price_old' => '۷٬۰۰۰٬۰۰۰', 'price_badge' => '٪۳۰', 'ribbon_text' => 'پرطرفدار', 'ribbon_style' => 'hot', 'chips' => "[sessions] ۱۲ جلسه\n[hours] ۹۶ ساعت\n[level] سطح مقدماتی\n[support] پشتیبانی", 'percent' => array( 'size' => 92 ), 'percent_label' => 'رضایت هنرجو', 'cap_percent' => array( 'size' => 80 ), 'rating' => array( 'size' => 4.9 ), 'tutor_name' => 'عباس شانه‌سازان', 'tutor_role' => 'مدرس مدیریت و بازاریابی' ),
				array( 'title' => "دوره تخصصی\nمنابع انسانی", 'price' => '۳٬۲۰۰٬۰۰۰', 'price_old' => '', 'price_badge' => '', 'ribbon_text' => 'جدید', 'ribbon_style' => 'new', 'chips' => "[sessions] ۸ جلسه\n[hours] ۴۸ ساعت\n[online] آنلاین\n[cert] مدرک معتبر", 'percent' => array( 'size' => 85 ), 'percent_label' => 'ظرفیت تکمیل‌شده', 'cap_percent' => array( 'size' => 60 ), 'cap_text' => '۶۰٪ ظرفیت پر شد', 'rating' => array( 'size' => 4.7 ), 'students' => '+۹۰۰ هنرجو', 'tutor_name' => 'مریم رادفر', 'tutor_role' => 'مشاور منابع انسانی' ),
				array( 'title' => "دوره کاربردی\nکارآفرینی", 'price' => '۵٬۸۰۰٬۰۰۰', 'price_old' => '۶٬۵۰۰٬۰۰۰', 'price_badge' => '٪۱۰', 'ribbon_text' => '٪۱۰ تخفیف', 'ribbon_style' => 'off', 'chips' => "[sessions] ۱۶ جلسه\n[hours] ۱۲۰ ساعت\n[level] سطح پیشرفته\n[support] پشتیبانی VIP", 'percent' => array( 'size' => 78 ), 'percent_label' => 'رضایت هنرجو', 'cap_percent' => array( 'size' => 95 ), 'cap_text' => '۹۵٪ ظرفیت پر شد — ۲ صندلی مونده', 'rating' => array( 'size' => 5 ), 'students' => '+۱۵۰۰ هنرجو', 'tutor_name' => 'کاوه احمدی', 'tutor_role' => 'کارآفرین و سرمایه‌گذار' ),
			),
		) );
		$this->end_controls_section();

		/* ==================== محتوا: واترمارک و اسلایدر ==================== */
		$this->start_controls_section( 'sec_general', array( 'label' => esc_html__( 'صحنه، ناوبری و اسلایدر', 'sazan-core' ) ) );

		/* صحنه (بلوک رنگی) */
		$this->add_control( 'anim', array(
			'label' => esc_html__( '🎞️ حالتِ جابه‌جاییِ اسلاید', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'fade',
			'options' => array(
				'fade'  => esc_html__( 'محو (کراس‌فِید)', 'sazan-core' ),
				'slide' => esc_html__( 'سُر خوردن از کنار', 'sazan-core' ),
				'zoom'  => esc_html__( 'زوم (بزرگ‌شدن)', 'sazan-core' ),
				'rise'  => esc_html__( 'بالا آمدن از پایین', 'sazan-core' ),
				'flip'  => esc_html__( 'چرخشِ سه‌بعدی', 'sazan-core' ),
			),
		) );
		$this->add_control( 'stage_rays', array(
			'label' => esc_html__( '🌟 پرتوهای نور (god rays)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes', 'separator' => 'before',
			'description' => esc_html__( 'پرتوهای نور از بالای صحنه پشتِ سوژه.', 'sazan-core' ),
		) );
		$this->add_control( 'stage_bokeh', array(
			'label' => esc_html__( 'حباب‌های نرمِ نور (bokeh)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
		) );
		$this->add_control( 'stage_text', array(
			'label' => esc_html__( 'متنِ عمودیِ کنارِ صحنه', 'sazan-core' ), 'type' => Controls_Manager::TEXT,
			'default' => 'SAZAN ACADEMY', 'separator' => 'before',
			'description' => esc_html__( 'کم‌رنگ کنارِ لبه‌ی بلوکِ رنگی. خالی = بدون متن.', 'sazan-core' ),
		) );
		$this->add_control( 'watermark', array( 'label' => esc_html__( 'لوگو/واترمارک پس‌زمینه', 'sazan-core' ), 'type' => Controls_Manager::MEDIA, 'separator' => 'before', 'description' => esc_html__( 'در بلوکِ رنگیِ سمت، کج و کم‌رنگ نمایش داده می‌شود.', 'sazan-core' ) ) );

		$this->add_control( 'show_arrows', array( 'label' => esc_html__( 'نمایش فلش‌ها', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'separator' => 'before' ) );
		$this->add_control( 'loop', array( 'label' => esc_html__( 'حلقه‌ای (بی‌انتها)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );
		$this->add_control( 'autoplay', array( 'label' => esc_html__( 'پخش خودکار', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );
		$this->add_control( 'autoplay_ms', array(
			'label' => esc_html__( 'مدت هر اسلاید (ثانیه)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 's' ), 'range' => array( 's' => array( 'min' => 2, 'max' => 15, 'step' => 0.5 ) ),
			'default' => array( 'unit' => 's', 'size' => 6 ), 'condition' => array( 'autoplay' => 'yes' ),
		) );
		$this->add_control( 'parallax', array(
			'label' => esc_html__( '🖱️ پارالاکسِ پوستر با موس', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes', 'separator' => 'before',
			'description' => esc_html__( 'پوستر با حرکتِ موس کمی جابه‌جا می‌شود.', 'sazan-core' ),
		) );
		$this->add_control( 'kenburns', array(
			'label' => esc_html__( 'افکتِ Ken Burns (زومِ آرام)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
		) );
		$this->add_control( 'rtl', array(
			'label' => esc_html__( 'متن‌ها راست‌چین', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
			'description' => esc_html__( 'فقط متن‌ها راست‌چین می‌شوند؛ تصویر و بلوک رنگی سرِ جای اصلی (سمت چپ) می‌مانند.', 'sazan-core' ),
		) );
		$this->end_controls_section();

		/* ==================== محتوا: پوستر و صحنه (ابعاد و جابه‌جایی) ==================== */
		$this->start_controls_section( 'sec_poster', array( 'label' => esc_html__( 'پوستر و صحنه (ابعاد و جابه‌جایی)', 'sazan-core' ) ) );
		$this->add_control( 'shape_w', array(
			'label' => esc_html__( 'پهنای بلوکِ رنگی (صحنه) — دسکتاپ', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( '%' ), 'range' => array( '%' => array( 'min' => 25, 'max' => 60 ) ),
			'default' => array( 'unit' => '%', 'size' => 42 ),
			'selectors' => array( '{{WRAPPER}} .sps-content .sps-shape' => 'width: {{SIZE}}%;' ),
			'description' => esc_html__( 'کوچک‌ترش کنید تا پوستر و کارت بیشتر دیده شوند. (در موبایل خودکار تمام‌عرض می‌شود.)', 'sazan-core' ),
		) );
		$this->add_control( 'poster_size', array(
			'label' => esc_html__( '🔍 اندازه‌ی پوستر — دسکتاپ', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 280, 'max' => 720 ) ),
			'default' => array( 'unit' => 'px', 'size' => 500 ), 'separator' => 'before',
			'selectors' => array(
				'{{WRAPPER}} .sps-content .sps-float' => 'width: {{SIZE}}px;',
				'{{WRAPPER}} .sps-content .sps-float__item img' => 'max-height: {{SIZE}}px;',
			),
		) );
		$this->add_responsive_control( 'poster_x', array(
			'label' => esc_html__( '↔️ جابه‌جاییِ افقیِ پوستر', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => -400, 'max' => 400 ) ),
			'default' => array( 'unit' => 'px', 'size' => 0 ),
			'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-ox: {{SIZE}}px;' ),
		) );
		$this->add_responsive_control( 'poster_y', array(
			'label' => esc_html__( '↕️ جابه‌جاییِ عمودیِ پوستر', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => -300, 'max' => 300 ) ),
			'default' => array( 'unit' => 'px', 'size' => 0 ),
			'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-oy: {{SIZE}}px;' ),
		) );
		$this->add_control( 'hd_mobile', array( 'label' => esc_html__( '📱 موبایل', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'mobile_shape', array(
			'label' => esc_html__( 'نمایشِ بلوکِ آبیِ صحنه در موبایل', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'label_on' => esc_html__( 'روشن', 'sazan-core' ), 'label_off' => esc_html__( 'خاموش', 'sazan-core' ),
			'return_value' => 'yes', 'default' => 'yes',
		) );
		$this->add_control( 'mobile_poster', array(
			'label' => esc_html__( 'نمایشِ پوستر/عکس در موبایل', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'label_on' => esc_html__( 'روشن', 'sazan-core' ), 'label_off' => esc_html__( 'خاموش', 'sazan-core' ),
			'return_value' => 'yes', 'default' => 'yes',
			'description' => esc_html__( 'برای موبایل می‌توانید فقط کارت را نگه دارید (پوستر و بلوکِ آبی پنهان شوند).', 'sazan-core' ),
		) );
		$this->end_controls_section();

		/* ==================== محتوا: شمارش معکوس ==================== */
		$this->start_controls_section( 'sec_timer', array( 'label' => esc_html__( 'شمارش معکوس (تایمر ثبت‌نام)', 'sazan-core' ) ) );
		$this->add_control( 'show_timer', array( 'label' => esc_html__( 'نمایش تایمر', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '' ) );
		$this->add_control( 'timer_deadline', array(
			'label' => esc_html__( 'تاریخ و ساعت پایان', 'sazan-core' ), 'type' => Controls_Manager::DATE_TIME,
			'condition' => array( 'show_timer' => 'yes' ), 'description' => esc_html__( 'مطابق منطقه‌ی زمانی سایت. مثلاً پایانِ مهلتِ ثبت‌نام.', 'sazan-core' ),
		) );
		$this->add_control( 'timer_label', array( 'label' => esc_html__( 'برچسب تایمر', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'تا پایان ثبت‌نام', 'sazan-core' ), 'condition' => array( 'show_timer' => 'yes' ) ) );
		$this->add_control( 'timer_expired', array( 'label' => esc_html__( 'متن پس از پایان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'مهلت ثبت‌نام به پایان رسید', 'sazan-core' ), 'condition' => array( 'show_timer' => 'yes' ) ) );
		$this->add_control( 'timer_scope', array(
			'label' => esc_html__( 'نمایش تایمر روی', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'all',
			'options' => array( 'all' => esc_html__( 'همه‌ی دوره‌ها', 'sazan-core' ), 'first' => esc_html__( 'فقط دوره اول', 'sazan-core' ) ),
			'condition' => array( 'show_timer' => 'yes' ),
		) );
		$this->end_controls_section();

		/* ==================== استایل: رنگ‌ها ==================== */
		$this->start_controls_section( 'sty_colors', array( 'label' => esc_html__( 'رنگ‌ها', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'c_accent', array( 'label' => esc_html__( 'رنگ اصلی (برند)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00B6F1', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-accent: {{VALUE}};' ) ) );
		$this->add_control( 'c_accent2', array( 'label' => esc_html__( 'رنگ اصلی ۲ (گرادیان)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0090c4', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-accent2: {{VALUE}};' ) ) );
		$this->add_control( 'c_shape1', array( 'label' => esc_html__( 'رنگ بلوک پشت', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00B6F1', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-shape1: {{VALUE}};' ) ) );
		$this->add_control( 'c_shape2', array( 'label' => esc_html__( 'رنگ بلوک پشت (گرادیان)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0090c4', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-shape2: {{VALUE}};' ) ) );
		$this->add_control( 'c_bg1', array( 'label' => esc_html__( 'پس‌زمینه‌ی صحنه (بالا)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#12283a', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-bg1: {{VALUE}};' ) ) );
		$this->add_control( 'c_bg2', array( 'label' => esc_html__( 'پس‌زمینه‌ی صحنه (پایین)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0a161f', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-bg2: {{VALUE}};' ) ) );
		$this->add_control( 'c_card', array( 'label' => esc_html__( 'ته‌رنگِ کارت (روی عکس)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(9,20,30,0.42)', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-card: {{VALUE}};' ) ) );
		$this->add_control( 'c_text', array( 'label' => esc_html__( 'رنگ متن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-text: {{VALUE}};' ) ) );
		$this->add_control( 'c_mut', array( 'label' => esc_html__( 'رنگ متن کم‌رنگ', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#8b93a7', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-mut: {{VALUE}};' ) ) );

		$this->add_control( 'hd_arrows', array( 'label' => esc_html__( 'فلش‌های ناوبری', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'c_arrow_bg', array( 'label' => esc_html__( 'پس‌زمینه‌ی فلش', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-arrow-bg: {{VALUE}};' ) ) );
		$this->add_control( 'c_arrow_ic', array( 'label' => esc_html__( 'رنگ آیکنِ فلش', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#1a2530', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-arrow-ic: {{VALUE}};' ) ) );
		$this->add_control( 'c_arrow_hbg', array( 'label' => esc_html__( 'پس‌زمینه‌ی فلش (هاور)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-arrow-hbg: {{VALUE}};' ), 'description' => esc_html__( 'خالی = رنگ برند.', 'sazan-core' ) ) );
		$this->add_control( 'c_arrow_hic', array( 'label' => esc_html__( 'رنگ آیکنِ فلش (هاور)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-arrow-hic: {{VALUE}};' ) ) );
		$this->end_controls_section();

		/* ==================== استایل: افکت شیشه‌ای (گلس‌مورفیسم) ==================== */
		$this->start_controls_section( 'sty_glass', array( 'label' => esc_html__( '✨ افکت شیشه‌ای (گلس‌مورفیسم)', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'glass', array(
			'label' => esc_html__( 'کارت شیشه‌ای (بلر)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
			'description' => esc_html__( 'ته‌رنگِ کارت، چیپ‌ها و جعبه‌ها شفاف و مات (frosted glass) می‌شوند.', 'sazan-core' ),
		) );
		$this->add_control( 'glass_blur', array(
			'label' => esc_html__( 'شدتِ بلر', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default' => array( 'unit' => 'px', 'size' => 18 ), 'condition' => array( 'glass' => 'yes' ),
			'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-blur: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'glass_tint', array(
			'label' => esc_html__( 'ته‌رنگِ شیشه', 'sazan-core' ), 'type' => Controls_Manager::COLOR,
			'default' => 'rgba(13,25,35,0.35)', 'condition' => array( 'glass' => 'yes' ),
			'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-glass: {{VALUE}};' ),
			'description' => esc_html__( 'هرچه شفاف‌تر (آلفای کمتر)، شیشه‌ای‌تر؛ برای خواناییِ متن کمی تیره نگه دارید.', 'sazan-core' ),
		) );
		$this->add_control( 'glass_border', array(
			'label' => esc_html__( 'رنگِ لبه‌ی شیشه', 'sazan-core' ), 'type' => Controls_Manager::COLOR,
			'default' => 'rgba(255,255,255,0.22)', 'condition' => array( 'glass' => 'yes' ),
			'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-glass-bd: {{VALUE}};' ),
		) );
		$this->end_controls_section();

		/* ==================== استایل: ابعاد و تایپوگرافی ==================== */
		$this->start_controls_section( 'sty_size', array( 'label' => esc_html__( 'ابعاد و تایپوگرافی', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'height', array(
			'label' => esc_html__( 'ارتفاع صحنه', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px', 'vh' ), 'range' => array( 'px' => array( 'min' => 420, 'max' => 900 ), 'vh' => array( 'min' => 50, 'max' => 100 ) ),
			'default' => array( 'unit' => 'px', 'size' => 660 ), 'tablet_default' => array( 'unit' => 'px', 'size' => 560 ), 'mobile_default' => array( 'unit' => 'px', 'size' => 0 ),
			'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-h: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'radius', array(
			'label' => esc_html__( 'گردی گوشه', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 48 ) ),
			'default' => array( 'unit' => 'px', 'size' => 30 ),
			'selectors' => array( '{{WRAPPER}} .sazan-prodslider' => '--sps-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name' => 'tg_title', 'label' => esc_html__( 'تایپوگرافی عنوان', 'sazan-core' ),
			'selector' => '{{WRAPPER}} .sps-title',
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name' => 'tg_price', 'label' => esc_html__( 'تایپوگرافی قیمت', 'sazan-core' ),
			'selector' => '{{WRAPPER}} .sps-price',
		) );
		$this->end_controls_section();
	}

	/* ==================== کمکی‌های منبعِ داده ==================== */

	/** فهرستِ نوع‌پست‌های عمومی برای انتخابگر. */
	private function get_post_type_options() {
		$out = array();
		if ( ! function_exists( 'get_post_types' ) ) { return array( 'post' => 'post' ); }
		$pts = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $pts as $pt ) {
			if ( 'attachment' === $pt->name ) { continue; }
			$label = isset( $pt->labels->singular_name ) ? $pt->labels->singular_name : $pt->name;
			$out[ $pt->name ] = $label . ' (' . $pt->name . ')';
		}
		if ( empty( $out ) ) { $out['post'] = 'post'; }
		return $out;
	}

	/** بهترین حدس برای نوع‌پستِ دوره (course > lp_course > product > post). */
	private function guess_course_post_type() {
		if ( function_exists( 'post_type_exists' ) ) {
			foreach ( array( 'course', 'courses', 'lp_course', 'stm-courses', 'tutor_course', 'product' ) as $pt ) {
				if ( post_type_exists( $pt ) ) { return $pt; }
			}
		}
		return 'post';
	}

	/** ساختِ آرایه‌ی اسلایدها از پست‌های واقعیِ سایت (هم‌شکل با آیتم‌های ریپیتر). */
	private function build_items_from_query( $s ) {
		$items = array();
		if ( ! function_exists( 'get_posts' ) ) { return $items; }

		$pt   = ! empty( $s['source_pt'] ) ? $s['source_pt'] : 'post';
		$args = array(
			'post_type'      => $pt,
			'posts_per_page' => ! empty( $s['source_count'] ) ? (int) $s['source_count'] : 6,
			'post_status'    => 'publish',
			'orderby'        => ! empty( $s['source_orderby'] ) ? $s['source_orderby'] : 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		);
		if ( 'title' === $args['orderby'] ) { $args['order'] = 'ASC'; }

		$tax   = trim( (string) ( $s['source_taxonomy'] ?? '' ) );
		$terms = trim( (string) ( $s['source_terms'] ?? '' ) );
		if ( '' !== $tax && '' !== $terms ) {
			$slugs = array_filter( array_map( 'trim', explode( ',', $terms ) ) );
			if ( $slugs ) {
				$args['tax_query'] = array( array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					'taxonomy' => $tax,
					'field'    => 'slug',
					'terms'    => $slugs,
				) );
			}
		}

		$posts = get_posts( $args );
		$pmeta = trim( (string) ( $s['source_percent_meta'] ?? '' ) );

		foreach ( $posts as $p ) {
			$id  = $p->ID;
			$img = function_exists( 'get_the_post_thumbnail_url' ) ? get_the_post_thumbnail_url( $id, 'large' ) : '';
			$it  = array(
				'image'       => array( 'url' => $img ? $img : '' ),
				'cover'       => array( 'url' => $img ? $img : '' ),
				'title'       => get_the_title( $id ),
				'cart_text'   => esc_html__( 'ثبت‌نام دوره', 'sazan-core' ),
				'cart_link'   => array( 'url' => get_permalink( $id ) ),
				'fav_text'    => esc_html__( 'دریافت سرفصل‌ها', 'sazan-core' ),
				'fav_icon'    => 'doc',
				'percent'     => array( 'size' => 0 ),
				'cap_percent' => array( 'size' => 0 ),
				'rating'      => array( 'size' => 0 ),
			);

			/* درصدِ گیج از متا (اختیاری) */
			if ( '' !== $pmeta ) {
				$pv = get_post_meta( $id, $pmeta, true );
				if ( '' !== $pv && is_numeric( $pv ) ) {
					$cap = max( 0, min( 100, (int) $pv ) );
					$it['percent']       = array( 'size' => $cap );
					$it['cap_percent']   = array( 'size' => $cap );
					$it['percent_label'] = esc_html__( 'ظرفیت تکمیل‌شده', 'sazan-core' );
				}
			}

			/* قیمتِ ووکامرس (اگر محصول باشد) */
			$it = $this->maybe_fill_woo_price( $it, $id );

			$items[] = $it;
		}
		return $items;
	}

	/** پرکردنِ قیمت/تخفیف از ووکامرس اگر پست، محصولِ ووکامرس باشد. */
	private function maybe_fill_woo_price( $it, $id ) {
		if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'wc_get_price_to_display' ) ) { return $it; }
		$product = wc_get_product( $id );
		if ( ! $product ) { return $it; }

		$strip = function ( $html ) { return trim( wp_strip_all_tags( (string) $html ) ); };

		if ( $product->is_on_sale() && '' !== $product->get_regular_price() && '' !== $product->get_sale_price() ) {
			$reg  = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );
			$sale = (float) wc_get_price_to_display( $product, array( 'price' => $product->get_sale_price() ) );
			$it['price']     = $strip( wc_price( $sale ) );
			$it['price_old'] = $strip( wc_price( $reg ) );
			if ( $reg > 0 && $sale < $reg ) {
				$pct = (int) round( ( 1 - $sale / $reg ) * 100 );
				$it['price_badge'] = $this->fa_digits( '٪' . $pct );
			}
		} else {
			$it['price'] = $strip( $product->get_price_html() );
		}
		$it['price_suf'] = '';
		return $it;
	}

	/** تبدیلِ ارقامِ لاتین به فارسی. */
	private function fa_digits( $str ) {
		$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		return str_replace( $en, $fa, (string) $str );
	}

	/* ==================== کمکی‌های رندر ==================== */

	/** آیکنِ SVG برای توکنِ چیپ. */
	private function chip_icon( $token ) {
		$p = ''; // مسیرِ داخلِ svg
		switch ( $token ) {
			case 'sessions': $p = '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>'; break; // تقویم/جلسه
			case 'hours':    $p = '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'; break; // ساعت
			case 'level':    $p = '<path d="M4 20V10M10 20V4M16 20v-8M22 20V7"/>'; break; // نمودارِ سطح
			case 'support':  $p = '<path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-6H6a2 2 0 0 0-2 2zM20 13v3a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2z"/>'; break; // هدست پشتیبانی
			case 'online':   $p = '<rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>'; break; // نمایشگر
			case 'offline':  $p = '<path d="M3 21h18M6 21V8l6-4 6 4v13"/><path d="M9 21v-6h6v6"/>'; break; // ساختمان
			case 'cert':     $p = '<circle cx="12" cy="9" r="5"/><path d="M9 13l-1 8 4-2 4 2-1-8"/>'; break; // مدال/مدرک
			case 'video':    $p = '<rect x="2" y="5" width="14" height="14" rx="2"/><path d="M16 10l6-3v10l-6-3z"/>'; break; // ویدیو
			case 'calendar': $p = '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>'; break;
			case 'users':    $p = '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 5a3 3 0 0 1 0 6M21 20a6 6 0 0 0-4-5.6"/>'; break; // هنرجویان
			default: return '';
		}
		return '<svg class="sps-chip__ic" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
	}

	/** رندرِ چیپ‌های مشخصات دوره از متن (هر خط یک چیپ؛ [token] برای آیکن). */
	private function render_chips( $raw ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
		$chips = array();
		foreach ( $lines as $ln ) {
			$ln = trim( $ln );
			if ( '' === $ln ) { continue; }
			$icon = '';
			if ( preg_match( '/^\[([a-z_]+)\]\s*(.*)$/i', $ln, $m ) ) {
				$icon = $this->chip_icon( strtolower( $m[1] ) );
				$ln   = trim( $m[2] );
			}
			if ( '' === $ln ) { continue; }
			$chips[] = '<span class="sps-chip">' . $icon . '<span class="sps-chip__t">' . esc_html( $ln ) . '</span></span>';
		}
		if ( ! $chips ) { return; }
		echo '<div class="sps-chips">' . implode( '', $chips ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/** ستاره‌های امتیاز. */
	private function render_rating( $rating, $students ) {
		$rating = max( 0, min( 5, (float) $rating ) );
		if ( $rating <= 0 && '' === trim( (string) $students ) ) { return; }
		$pct  = round( $rating / 5 * 100, 2 );
		$star = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M12 2l3 6.5 7 .9-5 4.8 1.3 7-6.3-3.4L5.7 21l1.3-7-5-4.8 7-.9z"/></svg>';
		echo '<div class="sps-rating">';
		if ( $rating > 0 ) {
			echo '<span class="sps-stars" style="--sps-rate:' . esc_attr( $pct ) . '%">';
			echo '<span class="sps-stars__bg">' . str_repeat( $star, 5 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<span class="sps-stars__fg">' . str_repeat( $star, 5 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</span>';
			echo '<b class="sps-rating__num">' . esc_html( $this->fa_digits( number_format( $rating, 1 ) ) ) . '</b>';
		}
		if ( '' !== trim( (string) $students ) ) {
			echo '<span class="sps-rating__students">' . esc_html( $students ) . '</span>';
		}
		echo '</div>';
	}

	/** آیکنِ دکمه‌ی دوم. */
	private function fav_icon_svg( $which ) {
		switch ( $which ) {
			case 'chat':  return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7A8.38 8.38 0 0 1 4 11.5 8.5 8.5 0 0 1 12.5 3 8.38 8.38 0 0 1 21 11.5z"/></svg>';
			case 'heart': return '<svg viewBox="0 0 24 24" width="18" height="18"><path d="M12 21s-7.5-4.6-10-9.2C.4 8.5 2 5 5.3 5c1.9 0 3.2 1.1 3.9 2.2C9.9 6.1 11.2 5 13.1 5 16.4 5 18 8.5 16.4 11.8 13.9 16.4 12 21 12 21z"/></svg>';
			case 'none':  return '';
			case 'doc':
			default:      return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M9 13h6M9 17h6"/></svg>';
		}
	}

	/** بلوک شمارش معکوس (مقدار اولیه صفر؛ JS هر ثانیه به‌روزرسانی می‌کند). */
	private function render_timer( $s ) {
		$deadline = str_replace( ' ', 'T', (string) $s['timer_deadline'] );
		$units = array(
			'd' => esc_html__( 'روز', 'sazan-core' ),
			'h' => esc_html__( 'ساعت', 'sazan-core' ),
			'm' => esc_html__( 'دقیقه', 'sazan-core' ),
			's' => esc_html__( 'ثانیه', 'sazan-core' ),
		);
		$out  = '<div class="sps-countdown" data-deadline="' . esc_attr( $deadline ) . '">';
		if ( ! empty( $s['timer_label'] ) ) {
			$out .= '<span class="sps-countdown__lbl">' . esc_html( $s['timer_label'] ) . '</span>';
		}
		$out .= '<div class="sps-countdown__boxes">';
		foreach ( $units as $u => $lbl ) {
			$out .= '<div class="sps-cd-box' . ( 's' === $u ? ' is-accent' : '' ) . '"><b data-u="' . esc_attr( $u ) . '">۰۰</b><i>' . esc_html( $lbl ) . '</i></div>';
		}
		$out .= '</div>';
		$out .= '<span class="sps-countdown__done">' . esc_html( $s['timer_expired'] ?? '' ) . '</span>';
		$out .= '</div>';
		return $out;
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		if ( 'dynamic' === ( $s['source'] ?? 'manual' ) ) {
			$items = $this->build_items_from_query( $s );
		} else {
			$items = array_values( (array) ( $s['items'] ?? array() ) );
		}
		if ( empty( $items ) ) {
			if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="sps-empty">' . esc_html__( 'هیچ دوره‌ای برای نمایش پیدا نشد. منبع/نوع‌پست را بررسی کنید.', 'sazan-core' ) . '</div>';
			}
			return;
		}

		$auto = ( 'yes' === $s['autoplay'] ) ? '1' : '0';
		$ms   = ! empty( $s['autoplay_ms']['size'] ) ? (float) $s['autoplay_ms']['size'] : 6;
		$ms   = max( 2, $ms ) * 1000;
		$loop = ( 'yes' === $s['loop'] ) ? '1' : '0';
		$rtl  = ( 'yes' === $s['rtl'] ) ? ' sps-rtl' : '';
		$plx  = ( 'yes' === ( $s['parallax'] ?? '' ) ) ? '1' : '0';
		$kb   = ( 'yes' === ( $s['kenburns'] ?? '' ) ) ? ' is-kb' : '';
		$gls  = ( 'yes' === ( $s['glass'] ?? 'yes' ) ) ? ' is-glass' : '';
		$bok  = ( 'yes' === ( $s['stage_bokeh'] ?? 'yes' ) ) ? ' has-bokeh' : '';
		$anim = $s['anim'] ?? 'fade';
		if ( ! in_array( $anim, array( 'fade', 'slide', 'zoom', 'rise', 'flip' ), true ) ) { $anim = 'fade'; }
		$anim = ' anim-' . $anim;
		if ( 'yes' !== ( $s['mobile_shape'] ?? 'yes' ) )  { $anim .= ' hide-shape-mob'; }
		if ( 'yes' !== ( $s['mobile_poster'] ?? 'yes' ) ) { $anim .= ' hide-poster-mob'; }

		$timer_on = ( 'yes' === $s['show_timer'] ) && ! empty( $s['timer_deadline'] );

		echo '<div class="sazan-prodslider' . esc_attr( $rtl . $kb . $gls . $bok . $anim ) . '" data-autoplay="' . esc_attr( $auto ) . '" data-speed="' . esc_attr( (int) $ms ) . '" data-loop="' . esc_attr( $loop ) . '" data-parallax="' . esc_attr( $plx ) . '">';
		echo '<div class="sps-wrap"><div class="sps-content">';

		/* بلوک رنگی «صحنه»: پرتوهای نور + واترمارک + متنِ عمودی */
		echo '<div class="sps-shape">';
		if ( 'yes' === ( $s['stage_rays'] ?? 'yes' ) ) {
			echo '<span class="sps-rays" aria-hidden="true"></span>';
		}
		if ( ! empty( $s['watermark']['url'] ) ) {
			echo '<img src="' . esc_url( $s['watermark']['url'] ) . '" alt="" loading="lazy">';
		}
		$stext = trim( (string) ( $s['stage_text'] ?? '' ) );
		if ( '' !== $stext ) {
			echo '<span class="sps-vtext" aria-hidden="true">' . esc_html( $stext ) . '</span>';
		}
		echo '</div>';

		/* تصاویر شناور (کراس‌فِید) + دکمه‌ی پخشِ تیزر */
		echo '<div class="sps-float">';
		foreach ( $items as $i => $it ) {
			$url = $it['image']['url'] ?? '';
			if ( ! $url ) { continue; }
			echo '<div class="sps-float__item' . ( 0 === $i ? ' is-active' : '' ) . '" data-i="' . esc_attr( $i ) . '">';
			echo '<img src="' . esc_url( $url ) . '" alt="" loading="lazy">';
			$vurl = $it['video_url']['url'] ?? '';
			if ( '' !== trim( (string) $vurl ) ) {
				echo '<button type="button" class="sps-play" data-video="' . esc_attr( $vurl ) . '" aria-label="' . esc_attr__( 'پخش تیزر', 'sazan-core' ) . '"><span class="sps-play__ring"></span><svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg></button>';
			}
			echo '</div>';
		}
		echo '</div>';

		/* اسلایدر */
		echo '<div class="sps-slider">';
		if ( 'yes' === $s['show_arrows'] ) {
			echo '<button type="button" class="sps-prev" aria-label="' . esc_attr__( 'قبلی', 'sazan-core' ) . '"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg></button>';
			echo '<button type="button" class="sps-next" aria-label="' . esc_attr__( 'بعدی', 'sazan-core' ) . '"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></button>';
		}
		echo '<div class="sps-wrp">';

		foreach ( $items as $i => $it ) {
			echo '<div class="sps-item' . ( 0 === $i ? ' is-active' : '' ) . '" data-i="' . esc_attr( $i ) . '">';
			echo '<div class="sps-card">';
			if ( ! empty( $it['cover']['url'] ) ) {
				echo '<img class="sps-cover" src="' . esc_url( $it['cover']['url'] ) . '" alt="" loading="lazy">';
			}
			echo '<span class="sps-tint" aria-hidden="true"></span>';

			/* ربانِ گوشه */
			if ( ! empty( $it['ribbon_text'] ) ) {
				$rst = $it['ribbon_style'] ?? 'hot';
				if ( ! in_array( $rst, array( 'new', 'off', 'hot' ), true ) ) { $rst = 'hot'; }
				echo '<span class="sps-ribbon sps-ribbon--' . esc_attr( $rst ) . '">' . esc_html( $it['ribbon_text'] ) . '</span>';
			}

			echo '<div class="sps-inner">';

			/* عنوان */
			if ( ! empty( $it['title'] ) ) {
				echo '<h3 class="sps-title">' . nl2br( esc_html( $it['title'] ) ) . '</h3>';
			}

			/* امتیاز + هنرجو */
			$this->render_rating( $it['rating']['size'] ?? 0, $it['students'] ?? '' );

			/* قیمت (با تخفیف) */
			$has_price = ( '' !== trim( (string) ( $it['price'] ?? '' ) ) ) || ( '' !== trim( (string) ( $it['price_pre'] ?? '' ) ) );
			if ( $has_price ) {
				echo '<div class="sps-pricerow">';
				echo '<span class="sps-price">';
				if ( ! empty( $it['price_pre'] ) ) { echo '<i class="sps-pre">' . esc_html( $it['price_pre'] ) . '</i>'; }
				echo esc_html( $it['price'] ?? '' );
				if ( ! empty( $it['price_suf'] ) ) { echo '<sup>' . esc_html( $it['price_suf'] ) . '</sup>'; }
				echo '</span>';
				if ( ! empty( $it['price_old'] ) ) {
					echo '<del class="sps-price__old">' . esc_html( $it['price_old'] ) . '</del>';
				}
				if ( ! empty( $it['price_badge'] ) ) {
					echo '<span class="sps-price__badge">' . esc_html( $it['price_badge'] ) . '</span>';
				}
				echo '</div>';
			}

			/* شمارش معکوس (اختیاری) */
			if ( $timer_on && ( 'all' === $s['timer_scope'] || 0 === $i ) ) {
				echo $this->render_timer( $s ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			/* کنترل‌ها: چیپ‌های مشخصات + گیج */
			echo '<div class="sps-ctr">';
			echo '<div class="sps-labels">';
			$this->render_chips( $it['chips'] ?? '' );
			echo '</div>';

			$pct = isset( $it['percent']['size'] ) ? max( 0, min( 100, (int) $it['percent']['size'] ) ) : 0;
			if ( $pct > 0 || ! empty( $it['percent_label'] ) ) {
				echo '<span class="sps-hr" aria-hidden="true"></span>';
				$dash = round( $pct * 2.953, 1 ); // محیط دایره‌ی r=47 ≈ 295.3
				echo '<div class="sps-inf">';
				echo '<div class="sps-inf__percent">';
				echo '<svg class="sps-gauge" viewBox="0 0 100 100" width="92" height="92"><circle class="sps-gauge__bg" cx="50" cy="50" r="47"/><circle class="sps-gauge__fg" cx="50" cy="50" r="47" stroke-dasharray="' . esc_attr( $dash ) . ' 295.3" style="--sps-dash:' . esc_attr( $dash ) . '"/></svg>';
				echo '<span class="sps-inf__num">' . esc_html( $pct ) . '٪</span>';
				echo '</div>';
				if ( ! empty( $it['percent_label'] ) ) {
					echo '<span class="sps-inf__title">' . esc_html( $it['percent_label'] ) . '</span>';
				}
				echo '</div>'; // inf
			}
			echo '</div>'; // ctr

			/* نوارِ ظرفیت */
			$cap = isset( $it['cap_percent']['size'] ) ? max( 0, min( 100, (int) $it['cap_percent']['size'] ) ) : 0;
			if ( $cap > 0 ) {
				echo '<div class="sps-cap">';
				echo '<div class="sps-cap__track"><span class="sps-cap__fill" style="--sps-cap:' . esc_attr( $cap ) . '%"></span></div>';
				if ( ! empty( $it['cap_text'] ) ) {
					echo '<span class="sps-cap__text">' . esc_html( $it['cap_text'] ) . '</span>';
				}
				echo '</div>';
			}

			/* بلوکِ مدرس */
			$tname = trim( (string) ( $it['tutor_name'] ?? '' ) );
			$trole = trim( (string) ( $it['tutor_role'] ?? '' ) );
			$timg  = $it['tutor_img']['url'] ?? '';
			if ( '' !== $tname || '' !== $timg ) {
				echo '<div class="sps-tutor">';
				if ( '' !== $timg ) {
					echo '<span class="sps-tutor__ava"><img src="' . esc_url( $timg ) . '" alt="" loading="lazy"></span>';
				} else {
					echo '<span class="sps-tutor__ava sps-tutor__ava--ph"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/></svg></span>';
				}
				echo '<span class="sps-tutor__meta">';
				if ( '' !== $tname ) { echo '<b class="sps-tutor__name">' . esc_html( $tname ) . '</b>'; }
				if ( '' !== $trole ) { echo '<i class="sps-tutor__role">' . esc_html( $trole ) . '</i>'; }
				echo '</span>';
				echo '</div>';
			}

			/* دکمه‌ها */
			echo '<div class="sps-bottom">';
			if ( ! empty( $it['cart_text'] ) ) {
				$link = $it['cart_link']['url'] ?? '';
				$tag  = ( $link && '#' !== $link ) ? 'a' : 'button';
				$attr = ( 'a' === $tag ) ? ' href="' . esc_url( $link ) . '"' . ( ! empty( $it['cart_link']['is_external'] ) ? ' target="_blank"' : '' ) . ( ! empty( $it['cart_link']['nofollow'] ) ? ' rel="nofollow"' : '' ) : ' type="button"';
				echo '<' . $tag . ' class="sps-cart"' . $attr . '>' . esc_html( $it['cart_text'] ) . '</' . $tag . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			if ( ! empty( $it['fav_text'] ) ) {
				$ficon = $this->fav_icon_svg( $it['fav_icon'] ?? 'doc' );
				$flink = $it['fav_link']['url'] ?? '';
				if ( $flink && '#' !== $flink ) {
					$fattr = ' href="' . esc_url( $flink ) . '"' . ( ! empty( $it['fav_link']['is_external'] ) ? ' target="_blank"' : '' ) . ( ! empty( $it['fav_link']['nofollow'] ) ? ' rel="nofollow"' : '' );
					echo '<a class="sps-fav"' . $fattr . '><span class="sps-fav__ic" aria-hidden="true">' . $ficon . '</span>' . esc_html( $it['fav_text'] ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo '<button type="button" class="sps-fav sps-fav--toggle"><span class="sps-fav__ic" aria-hidden="true">' . $ficon . '</span>' . esc_html( $it['fav_text'] ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
			echo '</div>'; // bottom

			echo '</div>'; // inner
			echo '</div>'; // card
			echo '</div>'; // item
		}

		echo '</div>'; // wrp
		echo '</div>'; // slider

		/* پاپ‌آپِ ویدیو (یک نمونه برای کلِ ویجت) */
		echo '<div class="sps-lightbox" role="dialog" aria-modal="true" aria-hidden="true"><div class="sps-lightbox__bg"></div><div class="sps-lightbox__box"><button type="button" class="sps-lightbox__close" aria-label="' . esc_attr__( 'بستن', 'sazan-core' ) . '"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg></button><div class="sps-lightbox__media"></div></div></div>';

		echo '</div></div>'; // content + wrap
		echo '</div>'; // prodslider
	}
}
