<?php
/**
 * ویجت «خدمات ما» (sazan-services) — هم‌سبک با اسلایدر دوره:
 *   صحنه‌ی تیره + کارت‌های شیشه‌ای (گلس‌مورفیسم)، برندِ فیروزه‌ای، درخششِ برند،
 *   حباب‌های نرمِ نور (bokeh)، آیکنِ شیشه‌ای، اسپات‌لایتِ دنبال‌کننده‌ی موس روی کارت،
 *   دکمه‌ی هم‌سبکِ «بیشتر» و سرتیترِ کیکر + عنوان.
 *
 * خودکفا؛ جاوااسکریپتِ آن initServices در sazan-sections.js است.
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

class Services extends Widget_Base {

	public function get_name() { return 'sazan-services'; }
	public function get_title() { return esc_html__( 'خدمات ما سازان (شیشه‌ای)', 'sazan-core' ); }
	public function get_icon() { return 'eicon-icon-box'; }
	public function get_keywords() { return array( 'sazan', 'سازان', 'services', 'خدمات', 'کارت', 'باکس', 'glass', 'شیشه‌ای' ); }

	protected function register_controls() {

		/* ==================== محتوا: سرتیتر ==================== */
		$this->start_controls_section( 'sec_head', array( 'label' => esc_html__( 'سرتیتر', 'sazan-core' ) ) );
		$this->add_control( 'skin', array(
			'label' => esc_html__( '🎨 طرح نمایش', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'cards',
			'options' => array(
				'cards' => esc_html__( 'کارتی (پیش‌فرض)', 'sazan-core' ),
				'row'   => esc_html__( 'ردیفی/افقی (آیکن کنارِ متن)', 'sazan-core' ),
				'top'   => esc_html__( 'مینیمالِ وسط‌چین (خطِ رنگیِ بالا)', 'sazan-core' ),
			),
			'description' => esc_html__( 'طرحِ «ردیفی» در ۲ و ۳ ستون هم تمیز است (تعدادِ ستون را از بخشِ «خدمات» تنظیم کنید).', 'sazan-core' ),
			'separator' => 'after',
		) );
		$this->add_control( 'show_head', array( 'label' => esc_html__( 'نمایش سرتیتر', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ) );
		$this->add_control( 'kicker', array( 'label' => esc_html__( 'برچسب بالا (کیکر)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => 'OUR SERVICES', 'condition' => array( 'show_head' => 'yes' ) ) );
		$this->add_control( 'title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'خدمات ما', 'sazan-core' ), 'condition' => array( 'show_head' => 'yes' ) ) );
		$this->add_control( 'subtitle', array( 'label' => esc_html__( 'زیرعنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => esc_html__( 'راهکارهای تخصصیِ سازان برای رشدِ کسب‌وکار و توسعه‌ی فردی.', 'sazan-core' ), 'condition' => array( 'show_head' => 'yes' ) ) );
		$this->add_control( 'align', array(
			'label' => esc_html__( 'چینشِ سرتیتر', 'sazan-core' ), 'type' => Controls_Manager::CHOOSE, 'default' => 'center',
			'options' => array(
				'right'  => array( 'title' => esc_html__( 'راست', 'sazan-core' ), 'icon' => 'eicon-text-align-right' ),
				'center' => array( 'title' => esc_html__( 'وسط', 'sazan-core' ), 'icon' => 'eicon-text-align-center' ),
				'left'   => array( 'title' => esc_html__( 'چپ', 'sazan-core' ), 'icon' => 'eicon-text-align-left' ),
			),
			'selectors' => array( '{{WRAPPER}} .svc-head' => 'text-align: {{VALUE}};' ),
			'condition' => array( 'show_head' => 'yes' ),
		) );
		$this->end_controls_section();

		/* ==================== محتوا: خدمات ==================== */
		$this->start_controls_section( 'sec_items', array( 'label' => esc_html__( 'خدمات', 'sazan-core' ) ) );

		$rep = new Repeater();
		$rep->add_control( 'icon_type', array(
			'label' => esc_html__( 'نوعِ آیکن', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'preset',
			'options' => array( 'preset' => esc_html__( 'آیکنِ آماده', 'sazan-core' ), 'image' => esc_html__( 'تصویرِ دلخواه', 'sazan-core' ) ),
		) );
		$rep->add_control( 'icon', array(
			'label' => esc_html__( 'آیکن', 'sazan-core' ), 'type' => Controls_Manager::SELECT, 'default' => 'consult',
			'options' => $this->icon_options(), 'condition' => array( 'icon_type' => 'preset' ),
		) );
		$rep->add_control( 'icon_img', array( 'label' => esc_html__( 'تصویرِ آیکن', 'sazan-core' ), 'type' => Controls_Manager::MEDIA, 'condition' => array( 'icon_type' => 'image' ) ) );
		$rep->add_control( 'badge', array( 'label' => esc_html__( 'برچسبِ کوچک (اختیاری)', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => '' ) );
		$rep->add_control( 'title', array( 'label' => esc_html__( 'عنوان', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'عنوان خدمت', 'sazan-core' ) ) );
		$rep->add_control( 'desc', array( 'label' => esc_html__( 'توضیح', 'sazan-core' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => esc_html__( 'توضیح کوتاه درباره‌ی این خدمت.', 'sazan-core' ) ) );
		$rep->add_control( 'btn_text', array( 'label' => esc_html__( 'متن دکمه', 'sazan-core' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'بیشتر بدانید', 'sazan-core' ) ) );
		$rep->add_control( 'link', array( 'label' => esc_html__( 'لینک', 'sazan-core' ), 'type' => Controls_Manager::URL, 'default' => array( 'url' => '#' ) ) );

		$this->add_control( 'items', array(
			'label' => esc_html__( 'خدمات', 'sazan-core' ), 'type' => Controls_Manager::REPEATER,
			'fields' => $rep->get_controls(), 'title_field' => '{{{ title }}}',
			'default' => array(
				array( 'icon' => 'consult', 'title' => 'مشاوره کسب‌وکار', 'desc' => 'مشاوره‌ی تخصصی و نقشه‌ی راهِ اختصاصی برای رشدِ پایدارِ کسب‌وکار شما.', 'badge' => 'پرطرفدار', 'btn_text' => 'بیشتر بدانید' ),
				array( 'icon' => 'course', 'title' => 'دوره‌های آموزشی', 'desc' => 'دوره‌ها و کارگاه‌های کاربردیِ حضوری و آنلاین با مدرک معتبر.', 'btn_text' => 'بیشتر بدانید' ),
				array( 'icon' => 'book', 'title' => 'مرکز دانش', 'desc' => 'دسترسی به مقاله‌ها، ویدیوها، پادکست‌ها و منابعِ آموزشیِ به‌روز.', 'btn_text' => 'بیشتر بدانید' ),
				array( 'icon' => 'growth', 'title' => 'رشد و توسعه‌ی فردی', 'desc' => 'برنامه‌های توانمندسازی و کوچینگ برای پیشرفتِ شغلی و شخصی.', 'btn_text' => 'بیشتر بدانید' ),
				array( 'icon' => 'team', 'title' => 'منابع انسانی', 'desc' => 'طراحیِ ساختار، جذب، ارزیابی و آموزشِ تیم‌های حرفه‌ای.', 'btn_text' => 'بیشتر بدانید' ),
				array( 'icon' => 'support', 'title' => 'پشتیبانی و همراهی', 'desc' => 'پشتیبانیِ تخصصی و همراهیِ گام‌به‌گام تا رسیدن به نتیجه.', 'badge' => 'جدید', 'btn_text' => 'بیشتر بدانید' ),
			),
		) );

		$this->add_responsive_control( 'columns', array(
			'label' => esc_html__( 'تعداد ستون', 'sazan-core' ), 'type' => Controls_Manager::SELECT,
			'default' => '3', 'tablet_default' => '2', 'mobile_default' => '1',
			'options' => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
			'selectors' => array( '{{WRAPPER}} .svc-grid' => 'grid-template-columns: repeat({{VALUE}},1fr);' ),
		) );
		$this->add_responsive_control( 'gap', array(
			'label' => esc_html__( 'فاصله بین کارت‌ها', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 8, 'max' => 60 ) ),
			'default' => array( 'unit' => 'px', 'size' => 22 ),
			'selectors' => array( '{{WRAPPER}} .svc-grid' => 'gap: {{SIZE}}{{UNIT}};' ),
		) );
		$this->end_controls_section();

		/* ==================== استایل: رنگ‌ها ==================== */
		$this->start_controls_section( 'sty_colors', array( 'label' => esc_html__( 'رنگ‌ها', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'c_accent', array( 'label' => esc_html__( 'رنگ برند', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#00B6F1', 'selectors' => array( '{{WRAPPER}} .sazan-services' => '--svc-accent: {{VALUE}};' ) ) );
		$this->add_control( 'c_accent2', array( 'label' => esc_html__( 'رنگ برند ۲ (گرادیان)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0090c4', 'selectors' => array( '{{WRAPPER}} .sazan-services' => '--svc-accent2: {{VALUE}};' ) ) );
		$this->add_control( 'c_bg', array( 'label' => esc_html__( 'پس‌زمینه‌ی بخش', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#0a161f', 'selectors' => array( '{{WRAPPER}} .sazan-services' => '--svc-bg: {{VALUE}};' ) ) );
		$this->add_control( 'c_bg2', array( 'label' => esc_html__( 'پس‌زمینه‌ی بخش (گرادیان)', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#12283a', 'selectors' => array( '{{WRAPPER}} .sazan-services' => '--svc-bg2: {{VALUE}};' ) ) );
		$this->add_control( 'c_card', array( 'label' => esc_html__( 'ته‌رنگِ کارت', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(255,255,255,0.05)', 'selectors' => array( '{{WRAPPER}} .sazan-services' => '--svc-card: {{VALUE}};' ) ) );
		$this->add_control( 'c_text', array( 'label' => esc_html__( 'رنگ متن', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#ffffff', 'selectors' => array( '{{WRAPPER}} .sazan-services' => '--svc-text: {{VALUE}};' ) ) );
		$this->add_control( 'c_mut', array( 'label' => esc_html__( 'رنگ متن کم‌رنگ', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => '#9fb2c0', 'selectors' => array( '{{WRAPPER}} .sazan-services' => '--svc-mut: {{VALUE}};' ) ) );
		$this->add_control( 'c_bd', array( 'label' => esc_html__( 'رنگ لبه‌ی کارت', 'sazan-core' ), 'type' => Controls_Manager::COLOR, 'default' => 'rgba(255,255,255,0.12)', 'selectors' => array( '{{WRAPPER}} .sazan-services' => '--svc-bd: {{VALUE}};' ) ) );
		$this->end_controls_section();

		/* ==================== استایل: پس‌زمینه و افکت‌ها ==================== */
		$this->start_controls_section( 'sty_fx', array( 'label' => esc_html__( '✨ پس‌زمینه و افکت‌ها', 'sazan-core' ), 'tab' => Controls_Manager::TAB_STYLE ) );
		$this->add_control( 'scene', array( 'label' => esc_html__( 'پس‌زمینه‌ی تیره‌ی بخش', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'description' => esc_html__( 'یک پنلِ تیره با درخششِ برند پشتِ کارت‌ها.', 'sazan-core' ) ) );
		$this->add_control( 'bokeh', array( 'label' => esc_html__( 'حباب‌های نرمِ نور (bokeh)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'condition' => array( 'scene' => 'yes' ) ) );
		$this->add_control( 'glass', array( 'label' => esc_html__( 'کارت شیشه‌ای (بلر)', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'separator' => 'before' ) );
		$this->add_control( 'glass_blur', array(
			'label' => esc_html__( 'شدتِ بلر', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 30 ) ),
			'default' => array( 'unit' => 'px', 'size' => 14 ), 'condition' => array( 'glass' => 'yes' ),
			'selectors' => array( '{{WRAPPER}} .sazan-services' => '--svc-blur: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_control( 'spotlight', array( 'label' => esc_html__( '🖱️ اسپات‌لایتِ دنبال‌کننده‌ی موس', 'sazan-core' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes', 'separator' => 'before' ) );
		$this->add_responsive_control( 'card_w', array(
			'label' => esc_html__( 'حداکثر پهنای کارت (باریک‌تر)', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 220, 'max' => 460 ) ),
			'default' => array( 'unit' => 'px', 'size' => 300 ), 'separator' => 'before',
			'selectors' => array( '{{WRAPPER}} .sazan-services' => '--svc-cardw: {{SIZE}}{{UNIT}};' ),
			'description' => esc_html__( 'کوچک‌ترش کنید تا کارت‌ها باریک‌تر و جمع‌وجورتر شوند (در ستون وسط‌چین می‌مانند).', 'sazan-core' ),
		) );
		$this->add_responsive_control( 'radius', array(
			'label' => esc_html__( 'گردی گوشه‌ی کارت', 'sazan-core' ), 'type' => Controls_Manager::SLIDER,
			'size_units' => array( 'px' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
			'default' => array( 'unit' => 'px', 'size' => 20 ),
			'selectors' => array( '{{WRAPPER}} .sazan-services' => '--svc-radius: {{SIZE}}{{UNIT}};' ),
		) );
		$this->add_group_control( Group_Control_Typography::get_type(), array(
			'name' => 'tg_title', 'label' => esc_html__( 'تایپوگرافی عنوانِ کارت', 'sazan-core' ), 'selector' => '{{WRAPPER}} .svc-card h3',
		) );
		$this->end_controls_section();
	}

	/** فهرستِ آیکن‌های آماده. */
	private function icon_options() {
		return array(
			'consult'   => esc_html__( 'مشاوره', 'sazan-core' ),
			'course'    => esc_html__( 'آموزش/دوره', 'sazan-core' ),
			'book'      => esc_html__( 'دانش/کتاب', 'sazan-core' ),
			'growth'    => esc_html__( 'رشد/نمودار', 'sazan-core' ),
			'team'      => esc_html__( 'تیم/منابع انسانی', 'sazan-core' ),
			'support'   => esc_html__( 'پشتیبانی', 'sazan-core' ),
			'rocket'    => esc_html__( 'راه‌اندازی/موشک', 'sazan-core' ),
			'cert'      => esc_html__( 'مدرک/گواهی', 'sazan-core' ),
			'target'    => esc_html__( 'هدف', 'sazan-core' ),
			'idea'      => esc_html__( 'ایده', 'sazan-core' ),
			'briefcase' => esc_html__( 'کسب‌وکار', 'sazan-core' ),
			'chat'      => esc_html__( 'گفتگو', 'sazan-core' ),
			'shield'    => esc_html__( 'اعتماد/امنیت', 'sazan-core' ),
			'chart'     => esc_html__( 'تحلیل', 'sazan-core' ),
		);
	}

	/** SVG آیکنِ آماده بر اساس توکن. */
	private function icon_svg( $token ) {
		switch ( $token ) {
			case 'consult':   $p = '<path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7A8.38 8.38 0 0 1 4 11.5 8.5 8.5 0 0 1 12.5 3 8.38 8.38 0 0 1 21 11.5z"/><path d="M8.5 11.5h7M8.5 8.5h4"/>'; break;
			case 'course':    $p = '<path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M7 9.5V15c0 1.5 2.5 3 5 3s5-1.5 5-3V9.5"/><path d="M21 7v6"/>'; break;
			case 'book':      $p = '<path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2z"/><path d="M4 19a2 2 0 0 1 2-2h12"/><path d="M9 7h6"/>'; break;
			case 'growth':    $p = '<path d="M3 20V4"/><path d="M3 20h18"/><path d="M7 16l4-5 3 3 5-7"/><path d="M19 7v4h-4"/>'; break;
			case 'team':      $p = '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 5.5a3 3 0 0 1 0 5M21 20a6 6 0 0 0-4-5.6"/>'; break;
			case 'support':   $p = '<path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-6H6a2 2 0 0 0-2 2zM20 13v3a2 2 0 0 1-2 2h-3"/><circle cx="12" cy="20" r="1.4"/>'; break;
			case 'rocket':    $p = '<path d="M5 15c-1.5 1.5-2 5-2 5s3.5-.5 5-2"/><path d="M14 4c3 0 6 3 6 6-2 5-6 7-9 8l-5-5c1-3 3-7 8-9z"/><circle cx="14.5" cy="9.5" r="1.6"/>'; break;
			case 'cert':      $p = '<circle cx="12" cy="9" r="5"/><path d="M9 13l-1 8 4-2 4 2-1-8"/>'; break;
			case 'target':    $p = '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.6"/>'; break;
			case 'idea':      $p = '<path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 1 4 10.5c-.6.6-1 1.3-1 2.1H9c0-.8-.4-1.5-1-2.1A6 6 0 0 1 12 3z"/>'; break;
			case 'briefcase': $p = '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/>'; break;
			case 'chat':      $p = '<path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7A8.38 8.38 0 0 1 4 11.5 8.5 8.5 0 0 1 12.5 3 8.38 8.38 0 0 1 21 11.5z"/>'; break;
			case 'shield':    $p = '<path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6z"/><path d="M9 12l2 2 4-4"/>'; break;
			case 'chart':     $p = '<path d="M4 20V4"/><path d="M4 20h16"/><rect x="7" y="12" width="3" height="5"/><rect x="12" y="8" width="3" height="9"/><rect x="17" y="5" width="3" height="12"/>'; break;
			default:          $p = '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>'; break;
		}
		return '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = array_values( (array) ( $s['items'] ?? array() ) );
		if ( empty( $items ) ) { return; }

		$skin = in_array( ( $s['skin'] ?? 'cards' ), array( 'cards', 'row', 'top' ), true ) ? $s['skin'] : 'cards';
		$cls  = 'sazan-services skin-' . $skin;
		if ( 'yes' === ( $s['scene'] ?? 'yes' ) )     { $cls .= ' has-scene'; }
		if ( 'yes' === ( $s['bokeh'] ?? 'yes' ) )     { $cls .= ' has-bokeh'; }
		if ( 'yes' === ( $s['glass'] ?? 'yes' ) )     { $cls .= ' is-glass'; }
		if ( 'yes' === ( $s['spotlight'] ?? 'yes' ) ) { $cls .= ' has-spot'; }

		echo '<div class="' . esc_attr( $cls ) . '">';
		echo '<div class="svc-inner">';

		/* بوکه/درخشش پس‌زمینه */
		echo '<span class="svc-fx" aria-hidden="true"></span>';

		/* سرتیتر */
		if ( 'yes' === ( $s['show_head'] ?? 'yes' ) ) {
			echo '<div class="svc-head">';
			if ( ! empty( $s['kicker'] ) ) { echo '<span class="svc-kicker">' . esc_html( $s['kicker'] ) . '</span>'; }
			if ( ! empty( $s['title'] ) )  { echo '<h2 class="svc-title">' . esc_html( $s['title'] ) . '</h2>'; }
			if ( ! empty( $s['subtitle'] ) ) { echo '<p class="svc-sub">' . esc_html( $s['subtitle'] ) . '</p>'; }
			echo '</div>';
		}

		/* شبکه‌ی خدمات */
		echo '<div class="svc-grid">';
		foreach ( $items as $idx => $it ) {
			echo '<article class="svc-card" style="--svc-i:' . esc_attr( $idx ) . '">';
			echo '<span class="svc-glow" aria-hidden="true"></span>';

			if ( ! empty( $it['badge'] ) ) {
				echo '<span class="svc-badge">' . esc_html( $it['badge'] ) . '</span>';
			}

			/* آیکن */
			echo '<span class="svc-ic">';
			if ( 'image' === ( $it['icon_type'] ?? 'preset' ) && ! empty( $it['icon_img']['url'] ) ) {
				echo '<img src="' . esc_url( $it['icon_img']['url'] ) . '" alt="" loading="lazy">';
			} else {
				echo $this->icon_svg( $it['icon'] ?? 'consult' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</span>';

			echo '<div class="svc-body">';
			if ( ! empty( $it['title'] ) ) { echo '<h3>' . esc_html( $it['title'] ) . '</h3>'; }
			if ( ! empty( $it['desc'] ) )  { echo '<p>' . esc_html( $it['desc'] ) . '</p>'; }

			if ( ! empty( $it['btn_text'] ) ) {
				$href     = ! empty( $it['link']['url'] ) ? $it['link']['url'] : '#';
				$target   = ! empty( $it['link']['is_external'] ) ? ' target="_blank"' : '';
				$nofollow = ! empty( $it['link']['nofollow'] ) ? ' rel="nofollow"' : '';
				echo '<a class="svc-btn" href="' . esc_url( $href ) . '"' . $target . $nofollow . '>' . esc_html( $it['btn_text'] )
					. '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg></a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>'; // body

			echo '</article>';
		}
		echo '</div>'; // grid

		echo '</div>'; // inner
		echo '</div>'; // services
	}
}
