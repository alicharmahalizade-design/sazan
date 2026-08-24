<?php
/** Elementor widget for the complete premium homepage. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Sazan_Homepage_Premium_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'sazan-premium-homepage';
	}

	public function get_title() {
		return 'صفحه اصلی یکپارچه — سازگاری نسخه قدیمی';
	}

	public function show_in_panel() {
		return false;
	}

	public function get_icon() {
		return 'eicon-site-identity';
	}

	public function get_categories() {
		return array( 'sazan-homepage' );
	}

	public function get_keywords() {
		return array( 'sazan', 'homepage', 'صفحه اصلی', 'سازان' );
	}

	public function get_style_depends() {
		return array( 'sazan-homepage-premium' );
	}

	public function get_script_depends() {
		return array( 'sazan-homepage-premium' );
	}

	private function menus() {
		$out = array( 0 => 'منوی پیش‌فرض نمونه' );
		foreach ( wp_get_nav_menus() as $menu ) {
			$out[ $menu->term_id ] = $menu->name;
		}
		return $out;
	}

	protected function register_controls() {
		$this->start_controls_section( 'header_section', array( 'label' => 'هدر و ناوبری' ) );
		$this->add_control( 'logo', array( 'label' => 'لوگو', 'type' => \Elementor\Controls_Manager::MEDIA, 'default' => Sazan_Homepage_Premium_Renderer::defaults()['logo'] ) );
		$this->add_control( 'logo_link', array( 'label' => 'لینک لوگو', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => home_url( '/' ) ) ) );
		$this->add_control( 'menu_id', array( 'label' => 'فهرست وردپرس', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $this->menus(), 'default' => 0 ) );
		$this->add_control( 'account_text', array( 'label' => 'متن دکمه هدر', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'ورود / عضویت' ) );
		$this->add_control( 'account_url', array( 'label' => 'لینک دکمه هدر', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => home_url( '/my-account/' ) ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'hero_section', array( 'label' => 'هیرو و ثبت‌نام' ) );
		$this->add_control( 'hero_title', array( 'label' => 'عنوان اصلی', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'دوره حکمرانی بر بازار', 'label_block' => true ) );
		$this->add_control( 'hero_summary', array( 'label' => 'توضیح کوتاه', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => Sazan_Homepage_Premium_Renderer::defaults()['hero_summary'] ) );
		$this->add_control( 'hero_image', array( 'label' => 'تصویر اصلی', 'type' => \Elementor\Controls_Manager::MEDIA, 'default' => Sazan_Homepage_Premium_Renderer::defaults()['hero_image'] ) );
		$this->add_control( 'primary_text', array( 'label' => 'متن دکمه', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'مشاهده و ثبت‌نام' ) );
		$this->add_control( 'primary_url', array( 'label' => 'لینک دکمه', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#courses' ) ) );
		$this->end_controls_section();

		$this->start_controls_section( 'headings_section', array( 'label' => 'عنوان سکشن‌ها' ) );
		$fields = array(
			'services_title'     => array( 'مسیر خدمات', 'مسیر مناسب رشد خود را انتخاب کنید' ),
			'courses_title'      => array( 'دوره‌ها', 'دوره‌های در حال ثبت‌نام' ),
			'consultation_title' => array( 'مشاوره', 'مسیر درست کسب‌وکارتان را با اطمینان انتخاب کنید' ),
			'about_title'        => array( 'درباره سازان', 'سازان؛ همراه رشد واقعی کسب‌وکار' ),
			'calendar_title'     => array( 'تقویم', 'تقویم آموزشی سازان' ),
			'articles_title'     => array( 'مقالات', 'بینش‌های کاربردی برای رشد کسب‌وکار' ),
			'footer_title'       => array( 'دعوت به اقدام فوتر', 'برای حرکت بعدی کسب‌وکارت آماده‌ای؟' ),
		);
		foreach ( $fields as $key => $data ) {
			$this->add_control( $key, array( 'label' => $data[0], 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $data[1], 'label_block' => true ) );
		}
		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$settings['logo_link']   = ! empty( $settings['logo_link']['url'] ) ? $settings['logo_link']['url'] : home_url( '/' );
		$settings['account_url'] = ! empty( $settings['account_url']['url'] ) ? $settings['account_url']['url'] : home_url( '/my-account/' );
		$settings['primary_url'] = ! empty( $settings['primary_url']['url'] ) ? $settings['primary_url']['url'] : '#courses';
		echo Sazan_Homepage_Premium_Renderer::html( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
