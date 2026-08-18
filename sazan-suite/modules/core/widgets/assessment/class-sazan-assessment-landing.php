<?php
/**
 * صفحه‌ی فرود ارزیابی هوشمند کسب‌وکار.
 *
 * تمام بخش‌های طرح ارزیابی در یک ویجت مستقل Elementor ارائه می‌شوند تا
 * ساخت یک‌جای صفحه، ویرایش محتوا و تنظیمات واکنش‌گرا در اختیار کاربر باشد.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Sazan\Base\Widget_Base;
use Sazan\Quiz_CPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assessment_Landing extends Widget_Base {

	public function get_name() {
		return 'sazan-assessment-landing';
	}

	public function get_title() {
		return esc_html__( 'ارزیابی هوشمند کسب‌وکار', 'sazan-core' );
	}

	public function get_icon() {
		return 'eicon-dashboard';
	}

	public function get_categories() {
		return array( 'sazan' );
	}

	public function get_keywords() {
		return array( 'سازان', 'ارزیابی', 'آزمون کسب‌وکار', 'لندینگ', 'business assessment', 'quiz' );
	}

	public function get_style_depends() {
		return array( 'sazan-assessment-landing' );
	}

	public function get_script_depends() {
		return array( 'sazan-assessment-landing' );
	}

	private function quiz_options() {
		$options = array( 0 => '— بدون آزمون پیش‌فرض —' );
		$quizzes = get_posts( array( 'post_type' => Quiz_CPT::POST_TYPE, 'numberposts' => -1, 'post_status' => array( 'publish', 'draft', 'private' ), 'orderby' => 'title', 'order' => 'ASC' ) );
		foreach ( $quizzes as $quiz ) {
			$options[ $quiz->ID ] = $quiz->post_title;
		}
		return $options;
	}

	protected function register_controls() {
		$this->start_controls_section( 'hero_content', array( 'label' => '۱. قهرمان و دعوت به ارزیابی' ) );
		$this->add_control( 'quiz_id', array( 'label' => 'آزمون مقصد دکمه‌ها', 'type' => Controls_Manager::SELECT2, 'options' => $this->quiz_options(), 'default' => 0, 'description' => 'اگر لینک دکمه‌ها خالی باشد، به صفحه‌ی همین آزمون هدایت می‌شود.' ) );
		$this->add_control( 'hero_eyebrow', array( 'label' => 'برچسب کوچک بالای عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'گزارش ارزیابی کسب‌وکار' ) );
		$this->add_control( 'hero_title', array( 'label' => 'عنوان اصلی', 'type' => Controls_Manager::TEXT, 'default' => 'ارزیابی هوشمند' ) );
		$this->add_control( 'hero_accent', array( 'label' => 'بخش طلایی عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'کسب‌وکار' ) );
		$this->add_control( 'hero_description', array( 'label' => 'توضیح قهرمان', 'type' => Controls_Manager::TEXTAREA, 'default' => 'نقاط قوت، گلوگاه‌ها و فرصت‌های رشد خود را با دقت شناسایی کنید.' ) );
		$this->add_control( 'hero_primary_label', array( 'label' => 'متن دکمه اصلی', 'type' => Controls_Manager::TEXT, 'default' => 'شروع ارزیابی' ) );
		$this->add_control( 'hero_primary_link', array( 'label' => 'لینک دکمه اصلی', 'type' => Controls_Manager::URL, 'placeholder' => 'خالی = لینک آزمون انتخاب‌شده' ) );
		$this->add_control( 'hero_secondary_label', array( 'label' => 'متن دکمه دوم', 'type' => Controls_Manager::TEXT, 'default' => 'مشاهده آزمون‌ها' ) );
		$this->add_control( 'hero_secondary_link', array( 'label' => 'لینک دکمه دوم', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#assessment-areas' ) ) );
		$this->add_control( 'hero_proof', array( 'label' => 'متن اطمینان پایین دکمه‌ها', 'type' => Controls_Manager::TEXT, 'default' => 'کمتر از ۱۵ دقیقه  •  گزارش اختصاصی  •  نتایج کاربردی' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'dashboard_content', array( 'label' => '۲. داشبورد تصویری قهرمان' ) );
		$this->add_control( 'dashboard_badge', array( 'label' => 'برچسب داشبورد', 'type' => Controls_Manager::TEXT, 'default' => 'نمونه' ) );
		$this->add_control( 'dashboard_title', array( 'label' => 'عنوان داشبورد', 'type' => Controls_Manager::TEXT, 'default' => 'گزارش ارزیابی کسب‌وکار' ) );
		$this->add_control( 'dashboard_score', array( 'label' => 'امتیاز نمایشی', 'type' => Controls_Manager::NUMBER, 'default' => 87, 'min' => 0, 'max' => 100 ) );
		$this->add_control( 'dashboard_score_label', array( 'label' => 'متن زیر امتیاز', 'type' => Controls_Manager::TEXT, 'default' => 'از ۱۰۰' ) );
		$this->add_control( 'dashboard_axis_title', array( 'label' => 'عنوان ابعاد', 'type' => Controls_Manager::TEXT, 'default' => 'ابعاد کلیدی کسب‌وکار' ) );
		$axes = new Repeater();
		$axes->add_control( 'label', array( 'label' => 'نام بُعد', 'type' => Controls_Manager::TEXT, 'default' => 'استراتژی' ) );
		$axes->add_control( 'value', array( 'label' => 'امتیاز', 'type' => Controls_Manager::NUMBER, 'default' => 88, 'min' => 0, 'max' => 100 ) );
		$this->add_control( 'dashboard_axes', array( 'label' => 'ابعاد امتیازدهی', 'type' => Controls_Manager::REPEATER, 'fields' => $axes->get_controls(), 'title_field' => '{{{ label }}}', 'default' => array(
			array( 'label' => 'استراتژی', 'value' => 88 ), array( 'label' => 'مالی', 'value' => 82 ), array( 'label' => 'بازاریابی', 'value' => 84 ), array( 'label' => 'عملیات', 'value' => 86 ), array( 'label' => 'منابع انسانی', 'value' => 79 ),
		) ) );
		$this->add_control( 'dashboard_trend_title', array( 'label' => 'عنوان نمودار روند', 'type' => Controls_Manager::TEXT, 'default' => 'روند عملکرد شما' ) );
		$points = new Repeater();
		$points->add_control( 'label', array( 'label' => 'برچسب دوره', 'type' => Controls_Manager::TEXT, 'default' => 'فروردین' ) );
		$points->add_control( 'value', array( 'label' => 'مقدار', 'type' => Controls_Manager::NUMBER, 'default' => 58, 'min' => 0, 'max' => 100 ) );
		$this->add_control( 'dashboard_trend_points', array( 'label' => 'نقاط نمودار روند', 'type' => Controls_Manager::REPEATER, 'fields' => $points->get_controls(), 'title_field' => '{{{ label }}}', 'default' => array(
			array( 'label' => 'فروردین', 'value' => 58 ), array( 'label' => 'اردیبهشت', 'value' => 61 ), array( 'label' => 'خرداد', 'value' => 76 ), array( 'label' => 'تیر', 'value' => 64 ), array( 'label' => 'مرداد', 'value' => 82 ), array( 'label' => 'شهریور', 'value' => 72 ), array( 'label' => 'مهر', 'value' => 92 ),
		) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'stats_one_content', array( 'label' => '۳. نوار آمار اول' ) );
		$stat = new Repeater();
		$stat->add_control( 'svg_code', array( 'label' => 'SVG اختصاصی', 'type' => Controls_Manager::TEXTAREA, 'rows' => 4, 'placeholder' => '<svg viewBox="0 0 24 24" ...>...</svg>', 'description' => 'کد SVG اختصاصی خود را وارد کنید؛ خالی بگذارید تا آیکون نمایش داده نشود.' ) );
		$stat->add_control( 'value', array( 'label' => 'عدد یا عبارت برجسته', 'type' => Controls_Manager::TEXT, 'default' => '+۳۰' ) );
		$stat->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'ارزیابی انجام‌شده' ) );
		$stat->add_control( 'caption', array( 'label' => 'توضیح کوتاه', 'type' => Controls_Manager::TEXT, 'default' => 'نتایج قابل اتکا' ) );
		$this->add_control( 'stats_one', array( 'label' => 'آیتم‌های آمار', 'type' => Controls_Manager::REPEATER, 'fields' => $stat->get_controls(), 'title_field' => '{{{ value }}} — {{{ title }}}', 'default' => array(
			array( 'value' => '+۳۰٬۰۰۰', 'title' => 'ارزیابی انجام‌شده', 'caption' => '' ),
			array( 'value' => 'گزارش اختصاصی', 'title' => 'ویژه کسب‌وکار شما', 'caption' => '' ),
			array( 'value' => 'نتایج عملی', 'title' => 'قابل اجرا و کاربردی', 'caption' => '' ),
			array( 'value' => 'کمتر از ۱۵ دقیقه', 'title' => 'زمان تقریبی تکمیل', 'caption' => '' ),
		) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'why_content', array( 'label' => '۴. چرا ارزیابی اهمیت دارد؟' ) );
		$this->add_control( 'why_title', array( 'label' => 'عنوان بخش', 'type' => Controls_Manager::TEXT, 'default' => 'چرا ارزیابی اهمیت دارد؟' ) );
		$this->add_control( 'why_description', array( 'label' => 'توضیح بخش', 'type' => Controls_Manager::TEXTAREA, 'default' => '' ) );
		$why = new Repeater();
		$why->add_control( 'svg_code', array( 'label' => 'SVG اختصاصی', 'type' => Controls_Manager::TEXTAREA, 'rows' => 4, 'placeholder' => '<svg viewBox="0 0 24 24" ...>...</svg>', 'description' => 'کد SVG اختصاصی خود را وارد کنید؛ خالی بگذارید تا آیکون نمایش داده نشود.' ) );
		$why->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'شناسایی گلوگاه‌ها' ) );
		$why->add_control( 'text', array( 'label' => 'توضیح', 'type' => Controls_Manager::TEXTAREA, 'default' => 'مشکلات پنهان کسب‌وکار را سریع و دقیق پیدا کنید.' ) );
		$this->add_control( 'why_items', array( 'label' => 'کارت‌های اهمیت', 'type' => Controls_Manager::REPEATER, 'fields' => $why->get_controls(), 'title_field' => '{{{ title }}}', 'default' => array(
			array( 'title' => 'شناسایی گلوگاه‌ها', 'text' => 'مشکلات پنهان کسب‌وکار را سریع و دقیق پیدا کنید.' ),
			array( 'title' => 'کشف فرصت‌های رشد', 'text' => 'فرصت‌های بالقوه را ببینید و مسیر رشد را هموار کنید.' ),
			array( 'title' => 'گزارش اختصاصی', 'text' => 'گزارش دقیق و قابل فهم متناسب با کسب‌وکار شما.' ),
			array( 'title' => 'تصمیم‌گیری دقیق‌تر', 'text' => 'با داده‌های واقعی، بهتر و سریع‌تر تصمیم بگیرید.' ),
		) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'areas_content', array( 'label' => '۵. حوزه‌های شروع ارزیابی' ) );
		$this->add_control( 'areas_title', array( 'label' => 'عنوان بخش', 'type' => Controls_Manager::TEXT, 'default' => 'از کجا شروع می‌کنید؟' ) );
		$this->add_control( 'areas_description', array( 'label' => 'توضیح بخش', 'type' => Controls_Manager::TEXT, 'default' => 'بخش موردنظر کسب‌وکارتان را انتخاب کنید.' ) );
		$area = new Repeater();
		$area->add_control( 'svg_code', array( 'label' => 'SVG اختصاصی', 'type' => Controls_Manager::TEXTAREA, 'rows' => 4, 'placeholder' => '<svg viewBox="0 0 24 24" ...>...</svg>', 'description' => 'کد SVG اختصاصی خود را وارد کنید؛ خالی بگذارید تا آیکون نمایش داده نشود.' ) );
		$area->add_control( 'badge', array( 'label' => 'نشان ویژه', 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$area->add_control( 'title', array( 'label' => 'عنوان حوزه', 'type' => Controls_Manager::TEXT, 'default' => 'فروش' ) );
		$area->add_control( 'text', array( 'label' => 'توضیح حوزه', 'type' => Controls_Manager::TEXTAREA, 'default' => 'فرایند فروش، تجربه مشتری و رشد درآمد.' ) );
		$area->add_control( 'link_label', array( 'label' => 'متن لینک', 'type' => Controls_Manager::TEXT, 'default' => 'شروع ارزیابی' ) );
		$area->add_control( 'link', array( 'label' => 'لینک حوزه', 'type' => Controls_Manager::URL ) );
		$this->add_control( 'areas', array( 'label' => 'کارت‌های حوزه', 'type' => Controls_Manager::REPEATER, 'fields' => $area->get_controls(), 'title_field' => '{{{ title }}}', 'default' => array(
			array( 'title' => 'فروش', 'text' => 'فرایند فروش، تجربه مشتری و رشد درآمد.', 'link_label' => 'شروع ارزیابی' ),
			array( 'title' => 'مارکتینگ', 'text' => 'استراتژی بازاریابی، کانال‌ها و پیام برند.', 'link_label' => 'شروع ارزیابی' ),
			array( 'title' => 'برندینگ', 'badge' => 'محبوب', 'text' => 'هویت برند، جایگاه‌یابی و تجربه متمایز.', 'link_label' => 'شروع ارزیابی' ),
			array( 'title' => 'سیستم‌سازی', 'text' => 'فرایندها، ساختار و نظم اجرایی کسب‌وکار.', 'link_label' => 'شروع ارزیابی' ),
			array( 'title' => 'مالی', 'text' => 'ارزیابی سلامت مالی و تصمیم‌های اقتصادی.', 'link_label' => 'شروع ارزیابی' ),
			array( 'title' => 'منابع انسانی', 'text' => 'مدیریت تیم، فرهنگ سازمانی و بهره‌وری.', 'link_label' => 'شروع ارزیابی' ),
		) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'comprehensive_content', array( 'label' => '۶. ارزیابی جامع کسب‌وکار' ) );
		$this->add_control( 'comprehensive_badge', array( 'label' => 'برچسب', 'type' => Controls_Manager::TEXT, 'default' => 'پیشنهاد ویژه' ) );
		$this->add_control( 'comprehensive_title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'ارزیابی جامع کسب‌وکار' ) );
		$this->add_control( 'comprehensive_text', array( 'label' => 'توضیح', 'type' => Controls_Manager::TEXTAREA, 'default' => 'تحلیل کامل و دقیق کسب‌وکارتان در یک نگاه؛ همراه با راهکارها و پیشنهادهای عملی برای رشد پایدار.' ) );
		$this->add_control( 'comprehensive_score', array( 'label' => 'امتیاز نمایشی گزارش', 'type' => Controls_Manager::NUMBER, 'default' => 91, 'min' => 0, 'max' => 100 ) );
		$bullet = new Repeater();
		$bullet->add_control( 'text', array( 'label' => 'متن مزیت', 'type' => Controls_Manager::TEXT, 'default' => 'تحلیل ۳۶۰ درجه کسب‌وکار' ) );
		$this->add_control( 'comprehensive_bullets', array( 'label' => 'مزیت‌ها', 'type' => Controls_Manager::REPEATER, 'fields' => $bullet->get_controls(), 'title_field' => '{{{ text }}}', 'default' => array( array( 'text' => 'تحلیل ۳۶۰ درجه کسب‌وکار' ), array( 'text' => 'درک عمیق نقاط قوت و ضعف' ), array( 'text' => 'مقایسه با میانگین صنعت' ), array( 'text' => 'پیشنهادهای عملی و مرحله‌ای' ) ) ) );
		$this->add_control( 'comprehensive_button_label', array( 'label' => 'متن دکمه', 'type' => Controls_Manager::TEXT, 'default' => 'شروع ارزیابی جامع' ) );
		$this->add_control( 'comprehensive_button_link', array( 'label' => 'لینک دکمه', 'type' => Controls_Manager::URL ) );
		$this->end_controls_section();

		$this->start_controls_section( 'stats_two_content', array( 'label' => '۷. نوار آمار دوم' ) );
		$this->add_control( 'stats_two', array( 'label' => 'آیتم‌های آمار', 'type' => Controls_Manager::REPEATER, 'fields' => $stat->get_controls(), 'title_field' => '{{{ value }}} — {{{ title }}}', 'default' => array(
			array( 'value' => '۹۶٪', 'title' => 'رضایت کاربران', 'caption' => '' ),
			array( 'value' => '+۲۵۰۰', 'title' => 'مدیر و کارآفرین از آن استفاده می‌کنند', 'caption' => '' ),
			array( 'value' => '+۳۰۰', 'title' => 'گزارش تحویل داده شده', 'caption' => '' ),
			array( 'value' => 'به‌روزرسانی مداوم', 'title' => 'بر اساس جدیدترین متدها', 'caption' => '' ),
		) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'process_content', array( 'label' => '۸. فرایند کار' ) );
		$this->add_control( 'process_title', array( 'label' => 'عنوان بخش', 'type' => Controls_Manager::TEXT, 'default' => 'چگونه کار می‌کند؟' ) );
		$this->add_control( 'process_description', array( 'label' => 'توضیح بخش', 'type' => Controls_Manager::TEXT, 'default' => 'در سه مرحله، تصویر روشن‌تری از کسب‌وکارتان بسازید.' ) );
		$process = new Repeater();
		$process->add_control( 'svg_code', array( 'label' => 'SVG اختصاصی', 'type' => Controls_Manager::TEXTAREA, 'rows' => 4, 'placeholder' => '<svg viewBox="0 0 24 24" ...>...</svg>', 'description' => 'کد SVG اختصاصی خود را وارد کنید؛ خالی بگذارید تا آیکون نمایش داده نشود.' ) );
		$process->add_control( 'number', array( 'label' => 'شماره مرحله', 'type' => Controls_Manager::TEXT, 'default' => '۱' ) );
		$process->add_control( 'title', array( 'label' => 'عنوان', 'type' => Controls_Manager::TEXT, 'default' => 'انتخاب آزمون' ) );
		$process->add_control( 'text', array( 'label' => 'توضیح', 'type' => Controls_Manager::TEXTAREA, 'default' => 'آزمون متناسب با نیازتان را انتخاب کنید.' ) );
		$this->add_control( 'process_items', array( 'label' => 'مراحل', 'type' => Controls_Manager::REPEATER, 'fields' => $process->get_controls(), 'title_field' => '{{{ number }}} — {{{ title }}}', 'default' => array(
			array( 'number' => '۱', 'title' => 'انتخاب آزمون', 'text' => 'آزمون متناسب با نیازتان را انتخاب کنید.' ),
			array( 'number' => '۲', 'title' => 'پاسخ به سؤالات', 'text' => 'به چند سؤال کوتاه و هدفمند پاسخ دهید.' ),
			array( 'number' => '۳', 'title' => 'دریافت گزارش', 'text' => 'گزارش اختصاصی و پیشنهادهای کاربردی را دریافت کنید.' ),
		) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'report_content', array( 'label' => '۹. نمونه گزارش' ) );
		$this->add_control( 'report_title', array( 'label' => 'عنوان بخش', 'type' => Controls_Manager::TEXT, 'default' => 'نمونه گزارش' ) );
		$this->add_control( 'report_text', array( 'label' => 'توضیح گزارش', 'type' => Controls_Manager::TEXTAREA, 'default' => 'گزارش شما شامل تحلیل عملکرد، نقاط قوت و فرصت‌های بهبود به‌صورت شفاف و کاربردی است.' ) );
		$this->add_control( 'report_button_label', array( 'label' => 'متن دکمه گزارش', 'type' => Controls_Manager::TEXT, 'default' => 'مشاهده نمونه گزارش' ) );
		$this->add_control( 'report_button_link', array( 'label' => 'لینک نمونه گزارش', 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#assessment-report' ) ) );
		$this->add_control( 'report_score', array( 'label' => 'امتیاز نمایشی گزارش', 'type' => Controls_Manager::NUMBER, 'default' => 87, 'min' => 0, 'max' => 100 ) );
		$this->add_control( 'report_graph_label', array( 'label' => 'عنوان نمودار گزارش', 'type' => Controls_Manager::TEXT, 'default' => 'روند عملکرد شما' ) );
		$report_bullet = new Repeater();
		$report_bullet->add_control( 'text', array( 'label' => 'متن نکته گزارش', 'type' => Controls_Manager::TEXT, 'default' => 'تحلیل عملکرد و نقاط قوت' ) );
		$this->add_control( 'report_bullets', array( 'label' => 'نکته‌های گزارش', 'type' => Controls_Manager::REPEATER, 'fields' => $report_bullet->get_controls(), 'title_field' => '{{{ text }}}', 'default' => array( array( 'text' => 'تحلیل عملکرد و نقاط قوت' ), array( 'text' => 'فرصت‌های رشد اولویت‌دار' ), array( 'text' => 'پیشنهادهای کلیدی و قابل اجرا' ) ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'faq_content', array( 'label' => '۱۰. سؤالات متداول' ) );
		$this->add_control( 'faq_title', array( 'label' => 'عنوان بخش', 'type' => Controls_Manager::TEXT, 'default' => 'سؤالات متداول' ) );
		$faq = new Repeater();
		$faq->add_control( 'question', array( 'label' => 'سؤال', 'type' => Controls_Manager::TEXT, 'default' => 'ارزیابی کسب‌وکار چقدر زمان می‌برد؟' ) );
		$faq->add_control( 'answer', array( 'label' => 'پاسخ', 'type' => Controls_Manager::TEXTAREA, 'default' => 'تکمیل هر ارزیابی معمولاً کمتر از ۱۵ دقیقه زمان می‌برد و نتیجه بلافاصله نمایش داده می‌شود.' ) );
		$this->add_control( 'faqs', array( 'label' => 'سؤالات', 'type' => Controls_Manager::REPEATER, 'fields' => $faq->get_controls(), 'title_field' => '{{{ question }}}', 'default' => array( array( 'question' => 'ارزیابی کسب‌وکار چقدر زمان می‌برد؟', 'answer' => 'تکمیل هر ارزیابی معمولاً کمتر از ۱۵ دقیقه زمان می‌برد و نتیجه بلافاصله نمایش داده می‌شود.' ), array( 'question' => 'چه اطلاعاتی در گزارش ارائه می‌شود؟', 'answer' => 'نقاط قوت، گلوگاه‌ها، فرصت‌های رشد و پیشنهادهای اولویت‌دار متناسب با پاسخ‌های شما ارائه می‌شود.' ), array( 'question' => 'آیا اطلاعات من محرمانه می‌ماند؟', 'answer' => 'بله؛ اطلاعات شما فقط برای تولید گزارش استفاده می‌شود و بدون اجازه‌تان منتشر نخواهد شد.' ), array( 'question' => 'چگونه می‌توانم نتیجه را با تیمم به اشتراک بگذارم؟', 'answer' => 'بعد از تکمیل آزمون، لینک گزارش و دکمه دانلود در اختیار شما قرار می‌گیرد.' ) ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'bottom_cta_content', array( 'label' => '۱۱. دعوت نهایی' ) );
		$this->add_control( 'bottom_title', array( 'label' => 'عنوان نهایی', 'type' => Controls_Manager::TEXT, 'default' => 'آماده‌اید کسب‌وکار خود را دقیق‌تر ببینید؟' ) );
		$this->add_control( 'bottom_text', array( 'label' => 'توضیح نهایی', 'type' => Controls_Manager::TEXT, 'default' => 'همین حالا ارزیابی را شروع کنید و مسیر رشد خود را روشن کنید.' ) );
		$this->add_control( 'bottom_button_label', array( 'label' => 'متن دکمه نهایی', 'type' => Controls_Manager::TEXT, 'default' => 'شروع ارزیابی' ) );
		$this->add_control( 'bottom_button_link', array( 'label' => 'لینک دکمه نهایی', 'type' => Controls_Manager::URL ) );
		$this->end_controls_section();

		$this->start_controls_section( 'landing_style', array( 'label' => '۱۲. استایل کلی صفحه', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'landing_background', 'label' => 'پس‌زمینه صفحه', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .sazan-assessment-landing' ) );
		$this->add_control( 'landing_accent', array( 'label' => 'رنگ فیروزه‌ای', 'type' => Controls_Manager::COLOR, 'default' => '#39d4f5', 'selectors' => array( '{{WRAPPER}} .sazan-assessment-landing' => '--assessment-accent: {{VALUE}};' ) ) );
		$this->add_control( 'landing_gold', array( 'label' => 'رنگ طلایی', 'type' => Controls_Manager::COLOR, 'default' => '#f6b53d', 'selectors' => array( '{{WRAPPER}} .sazan-assessment-landing' => '--assessment-gold: {{VALUE}};' ) ) );
		$this->add_control( 'landing_text', array( 'label' => 'رنگ متن اصلی', 'type' => Controls_Manager::COLOR, 'default' => '#f5f7fb', 'selectors' => array( '{{WRAPPER}} .sazan-assessment-landing' => '--assessment-text: {{VALUE}};' ) ) );
		$this->add_control( 'landing_muted', array( 'label' => 'رنگ متن فرعی', 'type' => Controls_Manager::COLOR, 'default' => '#aebbd0', 'selectors' => array( '{{WRAPPER}} .sazan-assessment-landing' => '--assessment-muted: {{VALUE}};' ) ) );
		$this->add_control( 'landing_panel', array( 'label' => 'رنگ پنل‌ها', 'type' => Controls_Manager::COLOR, 'default' => '#0b1e38', 'selectors' => array( '{{WRAPPER}} .sazan-assessment-landing' => '--assessment-panel: {{VALUE}};' ) ) );
		$this->add_control( 'landing_border', array( 'label' => 'رنگ حاشیه پنل‌ها', 'type' => Controls_Manager::COLOR, 'default' => '#1c3b5d', 'selectors' => array( '{{WRAPPER}} .sazan-assessment-landing' => '--assessment-border: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'landing_content_width', array( 'label' => 'حداکثر عرض محتوا', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 760, 'max' => 1900 ) ), 'default' => array( 'unit' => 'px', 'size' => 1700 ), 'selectors' => array( '{{WRAPPER}} .sazan-assessment-inner' => 'max-width: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'landing_section_gap', array( 'label' => 'فاصله بخش‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 24, 'max' => 140 ) ), 'default' => array( 'unit' => 'px', 'size' => 64 ), 'selectors' => array( '{{WRAPPER}} .sazan-assessment-section' => 'margin-bottom: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'landing_radius', array( 'label' => 'گردی پنل‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 36 ) ), 'default' => array( 'unit' => 'px', 'size' => 14 ), 'selectors' => array( '{{WRAPPER}} .sazan-assessment-panel, {{WRAPPER}} .sazan-assessment-area, {{WRAPPER}} .sazan-assessment-statbar, {{WRAPPER}} .sazan-assessment-bottom-cta' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'landing_body_typography', 'label' => 'تایپوگرافی متن', 'selector' => '{{WRAPPER}} .sazan-assessment-landing' ) );
		$this->end_controls_section();

		$this->start_controls_section( 'hero_style', array( 'label' => '۱۳. استایل قهرمان', 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'hero_title_typography', 'label' => 'تایپوگرافی عنوان اصلی', 'selector' => '{{WRAPPER}} .sazan-assessment-hero-title' ) );
		$this->add_control( 'hero_title_color', array( 'label' => 'رنگ عنوان اصلی', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-assessment-hero-title' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'hero_accent_color', array( 'label' => 'رنگ بخش طلایی عنوان', 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-assessment-hero-accent' => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'hero_align', array( 'label' => 'تراز متن', 'type' => Controls_Manager::CHOOSE, 'options' => array( 'right' => array( 'title' => 'راست', 'icon' => 'eicon-text-align-right' ), 'center' => array( 'title' => 'وسط', 'icon' => 'eicon-text-align-center' ), 'left' => array( 'title' => 'چپ', 'icon' => 'eicon-text-align-left' ) ), 'default' => 'right', 'selectors' => array( '{{WRAPPER}} .sazan-assessment-hero-copy' => 'text-align: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'hero_buttons_gap', array( 'label' => 'فاصله دکمه‌ها', 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 4, 'max' => 40 ) ), 'default' => array( 'unit' => 'px', 'size' => 10 ), 'selectors' => array( '{{WRAPPER}} .sazan-assessment-hero-actions' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'primary_button_background', 'label' => 'پس‌زمینه دکمه اصلی', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .sazan-assessment-button-primary' ) );
		$this->add_group_control( Group_Control_Border::get_type(), array( 'name' => 'primary_button_border', 'selector' => '{{WRAPPER}} .sazan-assessment-button-primary' ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'primary_button_shadow', 'selector' => '{{WRAPPER}} .sazan-assessment-button-primary' ) );
		$this->add_control( 'primary_button_color', array( 'label' => 'رنگ متن دکمه اصلی', 'type' => Controls_Manager::COLOR, 'default' => '#111c2e', 'selectors' => array( '{{WRAPPER}} .sazan-assessment-button-primary' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'secondary_button_color', array( 'label' => 'رنگ دکمه دوم', 'type' => Controls_Manager::COLOR, 'default' => '#f6f8fc', 'selectors' => array( '{{WRAPPER}} .sazan-assessment-button-secondary' => 'color: {{VALUE}};' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'seo_settings', array( 'label' => '۱۴. سئو و دسترسی', 'tab' => Controls_Manager::TAB_CONTENT ) );
		$this->add_control( 'seo_schema', array( 'label' => 'خروجی داده ساختاریافته', 'type' => Controls_Manager::SWITCHER, 'default' => 'yes', 'return_value' => 'yes', 'description' => 'برای جلوگیری از خروجی تکراری، اگر افزونه سئوی شما FAQ/صفحه را اسکیما می‌کند خاموشش کنید.' ) );
		$this->add_control( 'seo_name', array( 'label' => 'نام صفحه در اسکیما', 'type' => Controls_Manager::TEXT, 'default' => 'ارزیابی هوشمند کسب‌وکار' ) );
		$this->add_control( 'seo_description', array( 'label' => 'توضیح صفحه در اسکیما', 'type' => Controls_Manager::TEXTAREA, 'default' => 'با ارزیابی هوشمند سازان، نقاط قوت، گلوگاه‌ها و فرصت‌های رشد کسب‌وکارتان را شناسایی کنید.' ) );
		$this->add_control( 'hero_title_tag', array( 'label' => 'تگ عنوان اصلی', 'type' => Controls_Manager::SELECT, 'options' => array( 'h1' => 'H1', 'h2' => 'H2' ), 'default' => 'h1' ) );
		$this->add_control( 'section_title_tag', array( 'label' => 'تگ عنوان بخش‌ها', 'type' => Controls_Manager::SELECT, 'options' => array( 'h2' => 'H2', 'h3' => 'H3' ), 'default' => 'h2' ) );
		$this->add_control( 'aria_label', array( 'label' => 'برچسب دسترسی صفحه', 'type' => Controls_Manager::TEXT, 'default' => 'ارزیابی هوشمند کسب‌وکار' ) );
		$this->end_controls_section();
	}

	private function svg( $markup ) {
		$markup = trim( (string) $markup );
		if ( '' === $markup || false === stripos( $markup, '<svg' ) ) {
			return '';
		}
		$allowed = array(
			'svg' => array( 'xmlns' => true, 'viewBox' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'fill-rule' => true, 'clip-rule' => true, 'preserveAspectRatio' => true, 'class' => true, 'role' => true, 'aria-hidden' => true, 'focusable' => true, 'style' => true ),
			'g' => array( 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'fill-rule' => true, 'clip-rule' => true, 'transform' => true, 'opacity' => true, 'class' => true, 'style' => true ),
			'path' => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'fill-rule' => true, 'clip-rule' => true, 'transform' => true, 'opacity' => true, 'class' => true, 'style' => true ),
			'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true, 'style' => true ),
			'ellipse' => array( 'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true, 'style' => true ),
			'rect' => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'class' => true, 'style' => true ),
			'line' => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'class' => true, 'style' => true ),
			'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'class' => true, 'style' => true ),
			'polygon' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linejoin' => true, 'class' => true, 'style' => true ),
			'defs' => array(),
			'linearGradient' => array( 'id' => true, 'x1' => true, 'x2' => true, 'y1' => true, 'y2' => true, 'gradientUnits' => true ),
			'radialGradient' => array( 'id' => true, 'cx' => true, 'cy' => true, 'r' => true, 'fx' => true, 'fy' => true, 'gradientUnits' => true ),
			'stop' => array( 'offset' => true, 'stop-color' => true, 'stop-opacity' => true, 'style' => true ),
		);
		return wp_kses( $markup, $allowed );
	}

	private function link_settings( $settings, $fallback = '' ) {
		$link = is_array( $settings ) ? $settings : array();
		if ( empty( $link['url'] ) && $fallback ) {
			$link['url'] = $fallback;
		}
		return $link;
	}

	private function heading( $text, $tag, $class = '' ) {
		$allowed = array( 'h1', 'h2', 'h3' );
		$tag     = in_array( $tag, $allowed, true ) ? $tag : 'h2';
		return '<' . $tag . ' class="' . esc_attr( $class ) . '">' . esc_html( $text ) . '</' . $tag . '>';
	}

	private function line_points( $points ) {
		$points = array_values( array_slice( (array) $points, 0, 8 ) );
		if ( count( $points ) < 2 ) {
			$points = array( array( 'value' => 52 ), array( 'value' => 68 ), array( 'value' => 61 ), array( 'value' => 84 ) );
		}
		$out = array();
		$count = count( $points );
		foreach ( $points as $index => $point ) {
			$value = max( 0, min( 100, floatval( $point['value'] ?? 50 ) ) );
			$x     = 18 + ( $index * ( 364 / max( 1, $count - 1 ) ) );
			$y     = 96 - ( $value * 0.68 );
			$out[] = round( $x, 1 ) . ',' . round( $y, 1 );
		}
		return implode( ' ', $out );
	}

	private function render_dashboard( $s ) {
		$score = max( 0, min( 100, absint( $s['dashboard_score'] ) ) );
		echo '<div class="sazan-assessment-dashboard" aria-label="' . esc_attr( $s['dashboard_title'] ) . '">';
		echo '<div class="sazan-assessment-dashboard-head"><span class="sazan-assessment-badge">' . esc_html( $s['dashboard_badge'] ) . '</span><b>' . esc_html( $s['dashboard_title'] ) . '</b></div>';
		echo '<div class="sazan-assessment-dashboard-main"><div class="sazan-assessment-gauge" style="--assessment-score:' . esc_attr( $score ) . '%"><div><strong>' . esc_html( $score ) . '</strong><small>' . esc_html( $s['dashboard_score_label'] ) . '</small></div></div><div class="sazan-assessment-axes"><b>' . esc_html( $s['dashboard_axis_title'] ) . '</b>';
		foreach ( (array) $s['dashboard_axes'] as $axis ) {
			$value = max( 0, min( 100, floatval( $axis['value'] ?? 0 ) ) );
			echo '<div class="sazan-assessment-axis"><span>' . esc_html( $axis['label'] ) . '</span><i><em style="width:' . esc_attr( $value ) . '%"></em></i><strong>' . esc_html( $value ) . '</strong></div>';
		}
		echo '</div></div><div class="sazan-assessment-trend"><b>' . esc_html( $s['dashboard_trend_title'] ) . '</b><svg viewBox="0 0 400 110" role="img" aria-label="' . esc_attr( $s['dashboard_trend_title'] ) . '"><path class="grid" d="M18 28H382M18 58H382M18 88H382"/><polyline points="' . esc_attr( $this->line_points( $s['dashboard_trend_points'] ) ) . '"/><polyline class="secondary" points="18,88 78,82 139,85 200,76 260,84 321,71 382,77"/>';
		$points = array_values( array_slice( (array) $s['dashboard_trend_points'], 0, 8 ) );
		$count  = max( 1, count( $points ) - 1 );
		foreach ( $points as $index => $point ) { $value = max( 0, min( 100, floatval( $point['value'] ?? 50 ) ) ); $x = 18 + ( $index * ( 364 / $count ) ); $y = 96 - ( $value * 0.68 ); echo '<circle cx="' . esc_attr( round( $x, 1 ) ) . '" cy="' . esc_attr( round( $y, 1 ) ) . '" r="3"/>'; }
		echo '</svg><div class="sazan-assessment-trend-labels">';
		foreach ( $points as $point ) { echo '<span>' . esc_html( $point['label'] ?? '' ) . '</span>'; }
		echo '</div></div></div>';
	}

	private function render_stats( $items, $class = '' ) {
		echo '<div class="sazan-assessment-statbar ' . esc_attr( $class ) . '" role="list">';
		foreach ( (array) $items as $item ) {
			$svg = $this->svg( $item['svg_code'] ?? '' );
			echo '<div class="sazan-assessment-stat" role="listitem">' . ( $svg ? '<span class="sazan-assessment-stat-icon">' . $svg . '</span>' : '' ) . '<div><strong>' . esc_html( $item['value'] ?? '' ) . '</strong><b>' . esc_html( $item['title'] ?? '' ) . '</b><small>' . esc_html( $item['caption'] ?? '' ) . '</small></div></div>';
		}
		echo '</div>';
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$quiz_url = ! empty( $s['quiz_id'] ) ? get_permalink( absint( $s['quiz_id'] ) ) : '';
		$hero_tag = in_array( $s['hero_title_tag'] ?? 'h1', array( 'h1', 'h2' ), true ) ? $s['hero_title_tag'] : 'h1';
		$section_tag = in_array( $s['section_title_tag'] ?? 'h2', array( 'h2', 'h3' ), true ) ? $s['section_title_tag'] : 'h2';
		$assessment_fallback = $quiz_url ?: '#assessment-faq';
		$primary = $this->link_settings( $s['hero_primary_link'] ?? array(), $assessment_fallback );
		$secondary = $this->link_settings( $s['hero_secondary_link'] ?? array(), '' );
		$comprehensive = $this->link_settings( $s['comprehensive_button_link'] ?? array(), $assessment_fallback );
		$report = $this->link_settings( $s['report_button_link'] ?? array(), '' );
		$bottom = $this->link_settings( $s['bottom_button_link'] ?? array(), $assessment_fallback );
		$schema_name = trim( (string) ( $s['seo_name'] ?? $s['hero_title'] ) );
		$schema_desc = trim( (string) ( $s['seo_description'] ?? $s['hero_description'] ) );

		echo '<main class="sazan-assessment-landing" aria-label="' . esc_attr( $s['aria_label'] ?? 'ارزیابی هوشمند کسب‌وکار' ) . '"><div class="sazan-assessment-inner">';
		echo '<section class="sazan-assessment-hero sazan-assessment-section"><div class="sazan-assessment-hero-visual">';
		$this->render_dashboard( $s );
		echo '</div><div class="sazan-assessment-hero-copy"><span class="sazan-assessment-eyebrow">' . esc_html( $s['hero_eyebrow'] ) . '</span>' . $this->heading( $s['hero_title'], $hero_tag, 'sazan-assessment-hero-title' ) . '<span class="sazan-assessment-hero-accent">' . esc_html( $s['hero_accent'] ) . '</span><p>' . esc_html( $s['hero_description'] ) . '</p><div class="sazan-assessment-hero-actions">';
		if ( ! empty( $s['hero_primary_label'] ) ) { $this->add_link_attributes( 'assessment-hero-primary', $primary ); echo '<a class="sazan-assessment-button sazan-assessment-button-primary" ' . $this->get_render_attribute_string( 'assessment-hero-primary' ) . '>' . esc_html( $s['hero_primary_label'] ) . '<span aria-hidden="true">←</span></a>'; }
		if ( ! empty( $s['hero_secondary_label'] ) && ! empty( $secondary['url'] ) ) { $this->add_link_attributes( 'assessment-hero-secondary', $secondary ); echo '<a class="sazan-assessment-button sazan-assessment-button-secondary" ' . $this->get_render_attribute_string( 'assessment-hero-secondary' ) . '>' . esc_html( $s['hero_secondary_label'] ) . '<span aria-hidden="true">▣</span></a>'; }
		echo '</div><div class="sazan-assessment-proof">' . esc_html( $s['hero_proof'] ) . '</div></div></section>';

		$this->render_stats( $s['stats_one'], 'sazan-assessment-section' );
		echo '<section id="assessment-why" class="sazan-assessment-section sazan-assessment-centered"><header>' . $this->heading( $s['why_title'], $section_tag, 'sazan-assessment-section-title' ) . ( ! empty( $s['why_description'] ) ? '<p>' . esc_html( $s['why_description'] ) . '</p>' : '' ) . '</header><div class="sazan-assessment-why-grid">';
		foreach ( (array) $s['why_items'] as $item ) { $svg = $this->svg( $item['svg_code'] ?? '' ); echo '<article class="sazan-assessment-why-item">' . ( $svg ? '<span class="sazan-assessment-line-icon">' . $svg . '</span>' : '' ) . '<h3>' . esc_html( $item['title'] ) . '</h3><p>' . esc_html( $item['text'] ) . '</p></article>'; }
		echo '</div></section>';

		echo '<section id="assessment-areas" class="sazan-assessment-section sazan-assessment-centered"><header>' . $this->heading( $s['areas_title'], $section_tag, 'sazan-assessment-section-title' ) . '<p>' . esc_html( $s['areas_description'] ) . '</p></header><div class="sazan-assessment-areas-grid">';
		foreach ( (array) $s['areas'] as $item ) { $link = $this->link_settings( $item['link'] ?? array(), $quiz_url ); $svg = $this->svg( $item['svg_code'] ?? '' ); echo '<article class="sazan-assessment-area">' . ( ! empty( $item['badge'] ) ? '<span class="sazan-assessment-area-badge">' . esc_html( $item['badge'] ) . '</span>' : '' ) . ( $svg ? '<span class="sazan-assessment-area-icon">' . $svg . '</span>' : '' ) . '<h3>' . esc_html( $item['title'] ) . '</h3><p>' . esc_html( $item['text'] ) . '</p>'; if ( ! empty( $item['link_label'] ) && ! empty( $link['url'] ) ) { $key = 'assessment-area-' . wp_rand( 100, 99999 ); $this->add_link_attributes( $key, $link ); echo '<a ' . $this->get_render_attribute_string( $key ) . '>' . esc_html( $item['link_label'] ) . ' <span aria-hidden="true">←</span></a>'; } echo '</article>'; }
		echo '</div></section>';

		$comprehensive_score = max( 0, min( 100, absint( $s['comprehensive_score'] ) ) );
		echo '<section id="assessment-comprehensive" class="sazan-assessment-comprehensive sazan-assessment-section"><div class="sazan-assessment-report-visual"><div class="sazan-assessment-paper"><span></span><span></span><span></span><div class="sazan-assessment-mini-chart"><i></i><i></i><i></i><i></i></div><div class="sazan-assessment-mini-radar" style="--assessment-score:' . esc_attr( $comprehensive_score ) . '%"><span aria-hidden="true">✦</span><b>' . esc_html( $comprehensive_score ) . '</b><small>از ۱۰۰</small></div></div></div><div class="sazan-assessment-comprehensive-copy">' . ( ! empty( $s['comprehensive_badge'] ) ? '<span class="sazan-assessment-gold-badge">' . esc_html( $s['comprehensive_badge'] ) . '</span>' : '' ) . $this->heading( $s['comprehensive_title'], $section_tag, 'sazan-assessment-comprehensive-title' ) . '<p>' . esc_html( $s['comprehensive_text'] ) . '</p><ul>';
		foreach ( (array) $s['comprehensive_bullets'] as $bullet ) { echo '<li><span aria-hidden="true">✓</span>' . esc_html( $bullet['text'] ?? '' ) . '</li>'; }
		echo '</ul>'; if ( ! empty( $s['comprehensive_button_label'] ) && ! empty( $comprehensive['url'] ) ) { $this->add_link_attributes( 'assessment-comprehensive', $comprehensive ); echo '<a class="sazan-assessment-button sazan-assessment-button-primary" ' . $this->get_render_attribute_string( 'assessment-comprehensive' ) . '>' . esc_html( $s['comprehensive_button_label'] ) . ' <span aria-hidden="true">←</span></a>'; } echo '</div></section>';

		$this->render_stats( $s['stats_two'], 'sazan-assessment-section sazan-assessment-statbar-secondary' );
		echo '<section id="assessment-process" class="sazan-assessment-section sazan-assessment-centered"><header>' . $this->heading( $s['process_title'], $section_tag, 'sazan-assessment-section-title' ) . '<p>' . esc_html( $s['process_description'] ) . '</p></header><div class="sazan-assessment-process-grid">';
		foreach ( (array) $s['process_items'] as $index => $item ) { $svg = $this->svg( $item['svg_code'] ?? '' ); echo '<article class="sazan-assessment-process-item' . ( $svg ? '' : ' sazan-assessment-process-no-icon' ) . '"><span class="sazan-assessment-process-number">' . esc_html( $item['number'] ?? ( $index + 1 ) ) . '</span>' . ( $svg ? '<span class="sazan-assessment-process-icon">' . $svg . '</span>' : '' ) . '<div><h3>' . esc_html( $item['title'] ) . '</h3><p>' . esc_html( $item['text'] ) . '</p></div></article>'; }
		echo '</div></section>';

		$report_score = max( 0, min( 100, absint( $s['report_score'] ) ) );
		echo '<section id="assessment-report" class="sazan-assessment-report sazan-assessment-section"><div class="sazan-assessment-report-copy">' . $this->heading( $s['report_title'], $section_tag, 'sazan-assessment-section-title' ) . '<p>' . esc_html( $s['report_text'] ) . '</p><ul>';
		foreach ( (array) $s['report_bullets'] as $report_bullet ) { echo '<li>' . esc_html( $report_bullet['text'] ?? '' ) . '</li>'; }
		echo '</ul>';
		if ( ! empty( $s['report_button_label'] ) && ! empty( $report['url'] ) ) { $this->add_link_attributes( 'assessment-report', $report ); echo '<a class="sazan-assessment-button sazan-assessment-button-outline" ' . $this->get_render_attribute_string( 'assessment-report' ) . '>' . esc_html( $s['report_button_label'] ) . ' <span aria-hidden="true">←</span></a>'; }
		echo '</div><div class="sazan-assessment-report-preview"><div class="sazan-assessment-preview-chart"><b>' . esc_html( $s['report_graph_label'] ) . '</b><svg viewBox="0 0 380 120" role="img" aria-label="' . esc_attr( $s['report_graph_label'] ) . '"><path d="M8 30H372M8 60H372M8 90H372"/><polyline points="8,87 68,72 126,82 184,50 242,68 300,42 372,57"/><circle cx="8" cy="87" r="3"/><circle cx="68" cy="72" r="3"/><circle cx="126" cy="82" r="3"/><circle cx="184" cy="50" r="3"/><circle cx="242" cy="68" r="3"/><circle cx="300" cy="42" r="3"/><circle cx="372" cy="57" r="3"/></svg></div><div class="sazan-assessment-preview-score" style="--assessment-score:' . esc_attr( $report_score ) . '%"><div><strong>' . esc_html( $report_score ) . '</strong><small>از ۱۰۰</small></div></div></div></section>';

		echo '<section id="assessment-faq" class="sazan-assessment-faq sazan-assessment-section"><header>' . $this->heading( $s['faq_title'], $section_tag, 'sazan-assessment-section-title' ) . '</header><div class="sazan-assessment-faq-list">';
		foreach ( (array) $s['faqs'] as $index => $item ) { echo '<details' . ( 0 === $index ? ' open' : '' ) . '><summary>' . esc_html( $item['question'] ) . '<span aria-hidden="true">⌄</span></summary><div>' . wp_kses_post( $item['answer'] ) . '</div></details>'; }
		echo '</div></section>';

		echo '<section class="sazan-assessment-bottom-cta"><span class="sazan-assessment-rocket" aria-hidden="true">🚀</span><div><h2>' . esc_html( $s['bottom_title'] ) . '</h2><p>' . esc_html( $s['bottom_text'] ) . '</p></div>'; if ( ! empty( $s['bottom_button_label'] ) && ! empty( $bottom['url'] ) ) { $this->add_link_attributes( 'assessment-bottom', $bottom ); echo '<a class="sazan-assessment-button sazan-assessment-button-primary" ' . $this->get_render_attribute_string( 'assessment-bottom' ) . '>' . esc_html( $s['bottom_button_label'] ) . ' <span aria-hidden="true">←</span></a>'; } echo '</section>';
		echo '</div></main>';

		if ( 'yes' === ( $s['seo_schema'] ?? 'yes' ) && $schema_name ) {
			$graph = array( '@context' => 'https://schema.org', '@graph' => array( array( '@type' => 'WebPage', '@id' => get_permalink() . '#assessment', 'name' => $schema_name, 'description' => $schema_desc, 'url' => get_permalink() ) ) );
			$questions = array();
			foreach ( (array) $s['faqs'] as $item ) { if ( ! empty( $item['question'] ) && ! empty( $item['answer'] ) ) { $questions[] = array( '@type' => 'Question', 'name' => wp_strip_all_tags( $item['question'] ), 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $item['answer'] ) ) ); } }
			if ( $questions ) { $graph['@graph'][] = array( '@type' => 'FAQPage', 'mainEntity' => $questions ); }
			echo '<script type="application/ld+json">' . wp_json_encode( $graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>';
		}
	}
}
