<?php
/**
 * ویجت «فوتر سازان» — فوترِ حرفه‌ای، شیشه‌ای و هم‌برندِ هدر.
 * شاملِ بلوکِ برند + شبکه‌های اجتماعی، خبرنامه، ستون‌های منو،
 * اطلاعاتِ تماس و نوارِ پایین (کپی‌رایت + برو بالا). آیکن‌ها SVG توکار.
 *
 * @package Sazan\Widgets
 */

namespace Sazan\Widgets;

use Sazan\Base\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Footer extends Widget_Base {

	public function get_name() { return 'sazan-footer'; }
	public function get_title() { return esc_html__( 'فوتر سازان', 'sazan-core' ); }
	public function get_icon() { return 'eicon-footer'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'footer', 'فوتر', 'پاورقی', 'social', 'خبرنامه' ); }

	private function get_menus_options() {
		$menus = wp_get_nav_menus();
		if ( empty( $menus ) ) {
			return array( '0' => esc_html__( '— ابتدا یک منو در «نمایش > منوها» بسازید —', 'sazan-core' ) );
		}
		$out = array( '0' => esc_html__( '— انتخاب منو —', 'sazan-core' ) );
		foreach ( $menus as $m ) { $out[ $m->slug ] = $m->name; }
		return $out;
	}

	private function social_options() {
		return array(
			'instagram' => 'اینستاگرام',
			'telegram'  => 'تلگرام',
			'whatsapp'  => 'واتساپ',
			'youtube'   => 'یوتیوب',
			'aparat'    => 'آپارات',
			'linkedin'  => 'لینکدین',
			'twitter'   => 'ایکس (توییتر)',
			'eitaa'     => 'ایتا',
			'email'     => 'ایمیل',
			'phone'     => 'تماس',
		);
	}

	protected function register_controls() {
		$this->content_general();
		$this->content_brand();
		$this->content_social();
		$this->content_newsletter();
		$this->content_columns();
		$this->content_contact();
		$this->content_bottom();

		$this->style_general();
		$this->style_typography();
	}

	/* ===================== محتوا ===================== */
	private function content_general() {
		$this->start_controls_section( 'sec_general', array( 'label' => esc_html__( 'تنظیمات کلی', 'sazan-core' ) ) );
		$this->add_control( 'skin', array(
			'label' => esc_html__( 'طرح فوتر', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'glass',
			'prefix_class' => 'sazan-fskin-',
			'options' => array(
				'glass'  => esc_html__( 'شیشه‌ای (بلوردار و لاکچری)', 'sazan-core' ),
				'solid'  => esc_html__( 'یکدست تیره', 'sazan-core' ),
				'minimal'=> esc_html__( 'مینیمال', 'sazan-core' ),
			),
		) );
		$this->add_control( 'glow', array(
			'label' => esc_html__( 'هاله‌ی نورانیِ برند (بالای فوتر)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
		) );
		$this->add_control( 'top_border', array(
			'label' => esc_html__( 'خطِ گرادینتیِ بالای فوتر', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER,
			'return_value' => 'yes', 'default' => 'yes',
		) );
		$this->add_responsive_control( 'container_width', array(
			'label' => esc_html__( 'حداکثر عرض محتوا', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 600, 'max' => 2400 ), '%' => array( 'min' => 50, 'max' => 100 ) ),
			'default' => array( 'unit' => '%', 'size' => 80 ),
			'tablet_default' => array( 'unit' => '%', 'size' => 92 ),
			'mobile_default' => array( 'unit' => '%', 'size' => 100 ),
			'selectors' => array( '{{WRAPPER}} .sazan-footer__inner, {{WRAPPER}} .sazan-footer__bottom-inner' => 'max-width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();
	}

	private function content_brand() {
		$this->start_controls_section( 'sec_brand', array( 'label' => esc_html__( 'برند', 'sazan-core' ) ) );
		$this->add_control( 'brand_show', array( 'label' => esc_html__( 'نمایش بلوک برند', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'logo_image', array( 'label' => esc_html__( 'تصویر لوگو', 'sazan-core' ), 'type' => Controls_Manager::MEDIA, 'default' => array( 'url' => '' ) ) );
		$this->add_control( 'logo_text', array( 'label' => esc_html__( 'یا متن لوگو', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'سازان', 'sazan-core' ) ) );
		$this->add_control( 'logo_link', array( 'label' => esc_html__( 'لینک لوگو', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => home_url( '/' ) ) ) );
		$this->add_control( 'brand_desc', array(
			'label' => esc_html__( 'توضیح کوتاه', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA,
			'default' => esc_html__( 'گروه آموزشی سازان؛ همراهِ شما در مسیرِ یادگیری، مشاوره و رشدِ کسب‌وکار.', 'sazan-core' ),
			'rows' => 3,
		) );
		$this->add_control( 'logo_width', array(
			'label' => esc_html__( 'عرض تصویر لوگو', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 60, 'max' => 320 ) ), 'default' => array( 'unit' => 'px', 'size' => 150 ),
			'selectors' => array( '{{WRAPPER}} .sazan-footer__logo img' => 'width: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();
	}

	private function content_social() {
		$this->start_controls_section( 'sec_social', array( 'label' => esc_html__( 'شبکه‌های اجتماعی', 'sazan-core' ) ) );
		$this->add_control( 'social_show', array( 'label' => esc_html__( 'نمایش', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$rep = new Repeater();
		$rep->add_control( 'net', array(
			'label' => esc_html__( 'شبکه', 'sazan-core' ), 'type' => Controls_Manager::SELECT,
			'default' => 'instagram', 'options' => $this->social_options(),
		) );
		$rep->add_control( 'url', array( 'label' => esc_html__( 'لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );
		$this->add_control( 'socials', array(
			'label' => esc_html__( 'آیتم‌ها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER, 'fields' => $rep->get_controls(),
			'default' => array(
				array( 'net' => 'instagram', 'url' => array( 'url' => '#' ) ),
				array( 'net' => 'telegram', 'url' => array( 'url' => '#' ) ),
				array( 'net' => 'whatsapp', 'url' => array( 'url' => '#' ) ),
				array( 'net' => 'aparat', 'url' => array( 'url' => '#' ) ),
			),
			'title_field' => '{{{ net }}}',
		) );
		$this->end_controls_section();
	}

	private function content_newsletter() {
		$this->start_controls_section( 'sec_news', array( 'label' => esc_html__( 'خبرنامه', 'sazan-core' ) ) );
		$this->add_control( 'news_show', array( 'label' => esc_html__( 'نمایش', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'news_title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'عضویت در خبرنامه', 'sazan-core' ) ) );
		$this->add_control( 'news_desc', array( 'label' => esc_html__( 'توضیح', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'جدیدترین دوره‌ها و تخفیف‌ها را زودتر از همه دریافت کنید.', 'sazan-core' ) ) );
		$this->add_control( 'news_placeholder', array( 'label' => esc_html__( 'متن راهنمای ایمیل', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'ایمیل شما…', 'sazan-core' ) ) );
		$this->add_control( 'news_btn', array( 'label' => esc_html__( 'متن دکمه', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'عضویت', 'sazan-core' ) ) );
		$this->add_control( 'news_action', array(
			'label' => esc_html__( 'آدرس ارسال فرم (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::URL,
			'default' => array( 'url' => '' ),
			'description' => esc_html__( 'اگر خالی باشد، پیامِ موفقیت به‌صورت محلی نمایش داده می‌شود.', 'sazan-core' ),
		) );
		$this->end_controls_section();
	}

	private function content_columns() {
		$this->start_controls_section( 'sec_cols', array( 'label' => esc_html__( 'ستون‌های لینک', 'sazan-core' ) ) );
		$rep = new Repeater();
		$rep->add_control( 'title', array( 'label' => esc_html__( 'عنوان ستون', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'دسترسی سریع', 'sazan-core' ) ) );
		$rep->add_control( 'menu', array( 'label' => esc_html__( 'منو', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_menus_options(), 'default' => '0' ) );
		$this->add_control( 'columns', array(
			'label' => esc_html__( 'ستون‌ها', 'sazan-core' ), 'type' => Controls_Manager::REPEATER, 'fields' => $rep->get_controls(),
			'default' => array(
				array( 'title' => esc_html__( 'دسترسی سریع', 'sazan-core' ), 'menu' => '0' ),
				array( 'title' => esc_html__( 'خدمات', 'sazan-core' ), 'menu' => '0' ),
			),
			'title_field' => '{{{ title }}}',
		) );
		$this->end_controls_section();
	}

	private function content_contact() {
		$this->start_controls_section( 'sec_contact', array( 'label' => esc_html__( 'تماس', 'sazan-core' ) ) );
		$this->add_control( 'contact_show', array( 'label' => esc_html__( 'نمایش', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'contact_title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'ارتباط با ما', 'sazan-core' ) ) );
		$this->add_control( 'c_address', array( 'label' => esc_html__( 'آدرس', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'تهران، ایران', 'sazan-core' ) ) );
		$this->add_control( 'c_phone', array( 'label' => esc_html__( 'شماره تماس (نمایش)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '۰۹۱۲۶۰۸۲۸۷۶' ) );
		$this->add_control( 'c_phone_raw', array( 'label' => esc_html__( 'شماره برای tel:', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '09126082876' ) );
		$this->add_control( 'c_email', array( 'label' => esc_html__( 'ایمیل', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => 'info@sazan.com' ) );
		$this->add_control( 'c_hours', array( 'label' => esc_html__( 'ساعات پاسخگویی', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'شنبه تا پنجشنبه، ۹ تا ۱۸', 'sazan-core' ) ) );
		$this->end_controls_section();
	}

	private function content_bottom() {
		$this->start_controls_section( 'sec_bottom', array( 'label' => esc_html__( 'نوار پایین', 'sazan-core' ) ) );
		$this->add_control( 'copyright', array(
			'label' => esc_html__( 'متن کپی‌رایت', 'sazan-core' ), 'type' => Controls_Manager::TEXT,
			'default' => esc_html__( 'تمامی حقوق برای گروه آموزشی سازان محفوظ است.', 'sazan-core' ),
		) );
		$this->add_control( 'bottom_menu', array( 'label' => esc_html__( 'منوی پایین (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'options' => $this->get_menus_options(), 'default' => '0' ) );
		$this->add_control( 'backtop_show', array( 'label' => esc_html__( 'دکمه‌ی «برو بالا»', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'backtop_text', array( 'label' => esc_html__( 'متن دکمه‌ی برو بالا', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'برو بالا', 'sazan-core' ), 'condition' => array( 'backtop_show' => 'yes' ) ) );
		$this->end_controls_section();
	}

	/* ===================== استایل ===================== */
	private function style_general() {
		$this->start_controls_section( 'style_general', array( 'label' => esc_html__( 'رنگ‌ها', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'c_accent', array(
			'label' => esc_html__( 'رنگ برند (اکسنت)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#1B90B2',
			'selectors' => array( '{{WRAPPER}} .sazan-footer' => '--fz-accent: {{VALUE}};' ),
		) );
		$this->add_control( 'c_accent2', array(
			'label' => esc_html__( 'رنگ برند (روشن)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#2ba6c9',
			'selectors' => array( '{{WRAPPER}} .sazan-footer' => '--fz-accent-l: {{VALUE}};' ),
		) );
		$this->add_group_control( Group_Control_Background::get_type(), array(
			'name' => 'bg', 'types' => array( 'classic', 'gradient' ), 'selector' => '{{WRAPPER}} .sazan-footer',
			'fields_options' => array( 'background' => array( 'default' => 'classic' ), 'color' => array( 'default' => '#081218' ) ),
		) );
		$this->add_control( 'c_heading', array(
			'label' => esc_html__( 'رنگ عناوین', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff',
			'selectors' => array( '{{WRAPPER}} .sazan-footer' => '--fz-heading: {{VALUE}};' ),
		) );
		$this->add_control( 'c_text', array(
			'label' => esc_html__( 'رنگ متن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#a7bcc8',
			'selectors' => array( '{{WRAPPER}} .sazan-footer' => '--fz-text: {{VALUE}};' ),
		) );
		$this->add_control( 'c_border', array(
			'label' => esc_html__( 'رنگ حاشیه‌ها', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(255,255,255,0.09)',
			'selectors' => array( '{{WRAPPER}} .sazan-footer' => '--fz-border: {{VALUE}};' ),
		) );
		$this->add_responsive_control( 'pad', array(
			'label' => esc_html__( 'فاصله داخلیِ عمودی', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'range' => array( 'px' => array( 'min' => 20, 'max' => 140 ) ), 'default' => array( 'unit' => 'px', 'size' => 64 ),
			'selectors' => array( '{{WRAPPER}} .sazan-footer__inner' => 'padding-top: {{SIZE}}{{UNIT}}; padding-bottom: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();
	}

	private function style_typography() {
		$this->start_controls_section( 'style_typo', array( 'label' => esc_html__( 'تایپوگرافی', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'logo_typo', 'label' => esc_html__( 'لوگوی متنی', 'sazan-core' ), 'selector' => '{{WRAPPER}} .sazan-footer__logo-text' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'head_typo', 'label' => esc_html__( 'عناوین ستون', 'sazan-core' ), 'selector' => '{{WRAPPER}} .sazan-footer__col-title, {{WRAPPER}} .sazan-footer__news-title, {{WRAPPER}} .sazan-footer__contact-title' ) );
		$this->add_group_control( Group_Control_Typography::get_type(), array( 'name' => 'link_typo', 'label' => esc_html__( 'لینک‌ها', 'sazan-core' ), 'selector' => '{{WRAPPER}} .sazan-footer__col a, {{WRAPPER}} .sazan-footer__contact a' ) );
		$this->end_controls_section();
	}

	/* ===================== آیکن‌های SVG ===================== */
	private function svg( $name ) {
		switch ( $name ) {
			case 'brand':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 4 20h16L12 3Z" fill="currentColor" opacity=".16"/><path d="M12 3 4 20h16L12 3Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m8.4 13.2 2-2.2 2 2.4 2.9-3.2" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			case 'pin':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="12" cy="10" r="2.6" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>';
			case 'phone':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.5 3.5 9 4l1 3.5-1.8 1.4a12 12 0 0 0 5.9 5.9L15.5 15l3.5 1 .5 2.5a2 2 0 0 1-2.2 2.3A16.5 16.5 0 0 1 3.7 6.2 2 2 0 0 1 6.5 3.5Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			case 'mail':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m4 7 8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			case 'clock':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M12 7.5V12l3 2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			case 'arrow-up':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 19V6M6 12l6-6 6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			case 'send':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 3 3 10.5l6.5 2.5L12 20l3-7 6-10Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			case 'instagram':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17.2" cy="6.8" r="1.1" fill="currentColor"/></svg>';
			case 'telegram':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.5 4.3 2.9 11.4c-.9.35-.9 1.03-.16 1.27l4.7 1.47 1.8 5.6c.23.63.4.87.83.87.33 0 .5-.16.7-.36l2.26-2.2 4.7 3.47c.86.48 1.48.23 1.7-.8l3.06-14.4c.32-1.26-.5-1.83-1.6-1.32Z" fill="currentColor"/></svg>';
			case 'whatsapp':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.8a8.1 8.1 0 0 0-6.9 12.3L4 20.2l4.2-1.1A8.1 8.1 0 1 0 12 3.8Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9.2 8.4c.2-.5.4-.5.6-.5h.5c.2 0 .4 0 .6.5l.6 1.4c.1.2 0 .4-.1.5l-.5.6c-.1.1-.2.3-.1.5.3.6 1.3 1.6 2.1 2 .2.1.4.1.5 0l.6-.6c.1-.2.3-.2.5-.1l1.4.7c.2.1.4.2.4.4v.6c0 .5-.4 1-1 1.1-.6.1-1.4.1-3.3-.9-2.1-1.1-3.4-3.3-3.5-3.5-.1-.2-.8-1.1-.8-2.1s.5-1.5.7-1.6Z" fill="currentColor"/></svg>';
			case 'youtube':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2.5" y="5.5" width="19" height="13" rx="4" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M10.2 9.2v5.6l4.8-2.8-4.8-2.8Z" fill="currentColor"/></svg>';
			case 'aparat':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="4.4" r="1.2" fill="currentColor"/><circle cx="19.6" cy="12" r="1.2" fill="currentColor"/><circle cx="12" cy="19.6" r="1.2" fill="currentColor"/><circle cx="4.4" cy="12" r="1.2" fill="currentColor"/></svg>';
			case 'linkedin':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="3" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M7.2 10v6M7.2 7.6v.01M11 16v-3.2c0-1.1.9-1.9 1.9-1.9s1.9.8 1.9 1.9V16M11 10.2V16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
			case 'twitter':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4l7 9.2L4.4 20H6l6-6.4L16.6 20H20l-7.3-9.6L19.4 4H18l-5.6 6L8 4H4Z" fill="currentColor"/></svg>';
			case 'eitaa':
				return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M8.5 13c1.5 1.6 3.4 1.9 4.6.7 1-1 1-2.3.2-3.6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="14.6" cy="9.2" r="1" fill="currentColor"/></svg>';
			case 'email':
				return $this->svg( 'mail' );
			case 'chevron':
				return '<svg class="sazan-fchev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
		}
		return '';
	}

	/* ===================== رندر ===================== */
	protected function render() {
		try {
			$this->render_footer();
		} catch ( \Throwable $e ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div style="padding:14px;color:#c33;background:#fee;border-radius:8px;direction:rtl">';
				echo esc_html__( 'خطا در نمایش فوتر:', 'sazan-core' ) . ' ' . esc_html( $e->getMessage() );
				echo '</div>';
			}
		}
	}

	private function render_menu( $slug ) {
		if ( empty( $slug ) || '0' === $slug ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<span class="sazan-footer__menu-empty">' . esc_html__( 'منویی انتخاب نشده', 'sazan-core' ) . '</span>';
			}
			return;
		}
		wp_nav_menu( array( 'menu' => $slug, 'container' => false, 'menu_class' => 'sazan-footer-menu', 'fallback_cb' => false, 'depth' => 1, 'echo' => true ) );
	}

	private function render_footer() {
		$s = $this->get_settings_for_display();

		$skin = in_array( $s['skin'], array( 'glass', 'solid', 'minimal' ), true ) ? $s['skin'] : 'glass';
		$classes = 'sazan-footer skin-' . $skin;
		if ( 'yes' === $s['glow'] ) { $classes .= ' has-glow'; }
		if ( 'yes' === $s['top_border'] ) { $classes .= ' has-topline'; }

		echo '<footer class="' . esc_attr( $classes ) . '">';
		if ( 'yes' === $s['glow'] ) { echo '<span class="sazan-footer__glow" aria-hidden="true"></span>'; }
		echo '<div class="sazan-footer__inner">';

		/* --- خبرنامه (نوارِ بالا) --- */
		if ( 'yes' === $s['news_show'] ) {
			$action = ! empty( $s['news_action']['url'] ) ? $s['news_action']['url'] : '';
			echo '<div class="sazan-footer__news">';
			echo '<div class="sazan-footer__news-texts">';
			echo '<div class="sazan-footer__news-title">' . esc_html( $s['news_title'] ) . '</div>';
			if ( ! empty( $s['news_desc'] ) ) { echo '<p class="sazan-footer__news-desc">' . esc_html( $s['news_desc'] ) . '</p>'; }
			echo '</div>';
			echo '<form class="sazan-footer__news-form" method="post"' . ( $action ? ' action="' . esc_url( $action ) . '"' : '' ) . '>';
			echo '<input type="email" name="email" class="sazan-footer__news-input" placeholder="' . esc_attr( $s['news_placeholder'] ) . '" required>';
			echo '<button type="submit" class="sazan-footer__news-btn"><span>' . esc_html( $s['news_btn'] ) . '</span>' . $this->svg( 'send' ) . '</button>';
			echo '</form>';
			echo '<div class="sazan-footer__news-msg" role="status" aria-live="polite"></div>';
			echo '</div>';
		}

		/* --- گرید اصلی --- */
		echo '<div class="sazan-footer__grid">';

		// بلوک برند
		if ( 'yes' === $s['brand_show'] ) {
			echo '<div class="sazan-footer__brand">';
			$href = ! empty( $s['logo_link']['url'] ) ? $s['logo_link']['url'] : '';
			$open = $href ? '<a class="sazan-footer__logo" href="' . esc_url( $href ) . '">' : '<span class="sazan-footer__logo">';
			$close = $href ? '</a>' : '</span>';
			echo $open;
			if ( ! empty( $s['logo_image']['url'] ) ) {
				printf( '<img src="%s" alt="%s">', esc_url( $s['logo_image']['url'] ), esc_attr( $s['logo_text'] ) );
			} else {
				echo '<span class="sazan-footer__logo-mark">' . $this->svg( 'brand' ) . '</span>';
				echo '<span class="sazan-footer__logo-text">' . esc_html( $s['logo_text'] ) . '</span>';
			}
			echo $close;
			if ( ! empty( $s['brand_desc'] ) ) { echo '<p class="sazan-footer__desc">' . esc_html( $s['brand_desc'] ) . '</p>'; }

			if ( 'yes' === $s['social_show'] && ! empty( $s['socials'] ) ) {
				echo '<div class="sazan-footer__socials">';
				foreach ( $s['socials'] as $item ) {
					$net = isset( $item['net'] ) ? $item['net'] : '';
					$url = ! empty( $item['url']['url'] ) ? $item['url']['url'] : '#';
					if ( ! $net ) { continue; }
					$ext = ! empty( $item['url']['is_external'] ) ? ' target="_blank" rel="noopener"' : '';
					echo '<a class="sazan-footer__social" href="' . esc_url( $url ) . '"' . $ext . ' aria-label="' . esc_attr( $net ) . '">' . $this->svg( $net ) . '</a>';
				}
				echo '</div>';
			}
			echo '</div>';
		}

		// ستون‌های منو
		if ( ! empty( $s['columns'] ) ) {
			foreach ( $s['columns'] as $col ) {
				$title = isset( $col['title'] ) ? $col['title'] : '';
				echo '<div class="sazan-footer__col">';
				echo '<button type="button" class="sazan-footer__col-title">' . esc_html( $title ) . $this->svg( 'chevron' ) . '</button>';
				echo '<div class="sazan-footer__col-body">'; $this->render_menu( isset( $col['menu'] ) ? $col['menu'] : '0' ); echo '</div>';
				echo '</div>';
			}
		}

		// تماس
		if ( 'yes' === $s['contact_show'] ) {
			echo '<div class="sazan-footer__col sazan-footer__contact">';
			echo '<button type="button" class="sazan-footer__col-title">' . esc_html( $s['contact_title'] ) . $this->svg( 'chevron' ) . '</button>';
			echo '<div class="sazan-footer__col-body"><ul class="sazan-footer__contact-list">';
			if ( ! empty( $s['c_address'] ) ) {
				echo '<li><span class="sazan-footer__ci">' . $this->svg( 'pin' ) . '</span><span>' . esc_html( $s['c_address'] ) . '</span></li>';
			}
			if ( ! empty( $s['c_phone'] ) ) {
				$tel = preg_replace( '/[^0-9+]/', '', (string) $s['c_phone_raw'] );
				echo '<li><span class="sazan-footer__ci">' . $this->svg( 'phone' ) . '</span><a href="tel:' . esc_attr( $tel ) . '" dir="ltr">' . esc_html( $s['c_phone'] ) . '</a></li>';
			}
			if ( ! empty( $s['c_email'] ) ) {
				echo '<li><span class="sazan-footer__ci">' . $this->svg( 'mail' ) . '</span><a href="mailto:' . esc_attr( $s['c_email'] ) . '" dir="ltr">' . esc_html( $s['c_email'] ) . '</a></li>';
			}
			if ( ! empty( $s['c_hours'] ) ) {
				echo '<li><span class="sazan-footer__ci">' . $this->svg( 'clock' ) . '</span><span>' . esc_html( $s['c_hours'] ) . '</span></li>';
			}
			echo '</ul></div>';
			echo '</div>';
		}

		echo '</div>'; // grid
		echo '</div>'; // inner

		/* --- نوار پایین --- */
		echo '<div class="sazan-footer__bottom"><div class="sazan-footer__bottom-inner">';
		echo '<div class="sazan-footer__copy">' . wp_kses_post( $s['copyright'] ) . '</div>';
		if ( ! empty( $s['bottom_menu'] ) && '0' !== $s['bottom_menu'] ) {
			echo '<nav class="sazan-footer__botnav">'; $this->render_menu( $s['bottom_menu'] ); echo '</nav>';
		}
		if ( 'yes' === $s['backtop_show'] ) {
			echo '<button type="button" class="sazan-footer__backtop">' . $this->svg( 'arrow-up' ) . '<span>' . esc_html( $s['backtop_text'] ) . '</span></button>';
		}
		echo '</div></div>';

		echo '</footer>';
	}
}
