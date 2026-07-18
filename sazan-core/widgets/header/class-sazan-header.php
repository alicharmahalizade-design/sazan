<?php
/**
 * ویجت هدر سازان — هدر هلدینگ (سبز/طلایی) با جستجوی ایجکسی،
 * منوی حساب کاربری، مینی‌کارت بازشو، و چسبانِ ردیف دوم.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Header extends Widget_Base {

	public function get_name() { return 'sazan-header'; }
	public function get_title() { return esc_html__( 'هدر سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-header'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'header', 'هدر', 'menu', 'منو', 'nav', 'search', 'جستجو' ); }

	private function get_menus_options() {
		$menus = wp_get_nav_menus();
		if ( empty( $menus ) ) {
			return array( '0' => esc_html__( '— ابتدا یک منو در «نمایش > منوها» بسازید —', 'sazan-core' ) );
		}
		$out = array( '0' => esc_html__( '— انتخاب منو —', 'sazan-core' ) );
		foreach ( $menus as $m ) { $out[ $m->slug ] = $m->name; }
		return $out;
	}

	protected function register_controls() {
		$this->content_general();
		$this->content_logo();
		$this->content_contact();
		$this->content_search();
		$this->content_categories();
		$this->content_nav();
		$this->content_account();
		$this->content_cart();

		$this->style_general();
		$this->style_logo();
		$this->style_contact();
		$this->style_search();
		$this->style_categories();
		$this->style_nav();
		$this->style_account();
		$this->style_cart();
		$this->style_mobile();
	}

	/* ===================== محتوا ===================== */
	private function content_general() {
		$this->start_controls_section( 'sec_general', array( 'label' => esc_html__( 'تنظیمات کلی', 'sazan-core' ) ) );
		$this->add_control( 'header_skin', array(
			'label' => esc_html__( 'طرح هدر', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'classic',
			'options' => array(
				'classic' => esc_html__( 'کلاسیک (دو ردیفه)', 'sazan-core' ),
				'pro'     => esc_html__( 'حرفه‌ای (شناور / ناوبری قرصی)', 'sazan-core' ),
				'min'     => esc_html__( 'مینیمال (تخت با خط زیر منو)', 'sazan-core' ),
				'neon'    => esc_html__( 'نئونی (گلو و خط متحرک)', 'sazan-core' ),
				'glass'   => esc_html__( 'شیشه‌ای (Glass / شناور بلوردار)', 'sazan-core' ),
			),
		) );
		$this->add_control( 'mobile_menu', array(
			'label' => esc_html__( 'طرح منوی موبایل', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'drawer',
			'prefix_class' => 'sazan-mmenu-',
			'options' => array(
				'drawer'     => esc_html__( 'کشوی کناری', 'sazan-core' ),
				'fullscreen' => esc_html__( 'تمام‌صفحه', 'sazan-core' ),
			),
		) );
		$this->add_control( 'sticky', array(
			'label' => esc_html__( '📌 حالت چسبان (Sticky)', 'sazan-core' ), 'type' => Controls_Manager::SELECT,
			'default' => 'bottom', 'prefix_class' => 'sazan-sticky-', 'separator' => 'before',
			'options' => array(
				'none'   => esc_html__( 'خاموش', 'sazan-core' ),
				'full'   => esc_html__( 'کل هدر بچسبد', 'sazan-core' ),
				'bottom' => esc_html__( 'فقط ردیف دوم بچسبد (دسته‌بندی/منو/سرچ/حساب/سبد)', 'sazan-core' ),
			),
			'description' => esc_html__( 'برای چسباندن نوار پایین هنگام اسکرول، «فقط ردیف دوم» را انتخاب کن. (روی صفحه‌ی واقعی سایت تست کن، نه داخل ویرایشگر.)', 'sazan-core' ),
		) );
		$this->add_control( 'sticky_style', array(
			'label' => esc_html__( '✨ ظاهر حالت چسبان', 'sazan-core' ), 'type' => Controls_Manager::SELECT,
			'default' => 'bar', 'prefix_class' => 'sazan-stystyle-',
			'options' => array(
				'bar'   => esc_html__( 'نوار شناور شیشه‌ای (هدر + نوار چسبان یکی می‌شوند)', 'sazan-core' ),
				'solid' => esc_html__( 'تمام‌عرض کلاسیک', 'sazan-core' ),
			),
			'description' => esc_html__( 'با «نوار شناور شیشه‌ای»، هنگام اسکرول همین هدر به یک نوار چسبانِ زیبا و بلوردار تبدیل می‌شود؛ دیگر به ویجت جداگانه‌ی «نوار چسبان» نیازی نیست.', 'sazan-core' ),
			'condition' => array( 'sticky!' => 'none' ),
		) );
		$this->add_responsive_control( 'container_width', array(
			'label' => esc_html__( 'حداکثر عرض محتوا', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 600, 'max' => 2400 ), '%' => array( 'min' => 50, 'max' => 100 ) ),
			'default' => array( 'unit' => '%', 'size' => 80 ),
			'tablet_default' => array( 'unit' => '%', 'size' => 92 ),
			'mobile_default' => array( 'unit' => '%', 'size' => 100 ),
			'selectors' => array( '{{WRAPPER}} .sazan-header__top-inner, {{WRAPPER}} .sazan-header__bottom-inner' => 'max-width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'glass_blur', array(
			'label' => esc_html__( 'میزان بلور بازشوها و طرح شیشه‌ای (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'size' => 22 ),
			'selectors' => array( '{{WRAPPER}} .sazan-header' => '--sz-blur: {{SIZE}}px;' ),
		) );
		$this->add_responsive_control( 'glass_side', array(
			'label' => esc_html__( 'حاشیه‌ی کناریِ کارتِ شیشه‌ای (px) — ۰ یعنی تمام‌عرض', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 120 ) ), 'default' => array( 'unit' => 'px', 'size' => 12 ),
			'selectors' => array( '{{WRAPPER}} .sazan-header' => '--gs: {{SIZE}}px;' ),
		) );
		$this->add_control( 'full_width', array(
			'label' => esc_html__( 'تمام‌عرض (آزاد از قاب المنتور)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => '', 'prefix_class' => 'sazan-fullw-',
			'description' => esc_html__( 'هدر را تا کل پهنای صفحه باز می‌کند؛ سپس «حداکثر عرض محتوا» اثر می‌گذارد.', 'sazan-core' ),
		) );
		$this->add_control( 'hide_theme_header', array(
			'label' => esc_html__( 'مخفی‌کردن هدر قالب', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => '', 'separator' => 'before',
			'description' => esc_html__( 'هدر پیش‌فرضِ خودِ قالب را پنهان می‌کند تا فقط همین هدر دیده شود.', 'sazan-core' ),
		) );
		$this->add_control( 'theme_header_selector', array(
			'label' => esc_html__( 'سلکتور هدر قالب (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::TEXT,
			'default' => '', 'placeholder' => '#masthead, .site-header',
			'description' => esc_html__( 'اگر با پیش‌فرض پنهان نشد، سلکتور CSS هدر قالب را اینجا بنویس.', 'sazan-core' ),
			'condition' => array( 'hide_theme_header' => 'yes' ),
		) );
		$this->add_control( 'hide_theme_footer', array(
			'label' => esc_html__( 'مخفی‌کردن فوتر قالب', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => '',
			'description' => esc_html__( 'فوتر پیش‌فرضِ خودِ قالب را پنهان می‌کند.', 'sazan-core' ),
		) );
		$this->add_control( 'theme_footer_selector', array(
			'label' => esc_html__( 'سلکتور فوتر قالب (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::TEXT,
			'default' => '', 'placeholder' => '#colophon, .site-footer',
			'description' => esc_html__( 'اگر با پیش‌فرض پنهان نشد، سلکتور CSS فوتر قالب را اینجا بنویس.', 'sazan-core' ),
			'condition' => array( 'hide_theme_footer' => 'yes' ),
		) );
		$this->add_control( 'sticky_hint', array(
			'type' => Controls_Manager::RAW_HTML,
			'raw'  => esc_html__( 'تنظیمات تکمیلیِ چسبان (فاصله، مخفی هوشمند، جمع‌شدن، رنگ و سایه) در بخش «هدر چسبان — تنظیمات بیشتر» پایین است.', 'sazan-core' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->end_controls_section();

		$this->content_sticky();
	}

	private function content_sticky() {
		$this->start_controls_section( 'sec_sticky', array( 'label' => esc_html__( 'هدر چسبان — تنظیمات بیشتر', 'sazan-core' ) ) );
		$this->add_control( 'sticky_more_hint', array(
			'type' => Controls_Manager::RAW_HTML,
			'raw'  => esc_html__( 'کلید اصلی «حالت چسبان» در بخش «تنظیمات کلی» بالا قرار دارد. این‌جا فقط تنظیمات تکمیلی است.', 'sazan-core' ),
			'content_classes' => 'elementor-descriptor',
		) );
		$this->add_control( 'sticky_offset', array(
			'label' => esc_html__( 'فاصله از بالا (px)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 0, 'max' => 200 ) ), 'default' => array( 'size' => 0 ),
			'description' => esc_html__( 'اگر نوار ادمین یا هدرِ دیگری بالای صفحه دارید، اینجا تنظیم کنید.', 'sazan-core' ),
		) );
		$this->add_control( 'sticky_smart', array(
			'label' => esc_html__( 'مخفی هنگام اسکرول به پایین', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => '',
			'description' => esc_html__( 'با اسکرول به پایین پنهان و با اسکرول به بالا ظاهر می‌شود (هوشمند).', 'sazan-core' ),
		) );
		$this->add_control( 'sticky_shrink', array(
			'label' => esc_html__( 'جمع‌شدن هنگام چسبیدن', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
			'description' => esc_html__( 'هنگام چسبیدن کمی فشرده و باریک‌تر می‌شود.', 'sazan-core' ),
		) );
		$this->add_control( 'sticky_bg', array(
			'label' => esc_html__( 'رنگ پس‌زمینه هنگام چسبیدن', 'sazan-core' ), 'type' => Controls_Manager::COLOR,
			'selectors' => array(
				'{{WRAPPER}} .sazan-header.sz-stuck .sazan-header__top, {{WRAPPER}} .sazan-header.sz-stuck .sazan-header__bottom, {{WRAPPER}} .sazan-header.sz-stuck-b .sazan-header__bottom' => 'background: {{VALUE}} !important;',
			),
		) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array(
			'name' => 'sticky_shadow',
			'selector' => '{{WRAPPER}} .sazan-header.sz-stuck, {{WRAPPER}} .sazan-header.sz-stuck-b .sazan-header__bottom',
			'fields_options' => array(
				'box_shadow_type' => array( 'default' => 'yes' ),
				'box_shadow' => array( 'default' => array( 'horizontal' => 0, 'vertical' => 8, 'blur' => 30, 'spread' => -10, 'color' => 'rgba(0,0,0,0.35)' ) ),
			),
		) );
		$this->end_controls_section();
	}

	private function content_logo() {
		$this->start_controls_section( 'sec_logo', array( 'label' => esc_html__( 'لوگو', 'sazan-core' ) ) );
		$this->add_control( 'logo_image', array( 'label' => esc_html__( 'تصویر لوگو', 'sazan-core' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'logo_text', array( 'label' => esc_html__( 'یا متن لوگو', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'مسعود صرامی', 'sazan-core' ) ) );
		$this->add_control( 'logo_icon', array( 'label' => esc_html__( 'آیکن کنار متن', 'sazan-core' ), 'type' => Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-leaf', 'library' => 'fa-solid' ) ) );
		$this->add_control( 'logo_link', array( 'label' => esc_html__( 'لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => home_url( '/' ) ) ) );
		$this->add_control( 'logo_position', array(
			'label' => esc_html__( 'جای لوگو', 'sazan-core' ), 'type' => Controls_Manager::SELECT,
			'default' => 'right', 'prefix_class' => 'sazan-logo-',
			'options' => array( 'right' => esc_html__( 'راست', 'sazan-core' ), 'left' => esc_html__( 'چپ', 'sazan-core' ) ),
		) );
		$this->end_controls_section();
	}

	private function content_contact() {
		$this->start_controls_section( 'sec_contact', array( 'label' => esc_html__( 'تماس', 'sazan-core' ) ) );
		$this->add_control( 'contact_show', array( 'label' => esc_html__( 'نمایش', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'contact_label', array( 'label' => esc_html__( 'برچسب', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'تماس بگیرید', 'sazan-core' ) ) );
		$this->add_control( 'contact_phone', array( 'label' => esc_html__( 'شماره (نمایش)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '۰۹۹-۹۹۹۹۹۹' ) );
		$this->add_control( 'contact_phone_raw', array( 'label' => esc_html__( 'شماره برای tel:', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '099999999' ) );
		$this->add_control( 'contact_icon', array( 'label' => esc_html__( 'آیکن', 'sazan-core' ), 'type' => Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-phone-alt', 'library' => 'fa-solid' ) ) );
		$this->add_control( 'contact_side', array(
			'label' => esc_html__( 'محل بخش تماس', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'left',
			'options' => array( 'left' => esc_html__( 'چپ (کنار لوگو)', 'sazan-core' ), 'right' => esc_html__( 'راست (سمت لوگو)', 'sazan-core' ) ),
			'prefix_class' => 'sazan-contact-',
		) );
		$this->end_controls_section();
	}

	private function content_search() {
		$this->start_controls_section( 'sec_search', array( 'label' => esc_html__( 'جستجوی سریع', 'sazan-core' ) ) );
		$this->add_control( 'search_show', array( 'label' => esc_html__( 'نمایش', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'search_icon', array( 'label' => esc_html__( 'آیکن', 'sazan-core' ), 'type' => Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-search', 'library' => 'fa-solid' ) ) );
		$this->add_control( 'search_placeholder', array( 'label' => esc_html__( 'متن راهنما', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'جستجو کنید...', 'sazan-core' ) ) );
		$this->add_control( 'search_source', array(
			'label' => esc_html__( 'جستجو در', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'any',
			'options' => array(
				'any'     => esc_html__( 'همه‌چیز', 'sazan-core' ),
				'post'    => esc_html__( 'مقالات', 'sazan-core' ),
				'product' => esc_html__( 'محصولات (ووکامرس)', 'sazan-core' ),
				'page'    => esc_html__( 'برگه‌ها', 'sazan-core' ),
			),
		) );
		$this->add_control( 'search_count', array( 'label' => esc_html__( 'تعداد نتایج', 'sazan-core' ), 'type' => Controls_Manager::NUMBER, 'default' => 6, 'min' => 1, 'max' => 12 ) );
		$this->end_controls_section();
	}

	private function content_categories() {
		$this->start_controls_section( 'sec_cats', array( 'label' => esc_html__( 'دکمه دسته بندی ها', 'sazan-core' ) ) );
		$this->add_control( 'cats_show', array( 'label' => esc_html__( 'نمایش', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'cats_text', array( 'label' => esc_html__( 'متن', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'دسته‌بندی‌ها', 'sazan-core' ) ) );
		$this->add_control( 'cats_icon', array( 'label' => esc_html__( 'آیکن', 'sazan-core' ), 'type' => Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-th-large', 'library' => 'fa-solid' ) ) );
		$this->add_control( 'cats_chevron', array( 'label' => esc_html__( 'نمایش فلش', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'cats_menu', array( 'label' => esc_html__( 'منوی دراپ‌داون', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_menus_options(), 'default' => '0' ) );
		$this->add_control( 'cats_accordion', array( 'label' => esc_html__( 'حالت آکاردئونی (زیرمنوها با کلیک باز شوند)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->end_controls_section();
	}

	private function content_nav() {
		$this->start_controls_section( 'sec_nav', array( 'label' => esc_html__( 'منوی اصلی', 'sazan-core' ) ) );
		$this->add_control( 'nav_menu', array( 'label' => esc_html__( 'انتخاب منو', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_menus_options(), 'default' => '0' ) );
		$this->add_control( 'nav_chevron', array( 'label' => esc_html__( 'فلش روی آیتم‌های دارای زیرمنو', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'prefix_class' => 'sazan-navchev-' ) );
		$this->end_controls_section();
	}

	private function content_account() {
		$this->start_controls_section( 'sec_account', array( 'label' => esc_html__( 'حساب کاربری', 'sazan-core' ) ) );
		$this->add_control( 'acc_show', array( 'label' => esc_html__( 'نمایش', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'acc_text', array( 'label' => esc_html__( 'متن (مهمان)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'ورود/عضویت', 'sazan-core' ) ) );
		$this->add_control( 'acc_link', array( 'label' => esc_html__( 'لینک صفحه ورود', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => wp_login_url() ) ) );
		$this->add_control( 'acc_icon', array( 'label' => esc_html__( 'آیکن', 'sazan-core' ), 'type' => Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-user', 'library' => 'fa-solid' ) ) );
		$this->add_control( 'acc_logged_heading', array( 'label' => esc_html__( 'حالت ورود کرده', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'acc_greeting', array( 'label' => esc_html__( 'متن خوش‌آمد', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'سلام', 'sazan-core' ), 'description' => esc_html__( 'مثلاً: سلام علی', 'sazan-core' ) ) );
		$this->add_control( 'acc_menu', array( 'label' => esc_html__( 'منوی حساب (بازشو)', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_menus_options(), 'default' => '0' ) );
		$this->add_control( 'acc_trigger', array(
			'label' => esc_html__( 'باز شدن منو با', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'hover',
			'options' => array( 'hover' => esc_html__( 'هاور', 'sazan-core' ), 'click' => esc_html__( 'کلیک', 'sazan-core' ) ),
		) );
		$this->add_control( 'acc_account_link', array( 'label' => esc_html__( 'لینک نام کاربر', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '' ), 'description' => esc_html__( 'مثلاً صفحه حساب کاربری.', 'sazan-core' ) ) );
		$this->end_controls_section();
	}

	private function content_cart() {
		$this->start_controls_section( 'sec_cart', array( 'label' => esc_html__( 'سبد خرید', 'sazan-core' ) ) );
		$this->add_control( 'cart_show', array( 'label' => esc_html__( 'نمایش', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'cart_icon', array( 'label' => esc_html__( 'آیکن', 'sazan-core' ), 'type' => Controls_Manager::ICONS, 'default' => array( 'value' => 'fas fa-shopping-cart', 'library' => 'fa-solid' ) ) );
		$this->add_control( 'cart_link', array( 'label' => esc_html__( 'لینک سبد', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '#' ) ) );
		$this->add_control( 'cart_count', array( 'label' => esc_html__( 'تعداد (ثابت)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '0', 'description' => esc_html__( 'اگر ووکامرس فعال باشد، تعداد واقعی جایگزین می‌شود.', 'sazan-core' ) ) );
		$this->add_control( 'cart_show_count', array( 'label' => esc_html__( 'نمایش شمارنده', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'cart_dropdown', array( 'label' => esc_html__( 'مینی‌کارت بازشو', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'cart_trigger', array(
			'label' => esc_html__( 'باز شدن با', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'hover',
			'options' => array( 'hover' => esc_html__( 'هاور', 'sazan-core' ), 'click' => esc_html__( 'کلیک', 'sazan-core' ) ),
			'condition' => array( 'cart_dropdown' => 'yes' ),
		) );
		$this->end_controls_section();
	}

	/* ===================== استایل ===================== */
	private function style_general() {
		$this->start_controls_section( 'style_general', array( 'label' => esc_html__( 'عمومی', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Box_Shadow::get_type(), array( 'name' => 'header_shadow', 'selector' => '{{WRAPPER}} .sazan-header' ) );

		$this->add_control( 'h_top', array( 'label' => esc_html__( 'نوار بالا', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name' => 'top_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .sazan-header__top',
			'fields_options' => array( 'background' => array( 'default' => 'classic' ), 'color' => array( 'default' => '#0c1c28' ) ),
		) );
		$this->add_responsive_control( 'top_padding', array(
			'label' => esc_html__( 'فاصله داخلی', 'sazan-core' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ),
			'default' => array( 'top' => 16, 'right' => 40, 'bottom' => 16, 'left' => 40, 'unit' => 'px', 'isLinked' => false ),
			'selectors' => array( '{{WRAPPER}} .sazan-header__top-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );

		$this->add_control( 'h_bottom', array( 'label' => esc_html__( 'نوار پایین', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name' => 'bottom_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .sazan-header__bottom',
			'fields_options' => array( 'background' => array( 'default' => 'classic' ), 'color' => array( 'default' => '#0f2735' ) ),
		) );
		$this->add_responsive_control( 'bottom_radius', array(
			'label' => esc_html__( 'گردی گوشه‌های پایین', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px' ),
			'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'default' => array( 'unit' => 'px', 'size' => 22 ),
			'selectors' => array( '{{WRAPPER}} .sazan-header__bottom' => 'border-bottom-left-radius: {{SIZE}}{{UNIT}}; border-bottom-right-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'bottom_padding', array(
			'label' => esc_html__( 'فاصله داخلی', 'sazan-core' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ),
			'default' => array( 'top' => 12, 'right' => 28, 'bottom' => 12, 'left' => 28, 'unit' => 'px', 'isLinked' => false ),
			'selectors' => array( '{{WRAPPER}} .sazan-header__bottom-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
		) );
		$this->add_responsive_control( 'bottom_min_h', array(
			'label' => esc_html__( 'حداقل ارتفاع', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 40, 'max' => 140 ) ), 'default' => array( 'unit' => 'px', 'size' => 72 ),
			'selectors' => array( '{{WRAPPER}} .sazan-header__bottom-inner' => 'min-height: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();
	}

	private function style_logo() {
		$this->start_controls_section( 'style_logo', array( 'label' => esc_html__( 'لوگو', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'logo_width', array(
			'label' => esc_html__( 'عرض تصویر', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ),
			'range' => array( 'px' => array( 'min' => 40, 'max' => 400 ) ), 'default' => array( 'unit' => 'px', 'size' => 140 ),
			'selectors' => array( '{{WRAPPER}} .sazan-header__logo img' => 'width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'logo_typo', 'label' => esc_html__( 'تایپوگرافی متن', 'sazan-core' ), 'selector' => '{{WRAPPER}} .sazan-header__logo-text' ) );
		$this->add_control( 'logo_color', array( 'label' => esc_html__( 'رنگ متن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__logo-text' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'logo_icon_color', array( 'label' => esc_html__( 'رنگ آیکن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__logo-icon' => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'logo_icon_size', array( 'label' => esc_html__( 'اندازه آیکن', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 10, 'max' => 60 ) ), 'default' => array( 'unit' => 'px', 'size' => 30 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__logo-icon' => 'font-size: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'logo_gap', array( 'label' => esc_html__( 'فاصله آیکن تا متن', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 30 ) ), 'default' => array( 'unit' => 'px', 'size' => 10 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__logo' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}

	private function style_contact() {
		$this->start_controls_section( 'style_contact', array( 'label' => esc_html__( 'تماس', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'contact_label_color', array( 'label' => esc_html__( 'رنگ برچسب', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__c-label' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'contact_label_typo', 'label' => esc_html__( 'تایپوگرافی برچسب', 'sazan-core' ), 'selector' => '{{WRAPPER}} .sazan-header__c-label' ) );
		$this->add_control( 'contact_phone_color', array( 'label' => esc_html__( 'رنگ شماره', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__c-phone' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'contact_phone_typo', 'label' => esc_html__( 'تایپوگرافی شماره', 'sazan-core' ), 'selector' => '{{WRAPPER}} .sazan-header__c-phone' ) );
		$this->add_control( 'contact_icon_color', array( 'label' => esc_html__( 'رنگ آیکن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__c-icon' => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'contact_icon_size', array( 'label' => esc_html__( 'اندازه آیکن', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 12, 'max' => 50 ) ), 'default' => array( 'unit' => 'px', 'size' => 22 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__c-icon' => 'font-size: {{SIZE}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}

	private function style_search() {
		$this->start_controls_section( 'style_search', array( 'label' => esc_html__( 'جستجو', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'search_toggle_color', array( 'label' => esc_html__( 'رنگ آیکن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__search-toggle' => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'search_toggle_size', array( 'label' => esc_html__( 'اندازه آیکن', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 12, 'max' => 40 ) ), 'default' => array( 'unit' => 'px', 'size' => 20 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__search-toggle' => 'font-size: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'h_search_panel', array( 'label' => esc_html__( 'پنل جستجو', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'search_panel_bg', array( 'label' => esc_html__( 'پس‌زمینه پنل', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0c1c28', 'selectors' => array( '{{WRAPPER}} .sazan-header__search-panel' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'search_field_bg', array( 'label' => esc_html__( 'پس‌زمینه فیلد', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(255,255,255,0.08)', 'selectors' => array( '{{WRAPPER}} .sazan-header__search-input' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'search_text_color', array( 'label' => esc_html__( 'رنگ متن فیلد', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__search-input' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'search_results_bg', array( 'label' => esc_html__( 'پس‌زمینه نتایج', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0f2735', 'selectors' => array( '{{WRAPPER}} .sazan-header__search-results' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'search_results_color', array( 'label' => esc_html__( 'رنگ نتایج', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#e8f0f4', 'selectors' => array( '{{WRAPPER}} .sazan-header__search-results a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'search_results_hover', array( 'label' => esc_html__( 'هاور نتایج', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__search-results a:hover' => 'background: rgba(255,255,255,.05); color: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	private function style_categories() {
		$this->start_controls_section( 'style_cats', array( 'label' => esc_html__( 'دکمه دسته بندی ها', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'cats_typo', 'selector' => '{{WRAPPER}} .sazan-header__cat-btn' ) );
		$this->add_control( 'cats_color', array( 'label' => esc_html__( 'رنگ متن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__cat-btn' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'cats_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .sazan-header__cat-btn' ) );
		$this->add_control( 'h_cat_badge', array( 'label' => esc_html__( 'بَج آیکن', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'cats_icon_color', array( 'label' => esc_html__( 'رنگ آیکن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__cat-icon' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'cats_icon_bg', array( 'label' => esc_html__( 'پس‌زمینه دایره', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__cat-icon' => 'background: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'cats_icon_circle', array( 'label' => esc_html__( 'قطر دایره', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 18, 'max' => 56 ) ), 'default' => array( 'unit' => 'px', 'size' => 32 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__cat-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'cats_icon_size', array( 'label' => esc_html__( 'اندازه آیکن', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 8, 'max' => 30 ) ), 'default' => array( 'unit' => 'px', 'size' => 15 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__cat-icon' => 'font-size: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'cats_chevron_color', array( 'label' => esc_html__( 'رنگ فلش', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(255,255,255,.85)', 'selectors' => array( '{{WRAPPER}} .sazan-header__cat-chev' => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'cats_padding', array( 'label' => esc_html__( 'فاصله داخلی', 'sazan-core' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'default' => array( 'top' => 6, 'right' => 8, 'bottom' => 6, 'left' => 18, 'unit' => 'px', 'isLinked' => false ), 'selectors' => array( '{{WRAPPER}} .sazan-header__cat-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'cats_radius', array( 'label' => esc_html__( 'گردی گوشه‌ها', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 80 ) ), 'default' => array( 'unit' => 'px', 'size' => 50 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__cat-btn' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'h_dd', array( 'label' => esc_html__( 'دراپ‌داون', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'dd_bg', array( 'label' => esc_html__( 'پس‌زمینه', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0f2735', 'selectors' => array( '{{WRAPPER}} .sazan-header__cat-dropdown' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'dd_color', array( 'label' => esc_html__( 'رنگ آیتم', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__cat-dropdown a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'dd_color_h', array( 'label' => esc_html__( 'رنگ آیتم (هاور)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__cat-dropdown a:hover' => 'color: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	private function style_nav() {
		$this->start_controls_section( 'style_nav', array( 'label' => esc_html__( 'منوی اصلی', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'nav_typo', 'selector' => '{{WRAPPER}} .sazan-header__nav > ul > li > a' ) );
		$this->add_responsive_control( 'nav_gap', array( 'label' => esc_html__( 'فاصله بین آیتم‌ها', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 60 ) ), 'default' => array( 'unit' => 'px', 'size' => 26 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__nav > ul' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'nav_item_pad', array( 'label' => esc_html__( 'فاصله داخلی آیتم', 'sazan-core' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .sazan-header__nav > ul > li > a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->start_controls_tabs( 'nav_tabs' );
		$this->start_controls_tab( 'nav_n', array( 'label' => esc_html__( 'عادی', 'sazan-core' ) ) );
		$this->add_control( 'nav_color', array( 'label' => esc_html__( 'رنگ', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__nav > ul > li > a' => 'color: {{VALUE}};', '{{WRAPPER}} .sazan-header__nav .menu-item-has-children > a::after' => 'border-color: {{VALUE}};' ) ) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'nav_h', array( 'label' => esc_html__( 'هاور', 'sazan-core' ) ) );
		$this->add_control( 'nav_color_h', array( 'label' => esc_html__( 'رنگ', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__nav > ul > li > a:hover' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'nav_hover_bg', array( 'label' => esc_html__( 'پس‌زمینه هاور', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-header__nav > ul > li > a:hover' => 'background: {{VALUE}};' ) ) );
		$this->end_controls_tab();
		$this->start_controls_tab( 'nav_a', array( 'label' => esc_html__( 'فعال', 'sazan-core' ) ) );
		$this->add_control( 'nav_color_a', array( 'label' => esc_html__( 'رنگ', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__nav > ul > li.current-menu-item > a' => 'color: {{VALUE}};' ) ) );
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->add_control( 'h_sub', array( 'label' => esc_html__( 'زیرمنو', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'sub_bg', array( 'label' => esc_html__( 'پس‌زمینه', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0f2735', 'selectors' => array( '{{WRAPPER}} .sazan-header__nav .sub-menu' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'sub_color', array( 'label' => esc_html__( 'رنگ آیتم', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__nav .sub-menu a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'sub_color_h', array( 'label' => esc_html__( 'رنگ آیتم (هاور)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__nav .sub-menu a:hover' => 'color: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	private function style_account() {
		$this->start_controls_section( 'style_account', array( 'label' => esc_html__( 'حساب کاربری', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'acc_typo', 'selector' => '{{WRAPPER}} .sazan-header__account' ) );
		$this->add_control( 'acc_color', array( 'label' => esc_html__( 'رنگ متن/آیکن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__account' => 'color: {{VALUE}};' ) ) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name' => 'acc_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .sazan-header__account',
			'fields_options' => array( 'background' => array( 'default' => 'classic' ), 'color' => array( 'default' => 'rgba(255,255,255,0.06)' ) ),
		) );
		$this->add_responsive_control( 'acc_padding', array( 'label' => esc_html__( 'فاصله داخلی', 'sazan-core' ), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'default' => array( 'top' => 9, 'right' => 18, 'bottom' => 9, 'left' => 18, 'unit' => 'px', 'isLinked' => false ), 'selectors' => array( '{{WRAPPER}} .sazan-header__account' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'acc_radius', array( 'label' => esc_html__( 'گردی گوشه‌ها', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 80 ) ), 'default' => array( 'unit' => 'px', 'size' => 50 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__account' => 'border-radius: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'h_accdd', array( 'label' => esc_html__( 'منوی بازشو', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'accdd_bg', array( 'label' => esc_html__( 'پس‌زمینه', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0f2735', 'selectors' => array( '{{WRAPPER}} .sazan-header__acc-dropdown' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'accdd_color', array( 'label' => esc_html__( 'رنگ آیتم', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__acc-dropdown a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'accdd_color_h', array( 'label' => esc_html__( 'رنگ آیتم (هاور)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__acc-dropdown a:hover' => 'color: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	private function style_cart() {
		$this->start_controls_section( 'style_cart', array( 'label' => esc_html__( 'سبد خرید', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_responsive_control( 'cart_circle', array( 'label' => esc_html__( 'قطر دایره', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 30, 'max' => 80 ) ), 'default' => array( 'unit' => 'px', 'size' => 52 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__cart' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_group_control( Group_Control_Background::get_type(), array( 'name' => 'cart_bg', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .sazan-header__cart' ) );
		$this->add_control( 'cart_icon_color', array( 'label' => esc_html__( 'رنگ آیکن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0c1c28', 'selectors' => array( '{{WRAPPER}} .sazan-header__cart-icon' => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'cart_icon_size', array( 'label' => esc_html__( 'اندازه آیکن', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 12, 'max' => 40 ) ), 'default' => array( 'unit' => 'px', 'size' => 20 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__cart-icon' => 'font-size: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'cart_count_bg', array( 'label' => esc_html__( 'پس‌زمینه شمارنده', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0c1c28', 'selectors' => array( '{{WRAPPER}} .sazan-header__cart-count' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'cart_count_color', array( 'label' => esc_html__( 'رنگ شمارنده', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__cart-count' => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'cart_gap', array( 'label' => esc_html__( 'فاصله اکشن‌ها', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ), 'default' => array( 'unit' => 'px', 'size' => 14 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__actions' => 'gap: {{SIZE}}{{UNIT}};' ) ) );
		$this->add_control( 'h_cartdd', array( 'label' => esc_html__( 'مینی‌کارت', 'sazan-core' ), 'type' => Controls_Manager::HEADING, 'separator' => 'before' ) );
		$this->add_control( 'cartdd_bg', array( 'label' => esc_html__( 'پس‌زمینه', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0f2735', 'selectors' => array( '{{WRAPPER}} .sazan-header__cart-dropdown' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'cartdd_color', array( 'label' => esc_html__( 'رنگ متن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#e8f0f4', 'selectors' => array( '{{WRAPPER}} .sazan-header__cart-dropdown' => '--cartdd-color: {{VALUE}};' ) ) );
		$this->add_control( 'cartdd_price', array( 'label' => esc_html__( 'رنگ قیمت', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00b6f1', 'selectors' => array( '{{WRAPPER}} .sazan-header__cart-dropdown' => '--cartdd-price: {{VALUE}};' ) ) );
		$this->end_controls_section();
	}

	private function style_mobile() {
		$this->start_controls_section( 'style_mobile', array( 'label' => esc_html__( 'موبایل (منوی کناری)', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'm_note', array( 'type' => Controls_Manager::RAW_HTML, 'raw' => esc_html__( 'در عرض کمتر از ۱۰۲۴ پیکسل، نوار پایین مخفی و آیکن همبرگر نمایش داده می‌شود.', 'sazan-core' ), 'content_classes' => 'elementor-descriptor' ) );
		$this->add_control( 'burger_color', array( 'label' => esc_html__( 'رنگ همبرگر', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__burger span' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'drawer_bg', array( 'label' => esc_html__( 'پس‌زمینه کشو', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0c1c28', 'selectors' => array( '{{WRAPPER}} .sazan-header__drawer' => 'background: {{VALUE}};' ) ) );
		$this->add_control( 'drawer_color', array( 'label' => esc_html__( 'رنگ متن کشو', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-header__drawer, {{WRAPPER}} .sazan-header__drawer a' => 'color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'drawer_width', array( 'label' => esc_html__( 'عرض کشو', 'sazan-core' ), 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 200, 'max' => 480 ) ), 'default' => array( 'unit' => 'px', 'size' => 300 ), 'selectors' => array( '{{WRAPPER}} .sazan-header__drawer' => 'width: {{SIZE}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}

	/* ===================== رندر ===================== */
	private function icon( $icon ) {
		if ( ! is_array( $icon ) || empty( $icon['value'] ) ) { return; }
		Icons_Manager::render_icon( $icon, array( 'aria-hidden' => 'true' ) );
	}

	private function render_menu( $slug ) {
		if ( empty( $slug ) || '0' === $slug ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<span class="sazan-header__menu-empty">' . esc_html__( 'منویی انتخاب نشده است', 'sazan-core' ) . '</span>';
			}
			return;
		}
		wp_nav_menu( array( 'menu' => $slug, 'container' => false, 'menu_class' => 'sazan-menu', 'fallback_cb' => false, 'echo' => true ) );
	}

	private function cart_count( $fallback ) {
		if ( function_exists( 'WC' ) ) {
			$wc = WC();
			if ( $wc && isset( $wc->cart ) && $wc->cart ) {
				return $wc->cart->get_cart_contents_count();
			}
		}
		return $fallback;
	}

	protected function render() {
		try {
			$this->render_header();
		} catch ( \Throwable $e ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:14px;color:#c33;background:#fee;border-radius:8px;direction:rtl">';
				echo esc_html__( 'خطا در نمایش هدر:', 'sazan-core' ) . ' ' . esc_html( $e->getMessage() );
				echo '</div>';
			}
		}
	}

	private function render_header() {
		$s    = $this->get_settings_for_display();
		$chev = '<svg class="sazan-chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 6l-6 6 6 6"/></svg>';

		// مخفی‌کردن هدر/فوترِ خودِ قالب (اختیاری و قابل‌کنترل)
		$hide_css = '';
		if ( 'yes' === $s['hide_theme_header'] ) {
			$sel = '#masthead, .site-header, header.site-header, #site-header';
			$extra = trim( str_replace( array( '<', '>' ), '', (string) $s['theme_header_selector'] ) );
			if ( $extra ) { $sel .= ', ' . $extra; }
			$hide_css .= $sel . '{display:none !important;}';
		}
		if ( 'yes' === $s['hide_theme_footer'] ) {
			$sel = '#colophon, .site-footer, footer.site-footer, #site-footer';
			$extra = trim( str_replace( array( '<', '>' ), '', (string) $s['theme_footer_selector'] ) );
			if ( $extra ) { $sel .= ', ' . $extra; }
			$hide_css .= $sel . '{display:none !important;}';
		}
		if ( $hide_css ) { echo '<style id="sazan-hide-theme-chrome">' . $hide_css . '</style>'; }

		$skin = in_array( $s['header_skin'], array( 'pro', 'min', 'neon', 'glass' ), true ) ? $s['header_skin'] : 'classic';
		$st_mode   = in_array( $s['sticky'], array( 'full', 'bottom' ), true ) ? $s['sticky'] : 'none';
		$st_style  = ( isset( $s['sticky_style'] ) && 'solid' === $s['sticky_style'] ) ? 'solid' : 'bar';
		$st_off    = isset( $s['sticky_offset']['size'] ) ? (int) $s['sticky_offset']['size'] : 0;
		$st_smart  = ( 'yes' === $s['sticky_smart'] ) ? '1' : '0';
		$st_shrink = ( 'yes' === $s['sticky_shrink'] ) ? '1' : '0';
		$st_bg     = ! empty( $s['sticky_bg'] ) ? $s['sticky_bg'] : '';
		printf(
			'<div class="sazan-header skin-%1$s" data-sticky="%2$s" data-sticky-style="%3$s" data-sticky-offset="%4$d" data-sticky-smart="%5$s" data-sticky-shrink="%6$s" data-sticky-bg="%7$s">',
			esc_attr( $skin ), esc_attr( $st_mode ), esc_attr( $st_style ), $st_off, esc_attr( $st_smart ), esc_attr( $st_shrink ), esc_attr( $st_bg )
		);

		/* ---- بالا ---- */
		echo '<div class="sazan-header__top"><div class="sazan-header__top-inner">';
		$logo_href = ! empty( $s['logo_link']['url'] ) ? $s['logo_link']['url'] : '';
		$tag_open  = $logo_href ? '<a class="sazan-header__logo" href="' . esc_url( $logo_href ) . '">' : '<span class="sazan-header__logo">';
		$tag_close = $logo_href ? '</a>' : '</span>';
		echo $tag_open;
		if ( ! empty( $s['logo_image']['url'] ) ) {
			printf( '<img src="%s" alt="%s">', esc_url( $s['logo_image']['url'] ), esc_attr( $s['logo_text'] ) );
		} else {
			echo '<span class="sazan-header__logo-text">' . esc_html( $s['logo_text'] ) . '</span>';
			echo '<span class="sazan-header__logo-icon">'; $this->icon( $s['logo_icon'] ); echo '</span>';
		}
		echo $tag_close;

		echo '<div class="sazan-header__top-left">';
		if ( 'yes' === $s['contact_show'] ) {
			$tel = preg_replace( '/[^0-9+]/', '', (string) $s['contact_phone_raw'] );
			echo '<div class="sazan-header__contact">';
			echo '<div class="sazan-header__c-texts"><span class="sazan-header__c-label">' . esc_html( $s['contact_label'] ) . '</span>';
			echo '<a class="sazan-header__c-phone" href="tel:' . esc_attr( $tel ) . '">' . esc_html( $s['contact_phone'] ) . '</a></div>';
			echo '<span class="sazan-header__c-icon">'; $this->icon( $s['contact_icon'] ); echo '</span>';
			echo '</div>';
		}
		echo '<button type="button" class="sazan-header__burger" aria-label="menu"><span></span><span></span><span></span></button>';
		echo '</div>';
		echo '</div></div>';

		/* ---- پایین ---- */
		echo '<div class="sazan-header__bottom"><div class="sazan-header__bottom-inner">';
		if ( 'yes' === $s['cats_show'] ) {
			echo '<div class="sazan-header__categories"><button type="button" class="sazan-header__cat-btn">';
			echo '<span class="sazan-header__cat-icon">'; $this->icon( $s['cats_icon'] ); echo '</span>';
			echo '<span class="sazan-header__cat-text">' . esc_html( $s['cats_text'] ) . '</span>';
			if ( 'yes' === $s['cats_chevron'] ) { echo '<span class="sazan-header__cat-chev">' . $chev . '</span>'; }
			echo '</button>';
			echo '<div class="sazan-header__cat-dropdown' . ( 'yes' === $s['cats_accordion'] ? ' sazan-cat-accordion' : '' ) . '">'; $this->render_menu( $s['cats_menu'] ); echo '</div>';
			echo '</div>';
		}
		echo '<nav class="sazan-header__nav">'; $this->render_menu( $s['nav_menu'] ); echo '</nav>';
		echo '<div class="sazan-header__actions">';
		$this->render_search( $s );
		$this->render_account( $s );
		$this->render_cart( $s );
		echo '</div>';
		echo '</div></div>';

		/* ---- کشوی موبایل ---- */
		echo '<div class="sazan-header__overlay"></div>';
		echo '<aside class="sazan-header__drawer">';
		echo '<button type="button" class="sazan-header__drawer-close" aria-label="close">&times;</button>';
		echo '<div class="sazan-header__drawer-logo">';
		if ( ! empty( $s['logo_image']['url'] ) ) {
			printf( '<img src="%s" alt="%s">', esc_url( $s['logo_image']['url'] ), esc_attr( $s['logo_text'] ) );
		} else {
			echo '<span class="sazan-header__logo-text">' . esc_html( $s['logo_text'] ) . '</span>';
		}
		echo '</div>';
		echo '<div class="sazan-header__drawer-actions">';
		$this->render_search( $s );
		$this->render_account( $s );
		$this->render_cart( $s );
		echo '</div>';
		if ( 'yes' === $s['cats_show'] ) {
			echo '<div class="sazan-header__drawer-title">' . esc_html( $s['cats_text'] ) . '</div>';
			echo '<nav class="sazan-header__nav sazan-header__drawer-nav">'; $this->render_menu( $s['cats_menu'] ); echo '</nav>';
		}
		echo '<nav class="sazan-header__nav sazan-header__drawer-nav">'; $this->render_menu( $s['nav_menu'] ); echo '</nav>';
		if ( 'yes' === $s['contact_show'] ) {
			$tel = preg_replace( '/[^0-9+]/', '', (string) $s['contact_phone_raw'] );
			echo '<a class="sazan-header__drawer-phone" href="tel:' . esc_attr( $tel ) . '">' . esc_html( $s['contact_label'] ) . ': ' . esc_html( $s['contact_phone'] ) . '</a>';
		}
		echo '</aside>';

		echo '</div>'; // header
	}

	private function render_search( $s ) {
		if ( 'yes' !== $s['search_show'] ) { return; }
		echo '<div class="sazan-header__search">';
		echo '<button type="button" class="sazan-header__search-toggle" aria-label="search">'; $this->icon( $s['search_icon'] ); echo '</button>';
		echo '<div class="sazan-header__search-panel" data-source="' . esc_attr( $s['search_source'] ) . '" data-count="' . esc_attr( (int) $s['search_count'] ) . '">';
		echo '<form class="sazan-header__search-form" role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '">';
		echo '<input type="search" name="s" class="sazan-header__search-input" placeholder="' . esc_attr( $s['search_placeholder'] ) . '" autocomplete="off">';
		echo '</form>';
		echo '<div class="sazan-header__search-results"></div>';
		echo '</div></div>';
	}

	private function render_account( $s ) {
		if ( 'yes' !== $s['acc_show'] ) { return; }
		$trigger = ( 'click' === $s['acc_trigger'] ) ? 'click' : 'hover';

		if ( is_user_logged_in() ) {
			$u    = wp_get_current_user();
			$name = $u->display_name ? $u->display_name : $u->user_login;
			$href = ! empty( $s['acc_account_link']['url'] ) ? $s['acc_account_link']['url'] : ( ! empty( $s['acc_link']['url'] ) ? $s['acc_link']['url'] : '#' );
			echo '<div class="sazan-header__account-wrap" data-trigger="' . esc_attr( $trigger ) . '">';
			echo '<a class="sazan-header__account" href="' . esc_url( $href ) . '">';
			echo '<span class="sazan-header__acc-text">' . esc_html( trim( $s['acc_greeting'] . ' ' . $name ) ) . '</span>';
			echo '<span class="sazan-header__acc-icon">'; $this->icon( $s['acc_icon'] ); echo '</span>';
			echo '</a>';
			if ( ! empty( $s['acc_menu'] ) && '0' !== $s['acc_menu'] ) {
				echo '<div class="sazan-header__acc-dropdown">'; $this->render_menu( $s['acc_menu'] ); echo '</div>';
			}
			echo '</div>';
		} else {
			$href = ! empty( $s['acc_link']['url'] ) ? $s['acc_link']['url'] : '#';
			echo '<a class="sazan-header__account" href="' . esc_url( $href ) . '">';
			echo '<span class="sazan-header__acc-text">' . esc_html( $s['acc_text'] ) . '</span>';
			echo '<span class="sazan-header__acc-icon">'; $this->icon( $s['acc_icon'] ); echo '</span>';
			echo '</a>';
		}
	}

	private function render_cart( $s ) {
		if ( 'yes' !== $s['cart_show'] ) { return; }
		$href    = ! empty( $s['cart_link']['url'] ) ? $s['cart_link']['url'] : '#';
		$count   = $this->cart_count( $s['cart_count'] );
		$dd      = ( 'yes' === $s['cart_dropdown'] );
		$trigger = ( 'click' === $s['cart_trigger'] ) ? 'click' : 'hover';

		echo '<div class="sazan-header__cart-wrap" data-trigger="' . esc_attr( $trigger ) . '">';
		echo '<a class="sazan-header__cart" href="' . esc_url( $href ) . '">';
		if ( 'yes' === $s['cart_show_count'] ) { echo '<span class="sazan-header__cart-count">' . esc_html( $count ) . '</span>'; }
		echo '<span class="sazan-header__cart-icon">'; $this->icon( $s['cart_icon'] ); echo '</span>';
		echo '</a>';
		if ( $dd ) {
			echo '<div class="sazan-header__cart-dropdown">';
			if ( function_exists( 'woocommerce_mini_cart' ) ) {
				echo '<div class="widget_shopping_cart_content">';
				\Sazan\Mini_Cart::render();
				echo '</div>';
			} else {
				echo '<p class="sazan-header__cart-empty">' . esc_html__( 'سبد خرید شما خالی است.', 'sazan-core' ) . '</p>';
			}
			echo '</div>';
		}
		echo '</div>';
	}
}
