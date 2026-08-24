<?php
/** Independent Elementor widgets for every premium homepage section. */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Sazan_Homepage_Section_Widget_Base extends \Elementor\Widget_Base {
	abstract protected function section_key();

	public function get_categories() { return array( 'sazan-homepage' ); }
	public function get_style_depends() { return array( 'sazan-homepage-premium' ); }
	public function get_script_depends() { return array( 'sazan-homepage-premium' ); }
	public function get_keywords() { return array( 'sazan', 'homepage', 'سازان', 'صفحه اصلی', $this->section_key() ); }

	protected function menus( $empty = 'منوی پیش‌فرض نمونه' ) {
		$out = array( 0 => $empty );
		foreach ( wp_get_nav_menus() as $menu ) { $out[ $menu->term_id ] = $menu->name; }
		return $out;
	}

	protected function post_categories() {
		$out = array( 0 => 'همه دسته‌بندی‌ها' );
		if ( function_exists( 'get_categories' ) ) {
			foreach ( get_categories( array( 'hide_empty' => false ) ) as $category ) {
				$out[ (int) $category->term_id ] = $category->name;
			}
		}
		return $out;
	}

	protected function seo_controls( $default_id, $default_aria, $heading = true ) {
		$this->start_controls_section( 'seo_section', array( 'label' => 'سئو و دسترس‌پذیری', 'tab' => \Elementor\Controls_Manager::TAB_CONTENT ) );
		$this->add_control( 'section_id', array( 'label' => 'شناسه سکشن (Anchor)', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $default_id, 'description' => 'فقط حروف انگلیسی، عدد و خط تیره؛ مانند courses.' ) );
		$this->add_control( 'aria_label', array( 'label' => 'برچسب دسترس‌پذیری', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $default_aria, 'label_block' => true ) );
		if ( $heading ) {
			$this->add_control( 'heading_tag', array( 'label' => 'تگ عنوان', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'h2', 'options' => array( 'h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6' ) ) );
		}
		$this->add_control( 'schema_type', array( 'label' => 'نوع Schema سکشن', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '', 'options' => array( '' => 'بدون Schema مستقل', 'WebPageElement' => 'WebPageElement', 'ItemList' => 'ItemList', 'Course' => 'Course', 'Organization' => 'Organization' ) ) );
		$this->end_controls_section();
	}

	protected function style_controls() {
		$this->start_controls_section( 'style_section', array( 'label' => 'استایل سکشن', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'accent_color', array( 'label' => 'رنگ اصلی', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-premium-section-widget' => '--color-primary: {{VALUE}};--cyan: {{VALUE}};--cyan-soft: {{VALUE}};' ) ) );
		$this->add_control( 'heading_color', array( 'label' => 'رنگ عنوان‌ها', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-premium-section-widget h1,{{WRAPPER}} .sazan-premium-section-widget h2,{{WRAPPER}} .sazan-premium-section-widget h3' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'text_color', array( 'label' => 'رنگ متن', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-premium-section-widget p,{{WRAPPER}} .sazan-premium-section-widget small' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'background_color', array( 'label' => 'پس‌زمینه کلی ویجت', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-premium-section-widget' => 'background-color: {{VALUE}};' ) ) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'heading_typography', 'label' => 'تایپوگرافی عنوان', 'selector' => '{{WRAPPER}} .sazan-premium-section-widget h1,{{WRAPPER}} .sazan-premium-section-widget h2' ) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'text_typography', 'label' => 'تایپوگرافی متن‌ها', 'selector' => '{{WRAPPER}} .sazan-premium-section-widget p,{{WRAPPER}} .sazan-premium-section-widget small,{{WRAPPER}} .sazan-premium-section-widget span' ) );
		$this->add_group_control( \Elementor\Group_Control_Typography::get_type(), array( 'name' => 'button_typography', 'label' => 'تایپوگرافی دکمه‌ها', 'selector' => '{{WRAPPER}} .sazan-premium-section-widget a,{{WRAPPER}} .sazan-premium-section-widget button' ) );
		$this->add_control( 'link_color', array( 'label' => 'رنگ لینک‌ها', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-premium-section-widget a' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'link_hover_color', array( 'label' => 'رنگ لینک در حالت Hover', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .sazan-premium-section-widget a:hover,{{WRAPPER}} .sazan-premium-section-widget a:focus-visible' => 'color: {{VALUE}};' ) ) );
		$this->add_control( 'surface_color', array( 'label' => 'رنگ کارت‌ها و سطوح', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .service-card,{{WRAPPER}} .course-card,{{WRAPPER}} .calendar-item,{{WRAPPER}} .article-card,{{WRAPPER}} .footer-cta' => 'background-color: {{VALUE}};' ) ) );
		$this->add_control( 'border_color', array( 'label' => 'رنگ کادرها', 'type' => \Elementor\Controls_Manager::COLOR, 'selectors' => array( '{{WRAPPER}} .service-card,{{WRAPPER}} .course-card,{{WRAPPER}} .calendar-item,{{WRAPPER}} .article-card,{{WRAPPER}} .footer-cta' => 'border-color: {{VALUE}};' ) ) );
		$this->add_responsive_control( 'card_radius', array( 'label' => 'گردی گوشه کارت‌ها', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em' ), 'selectors' => array( '{{WRAPPER}} .service-card,{{WRAPPER}} .course-card,{{WRAPPER}} .calendar-item,{{WRAPPER}} .article-card,{{WRAPPER}} .footer-cta' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'section_padding', array( 'label' => 'فاصله داخلی', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em', '%' ), 'selectors' => array( '{{WRAPPER}} .sazan-premium-section-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->add_responsive_control( 'section_margin', array( 'label' => 'فاصله بیرونی', 'type' => \Elementor\Controls_Manager::DIMENSIONS, 'size_units' => array( 'px', 'em', '%' ), 'selectors' => array( '{{WRAPPER}} .sazan-premium-section-widget' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ) ) );
		$this->end_controls_section();
	}

	protected function render() {
		echo Sazan_Homepage_Premium_Renderer::section( $this->section_key(), $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

final class Sazan_Homepage_Header_Widget extends Sazan_Homepage_Section_Widget_Base {
	public function get_name() { return 'sazan-home-header'; }
	public function get_title() { return 'سازان — هدر صفحه اصلی'; }
	public function get_icon() { return 'eicon-header'; }
	protected function section_key() { return 'header'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوای هدر' ) );
		$this->add_control( 'logo', array( 'label' => 'تصویر لوگو', 'type' => \Elementor\Controls_Manager::MEDIA, 'default' => Sazan_Homepage_Premium_Renderer::defaults()['logo'] ) );
		$this->add_control( 'logo_alt', array( 'label' => 'متن جایگزین لوگو (Alt)', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'سازان', 'label_block' => true ) );
		$this->add_control( 'logo_link', array( 'label' => 'لینک لوگو', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => home_url( '/' ) ) ) );
		$this->add_control( 'menu_id', array( 'label' => 'فهرست واقعی وردپرس', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $this->menus(), 'default' => 0 ) );
		$this->add_control( 'menu_aria', array( 'label' => 'عنوان دسترس‌پذیری منو', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'منوی اصلی' ) );
		$this->add_control( 'account_text', array( 'label' => 'متن دکمه هدر', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'ورود / عضویت' ) );
		$this->add_control( 'account_link', array( 'label' => 'لینک دکمه هدر', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => home_url( '/my-account/' ) ) ) );
		$this->add_control( 'search_label', array( 'label' => 'عنوان فرم جستجو', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'جستجو در سازان' ) );
		$this->add_control( 'search_placeholder', array( 'label' => 'متن داخل جستجو', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'جستجو کنید...' ) );
		$this->add_control( 'search_button', array( 'label' => 'متن دکمه جستجو', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'جستجو' ) );
		$this->end_controls_section();
		$this->seo_controls( 'site-header', 'سربرگ اصلی', false ); $this->style_controls();
	}
}

final class Sazan_Homepage_Hero_Widget extends Sazan_Homepage_Section_Widget_Base {
	public function get_name() { return 'sazan-home-hero'; }
	public function get_title() { return 'سازان — هیرو و اسلایدر'; }
	public function get_icon() { return 'eicon-slider-full-screen'; }
	protected function section_key() { return 'hero'; }
	private function slide_defaults() {
		$base = SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/images/';
		return array(
			array( 'title' => 'دوره حکمرانی بر بازار', 'ribbon' => 'پرطرفدار', 'summary' => Sazan_Homepage_Premium_Renderer::defaults()['hero_summary'], 'image' => array( 'url' => $base . 'حکمرانی-بر-بازار.webp' ), 'image_alt' => 'تصویر دوره حکمرانی بر بازار', 'primary_text' => 'مشاهده و ثبت‌نام', 'primary_link' => array( 'url' => '#courses' ), 'secondary_text' => 'سرفصل‌های دوره', 'secondary_link' => array( 'url' => '#courses' ) ),
			array( 'title' => 'زبان بدن', 'ribbon' => 'پرطرفدار', 'summary' => 'مهارت ارتباط غیرکلامی را به ابزاری حرفه‌ای برای اثرگذاری بیشتر تبدیل کنید.', 'image' => array( 'url' => $base . 'زبان-بدن.webp' ), 'image_alt' => 'تصویر دوره زبان بدن', 'primary_text' => 'مشاهده دوره', 'primary_link' => array( 'url' => '#courses' ), 'secondary_text' => 'جزئیات دوره', 'secondary_link' => array( 'url' => '#courses' ) ),
			array( 'title' => 'مذاکره‌کننده حرفه‌ای', 'ribbon' => 'پیشنهاد سازان', 'summary' => 'برای مذاکره‌های مهم آماده شوید و با ساختاری روشن به توافق‌های بهتر برسید.', 'image' => array( 'url' => $base . 'ChatGPT-Image-Aug-2-2026-07_30_24-PM.png' ), 'image_alt' => 'تصویر دوره مذاکره‌کننده حرفه‌ای', 'primary_text' => 'ثبت‌نام در دوره', 'primary_link' => array( 'url' => '#courses' ), 'secondary_text' => 'مشاهده سرفصل‌ها', 'secondary_link' => array( 'url' => '#courses' ) ),
		);
	}
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'اسلایدهای هیرو' ) );
		$r = new \Elementor\Repeater();
		$r->add_control( 'title', array( 'label' => 'عنوان اسلاید', 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => true ) );
		$r->add_control( 'ribbon', array( 'label' => 'برچسب بالای اسلاید', 'type' => \Elementor\Controls_Manager::TEXT ) );
		$r->add_control( 'summary', array( 'label' => 'توضیح', 'type' => \Elementor\Controls_Manager::TEXTAREA ) );
		$r->add_control( 'image', array( 'label' => 'تصویر اسلاید', 'type' => \Elementor\Controls_Manager::MEDIA ) );
		$r->add_control( 'image_alt', array( 'label' => 'متن جایگزین تصویر (Alt)', 'type' => \Elementor\Controls_Manager::TEXT ) );
		$r->add_control( 'primary_text', array( 'label' => 'متن دکمه اصلی', 'type' => \Elementor\Controls_Manager::TEXT ) );
		$r->add_control( 'primary_link', array( 'label' => 'لینک دکمه اصلی', 'type' => \Elementor\Controls_Manager::URL, 'show_external' => true ) );
		$r->add_control( 'secondary_text', array( 'label' => 'متن دکمه دوم', 'type' => \Elementor\Controls_Manager::TEXT ) );
		$r->add_control( 'secondary_link', array( 'label' => 'لینک دکمه دوم', 'type' => \Elementor\Controls_Manager::URL, 'show_external' => true ) );
		$this->add_control( 'slides', array( 'label' => 'اسلایدها', 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'default' => $this->slide_defaults(), 'title_field' => '{{{ title }}}' ) );
		$this->end_controls_section();
		$this->start_controls_section( 'shared_content', array( 'label' => 'اطلاعات مشترک هیرو' ) );
		foreach ( array( 'eyebrow' => array( 'متن بالای عنوان', 'مسیر آموزشی ویژه مدیران آینده' ), 'rating_value' => array( 'عدد امتیاز', '۴.۹ از ۵' ), 'rating_label' => array( 'عنوان امتیاز', 'امتیاز هنرجویان' ), 'learners_value' => array( 'تعداد هنرجویان', '+۲,۰۰۰' ), 'learners_label' => array( 'عنوان هنرجویان', 'هنرجوی این مسیر' ), 'capacity_label' => array( 'عنوان ظرفیت', 'ظرفیت دوره' ), 'capacity_text' => array( 'متن ظرفیت', 'فقط ۵ صندلی باقی‌مانده' ), 'teacher_label' => array( 'عنوان مدرس', 'مدرس دوره' ), 'teacher_name' => array( 'نام مدرس', 'عباس شانه‌سازان' ), 'teacher_role' => array( 'تخصص مدرس', 'مدیریت و بازاریابی' ), 'teacher_alt' => array( 'Alt تصویر مدرس', 'عباس شانه‌سازان' ), 'proof_1' => array( 'اعتمادساز اول', 'ضمانت کیفیت آموزش' ), 'proof_2' => array( 'اعتمادساز دوم', 'دسترسی همیشگی' ), 'proof_3' => array( 'اعتمادساز سوم', 'پشتیبانی واقعی' ) ) as $key => $data ) { $this->add_control( $key, array( 'label' => $data[0], 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $data[1] ) ); }
		$this->add_control( 'teacher_image', array( 'label' => 'تصویر مدرس', 'type' => \Elementor\Controls_Manager::MEDIA, 'default' => array( 'url' => SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/images/Untitled-6.webp' ) ) );
		foreach ( array( 'days' => array( 'عدد روز', '۳۸' ), 'hours' => array( 'عدد ساعت', '۲۱' ), 'minutes' => array( 'عدد دقیقه', '۴۱' ), 'seconds' => array( 'عدد ثانیه', '۳۰' ), 'days_label' => array( 'برچسب روز', 'روز' ), 'hours_label' => array( 'برچسب ساعت', 'ساعت' ), 'minutes_label' => array( 'برچسب دقیقه', 'دقیقه' ), 'seconds_label' => array( 'برچسب ثانیه', 'ثانیه' ) ) as $key => $data ) { $this->add_control( $key, array( 'label' => $data[0], 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $data[1] ) ); }
		$feature = new \Elementor\Repeater(); $feature->add_control( 'title', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT ) ); $feature->add_control( 'subtitle', array( 'label' => 'توضیح', 'type' => \Elementor\Controls_Manager::TEXT ) );
		$this->add_control( 'features', array( 'label' => 'ویژگی‌های کوتاه', 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $feature->get_controls(), 'default' => array( array( 'title' => '۱۲ جلسه', 'subtitle' => 'آموزش کاربردی' ), array( 'title' => '۹۶ ساعت', 'subtitle' => 'محتوای تخصصی' ), array( 'title' => 'مقدماتی', 'subtitle' => 'بدون پیش‌نیاز' ), array( 'title' => 'پشتیبانی', 'subtitle' => 'همراهی واقعی' ) ), 'title_field' => '{{{ title }}}' ) );
		$this->end_controls_section();
		$this->seo_controls( 'top', 'دوره‌های منتخب', true ); $this->update_control( 'heading_tag', array( 'default' => 'h1' ) ); $this->style_controls();
	}
}

final class Sazan_Homepage_Services_Widget extends Sazan_Homepage_Section_Widget_Base {
	public function get_name() { return 'sazan-home-services'; }
	public function get_title() { return 'سازان — مسیرهای خدمات'; }
	public function get_icon() { return 'eicon-apps'; }
	protected function section_key() { return 'services'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوای سکشن' ) );
		$this->add_control( 'kicker', array( 'label' => 'متن بالای عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'SAZAN ECOSYSTEM' ) );
		$this->add_control( 'title', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'مسیر مناسب رشد خود را انتخاب کنید', 'label_block' => true ) );
		$this->add_control( 'subtitle', array( 'label' => 'توضیح', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'مشاوره، آموزش یا محتوای تخصصی؛ بر اساس نیاز امروزتان شروع کنید.' ) );
		$this->add_control( 'start_label', array( 'label' => 'برچسب نقطه شروع', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'نقطه شروع پیشنهادی' ) );
		$this->add_control( 'start_title', array( 'label' => 'عنوان نقطه شروع', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'نمی‌دانید کدام مسیر برای شما مناسب‌تر است؟' ) );
		$this->add_control( 'start_link_text', array( 'label' => 'متن لینک ارزیابی', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'دریافت ارزیابی اولیه' ) );
		$this->add_control( 'start_link', array( 'label' => 'آدرس لینک ارزیابی', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#calendar' ), 'show_external' => true ) );
		foreach ( array( 'future_label' => array( 'برچسب خدمات آینده', 'در مسیر توسعه' ), 'future_description' => array( 'توضیح خدمات آینده', 'خدمات تکمیلی سازان به‌زودی در دسترس قرار می‌گیرند.' ), 'future_1' => array( 'خدمت آینده اول', 'رشد فردی' ), 'future_2' => array( 'خدمت آینده دوم', 'منابع انسانی' ), 'future_3' => array( 'خدمت آینده سوم', 'پشتیبانی و همراهی' ), 'future_status' => array( 'وضعیت خدمات آینده', 'به‌زودی' ) ) as $key => $data ) { $this->add_control( $key, array( 'label' => $data[0], 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $data[1] ) ); }
		$r = new \Elementor\Repeater(); foreach ( array( 'eyebrow' => 'متن بالای کارت', 'title' => 'عنوان کارت', 'description' => 'توضیح کارت', 'link_text' => 'متن لینک' ) as $key => $label ) { $r->add_control( $key, array( 'label' => $label, 'type' => 'description' === $key ? \Elementor\Controls_Manager::TEXTAREA : \Elementor\Controls_Manager::TEXT ) ); } $r->add_control( 'link', array( 'label' => 'آدرس لینک', 'type' => \Elementor\Controls_Manager::URL, 'show_external' => true ) );
		$this->add_control( 'items', array( 'label' => 'کارت‌های مسیر', 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'default' => array( array( 'eyebrow' => 'برای پیدا کردن مسیر درست', 'title' => 'مشاوره کسب‌وکار', 'description' => 'مسئله را دقیق شناسایی کنید و نقشه راه متناسب با کسب‌وکارتان بسازید.', 'link_text' => 'بررسی مسیر مشاوره', 'link' => array( 'url' => '#about' ) ), array( 'eyebrow' => 'برای ساختن مهارت اجرایی', 'title' => 'دوره‌های آموزشی', 'description' => 'مهارت‌های کاربردی مدیریت و بازار را یاد بگیرید و در کار واقعی اجرا کنید.', 'link_text' => 'مشاهده دوره‌ها', 'link' => array( 'url' => '#courses' ) ), array( 'eyebrow' => 'برای تصمیم‌های دقیق‌تر', 'title' => 'مرکز دانش', 'description' => 'به مقاله‌ها و راهنماهای تخصصی دسترسی داشته باشید و سریع‌تر تصمیم بگیرید.', 'link_text' => 'ورود به مرکز دانش', 'link' => array( 'url' => '#articles' ) ) ), 'title_field' => '{{{ title }}}' ) );
		$this->end_controls_section(); $this->seo_controls( 'consulting', 'مسیرهای خدمات سازان' ); $this->style_controls();
	}
}

final class Sazan_Homepage_Courses_Widget extends Sazan_Homepage_Section_Widget_Base {
	public function get_name() { return 'sazan-home-courses'; }
	public function get_title() { return 'سازان — دوره‌های ثبت‌نامی دستی'; }
	public function get_icon() { return 'eicon-products'; }
	protected function section_key() { return 'courses'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'عنوان سکشن' ) );
		$this->add_control( 'kicker', array( 'label' => 'متن بالای عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'COURSES' ) );
		$this->add_control( 'title', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'دوره‌های در حال ثبت‌نام' ) );
		$this->add_control( 'subtitle', array( 'label' => 'توضیح', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'مهارت‌های کاربردی برای فروش، مذاکره و مدیریت مالی.' ) );
		$this->add_control( 'all_filter_text', array( 'label' => 'متن فیلتر همه', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'همه' ) );
		$this->add_control( 'count_suffix', array( 'label' => 'متن بعد از تعداد دوره‌ها', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'دوره فعال' ) );
		$this->end_controls_section();
		$this->start_controls_section( 'courses_section', array( 'label' => 'دوره‌ها — ورود کاملاً دستی' ) );
		$r = new \Elementor\Repeater();
		$r->add_control( 'image', array( 'label' => 'تصویر دوره', 'type' => \Elementor\Controls_Manager::MEDIA ) );
		foreach ( array( 'image_alt' => 'Alt تصویر', 'status' => 'وضعیت ثبت‌نام', 'category' => 'دسته‌بندی', 'category_slug' => 'شناسه فیلتر انگلیسی', 'mode' => 'نوع برگزاری', 'title' => 'نام دوره', 'date' => 'تاریخ شروع', 'instructor' => 'نام مدرس', 'button_text' => 'متن دکمه' ) as $key => $label ) { $r->add_control( $key, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::TEXT, 'label_block' => in_array( $key, array( 'title', 'image_alt' ), true ) ) ); }
		$r->add_control( 'link', array( 'label' => 'لینک صفحه دوره', 'type' => \Elementor\Controls_Manager::URL, 'show_external' => true ) );
		$base = SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/images/';
		$this->add_control( 'courses', array( 'label' => 'فهرست دوره‌های انتخابی', 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'default' => array( array( 'image' => array( 'url' => $base . 'حکمرانی-بر-بازار-محصول.jpg' ), 'image_alt' => 'دوره حکمرانی بر بازار', 'status' => 'ثبت‌نام باز', 'category' => 'فروش', 'category_slug' => 'sale', 'mode' => 'حضوری', 'title' => 'دوره حکمرانی بر بازار', 'date' => '۱۲ اردیبهشت ۱۴۰۵', 'instructor' => 'عباس شانه‌سازان', 'button_text' => 'جزئیات و ثبت‌نام', 'link' => array( 'url' => '#' ) ) ), 'title_field' => '{{{ title }}}' ) );
		$this->end_controls_section(); $this->seo_controls( 'courses', 'دوره‌های در حال ثبت‌نام' ); $this->style_controls();
	}
}

final class Sazan_Homepage_Consultation_Widget extends Sazan_Homepage_Section_Widget_Base {
	public function get_name() { return 'sazan-home-consultation'; }
	public function get_title() { return 'سازان — بنر مشاوره'; }
	public function get_icon() { return 'eicon-call-to-action'; }
	protected function section_key() { return 'consultation'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوا و دکمه' ) );
		$this->add_control( 'image', array( 'label' => 'تصویر مشاور', 'type' => \Elementor\Controls_Manager::MEDIA, 'default' => array( 'url' => SAZAN_HOMEPAGE_PREMIUM_URL . 'assets/images/Untitled-6.webp' ) ) );
		$this->add_control( 'kicker', array( 'label' => 'متن بالای عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'همراه شما برای یک تصمیم مطمئن' ) );
		$this->add_control( 'title', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'مسیر درست کسب‌وکارتان را با اطمینان انتخاب کنید', 'label_block' => true ) );
		$this->add_control( 'description', array( 'label' => 'توضیح', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'مسئله کسب‌وکار شما را بررسی می‌کنیم و متناسب با شرایط واقعی، نقطه شروع و مسیر اقدام را پیشنهاد می‌دهیم.' ) );
		$this->add_control( 'button_text', array( 'label' => 'متن دکمه', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'دریافت مشاوره اولیه' ) );
		$this->add_control( 'button_link', array( 'label' => 'لینک دکمه', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#consulting' ), 'show_external' => true ) );
		foreach ( array( 'proof_1' => array( 'مزیت اول', 'ارزیابی اولیه' ), 'proof_2' => array( 'مزیت دوم', 'پاسخ‌گویی سریع' ), 'action_label' => array( 'متن بالای دکمه', 'برای شروع آماده‌اید؟' ), 'note' => array( 'متن زیر دکمه', 'بدون تعهد و متناسب با نیاز شما' ) ) as $key => $data ) { $this->add_control( $key, array( 'label' => $data[0], 'type' => \Elementor\Controls_Manager::TEXT, 'default' => $data[1] ) ); }
		$this->end_controls_section(); $this->seo_controls( 'consultation-cta', 'مشاوره سازان' ); $this->style_controls();
	}
}

final class Sazan_Homepage_About_Widget extends Sazan_Homepage_Section_Widget_Base {
	public function get_name() { return 'sazan-home-about'; }
	public function get_title() { return 'سازان — درباره و اعتبار'; }
	public function get_icon() { return 'eicon-person'; }
	protected function section_key() { return 'about'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'محتوای درباره سازان' ) );
		$this->add_control( 'kicker', array( 'label' => 'متن بالای عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'SAZAN ACADEMY' ) );
		$this->add_control( 'title', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'سازان؛ همراه رشد واقعی کسب‌وکار' ) );
		$this->add_control( 'description', array( 'label' => 'توضیح', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'ما آموزش، مشاوره و توسعه منابع انسانی را به راهکارهایی قابل اجرا تبدیل می‌کنیم؛ راهکارهایی که از مسئله واقعی شما شروع می‌شوند و به نتیجه‌ای قابل اندازه‌گیری می‌رسند.' ) );
		$this->add_control( 'button_text', array( 'label' => 'متن دکمه', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'آشنایی با مسیرهای همکاری' ) );
		$this->add_control( 'button_link', array( 'label' => 'لینک دکمه', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#consulting' ), 'show_external' => true ) );
		$value = new \Elementor\Repeater(); $value->add_control( 'title', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT ) ); $value->add_control( 'description', array( 'label' => 'توضیح', 'type' => \Elementor\Controls_Manager::TEXT ) ); $this->add_control( 'values', array( 'label' => 'ارزش‌ها', 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $value->get_controls(), 'default' => array( array( 'title' => 'یادگیری کاربردی', 'description' => 'متمرکز بر اجرا، نه فقط انتقال دانش' ), array( 'title' => 'رشد قابل سنجش', 'description' => 'مسیر روشن با خروجی‌های واقعی' ) ), 'title_field' => '{{{ title }}}' ) );
		$stat = new \Elementor\Repeater(); foreach ( array( 'number' => 'عدد', 'title' => 'عنوان', 'description' => 'توضیح' ) as $key => $label ) { $stat->add_control( $key, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::TEXT ) ); } $this->add_control( 'stats', array( 'label' => 'آمارها', 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $stat->get_controls(), 'default' => array( array( 'number' => '۳۰+', 'title' => 'دوره تخصصی', 'description' => 'برای مدیران و تیم‌های حرفه‌ای' ), array( 'number' => '۱۸+', 'title' => 'سال تجربه آموزشی', 'description' => 'آموزش مبتنی بر تجربه' ), array( 'number' => '۲۰+', 'title' => 'سال تجربه مشاوره', 'description' => 'همراه سازمان‌ها و مدیران' ) ), 'title_field' => '{{{ title }}}' ) );
		$this->end_controls_section(); $this->seo_controls( 'about', 'درباره سازان' ); $this->style_controls();
	}
}

final class Sazan_Homepage_Calendar_Widget extends Sazan_Homepage_Section_Widget_Base {
	public function get_name() { return 'sazan-home-calendar'; }
	public function get_title() { return 'سازان — تقویم آموزشی'; }
	public function get_icon() { return 'eicon-calendar'; }
	protected function section_key() { return 'calendar'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'عنوان سکشن' ) ); $this->add_control( 'kicker', array( 'label' => 'متن بالای عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'CALENDAR' ) ); $this->add_control( 'title', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'تقویم آموزشی سازان' ) ); $this->add_control( 'subtitle', array( 'label' => 'توضیح', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'برنامه دوره‌های پیش‌ رو را ببینید و پیش از تکمیل ظرفیت ثبت‌نام کنید.' ) ); $this->add_control( 'summary_label', array( 'label' => 'عنوان شمارنده', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'برنامه فعال' ) ); $this->add_control( 'summary_note', array( 'label' => 'توضیح شمارنده', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'ثبت‌نام در حال انجام' ) ); $this->end_controls_section();
		$this->start_controls_section( 'events_section', array( 'label' => 'برنامه‌ها' ) ); $r = new \Elementor\Repeater(); foreach ( array( 'datetime' => 'تاریخ استاندارد (YYYY-MM-DD)', 'day' => 'روز', 'month' => 'ماه', 'year' => 'سال', 'status' => 'وضعیت', 'mode' => 'نوع برگزاری', 'title' => 'عنوان برنامه', 'instructor' => 'مدرس', 'duration' => 'مدت آموزش', 'button_text' => 'متن دکمه' ) as $key => $label ) { $r->add_control( $key, array( 'label' => $label, 'type' => \Elementor\Controls_Manager::TEXT ) ); } $r->add_control( 'link', array( 'label' => 'لینک دکمه', 'type' => \Elementor\Controls_Manager::URL, 'show_external' => true ) );
		$this->add_control( 'events', array( 'label' => 'رویدادها', 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'default' => array( array( 'datetime' => '2026-08-24', 'day' => '۲', 'month' => 'شهریور', 'year' => '۱۴۰۵', 'status' => 'ثبت‌نام باز', 'mode' => 'حضوری', 'title' => 'کارگاه مذاکره‌کننده حرفه‌ای', 'instructor' => 'عباس شانه‌سازان', 'duration' => '۷۰ ساعت آموزش', 'button_text' => 'مشاهده و ثبت‌نام', 'link' => array( 'url' => '#courses' ) ) ), 'title_field' => '{{{ title }}}' ) );
		$this->end_controls_section(); $this->seo_controls( 'calendar', 'تقویم آموزشی سازان' ); $this->style_controls();
	}
}

final class Sazan_Homepage_Articles_Widget extends Sazan_Homepage_Section_Widget_Base {
	public function get_name() { return 'sazan-home-articles'; }
	public function get_title() { return 'سازان — آخرین مقالات داینامیک'; }
	public function get_icon() { return 'eicon-posts-grid'; }
	protected function section_key() { return 'articles'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'عنوان و لینک آرشیو' ) ); $this->add_control( 'kicker', array( 'label' => 'متن بالای عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'مجله سازان' ) ); $this->add_control( 'title', array( 'label' => 'عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'بینش‌های کاربردی برای رشد کسب‌وکار' ) ); $this->add_control( 'subtitle', array( 'label' => 'توضیح', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'مطالب کوتاه و قابل‌اجرا برای تصمیم‌های بهتر مدیریتی' ) ); $this->add_control( 'all_text', array( 'label' => 'متن دکمه آرشیو', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'مشاهده همه مقالات' ) ); $this->add_control( 'all_link', array( 'label' => 'لینک آرشیو', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#articles' ), 'show_external' => true ) ); $this->end_controls_section();
		$this->start_controls_section( 'query_section', array( 'label' => 'منبع و فیلتر مقالات' ) );
		$this->add_control( 'source', array( 'label' => 'منبع محتوا', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'latest', 'options' => array( 'latest' => 'آخرین مقالات وردپرس (داینامیک)', 'manual' => 'ورود دستی' ) ) );
		$this->add_control( 'posts_per_page', array( 'label' => 'تعداد مقاله', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '4', 'options' => array( '3' => '۳ مقاله', '4' => '۴ مقاله', '6' => '۶ مقاله', '8' => '۸ مقاله', '12' => '۱۲ مقاله' ), 'condition' => array( 'source' => 'latest' ) ) );
		$this->add_control( 'category_id', array( 'label' => 'دسته‌بندی', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 0, 'options' => $this->post_categories(), 'condition' => array( 'source' => 'latest' ) ) );
		$this->add_control( 'orderby', array( 'label' => 'مرتب‌سازی بر اساس', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'date', 'options' => array( 'date' => 'تاریخ انتشار', 'modified' => 'آخرین بروزرسانی', 'title' => 'عنوان', 'comment_count' => 'تعداد دیدگاه', 'rand' => 'تصادفی' ), 'condition' => array( 'source' => 'latest' ) ) );
		$this->add_control( 'order', array( 'label' => 'ترتیب', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => 'DESC', 'options' => array( 'DESC' => 'جدیدترین ابتدا', 'ASC' => 'قدیمی‌ترین ابتدا' ), 'condition' => array( 'source' => 'latest' ) ) );
		$this->add_control( 'excerpt_words', array( 'label' => 'طول خلاصه', 'type' => \Elementor\Controls_Manager::SELECT, 'default' => '18', 'options' => array( '12' => '۱۲ کلمه', '18' => '۱۸ کلمه', '24' => '۲۴ کلمه', '32' => '۳۲ کلمه' ), 'condition' => array( 'source' => 'latest' ) ) );
		$this->add_control( 'dynamic_badge', array( 'label' => 'برچسب کنار تصویر', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'مقاله جدید', 'condition' => array( 'source' => 'latest' ) ) );
		$this->add_control( 'dynamic_button_text', array( 'label' => 'متن دکمه مقالات', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'مطالعه مقاله', 'condition' => array( 'source' => 'latest' ) ) );
		$this->add_control( 'empty_message', array( 'label' => 'پیام نبود مقاله', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'هنوز مقاله‌ای منتشر نشده است.', 'condition' => array( 'source' => 'latest' ) ) );
		$this->end_controls_section();
		$this->start_controls_section( 'articles_section', array( 'label' => 'مقالات دستی', 'condition' => array( 'source' => 'manual' ) ) ); $r = new \Elementor\Repeater(); $r->add_control( 'image', array( 'label' => 'تصویر', 'type' => \Elementor\Controls_Manager::MEDIA ) ); foreach ( array( 'image_alt' => 'Alt تصویر', 'category' => 'دسته‌بندی', 'views' => 'برچسب کنار تصویر', 'title' => 'عنوان', 'excerpt' => 'خلاصه', 'date' => 'تاریخ', 'button_text' => 'متن دکمه' ) as $key => $label ) { $r->add_control( $key, array( 'label' => $label, 'type' => 'excerpt' === $key ? \Elementor\Controls_Manager::TEXTAREA : \Elementor\Controls_Manager::TEXT ) ); } $r->add_control( 'link', array( 'label' => 'لینک مقاله', 'type' => \Elementor\Controls_Manager::URL, 'show_external' => true ) );
		$this->add_control( 'articles', array( 'label' => 'فهرست مقالات دستی', 'type' => \Elementor\Controls_Manager::REPEATER, 'fields' => $r->get_controls(), 'default' => array(), 'title_field' => '{{{ title }}}', 'condition' => array( 'source' => 'manual' ) ) ); $this->end_controls_section(); $this->seo_controls( 'articles', 'آخرین مقالات سازان' ); $this->style_controls();
	}
}

final class Sazan_Homepage_Footer_Widget extends Sazan_Homepage_Section_Widget_Base {
	public function get_name() { return 'sazan-home-footer'; }
	public function get_title() { return 'سازان — فوتر صفحه اصلی'; }
	public function get_icon() { return 'eicon-footer'; }
	protected function section_key() { return 'footer'; }
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => 'دعوت به اقدام و تماس' ) );
		$this->add_control( 'kicker', array( 'label' => 'متن بالای عنوان', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'NEXT MOVE' ) );
		$this->add_control( 'title', array( 'label' => 'عنوان دعوت به اقدام', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'برای حرکت بعدی کسب‌وکارت آماده‌ای؟' ) );
		$this->add_control( 'description', array( 'label' => 'توضیح', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'یک گفت‌وگوی کوتاه می‌تواند مسیر درست را شفاف کند.' ) );
		$this->add_control( 'button_text', array( 'label' => 'متن دکمه', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'درخواست مشاوره' ) );
		$this->add_control( 'button_link', array( 'label' => 'لینک دکمه', 'type' => \Elementor\Controls_Manager::URL, 'default' => array( 'url' => '#consulting' ), 'show_external' => true ) );
		foreach ( array( 'contact_heading' => array( 'عنوان اطلاعات تماس', 'اطلاعات تماس' ), 'phone' => array( 'شماره تماس', '۰۹۱۲۶۰۸۲۸۷۶' ), 'email' => array( 'ایمیل', 'info@irsazan.com' ), 'address' => array( 'نشانی', 'اهواز، کیان آباد، نبش، خیابان سپهری، روبروی پمپ بنزین، ساختمان آسمان، طبقه ۶ واحد ۱۲' ), 'hours' => array( 'ساعات پاسخ‌گویی', 'شنبه تا پنجشنبه ۹ تا ۲۱' ), 'copyright' => array( 'متن کپی‌رایت', '© ۱۴۰۵ تمامی حقوق این وب‌سایت متعلق به سازان است.' ) ) as $key => $data ) { $this->add_control( $key, array( 'label' => $data[0], 'type' => 'address' === $key ? \Elementor\Controls_Manager::TEXTAREA : \Elementor\Controls_Manager::TEXT, 'default' => $data[1], 'label_block' => true ) ); }
		$this->add_control( 'category_heading', array( 'label' => 'عنوان ستون دسته‌بندی', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'دسته‌بندی‌ها' ) );
		$this->add_control( 'category_menu_id', array( 'label' => 'فهرست دسته‌بندی فوتر', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $this->menus(), 'default' => 0 ) );
		$this->add_control( 'quick_heading', array( 'label' => 'عنوان ستون دسترسی سریع', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'دسترسی سریع' ) );
		$this->add_control( 'quick_menu_id', array( 'label' => 'فهرست دسترسی سریع', 'type' => \Elementor\Controls_Manager::SELECT, 'options' => $this->menus(), 'default' => 0 ) );
		$this->add_control( 'about_heading', array( 'label' => 'عنوان درباره فوتر', 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'درباره سازان' ) );
		$this->add_control( 'about_text', array( 'label' => 'متن درباره فوتر', 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'سازان، مرکز آموزش و مشاوره تخصصی کسب‌وکار است. با ما مسیر رشد حرفه‌ای و هوشمندانه کسب‌وکار خود را بسازید.' ) );
		$this->end_controls_section(); $this->seo_controls( 'site-footer', 'پاورقی سایت', true ); $this->style_controls();
	}
}

function sazan_homepage_section_widget_classes() {
	return array( 'Sazan_Homepage_Header_Widget', 'Sazan_Homepage_Hero_Widget', 'Sazan_Homepage_Services_Widget', 'Sazan_Homepage_Courses_Widget', 'Sazan_Homepage_Consultation_Widget', 'Sazan_Homepage_About_Widget', 'Sazan_Homepage_Calendar_Widget', 'Sazan_Homepage_Articles_Widget', 'Sazan_Homepage_Footer_Widget' );
}
